<?php
declare(strict_types=1);

/**
 *	Nino
 *	redirects-smoke.php		Contract test for the Redirects feature
 *												(\Nino\Modules\Redirects): the manifest and activation
 *												through \Nino\Features, what a path and a target may
 *												be, the order rules are matched in and the loop guard,
 *												the live callback (an address with no route, one with
 *												a route, a subtree remainder, the project directory in
 *												the Location, POST left alone, a Location somebody
 *												else set), the addresses nothing answered and the
 *												shapes that are never written down, every panel action
 *												including the probe's three answers, the permission
 *												each of them is behind, and deactivation. Travels with
 *												the feature and runs against the checkout three levels
 *												up, or the one NINO_ROOT names (see tests/harness.php
 *												there).
 *
 *	Usage: php features/Redirects/tests/redirects-smoke.php
 *		   NINO_ROOT=../nino php features/Redirects/tests/redirects-smoke.php
 */

// Before the kernel loads: the autoloader and Features::dir() read the same
// constant, so the directory this feature lives in is the one searched -
// wherever NINO_ROOT points
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/**
 *	Build a request the way \Nino\Http::request() leaves it and resolve a
 *	response for it - the whole pipeline, so what is under test is the callback
 *	as it really runs and not a method called by hand
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$method
 *
 *	@return 	array										The response array
 */
function redirectsRequest( array &$appData, string $uri, string $method = 'GET' ): array {
	$request = [ 'REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1' ];
	\Nino\Http::request( $appData, $request );
	\Nino\Http::response( $appData, $request );
	return $request['/nino/http/response'];
}

/**
 *	Call one of the panel's actions the way the workbench does
 *
 *	@param		array 		&$appData
 *	@param		string		$method				An api* method name
 *	@param		array			$post
 *
 *	@return 	array										[ status, body ]
 */
function redirectsPanel( array &$appData, string $method, array $post = [] ): array {
	$_POST['data'] = json_encode( $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Redirects\Admin::$method( $appData, $request );
	$_POST = [];
	return [
		$request['/nino/http/response']['statusCode'],
		$request['/nino/http/response']['body'],
	];
}

/**
 *	The stored file, straight off disk - never through the request cache, so a
 *	check reads what was written rather than what was remembered
 *
 *	@param		array 		&$appData
 *
 *	@return 	array
 */
function redirectsFile( array &$appData ): array {
	unset( $appData['./redirects/rules'] );
	$file = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, [] );
	return is_array( $file ) ? $file : [];
}

$appData = ninoSandbox( 'redirects' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
// What a request always carries and a sandbox does not - a Location is built
// on it (see the other feature suites)
$appData['/nino/dir'] = '';


// --- The feature ------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir 			= dirname( __DIR__ );
$manifest	= \Nino\Features::manifest( $dir );

check( 'the manifest validates without a warning', is_array( $manifest ) && ninoWarnings() === [] );
check( 'key, class and version are what the directory says', is_array( $manifest ) && $manifest['key'] === 'redirects'
	&& $manifest['module'] === '\\Nino\\Modules\\Redirects' && $manifest['version'] === '1.0.0' );
check( 'it is system, and names the kernel it needs', ( $manifest['category'] ?? '' ) === 'system' && ( $manifest['nino'] ?? '' ) === '^1.3' );
check( 'the rules file is declared under data, so a backup carries it',
	in_array( \Nino\Modules\Redirects\Rules::PATH, (array) ( $manifest['data'] ?? [] ), true ) === true );
check( 'it offers the one switch that decides whether anything is written down',
	( $manifest['settings']['record']['type'] ?? '' ) === 'bool' && ( $manifest['settings']['record']['default'] ?? null ) === true );
check( 'it brings no route, no shortcode and no install unit', ( $manifest['manual']['routes'] ?? [] ) === []
	&& ( $manifest['manual']['shortcodes'] ?? [] ) === [] && ( $manifest['manual']['install'] ?? [] ) === [] );

check( 'the feature activates', \Nino\Features::activate( $appData, 'redirects' ) === true );
check( '...and its class is in the module list', in_array( '\\Nino\\Modules\\Redirects', $appData['/nino/modules'], true ) === true );

echo "\n";


// --- What a rule may be -----------------------------------------------------

echo "What a path and a target may be\n";

check( 'a path is absolute, without a query, without a trailing slash',
	\Nino\Modules\Redirects\Rules::path( '/old/page/' ) === '/old/page'
	&& \Nino\Modules\Redirects\Rules::path( '/old/page?utm=1#top' ) === '/old/page'
	&& \Nino\Modules\Redirects\Rules::path( ' /old/page ' ) === '/old/page' );
check( '...and nothing that could leave the site is one',
	\Nino\Modules\Redirects\Rules::path( 'old/page' ) === ''
	&& \Nino\Modules\Redirects\Rules::path( '//evil.example' ) === ''
	&& \Nino\Modules\Redirects\Rules::path( 'https://evil.example/x' ) === ''
	&& \Nino\Modules\Redirects\Rules::path( '/old/../../etc' ) === ''
	&& \Nino\Modules\Redirects\Rules::path( '/' ) === ''
	&& \Nino\Modules\Redirects\Rules::path( '' ) === '' );
// A header is a line, and a value carrying a newline is two of them
check( '...nor anything that could reach a header as a second line',
	\Nino\Modules\Redirects\Rules::path( "/old\r\nLocation: https://evil.example" ) === ''
	&& \Nino\Modules\Redirects\Rules::target( "https://example.com/x\r\nSet-Cookie: a=b" ) === '' );

check( 'a target is a path of this site or an https address of another',
	\Nino\Modules\Redirects\Rules::target( '/new/page' ) === '/new/page'
	&& \Nino\Modules\Redirects\Rules::target( 'https://example.com/new/' ) === 'https://example.com/new' );
/*	http is refused rather than upgraded: a redirect is the one moment a site
	chooses the next address for somebody, and choosing a plaintext one hands
	that request to whoever is on the wire	*/
check( '...and http is refused, as is anything without a host',
	\Nino\Modules\Redirects\Rules::target( 'http://example.com/new' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'https://' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'javascript:alert(1)' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'new/page' ) === '' );

check( 'a rule that sends an address to itself is a loop', \Nino\Modules\Redirects\Rules::loops( '/a', '/a', false ) !== '' );
check( '...and so is a subtree that sends into itself', \Nino\Modules\Redirects\Rules::loops( '/a', '/a/b', true ) !== '' );
check( '...while the same target is fine for a single page, and anywhere else always is',
	\Nino\Modules\Redirects\Rules::loops( '/a', '/a/b', false ) === ''
	&& \Nino\Modules\Redirects\Rules::loops( '/a', 'https://example.com/a', true ) === '' );

echo "\n";


// --- Normalising ------------------------------------------------------------

echo "What a stored file is held to\n";

$notes = [];
$normalised = \Nino\Modules\Redirects\Rules::normalize( [
	'rules' => [
		[ 'from' => '/shop', 'to' => '/store', 'subtree' => true ],
		[ 'from' => '/shop/archive', 'to' => '/store/old', 'status' => 302 ],
		[ 'from' => '/shop', 'to' => '/elsewhere' ],
		[ 'from' => '/bad', 'to' => 'http://example.com' ],
		[ 'from' => '/loop', 'to' => '/loop' ],
		[ 'from' => '/odd', 'to' => '/fine', 'status' => 418 ],
		'not a rule',
	],
	'misses' => [ '/gone' => [ 'count' => 3, 'last' => '2026-09-01 10:00:00' ], '/ignored' => 'not a miss' ],
], $notes );

check( 'the longest path comes first, so a rule under another is reached before it',
	array_column( $normalised['rules'], 'from' ) === [ '/shop/archive', '/shop', '/odd' ] );
/*	A note is a fill key and what to put in it, not a sentence: what a stored
	file is held to is Rules' business, which language the workbench says it in
	is the panel's (see Admin::_notes()). The panel used to print these in
	English into a German workbench	*/
$noteKeys = array_column( $notes, 'key' );
check( 'every note names a fill rather than carrying an English sentence', $notes !== []
	&& count( array_filter( $notes, static fn( mixed $n ): bool => is_array( $n ) && str_starts_with( (string) ( $n['key'] ?? '' ), '/_admin/redirects/' ) ) ) === count( $notes ) );
check( 'a second rule for one address is dropped, and said so', count( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/shop' ) ) === 1
	&& count( array_filter( $notes, static fn( array $n ): bool => $n['key'] === '/_admin/redirects/note/duplicate' && ( $n['inserts']['%s'] ?? '' ) === '/shop' ) ) === 1 );
check( 'a target that is not one takes its rule with it', count( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/bad' ) ) === 0 );
check( 'a rule that loops is dropped rather than stored for the browser to find out', count( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/loop' ) ) === 0 );
check( 'a status that is not a redirect becomes 301, and is not silent',
	( array_values( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/odd' ) )[0]['status'] ?? 0 ) === 301
	&& count( array_filter( $notes, static fn( array $n ): bool => $n['key'] === '/_admin/redirects/note/status' && ( $n['inserts']['%d'] ?? '' ) === '418' ) ) === 1 );
check( '...and a dropped loop carries its reason as a fill of its own, because the reason is one too',
	count( array_filter( $notes, static fn( array $n ): bool => $n['key'] === '/_admin/redirects/note/dropped' && ( $n['inserts']['%r'] ?? '' ) === '/_admin/redirects/reason/self' ) ) === 1 );
check( 'loops() names the reason with a key rather than a sentence', \Nino\Modules\Redirects\Rules::loops( '/a', '/a', false ) === '/_admin/redirects/reason/self'
	&& \Nino\Modules\Redirects\Rules::loops( '/a', '/a/b', true ) === '/_admin/redirects/reason/subtree' );
check( 'a miss that is not one is not a miss', array_keys( $normalised['misses'] ) === [ '/gone' ] );
check( 'every rule carries the whole shape, whatever the file said',
	array_keys( $normalised['rules'][0] ) === [ 'from', 'to', 'status', 'subtree', 'hits', 'last' ] );

$rules = $normalised['rules'];

check( 'an exact rule answers its own address', ( \Nino\Modules\Redirects\Rules::match( $rules, '/shop/archive' )['to'] ?? '' ) === '/store/old' );
/*	The exact rule wins even though the subtree rule would also match: "this
	page moved there" is a statement about that page	*/
check( '...even where a subtree rule covers it too', ( \Nino\Modules\Redirects\Rules::match( $rules, '/shop/archive' )['status'] ?? 0 ) === 302 );
check( 'a subtree rule answers everything below it, and carries the rest of the path over',
	( \Nino\Modules\Redirects\Rules::match( $rules, '/shop/socks/red' )['to'] ?? '' ) === '/store/socks/red' );
check( '...and the address it is written for', ( \Nino\Modules\Redirects\Rules::match( $rules, '/shop' )['to'] ?? '' ) === '/store' );
check( 'an address no rule covers is answered by none', \Nino\Modules\Redirects\Rules::match( $rules, '/shopping' ) === null
	&& \Nino\Modules\Redirects\Rules::match( $rules, '/other' ) === null );

echo "\n";


// --- The live request -------------------------------------------------------

echo "What happens to a request\n";

$appData['/nino/http/routes']['GET://here'] = [ 'uri' => '/here', 'body' => 'a page' ];

\Nino\Modules\Redirects\Rules::write( $appData, \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [
	[ 'from' => '/gone', 'to' => '/here', 'status' => 301 ],
	[ 'from' => '/moved', 'to' => '/here', 'status' => 302 ],
	[ 'from' => '/old-shop', 'to' => '/here', 'subtree' => true ],
	[ 'from' => '/away', 'to' => 'https://example.com/there' ],
	// A rule for an address a page answers: never consulted, and this is the
	// check that says so
	[ 'from' => '/here', 'to' => '/gone' ],
] ] ) );

$appData['/nino/modules'] = [ '\\Nino\\Modules\\Redirects' ];
\Nino\Modules::callModules( $appData, 'init' );

$response = redirectsRequest( $appData, '/gone' );
check( 'an address with no route is answered by its rule', $response['statusCode'] === 301 && ( $response['header']['Location'] ?? '' ) === '/here' );
check( '...with nothing in the body and nothing cached on top of it', $response['body'] === ''
	&& ( $response['header']['Cache-Control'] ?? '' ) === 'no-store' );

check( 'a temporary rule answers 302', ( redirectsRequest( $appData, '/moved' )['statusCode'] ?? 0 ) === 302 );
check( 'a subtree rule takes the rest of the path with it', ( redirectsRequest( $appData, '/old-shop/socks' )['header']['Location'] ?? '' ) === '/here/socks' );
check( 'a target on another site is passed through as it stands', ( redirectsRequest( $appData, '/away' )['header']['Location'] ?? '' ) === 'https://example.com/there' );

/*	The whole safety of the feature in one check: a path that has a route is
	answered by that route, whatever a rule says about it. Without this a
	mistyped rule could take a working page off the site	*/
$onPage = redirectsRequest( $appData, '/here' );
check( 'an address a page answers is left alone, rule or no rule', isset( $onPage['header']['Location'] ) === false && $onPage['statusCode'] === 200 );

$posted = redirectsRequest( $appData, '/gone', 'POST' );
check( 'a POST is never redirected - the browser would drop its body and repeat it as a GET', isset( $posted['header']['Location'] ) === false );

check( 'HEAD is, because it is a GET without the body', ( redirectsRequest( $appData, '/gone', 'HEAD' )['statusCode'] ?? 0 ) === 301 );

// The project directory is the one thing a rule cannot carry: the same rules
// have to work whether the site is at / or at /shop
$appData['/nino/dir'] = '/sub';
check( 'a site path is sent with the project directory in front of it', ( redirectsRequest( $appData, '/gone' )['header']['Location'] ?? '' ) === '/sub/here' );
check( '...and an address on another site is not', ( redirectsRequest( $appData, '/away' )['header']['Location'] ?? '' ) === 'https://example.com/there' );
$appData['/nino/dir'] = '';

echo "\n";


// --- The addresses nothing answered -----------------------------------------

echo "What is written down, and what is not\n";

redirectsRequest( $appData, '/no-such-page' );
redirectsRequest( $appData, '/no-such-page' );
redirectsRequest( $appData, '/another.html' );

$misses = redirectsFile( $appData )['misses'] ?? [];

check( 'an address nothing answered is remembered, and counted', ( $misses['/no-such-page']['count'] ?? 0 ) === 2 );
check( '...with the last time it was asked for', ( $misses['/no-such-page']['last'] ?? '' ) !== '' );
// What a site migrated from somewhere else still gets asked for
check( '...and an .html address is one somebody could have linked to', isset( $misses['/another.html'] ) === true );

/*	Everything else that reaches a 404 on a public site is a scanner, and a
	list of those is a list nobody reads twice	*/
foreach( [ '/wp-login.php', '/.env', '/vendor/phpunit/phpunit.xml', '/favicon.ico', '/_admin/typo', '/.nino/auth/nope' ] as $scan )
	redirectsRequest( $appData, $scan );

check( 'what a scanner asks for, and what is below /_ or /. , is not written down at all',
	array_keys( array_diff_key( redirectsFile( $appData )['misses'] ?? [], $misses ) ) === [] );

check( 'an address that has a rule is never a miss', isset( redirectsFile( $appData )['misses']['/gone'] ) === false );

// The switch, which is the same cost for both halves: a file write on a
// request that would otherwise have touched nothing
$appData['/nino/features']['redirects']['settings']['record'] = false;
redirectsRequest( $appData, '/not-collected' );
check( 'with remembering switched off, nothing new is collected', isset( redirectsFile( $appData )['misses']['/not-collected'] ) === false );

$before = redirectsFile( $appData );
redirectsRequest( $appData, '/gone' );
check( '...and a rule is still followed, it is only not counted', ( redirectsFile( $appData ) === $before ) === true );

unset( $appData['/nino/features']['redirects']['settings']['record'] );
redirectsRequest( $appData, '/gone' );
check( 'with it on, a rule counts the times it was followed',
	( array_values( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/gone' ) )[0]['hits'] ?? 0 ) > 0 );

echo "\n";


// --- The panel --------------------------------------------------------------

echo "The panel\n";

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

[ $status, $listed ] = redirectsPanel( $appData, 'apiList' );
check( 'the list answers the rules, the addresses and what the panel needs to say about itself', $status === 200
	&& is_array( $listed['rules'] ) && is_array( $listed['misses'] )
	&& $listed['statuses'] === \Nino\Modules\Redirects\Rules::STATUSES
	&& $listed['recording'] === true && $listed['limit'] === \Nino\Modules\Redirects\Rules::MISS_LIMIT );
check( '...with each address as a row rather than a key, so the table can sort on it',
	array_keys( $listed['misses'][0] ?? [] ) === [ 'path', 'count', 'last' ] );

[ $status, $saved ] = redirectsPanel( $appData, 'apiSave', [ 'from' => '/press/', 'to' => '/news', 'status' => 302, 'subtree' => false, 'was' => '' ] );
check( 'a rule can be added, and its address is normalised on the way in', $status === 200 && $saved['saved'] === '/press'
	&& count( array_filter( $saved['rules'], static fn( array $r ): bool => $r['from'] === '/press' ) ) === 1 );
check( '...and it answers straight away', ( redirectsRequest( $appData, '/press' )['header']['Location'] ?? '' ) === '/news' );

[ $status, $renamed ] = redirectsPanel( $appData, 'apiSave', [ 'from' => '/presse', 'to' => '/news', 'status' => 301, 'subtree' => false, 'was' => '/press' ] );
check( 'a rule can be renamed in one save rather than a delete and an add', $status === 200
	&& count( array_filter( $renamed['rules'], static fn( array $r ): bool => $r['from'] === '/press' ) ) === 0
	&& count( array_filter( $renamed['rules'], static fn( array $r ): bool => $r['from'] === '/presse' ) ) === 1 );

check( 'an address that is not one is refused, in the panel\'s own words', redirectsPanel( $appData, 'apiSave', [ 'from' => 'nope', 'to' => '/news' ] )[0] === 400 );
check( 'so is a target that is not one', redirectsPanel( $appData, 'apiSave', [ 'from' => '/x', 'to' => 'http://example.com' ] )[0] === 400 );
check( 'and so is a rule that would send a visitor back into itself', redirectsPanel( $appData, 'apiSave', [ 'from' => '/x', 'to' => '/x' ] )[0] === 400
	&& redirectsPanel( $appData, 'apiSave', [ 'from' => '/x', 'to' => '/x/y', 'subtree' => true ] )[0] === 400 );

// An address that has a rule is answered now, so it is not an address nothing
// answers - leaving it on the list would have somebody write the same rule twice
redirectsRequest( $appData, '/orphan' );
check( 'the address is on the list first', isset( redirectsFile( $appData )['misses']['/orphan'] ) === true );
redirectsPanel( $appData, 'apiSave', [ 'from' => '/orphan', 'to' => '/here' ] );
check( '...and saving a rule for it takes it off', isset( redirectsFile( $appData )['misses']['/orphan'] ) === false );

[ $status, $probed ] = redirectsPanel( $appData, 'apiProbe', [ 'path' => '/here' ] );
check( 'the probe says when a page answers an address, so no rule is consulted', $status === 200 && $probed['answer'] === 'route' );
[ $status, $probed ] = redirectsPanel( $appData, 'apiProbe', [ 'path' => '/old-shop/socks' ] );
check( '...which rule answers it, and where it sends', $status === 200 && $probed['answer'] === 'rule'
	&& $probed['from'] === '/old-shop' && $probed['to'] === '/here/socks' && $probed['status'] === 301 );
[ $status, $probed ] = redirectsPanel( $appData, 'apiProbe', [ 'path' => '/nothing-here' ] );
check( '...and when nothing does', $status === 200 && $probed['answer'] === 'nothing' );
check( 'a probe for something that is not an address is a 400', redirectsPanel( $appData, 'apiProbe', [ 'path' => 'nope' ] )[0] === 400 );

[ $status, $deleted ] = redirectsPanel( $appData, 'apiDelete', [ 'from' => '/presse' ] );
check( 'a rule can be deleted', $status === 200 && count( array_filter( $deleted['rules'], static fn( array $r ): bool => $r['from'] === '/presse' ) ) === 0 );
check( '...and deleting one that is not there is a 404, not a silent success', redirectsPanel( $appData, 'apiDelete', [ 'from' => '/presse' ] )[0] === 404 );

redirectsRequest( $appData, '/one-more' );
check( 'one address can be forgotten', redirectsPanel( $appData, 'apiForget', [ 'path' => '/one-more' ] )[0] === 200
	&& isset( redirectsFile( $appData )['misses']['/one-more'] ) === false );
check( '...and all of them at once', redirectsPanel( $appData, 'apiForget' )[0] === 200
	&& ( redirectsFile( $appData )['misses'] ?? [] ) === [] );
check( '...while the rules are untouched by that', ( redirectsFile( $appData )['rules'] ?? [] ) !== [] );

/*	And the panel resolves them, in the language the operator picked - which is
	the whole point of the keys. A rule the stored file cannot use is written
	straight into it here, because that is the only way a note reaches apiList
	at all: the panel refuses such a rule on the way in	*/
$noteFileBefore = redirectsFile( $appData );
$noteFillsDe = include __DIR__. '/../text/de_DE.php';
$noteFillsEn = include __DIR__. '/../text/en_US.php';
$noteRead = static function( array &$appData ): array {
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, [
		'rules'	=> [ [ 'from' => '/loop-note', 'to' => '/loop-note', 'status' => 301, 'subtree' => false, 'hits' => 0, 'last' => '' ] ],
		'misses'=> [],
	] );
	unset( $appData['./redirects/rules'] );
	return redirectsPanel( $appData, 'apiList' )[1]['notes'] ?? [];
};
$noteSaid = static fn( array $fills ): string => strtr(
	(string) $fills['[[/_admin/redirects/note/dropped]]'],
	[ '%s' => '/loop-note', '%r' => (string) $fills['[[/_admin/redirects/reason/self]]'] ]
);

$appData['/nino/locales/available'] = [ 'de_DE', 'en_US' ];
$noteNative = $noteRead( $appData );
check( 'the notes reach the browser as sentences rather than keys', count( $noteNative ) === 1
	&& is_string( $noteNative[0] )
	&& str_contains( $noteNative[0], '/_admin/redirects/' ) === false );
check( '...put together out of the panel\'s own fills, the reason among them', $noteNative[0] === $noteSaid( $noteFillsDe ) );

\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'en_US' );
$noteOther = $noteRead( $appData );
check( '...and in the interface language the operator picked, not the project\'s', count( $noteOther ) === 1
	&& $noteOther[0] === $noteSaid( $noteFillsEn )
	&& $noteOther[0] !== $noteNative[0] );
\Nino\Runtime::setSessionValue( $appData, './admin/locale', '' );
// Exactly what stood here before, so the sections after this one still find it
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, $noteFileBefore );
unset( $appData['./redirects/rules'] );

echo "\n";


// --- Who may do any of it ---------------------------------------------------

echo "The permission every action is behind\n";

\Nino\Auth::logoutUser( $appData );

check( 'nobody logged in gets nothing at all', redirectsPanel( $appData, 'apiList' )[0] === 401
	&& redirectsPanel( $appData, 'apiSave', [ 'from' => '/a', 'to' => '/b' ] )[0] === 401
	&& redirectsPanel( $appData, 'apiDelete', [ 'from' => '/a' ] )[0] === 401
	&& redirectsPanel( $appData, 'apiForget' )[0] === 401
	&& redirectsPanel( $appData, 'apiProbe', [ 'path' => '/a' ] )[0] === 401 );

\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/elements/manage' ] );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );

check( 'an account with another permission gets a 403 from every one of them', redirectsPanel( $appData, 'apiList' )[0] === 403
	&& redirectsPanel( $appData, 'apiSave', [ 'from' => '/a', 'to' => '/b' ] )[0] === 403
	&& redirectsPanel( $appData, 'apiDelete', [ 'from' => '/a' ] )[0] === 403
	&& redirectsPanel( $appData, 'apiForget' )[0] === 403
	&& redirectsPanel( $appData, 'apiProbe', [ 'path' => '/a' ] )[0] === 403 );

\Nino\Auth::logoutUser( $appData );

echo "\n";


// --- Deactivation -----------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'redirects' ) === true );
check( 'the class is out of the module list', in_array( '\\Nino\\Modules\\Redirects', $appData['/nino/modules'], true ) === false );
// Everything a feature keeps is the project's - putting it back finds its
// rules where it left them
check( '...and the rules it wrote are still on disk', ( redirectsFile( $appData )['rules'] ?? [] ) !== [] );

ninoDone( $appData );
