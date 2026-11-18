<?php
declare(strict_types=1);

/**
 *	Nino features
 *	protected-smoke.php	The test of the feature ProtectedArea: the components, the page and the block, the gate - an address
 *											that is protected never answers its content without a session, in any spelling -, the unlock
 *											(a hash, a cap that holds, a return that stays on the site), the session epoch, the panel's three
 *											actions and what each of them refuses, the Seo callback and the cache blacklist, and - where node is
 *											on the path - the panel script's own test. One pin for each behaviour, over the structure and never
 *											the words. It travels with the feature and runs against the checkout three levels up, or the one
 *											NINO_ROOT names.
 *
 *	Usage: php features/ProtectedArea/tests/protected-smoke.php
 *	       NINO_ROOT=../nino php features/ProtectedArea/tests/protected-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/*	Worker mode: this same file re-entered as its own process, pointed at a sandbox somebody else built, to post one wrong
	password and print the status it was answered with. flock() is per open file description, so a single process can satisfy
	a lock by accident and proves nothing about one	*/
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

	// Every worker waits for the same moment before posting, booting done: started one after the other and left to
	// run, the first is finished before the last has begun
	while( microtime( true ) < (float) ( $argv[4] ?? 0 ) )
		usleep( 200 );

	\Nino\Modules\ProtectedArea::callbackUnlock( $worker, $workerRequest );

	echo $workerRequest['/nino/http/response']['statusCode'];
	exit( 0 );
}

/*	Worker mode 2: the same, with a real php session, to say whether an unlock gives the session a new id. The kernel starts
	a session only where a visitor has one; the worker starts one the way Runtime does and unlocks	*/
if( ( $argv[1] ?? '' ) === 'session-worker' ) {

	$worker = [ './nino/uid' => $argv[2] ];
	\Nino\AppData::prepare( $worker );
	$worker['./nino/filesystem/path']					= $argv[2];
	$worker['./nino/filesystem/configpath']		= $argv[2]. '/private';
	$worker['./nino/filesystem/contentpath']	= $argv[2]. '/private';
	$worker['./nino/filesystem/publicpath']		= $argv[2]. '/public';
	\Nino\AppData::init( $worker );

	@mkdir( $argv[2]. '/sessions' );
	ini_set( 'session.save_path', $argv[2]. '/sessions' );

	foreach( [ '_sessionReady' => true, '_sessionCookieParams' => [ 'lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax' ] ] as $name => $value ) {
		$property = new \ReflectionProperty( \Nino\Runtime::class, $name );
		$property->setValue( null, $value );
	}

	$_SERVER['REMOTE_ADDR']	= $argv[3];
	$_POST									= [ 'password' => $argv[4], 'return' => '/intern' ];
	$workerRequest					= [ '/nino/http/response' => [ 'statusCode' => 200, 'header' => [] ] ];
	$started								= \Nino\Runtime::startSession( $worker ) === true;
	$before									= session_id();

	\Nino\Modules\ProtectedArea::callbackUnlock( $worker, $workerRequest );

	echo json_encode( [ $started, $before !== '', session_id() !== '' && session_id() !== $before, $workerRequest['/nino/http/response']['statusCode'] ] );
	exit( 0 );
}

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, is written for this kernel and names the two files its counter and its epoch write',
	is_array( $manifest ) === true && $manifest['key'] === 'protected' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false
	&& $manifest['data'] === [ '/data/protected.php', '/data/protected-session.php' ] );

$appData = ninoSandbox( 'protected' );
$appData['./nino/filesystem/path'] = $root;
$appData['/nino/dir'] = '';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'Basis and the feature are switched on, and the unit brought the page, its words in both languages and the section of the privacy policy',
	\Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'protected' ) === true
	&& \Nino\Filesystem::fileExists( $appData, '/templates/page-protected.tpl' ) === true );

$unit = (array) include $dir. '/install/manifest.php';
$privacy = (array) include $dir. '/install/elements/privacy.php';
$words = [ 'de_DE' => (array) include $dir. '/install/text/de_DE.php', 'en_US' => (array) include $dir. '/install/text/en_US.php' ];
check( 'its words are the same keys in both languages, and the page is named, titled and described',
	array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) && count( array_filter( array_keys( $words['en_US'] ), static fn( string $key ): bool => str_starts_with( $key, '[[/_nino/webpage/.protected/' ) ) ) === 3
	&& ( $unit['elements']['privacy'] ?? '' ) === 'elements/privacy.php' && isset( $privacy['*']['protected'], $privacy['de_DE']['protected']['text'], $privacy['en_US']['protected']['text'] ) === true );

// One password for every section: the setting holds its hash, as the panel and the Features panel leave it
$PASSWORD = 'sesam-öffne-dich';
check( 'the settings answer their defaults - inert until a password is set', \Nino\Features::settings( $appData, 'protected' ) === [ 'paths' => [], 'password' => '', 'attempts' => 5 ] );
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => [ '/intern', '/preview/client-a', '/_admin', '/.protected', '', '/Club/Area' ], 'password' => \Nino\Modules\ProtectedArea::hashPassword( $PASSWORD ), 'attempts' => '3' ] );
$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$appData['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Csrf' ], (array) ( $stored['/nino/modules'] ?? [] ) );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );

\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
// By reference: what the gate leaves in $appData for the form (its error and its return) is read when the form is drawn
$draw = static function( string $source ) use ( &$appData ): string { return trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-protected' ] + \Nino\Html::context( $appData ) ) ); };
$ignored = ninoWarnings();

/**
 *	Drive the gate the way \Nino\Http::response() answers a request: the route found for the path, its uri and body laid
 *	into the response and the callbacks of the response run, the gate among them. Nothing is rendered
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$method
 *
 *	@return 	array										The request, response and all
 */
function protectedServe( array &$appData, string $uri, string $method = 'GET' ): array {
	$request = ninoVisit( $appData, $uri, $method );
	\Nino\Http::response( $appData, $request );
	return $request;
}

/**
 *	POST /.protected as the route callback reads it: $_POST filled by hand and the callback called itself
 *
 *	@param		array 		&$appData
 *	@param		array 		$post
 *
 *	@return 	array
 */
function protectedUnlock( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'password' => '', 'return' => '' ], $post );
	$request = ninoVisit( $appData, '/.protected', 'POST' );
	\Nino\Modules\ProtectedArea::callbackUnlock( $appData, $request );
	return $request;
}

/**
 *	The tries a client has on file with the kernel's counter: its entry is keyed by the sha256 of the address, never by the address
 *
 *	@param		array 		&$appData
 *	@param		string		$ip
 *
 *	@return 	int
 */
function protectedTries( array &$appData, string $ip ): int {
	return (int) ( \Nino\Filesystem::getFileContent( $appData, '/data/protected.php', [] )[ hash( 'sha256', \Nino\Http::clientBucket( $ip ) ) ]['tries'] ?? 0 );
}

$locked = static fn( array $request ): bool => $request['/nino/http/response']['statusCode'] === 401 && $request['/nino/http/response']['body'] === '[template /templates/page-protected]';

echo "\nThe components, the page and the block\n";

check( 'the folders are read without a reason or a warning', \Nino\Features::componentProblems( $appData, 'protected' ) === [] && $ignored === [] );
$schemas = \Nino\Components::components( $appData );
check( 'both components are in their group, labelled in both languages, with the icon lock and a standard part',
	array_filter( [ 'protected-form', 'protected-logout' ], static fn( string $name ): bool => ( $schemas[$name]['group'] ?? '' ) !== 'protected' || ( $schemas[$name]['icon'] ?? '' ) !== 'lock'
		|| isset( $schemas[$name]['label']['de_DE'], $schemas[$name]['label']['en_US'] ) === false ) === []
	&& in_array( 'lock', \Nino\Components::ICONS, true ) === true && ( \Nino\Components::groups( $appData )['protected']['part'] ?? false ) === true );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/protected/part/part.css' ), [ 'protected-form', 'protected-logout' ] ) === [] );
check( 'a tool offers the form in a column, and the link in a column, the header actions and a footer column',
	in_array( 'protected-form', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& array_filter( [ 'section-col', 'header-actions', 'footer-col' ], static fn( string $place ): bool => in_array( 'protected-logout', \Nino\Components::allowed( $appData, $place ), true ) === false ) === [] );

// The page of the unit is made of components: a tool reads it as blocks, with nothing it has to keep as free html
$page = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-protected.tpl' ) );
$found = [];
foreach( \Nino\Components::check( $appData, \Nino\Markup::parse( $page, \Nino\Components::containers( $appData ) ) ) as $at => $problems )
	foreach( $problems as $problem )
		if( ( in_array( $problem['code'], [ 'unknown', 'bare' ], true ) === true && $problem['detail'] === 'template' ) === false && ( $problem['code'] === 'free' && $at === 0 ) === false )
			$found[] = $problem['code'];
check( 'the page of the unit is made of components and the two includes of the frame: no class of Nino 1, no element of its own, nothing a tool keeps as free html, the form among them',
	preg_match( '/nino-|<section|<div|<form|<h1/', $page ) === 0 && str_contains( $page, '[protected-form]' ) === true && $found === [] );

$blocks = \Nino\Blocks::get( $appData, 'protected-login' );
check( 'the block is valid, names the feature and brings the form', is_array( $blocks ) === true && in_array( 'protected', $blocks['features'] ?? [], true ) === true && ( $blocks['missing'] ?? [] ) === []
	&& str_contains( (string) file_get_contents( $dir. '/blocks/protected-login/block.tpl' ), '[protected-form]' ) === true );
check( 'the demo of the group is read without a note and draws the form', substr_count( $draw( (string) ( \Nino\Components::groups( $appData )['protected']['demo'] ?? '' ) ), 'class="protected-form"' ) === 1 );

echo "\nThe gate\n";

$keptHash = \Nino\Features::setting( $appData, 'protected', 'password' );
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => null ] );
$inertList = [];
\Nino\Callbacks::doCallbacks( $appData, '/seo/exclude', $inertList );
$inertCache = $appData;
$inertCache['/nino/cache/blacklist'] = [];
\Nino\Modules\ProtectedArea::init( $inertCache );
check( 'nothing is protected while the password is empty, even with paths configured, and the cache and Seo are told nothing',
	\Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === false && $inertList === [] && ( $inertCache['/nino/cache/blacklist'] ?? [] ) === [] );
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => $keptHash ] );
check( 'the paths are read as prefixes: the tools and the module endpoints never are one, a prefix is the page and what is below it and no neighbour',
	\Nino\Modules\ProtectedArea::prefixes( $appData ) === [ '/intern', '/preview/client-a', '/Club/Area' ]
	&& \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === true && \Nino\Modules\ProtectedArea::protects( $appData, '/intern/notes/2026' ) === true
	&& \Nino\Modules\ProtectedArea::protects( $appData, '/preview/client-a/draft' ) === true
	&& array_filter( [ '/internal', '/about', '/', '/_admin', '/.protected' ], static fn( string $uri ): bool => \Nino\Modules\ProtectedArea::protects( $appData, $uri ) === true ) === [] );

$appData['/nino/http/routes'] = array_merge( $appData['/nino/http/routes'], [
	'GET://intern'			=> [ 'uri' => '/intern', 'locale' => 'de_DE', 'body' => 'SECRET-DE' ],
	'GET://members'			=> [ 'uri' => '/intern', 'locale' => 'en_US', 'body' => 'SECRET-EN', 'header' => [ 'Content-Type' => 'text/plain', 'Content-Disposition' => 'attachment', 'Link' => '</intern/plan.pdf>; rel=preload', 'Refresh' => '0;url=/intern/plan', 'X-Robots-Tag' => 'all', 'Access-Control-Allow-Origin' => '*' ] ],
	'GET://members/*'		=> [ 'uri' => '/intern/*', 'locale' => 'en_US', 'body' => 'SECRET-TREE' ],
	'GET://news/*'			=> [ 'uri' => '/intern/post', 'body' => 'SECRET-NEWS' ],
	'GET://intern/archive'	=> [ 'uri' => '/archive', 'body' => 'SECRET-ARCHIVE' ],
	'GET://internal'		=> [ 'uri' => '/internal', 'body' => 'PUBLIC' ],
	'GET://about'			=> [ 'uri' => '/about', 'body' => 'PUBLIC' ],
	// A route below a prefix that answers as the front page: the protection does not widen to the whole site
	'GET://intern/home'	=> [ 'uri' => '/', 'body' => 'PUBLIC-HOME' ],
	// An alias below a prefix has a uri of its own, and a route that answers an error shuts nothing
	'GET://contact'			=> [ 'uri' => '/contact', 'body' => 'PUBLIC-CONTACT' ],
	'GET://intern/kontakt'	=> [ 'uri' => '/contact', 'body' => 'PUBLIC-CONTACT' ],
	'GET://intern/fehlt'		=> [ 'uri' => '/404', 'statusCode' => 404, 'body' => 'PUBLIC-404' ],
] );
$_SERVER['REMOTE_ADDR'] = '198.51.100.40';

$leaks = [];
foreach( [ '/intern', '/members', '/members/2026/plan', '/news/hello-world', '/intern/archive' ] as $each )
	if( $locked( protectedServe( $appData, $each ) ) === false || str_contains( (string) protectedServe( $appData, $each )['/nino/http/response']['body'], 'SECRET' ) === true )
		$leaks[] = $each;
check( 'a protected address answers a locked visitor with the form and never with the page: its own, the one in the other language, below a wildcard route, a page below a prefix and a path under one'. ( $leaks === [] ? '' : ' - '. implode( ' ', $leaks ) ), $leaks === [] );
check( 'the gate runs at priority 1 on the response, before the page cache (9) decides what is served, and the two routes of the feature are registered',
	count( array_filter( $appData['./nino/callbacks']['/nino/http/response'][1] ?? [], static fn( mixed $callback ): bool => $callback === [ \Nino\Modules\ProtectedArea::class, 'callbackGate' ] ) ) === 1
	&& isset( $appData['/nino/http/routes']['POST://.protected'], $appData['/nino/http/routes']['GET://.protected/logout'] ) === true );
check( 'a route below a prefix that is an alias of another page, or answers an error, shuts nothing but itself: the page it names stays open and an unknown address stays a 404',
	protectedServe( $appData, '/contact' )['/nino/http/response']['body'] === 'PUBLIC-CONTACT' && protectedServe( $appData, '/gibtesnicht' )['/nino/http/response']['statusCode'] === 404
	&& \Nino\Modules\ProtectedArea::protects( $appData, '/404' ) === false && $locked( protectedServe( $appData, '/intern/kontakt' ) ) === true );
check( 'a path that only begins like a prefix, an unprotected page and the front page - even where a route below a prefix answers as it - are the pages they are', protectedServe( $appData, '/internal' )['/nino/http/response']['body'] === 'PUBLIC' && protectedServe( $appData, '/about' )['/nino/http/response']['body'] === 'PUBLIC' && $locked( protectedServe( $appData, '/' ) ) === false );

// The spellings one page is asked for: case, runs of slashes, a trailing dot or slash, a dot segment, an escaped slash, twice escaped, a letter escaped
$spelled = [];
foreach( [ '/Intern', '/INTERN/', '//intern', '/intern//', '/intern.', '/intern/.', '/intern/../intern', '/x/../intern', '/intern%2F', '/intern%2Fsecret', '/intern%252Fsecret', '/%69ntern', '/Preview/Client-A', '/preview//client-a/', '/preview/client-a./draft', '/intern\\secret' ] as $each ) {
	$answer = protectedServe( $appData, $each );
	if( $locked( $answer ) === false && str_contains( (string) $answer['/nino/http/response']['body'], 'SECRET' ) === true || \Nino\Modules\ProtectedArea::protects( $appData, $each ) === false )
		$spelled[] = $each;
}
check( 'the gate holds for every spelling of an address: other case, double slashes, a trailing dot or slash, a dot segment, an escaped slash and an escaped letter, twice escaped too'. ( $spelled === [] ? '' : ' - '. implode( ' ', $spelled ) ), $spelled === [] );
check( 'a HEAD request is gated as the GET it stands for', $locked( protectedServe( $appData, '/intern', 'HEAD' ) ) === true );

// What the route brought is not in the answer: no file, no header of its own, and not the callbacks bound to its uri
$appData['/nino/http/routes']['GET://files'] = [ 'uri' => '/intern/files', 'file' => '/data/protected.php', 'body' => 'SECRET-FILE', 'header' => [ 'content-type' => 'application/octet-stream' ] ];
\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://intern/files', static function( array &$appData, array &$request ): void { $request['/nino/http/response']['body'] = 'SECRET-BY-CALLBACK'; } );
$answer = protectedServe( $appData, '/files' );
$memberAnswer = protectedServe( $appData, '/members' );
check( 'the answer to a locked visitor is the gate\'s own: 401, never cached, under the address of the form, with no file, no callback of the route, the policy of the response and none of the route\'s headers',
	$locked( $answer ) === true && isset( $answer['/nino/http/response']['file'] ) === false && $answer['/nino/http/response']['uri'] === '/.protected'
	&& ( $answer['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store' && ( static function() use ( $memberAnswer, &$appData ): bool {
		$names = array_keys( array_change_key_case( $memberAnswer['/nino/http/response']['header'] ) );
		sort( $names );
		return $names === [ 'cache-control', 'content-security-policy' ] && trim( (string) $memberAnswer['/nino/http/response']['header']['Content-Security-Policy'] ) !== ''
			&& $memberAnswer['/nino/http/response']['header']['Content-Security-Policy'] === ninoVisit( $appData, '/' )['/nino/http/response']['header']['Content-Security-Policy'];
	} )() );

$request = protectedServe( $appData, '/intern/notes' );
$form = $draw( (string) $request['/nino/http/response']['body'] );
check( 'the form posts to /.protected with the csrf token of the session and the path that was asked for, and runs without a script',
	str_contains( $form, '<form class="protected-form" action="/.protected" method="post">' ) === true && str_contains( $form, 'name="_csrf" value="'. \Nino\Csrf::getToken( $appData ). '"' ) === true
	&& str_contains( $form, 'name="return" value="/intern/notes"' ) === true && str_contains( $form, '<script' ) === false );
check( 'a locked visitor sees no error before a try, and the form never holds a password', substr_count( $form, ' role="alert" hidden>' ) === 2 && str_contains( $form, $PASSWORD ) === false && str_contains( $form, 'value="" ' ) === false );
check( 'the address a visitor asked for is text in the form: it cannot close the attribute, and a bracket in it is no call and no fill', ( static function() use ( &$appData, $draw ): bool {
	$appData['./protected/return'] = '/intern/"><script>x</script>[csrf][[/nino/dir]]';
	$html = $draw( '[protected-form]' );
	unset( $appData['./protected/return'] );
	return str_contains( $html, '<script' ) === false && substr_count( $html, 'name="_csrf"' ) === 1 && str_contains( $html, '&quot;&gt;&lt;script&gt;x&lt;/script&gt;[csrf][[/nino/dir]]' ) === true;
} )() );

echo "\nThe unlock\n";

\Nino\Csrf::init( $appData );
$_POST = [ 'password' => $PASSWORD, 'return' => '/intern' ];
$noToken = ninoVisit( $appData, '/.protected', 'POST' );
\Nino\Http::response( $appData, $noToken );
check( 'a post without the token of the session is refused by the kernel, the right password included, and counts nothing', $noToken['/nino/http/response']['statusCode'] === 403 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && protectedTries( $appData, '198.51.100.40' ) === 0 );
check( 'the kernel is asked, not assumed: the unlock answers nothing where the guard refused', ( static function() use ( &$appData, $PASSWORD ): bool {
	$_POST = [ 'password' => $PASSWORD, 'return' => '/intern', '_csrf' => 'not-the-token' ];
	$request = ninoVisit( $appData, '/.protected', 'POST' );
	\Nino\Http::response( $appData, $request );
	return $request['/nino/http/response']['statusCode'] === 403 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === false;
} )() );

$wrong = protectedUnlock( $appData, [ 'password' => 'not-the-password', 'return' => '/intern/notes' ] );
$wrongForm = $draw( (string) $wrong['/nino/http/response']['body'] );
check( 'a wrong password is answered 401 with the form again and one error line, the path carried back, a try on file and no session',
	$locked( $wrong ) === true && substr_count( $wrongForm, ' role="alert" hidden>' ) === 1 && str_contains( $wrongForm, 'name="return" value="/intern/notes"' ) === true
	&& \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && protectedTries( $appData, '198.51.100.40' ) === 1 );
check( 'the counter is the kernel\'s, under the file the manifest names, and holds the client by a hash: the address is nowhere in it',
	str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/protected.php' ) ), '198.51.100.40' ) === false && in_array( '/data/protected.php', $manifest['data'], true ) === true );
check( 'the password is in no answer: not in the form, not in a header', str_contains( $wrongForm, 'not-the-password' ) === false && str_contains( json_encode( $wrong['/nino/http/response']['header'] ), 'not-the-password' ) === false );
check( 'a post whose values are arrays is answered, not raised at', protectedUnlock( $appData, [ 'password' => [ 'x' ], 'return' => [ 'y' ] ] )['/nino/http/response']['statusCode'] === 401 );

$epochBefore = \Nino\Filesystem::fileExists( $appData, '/data/protected-session.php' );
$tokenBefore = \Nino\Csrf::getToken( $appData );
$right = protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern/notes' ] );
check( 'the right password unlocks, redirects (303) to the return, forgets the tries and gives the session a new token',
	$right['/nino/http/response']['statusCode'] === 303 && ( $right['/nino/http/response']['header']['Location'] ?? '' ) === '/intern/notes' && ( $right['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store'
	&& \Nino\Modules\ProtectedArea::unlocked( $appData ) === true && protectedTries( $appData, '198.51.100.40' ) === 0 && \Nino\Csrf::getToken( $appData ) !== $tokenBefore && $epochBefore === false );
check( 'an unlocked session passes the gate at every address, the other language and below a wildcard route included',
	array_filter( [ '/intern', '/members', '/members/2026/plan', '/news/hello-world' ], static fn( string $each ): bool => str_contains( (string) protectedServe( $GLOBALS['appData'], $each )['/nino/http/response']['body'], 'SECRET' ) === false ) === [] );

$hostile = [];
foreach( [ '//evil.example/x', '/\\evil.example', 'https://evil.example/', 'javascript:alert(1)', '/javascript:alert(1)', "/a\r\nSet-Cookie: x=1", "/a\tb", "/a\x00b", '', 'intern', '/https:evil.example' ] as $return ) {
	\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );
	$sent = protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => $return ] );
	if( ( $sent['/nino/http/response']['header']['Location'] ?? '' ) !== '/' || $sent['/nino/http/response']['statusCode'] !== 303 )
		$hostile[] = $return;
}
check( 'a return that is no path of this site - another host, a scheme, a line break, a control character - is the front page, never an open redirect'. ( $hostile === [] ? '' : ' - '. implode( ' ', array_map( 'json_encode', $hostile ) ) ), $hostile === [] );
check( 'a return that is a template or a fill is carried back as the text it is, not rendered', ( static function() use ( &$appData, $draw ): bool {
	$wrong = protectedUnlock( $appData, [ 'password' => 'x', 'return' => '/a[template][[/project/company/general/name]][csrf]' ] );
	$html = $draw( (string) $wrong['/nino/http/response']['body'] );
	return str_contains( $html, 'name="return" value="/a[template][[/project/company/general/name]][csrf]"' ) === true && substr_count( $html, 'name="_csrf"' ) === 1 && str_contains( $html, 'general/name]]' ) === true;
} )() );

echo "\nThe password is a hash\n";

$configText = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/config.php' ) );
$stored = \Nino\Features::setting( $appData, 'protected', 'password' );
check( 'the setting holds a hash of the password and no file of the project holds the password itself', is_string( $stored ) === true && password_get_info( $stored )['algo'] !== null && $stored !== $PASSWORD
	&& str_contains( $configText, $PASSWORD ) === false && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/protected-session.php' ) ), $PASSWORD ) === false );
check( 'a password of any length counts to its end: 72 bytes are no limit', ( static function() use ( &$appData ): bool {
	$long = str_repeat( 'a', 100 );
	$hash = \Nino\Modules\ProtectedArea::hashPassword( $long );
	return password_verify( base64_encode( hash( 'sha256', $long, true ) ), $hash ) === true && password_verify( base64_encode( hash( 'sha256', str_repeat( 'a', 99 ). 'b', true ) ), $hash ) === false;
} )() );
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => 'plain-text-password' ] );
\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );
$plain = protectedUnlock( $appData, [ 'password' => 'plain-text-password', 'return' => '/intern' ] );
check( 'a stored value that is no hash - a password written by hand into config.php - verifies nothing: the gate stays shut',
	$plain['/nino/http/response']['statusCode'] === 401 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === false );
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => $keptHash ] );

echo "\nThe cap on wrong attempts\n";

\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );
$_SERVER['REMOTE_ADDR'] = '198.51.100.50';
$statuses = [];
for( $try = 0; $try < 3; $try++ )
	$statuses[] = protectedUnlock( $appData, [ 'password' => 'wrong-'. $try, 'return' => '/intern' ] )['/nino/http/response']['statusCode'];
$capped = protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern' ] );
check( 'after the attempts the form refuses (429), the right password included, counts the try and says the same thing for every password',
	$statuses === [ 401, 401, 401 ] && $capped['/nino/http/response']['statusCode'] === 429 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && protectedTries( $appData, '198.51.100.50' ) === 4
	&& protectedUnlock( $appData, [ 'password' => 'wrong', 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 429 );
check( 'the cap is the address\'s and not the session\'s: a new session, no cookie, starts nothing new', ( static function() use ( &$appData, $PASSWORD ): bool {
	$session = $_SESSION ?? [];
	$_SESSION = [];
	$refused = protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 429;
	$_SESSION = $session;
	return $refused;
} )() );
check( 'a second visitor has tries of their own, and one behind a trusted proxy is counted by the address the proxy names', ( static function() use ( &$appData ): bool {
	$_SERVER['REMOTE_ADDR'] = '198.51.100.51';
	$other = protectedUnlock( $appData, [ 'password' => 'x', 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 401;
	$appData['/nino/http/proxies'] = [ '203.0.113.9' ];
	$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
	$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.60, 203.0.113.9';
	protectedUnlock( $appData, [ 'password' => 'x', 'return' => '/intern' ] );
	$counted = protectedTries( $appData, '198.51.100.60' ) === 1 && protectedTries( $appData, '203.0.113.9' ) === 0;
	unset( $appData['/nino/http/proxies'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
	$_SERVER['REMOTE_ADDR'] = '198.51.100.50';
	return $other === true && $counted === true;
} )() );

check( 'the cap holds under real concurrency: eight posts that overlap in eight processes get three tries and five refusals, every try counted', ( static function() use ( &$appData ): bool {
	if( function_exists( 'proc_open' ) === false )
		return true;
	$burstIp = '198.51.100.70';
	$burstStart = sprintf( '%.3f', microtime( true ) + 1.5 );
	$processes = [];
	for( $worker = 0; $worker < 8; $worker++ ) {
		$pipes = [];
		$process = proc_open( [ PHP_BINARY, __FILE__, 'unlock-worker', ninoSandboxDir( $appData ), $burstIp, $burstStart ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + $_ENV );
		$processes[] = [ $process, $pipes ];
	}
	$answers = [];
	foreach( $processes as [ $process, $pipes ] ) {
		$answers[] = trim( (string) stream_get_contents( $pipes[1] ) );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );
	}
	$count = array_count_values( $answers );
	return count( $answers ) === 8 && ( $count['401'] ?? 0 ) === 3 && ( $count['429'] ?? 0 ) === 5 && protectedTries( $appData, $burstIp ) === 8;
} )() );

echo "\nSigning out\n";

\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );
$_SERVER['REMOTE_ADDR'] = '198.51.100.80';
protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern' ] );
$logout = ninoVisit( $appData, '/.protected/logout' );
$linkWhileUnlocked = $draw( '[protected-logout]' );
\Nino\Modules\ProtectedArea::callbackLogout( $appData, $logout );
check( 'the link is drawn only while unlocked and points at the project\'s logout; locking again goes home (303, no-store) and the gate shuts',
	$linkWhileUnlocked === '<a class="protected-logout" href="/.protected/logout">'. \Nino\Html::renderSource( $appData, '[[/feature/protected/logout/label]]', \Nino\Html::context( $appData ) ). '</a>'
	&& $logout['/nino/http/response']['statusCode'] === 303 && ( $logout['/nino/http/response']['header']['Location'] ?? '' ) === '/' && ( $logout['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store'
	&& \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && $draw( '[protected-logout]' ) === '' && $locked( protectedServe( $appData, '/intern' ) ) === true );

protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern' ] );
$epoch = (string) ( \Nino\Filesystem::getFileContent( $appData, '/data/protected-session.php', [] )['epoch'] ?? '' );
$old = \Nino\Runtime::getSessionValue( $appData, './protected/unlocked' );
check( 'signing everybody out writes a new epoch: the session of before is locked, whatever the browser kept, and an unlock after it works',
	preg_match( '/^[0-9a-f]{32}$/', $epoch ) === 1 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === true && \Nino\Modules\ProtectedArea::signOutAll( $appData ) === true
	&& \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && $old === $epoch && $locked( protectedServe( $appData, '/intern' ) ) === true
	&& protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 303 && \Nino\Modules\ProtectedArea::unlocked( $appData ) === true );
check( 'a stamp that is no epoch is no unlock: a bare true, an empty string, a number, the epoch of another time', ( static function() use ( &$appData, $epoch ): bool {
	foreach( [ true, '', 1, [ 'x' ], $epoch ] as $stamp ) {
		\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', $stamp );
		if( \Nino\Modules\ProtectedArea::unlocked( $appData ) === true )
			return false;
	}
	return true;
} )() );
$record = \Nino\Filesystem::getFileContent( $appData, '/data/protected-session.php', [] );
check( 'the epoch file holds the epoch and a keyed hash of the password it was written under, never the password',
	array_keys( $record ) === [ 'epoch', 'pw' ] && str_contains( json_encode( $record ), $PASSWORD ) === false && str_contains( json_encode( $record ), (string) \Nino\Features::setting( $appData, 'protected', 'password' ) ) === false
	&& hash_equals( $record['pw'], hash_hmac( 'sha256', (string) \Nino\Features::setting( $appData, 'protected', 'password' ), $record['epoch'] ) ) === true );

echo "\nSeo, the cache and the Features panel\n";

$list = [];
\Nino\Callbacks::doCallbacks( $appData, '/seo/exclude', $list );
check( 'Seo is told every protected prefix as a subtree, and the address of every route whose page lies under one but whose path does not',
	in_array( '/intern/*', $list, true ) === true && in_array( '/preview/client-a/*', $list, true ) === true && in_array( '/members/*', $list, true ) === true
	&& in_array( '/internal', $list, true ) === false && in_array( '/about', $list, true ) === false );
check( 'the cache is kept from storing a protected page: every prefix and the address of the page in the other language is a subtree of its blacklist, and no neighbour', ( static function() use ( &$appData ): bool {
	$probe = $appData;
	$probe['/nino/cache/blacklist'] = [];
	\Nino\Modules\ProtectedArea::init( $probe );
	$blacklist = $probe['/nino/cache/blacklist'];
	return \Nino\Http::matchesPath( '/intern', $blacklist ) === true && \Nino\Http::matchesPath( '/preview/client-a/draft', $blacklist ) === true && \Nino\Http::matchesPath( '/members', $blacklist ) === true && \Nino\Http::matchesPath( '/members/2026/plan', $blacklist ) === true && \Nino\Http::matchesPath( '/internal', $blacklist ) === false;
} )() );

$announce = static function( array &$appData, array $fields, int $status = 200, string $key = 'protected' ) : void {
	$event = [ 'action' => 'features/settings', 'panel' => 'x', 'status' => $status, 'user' => '', 'data' => [ 'key' => $key, 'fields' => $fields ] ];
	\Nino\Modules\ProtectedArea::callbackAdminAction( $appData, $event );
};
$epochOf = static fn(): string => (string) ( \Nino\Filesystem::getFileContent( $GLOBALS['appData'], '/data/protected-session.php', [] )['epoch'] ?? '' );
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => 'typed-in-the-features-panel' ] );
$beforeFeatures = $epochOf();
$announce( $appData, [ 'password' => '', 'paths' => '/intern' ] );
$stored = \Nino\Features::setting( $appData, 'protected', 'password' );
check( 'a password typed in the Features panel is stored as a hash and signs everybody out, and it is the one that unlocks',
	is_string( $stored ) === true && password_get_info( $stored )['algo'] !== null && $epochOf() !== $beforeFeatures && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/config.php' ) ), 'typed-in-the-features-panel' ) === false
	&& protectedUnlock( $appData, [ 'password' => 'typed-in-the-features-panel', 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 303 );
$beforeSave = $epochOf();
$announce( $appData, [ 'password' => '', 'paths' => '/intern' ] );
$announce( $appData, [ 'paths' => '/intern' ] );
$announce( $appData, [ 'password' => '' ], 400 );
$announce( $appData, [ 'password' => '' ], 200, 'another' );
check( 'a save that sent no password, was refused or is another feature\'s changes nothing and signs nobody out', $epochOf() === $beforeSave );
$_SERVER['REMOTE_ADDR'] = '198.51.100.81';
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => \Nino\Modules\ProtectedArea::hashPassword( $PASSWORD ), 'attempts' => 5 ] );
\Nino\Modules\ProtectedArea::signOutAll( $appData );

echo "\nThe workbench, the other languages, the methods and the headers\n";

$keptPaths = \Nino\Features::setting( $appData, 'protected', 'paths' );

// A line that folds to a tool or an endpoint, however it is spelled, is no prefix - nor is one that holds an escape, a backslash or a dot segment
$tools = [ '/%5Fadmin', '/x/../_admin', '/%2e%2e/_admin', '/x/../.protected', '//_admin', '/_Admin', '/.PROTECTED', '/a%2F..%2F_admin', '/a\\_admin', '/./_admin', '/intern/../x', '/intern%2Fx', '/' ];
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => array_merge( $tools, [ '/intern' ] ) ] );
$taken = array_filter( $tools, static fn( string $line ): bool => \Nino\Modules\ProtectedArea::cleanPrefix( $line ) !== null );
check( 'a line that folds to the front page, a tool or a module endpoint is no prefix, in no spelling - written by hand into the setting it protects nothing but what is a prefix'. ( $taken === [] ? '' : ' - '. implode( ' ', $taken ) ),
	$taken === [] && \Nino\Modules\ProtectedArea::prefixes( $appData ) === [ '/intern' ]
	&& array_filter( [ '/_admin', '/_admin/recovery.php', '/.protected', '/.protected/logout', '/' ], static fn( string $uri ): bool => \Nino\Modules\ProtectedArea::protects( $appData, $uri ) === true ) === [] );
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => $keptPaths ] );

// And the gate lets the workbench through whatever it is told: a page whose uri is protected, asked for at a path of the tools
$workbench = ninoVisit( $appData, '/_admin/recovery.php' );
$workbench['/nino/http/response']['uri'] = '/intern';
$workbench['/nino/http/response']['body'] = 'WORKBENCH';
\Nino\Modules\ProtectedArea::callbackGate( $appData, $workbench );
$other = ninoVisit( $appData, '/Intern' );
\Nino\Modules\ProtectedArea::callbackGate( $appData, $other );
$edge = [];
foreach( [ '/_admin/../intern', '/_adminx', '/_admin%2F..%2Fintern', '/_Admin/./../intern' ] as $near ) {
	$near = ninoVisit( $appData, $near );
	$near['/nino/http/response']['uri'] = '/intern';
	$near['/nino/http/response']['body'] = 'NEAR';
	\Nino\Modules\ProtectedArea::callbackGate( $appData, $near );
	if( $locked( $near ) === false )
		$edge[] = $near['/nino/http/request']['uri'];
}
check( 'the passage of the workbench ends where it does: an address that only begins like it, or leaves it by a dot segment, is shut where its page is'. ( $edge === [] ? '' : ' - '. implode( ' ', $edge ) ), $edge === [] );
check( 'the gate lets every address of the workbench through, the recovery page included, and still shuts the page next to it', $workbench['/nino/http/response']['body'] === 'WORKBENCH' && $workbench['/nino/http/response']['statusCode'] === 200 && $locked( $other ) === true );

// A prefix with capitals is the address it folds to
check( 'a prefix written with capitals shuts the address in lower case', $locked( protectedServe( $appData, '/club/area/room' ) ) === true && \Nino\Modules\ProtectedArea::protects( $appData, '/CLUB/area' ) === true );

// The gate holds for every method
$methods = [];
foreach( [ 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS' ] as $method ) {
	$sent = ninoVisit( $appData, '/intern/form', $method );
	$sent['/nino/http/response']['body'] = 'SECRET-FORM';
	\Nino\Modules\ProtectedArea::callbackGate( $appData, $sent );
	if( $locked( $sent ) === false )
		$methods[] = $method;
}
check( 'the gate holds for every method, not only GET and HEAD'. ( $methods === [] ? '' : ' - '. implode( ' ', $methods ) ), $methods === [] );

// A prefix that is the path of the page in one language shuts the page in every language
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => [ '/members' ] ] );
$open = array_filter( [ '/members', '/members/2026/plan', '/intern', '/intern/', '/news/hello-world' ], static fn( string $each ): bool => $locked( protectedServe( $GLOBALS['appData'], $each ) ) === false );
$seoList = [];
\Nino\Callbacks::doCallbacks( $appData, '/seo/exclude', $seoList );
$cacheProbe = $appData;
$cacheProbe['/nino/cache/blacklist'] = [];
\Nino\Modules\ProtectedArea::init( $cacheProbe );
check( 'a prefix that is the path of a page in one language shuts the page at its other addresses, keeps it out of the sitemap and out of the cache, and shuts no neighbour'. ( $open === [] ? '' : ' - '. implode( ' ', $open ) ),
	$open === [] && in_array( '/intern/*', $seoList, true ) === true && in_array( '/members/*', $seoList, true ) === true && in_array( '/internal/*', $seoList, true ) === false
	&& \Nino\Http::matchesPath( '/intern', $cacheProbe['/nino/cache/blacklist'] ) === true && \Nino\Http::matchesPath( '/internal', $cacheProbe['/nino/cache/blacklist'] ) === false
	&& $locked( protectedServe( $appData, '/internal' ) ) === false );
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => $keptPaths ] );

// An unlock gives the session a new id: the one that was known before is worth nothing
$identity = [ false ];
if( function_exists( 'proc_open' ) === true ) {
	$pipes = [];
	$process = proc_open( [ PHP_BINARY, __FILE__, 'session-worker', ninoSandboxDir( $appData ), '198.51.100.95', $PASSWORD ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, null, [ 'NINO_ROOT' => $root ] + $_ENV );
	$identity = (array) json_decode( trim( (string) stream_get_contents( $pipes[1] ) ), true );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	proc_close( $process );
}
check( 'an unlock gives the session a new id, so that an id known before it is no key (the worker started a real session, unlocked and compared)', $identity === [ true, true, true, 303 ] );

// The epoch of a header is the policy's: what a Features save refused changes nothing, even where a plain text password is waiting
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => 'typed-and-waiting' ] );
$epochWaiting = $epochOf();
$announce( $appData, [ 'password' => '' ], 500 );
$announce( $appData, [ 'password' => '' ], 403 );
check( 'a save of the Features panel that did not go through neither hashes the password that waits nor signs anybody out', $epochOf() === $epochWaiting && \Nino\Features::setting( $appData, 'protected', 'password' ) === 'typed-and-waiting' );
\Nino\Features::saveSettings( $appData, 'protected', [ 'password' => \Nino\Modules\ProtectedArea::hashPassword( $PASSWORD ) ] );
\Nino\Modules\ProtectedArea::signOutAll( $appData );

// What an unlocked visitor is shown is not the page the cache keeps: for an unlocked session the request is kept out of the cache, for another it is not
$_SERVER['REMOTE_ADDR'] = '198.51.100.96';
$cacheOf = static function( array $appData ): array {
	$request = ninoVisit( $appData, '/about' );
	$appData['/nino/cache/blacklist'] = [];
	\Nino\Modules\ProtectedArea::callbackGate( $appData, $request );
	return (array) $appData['/nino/cache/blacklist'];
};
$lockedList = $cacheOf( $appData );
protectedUnlock( $appData, [ 'password' => $PASSWORD, 'return' => '/intern' ] );
$unlockedList = $cacheOf( $appData );
check( 'where the visitor has unlocked the request is kept out of the page cache, for any page, and for a visitor without a session it is not: the link to lock again is never in a copy for others',
	\Nino\Http::matchesPath( '/about', $lockedList ) === false && \Nino\Http::matchesPath( '/', $lockedList ) === false
	&& \Nino\Http::matchesPath( '/about', $unlockedList ) === true && \Nino\Http::matchesPath( '/', $unlockedList ) === true );
\Nino\Modules\ProtectedArea::signOutAll( $appData );

// The error is tied to the field
$described = protectedUnlock( $appData, [ 'password' => 'not-it', 'return' => '/intern' ] );
$describedForm = $draw( (string) $described['/nino/http/response']['body'] );
check( 'the sentence of the error is the description of the field: the field names the id of the sentence that is shown, and every sentence has an id of its own',
	str_contains( $describedForm, 'aria-describedby="protected-error-wrong"' ) === true && substr_count( $describedForm, 'id="protected-error-wrong"' ) === 1 && substr_count( $describedForm, 'id="protected-error-locked"' ) === 1
	&& ( static function() use ( &$appData, $draw ): bool { unset( $appData['./protected/error'] ); return str_contains( $draw( '[protected-form]' ), 'aria-describedby' ) === false; } )() );

// A restored backup brings no epoch of an earlier time
$staged = ninoSandboxDir( $appData ). '/staged';
@mkdir( $staged. '/data', 0775, true );
file_put_contents( $staged. '/data/protected-session.php', '<?php return '. var_export( [ 'epoch' => 'abababababababababababababababab', 'pw' => 'x' ], true ). ';' );
$restoreArgs = [ 'dataDir' => ninoSandboxDir( $appData ). '/private/data', 'staging' => $staged ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $restoreArgs );
$restored = (array) include $staged. '/data/protected-session.php';
$bare = ninoSandboxDir( $appData ). '/staged-bare';
@mkdir( $bare, 0775, true );
$bareArgs = [ 'dataDir' => '', 'staging' => $bare ];
\Nino\Modules\ProtectedArea::callbackRestore( $appData, $bareArgs );
check( 'a restore gives the backup a new epoch before it is copied: the sessions of the epoch it brings are locked, a backup without the file gets one too',
	preg_match( '/^[0-9a-f]{32}$/', (string) ( $restored['epoch'] ?? '' ) ) === 1 && $restored['epoch'] !== 'abababababababababababababababab' && is_file( $bare. '/data/protected-session.php' ) === true );

// A route that names a tool or an endpoint of a module as its uri is never taken in: it cannot shut the way back in
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => [ '/endpoint', '/toolish', '/gone' ] ] );
$appData['/nino/http/routes']['GET://gone'] = [ 'uri' => '/404', 'statusCode' => 404, 'body' => 'X' ];
$appData['/nino/http/routes']['GET://endpoint'] = [ 'uri' => '/.protected/logout', 'body' => 'X' ];
$appData['/nino/http/routes']['GET://toolish'] = [ 'uri' => '/_admin', 'body' => 'X' ];
check( 'a route whose uri is a tool, a module endpoint or an error page adds nothing: the logout, the workbench and the 404 stay as they are while the routes themselves are shut',
	\Nino\Modules\ProtectedArea::protects( $appData, '/.protected/logout' ) === false && \Nino\Modules\ProtectedArea::protects( $appData, '/_admin/x' ) === false
	&& \Nino\Modules\ProtectedArea::protects( $appData, '/endpoint' ) === true && $locked( protectedServe( $appData, '/endpoint' ) ) === true
	&& protectedServe( $appData, '/.protected/logout' )['/nino/http/response']['statusCode'] === 303 && protectedServe( $appData, '/gibtesnicht' )['/nino/http/response']['statusCode'] === 404 );
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => $keptPaths ] );

// Two forms on a page have ids of their own
$appData['./protected/forms'] = 0;
$twice = $draw( '[protected-form][protected-form]' );
preg_match_all( '/ id="([^"]+)"/', $twice, $ids );
check( 'two forms on one page have no id in common', count( $ids[1] ) === 4 && count( array_unique( $ids[1] ) ) === 4 );

echo "\nThe panel\n";

$panel = \Nino\Modules\ProtectedArea\Admin::class;
check( 'the panel is registered while the feature is active, with a right of its own and a sign of its own',
	in_array( $panel, \Nino\Modules\ProtectedArea::adminPanels( $appData ), true ) === true && $panel::perm() === '/_admin/protected/manage'
	&& preg_match( '/^<svg [^>]*>(<rect [^>]*\/>|<path d="[^"]+"\/>)+<\/svg>$/', $panel::icon() ) === 1 );

ninoUser( $appData, 'editor@example.org', [] );
$settingsBefore = \Nino\Features::settings( $appData, 'protected' );
$epochBeforePanel = $epochOf();
$denied = [];
foreach( [ 'apiState' => [], 'apiSave' => [ 'paths' => '/open', 'pw' => 'brand-new-password' ], 'apiSignOut' => [] ] as $action => $data )
	$denied[$action] = ninoCall( $appData, [ $panel, $action ], $data )[0];
check( 'an account without the right is refused (403) by every action and nothing is written: no setting, no epoch', array_values( array_unique( $denied ) ) === [ 403 ]
	&& \Nino\Features::settings( $appData, 'protected' ) === $settingsBefore && $epochOf() === $epochBeforePanel );

ninoUser( $appData, 'admin@example.org', [ '/_admin/protected/manage' ] );
[ $status, $state ] = ninoCall( $appData, [ $panel, 'apiState' ], [] );
check( 'the state says the paths as written, that a password is set and how long it has to be - and never the password or its hash',
	$status === 200 && $state['paths'] === [ '/intern', '/preview/client-a', '/_admin', '/.protected', '/Club/Area' ] && $state['hasPassword'] === true && $state['minLength'] === 8 && $state['maxLength'] === 200
	&& array_keys( $state ) === [ 'paths', 'hasPassword', 'minLength', 'maxLength' ] && str_contains( json_encode( $state ), (string) \Nino\Features::setting( $appData, 'protected', 'password' ) ) === false );

$settingsBefore = \Nino\Features::settings( $appData, 'protected' );
$refused = [];
foreach( [ 'a line that is no path' => 'intern', 'a tool' => '/_admin/x', 'a module endpoint' => '/.protected', 'a control character' => "/a\x01b", 'no list' => [ 'a' => '/x' ], 'a number' => [ 5 ] ] as $why => $paths ) {
	$answer = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'paths' => $paths ] );
	if( $answer[0] !== 400 || str_starts_with( (string) ( $answer[1]['code'] ?? '' ), 'protected_path' ) === false )
		$refused[] = $why;
}
check( 'a path that cannot be protected, or no list of lines, is refused (400) with the code protected_path or protected_paths at the field - and nothing is written', $refused === []
	&& \Nino\Features::settings( $appData, 'protected' ) === $settingsBefore );
check( 'the lines of the field are saved as the paths, the password untouched, nobody signed out and the new state is the answer', ( static function() use ( &$appData, $epochOf ): bool {
	$epoch = $epochOf();
	[ $status, $body ] = ninoCall( $appData, [ \Nino\Modules\ProtectedArea\Admin::class, 'apiSave' ], [ 'paths' => "/intern\n\n /members/ \r\n/intern\n/preview/client-a", 'pw' => '' ] );
	return $status === 200 && $body['paths'] === [ '/intern', '/members/', '/preview/client-a' ] && \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/intern', '/members/', '/preview/client-a' ]
		&& $body['hasPassword'] === true && $epochOf() === $epoch && \Nino\Features::setting( $appData, 'protected', 'attempts' ) === 5;
} )() );
$keptPaths = \Nino\Features::setting( $appData, 'protected', 'paths' );
check( 'an empty field is accepted: it protects nothing', ninoCall( $appData, [ $panel, 'apiSave' ], [ 'paths' => '' ] )[0] === 200 && \Nino\Modules\ProtectedArea::protects( $appData, '/intern' ) === false );
\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => $keptPaths ] );

$beforeRefused = [ \Nino\Features::setting( $appData, 'protected', 'password' ), $epochOf() ];
$lengths = [];
foreach( [ 'short' => [ 'seven77', 'protected_short', 8 ], 'seven characters of two bytes' => [ 'äöüäöüä', 'protected_short', 8 ], 'long' => [ str_repeat( 'x', 201 ), 'protected_long', 200 ], 'no string' => [ [ 'x' ], 'protected_short', 8 ] ] as $why => [ $pw, $code, $limit ] ) {
	$answer = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'paths' => '/intern', 'pw' => $pw ] );
	if( $answer[0] !== 400 || $answer[1]['code'] !== $code || $answer[1]['params'] !== [ $limit ] || $answer[1]['field'] !== 'pw' )
		$lengths[] = $why;
}
check( 'a password that is too short or too long, counted in characters, is refused (400) with its code, the limit and the field - and the password and the epoch stay as they were',
	$lengths === [] && [ \Nino\Features::setting( $appData, 'protected', 'password' ), $epochOf() ] === $beforeRefused
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'paths' => '/intern', 'pw' => str_repeat( 'ä', 8 ) ] )[0] === 200 && ninoCall( $appData, [ $panel, 'apiSave' ], [ 'paths' => '/intern', 'pw' => str_repeat( 'ä', 200 ) ] )[0] === 200 );

\Nino\Runtime::unsetSessionValue( $appData, './protected/unlocked' );
protectedUnlock( $appData, [ 'password' => str_repeat( 'ä', 200 ), 'return' => '/intern' ] );
$unlockedBefore = \Nino\Modules\ProtectedArea::unlocked( $appData );
$_SERVER['REMOTE_ADDR'] = '198.51.100.82';
$epochBeforePw = $epochOf();
[ $status, $body, $answered ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'paths' => '/intern', 'pw' => 'the-new-shared-password' ] );
$logLine = $panel::log( 'protected/save', [ 'paths' => '/intern', 'pw' => 'the-new-shared-password' ] );
check( 'a new password is stored as a hash, signs everybody out, replaces the old one and is in no answer and no log line',
	$status === 200 && $unlockedBefore === true && \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && $epochOf() !== $epochBeforePw
	&& password_verify( base64_encode( hash( 'sha256', 'the-new-shared-password', true ) ), (string) \Nino\Features::setting( $appData, 'protected', 'password' ) ) === true
	&& protectedUnlock( $appData, [ 'password' => str_repeat( 'ä', 200 ), 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 401
	&& protectedUnlock( $appData, [ 'password' => 'the-new-shared-password', 'return' => '/intern' ] )['/nino/http/response']['statusCode'] === 303
	&& str_contains( json_encode( $answered ), 'the-new-shared-password' ) === false && str_contains( $logLine, 'the-new-shared-password' ) === false && str_contains( $logLine, 'password' ) === true );
check( 'every action that writes has a log line, and none of them names a password', $panel::log( 'protected/signout', [] ) !== '' && $panel::log( 'protected/save', [ 'pw' => '' ] ) !== '' && $panel::log( 'protected/state', [] ) === '' );
[ $status, $body ] = ninoCall( $appData, [ $panel, 'apiSignOut' ], [] );
check( 'signing everybody out answers 200 and locks the session that was unlocked, leaving the password and the paths as they were', $status === 200 && ( $body['signedOut'] ?? false ) === true
	&& \Nino\Modules\ProtectedArea::unlocked( $appData ) === false && \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/intern' ] );

// The script of the panel builds nothing itself, and every word it reads is one the panel brings in both languages (the guide, "What a panel does not build itself")
$script = (string) file_get_contents( $dir. '/assets/admin.js' );
$texts = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];
preg_match_all( "~'(/_admin/[a-z/_-]+)'~", $script, $read );
$own = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( $texts['en_US'] ) );
// What the panel does not bring has to be in the workbench's own words, in both languages: a key nobody has is an empty line
$kernelWords = [];
foreach( [ 'de_DE', 'en_US' ] as $locale )
	$kernelWords[$locale] = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( (array) include $root. '/_admin/text/'. $locale. '.php' ) );
$missingWords = array_filter( array_unique( $read[1] ), static fn( string $key ): bool => in_array( $key, $own, true ) === false && ( in_array( $key, $kernelWords['de_DE'], true ) === false || in_array( $key, $kernelWords['en_US'], true ) === false ) );
check( 'the panel script takes its bar, buttons, dialog and fields from the workbench: no button, confirm, alert, file input or button class of its own',
	preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(|nino-admin-btn-|type\s*[:=]\s*.file/', $script ) === 0 && str_contains( $script, 'Nino.adminUi.actionBar( {' ) === true );
check( 'the form is the shape of the guide: novalidate, a fieldset with its fieldgrid, one main button, errors under the field, the answer as a toast, unsaved input reported, the question of the sign-out of the level big and red',
	str_contains( $script, 'noValidate = true' ) === true && str_contains( $script, "'fieldset'" ) === true && str_contains( $script, 'nino-admin-fieldgrid' ) === true && substr_count( $script, 'primary :' ) === 1
	&& str_contains( $script, 'Nino.adminUi.fieldError(' ) === true && str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'dirty' ) === true
	&& preg_match( "/Nino\.adminUi\.confirm\(\s*'big'/", $script ) === 1 && str_contains( $script, "kind		: 'danger'" ) === true );
check( 'both languages of the panel have the same keys, and every word the script reads, the workbench\'s common ones included, is there in both languages', array_keys( $texts['de_DE'] ) === array_keys( $texts['en_US'] ) && array_values( $missingWords ) === [] && count( $read[1] ) > 8 );

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: protected-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/protected-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'protected-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

echo "\nThe cookie and the logs\n";

// The session cookie is the kernel's (\Nino\Runtime: HttpOnly, SameSite, Secure under https, its id from the session handler), and the feature adds none of its own
$code = '';
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && in_array( pathinfo( (string) $file, PATHINFO_EXTENSION ), [ 'php', 'js' ], true ) === true )
		$code .= (string) file_get_contents( (string) $file );
check( 'the feature sets no cookie and no session setting of its own, and no password variable reaches a log call',
	preg_match( '/setcookie\(|session_set_cookie_params|ini_set\(\s*.session|Set-Cookie/i', $code ) === 0
	&& preg_match( '/(?:error_log|trigger_error|var_dump|print_r|var_export|printf|console\.log)\([^;]*\$(?:password|pw|posted|stored|configured|hash)\b/', $code ) === 0 );

echo "\nSwitched off\n";

check( 'switched off, the panel is gone and the counter, the epoch and the settings stay', \Nino\Features::deactivate( $appData, 'protected' ) === true
	&& \Nino\Filesystem::fileExists( $appData, '/data/protected.php' ) === true && \Nino\Filesystem::fileExists( $appData, '/data/protected-session.php' ) === true
	&& \Nino\Features::setting( $appData, 'protected', 'paths' ) === [ '/intern' ] );
$off = ninoSandbox( 'protected-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the form and the link draw nothing', \Nino\Html::renderSource( $off, '[protected-form][protected-logout]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old components API, the old stylesheet vocabulary or the old template names is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|\bnino-(?!admin-fieldgrid)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\.|protected-error\]|hash_equals\( \$configured/', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
