<?php
declare(strict_types=1);

/**
 *	Nino
 *	protected-smoke.php	Contract test for the Protected area feature
 *												(\Nino\Modules\ProtectedArea): the manifest and the
 *												activation through \Nino\Features, protects() against
 *												configured prefixes, the gate that replaces a locked
 *												visitor's response with the password form and excludes
 *												protected prefixes from the full-page cache, unlocking
 *												with its per-ip attempt cap - eight real processes
 *												posting at once among them, since a burst is what a
 *												cap has to survive - an unsafe 'return' falling back
 *												to '/', locking again, the [protected-logout]
 *												shortcode, the session epoch - a sign-out and a new
 *												password locking every session at once, a session
 *												from before the epoch kept - the Seo exclusion, the
 *												password form leaving the contact-form script alone,
 *												an empty password leaving the feature inert, the
 *												panel (password, pages, sign-out, its guards),
 *												deactivation, and - where node is on the path -
 *												the panel script's own test. Travels with the feature and runs
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

/*	Worker mode: this same file re-entered as its own process, pointed at a
	sandbox somebody else built, to post one wrong password and print the
	status it was answered with. See "the cap under real concurrency" below -
	flock() is per open file description, so a single process can satisfy a
	lock by accident and proves nothing about one	*/
if( ( $argv[1] ?? '' ) === 'unlock-worker' ) {

	$worker = [ './nino/uid' => $argv[2] ];
	\Nino\AppData::prepare( $worker );
	$worker['./nino/filesystem/path']					= $argv[2];
	$worker['./nino/filesystem/configpath']		= $argv[2]. '/private';
	$worker['./nino/filesystem/contentpath']	= $argv[2]. '/private';
	$worker['./nino/filesystem/publicpath']		= $argv[2]. '/public';
	\Nino\AppData::init( $worker );

	$_SERVER['REMOTE_ADDR']	= $argv[3];
	$_POST									= [ 'password' => 'still-not-the-password', 'return' => '/intern' ];
	$workerRequest					= [ '/nino/http/response' => [ 'statusCode' => 200, 'header' => [] ] ];

	// Every worker waits for the same moment before posting, booting done.
	// Started one after the other and left to run, the first is finished
	// before the last has begun - eight posts that never overlap, which is
	// the one thing this section is here to arrange
	while( microtime( true ) < (float) ( $argv[4] ?? 0 ) )
		usleep( 200 );

	\Nino\Modules\ProtectedArea::callbackUnlock( $worker, $workerRequest );

	echo $workerRequest['/nino/http/response']['statusCode'];
	exit( 0 );
}

$appData = ninoSandbox( 'protected' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Needed for a fill to actually resolve through renderHtml() below - a bare
// ninoSandbox() never sets this (AppData::init() would, but nothing here
// calls it; see \Nino\Html::getFills()), and without it the merged install
// text under /text/<locale>.php would never be found
$appData['/nino/locales/textfiles'] = '/text';

// The project directory and its fill, which \Nino\request() registers from
// the same key on every request - empty here, the way a site at the domain
// root has it; the subdirectory section at the end sets both to /sub
$appData['/nino/dir'] = '';
\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '' ], '*' );

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
check( 'it declares the data files its attempt cap and its session epoch write', is_array( $manifest ) && $manifest['data'] === [ '/data/protected.php', '/data/protected-session.php' ] );
check( 'its manual names the panel and the Seo callback', is_array( $manifest ) && isset( $manifest['manual']['panel']['Protected area'] ) === true
	&& isset( $manifest['manual']['callbacks']['/seo/exclude'] ) === true );

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

/*	The kernel's script binds every .nino-form on a page to the contact-form
	request: it prevents the native submit, posts by XHR and writes the answer
	into the form's first <p>, of which this one has none. With javascript on, a
	visitor was never taken to the page and a wrong password showed nothing.
	Not a class this form carries, whatever else it does - the same word as a
	prefix of nino-form-input is another thing	*/
$formTemplate = (string) file_get_contents( $dir. '/install/templates/page-protected.tpl' );
preg_match( '/<form\b[^>]*>/', $formTemplate, $formTag );
check( 'the password form is a form of its own, not one the contact-form script binds', ( $formTag[0] ?? '' ) !== ''
	&& preg_match( '/(?<![\w-])nino-form(?![\w-])/', $formTag[0] ) === 0 );
check( '...and it still posts to the endpoint, with the csrf token and the return path', str_contains( $formTemplate, 'action="[[/nino/dir]]/.protected" method="post"' ) === true
	&& str_contains( $formTemplate, '[csrf]' ) === true && str_contains( $formTemplate, 'name="return"' ) === true );
check( 'the unit merged the texts for both locales', \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/protected/label/submit]]'] === 'Entsperren'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/protected/label/submit]]'] === 'Unlock' );
check( 'the settings answer their defaults - inert until a password is set', \Nino\Features::settings( $appData, 'protected' ) === [ 'paths' => [], 'password' => '', 'attempts' => 5 ] );

/*	The page template carries [[/protected/return]], which the gate fills at
	request time and no text file answers. The Text panel's scan for missing
	keys reads the templates as source, so without the unit's blacklist entry
	it reported that key - and the Dashboard counted it - on every project
	with this feature, as a gap nobody could close	*/
\Nino\Auth::insertUser( $appData, 'scan@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'scan@example.com', 'correct horse battery staple' );
$_POST = [ 'action' => 'keys/scan', 'data' => '{}' ];
$scanRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Admin\Admin::handlePost( $appData, $scanRequest );
$reported = array_column( (array) ( $scanRequest['/nino/http/response']['body']['missing'] ?? [] ), 'key' );
check( 'the Text panel\'s scan reports no key of this feature: what the template uses is in the text files or in the blacklist', $scanRequest['/nino/http/response']['statusCode'] === 200
	&& array_filter( $reported, static fn( string $key ): bool => str_starts_with( $key, '/protected/' ) === true ) === [] );
\Nino\Auth::logoutUser( $appData );
$_POST = [];

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

// What the Seo feature asks, by the literal string - nothing here requires it
$seoExcluded = [];
\Nino\Callbacks::doCallbacks( $appData, '/seo/exclude', $seoExcluded );
check( 'init answers /seo/exclude with every protected prefix and its subtree while a password is set', $seoExcluded === [ '/intern/*', '/preview/client-a/*' ] );

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
check( 'the wrong-password error resolves through [protected-error]', \Nino\Html::renderHtml( $appData, '[protected-error]' ) === '<p class="nino-protected-error">Falsches Passwort. Bitte versuche es erneut.</p>' );
check( 'the posted return path is carried back into the hidden field', ( $appData['./nino/html/fills']['*']['[[/protected/return]]'] ?? '' ) === '/intern/notes' );
check( 'the session is still locked', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
check( 'one wrong attempt was recorded for this ip', \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )['127.0.0.1']['tries'] === 1 );
check( 'the counter lives under /data/protected.php, as the manifest says', is_file( \Nino\Filesystem::path( $appData, '/data/protected.php' ) ) === true );

echo "\n";


// --- Unlocking - the right password --------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - the right password\n";

$_SERVER['REMOTE_ADDR'] = '198.51.100.10';

// A wrong try first, so the unlock below has something to clear. Every
// attempt is claimed against the cap now, the right one included - the claim
// has to happen before the password is compared to be a cap at all - and
// somebody who is through is not somebody the cap should still count down on
protectedUnlock( $appData, [ 'password' => 'not-the-password', 'return' => '/intern/notes' ] );
check( 'a wrong try is on file for this ip', ( \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )['198.51.100.10']['tries'] ?? 0 ) === 1 );

$rightRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/intern/notes' ] );
check( 'the right password unlocks and redirects (303)', $rightRequest['/nino/http/response']['statusCode'] === 303
	&& ( $rightRequest['/nino/http/response']['header']['Location'] ?? '' ) === '/intern/notes' );
check( 'the session now reads unlocked', \Nino\Modules\ProtectedArea::unlocked( $appData ) === true );
check( 'and the unlock left no attempts on file for that ip', isset( \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )['198.51.100.10'] ) === false );

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

// The wrong-password answer carries the posted return back into the form, so
// the visitor keeps their destination - and a value carried into a page is a
// value that is rendered. Escaped, it was still read by the fill and the
// shortcode pass that run over the rendered page: a locked visitor could put
// any of the project's templates, and any of its texts, into the 401 they
// were served
\Nino\Filesystem::putFileContent( $appData, '/templates/page-secret.tpl', 'THE-SECRET-TEMPLATE' );
\Nino\Html::addFills( $appData, [ '[[/secret/fill]]' => 'THE-SECRET-TEXT' ], '*' );
\Nino\Modules\Template::init( $appData );

$_SERVER['REMOTE_ADDR'] = '198.51.100.13';
$renderingReturnRequest = protectedUnlock( $appData, [ 'password' => 'not-the-password', 'return' => '[template /templates/page-secret] [[/secret/fill]]' ] );
$renderedForm = \Nino\Html::renderHtml( $appData, (string) $renderingReturnRequest['/nino/http/response']['body'] );
check( 'a return that is a template or a fill is carried back as the text it is, not rendered', str_contains( $renderedForm, 'THE-SECRET-TEMPLATE' ) === false
	&& str_contains( $renderedForm, 'THE-SECRET-TEXT' ) === false );

// Nothing says a post carries strings. An array under a name the endpoint
// reads used to reach a (string) cast, and the warning that raises is fatal
// to the request (see \Nino\Runtime::NON_FATAL_LEVELS) - an unauthenticated
// 500 from a post anybody can send
$_POST = [ 'password' => [ 'x' ], 'return' => [ 'y' ] ];
$arrayRequest = [ '/nino/http/request' => [ 'method' => 'POST', 'uri' => '/.unlock' ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [] ] ];
ninoWarnings();
\Nino\Modules\ProtectedArea::callbackUnlock( $appData, $arrayRequest );
check( 'a post whose values are arrays is answered, not raised at', ninoWarnings() === [] && $arrayRequest['/nino/http/response']['statusCode'] === 401 );

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
check( 'the locked error, not the wrong-password one, is what [protected-error] now shows', \Nino\Html::renderHtml( $appData, '[protected-error]' ) === '<p class="nino-protected-error">Zu viele falsche Versuche. Bitte versuche es in einer Stunde erneut.</p>' );

echo "\n";


// --- The cap under real concurrency ---------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - the cap under real concurrency\n";

/*	The count used to be read without a lock and written back only after the
	password had been compared. Every request of a burst read the same count,
	passed the same check and got to try a password, so a cap of three was
	three per burst - and a burst is the one case a cap exists for. Eight real
	processes, not eight calls: flock() is per open file description, which a
	single process satisfies by accident.

	The claim is inside the lock that records it now, so exactly three of the
	eight get a try and the file says three, whichever order they arrive in	*/
$burstIp 				= '198.51.100.30';
$burstSandbox		= ninoSandboxDir( $appData );
$burstStart			= sprintf( '%.6F', microtime( true ) + 1.5 );
$burstProcesses	= [];

for( $i = 0; $i < 8; $i++ ) {
	$burstPipes = [];
	$burstProcess = proc_open(
		[ PHP_BINARY, __FILE__, 'unlock-worker', $burstSandbox, $burstIp, $burstStart ],
		[ 1 => [ 'pipe', 'w' ], 2 => [ 'file', '/dev/null', 'w' ] ],
		$burstPipes
	);
	if( is_resource( $burstProcess ) === true )
		$burstProcesses[] = [ $burstProcess, $burstPipes[1] ];
}

$burstAnswers = [];

foreach( $burstProcesses as [ $burstProcess, $burstPipe ] ) {
	$burstAnswers[] = (int) stream_get_contents( $burstPipe );
	fclose( $burstPipe );
	proc_close( $burstProcess );
}

// The parent read this file before the workers wrote it - same way mutate()
// forces its own re-read (see \Nino\Filesystem::mutate())
$appData['./nino/filesystem/cache']['/data/protected.php']['fstat'] = [];
$burstTries = (int) ( \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )[$burstIp]['tries'] ?? 0 );
$burstTried = count( array_keys( $burstAnswers, 401, true ) );

check( 'all eight parallel posts were answered', count( $burstAnswers ) === 8 );
check( 'exactly three of them got a try, the other five were refused (429)', $burstTried === 3
	&& count( array_keys( $burstAnswers, 429, true ) ) === 5 );
check( 'and the counter on file says three, not eight', $burstTries === 3 );

echo "\n";



// --- The cap behind a reverse proxy ---------------------------------------------

echo "Modules\\ProtectedArea::callbackUnlock - the cap counts the visitor, not the proxy\n";

/*	Behind a proxy REMOTE_ADDR is the proxy's address for every visitor alike,
	so three wrong tries by anybody locked the area for everybody. With the
	proxy named under '/nino/http/proxies', the forwarded address is what
	counts (see \Nino\Http::getClientIp())	*/
$appData['/nino/http/proxies'] 		= [ '203.0.113.9' ];
$_SERVER['REMOTE_ADDR'] 					= '203.0.113.9';
$_SERVER['HTTP_X_FORWARDED_FOR'] 	= '198.51.100.20';

for( $i = 1; $i <= 3; $i++ )
	protectedUnlock( $appData, [ 'password' => 'still-not-the-password', 'return' => '/intern' ] );

$proxiedCounters = \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] );
check( 'the tries are counted for the visitor behind the proxy', ( $proxiedCounters['198.51.100.20']['tries'] ?? 0 ) === 3
	&& isset( $proxiedCounters['203.0.113.9'] ) === false );

$proxiedCapRequest = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/intern' ] );
check( 'that visitor is capped like any other', $proxiedCapRequest['/nino/http/response']['statusCode'] === 429 );

$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.21';
$secondVisitorRequest = protectedUnlock( $appData, [ 'password' => 'still-not-the-password', 'return' => '/intern' ] );
check( 'a second visitor through the same proxy has their own tries left', $secondVisitorRequest['/nino/http/response']['statusCode'] === 401 );

$appData['/nino/http/proxies'] = [];
unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

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


// --- A site in a subdirectory --------------------------------------------------

echo "A site in a subdirectory - every address carries the project directory\n";

/*	Request uris are the project's own - routes are keyed without the
	directory a site may sit in - so every address this feature writes has to
	put that directory in front: the form's action, the logout link and both
	redirects. Without it the form posted beside the site, the link led
	nowhere and both redirects left the site. The fill is what the kernel
	registers from the same key on every request */
$appData['/nino/dir'] = '/sub';
\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '/sub' ], '*' );
$_SERVER['REMOTE_ADDR'] = '198.51.100.77';

$gateInSub = protectedGate( $appData, '/intern' );
check( 'the form posts to the project\'s own endpoint', str_contains( \Nino\Html::renderHtml( $appData, (string) $gateInSub['/nino/http/response']['body'] ), 'action="/sub/.protected"' ) === true );

$unlockInSub = protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/intern/notes' ] );
check( '...the unlock sends the visitor back to the page within the project', ( $unlockInSub['/nino/http/response']['header']['Location'] ?? '' ) === '/sub/intern/notes' );

\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', true );
check( '...the logout link points at the project\'s own endpoint', \Nino\Html::renderHtml( $appData, '[protected-logout]' ) === '<a href="/sub/.protected/logout" class="nino-protected-logout">Diesen Bereich wieder sperren</a>' );
check( '...and locking again lands on the project\'s front page, not the domain\'s', ( protectedLogout( $appData )['/nino/http/response']['header']['Location'] ?? '' ) === '/sub/' );

$appData['/nino/dir'] = '';
\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '' ], '*' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

echo "\n";


// --- The session epoch - signing everybody out ------------------------------------

echo "Sign-out - one new epoch locks every session that unlocked before it\n";

$epochFile = \Nino\Filesystem::path( $appData, '/data/protected-session.php' );
$_SERVER['REMOTE_ADDR'] = '198.51.100.90';

check( 'no epoch is on file before anybody signed everybody out', is_file( $epochFile ) === false );

// What this feature stored before the epoch existed: a bare true. An update
// must not ask anybody for the password again
\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', true );
check( 'a legacy session (a bare true) is accepted until the first sign-out', \Nino\Modules\ProtectedArea::unlocked( $appData ) === true );

check( 'signing everybody out succeeds and writes an epoch', \Nino\Modules\ProtectedArea::signOutAll( $appData ) === true
	&& preg_match( '/^[0-9a-f]{32}$/', (string) ( \Nino\Filesystem::getFileContent( $appData, '/data/protected-session.php', [] )['epoch'] ?? '' ) ) === 1 );
check( '...and the legacy session is locked afterwards', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
check( '...with the gate asking for the password again', protectedGate( $appData, '/intern' )['/nino/http/response']['statusCode'] === 401 );

protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/intern' ] );
check( 'a fresh unlock is stamped with the new epoch and works', \Nino\Modules\ProtectedArea::unlocked( $appData ) === true
	&& \Nino\Runtime::getSessionValue( $appData, './protected/unlocked' ) === \Nino\Filesystem::getFileContent( $appData, '/data/protected-session.php', [] )['epoch'] );
check( '...and the protected GET passes through', protectedGate( $appData, '/intern' )['/nino/http/response']['body'] === 'ORIGINAL PAGE' );

$stampedSession = \Nino\Runtime::getSessionValue( $appData, './protected/unlocked' );
\Nino\Modules\ProtectedArea::signOutAll( $appData );
check( 'a session that unlocked before a sign-out is locked after it', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );

// The cookie a browser kept is the same value it always was; what changed is
// what it is compared with
\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', $stampedSession );
check( '...whatever the browser kept - the old stamp is not the epoch any more', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );

\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', '' );
check( 'an empty stamp is no stamp', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', [ 'x' ] );
check( 'and neither is a stamp that is not a string', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

echo "\n";


// --- An empty password leaves the feature inert ----------------------------------

echo "An empty password protects nothing\n";

check( 'paths are still configured at this point', \Nino\Features::setting( $appData, 'protected', 'paths' ) !== [] );
check( 'clearing the password is accepted', \Nino\Features::saveSettings( $appData, 'protected', [ 'password' => null ] ) === [] );
check( 'an empty password setting protects nothing, even with paths configured', \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === false );

$seoExcludedInert = [];
\Nino\Callbacks::doCallbacks( $appData, '/seo/exclude', $seoExcludedInert );
check( '...and asks Seo to keep nothing out - there is nothing to hide', $seoExcludedInert === [] );

echo "\n";


// --- The panel -------------------------------------------------------------------

echo "The panel - guards, the state, the pages, the password, signing out\n";

/**
 *	Drive a panel action the way the workbench does: through the registry's
 *	dispatch, with the payload in the 'data' field
 *
 *	@param		array 		&$appData
 *	@param		string		$action
 *	@param		array 		$data
 *
 *	@return 	array											The response: statusCode and body
 */
function protectedPanel( array &$appData, string $action, array $data = [] ): array {
	$_POST = [ 'action' => $action, 'data' => json_encode( $data ) ];
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Admin\Admin::handlePost( $appData, $request );
	$_POST = [];
	return $request['/nino/http/response'];
}

// The site the panel lists: a page with a variant per language, a protected
// page and one below it, a wildcard route, a page that names its Content-Type
// in lower case, and everything that must not be offered - the front page,
// the error pages, the files, the tools and the module endpoints
$appData['/nino/http/routes'] = [
	'GET://'							=> [ 'uri' => '/', 'body' => 'home' ],
	'GET://about'					=> [ 'uri' => '/about', 'locale' => 'en_US', 'body' => 'about' ],
	'GET://ueber-uns'			=> [ 'uri' => '/about', 'locale' => 'de_DE', 'body' => 'ueber uns' ],
	'GET://intern'				=> [ 'uri' => '/intern', 'body' => 'intern' ],
	'GET://intern/notes'	=> [ 'uri' => '/intern/notes', 'body' => 'notes' ],
	'GET://blog/*'				=> [ 'uri' => '/blog', 'body' => 'blog' ],
	'GET://plain'					=> [ 'uri' => '/plain', 'body' => 'plain', 'header' => [ 'content-type' => 'text/html; charset=UTF-8' ] ],
	'GET://404'						=> [ 'uri' => '/404', 'body' => 'nope', 'statusCode' => 404 ],
	'GET://gone'					=> [ 'uri' => '/gone', 'body' => 'gone', 'statusCode' => 410 ],
	'GET://robots.txt'		=> [ 'uri' => '/robots.txt', 'body' => 'r', 'header' => [ 'Content-Type' => 'text/plain; charset=utf-8' ] ],
	'GET://sitemap.xml'		=> [ 'uri' => '/sitemap.xml', 'body' => 's', 'header' => [ 'Content-Type' => 'application/xml; charset=utf-8' ] ],
	'GET://api/data'			=> [ 'uri' => '/api/data', 'body' => '{}', 'header' => [ 'Content-Type' => 'application/json' ] ],
	'GET://_admin/x'			=> [ 'uri' => '/_admin/x', 'body' => 'admin' ],
	'GET://.hidden'				=> [ 'uri' => '/.hidden', 'body' => 'hidden' ],
	'POST://form'					=> [ 'uri' => '/form', 'body' => 'post' ],
];
\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [
	'[[/webpage/about/title]]' => 'About Us',
] ) );
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] ), [
	'[[/webpage/intern/title]]' => 'Interner Bereich',
	'[[/webpage/about/title]]' => 'Über uns',
] ) );

// What a project looks like when the panel is first opened: a password, the
// developer's own prefixes (one of them a route's, one a path no route names,
// one that is never one - a tool), and a count of tries to keep
\Nino\Features::saveSettings( $appData, 'protected', [
	'paths'			=> [ '/intern', '/preview/client-a', '/_admin' ],
	'password'	=> 'sesam-öffne-dich',
	'attempts'	=> '3',
] );

check( 'the panel is registered while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['protected'] ) === true );

\Nino\Auth::logoutUser( $appData );
foreach( [ 'protected/state', 'protected/pages', 'protected/password', 'protected/signout' ] as $action )
	check( "$action without a login is 401", protectedPanel( $appData, $action, [ 'paths' => [], 'pw' => 'long-enough-pw' ] )['statusCode'] === 401 );

\Nino\Auth::insertUser( $appData, 'plain@example.com', 'correct horse battery staple', [ '/_admin/users/manage' ] );
\Nino\Auth::loginUser( $appData, 'plain@example.com', 'correct horse battery staple' );
foreach( [ 'protected/state', 'protected/pages', 'protected/password', 'protected/signout' ] as $action )
	check( "$action without the panel's permission is 403", protectedPanel( $appData, $action, [ 'paths' => [], 'pw' => 'long-enough-pw' ] )['statusCode'] === 403 );
check( '...and nothing was written by any of them', \Nino\Features::setting( $appData, 'protected', 'password' ) === 'sesam-öffne-dich'
	&& \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/intern', '/preview/client-a', '/_admin' ] );
\Nino\Auth::logoutUser( $appData );

\Nino\Auth::loginUser( $appData, 'scan@example.com', 'correct horse battery staple' );

// state
$state = protectedPanel( $appData, 'protected/state' );
$stateBody = (array) ( $state['body'] ?? [] );
check( 'state answers 200', $state['statusCode'] === 200 );
check( 'state says a password is set and never what it is', ( $stateBody['hasPassword'] ?? null ) === true
	&& str_contains( (string) json_encode( $stateBody ), 'sesam' ) === false && isset( $stateBody['password'] ) === false );
check( 'state names the shortest password the panel takes', ( $stateBody['minLength'] ?? 0 ) === 8 );

$byUri = [];
foreach( (array) ( $stateBody['pages'] ?? [] ) as $page )
	$byUri[ (string) $page['uri'] ] = $page;

check( 'the list is the pages: the language variants as one, a wildcard as its prefix, a lower-case content type kept',
	array_keys( $byUri ) === [ '/about', '/intern', '/intern/notes', '/blog', '/plain' ]
	&& $byUri['/about']['paths'] === [ '/about', '/ueber-uns' ] && $byUri['/blog']['paths'] === [ '/blog' ] );
check( 'not offered: the front page, error pages, files, tools, module endpoints, a post route',
	array_filter( array_keys( $byUri ), static fn( string $uri ): bool => in_array( $uri, [ '/', '/404', '/gone', '/robots.txt', '/sitemap.xml', '/api/data', '/_admin/x', '/.hidden', '/form' ], true ) ) === [] );
check( 'a title is the page\'s own text in the route\'s locale, else the native one, else empty',
	$byUri['/about']['title'] === 'About Us' && $byUri['/intern']['title'] === 'Interner Bereich' && $byUri['/blog']['title'] === '' );
check( 'a page that is a prefix is chosen; one below it is covered, not chosen; the others are neither',
	$byUri['/intern']['selected'] === true && $byUri['/intern']['covered'] === false
	&& $byUri['/intern/notes']['selected'] === false && $byUri['/intern/notes']['covered'] === true
	&& $byUri['/about']['selected'] === false && $byUri['/about']['covered'] === false );
check( 'a prefix the list cannot name is reported as extra - and the tool one the gate ignores is not', ( $stateBody['extra'] ?? null ) === [ '/preview/client-a' ] );

// pages
check( 'a path the server does not list is refused (400)', protectedPanel( $appData, 'protected/pages', [ 'paths' => [ '/about', '/secret' ] ] )['statusCode'] === 400 );
check( '...a prefix typed in is not a page of the list either', protectedPanel( $appData, 'protected/pages', [ 'paths' => [ '/intern/*' ] ] )['statusCode'] === 400 );
check( '...a non-string entry, and a payload that is no list', protectedPanel( $appData, 'protected/pages', [ 'paths' => [ [ '/about' ] ] ] )['statusCode'] === 400
	&& protectedPanel( $appData, 'protected/pages', [ 'paths' => '/about' ] )['statusCode'] === 400
	&& protectedPanel( $appData, 'protected/pages', [ 'paths' => [ 'a' => '/about' ] ] )['statusCode'] === 400
	&& protectedPanel( $appData, 'protected/pages', [] )['statusCode'] === 400 );
check( '...and nothing was written by any of them', \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/intern', '/preview/client-a', '/_admin' ] );

$saved = protectedPanel( $appData, 'protected/pages', [ 'paths' => [ '/about', '/ueber-uns', '/blog' ] ] );
check( 'a choice of listed pages is saved (200)', $saved['statusCode'] === 200 );
check( 'both language variants of a page are protected together', \Nino\Modules\ProtectedArea::protects( $appData, '/about' ) === true
	&& \Nino\Modules\ProtectedArea::protects( $appData, '/ueber-uns' ) === true && \Nino\Modules\ProtectedArea::protects( $appData, '/blog/a-post' ) === true );
check( '...the page left out is open again', \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === false );
check( '...the developer\'s own prefixes are kept as they were, ahead of the choice', \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/preview/client-a', '/_admin', '/about', '/ueber-uns', '/blog' ] );
check( '...and the other settings are untouched', \Nino\Features::setting( $appData, 'protected', 'password' ) === 'sesam-öffne-dich'
	&& \Nino\Features::setting( $appData, 'protected', 'attempts' ) === 3 );
check( '...with the new state in the answer', ( $saved['body']['pages'][0]['selected'] ?? null ) === true && ( $saved['body']['extra'] ?? null ) === [ '/preview/client-a' ] );

check( 'a choice survives a fresh read of config.php', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/features']['protected']['settings']['paths'] ?? null ) === [ '/preview/client-a', '/_admin', '/about', '/ueber-uns', '/blog' ] );

$cleared = protectedPanel( $appData, 'protected/pages', [ 'paths' => [] ] );
check( 'an empty choice is accepted: it switches the listed pages off and keeps the developer\'s prefixes', $cleared['statusCode'] === 200
	&& \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/preview/client-a', '/_admin' ]
	&& \Nino\Modules\ProtectedArea::protects( $appData, '/about' ) === false && \Nino\Modules\ProtectedArea::protects( $appData, '/preview/client-a/x' ) === true );

// A line the list names, written the way a developer might - with the slash on the end
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => [ '/intern/', '/preview/client-a' ] ] );
protectedPanel( $appData, 'protected/pages', [ 'paths' => [ '/blog' ] ] );
check( 'a line of the list\'s own, in another spelling, is replaced by the choice and not kept beside it', \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/preview/client-a', '/blog' ] );

// password
$unlockedBefore = static function( array &$appData ): void {
	\Nino\Modules\ProtectedArea::signOutAll( $appData );
	\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', \Nino\Filesystem::getFileContent( $appData, '/data/protected-session.php', [] )['epoch'] );
};

check( 'a short password is refused (400)', protectedPanel( $appData, 'protected/password', [ 'pw' => '1234567' ] )['statusCode'] === 400 );
check( '...a very long one, one that is no string, and none at all', protectedPanel( $appData, 'protected/password', [ 'pw' => str_repeat( 'x', 201 ) ] )['statusCode'] === 400
	&& protectedPanel( $appData, 'protected/password', [ 'pw' => [ 'longenough' ] ] )['statusCode'] === 400
	&& protectedPanel( $appData, 'protected/password', [] )['statusCode'] === 400 );
check( '...and the old password still stands, with nobody signed out', \Nino\Features::setting( $appData, 'protected', 'password' ) === 'sesam-öffne-dich' );

$unlockedBefore( $appData );
check( 'somebody is unlocked before the password changes', \Nino\Modules\ProtectedArea::unlocked( $appData ) === true );

$changed = protectedPanel( $appData, 'protected/password', [ 'pw' => 'eight-chars-ok' ] );
check( 'a password of 8 to 200 characters is saved (200)', $changed['statusCode'] === 200 && \Nino\Features::setting( $appData, 'protected', 'password' ) === 'eight-chars-ok' );
check( '...and signs everybody out', \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
check( '...the paths and the attempts are kept', \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/preview/client-a', '/blog' ]
	&& \Nino\Features::setting( $appData, 'protected', 'attempts' ) === 3 );
check( '...and the answer never carries the password', str_contains( (string) json_encode( $changed['body'] ), 'eight-chars-ok' ) === false
	&& ( $changed['body']['hasPassword'] ?? null ) === true );
check( '...the old one no longer unlocks, the new one does', protectedUnlock( $appData, [ 'password' => 'sesam-öffne-dich', 'return' => '/blog' ] )['/nino/http/response']['statusCode'] === 401
	&& protectedUnlock( $appData, [ 'password' => 'eight-chars-ok', 'return' => '/blog' ] )['/nino/http/response']['statusCode'] === 303 );
check( 'a password of exactly 200 characters is taken', protectedPanel( $appData, 'protected/password', [ 'pw' => str_repeat( 'x', 200 ) ] )['statusCode'] === 200 );
protectedPanel( $appData, 'protected/password', [ 'pw' => 'eight-chars-ok' ] );

// sign-out
$unlockedBefore( $appData );
$signedOut = protectedPanel( $appData, 'protected/signout' );
check( 'sign-out answers 200 and locks the session that was unlocked', $signedOut['statusCode'] === 200 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
check( '...leaving the password and the pages as they were', \Nino\Features::setting( $appData, 'protected', 'password' ) === 'eight-chars-ok'
	&& \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/preview/client-a', '/blog' ] );

// log
$logLines = [
	\Nino\Modules\ProtectedArea\Admin::log( 'protected/password', [ 'pw' => 'eight-chars-ok' ] ),
	\Nino\Modules\ProtectedArea\Admin::log( 'protected/pages', [ 'paths' => [ '/blog', '/about' ] ] ),
	\Nino\Modules\ProtectedArea\Admin::log( 'protected/signout', [] ),
];
check( 'every mutating action has a log line, and none of them holds the password', array_filter( $logLines, static fn( string $line ): bool => $line === '' ) === []
	&& str_contains( implode( ' ', $logLines ), 'eight-chars-ok' ) === false );
check( 'reading the state is not logged', \Nino\Modules\ProtectedArea\Admin::log( 'protected/state', [] ) === '' );

\Nino\Auth::logoutUser( $appData );
\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );

echo "\n";


// --- Deactivation ------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'protected' ) === true );
check( 'the class is removed from /nino/modules', in_array( '\\Nino\\Modules\\ProtectedArea', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the panel is gone with it', isset( \Nino\Admin\Admin::panels( $appData )['protected'] ) === false );
check( 'the settings and the attempt counter survive deactivation', \Nino\Features::setting( $appData, 'protected', 'attempts' ) === 3
	&& is_file( \Nino\Filesystem::path( $appData, '/data/protected.php' ) ) === true );
check( 'the copied template survives deactivation too', is_file( \Nino\Filesystem::path( $appData, '/templates/page-protected.tpl' ) ) === true );

echo "\n";


// --- The panel's script, where node is on the path ------------------------------

// protected-js-smoke.js beside this file draws the panel over a dom stand-in;
// this suite runs it too where node is on the path, the way stats-smoke.php
// runs its twin, so bin/check.sh and CI cover both halves in one go
$jsTest	= __DIR__. '/protected-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: protected-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'protected-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
