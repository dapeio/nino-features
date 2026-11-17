<?php
declare(strict_types=1);

/**
 *	Nino features
 *	redirects-smoke.php		Contract test for the Redirects feature (\Nino\Modules\Redirects): the manifest
 *												and activation, what a path and a target may be, the order rules are matched
 *												in and the loop guard, the live callback (an address with no route, one with a
 *												route, a subtree remainder, the project directory in the Location, POST left
 *												alone), the addresses nothing answered, every action of the panel with the code
 *												each refusal answers with, the permission each is behind, and what the script
 *												of the panel builds and says. Runs against the checkout three levels up, or the
 *												one NINO_ROOT names.
 *
 *	Usage: php features/Redirects/tests/redirects-smoke.php
 *		   NINO_ROOT=../nino php features/Redirects/tests/redirects-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';


/**
 *	Build a request the way \Nino\Http::request() leaves it and resolve a
 *	response for it - the whole pipeline, so what is under test is the callback
 *	as it really runs and not a method called by hand. ninoVisit() stops after
 *	the request is built, and the callback under test is one of the response:
 *	this runs \Nino\Http::response() as well
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$method
 *
 *	@return 	array										The response array
 */
function redirectsRequest( array &$appData, string $uri, string $method = 'GET' ): array {
	$request = ninoVisit( $appData, $uri, $method );
	\Nino\Http::response( $appData, $request );
	return $request['/nino/http/response'];
}

/**
 *	Let the time between two notes pass: the times of the last notes the file
 *	holds are taken out of it, which is how a check that asks for the next note
 *	waits out Rules::NOTE_INTERVAL without sleeping
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function redirectsRested( array &$appData ): void {
	$file = redirectsFile( $appData );
	if( $file === [] )
		return;
	unset( $file['noted'] );
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, $file );
	unset( $appData['./redirects/rules'] );
}

/**
 *	The sentence the panel says for a code in one language, as its text
 *	directory has it - '' for a code without one
 *
 *	@param		array 		&$appData
 *	@param		string		$code
 *	@param		string		$locale
 *
 *	@return 	string
 */
function redirectsSentence( array &$appData, string $code, string $locale ): string {
	return (string) ( \Nino\Admin\Admin::textFills( $appData, '/features/Redirects/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' );
}

/**
 *	Store a file of the shape the panel writes, whole and over whatever is
 *	there, and forget the copy this request read - the setup of a check that
 *	needs a state the panel would refuse to make
 *
 *	@param		array 		&$appData
 *	@param		array 		$file					A normalised file: rules and misses
 *
 *	@return 	void
 */
function redirectsStore( array &$appData, array $file ): void {
	unset( $appData['./redirects/rules'] );
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, [
		'format'	=> \Nino\Modules\Redirects\Rules::FORMAT,
		'rules'		=> array_values( $file['rules'] ?? [] ),
		'misses'	=> $file['misses'] ?? [],
	] );
	redirectsRested( $appData );
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

/**
 *	The class a call threw, or '' where it returned. A call that may throw goes
 *	through this rather than standing in the open: an uncaught throwable here
 *	ends the suite at the line it happened on, and what is under test is that
 *	nothing is thrown at all
 *
 *	@param		callable	$callback
 *
 *	@return 	string								The throwable's class, or ''
 */
function redirectsThrew( callable $callback ): string {
	try {
		$callback();
	} catch( \Throwable $error ) {
		return $error::class;
	}
	return '';
}

$appData = ninoSandbox( 'redirects' );
$panel = \Nino\Modules\Redirects\Admin::class;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
// What a request always carries and a sandbox does not - a Location is built
// on it (see the other feature suites)
$appData['/nino/dir'] = '';

// --- The feature ------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir 			= dirname( __DIR__ );
$manifest	= \Nino\Features::manifest( $dir );

check( 'the manifest validates without a warning, for this kernel, and brings no route, no shortcode and no install unit', is_array( $manifest ) && ninoWarnings() === []
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false
	&& ( $manifest['manual']['routes'] ?? [] ) === [] && ( $manifest['manual']['shortcodes'] ?? [] ) === [] && ( $manifest['manual']['install'] ?? [] ) === [] );
check( 'the rules file is declared under data, so a backup carries it, and one switch decides whether anything is written down',
	in_array( \Nino\Modules\Redirects\Rules::PATH, (array) ( $manifest['data'] ?? [] ), true ) === true
	&& ( $manifest['settings']['record']['type'] ?? '' ) === 'bool' && ( $manifest['settings']['record']['default'] ?? null ) === true );

check( 'the feature activates, its class is in the module list and its panel is in the registry with the Settings tab', \Nino\Features::activate( $appData, 'redirects' ) === true
	&& in_array( '\\Nino\\Modules\\Redirects', $appData['/nino/modules'], true ) === true
	&& isset( \Nino\Admin\Admin::panels( $appData )['redirects']['tabs']['redirects-settings'] ) === true );

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
// A browser reads a backslash after the slash as a second slash, and \Nino\Http::redirect() refuses it as it refuses '//'
check( '...nor a backslash where the second slash would be, which a browser reads as one',
	\Nino\Modules\Redirects\Rules::path( '/\\evil.example' ) === '' && \Nino\Modules\Redirects\Rules::target( '/\\evil.example' ) === ''
	&& \Nino\Modules\Redirects\Rules::path( '/a\\b' ) === '/a\\b' );
// A header is a line, and a value carrying a newline is two of them
check( '...nor anything that could reach a header as a second line',
	\Nino\Modules\Redirects\Rules::path( "/old\r\nLocation: https://evil.example" ) === ''
	&& \Nino\Modules\Redirects\Rules::target( "https://example.com/x\r\nSet-Cookie: a=b" ) === '' );

check( 'a target is a path of this site or an https address of another',
	\Nino\Modules\Redirects\Rules::target( '/new/page' ) === '/new/page'
	&& \Nino\Modules\Redirects\Rules::target( 'https://example.com/new/' ) === 'https://example.com/new' );
// The kernel's redirect sends a path whose first segment holds no colon, which a browser would read as a scheme
check( '...and a path is one the kernel\'s redirect would send: a colon in the first segment is none, a colon further in is',
	\Nino\Modules\Redirects\Rules::target( '/mailto:me@example.com' ) === '' && \Nino\Modules\Redirects\Rules::target( '/new:page' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( '/new/page:2' ) === '/new/page:2' && \Nino\Modules\Redirects\Rules::path( '/old:page' ) === '/old:page' );
// ...and an address on another site is one it would send: a host, no credentials in front of it, a length a header takes
check( '...and so is an https address: one with credentials in it, or one too long for a header, is none',
	\Nino\Modules\Redirects\Rules::target( 'https://user:secret@example.com/new' ) === '' && \Nino\Modules\Redirects\Rules::target( 'https://user@example.com/new' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'https:///new' ) === '' && \Nino\Modules\Redirects\Rules::target( 'https://example.com/'. str_repeat( 'a', 2048 ) ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'https://example.com:8443/new?x=1' ) === 'https://example.com:8443/new?x=1' );
/*	http is refused rather than upgraded: a redirect is the one moment a site
	chooses the next address for somebody, and choosing a plaintext one hands
	that request to whoever is on the wire	*/
check( '...and http is refused, as is anything without a host',
	\Nino\Modules\Redirects\Rules::target( 'http://example.com/new' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'https://' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'javascript:alert(1)' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( 'new/page' ) === '' );

/*	The home page is a target although it is not a path a rule can start from:
	path( '/' ) is '' and stays so (a rule from '/' is refused above), while
	target( '/' ) is '/' - an old address that moved to the front page is the
	commonest rule there is, and it used to be refused as "not a target"	*/
check( 'the home page is a target, though it can never be an old address',
	\Nino\Modules\Redirects\Rules::target( '/' ) === '/' && \Nino\Modules\Redirects\Rules::target( ' / ' ) === '/'
	&& \Nino\Modules\Redirects\Rules::path( '/' ) === ''
	&& \Nino\Modules\Redirects\Rules::target( '//' ) === '' && \Nino\Modules\Redirects\Rules::target( '' ) === '' );
check( '...also with a query or a fragment, as any other path is', \Nino\Modules\Redirects\Rules::target( '/?x=1' ) === '/'
	&& \Nino\Modules\Redirects\Rules::target( '/#top' ) === '/' && \Nino\Modules\Redirects\Rules::target( '/a?x' ) === '/a'
	&& \Nino\Modules\Redirects\Rules::target( '/?x' ) === \Nino\Modules\Redirects\Rules::target( '/' ) );

check( 'a target is held to the length a header takes, a path as well as an address of another site',
	\Nino\Modules\Redirects\Rules::target( '/'. str_repeat( 'a', \Nino\Modules\Redirects\Rules::TARGET_LENGTH - 1 ) ) !== ''
	&& \Nino\Modules\Redirects\Rules::target( '/'. str_repeat( 'a', \Nino\Modules\Redirects\Rules::TARGET_LENGTH ) ) === '' );
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
check( 'every note names a fill rather than carrying an English sentence', $notes !== []
	&& count( array_filter( $notes, static fn( mixed $n ): bool => is_array( $n ) && str_starts_with( (string) ( $n['key'] ?? '' ), '/_admin/redirects/' ) ) ) === count( $notes ) );
check( 'a second rule for one address is dropped, and said so', count( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/shop' ) ) === 1
	&& count( array_filter( $notes, static fn( array $n ): bool => $n['key'] === '/_admin/redirects/note/duplicate' && ( $n['inserts']['%s'] ?? '' ) === '/shop' ) ) === 1 );
check( 'a target that is not one takes its rule with it', count( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/bad' ) ) === 0 );
check( 'a rule that loops is dropped rather than stored for the browser to find out', count( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/loop' ) ) === 0 );
check( 'a status that is not a redirect becomes 301, and is not silent',
	( array_values( array_filter( $normalised['rules'], static fn( array $r ): bool => $r['from'] === '/odd' ) )[0]['status'] ?? 0 ) === 301
	&& count( array_filter( $notes, static fn( array $n ): bool => $n['key'] === '/_admin/redirects/note/status' && ( $n['inserts']['%d'] ?? '' ) === '418' ) ) === 1 );
check( '...and a dropped loop carries its reason as a fill of its own, the words of the code the loop is refused with',
	count( array_filter( $notes, static fn( array $n ): bool => $n['key'] === '/_admin/redirects/note/dropped' && ( $n['inserts']['%r'] ?? '' ) === '/_admin/error/redirects_loop_self' ) ) === 1 );
check( 'loops() names the reason with its code rather than a sentence', \Nino\Modules\Redirects\Rules::loops( '/a', '/a', false ) === 'redirects_loop_self'
	&& \Nino\Modules\Redirects\Rules::loops( '/a', '/a/b', true ) === 'redirects_loop_subtree' );
check( 'a miss that is not one is not a miss', array_keys( $normalised['misses'] ) === [ '/gone' ] );
$timed = \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [ [ 'from' => '/a', 'to' => '/b' ] ], 'noted' => [ 'miss' => 'soon', 'hit' => [ 'at' => 5, 'n' => -2 ], 'rules' => [ '/a' => 5, '/not-a-rule' => 7, '/a/b' => 'x' ] ] ] )['noted'];
check( 'the times of the last notes are held to Unix times of the rules there are, and nothing else is kept', $timed === [ 'miss' => 0, 'hit' => [ 'at' => 5, 'n' => 0 ], 'rules' => [ '/a' => 5 ] ] );
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

redirectsStore( $appData, \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [
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

/*	What the kernel's redirect will not send is not sent: the rest of a path
	that moved to the front page is what follows the old address, so a request
	for '/moved-home//evil.example' makes '//evil.example' of it - the address
	of another host, which the Location used to be built from as it stood. The
	response is the 404 it was, a warning says why, and the rule has not been
	followed	*/
$fileBeforeHome = redirectsFile( $appData );
redirectsStore( $appData, \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [ [ 'from' => '/moved-home', 'to' => '/', 'subtree' => true ] ] ] ) );
ninoWarnings();
$refusedBySubtree = redirectsRequest( $appData, '/moved-home//evil.example' );
check( 'a Location that would be the address of another host is not sent: the response is the 404 it was, and nothing is written to the log for it, however often it is asked',
	isset( $refusedBySubtree['header']['Location'] ) === false && $refusedBySubtree['statusCode'] === 404 && ninoWarnings() === []
	&& isset( redirectsRequest( $appData, '/moved-home//evil.example' )['header']['Location'] ) === false && ninoWarnings() === [] );
check( '...and a rule that was not followed is not counted as one', ( redirectsFile( $appData )['rules'][0]['hits'] ?? -1 ) === 0 );
check( '...while the same rule sends the pages that are paths', ( redirectsRequest( $appData, '/moved-home/page' )['header']['Location'] ?? '' ) === '/page'
	&& ( redirectsFile( $appData )['rules'][0]['hits'] ?? -1 ) === 1 );
redirectsStore( $appData, $fileBeforeHome );

echo "What is written down, and what is not\n";

redirectsRequest( $appData, '/no-such-page' );
redirectsRested( $appData );
redirectsRequest( $appData, '/no-such-page' );
redirectsRested( $appData );
redirectsRequest( $appData, '/another.html' );

$misses = redirectsFile( $appData )['misses'] ?? [];

check( 'an address nothing answered is remembered, and counted', ( $misses['/no-such-page']['count'] ?? 0 ) === 2 );
check( '...with the last time it was asked for', ( $misses['/no-such-page']['last'] ?? '' ) !== '' );
// What a site migrated from somewhere else still gets asked for
check( '...and an .html address is one somebody could have linked to', isset( $misses['/another.html'] ) === true );

/*	Everything else that reaches a 404 on a public site is a scanner, and a
	list of those is a list nobody reads twice	*/
foreach( [ '/wp-login.php', '/.env', '/vendor/phpunit/phpunit.xml', '/favicon.ico', '/_admin/typo', '/.nino/auth/nope' ] as $scan ) {
	redirectsRested( $appData );
	redirectsRequest( $appData, $scan );
}

check( 'what a scanner asks for, and what is below /_ or /. , is not written down at all',
	array_keys( array_diff_key( redirectsFile( $appData )['misses'] ?? [], $misses ) ) === [] );

redirectsRested( $appData );
redirectsRequest( $appData, '/gone' );
check( 'an address that has a rule is never a miss, though a rule that is followed is a note of its own', isset( redirectsFile( $appData )['misses']['/gone'] ) === false
	&& ( array_values( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/gone' ) )[0]['hits'] ?? 0 ) > 0 );

// The switch, which is the same cost for both halves: a file write on a
// request that would otherwise have touched nothing
$appData['/nino/features']['redirects']['settings']['record'] = false;
redirectsRested( $appData );
redirectsRequest( $appData, '/not-collected' );
check( 'with remembering switched off, nothing new is collected', isset( redirectsFile( $appData )['misses']['/not-collected'] ) === false );

redirectsRested( $appData );
$before = redirectsFile( $appData );
check( '...and a rule is followed all the same, it is only not counted', ( redirectsRequest( $appData, '/gone' )['statusCode'] ?? 0 ) === 301 && ( redirectsFile( $appData ) === $before ) === true );

unset( $appData['/nino/features']['redirects']['settings']['record'] );
redirectsRested( $appData );
redirectsRequest( $appData, '/gone' );
check( 'with it on, a rule counts the times it was followed',
	( array_values( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/gone' ) )[0]['hits'] ?? 0 ) > 0 );

echo "\n";
// --- What is held to its bounds ---------------------------------------------

echo "The list of unanswered addresses\n";

$fileBeforeStream	= redirectsFile( $appData );
$listFile					= ninoSandboxDir( $appData ). '/private/data/redirects.php';
$limit						= \Nino\Modules\Redirects\Rules::MISS_PATH_LENGTH;

redirectsRested( $appData );
$beforeLong	= (string) file_get_contents( $listFile );
check( 'an address past the bound is the 404 it is and is not written down, one of exactly the bound is',
	redirectsRequest( $appData, '/'. str_repeat( 'a', 6000 ) )['statusCode'] === 404 && redirectsRequest( $appData, '/'. str_repeat( 'b', $limit ) )['statusCode'] === 404
	&& (string) file_get_contents( $listFile ) === $beforeLong
	&& ( redirectsRequest( $appData, '/'. str_repeat( 'c', $limit - 1 ) )['statusCode'] === 404 ) && isset( redirectsFile( $appData )['misses']['/'. str_repeat( 'c', $limit - 1 )] ) === true );

// A note inside the interval is not written, one after it is: the times the file holds decide, not the clock of the test
$twoRules = \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [ [ 'from' => '/followed', 'to' => '/here' ], [ 'from' => '/followed-too', 'to' => '/here' ] ] ] );
redirectsStore( $appData, $twoRules );
$file = redirectsFile( $appData );
$file['noted'] = [ 'miss' => time(), 'hit' => [ 'at' => 0, 'n' => 0 ], 'rules' => [ '/followed' => time() ] ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, $file );
unset( $appData['./redirects/rules'] );
check( 'a note made just now holds the next one back, for the list and for a rule, and one a second old does not',
	\Nino\Modules\Redirects\Rules::missDue( redirectsFile( $appData ) ) === false && \Nino\Modules\Redirects\Rules::hitDue( redirectsFile( $appData ), '/followed' ) === false
	&& \Nino\Modules\Redirects\Rules::hitDue( redirectsFile( $appData ), '/followed-too' ) === true
	&& ( static function() use ( $file, &$appData ): bool {
		$file['noted'] = [ 'miss' => time() - \Nino\Modules\Redirects\Rules::NOTE_INTERVAL, 'hit' => [ 'at' => 0, 'n' => 0 ], 'rules' => [ '/followed' => time() - \Nino\Modules\Redirects\Rules::NOTE_INTERVAL ] ];
		return \Nino\Modules\Redirects\Rules::missDue( $file ) === true && \Nino\Modules\Redirects\Rules::hitDue( $file, '/followed' ) === true;
	} )() );
redirectsRequest( $appData, '/held-back' );
check( 'an address asked for inside the interval is a 404 that is not noted', isset( redirectsFile( $appData )['misses']['/held-back'] ) === false );

// The list is held to its ceiling in number and in the length of each, whatever the file says
$overfull = [];
for( $n = 0; $n < 3 * \Nino\Modules\Redirects\Rules::MISS_LIMIT; $n++ )
	$overfull['/over/'. $n] = [ 'count' => 1 + $n % 7, 'last' => '2026-09-01 10:00:00' ];
redirectsStore( $appData, [ 'rules' => [], 'misses' => [] ] );
for( $n = 0; $n < 120; $n++ ) {
	redirectsRested( $appData );
	redirectsRequest( $appData, str_pad( '/long-'. $n. '-', $limit, 'x' ) );
}
$listed = redirectsFile( $appData )['misses'] ?? [];
check( 'many different addresses of the longest length leave the list at its ceiling, none longer than the bound, and a file that holds more is read as the ceiling',
	count( $listed ) === \Nino\Modules\Redirects\Rules::MISS_LIMIT && max( array_map( 'strlen', array_keys( $listed ) ) ) <= $limit
	&& count( \Nino\Modules\Redirects\Rules::normalize( [ 'misses' => $overfull ] )['misses'] ) === \Nino\Modules\Redirects\Rules::MISS_LIMIT );

redirectsStore( $appData, $fileBeforeStream );

echo "\n";


// --- A file somebody shaped by hand -----------------------------------------

echo "A list somebody edited by hand\n";

// Both sections below put a file of their own on disk, so what the rest of
// the suite was left with is kept here and put back when they are done
$fileBeforeEdits = redirectsFile( $appData );

/*	The file may be edited by hand, and a rule out of that file is held to
	what a rule may be before any of it is used. The list of addresses was not:
	noteMiss() sorted whatever stood under 'misses' straight out of the file,
	so an entry a person had shaped by hand - a note to self where a count and
	a time belong - reached the sort as a string and took every unanswered
	request on the site down with it	*/
$handEdited = static function( array &$appData, mixed $entry ): void {
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, [
		'format'	=> \Nino\Modules\Redirects\Rules::FORMAT,
		'rules'		=> [],
		'misses'	=> [ '/hand/edited' => $entry ],
	] );
	redirectsRested( $appData );
	unset( $appData['./redirects/rules'] );
};

$handEdited( $appData, 'a note to self' );
check( 'an address nothing answers is still a 404 rather than a 500 when the list was edited by hand',
	redirectsThrew( static fn(): array => redirectsRequest( $appData, '/after/a/hand/edit' ) ) === ''
	&& isset( redirectsFile( $appData )['misses']['/after/a/hand/edit'] ) === true );
check( '...and the entry that is not one is gone rather than written back',
	isset( redirectsFile( $appData )['misses']['/hand/edited'] ) === false && ninoWarnings() === [] );

$handEdited( $appData, [ 'last' => '2026-09-01 10:00:00' ] );
redirectsRequest( $appData, '/after/a/half/edit' );
check( 'an entry missing its count is counted from one rather than read as nothing',
	( redirectsFile( $appData )['misses']['/hand/edited']['count'] ?? 0 ) === 1 && ninoWarnings() === [] );

echo "\n";


// --- A full list ------------------------------------------------------------

echo "A list that is already full\n";

/*	The list is the ceiling and the cut, and the cut used to happen after the
	new address had been counted but before it had a count anybody could see: it
	arrived at one, sorted under everything asked for twice, and was cut off
	again on every request. A list that once filled up therefore answered about
	the pages that broke last year and could never learn about the one that
	broke today	*/
$full = [];
for( $n = 0; $n < \Nino\Modules\Redirects\Rules::MISS_LIMIT; $n++ )
	$full['/old/'. $n] = [ 'count' => 2, 'last' => '2026-09-01 10:00:00' ];

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, [
	'format' => \Nino\Modules\Redirects\Rules::FORMAT, 'rules' => [], 'misses' => $full,
] );
redirectsRested( $appData );
unset( $appData['./redirects/rules'] );

redirectsRequest( $appData, '/broke-today' );
redirectsRested( $appData );
redirectsRequest( $appData, '/broke-today' );
redirectsRested( $appData );
redirectsRequest( $appData, '/broke-today' );

$afterFull = redirectsFile( $appData )['misses'] ?? [];

check( 'an address asked for now reaches a list that is already full',
	( $afterFull['/broke-today']['count'] ?? 0 ) === 3 );
check( '...by taking the place of the least asked for, so the ceiling still holds',
	count( $afterFull ) === \Nino\Modules\Redirects\Rules::MISS_LIMIT );
// The place is held open, not put at the front: the panel draws the list in
// the order it is in, and the newest address is not the most asked for one
$counts = array_column( $afterFull, 'count' );
$sorted = $counts;
rsort( $sorted );
check( '...and the list is still the most asked for first', $counts === $sorted );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, $fileBeforeEdits );
unset( $appData['./redirects/rules'] );

echo "\n";


// --- A file a site of Nino 1 wrote ----------------------------------------------

echo "The data of a site of Nino 1\n";

/*	The file the Redirects feature of Nino 1 wrote, byte for byte in its shape: the rules and the addresses are read as they stand, nothing is
	converted and nothing is written back by reading	*/
$legacy = "<?php return array (\n  'format' => 1,\n  'rules' => \n  array (\n    0 => \n    array (\n      'from' => '/legacy',\n      'to' => '/here',\n      'status' => 302,\n      'subtree' => true,\n      'hits' => 4,\n      'last' => '2026-01-02 03:04:05',\n    ),\n  ),\n  'misses' => \n  array (\n    '/lost' => \n    array (\n      'count' => 2,\n      'last' => '2026-01-02 03:04:05',\n    ),\n  ),\n);\n";
$fileBeforeLegacy = redirectsFile( $appData );
file_put_contents( $listFile, $legacy );
unset( $appData['./redirects/rules'] );
$read = \Nino\Modules\Redirects\Rules::read( $appData );
check( 'a file of the shape Nino 1 wrote is read as it stands, and reading writes nothing',
	$read['rules'] === [ [ 'from' => '/legacy', 'to' => '/here', 'status' => 302, 'subtree' => true, 'hits' => 4, 'last' => '2026-01-02 03:04:05' ] ]
	&& $read['misses'] === [ '/lost' => [ 'count' => 2, 'last' => '2026-01-02 03:04:05' ] ] && (string) file_get_contents( $listFile ) === $legacy
	&& ( redirectsRequest( $appData, '/legacy/page' )['header']['Location'] ?? '' ) === '/here/page' );
redirectsStore( $appData, $fileBeforeLegacy );

echo "\n";


// --- The home page as a target -------------------------------------------------

echo "The home page as a target\n";

$homeFile = redirectsFile( $appData );

$home = \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [
	[ 'from' => '/gone-home', 'to' => '/' ],
	[ 'from' => '/old', 'to' => '/', 'subtree' => true ],
] ] );

check( 'a rule to the home page survives a read of the file', array_column( $home['rules'], 'to', 'from' ) === [ '/gone-home' => '/', '/old' => '/' ] );
/*	A subtree rule to '/' used to build rtrim( '/', '/' ). $rest, which is ''
	for the base itself and would have sent an empty Location	*/
check( 'a subtree that moved to the home page answers its own address with / and carries the rest over',
	( \Nino\Modules\Redirects\Rules::match( $home['rules'], '/old' )['to'] ?? '' ) === '/'
	&& ( \Nino\Modules\Redirects\Rules::match( $home['rules'], '/old/x' )['to'] ?? '' ) === '/x' );

redirectsStore( $appData, $home );

check( 'the Location is the front page, and never empty', ( redirectsRequest( $appData, '/gone-home' )['header']['Location'] ?? '' ) === '/'
	&& ( redirectsRequest( $appData, '/old' )['header']['Location'] ?? '' ) === '/'
	&& ( redirectsRequest( $appData, '/old/x' )['header']['Location'] ?? '' ) === '/x' );

$appData['/nino/dir'] = '/sub';
check( '...with the project directory in front of it', ( redirectsRequest( $appData, '/gone-home' )['header']['Location'] ?? '' ) === '/sub/'
	&& ( redirectsRequest( $appData, '/old' )['header']['Location'] ?? '' ) === '/sub/' );
$appData['/nino/dir'] = '';

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, $homeFile );
unset( $appData['./redirects/rules'] );

echo "\n";


// --- What answers a target ---------------------------------------------------------

echo "What answers a target\n";

$routesBefore = $appData['/nino/http/routes'];
$fillsFile		= \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] );

foreach( [ 'GET://', 'GET://blog', 'GET://about', 'GET://news/*', 'POST://form', 'GET://_admin', 'GET://sitemap.xml', 'GET://old.html', 'GET:///double', 'GET://\\back' ] as $key )
	$appData['/nino/http/routes'][$key] = [ 'uri' => substr( $key, 5 ), 'body' => 'x' ];
// A route whose response uri is not its public address, which is what a
// translated page has - the name is looked up under the uri
$appData['/nino/http/routes']['GET://ueber-uns'] = [ 'uri' => '/about-us', 'locale' => 'de_DE', 'body' => 'x' ];

\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [
	'[[/_nino/webpage/blog/name]]'				=> '<em>Das</em> Blog &amp; mehr',
	'[[/_nino/webpage/about-us/name]]'		=> 'Über uns',
] );

$pages = \Nino\Modules\Redirects\Rules::routes( $appData );

check( 'the pages a target can be picked from are the GET routes shaped like a page, by path',
	array_keys( $pages ) === [ '/', '/about', '/blog', '/here', '/old.html', '/ueber-uns' ] );
check( '...never a wildcard, a POST route, the workbench or a file like sitemap.xml',
	isset( $pages['/news/*'], $pages['/form'], $pages['/_admin'], $pages['/sitemap.xml'] ) === false );
check( '...nor an address a browser would read as the address of another host: \\Nino\\Http::pages() leaves out what starts with two slashes, or a slash and a backslash',
	isset( $pages['//double'] ) === false && isset( $pages['/\\back'] ) === false );
check( '...labelled with the name the menu would give the page, tags stripped and entities decoded',
	$pages['/blog']['label'] === 'Das Blog & mehr' && $pages['/ueber-uns']['label'] === 'Über uns' );
check( '...and with its path where nobody named it', $pages['/about']['label'] === '/about' && $pages['/']['label'] === '/' );
check( '...each as a path, a label and the locale of its route', $pages['/ueber-uns'] === [ 'path' => '/ueber-uns', 'label' => 'Über uns', 'locale' => 'de_DE' ]
	&& $pages['/blog']['locale'] === '' );

// A language the page is named in only, and the one every language shares
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/_nino/webpage/blog/name]]' => 'The blog' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [ '[[/_nino/webpage/about/name]]' => 'About' ] );
check( 'a name in a language that is not the native one, or in global.php, is found as the menu would find it',
	\Nino\Modules\Redirects\Rules::routes( $appData )['/blog']['label'] === 'The blog'
	&& \Nino\Modules\Redirects\Rules::routes( $appData )['/about']['label'] === 'About' );
// ...and one a module added to the fills for this request, which only the kernel's reading of the fills knows
\Nino\Html::addFills( $appData, [ '[[/_nino/webpage/about/name]]' => 'About, by a module' ], '*' );
check( 'a name added to the fills of the request is found as well, and wins as it does on a page',
	\Nino\Modules\Redirects\Rules::routes( $appData )['/about']['label'] === 'About, by a module' );
\Nino\Html::removeFill( $appData, '/_nino/webpage/about/name', '*' );
// A language the site does not list has no fills: the kernel reads no file for a name it cannot vouch for
$nativeBefore = $appData['/nino/locales/native'];
$appData['/nino/locales/native'] = 'fr_FR';
\Nino\Filesystem::putFileContent( $appData, '/text/fr_FR.php', [ '[[/_nino/webpage/blog/name]]' => 'Le blog' ] );
check( 'a name in a language the site does not list is not read, native or not',
	\Nino\Modules\Redirects\Rules::routes( $appData )['/blog']['label'] === 'The blog' );
$appData['/nino/locales/native'] = $nativeBefore;
@unlink( \Nino\Filesystem::path( $appData, '/text/fr_FR.php' ) );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [] );
\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [] );

// The answers
$rulesNow = \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => [
	[ 'from' => '/chain-a', 'to' => '/chain-b' ],
	[ 'from' => '/chain-b', 'to' => '/chain-a' ],
	[ 'from' => '/via-rule', 'to' => '/gone' ],
	[ 'from' => '/gone', 'to' => '/here' ],
	[ 'from' => '/new-base', 'to' => '/news', 'subtree' => true ],
	[ 'from' => '/dead-chain', 'to' => '/dead-hop' ],
	[ 'from' => '/dead-hop', 'to' => '/nowhere-at-all' ],
	[ 'from' => '/file-chain', 'to' => '/file-hop' ],
	[ 'from' => '/file-hop', 'to' => '/public/a.pdf' ],
	[ 'from' => '/site-chain', 'to' => '/site-hop' ],
	[ 'from' => '/site-hop', 'to' => 'https://example.org/y' ],
	[ 'from' => '/public/a.pdf', 'to' => '/here' ],
] ] );
// Normalising keeps loops out of a stored file, so the cycle is two rules that
// are each fine on their own - which is how one gets in
check( 'two rules that send to one another are each fine alone', count( $rulesNow['rules'] ) === 12 );
redirectsStore( $appData, $rulesNow );

$publicDir = \Nino\Filesystem::getPublicPath( $appData );
is_dir( $publicDir ) === true || mkdir( $publicDir, 0755, true );
file_put_contents( $publicDir. '/a.pdf', 'x' );

check( 'a page of the site answers its address', \Nino\Modules\Redirects\Rules::answer( $appData, '/here' ) === 'route'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/' ) === 'route'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/news/anything' ) === 'route' );
check( 'an address on another site is external, and nothing is requested to find out',
	\Nino\Modules\Redirects\Rules::answer( $appData, 'https://example.org/x' ) === 'external' );
check( 'an address another rule answers is reported as that', \Nino\Modules\Redirects\Rules::answer( $appData, '/via-rule' ) === 'rule'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/gone' ) === 'rule' );
check( 'a chain that ends on a page, a file or another site is a rule too',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/file-chain' ) === 'rule'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/site-chain' ) === 'rule' );
check( '...and one that ends where nothing answers is nothing, because that is where a visitor lands',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/dead-chain' ) === 'nothing'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/dead-hop' ) === 'nothing' );
check( 'a chain of rules that comes back to where it was is a loop', \Nino\Modules\Redirects\Rules::answer( $appData, '/chain-a' ) === 'loop'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/chain-b' ) === 'loop' );
// A chain longer than Rules::HOPS that never comes back to an address is not a loop: it is long
$withLongChain = static function( array &$appData ): array {
	$before = \Nino\Modules\Redirects\Rules::read( $appData );
	$long = [];
	for( $hop = 0; $hop < \Nino\Modules\Redirects\Rules::HOPS + 3; $hop++ )
		$long[] = [ 'from' => '/long-'. $hop, 'to' => '/long-'. ( $hop + 1 ) ];
	$long[] = [ 'from' => '/long-'. ( \Nino\Modules\Redirects\Rules::HOPS + 3 ), 'to' => '/here' ];
	$notes = [];
	redirectsStore( $appData, \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => array_merge( $before['rules'], $long ), 'misses' => $before['misses'] ], $notes ) );
	return $before;
};
$rulesBeforeLong = $withLongChain( $appData );
check( 'a chain of more than HOPS rules that never comes back is a chain, not a loop - whatever it ends on',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/long-0' ) === 'chain' && \Nino\Modules\Redirects\Rules::answer( $appData, '/long-1' ) === 'chain' );
check( '...while the end of the same chain, a few rules from the page, is the rule it always was',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/long-'. ( \Nino\Modules\Redirects\Rules::HOPS + 3 ) ) === 'rule'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/long-'. ( \Nino\Modules\Redirects\Rules::HOPS + 1 ) ) === 'rule' );
redirectsStore( $appData, $rulesBeforeLong );

check( 'a file in the public directory answers its address', \Nino\Modules\Redirects\Rules::answer( $appData, '/public/a.pdf' ) === 'file'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/public/b.pdf' ) === 'nothing' );
check( '...before any rule: the web server serves it, so a rule for that address is never reached',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/public/a.pdf' ) === 'file' );
check( '...and only there: nothing else of the project is a target', \Nino\Modules\Redirects\Rules::answer( $appData, '/config.php' ) === 'nothing'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/public/../config.php' ) === 'nothing' );
check( 'an address nothing answers', \Nino\Modules\Redirects\Rules::answer( $appData, '/nowhere' ) === 'nothing' );

// A subtree rule sends many pages: what answers them is a page or a wildcard
// below the target, and without one nothing does
check( 'a subtree rule is answered by a route below its target, a wildcard among them',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/news', true ) === 'route' );
check( '...and by nothing where nothing lies below it', \Nino\Modules\Redirects\Rules::answer( $appData, '/fresh', true ) === 'nothing'
	&& \Nino\Modules\Redirects\Rules::answer( $appData, '/fresh', false ) === 'nothing' );
check( '...while a wildcard route alone does not answer the base of a single-page rule',
	\Nino\Modules\Redirects\Rules::answer( $appData, '/news', false ) === 'nothing' );

unlink( $publicDir. '/a.pdf' );

$appData['/nino/http/routes'] = $routesBefore;
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', is_array( $fillsFile ) === true ? $fillsFile : [] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, $homeFile );
unset( $appData['./redirects/rules'] );

echo "\n";
// --- The panel --------------------------------------------------------------

echo "The panel\n";

ninoUser( $appData, 'dev@example.com' );

[ $status, $listed ] = ninoCall( $appData, [ $panel, 'apiList' ] );
check( 'the list answers the rules, the addresses and what the panel needs to say about itself', $status === 200
	&& is_array( $listed['rules'] ) && is_array( $listed['misses'] )
	&& $listed['statuses'] === \Nino\Modules\Redirects\Rules::STATUSES
	&& $listed['recording'] === true && $listed['limit'] === \Nino\Modules\Redirects\Rules::MISS_LIMIT );
check( '...with each address as a row rather than a key, so the table can sort on it',
	array_keys( $listed['misses'][0] ?? [] ) === [ 'path', 'count', 'last' ] );

[ $status, $saved ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/press/', 'to' => '/news', 'status' => 302, 'subtree' => false, 'was' => '' ] );
check( 'a rule can be added, and its address is normalised on the way in', $status === 200 && $saved['saved'] === '/press'
	&& count( array_filter( $saved['rules'], static fn( array $r ): bool => $r['from'] === '/press' ) ) === 1 );
check( '...and it answers straight away', ( redirectsRequest( $appData, '/press' )['header']['Location'] ?? '' ) === '/news' );

[ $status, $renamed ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/presse', 'to' => '/news', 'status' => 301, 'subtree' => false, 'was' => '/press' ] );
check( 'a rule can be renamed in one save rather than a delete and an add', $status === 200
	&& count( array_filter( $renamed['rules'], static fn( array $r ): bool => $r['from'] === '/press' ) ) === 0
	&& count( array_filter( $renamed['rules'], static fn( array $r ): bool => $r['from'] === '/presse' ) ) === 1 );

/*	Renaming a rule onto an address another rule already answers used to take
	that other rule with it, hits and all, and answer 200 - apiSave() saw both
	of them as "the one being edited" and kept the first it came to. A rule
	that silently went away is the one kind of mistake nobody goes looking
	for, so it is a refusal like the others	*/
ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/taken', 'to' => '/two', 'status' => 301, 'subtree' => false, 'was' => '' ] );
[ $status, $collided ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/taken', 'to' => '/one', 'status' => 301, 'subtree' => false, 'was' => '/presse' ] );
check( 'renaming a rule onto an address another rule answers is refused rather than done quietly: 400, with the code and the address that is taken', $status === 400
	&& ( $collided['code'] ?? '' ) === 'redirects_taken' && ( $collided['params'] ?? [] ) === [ '/taken' ] );
check( '...and both rules are still there, the other one with where it sent',
	count( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/presse' ) ) === 1
	&& ( array_values( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/taken' ) )[0]['to'] ?? '' ) === '/two' );
ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/taken' ] );

/*	A refusal a person can cause carries a code, and the sentence of the code is
	the script's to say in the language of the workbench: the answer holds the
	English sentence for a log, the code and what belongs in it - no fill key,
	no translated text	*/
$refusals = [
	'redirects_from'					=> [ 'from' => 'nope', 'to' => '/news' ],
	'redirects_to'						=> [ 'from' => '/x', 'to' => 'http://example.com' ],
	'redirects_status'				=> [ 'from' => '/x', 'to' => '/news', 'status' => 418 ],
	'redirects_loop_self'			=> [ 'from' => '/x', 'to' => '/x' ],
	'redirects_loop_subtree'	=> [ 'from' => '/x', 'to' => '/x/y', 'subtree' => true ],
];
foreach( $refusals as $code => $post ) {
	[ $status, $refused ] = ninoCall( $appData, [ $panel, 'apiSave' ], $post );
	check( "a rule that is not one is refused with 400 and the code $code, and no param", $status === 400 && ( $refused['code'] ?? '' ) === $code
		&& array_keys( $refused ) === [ 'error', 'code' ] && str_contains( (string) $refused['error'], '/_admin/' ) === false );
}
check( 'the answer holds the English message for a log and the code, and nothing the script has to translate',
	ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => 'nope', 'to' => '/news' ] )[1] === [ 'error' => 'the old address is not a path of this site', 'code' => 'redirects_from' ] );

/*	A browser reads a backslash after the slash as a second slash, and the
	kernel's redirect refuses such an address as it refuses '//' - a rule with
	one could be saved and never be followed, so it is refused at the save	*/
check( 'a target the kernel\'s redirect would refuse is refused at the save, not saved to be refused on every request',
	ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/x', 'to' => '/\\evil.example' ] )[1]['code'] === 'redirects_to'
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/x', 'to' => '/a:b' ] )[1]['code'] === 'redirects_to'
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/x', 'to' => 'https://user:secret@example.com/' ] )[1]['code'] === 'redirects_to'
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/\\x', 'to' => '/news' ] )[1]['code'] === 'redirects_from' );

foreach( [ 'redirects_from' => false, 'redirects_to' => false, 'redirects_status' => false, 'redirects_loop_self' => false, 'redirects_loop_subtree' => false, 'redirects_taken' => true, 'redirects_unknown' => true ] as $code => $takesAddress )
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$sentence = redirectsSentence( $appData, $code, $locale );
		check( "...and the code $code has its sentence in $locale". ( $takesAddress === true ? ', with a place for the address' : '' ),
			$sentence !== '' && str_contains( $sentence, '%s' ) === $takesAddress );
	}

// An address that has a rule is answered now, so it is not an address nothing
// answers - leaving it on the list would have somebody write the same rule twice
redirectsRested( $appData );
redirectsRequest( $appData, '/orphan' );
check( 'the address is on the list first', isset( redirectsFile( $appData )['misses']['/orphan'] ) === true );
ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/orphan', 'to' => '/here' ] );
check( '...and saving a rule for it takes it off', isset( redirectsFile( $appData )['misses']['/orphan'] ) === false );

[ $status, $probed ] = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => '/here' ] );
check( 'the probe says when a page answers an address, so no rule is consulted', $status === 200 && $probed['answer'] === 'route' );
[ $status, $probed ] = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => '/old-shop/socks' ] );
check( '...which rule answers it, and where it sends', $status === 200 && $probed['answer'] === 'rule'
	&& $probed['from'] === '/old-shop' && $probed['to'] === '/here/socks' && $probed['status'] === 301 );
[ $status, $probed ] = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => '/nothing-here' ] );
check( '...and when nothing does', $status === 200 && $probed['answer'] === 'nothing' );
check( 'a probe for something that is not an address is a 400', ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => 'nope' ] )[0] === 400 );

/*	What a target leads to. The rule is written whatever the answer is: a rule
	for a page that is not made yet is how a move is prepared, so the panel says
	so and goes on	*/
[ $status, $warned ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/dead-end', 'to' => '/nowhere', 'status' => 301, 'subtree' => false, 'was' => '' ] );
check( 'a rule whose target nothing answers is saved, and the save says so', $status === 200 && $warned['saved'] === '/dead-end'
	&& count( $warned['warnings'] ) === 1 && str_contains( $warned['warnings'][0], '/nowhere' ) === true
	&& ( array_values( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/dead-end' ) )[0]['to'] ?? '' ) === '/nowhere' );
$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/', 'body' => 'the front page' ];
check( '...a rule to a page, to the front page or to another site is saved without a word',
	( ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/to-page', 'to' => '/here' ] )[1]['warnings'] ?? null ) === []
	&& ( ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/to-site', 'to' => 'https://example.org' ] )[1]['warnings'] ?? null ) === []
	&& ( ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/to-home', 'to' => '/' ] )[1]['warnings'] ?? null ) === [] );
unset( $appData['/nino/http/routes']['GET://'] );
check( '...so is the target the older save above went to: nothing there answered /news either',
	count( ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/press', 'to' => '/news', 'status' => 302 ] )[1]['warnings'] ?? [] ) === 1 );
ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/press' ] );

[ $status, $viaRule ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/twice', 'to' => '/to-page' ] );
check( 'a target another rule answers is a softer warning, and says the visitor is redirected twice',
	$status === 200 && count( $viaRule['warnings'] ) === 1 && str_contains( $viaRule['warnings'][0], '/to-page' ) === true
	&& $viaRule['warnings'][0] !== $warned['warnings'][0] );

[ $status, $viaDead ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/twice-dead', 'to' => '/dead-end' ] );
check( '...but where that rule leads nowhere, the visitor lands on the 404 page and the warning says that instead',
	$status === 200 && count( $viaDead['warnings'] ) === 1 && $viaDead['warnings'][0] !== $viaRule['warnings'][0] );
ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/twice-dead' ] );

ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/cycle-a', 'to' => '/cycle-b' ] );
[ $status, $cycle ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/cycle-b', 'to' => '/cycle-a' ] );
check( 'a rule that closes a ring with the others is refused at its field like one that sends an address to itself, and is not stored',
	$status === 400 && ( $cycle['code'] ?? '' ) === 'redirects_loop_chain'
	&& count( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/cycle-b' ) ) === 0
	&& ( redirectsFile( $appData )['rules'] === array_values( redirectsFile( $appData )['rules'] ) ) );
// A ring a hand wrote into the file is still told by the list
$ringFile = redirectsFile( $appData );
redirectsStore( $appData, \Nino\Modules\Redirects\Rules::normalize( [ 'rules' => array_merge( $ringFile['rules'], [ [ 'from' => '/cycle-b', 'to' => '/cycle-a' ] ] ), 'misses' => $ringFile['misses'] ] ) );


[ $status, $listed ] = ninoCall( $appData, [ $panel, 'apiList' ] );
$answers = array_column( $listed['rules'], 'answer', 'from' );
check( 'the list carries what answers every rule\'s target, and the pages a target can be picked from',
	$status === 200 && count( $answers ) === count( $listed['rules'] ) && array_diff( $answers, [ 'route', 'external', 'rule', 'loop', 'chain', 'file', 'nothing' ] ) === []
	&& ( $answers['/dead-end'] ?? '' ) === 'nothing' && ( $answers['/to-page'] ?? '' ) === 'route'
	&& ( $answers['/to-site'] ?? '' ) === 'external' && ( $answers['/twice'] ?? '' ) === 'rule'
	&& ( $answers['/cycle-a'] ?? '' ) === 'loop'
	&& in_array( 'here', array_map( static fn( array $page ): string => ltrim( $page['path'], '/' ), $listed['routes'] ), true ) === true );
check( '...and the stored file stays what it was: the answer is read off, never written',
	array_keys( redirectsFile( $appData )['rules'][0] ) === [ 'from', 'to', 'status', 'subtree', 'hits', 'last' ] );

$appData['/nino/http/routes']['GET://later'] = [ 'uri' => '/later', 'body' => 'x' ];
ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/for-later', 'to' => '/later' ] );
check( 'a target whose page exists answers as a route', ( array_column( ninoCall( $appData, [ $panel, 'apiList' ] )[1]['rules'], 'answer', 'from' )['/for-later'] ?? '' ) === 'route' );
unset( $appData['/nino/http/routes']['GET://later'] );
check( '...and flips to nothing when the page is removed, without the rule being touched',
	( array_column( ninoCall( $appData, [ $panel, 'apiList' ] )[1]['rules'], 'answer', 'from' )['/for-later'] ?? '' ) === 'nothing' );

check( 'saving and deleting answer with the rules as the list does, answers included',
	array_diff( array_column( ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/to-page', 'to' => '/here' ] )[1]['rules'], 'answer' ), [ 'route', 'external', 'rule', 'loop', 'chain', 'file', 'nothing' ] ) === []
	&& isset( ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/for-later' ] )[1]['rules'][0]['answer'] ) === true );

$fillsEn = include __DIR__. '/../text/en_US.php';
$fillsDe = include __DIR__. '/../text/de_DE.php';
check( 'the words about a target exist in both languages, and the warnings name the target - four of them now, the chain having words of its own',
	array_keys( $fillsEn ) === array_keys( $fillsDe )
	&& count( array_filter( array_keys( $fillsEn ), static fn( string $key ): bool => str_contains( $key, '/redirects/warning/' ) === true ) ) === 4
	&& count( array_filter( array_merge( $fillsEn, $fillsDe ), static fn( string $text, string $key ): bool => str_contains( $key, '/redirects/warning/' ) === true && preg_match( '/["“„]%s["”“]/u', $text ) !== 1, ARRAY_FILTER_USE_BOTH ) ) === 0 );

foreach( [ '/dead-end', '/to-page', '/to-site', '/to-home', '/twice', '/cycle-a', '/cycle-b' ] as $made )
	ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => $made ] );

[ $status, $deleted ] = ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/presse' ] );
check( 'a rule can be deleted, and the answer is the rules the panel redraws from - nothing beside them', $status === 200
	&& array_keys( $deleted ) === [ 'rules' ]
	&& count( array_filter( $deleted['rules'], static fn( array $r ): bool => $r['from'] === '/presse' ) ) === 0 );
[ $status, $unknown ] = ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/presse' ] );
check( '...and deleting one that is not there is a 404 with the code and the address, not a silent success', $status === 404
	&& ( $unknown['code'] ?? '' ) === 'redirects_unknown' && ( $unknown['params'] ?? [] ) === [ '/presse' ] );

redirectsRested( $appData );
redirectsRequest( $appData, '/one-more' );
check( 'the address is on the list before it is forgotten', isset( redirectsFile( $appData )['misses']['/one-more'] ) === true );
[ $status, $forgot ] = ninoCall( $appData, [ $panel, 'apiForget' ], [ 'path' => '/one-more' ] );
check( 'one address can be forgotten, with a plain ok - the panel drops the row itself', $status === 200
	&& $forgot === [ 'ok' => true ]
	&& isset( redirectsFile( $appData )['misses']['/one-more'] ) === false );
redirectsRested( $appData );
redirectsRequest( $appData, '/and-another-one' );
check( '...and all of them at once, when there are some', isset( redirectsFile( $appData )['misses']['/and-another-one'] ) === true && ninoCall( $appData, [ $panel, 'apiForget' ] )[0] === 200
	&& ( redirectsFile( $appData )['misses'] ?? [] ) === [] );
check( '...while the rules are untouched by that', ( redirectsFile( $appData )['rules'] ?? [] ) !== [] );

/*	The site writes into the same file - every address nothing answered, every
	rule it follows - and an action that read the file, changed it and wrote it
	whole took back what was written in between. Another request is a copy of
	the app data that writes: this one's own copy of the file, and the one
	Filesystem cached for it, are the ones from before	*/
foreach( [ 'apiSave' => [ 'from' => '/meanwhile', 'to' => '/here' ], 'apiDelete' => [ 'from' => '/meanwhile' ], 'apiForget' => [ 'path' => '/elsewhere' ] ] as $method => $post ) {
	redirectsRested( $appData );
	\Nino\Modules\Redirects\Rules::read( $appData );
	$other = $appData;
	\Nino\Modules\Redirects\Rules::noteMiss( $other, '/written-during-'. $method );
	ninoCall( $appData, [ $panel, $method ], $post );
	check( "$method keeps the address another request wrote into the file after this one had read it", isset( redirectsFile( $appData )['misses']['/written-during-'. $method] ) === true );
}
check( '...and the rule it made is gone again after the delete', count( array_filter( redirectsFile( $appData )['rules'], static fn( array $r ): bool => $r['from'] === '/meanwhile' ) ) === 0 );
ninoCall( $appData, [ $panel, 'apiForget' ] );

/*	A value that is no string is no address, whatever the action reads it for -
	never a cast of the array, which raises and ends the request	*/
ninoWarnings();
check( 'an address posted as an array is refused like any other, and an array for the rule\'s old place is none at all',
	ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => [ '/a' ], 'to' => '/here' ] )[1]['code'] === 'redirects_from'
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/a', 'to' => [ '/here' ] ] )[1]['code'] === 'redirects_to'
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/array-was', 'to' => '/here', 'was' => [ '/a' ] ] )[0] === 200
	&& ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => [ '/array-was' ] ] )[1]['code'] === 'redirects_from'
	&& ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => [ '/a' ] ] )[1]['code'] === 'redirects_from'
	&& ninoCall( $appData, [ $panel, 'apiForget' ], [ 'path' => [ '/a' ] ] )[0] === 200
	&& ninoWarnings() === [] );
ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/array-was' ] );

/*	A file nothing can be written over, which is what a lock that cannot be
	taken is: the lock file's name is a directory. A write or a lock the server
	could not help is a 500 with the English sentence for a log and no code - no
	person caused it, and there is nothing for them to read	*/
$lockPath		= \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( \Nino\Modules\Redirects\Rules::PATH ). '.lock';
$fileLocked	= redirectsFile( $appData );
$kept				= $fileLocked['rules'][0]['from'] ?? '/none';
@unlink( $lockPath );
mkdir( $lockPath, 0755, true );
$cannot = [
	ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/cannot', 'to' => '/here' ] ),
	ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => $kept ] ),
	ninoCall( $appData, [ $panel, 'apiForget' ], [ 'path' => '/anything' ] ),
];
rmdir( $lockPath );
check( 'a file that cannot be written is a 500 in all three actions: the English sentence of the action, no code, no params',
	array_column( $cannot, 0 ) === [ 500, 500, 500 ]
	&& array_column( $cannot, 1 ) === [ [ 'error' => 'the rules could not be written' ], [ 'error' => 'the rules could not be written' ], [ 'error' => 'the list could not be written' ] ] );
check( '...and the file is as it was', redirectsFile( $appData ) === $fileLocked );

/*	And the panel resolves them, in the language the operator picked - which is
	the whole point of the keys. A rule the stored file cannot use is written
	straight into it here, because that is the only way a note reaches apiList
	at all: the panel refuses such a rule on the way in	*/
$noteFileBefore = redirectsFile( $appData );
$noteFillsDe = include __DIR__. '/../text/de_DE.php';
$noteFillsEn = include __DIR__. '/../text/en_US.php';
$noteRead = static function( array &$appData ) use ( $panel ): array {
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Redirects\Rules::PATH, [
		'rules'	=> [ [ 'from' => '/loop-note', 'to' => '/loop-note', 'status' => 301, 'subtree' => false, 'hits' => 0, 'last' => '' ] ],
		'misses'=> [],
	] );
	unset( $appData['./redirects/rules'] );
	return ninoCall( $appData, [ $panel, 'apiList' ] )[1]['notes'] ?? [];
};
$noteSaid = static fn( array $fills ): string => strtr(
	(string) $fills['[[/_admin/redirects/note/dropped]]'],
	[ '%s' => '/loop-note', '%r' => (string) $fills['[[/_admin/error/redirects_loop_self]]'] ]
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

check( 'nobody logged in gets nothing at all', ninoCall( $appData, [ $panel, 'apiList' ] )[0] === 401
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/a', 'to' => '/b' ] )[0] === 401
	&& ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/a' ] )[0] === 401
	&& ninoCall( $appData, [ $panel, 'apiForget' ] )[0] === 401
	&& ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => '/a' ] )[0] === 401 );

ninoUser( $appData, 'editor@example.com', [ '/_admin/elements/manage' ] );

check( 'an account with another permission gets a 403 from every one of them', ninoCall( $appData, [ $panel, 'apiList' ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'from' => '/a', 'to' => '/b' ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'from' => '/a' ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiForget' ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'path' => '/a' ] )[0] === 403 );

\Nino\Auth::logoutUser( $appData );

echo "\n";


// --- Deactivation -----------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'redirects' ) === true );
check( 'the class is out of the module list', in_array( '\\Nino\\Modules\\Redirects', $appData['/nino/modules'], true ) === false );
// Everything a feature keeps is the project's - putting it back finds its
// rules where it left them
check( '...and the rules it wrote are still on disk', ( redirectsFile( $appData )['rules'] ?? [] ) !== [] );

echo "\n";


// --- The script of the panel ------------------------------------------------------------

echo "The script of the panel\n";

$script = (string) file_get_contents( $dir. '/assets/admin.js' );
$words = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];

check( 'the rail entry has a sign of its own: an svg that draws, no script in it', preg_match( '/^<svg [^>]*>(<(path|circle|rect|line|polyline)[^>]*\/>)+<\/svg>$/', \Nino\Modules\Redirects\Admin::icon() ) === 1 );
check( 'the script takes its buttons, dialogs, table, tabs and bars from the workbench and builds none of them itself',
	preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(|\bprompt\(|nino-admin-btn-|type\s*[:=]\s*.file|<dialog/', $script ) === 0
	&& str_contains( $script, 'Nino.adminUi.table(' ) === true && str_contains( $script, 'Nino.adminUi.formDialog(' ) === true && str_contains( $script, 'Nino.adminUi.tabs(' ) === true
	&& str_contains( $script, 'Nino.adminUi.actionBar(' ) === true && str_contains( $script, 'Nino.adminUi.contextBar(' ) === true );
check( 'a dialog asks by the level of the action, with a title, and a refusal is said at its field',
	substr_count( $script, "Nino.adminUi.confirm( 'destructive'" ) === 2 && str_contains( $script, 'Nino.adminUi.fieldError(' ) === true && str_contains( $script, 'noValidate = true' ) === true );
check( 'a list has the one main button of its bar and the answer of an immediate action is a toast, never a line in the content',
	substr_count( $script, "kind : 'primary'" ) === 2 && str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'Nino.adminUi.sectionHelp(' ) === true && str_contains( $script, "'lead'" ) === false && preg_match( '/hint\([^)]*(saved|deleted)/', $script ) === 0 );
check( 'the table of the rules has columns few enough for its buttons to stay in sight, an example address is a word of the panel and no sentence of the script stands in it',
	( static function() use ( $script ): bool {
		$start = (int) strpos( $script, 'const rulesPane' );
		$block = substr( $script, $start, (int) strpos( $script, 'actions	: function', $start ) - $start );
		return substr_count( $block, '{ key :' ) <= 5 && str_contains( $block, 'wrap : true' ) === true;
	} )() && preg_match( "~['\"]/(old|new)/page['\"]~", $script ) === 0 );
check( 'the stylesheet of Nino 1 is gone: the panel brings no file of css', is_file( $dir. '/assets/admin.css' ) === false && str_contains( implode( ',', \Nino\Modules\Redirects\Admin::assets() ), '.css' ) === false );

check( 'both languages of the panel have the same keys, and every word the script reads is one of them or the workbench\'s own',
	array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) && ( static function() use ( $script, $words ): bool {
		preg_match_all( "~'(/_admin/(?!common/|features/)[a-z/_-]+)'~", $script, $read );
		$own = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( $words['en_US'] ) );
		$prefixes = array_values( array_filter( $read[1], static fn( string $key ): bool => str_ends_with( $key, '/' ) ) );
		$exact = array_diff( array_unique( array_diff( $read[1], $prefixes ) ), $own );
		// The keys that end in a slash are completed by the script: the two statuses, the answers of a target and of the probe, the messages of the probe
		$completed = [ '/_admin/redirects/status/' => [ '301', '302' ], '/_admin/redirects/answer/' => [ 'nothing', 'rule', 'loop', 'chain' ], '/_admin/redirects/probe/' => [ 'route', 'rule', 'nothing' ], '/_admin/redirects/msg/' => [ 'probe-route', 'probe-rule', 'probe-nothing' ] ];
		$lacking = [];
		foreach( $prefixes as $prefix )
			foreach( $completed[$prefix] ?? [ '?' ] as $end )
				if( in_array( $prefix. $end, $own, true ) === false )
					$lacking[] = $prefix. $end;
		return $exact === [] && $lacking === [];
	} )() );

redirectsStore( $appData, [ 'rules' => [], 'misses' => [ '/tile' => [ 'count' => 1, 'last' => '2026-09-01 10:00:00' ] ] ] );
check( 'the Dashboard tile says how many addresses nothing answered, with a word in both languages',
	( ( \Nino\Modules\Redirects\Admin::summary( $appData ) ?? [] )['label'] ?? '' ) === '/_admin/redirects/label/tile' && isset( $words['de_DE']['[[/_admin/redirects/label/tile]]'], $words['en_US']['[[/_admin/redirects/label/tile]]'] ) === true );

echo "\n";

// Nothing of the old components API or the old stylesheet vocabulary is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Modules\\\\Components|(?<![-\w])nino-(?!admin-)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
