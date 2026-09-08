<?php
declare(strict_types=1);

/**
 *	Nino
 *	protected-smoke.php	Contract test for the Protected feature
 *												(\Nino\Modules\ProtectedArea): the manifest and the
 *												activation through \Nino\Features, protects() against
 *												configured prefixes, the gate that replaces a locked
 *												visitor's response with the password form and excludes
 *												protected prefixes from the full-page cache, unlocking
 *												with its per-ip attempt cap, an unsafe 'return' falling
 *												back to '/', locking again, the [protected-logout]
 *												shortcode, an empty password leaving the feature inert,
 *												and deactivation. Travels with the feature and runs
 *												against the checkout three levels up, or the one
 *												NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/ProtectedArea/tests/protected-smoke.php
 *	       NINO_ROOT=../nino php features/ProtectedArea/tests/protected-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names. The features root
// is this feature's own parent either way, so the kernel's autoloader serves
// the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'protected' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Needed for a fill to actually resolve through renderHtml() below - a bare
// ninoSandbox() never sets this (AppData::init() would, but nothing here
// calls it; see \Nino\Html::getFills()), and without it the merged install
// text under /text/<locale>.php would never be found
$appData['/nino/locales/textfiles'] = '/text';

/**
 *	Drive the gate the way \Nino\Http::response() would call it, with a
 *	hand-built request already carrying a resolved route's body/status - the
 *	shape kernel-smoke.php's Http::request/response section and Modules\Form's
 *	own callback tests use
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$body					What the resolved route would have answered
 *
 *	@return 	array
 */
function protectedGate( array &$appData, string $uri, string $body = 'ORIGINAL PAGE' ): array {
	$request = [
		'/nino/http/request'	=> [ 'uri' => $uri ],
		'/nino/http/response'	=> [ 'statusCode' => 200, 'body' => $body, 'header' => [] ],
	];
	\Nino\Modules\ProtectedArea::callbackGate( $appData, $request );
	return $request;
}

/**
 *	Drive POST /.protected the way Modules\Form's own tests drive
 *	POST /.form: $_POST filled by hand, the route callback called directly
 *
 *	@param		array 		&$appData
 *	@param		array 		$post
 *
 *	@return 	array
 */
function protectedUnlock( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'password' => '', 'return' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200, 'header' => [] ] ];
	\Nino\Modules\ProtectedArea::callbackUnlock( $appData, $request );
	return $request;
}

/**
 *	@param		array 		&$appData
 *
 *	@return 	array
 */
function protectedLogout( array &$appData ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200, 'header' => [] ] ];
	\Nino\Modules\ProtectedArea::callbackLogout( $appData, $request );
	return $request;
}


// --- The feature - manifest and activation ------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "protected"', is_array( $manifest ) && $manifest['key'] === 'protected' && ninoWarnings() === [] );
check( 'key, class and version are what the directory says', is_array( $manifest ) && $manifest['module'] === '\\Nino\\Modules\\ProtectedArea' && $manifest['version'] === '1.0.0' );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it declares the data file its attempt cap writes', is_array( $manifest ) && $manifest['data'] === [ '/data/protected.php' ] );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it, inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'protected' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'protected' ) === true );

$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\ProtectedArea', $stored['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'protected' )['installed'] === $manifest['version'] );
check( 'the unit copied the password form template, add-only', is_file( \Nino\Filesystem::path( $appData, '/templates/page-protected.tpl' ) ) === true );
check( 'the unit merged the texts for both locales', \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/protected/label/submit]]'] === 'Entsperren'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/protected/label/submit]]'] === 'Unlock' );
check( 'the settings answer their defaults - inert until a password is set', \Nino\Features::settings( $appData, 'protected' ) === [ 'paths' => [], 'password' => '', 'attempts' => 5 ] );

echo "\n";


// --- protects() ----------------------------------------------------------------

echo "Modules\\ProtectedArea::protects - configured prefixes, sub-paths, unrelated paths\n";

\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => [ '/intern' ] ] );
check( 'nothing is protected while the password is still empty, even with paths configured', \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === false );

check( 'a posted settings form is validated and typed', \Nino\Features::saveSettings( $appData, 'protected', [
	'paths'			=> [ '/intern', '/preview/client-a', '/_admin', '/.protected', '' ],
	'password'	=> 'sesam-öffne-dich',
	'attempts'	=> '3',
] ) === [] );
check( 'attempts came back as an int, bounded and stored', \Nino\Features::setting( $appData, 'protected', 'attempts' ) === 3 );

check( 'a /_admin entry never became a protected prefix (the tools)', \Nino\Modules\ProtectedArea::protects( $appData, '/_admin' ) === false );
check( 'a /.protected entry never became a protected prefix either (module endpoints)', \Nino\Modules\ProtectedArea::protects( $appData, '/.protected' ) === false );
check( 'protects() the prefix itself', \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === true );
check( 'protects() a sub-path below it', \Nino\Modules\ProtectedArea::protects( $appData, '/intern/notes/2026' ) === true );
check( 'protects() a second configured prefix and its own sub-paths', \Nino\Modules\ProtectedArea::protects( $appData, '/preview/client-a/draft' ) === true );
check( 'a merely similarly-named uri is not protected - no shared path boundary', \Nino\Modules\ProtectedArea::protects( $appData, '/internal' ) === false );
check( 'an unrelated uri is not protected', \Nino\Modules\ProtectedArea::protects( $appData, '/about' ) === false );
check( 'the site root is not protected', \Nino\Modules\ProtectedArea::protects( $appData, '/' ) === false );

echo "\n";


// --- init() - routes, the gate's priority, the cache blacklist -----------------

echo "Modules\\ProtectedArea::init - routes, the gate's priority, the cache blacklist\n";

\Nino\Modules::callModules( $appData, 'init' );

check( 'init registers the unlock route', isset( $appData['/nino/http/routes']['POST://.protected'] ) === true );
check( 'init registers the logout route', isset( $appData['/nino/http/routes']['GET://.protected/logout'] ) === true );
check( 'the gate is registered at priority 1, before Modules\\Cache\'s 9', count( $appData['./nino/callbacks']['/nino/http/response'][1] ?? [] ) > 0 );

$blacklist = (array) ( $appData['/nino/cache/blacklist'] ?? [] );
check( 'the first protected prefix extends the cache blacklist as itself and as a subtree', in_array( '/intern', $blacklist, true ) === true
	&& in_array( '/intern/*', $blacklist, true ) === true );
check( 'the second one does too', in_array( '/preview/client-a', $blacklist, true ) === true
	&& in_array( '/preview/client-a/*', $blacklist, true ) === true );

echo "\n";


// --- The gate --------------------------------------------------------------

echo "Modules\\ProtectedArea::callbackGate - the password form instead of the page\n";

check( 'the session starts locked', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );

$lockedRequest = protectedGate( $appData, '/intern' );
check( 'a protected uri without an unlocked session gets the password form (401)', $lockedRequest['/nino/http/response']['statusCode'] === 401
	&& $lockedRequest['/nino/http/response']['body'] === '[template /templates/page-protected]' );
check( 'the response carries Cache-Control: no-store', ( $lockedRequest['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store' );
check( 'the current uri is carried into the return fill', ( $appData['./nino/html/fills']['*']['[[/protected/return]]'] ?? '' ) === '/intern' );

$unprotectedRequest = protectedGate( $appData, '/about', 'PUBLIC PAGE' );
check( 'an unprotected uri is left untouched', $unprotectedRequest['/nino/http/response']['statusCode'] === 200
	&& $unprotectedRequest['/nino/http/response']['body'] === 'PUBLIC PAGE' );

echo "\n";


// --- Unlocking - wrong password ------------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - a wrong password\n";

$wrongRequest = protectedUnlock( $appData, [ 'password' => 'not-the-password', 'return' => '/intern/notes' ] );
check( 'a wrong password answers 401 and re-renders the form', $wrongRequest['/nino/http/response']['statusCode'] === 401
	&& $wrongRequest['/nino/http/response']['body'] === '[template /templates/page-protected]' );
check( 'the wrong-password error resolves through [protected-error]', \Nino\Html::renderHtml( $appData, '[protected-error]' ) === '<p class="nino-protected-error">Falsches Passwort. Bitte versuchen Sie es erneut.</p>' );
check( 'the posted return path is carried back into the hidden field', ( $appData['./nino/html/fills']['*']['[[/protected/return]]'] ?? '' ) === '/intern/notes' );
check( 'the session is still locked', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
check( 'one wrong attempt was recorded for this ip', \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )['127.0.0.1']['tries'] === 1 );
check( 'the counter lives under /data/protected.php, as the manifest says', is_file( \Nino\Filesystem::path( $appData, '/data/protected.php' ) ) === true );

echo "\n";


// --- Unlocking - the right password --------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - the right password\n";

$_SERVER['REMOTE_ADDR'] = '198.51.100.10';

$rightRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/intern/notes' ] );
check( 'the right password unlocks and redirects (303)', $rightRequest['/nino/http/response']['statusCode'] === 303
	&& ( $rightRequest['/nino/http/response']['header']['Location'] ?? '' ) === '/intern/notes' );
check( 'the session now reads unlocked', \Nino\Modules\ProtectedArea::unlocked( $appData ) === true );

$passedRequest = protectedGate( $appData, '/intern' );
check( 'the protected GET now passes through untouched', $passedRequest['/nino/http/response']['statusCode'] === 200
	&& $passedRequest['/nino/http/response']['body'] === 'ORIGINAL PAGE' );

echo "\n";


// --- An unsafe return path -----------------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - a 'return' that is not a local path\n";

$_SERVER['REMOTE_ADDR'] = '198.51.100.11';

$hostileReturnRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => 'https://evil.example/phish' ] );
check( 'a return with a scheme is replaced by /', ( $hostileReturnRequest['/nino/http/response']['header']['Location'] ?? '' ) === '/' );

$protocolRelativeReturnRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '//evil.example/phish' ] );
check( 'a protocol-relative return is replaced by / too', ( $protocolRelativeReturnRequest['/nino/http/response']['header']['Location'] ?? '' ) === '/' );

$injectedReturnRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => "/intern\r\nSet-Cookie: x=1" ] );
check( 'a return carrying a line break or control character is replaced by /', ( $injectedReturnRequest['/nino/http/response']['header']['Location'] ?? '' ) === '/' );

echo "\n";


// --- The attempt cap ------------------------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - the attempt cap (3 per hour, from settings)\n";

$_SERVER['REMOTE_ADDR'] = '198.51.100.12';

for( $i = 1; $i <= 3; $i++ ) {
	$capRequest = protectedUnlock( $appData, [ 'password' => 'still-not-the-password', 'return' => '/intern' ] );
	check( "wrong attempt $i of 3 is answered as wrong (401)", $capRequest['/nino/http/response']['statusCode'] === 401 );
}

check( 'three wrong tries are on file for this ip', \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )['198.51.100.12']['tries'] === 3 );

$cappedRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/intern' ] );
check( 'the next attempt is refused (429) even with the right password', $cappedRequest['/nino/http/response']['statusCode'] === 429 );
check( 'the locked error, not the wrong-password one, is what [protected-error] now shows', \Nino\Html::renderHtml( $appData, '[protected-error]' ) === '<p class="nino-protected-error">Zu viele falsche Versuche. Bitte versuchen Sie es in einer Stunde erneut.</p>' );

echo "\n";


// --- Locking again ---------------------------------------------------------------

echo "Modules\\ProtectedArea::callbackLogout / [protected-logout]\n";

$logoutRequest = protectedLogout( $appData );
check( 'logout redirects home (303)', $logoutRequest['/nino/http/response']['statusCode'] === 303
	&& ( $logoutRequest['/nino/http/response']['header']['Location'] ?? '' ) === '/' );
check( 'logout unsets the session', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );

$lockedAgainRequest = protectedGate( $appData, '/intern' );
check( 'the gate closes again after logout', $lockedAgainRequest['/nino/http/response']['statusCode'] === 401 );

\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );
check( '[protected-logout] renders nothing while locked', \Nino\Html::renderHtml( $appData, '[protected-logout]' ) === '' );

\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', true );
check( '[protected-logout] renders the link while unlocked', \Nino\Html::renderHtml( $appData, '[protected-logout]' ) === '<a href="/.protected/logout" class="nino-protected-logout">Diesen Bereich wieder sperren</a>' );
\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );

echo "\n";


// --- An empty password leaves the feature inert ----------------------------------

echo "An empty password protects nothing\n";

check( 'paths are still configured at this point', \Nino\Features::setting( $appData, 'protected', 'paths' ) !== [] );
check( 'clearing the password is accepted', \Nino\Features::saveSettings( $appData, 'protected', [ 'password' => null ] ) === [] );
check( 'an empty password setting protects nothing, even with paths configured', \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === false );

echo "\n";


// --- Deactivation ------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'protected' ) === true );
check( 'the class is removed from /nino/modules', in_array( '\\Nino\\Modules\\ProtectedArea', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the settings and the attempt counter survive deactivation', \Nino\Features::setting( $appData, 'protected', 'attempts' ) === 3
	&& is_file( \Nino\Filesystem::path( $appData, '/data/protected.php' ) ) === true );
check( 'the copied template survives deactivation too', is_file( \Nino\Filesystem::path( $appData, '/templates/page-protected.tpl' ) ) === true );

ninoDone( $appData );
