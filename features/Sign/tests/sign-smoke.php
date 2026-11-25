<?php
declare(strict_types=1);

/**
 *	Nino
 *	sign-smoke.php	Contract test for the Sign feature (Modules\Sign): the manifest and the activation
 *									through \Nino\Features, the request and the data it leaves - the PDF as it is shown,
 *									its hash, the token that is mailed and kept as a hash -, the page the link opens and
 *									what a GET changes, the PDFs the kernel streams to the holder of the token, the member
 *									and the owner, the post that signs and the post that declines with every refusal, the
 *									record on the last page of the signed document, the reminder, the cancel and the
 *									delete, the two components, the panel and its permission with the codes of its
 *									refusals, the mails, the restore, deactivation, the look for the Design feature, and -
 *									where node is on the path - the scripts' own test. Travels with the feature and runs
 *									against the checkout three levels up, or the one NINO_ROOT names (see tests/harness.php
 *									there). The PDFs are Pdf's: its option append is pinned in features/Pdf/tests/.
 *
 *									The feature asks for a kernel that carries the file response, which its manifest names
 *									as the floor. On a kernel below that floor the activation is refused, as it has to be:
 *									the test says so, and does by hand what an activation does (the unit, the classes in
 *									/nino/modules, the version), so that everything else is still looked at.
 *
 *	Usage: php features/Sign/tests/sign-smoke.php
 *	       NINO_ROOT=../nino php features/Sign/tests/sign-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits in a project's features/, else the
// one NINO_ROOT names (a checkout beside the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/*	Worker mode: this same file re-entered as a process of its own, pointed at the sandbox of the suite. Two requests that are alike and run at once are only seen from
	processes - flock() is per open file description, so one process proves nothing about the lock - and so is a write that fails for the user a process runs as	*/
if( in_array( $argv[1] ?? '', [ 'sign-worker', 'removed-worker' ], true ) === true ) {

	$worker = [ './nino/uid' => $argv[2] ];
	\Nino\AppData::prepare( $worker );
	$worker['./nino/filesystem/path']					= $argv[2];
	$worker['./nino/filesystem/configpath']		= $argv[2]. '/private';
	$worker['./nino/filesystem/contentpath']	= $argv[2]. '/private';
	$worker['./nino/filesystem/publicpath']		= $argv[2]. '/public';
	\Nino\AppData::init( $worker );
	$worker['/nino/dir'] = '';
	$_SERVER['REMOTE_ADDR'] = $argv[3];
	\Nino\Html::addFills( $worker, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club', '[[/project/company/contact/email]]' => 'owner@example.org' ], '*' );
	\Nino\Html::init( $worker );
	\Nino\Features::registerComponents( $worker );
	\Nino\Modules::callModules( $worker, 'init' );

	// A process that may read the files and take the locks, and may not write a file of the data: what is read is written back only where the write is made
	if( $argv[1] === 'removed-worker' ) {

		set_error_handler( static function( int $level, string $message ) use ( $argv ): bool {
			file_put_contents( $argv[5], $message. "\n", FILE_APPEND );
			return true;
		} );

		// Root gives up its rights; a process that has none to give up is read-only by the files that the test made so
		if( $argv[4] === 'other' && function_exists( 'posix_setuid' ) === true && posix_getuid() === 0 ) {
			posix_setgid( 65534 );
			posix_setuid( 65534 );
		}

		$told = [ 'id' => $argv[6], 'email' => 'gone@example.org', 'group' => '' ];
		\Nino\Callbacks::doCallbacks( $worker, '/members/removed', $told );
		echo 'done';
		exit( 0 );
	}

	// Waits for the signal, so that the posts come at once, and posts the form of one request: the token the visitor holds
	$told = [];

	foreach( [ '/sign/signed', '/sign/declined' ] as $event )
		\Nino\Callbacks::registerCallback( $worker, $event, static function( array &$worker, array &$entry ) use ( &$told, $event ): void { $told[] = $event; } );

	\Nino\Callbacks::registerCallback( $worker, \Nino\Mail::TRANSPORT, static function( array &$worker, array &$mail ) use ( $argv ): void {
		file_put_contents( $argv[4]. '.mails', json_encode( [ 'to' => $mail['to'] ?? '' ] ). "\n", FILE_APPEND | LOCK_EX );
		$mail['sent'] = true;
	} );

	while( is_file( $argv[4] ) === false )
		usleep( 500 );

	$_POST = [ 't' => $argv[5], 'action' => $argv[6], 'consent' => '1', 'name' => 'Ada' ];
	$request = ninoVisit( $worker, '/.sign', 'POST' );
	\Nino\Modules\Sign::callbackPost( $worker, $request );

	// A server that cannot let the client go before the work: a length somebody else set. The kernel hands nothing back, and the mails wait in the outbox
	if( ( $argv[7] ?? '' ) === 'drop' )
		$request['/nino/http/response']['header']['Content-Length'] = '0';

	foreach( \Nino\Http::prepareAfter( $worker, $request ) as $work )
		$work();

	echo json_encode( [ 'status' => $request['/nino/http/response']['statusCode'], 'location' => (string) ( $request['/nino/http/response']['header']['Location'] ?? '' ), 'told' => $told ] );
	exit( 0 );
}

$appData = ninoSandbox( 'sign' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$sandbox = ninoSandboxDir( $appData );

// The project directory and its fill, which \Nino\request() registers from the same key on every request - empty here,
// the way a site at the domain root has it - and the facts of the website the mails are built on: its address, which
// every link of a mail starts with (\Nino\Html::absoluteUrl()), and the contact address
$appData['/nino/dir'] = '';

// The look of the site's mails: the base unit's words, which a project has from its wizard
if( is_file( $root. '/_admin/install/library/base/text/global.php' ) === true )
	\Nino\Html::addFills( $appData, (array) include $root. '/_admin/install/library/base/text/global.php', '*' );

\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club', '[[/project/company/contact/email]]' => 'owner@example.org', '[[/project/company/general/name]]' => 'The Club' ], '*' );

// What the kernel boots with, as far as the feature touches it: the [csrf] guard and the [template] shortcode. The components of the register come with the features
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Csrf', '\\Nino\\Modules\\Template' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
\Nino\Csrf::init( $appData );

// The workbench's entry point, which ninoCall() hands a panel action
$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];

/*	A transport takes the mail so nothing is actually sent - the same \Nino\Mail::TRANSPORT callback the Mailer feature
	registers. $delivery is what the stub says about the mail, so that a refusal is as easy to make as a delivery	*/
$mails = [];
$delivery = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$delivery ): void {
	$mails[] = $mail;
	$mail['sent'] = $delivery;
} );

// What the features that build on this one are told: the request that was signed or declined
$told = [];
foreach( [ '/sign/signed', '/sign/declined' ] as $event )
	\Nino\Callbacks::registerCallback( $appData, $event, static function( array &$appData, array &$entry ) use ( &$told, $event ): void {
		$told[] = [ $event, $entry ];
	} );

/**
 *	The index as it is stored, read fresh
 *
 *	@param		array 		&$appData
 *
 *	@return 	array
 */
function signIndex( array &$appData ): array {

	$state = \Nino\Filesystem::getFileContent( $appData, '/data/sign/requests.php', [] );

	return is_array( $state ) === true ? $state : [];
}

/**
 *	Switch the feature on, the way a project does: Basis, the PDF feature and the Members come with it
 *
 *	@param		array 		&$appData
 *
 *	@return 	true|string							True, or why not
 */
function signActivate( array &$appData ): true|string {

	return \Nino\Features::activate( $appData, 'sign' );
}

/**
 *	The elements of a rendered piece of html by xpath, as a document reads them
 *
 *	@param		string		$html
 *	@param		string		$query
 *
 *	@return 	array										The DOMElements
 */
function signFind( string $html, string $query ): array {

	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8"><body>'. $html. '</body>' );
	libxml_clear_errors();

	return iterator_to_array( ( new DOMXPath( $document ) )->query( $query ) ?: [] );
}

/**
 *	An attribute of the first of the elements signFind() found - '' where there is none, so that a check on a page that lacks
 *	the element fails and does not stop the run
 *
 *	@param		array 		$nodes
 *	@param		string		$name
 *
 *	@return 	string
 */
function signAttr( array $nodes, string $name ): string {

	return isset( $nodes[0] ) === true ? $nodes[0]->getAttribute( $name ) : '';
}

/**
 *	Whether the first of the elements found has an attribute - false where there is none
 *
 *	@param		array 		$nodes
 *	@param		string		$name
 *
 *	@return 	bool
 */
function signHas( array $nodes, string $name ): bool {

	return isset( $nodes[0] ) === true && $nodes[0]->hasAttribute( $name ) === true;
}

/**
 *	The text of the first of the elements found - '' where there is none
 *
 *	@param		array 		$nodes
 *
 *	@return 	string
 */
function signContent( array $nodes ): string {

	return isset( $nodes[0] ) === true ? (string) $nodes[0]->textContent : '';
}

/**
 *	A request of the site the way the web server hands it over, answered by the routes and the callbacks that are registered:
 *	the request built, the global callbacks of the response run - the csrf guard among them - and then the route's own.
 *	A post carries the csrf token of the session unless $csrf is false. Nothing is rendered
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$method
 *	@param		array 		$post
 *	@param		bool			$csrf					Whether a post carries the token
 *	@param		array 		$server				More of $_SERVER: the address of the client, the browser as HTTP_USER_AGENT
 *
 *	@return 	array										The request, response and all
 */
function signServe( array &$appData, string $uri, string $method = 'GET', array $post = [], bool $csrf = true, array $server = [] ): array {

	// What a request keeps for itself alone is gone before the next one begins
	unset( $appData['./sign/request'], $appData['./sign/forms'] );

	$_POST = $post + ( $method === 'POST' && $csrf === true ? [ '_csrf' => \Nino\Csrf::getToken( $appData ) ] : [] );
	$request = ninoVisit( $appData, $uri, $method, $server );
	\Nino\Http::response( $appData, $request );

	// What \Nino\Http::output() does once the answer is finished: it asks which work may run after it, and runs that once the client is let go. The outbox
	// delivers the mails it holds in an order of its own: what this request sent is put in the order of the addresses, so that the member's mail and the owner's are told apart
	global $mails;

	$sentBefore = count( $mails );

	foreach( \Nino\Http::prepareAfter( $appData, $request ) as $work )
		$work();

	$sentNow = array_slice( $mails, $sentBefore );
	usort( $sentNow, static fn( array $a, array $b ): int => strcmp( (string) $a['to'], (string) $b['to'] ) );
	array_splice( $mails, $sentBefore, count( $sentNow ), $sentNow );

	$_POST = [];

	return $request;
}

/**
 *	A request of the site rendered the way a visitor is sent it: the response as the kernel makes it, the body through the
 *	fills and shortcodes
 *
 *	@param		array 		&$appData
 *	@param		array 		$request			As signServe() answers it
 *
 *	@return 	string										The body, rendered
 */
function signBody( array &$appData, array $request ): string {

	return is_string( $request['/nino/http/response']['body'] ?? null ) === true ? \Nino\Html::renderHtml( $appData, $request['/nino/http/response']['body'] ) : '';
}

/**
 *	A new window for every cap: the counter of the feature, the one of the login links and the mail cap of the kernel, which
 *	counts the same client
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function signFresh( array &$appData ): void {

	foreach( [ 'sign-tries', 'members-tries', 'ratelimit', 'sysmail-system', 'sysmail-test' ] as $counter )
		ninoCounterSet( $appData, $counter, [] );

	foreach( [ \Nino\MailQueue::WAITING, \Nino\MailQueue::DUE, '/data/mail-keys.php', \Nino\MailQueue::FAILED ] as $file )
		@unlink( \Nino\Filesystem::path( $appData, $file ) );

	unset( $appData['./nino/filesystem/cache'] );
}

/**
 *	The mails that wait in the outbox of the kernel, as the file holds them: read from the disk, never from the cache of this process, since the children write it
 *
 *	@param		array 		&$appData
 *
 *	@return 	array										id => the mail
 */
function signOutbox( array &$appData ): array {

	clearstatcache();
	$state = \Nino\MailQueue::decode( (string) @file_get_contents( \Nino\Filesystem::path( $appData, \Nino\MailQueue::WAITING ) ) );

	return is_array( $state ) === true ? array_column( (array) ( $state['waiting'] ?? [] ), null, 'id' ) : [];
}

/**
 *	A visitor who has never been here: no session, nobody signed in
 *
 *	@return 	void
 */
function signVisitor(): void {

	$_SESSION = [];
	unset( $GLOBALS['appData']['./nino/auth/current'] );
}

/**
 *	Who is at the workbench: nobody, the plain account (which may do nothing there) or the full-access one
 *
 *	@param		string		$who					'', 'plain' or 'admin'
 *
 *	@return 	void
 */
function signWorkbench( string $who ): void {

	global $appData;

	signVisitor();

	if( $who === 'admin' )
		\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );
	else if( $who === 'plain' )
		\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );
}

/**
 *	The id in capitals - which is no id. An id of only digits has no letter to turn into a capital and would be the id itself, which the
 *	checks that hold it for no id would then be run against: so then a capital stands in for the first digit
 *
 *	@param		string		$id
 *
 *	@return 	string										Never the id itself
 */
function signCapitals( string $id ): string {

	$capitals = strtoupper( $id );

	return $capitals === $id ? 'F'. substr( $id, 1 ) : $capitals;
}

/**
 *	Sign an account in the way a member does: a link is mailed, the page it opens asks, the button of that page signs in
 *
 *	@param		array 		&$appData
 *	@param		array 		$account
 *
 *	@return 	bool											Whether the member is signed in afterwards
 */
function signSignIn( array &$appData, array $account ): bool {

	global $mails;

	signFresh( $appData );
	signVisitor();

	$count = count( $mails );

	if( \Nino\Modules\Members::link( $appData, $account, 'login' ) !== true || preg_match( '~href="https://example\.org/club/\.members/login\?t=([A-Za-z0-9_-]+)"~', (string) ( $mails[$count]['body'] ?? '' ), $found ) !== 1 )
		return false;

	signServe( $appData, '/.members/login?t='. $found[1] );
	signServe( $appData, '/.members/confirm', 'POST', [ 'token' => $found[1] ] );

	return ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $account['id'];
}

/**
 *	A member whose account was switched off and on again has a new epoch - the sessions it had are over: sign it in again and keep the session
 *
 *	@param		array 		&$appData
 *	@param		array 		$account
 *	@param		string		$who					The key of $GLOBALS['sessions']
 *
 *	@return 	void
 */
function signResign( array &$appData, array $account, string $who ): void {

	signSignIn( $appData, $account );
	$GLOBALS['sessions'][$who] = $_SESSION;
	signVisitor();
}

/**
 *	Speak as somebody: the session of the browser they signed in with, or none
 *
 *	@param		string		$who					A key of $GLOBALS['sessions'], or '' for a visitor
 *
 *	@return 	void
 */
function signAs( string $who ): void {

	$_SESSION = $who === '' ? [] : $GLOBALS['sessions'][$who];
	unset( $GLOBALS['appData']['./nino/auth/current'] );
}

/**
 *	A picture a member drew: a transparent canvas with strokes on it, as the pad posts it
 *
 *	@param		int				$width
 *	@param		int				$height
 *	@param		bool			$strokes			Whether anything is drawn on it
 *
 *	@return 	string										The PNG bytes
 */
function signPng( int $width = 600, int $height = 200, bool $strokes = true ): string {

	$image = imagecreatetruecolor( $width, $height );
	imagealphablending( $image, false );
	imagesavealpha( $image, true );
	imagefill( $image, 0, 0, (int) imagecolorallocatealpha( $image, 0, 0, 0, 127 ) );

	if( $strokes === true ) {
		imagesetthickness( $image, 3 );
		imageline( $image, 30, 120, 200, 40, (int) imagecolorallocate( $image, 16, 22, 47 ) );
		imageline( $image, 200, 40, 380, 150, (int) imagecolorallocate( $image, 16, 22, 47 ) );
		imageline( $image, 380, 150, 560, 60, (int) imagecolorallocate( $image, 16, 22, 47 ) );
		// A stroke across whatever the measure is, so that the smallest canvas holds some ink as well
		imageline( $image, 2, $height - 3, $width - 3, 2, (int) imagecolorallocate( $image, 16, 22, 47 ) );
	}

	ob_start();
	imagepng( $image );

	return (string) ob_get_clean();
}

/**
 *	The data URL of picture bytes, as the canvas makes it
 *
 *	@param		string		$bytes
 *
 *	@return 	string
 */
function signUrl( string $bytes ): string {

	return 'data:image/png;base64,'. base64_encode( $bytes );
}

/**
 *	The number of pages of a PDF, and the content of one, inflated: the operators a layout wrote
 *
 *	@param		string		$pdf
 *
 *	@return 	int
 */
function signPages( string $pdf ): int {

	return preg_match_all( '#/Type /Page\b#', $pdf );
}

/**
 *	Whether pdftotext is missing - and then the pin that reads words through it is NOT run, which is said where it happens, once for each pin, and counted: a missing
 *	program must not make a pin pass without a word. The check that asks is true where the program is missing, so that the pin is skipped and not red
 *
 *	@param		string		$pin					Which pin, for the line that says it
 *
 *	@return 	bool
 */
function signNoPdftotext( string $pin ): bool {

	static $found = null;

	$found ??= trim( (string) shell_exec( 'command -v pdftotext 2>/dev/null' ) ) !== '';

	if( $found === true )
		return false;

	$GLOBALS['pdfNotes'] = ( $GLOBALS['pdfNotes'] ?? 0 ) + 1;
	echo "  note - pdftotext is not on the path: the pin on the words of a document ($pin) was NOT run\n";

	return true;
}

/**
 *	The text of a PDF as pdftotext reads it
 *
 *	@param		string		$pdf
 *	@param		string		$options
 *
 *	@return 	string
 */
function signText( string $pdf, string $options = '-layout' ): string {

	$file = ninoSandboxDir( $GLOBALS['appData'] ). '/read.pdf';
	file_put_contents( $file, $pdf );

	return (string) shell_exec( 'pdftotext '. $options. ' '. escapeshellarg( $file ). ' - 2>/dev/null' );
}

// --- The feature - manifest and activation -------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "sign", without a warning', is_array( $manifest ) && $manifest['key'] === 'sign' && ninoWarnings() === [] );
check( 'class, version and category are what the plan says: a feature of communication, 2.0.0, that requires Basis - the page is made of its components - the PDF feature and the Members, and the extension gd',
	is_array( $manifest ) && $manifest['module'] === '\\Nino\\Modules\\Sign' && $manifest['version'] === '2.0.0' && $manifest['category'] === 'communication' && $manifest['requires'] === [ 'basis', 'pdf', 'members' ]
	&& $manifest['php'] === [ 'ext' => [ 'gd' ] ] && $manifest['nino'] === '^2.0' );
check( 'it names itself in both interface languages', is_array( $manifest ) && $manifest['name'] === [ 'en_US' => 'Signature', 'de_DE' => 'Unterschrift' ]
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it owns the directory of the requests and nothing else - not the counter, which is an hour long - and brings its script; the look is the standard part of the group, and it names no component and no stack',
	is_array( $manifest ) && $manifest['data'] === [ '/data/sign' ] && $manifest['assets'] === [ '/features/Sign/assets/sign.js' ] && array_key_exists( 'components', $manifest ) === false && array_key_exists( 'stacks', $manifest ) === false );
check( 'the settings are the four of the plan, each with its type, its default and its bounds - and no tag is delivered: the tags are words of an application',
	is_array( $manifest ) && array_keys( $manifest['settings'] ) === [ 'days', 'notifyOwner', 'attempts', 'tags' ]
	&& [ $manifest['settings']['days']['type'], $manifest['settings']['days']['default'], $manifest['settings']['days']['min'], $manifest['settings']['days']['max'], $manifest['settings']['days']['unit'] ] === [ 'int', 14, 1, 90, 'days' ]
	&& $manifest['settings']['notifyOwner']['type'] === 'bool' && $manifest['settings']['notifyOwner']['default'] === true
	&& [ $manifest['settings']['attempts']['type'], $manifest['settings']['attempts']['default'], $manifest['settings']['attempts']['min'], $manifest['settings']['attempts']['max'], $manifest['settings']['attempts']['unit'] ] === [ 'int', 20, 1, 100, 'per-hour' ]
	&& $manifest['settings']['tags']['type'] === 'lines' && $manifest['settings']['tags']['default'] === [] );
check( 'it says all six sections of its manual in both languages, with the backup in the panel section and its section of the privacy policy as a line of the install section', is_array( $manifest ) && (function() use ( $manifest ): bool {
	foreach( \Nino\Features::MANUAL_SECTIONS as $section )
		foreach( (array) ( $manifest['manual'][$section] ?? [] ) as $line )
			if( is_array( $line ) === false || trim( (string) ( $line['en_US'] ?? '' ) ) === '' || trim( (string) ( $line['de_DE'] ?? '' ) ) === '' )
				return false;
	return isset( $manifest['manual']['install']['elements/privacy.php'], $manifest['manual']['install']['templates/page-sign.tpl'], $manifest['manual']['panel']['Backup'] ) === true && count( $manifest['manual']['shortcodes'] ) >= 3
		&& count( $manifest['manual']['routes'] ) === 6 && isset( $manifest['manual']['callbacks']['/members/removed'], $manifest['manual']['callbacks']['/sign/signed'], $manifest['manual']['callbacks']['/sign/declined'] );
} )() );

// The catalogue's preview reads every manifest through the kernel, and is a script of the catalogue: it is there when this test
// runs in the repository, not when the feature has been dropped into a project
$preview = dirname( __DIR__, 3 ). '/bin/catalogue.php';

if( is_file( $preview ) === false )
	echo "  note - no bin/catalogue.php beside this feature: the catalogue's preview was not asked\n";
else {
	$listed = json_decode( (string) shell_exec( escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $preview ). ' '. escapeshellarg( $root ). ' 2>/dev/null' ), true );
	check( 'the catalogue preview lists it, with its category and its version', is_array( $listed ) && count( array_filter( (array) ( $listed['packages'] ?? $listed['features'] ?? [] ), static fn( mixed $entry ): bool => is_array( $entry ) === true
		&& ( $entry['key'] ?? '' ) === 'sign' && ( $entry['category'] ?? '' ) === 'communication' && ( $entry['version'] ?? '' ) === '2.0.0' ) ) === 1 );
}

// The Legal module's type is what the feature's section is added to: put there first, where the kernel has the module
if( class_exists( '\\Nino\\Modules\\Legal' ) === true ) {
	$routes = [];
	$blacklist = [];
	$config = [];
	\Nino\Features::applyUnit( $appData, $root. '/_nino/Nino/Modules/Legal/install', [ 'de_DE', 'en_US' ], $routes, $blacklist, $config, true );
}

ninoWarnings();
check( 'the registry lists it, inactive, with nothing in the way', ( \Nino\Features::get( $appData, 'sign' )['problems'] ?? null ) === [] && ( \Nino\Features::get( $appData, 'sign' )['active'] ?? null ) === false );
check( 'activation succeeds, Basis, the PDF feature and the Members first', signActivate( $appData ) === true && \Nino\Features::get( $appData, 'basis' )['active'] === true );
$modules = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'];
check( 'the class is listed in /nino/modules after the two it requires, and the version is recorded', array_search( '\\Nino\\Modules\\Pdf', $modules, true ) < array_search( '\\Nino\\Modules\\Sign', $modules, true )
	&& array_search( '\\Nino\\Modules\\Members', $modules, true ) < array_search( '\\Nino\\Modules\\Sign', $modules, true ) && \Nino\Features::get( $appData, 'sign' )['installed'] === '2.0.0' );
$unitWords = [ 'en_US' => (array) include $dir. '/install/text/en_US.php', 'de_DE' => (array) include $dir. '/install/text/de_DE.php' ];
check( 'the words of the unit are the same keys in both languages, none of them empty, and the states a page can say - one for each - are among them',
	array_keys( $unitWords['en_US'] ) === array_keys( $unitWords['de_DE'] ) && array_filter( array_merge( $unitWords['en_US'], $unitWords['de_DE'] ), static fn( mixed $word ): bool => is_string( $word ) === false || trim( $word ) === '' ) === []
	&& array_filter( [ 'signed', 'declined', 'cancelled', 'expired', 'invalid', 'consent', 'empty', 'long', 'limit', 'changed', 'write', 'again' ], static fn( string $state ): bool => isset( $GLOBALS['unitWords']['de_DE']['[[/feature/sign/state/'. $state. ']]'], $GLOBALS['unitWords']['en_US']['[[/feature/sign/state/'. $state. ']]'] ) === false ) === [] );
$stateWord = static fn( string $locale, string $state ): string => (string) $GLOBALS['unitWords'][$locale]['[[/feature/sign/state/'. $state. ']]'];
check( 'the states are told apart in both languages - all eleven say their own thing - and no sentence holds a placeholder nobody fills ({owner}), nor promises a mail of the signed copy',
	count( array_unique( array_map( static fn( string $state ): string => $GLOBALS['stateWord']( 'en_US', $state ), [ 'signed', 'declined', 'cancelled', 'expired', 'again', 'changed', 'invalid', 'consent', 'empty', 'long', 'limit', 'write' ] ) ) ) === 12
	&& count( array_unique( array_map( static fn( string $state ): string => $GLOBALS['stateWord']( 'de_DE', $state ), [ 'signed', 'declined', 'cancelled', 'expired', 'again', 'changed', 'invalid', 'consent', 'empty', 'long', 'limit', 'write' ] ) ) ) === 12
	&& preg_match( '/[{}]/', implode( '', array_merge( $unitWords['en_US'], $unitWords['de_DE'] ) ) ) !== 1 );
check( 'the unit copied the page and merged its words into both languages', is_file( \Nino\Filesystem::path( $appData, '/templates/page-sign.tpl' ) ) === true
	&& array_filter( [ 'de_DE', 'en_US' ], static fn( string $locale ): bool => trim( (string) ( \Nino\Filesystem::getFileContent( $GLOBALS['appData'], '/text/'. $locale. '.php', [] )['[[/feature/sign/form/consent]]'] ?? '' ) ) === '' ) === [] );

if( class_exists( '\\Nino\\Modules\\Legal' ) === false )
	echo "  note - this Nino has no \\Nino\\Modules\\Legal: the section of the privacy policy was not looked for\n";
else {
	$privacy = \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] );
	check( 'the section of the privacy policy is there in both languages, at its position', isset( $privacy['de_DE']['sign']['title'], $privacy['en_US']['sign']['text'] ) === true && ( $privacy['*']['sign']['order'] ?? null ) === 870
		&& array_filter( [ 'de_DE', 'en_US' ], static fn( string $locale ): bool => trim( (string) ( $privacy[$locale]['sign']['title'] ?? '' ) ) === '' || trim( strip_tags( (string) ( $privacy[$locale]['sign']['text'] ?? '' ) ) ) === '' ) === [] );

	// What the section tells is what the feature does: the record of a signing, how long it stays, the mails, the days of the link,
	// the cookie of the form and the count of the posts - in both languages
	$privacyWords = [ 'de_DE' => [ 'IP-Adresse', 'Browser', 'Hashwert', 'löscht', 'E-Mail', '14 Tage', 'Sitzungs-Cookie', 'eine Stunde', 'einfache elektronische Signatur', 'Hashwert des gezeigten Dokuments', 'Name und die E-Mail-Adresse des Kontos', 'Wird ein Konto gelöscht', 'die nicht unterschrieben sind, samt ihren Dateien', 'abgelaufenen', 'zurückgezogenen', 'abgelehnten', 'als geändert erkannten', 'Ein unterschriebenes Dokument bleibt dagegen', 'mit Name, E-Mail-Adresse, Zeitpunkt, IP-Adresse und Browser als Beleg der Unterschrift', 'in der Verwaltung der Website', 'als Nachweis nicht mehr braucht', 'Art. 6 Abs. 1 lit. f DSGVO', 'wenn das Konto gelöscht ist' ],
		'en_US' => [ 'IP address', 'browser', 'hash', 'deletes', 'email', '14 days', 'session cookie', 'one hour', 'simple electronic signature', 'hash of the document as it was shown', 'name and the email address of the account', 'When an account is deleted', 'that are not signed', 'ran out', 'taken back', 'declined ones', 'found changed', 'A signed document stays', 'with the name, the email address, the time, the IP address and the browser as the record of the signature', 'in the administration of the website', 'not needed as proof any more', 'Art. 6(1)(f) GDPR', 'when the account is deleted' ] ];
	check( '...and it says what the feature keeps and sets: the address and the browser with a signature, the hashes, that it stays until the operator deletes it, what of the requests of an account goes with it and what stays (the signed one, with its record), when the operator deletes it, the legal basis of keeping it, the mails, the days of the link, the cookie of the form, the hour of the count and what kind of signature this is',
		array_filter( $privacyWords, static fn( array $words, string $locale ): bool => array_filter( $words, static fn( string $word ): bool => str_contains( (string) ( $GLOBALS['privacy'][$locale]['sign']['text'] ?? '' ), $word ) === false ) !== [], ARRAY_FILTER_USE_BOTH ) === [] );

	$privacy['en_US']['sign']['text'] = '<p>The editor changed this.</p>';
	\Nino\Filesystem::putFileContent( $appData, '/elements/privacy.php', $privacy );
	unset( $appData['./nino/features/all'] );
	signActivate( $appData );
	check( 'a second activation changes nothing of it - a section the editor changed stays', \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] ) === $privacy );
}

// The kernel boots as \Nino\init() does it: the register reads the folders of the features that are on, then the modules run, then the parts are read
$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$appData['/nino/modules'] = array_values( array_unique( array_merge( [ '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Csrf' ], (array) ( $stored['/nino/modules'] ?? [] ) ) ) );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Csrf::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
$ignored = ninoWarnings();

check( 'init registers the three routes with their handlers, the listener of the accounts that are deleted and the merge of a restore - and no shortcode for the two components: the register draws them',
	( $appData['/nino/http/routes']['GET://.sign']['uri'] ?? null ) === '/.sign' && ( $appData['/nino/http/routes']['POST://.sign']['uri'] ?? null ) === '/.sign' && ( $appData['/nino/http/routes']['GET://.sign/pdf']['uri'] ?? null ) === '/.sign/pdf'
	&& isset( $appData['./nino/callbacks']['/nino/http/response/GET://.sign'], $appData['./nino/callbacks']['/nino/http/response/POST://.sign'], $appData['./nino/callbacks']['/nino/http/response/GET://.sign/pdf'], $appData['./nino/callbacks']['/members/removed'], $appData['./nino/callbacks']['/nino/admin/restore'] ) === true
	&& array_intersect( [ 'sign-form', 'sign-list' ], \Nino\Html::shortcodes( $appData ) ) === [] );
check( '...and the routes keep the csrf guard: none of them says csrf false', array_filter( [ 'GET://.sign', 'POST://.sign', 'GET://.sign/pdf' ], static fn( string $route ): bool => ( $GLOBALS['appData']['/nino/http/routes'][$route]['csrf'] ?? true ) === false ) === [] );

echo "\nThe components - registry and standard part\n";

$schemas = array_intersect_key( \Nino\Components::components( $appData ), array_flip( [ 'sign-form', 'sign-list' ] ) );
check( 'the folders are read without a reason or a warning', \Nino\Features::componentProblems( $appData, 'sign' ) === [] && $ignored === [] );
check( 'the registry knows the form and the list, in their group, labelled in both languages, each with an icon of the table, and the group has a standard part, a demo and two knobs',
	array_keys( $schemas ) === [ 'sign-form', 'sign-list' ] && array_unique( array_column( $schemas, 'group' ) ) === [ 'sign' ]
	&& array_filter( $schemas, static fn( array $schema ): bool => isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === false || in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['sign']['part'] ?? false ) === true && trim( (string) ( \Nino\Components::groups( $appData )['sign']['demo'] ?? '' ) ) !== '' && count( \Nino\Components::groups( $appData )['sign']['knobs'] ) === 2 );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/sign/part/part.css' ), [ 'sign-form', 'sign-list' ] ) === [] );
check( '...the form has the attribute sample, the list has open and sample, all of them bools: open on and sample off by default', array_keys( $schemas['sign-form']['attributes'] ) === [ 'sample' ] && array_keys( $schemas['sign-list']['attributes'] ) === [ 'open', 'sample' ]
	&& $schemas['sign-list']['attributes']['open']['default'] === true && $schemas['sign-form']['attributes']['sample']['default'] === false );

echo "\n";

// --- What the tests draw on ---------------------------------------------------------------------------------

// The owner: a full-access account at the workbench and one that may do nothing there
ninoUser( $appData, 'admin@example.com' );
ninoUser( $appData, 'plain@example.com', [], 'plain password' );
signVisitor();

// The members: two who are active, one who is not
$ada = \Nino\Modules\Members::add( $appData, 'ada@example.org', 'Ada Lovelace', 'staff' );
$bob = \Nino\Modules\Members::add( $appData, 'bob@example.org', '', 'guests' );
$cyd = \Nino\Modules\Members::add( $appData, 'cyd@example.org', 'Cyd', 'staff' );
\Nino\Modules\Members::change( $appData, $cyd['id'], [ 'active' => false ] );

// The documents: the layout of the PDF feature with the owner's own signature, and the templates saved through its Store -
// one with a text, a free text, a field for the owner and a field for the member, one that has no field for a member
$ownerPng = signPng( 80, 30 );
// The tags are the project's: the PDF feature takes a template of a tag that is on its list
\Nino\Features::saveSettings( $appData, 'pdf', [ 'tags' => [ 'contract', 'privacy', 'offer' ] ] );
$layout = \Nino\Modules\Pdf\Store::saveLayout( $appData, [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '<p align="center">Page [[page]] / [[pages]]</p>' ] );
$picture = \Nino\Modules\Pdf\Store::saveImage( $appData, 'signature', $ownerPng );
$field = static fn( string $name, string $party, int $x ): array => [ 'type' => 'signature', 'name' => $name, 'party' => $party, 'label' => $party === 'owner' ? 'The owner' : 'The member', 'page' => 'last', 'x' => $x, 'y' => 230, 'w' => 60, 'h' => 20, 'line' => true ];
$saved = [
	\Nino\Modules\Pdf\Store::saveTemplate( $appData, [ 'key' => 'contract-one', 'isNew' => true, 'name' => 'Service contract', 'tag' => 'contract', 'blocks' => [
		[ 'type' => 'html', 'html' => '<h1>Service contract</h1><p>Between the club and [[client]].</p>' ],
		[ 'type' => 'input', 'name' => 'note', 'label' => 'A note', 'multiline' => true, 'required' => true ],
		$field( 'seller', 'owner', 20 ), $field( 'buyer', 'member', 110 ),
	] ] ),
	\Nino\Modules\Pdf\Store::saveTemplate( $appData, [ 'key' => 'two-fields', 'isNew' => true, 'name' => 'Two fields', 'tag' => 'privacy', 'blocks' => [
		[ 'type' => 'html', 'html' => '<p>Two places to sign.</p>' ], $field( 'first', 'member', 20 ), $field( 'second', 'member', 110 ),
	] ] ),
	\Nino\Modules\Pdf\Store::saveTemplate( $appData, [ 'key' => 'owner-only', 'isNew' => true, 'name' => 'Owner only', 'tag' => 'contract', 'blocks' => [
		[ 'type' => 'html', 'html' => '<p>Only the owner signs.</p>' ], $field( 'seller', 'owner', 20 ),
	] ] ),
	\Nino\Modules\Pdf\Store::saveTemplate( $appData, [ 'key' => 'other-tag', 'isNew' => true, 'name' => 'Other tag', 'tag' => 'offer', 'blocks' => [
		[ 'type' => 'html', 'html' => '<p>An offer.</p>' ], $field( 'buyer', 'member', 20 ),
	] ] ),
];
check( 'the layout, the owner\'s signature and four templates are saved through the Store of the PDF feature', ( $layout['ok'] ?? false ) === true && ( $picture['ok'] ?? false ) === true
	&& array_filter( $saved, static fn( array $answer ): bool => ( $answer['ok'] ?? false ) !== true ) === [] && array_keys( \Nino\Modules\Pdf::templates( $appData ) ) === [ 'other-tag', 'owner-only', 'contract-one', 'two-fields' ] );
$input = [ 'tokens' => [ 'client' => 'Ada Lovelace' ], 'inputs' => [ 'note' => "first line\nsecond line" ] ];


// --- The data layer and request() -----------------------------------------------------------------------------

echo "Modules\\Sign - request(), and what it leaves\n";

signFresh( $appData );
$before = count( $mails );
$refusals = [
	'a template nobody has' => \Nino\Modules\Sign::request( $appData, 'nowhere', $input, $ada['id'] ),
	'something that is no template key' => \Nino\Modules\Sign::request( $appData, '../contract-one', $input, $ada['id'] ),
	'a template with no field for a member' => \Nino\Modules\Sign::request( $appData, 'owner-only', $input, $ada['id'] ),
	'an account nobody has' => \Nino\Modules\Sign::request( $appData, 'contract-one', $input, 'ffffffffffff' ),
	'something that is no account' => \Nino\Modules\Sign::request( $appData, 'contract-one', $input, '../ada' ),
	'an account that is switched off' => \Nino\Modules\Sign::request( $appData, 'contract-one', $input, $cyd['id'] ),
	'no free text where one is asked for' => \Nino\Modules\Sign::request( $appData, 'contract-one', [ 'tokens' => [ 'client' => 'x' ] ], $ada['id'] ),
	'a free text of blanks' => \Nino\Modules\Sign::request( $appData, 'contract-one', [ 'inputs' => [ 'note' => " \n\t " ] ], $ada['id'] ),
];
check( 'a request is refused by name: template, member (the template has no field for one), account (nobody, none, or switched off) and input (a free text it asks for is not given)',
	$refusals === [ 'a template nobody has' => 'template', 'something that is no template key' => 'template', 'a template with no field for a member' => 'member', 'an account nobody has' => 'account',
		'something that is no account' => 'account', 'an account that is switched off' => 'account', 'no free text where one is asked for' => 'input', 'a free text of blanks' => 'input' ] );
check( '...and a refusal leaves nothing behind: no index, no directory, no mail, no warning', signIndex( $appData ) === [] && glob( \Nino\Filesystem::path( $appData, '/data/sign' ). '/*' ) === [] && count( $mails ) === $before && ninoWarnings() === [] );

$today = gmdate( 'Y-m-d' );
$made = \Nino\Modules\Sign::request( $appData, 'contract-one', $input, $ada['id'], [ 'title' => "  Service & \x07contract\n2026 " ] );
$id = (string) ( $made['id'] ?? '' );
$stored = signIndex( $appData )[$id] ?? [];
$pdfPath = (string) \Nino\Filesystem::path( $appData, '/data/sign/'. $id. '/shown.pdf' );
$pdf = is_file( $pdfPath ) === true ? (string) file_get_contents( $pdfPath ) : '';
$mail = end( $mails ) ?: [];
preg_match( '~href="https://example\.org/club/\.sign\?t=([A-Za-z0-9_-]{43})"~', (string) ( $mail['body'] ?? '' ), $link );
$token = $link[1] ?? '';

check( 'a request is made: the answer is the request as get() has it - an id of 12 hex characters, the title cleaned, status requested, the account - and says the mail was delivered', is_array( $made ) && preg_match( '/^[a-f0-9]{12}$/D', $id ) === 1
	&& $made['title'] === 'Service & contract 2026' && $made['status'] === 'requested' && $made['account'] === $ada['id'] && $made['template'] === 'contract-one' && $made['mailed'] === true
	&& array_diff_key( $made, \Nino\Modules\Sign::get( $appData, $id ) ) === [ 'mailed' => true ] );
check( '...the index holds it in the shape of the plan: the template, the data as given, the title, the account, the status, the hash of the token, the time it is good until, the times, the hash of the PDF, the record of a signing that is not there yet, and the page it came from',
	array_keys( $stored ) === [ 'template', 'data', 'title', 'account', 'status', 'token', 'expires', 'created', 'viewed', 'signed', 'declined', 'changed', 'hashShown', 'hashSigned', 'method', 'record', 'page' ]
	&& $stored['data'] === $input && $stored['viewed'] === null && $stored['signed'] === null && $stored['declined'] === null && $stored['changed'] === null && $stored['page'] === '' && $stored['hashSigned'] === '' && $stored['method'] === ''
	&& $stored['record'] === [ 'ip' => '', 'agent' => '', 'consent' => '' ] && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $stored['created'] ) === 1 && abs( $stored['expires'] - ( time() + 14 * 86400 ) ) < 5 );
check( '...the PDF is kept as shown.pdf and nothing else of the request yet: it is a PDF, its sha256 is the hashShown the index holds, and signed.pdf and the picture of the signature are not there',
	str_starts_with( $pdf, '%PDF-1.4' ) === true && $stored['hashShown'] === hash( 'sha256', $pdf ) && is_file( dirname( $pdfPath ). '/signed.pdf' ) === false && is_file( dirname( $pdfPath ). '/signature.png' ) === false
	&& \Nino\Modules\Sign::path( $appData, $id, 'shown' ) === $pdfPath && \Nino\Modules\Sign::path( $appData, $id, 'signed' ) === null );
check( '...the document is the template as the PDF feature makes it, with the day and the title: the text, the free text, the day of the request and the title in the file',
	signPages( $pdf ) === 1 && str_contains( $pdf, '/CreationDate (D:'. str_replace( '-', '', $today ). '000000Z)' ) === true && str_contains( $pdf, '/Title (Service & contract 2026)' ) === true );

if( signNoPdftotext( 'the words of the shown document' ) === false )
	check( '...and pdftotext reads the text of it', preg_match( '/Between the club and Ada Lovelace/', signText( $pdf ) ) === 1 && preg_match( '/first line\s+second line/', signText( $pdf ) ) === 1 );

check( '...the owner\'s own signature is drawn on its field (a picture and its mask on the page) and the member\'s field shows its label alone: one picture in the file', substr_count( $pdf, '/Subtype /Image' ) === 2 );
check( 'the member is mailed once, to the address of the account, in the site\'s native language, with the link - the token is 43 characters of base64url - the title escaped and the days the link is valid for',
	count( $mails ) === $before + 1 && $mail['to'] === 'ada@example.org' && $mail['subject'] === 'Ein Dokument wartet auf Deine Unterschrift' && $token !== '' && str_contains( $mail['body'], 'Service &amp; contract 2026' ) === true
	&& str_contains( $mail['body'], 'Hallo Ada Lovelace,' ) === true && str_contains( $mail['body'], 'gültig für 14 Tage.' ) === true && $mail['replyTo'] === 'owner@example.org' );
// The language of an account, where a module knows one: a request for it is mailed in it
signFresh( $appData );
\Nino\Callbacks::registerCallback( $appData, '/members/language', static function( array &$appData, array &$ask ) use ( $ada ): void { $ask['locale'] = $ask['account'] === $ada['id'] ? 'en_US' : ''; } );
$sameAccount = \Nino\Modules\Sign::request( $appData, 'contract-one', $input, $ada['id'] );
$englishMail = end( $mails ) ?: [];
unset( $appData['./nino/callbacks']['/members/language'] );
\Nino\Modules\Sign::remove( $appData, (string) ( $sameAccount['id'] ?? '' ) );
check( '...an account whose language a module names is mailed the request in it, though the site\'s native language is German: the subject, the greeting and the days of the link are the English ones, and the language of the request is back',
	is_array( $sameAccount ) && ( $englishMail['subject'] ?? '' ) === (string) \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/sign/mail/request-subject]]'] && ( $englishMail['subject'] ?? '' ) !== $mail['subject'] && str_contains( (string) ( $englishMail['body'] ?? '' ), (string) \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/sign/mail/greeting]]']. ' Ada Lovelace' ) === true && $appData['./nino/locales/current'] === 'de_DE' );
check( '...the token is kept as its sha256 and as nothing else: not in the index, not on the disk', $stored['token'] === hash( 'sha256', $token ) && preg_match( '/^[a-f0-9]{64}$/D', $stored['token'] ) === 1
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/sign/requests.php' ) ), $token ) === false
	&& str_contains( (string) shell_exec( 'grep -rl '. escapeshellarg( $token ). ' '. escapeshellarg( \Nino\Filesystem::path( $appData, '/data' ) ). ' 2>/dev/null' ), $token ) === false );
check( '...and the API never gives the hash of the token out: get(), requests() and the answer of request() have none', array_key_exists( 'token', \Nino\Modules\Sign::get( $appData, $id ) ) === false
	&& array_key_exists( 'token', \Nino\Modules\Sign::requests( $appData )[0] ) === false && array_key_exists( 'token', $made ) === false );
echo "\n";

// The options: how long the link is good for, the page it returns to, the title
signFresh( $appData );
$withDays = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'days' => 3 ] );
$tooFew = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'days' => 0 ] );
$tooMany = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'days' => 200 ] );
$expires = static fn( array $request ): int => signIndex( $GLOBALS['appData'] )[ $request['id'] ]['expires'];
check( 'days sets how long the link is good for and is held to 1 to 90; the setting days (14) is the default, and a request with no title has the name of the template for one',
	abs( $expires( $withDays ) - ( time() + 3 * 86400 ) ) < 5 && abs( $expires( $tooFew ) - ( time() + 86400 ) ) < 5 && abs( $expires( $tooMany ) - ( time() + 90 * 86400 ) ) < 5 && $withDays['title'] === 'Two fields' );
\Nino\Features::saveSettings( $appData, 'sign', [ 'days' => '5' ] );
$bySetting = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
check( '...and the setting is what a request without days takes', abs( $expires( $bySetting ) - ( time() + 5 * 86400 ) ) < 5 );
\Nino\Features::saveSettings( $appData, 'sign', [ 'days' => '14' ] );

$linkOf = static function( array $mail ): string {
	preg_match( '~href="(https://example\.org/club/\.sign\?[^"]+)"~', (string) $mail['body'], $found );
	return html_entity_decode( $found[1] ?? '' );
};
$returns = [];
foreach( [ '/club/documents?x=1#top' => '/club/documents', '/' => '/', '//evil.example' => '', '/.sign' => '', '/.members/login' => '', 'club' => '', 'https://evil.example/' => '', "/a b" => '', '/\\evil' => '', '/javascript:alert(1)' => '', '' => '' ] as $page => $expected ) {
	signFresh( $appData );
	$request = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'page' => (string) $page ] );
	$returns[$page] = preg_match( '~&return=([^&]*)$~', $linkOf( end( $mails ) ), $found ) === 1 ? rawurldecode( $found[1] ) : '';
	\Nino\Modules\Sign::remove( $appData, $request['id'] );
}
check( 'page names the path the link takes the member back to, held to the rules of a return path of the Members feature: a path of the site without its query, no endpoint of a module, no address and no path with a space or a scheme - any other is left out of the link',
	$returns === [ '/club/documents?x=1#top' => '/club/documents', '/' => '/', '//evil.example' => '', '/.sign' => '', '/.members/login' => '', 'club' => '', 'https://evil.example/' => '', "/a b" => '', '/\\evil' => '', '/javascript:alert(1)' => '', '' => '' ] );
signFresh( $appData );
$paged = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'page' => '/club/documents?x=1' ] );
$plain = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
$pagedMails = count( $mails );
signFresh( $appData );
$remindedPaged = \Nino\Modules\Sign::remind( $appData, $paged['id'] );
$remindedPlain = \Nino\Modules\Sign::remind( $appData, $plain['id'] );
check( 'the page is kept on the request - the path without its query, \'\' for none - and a reminder takes the member to it again: the new link returns to the page request() named, and none where there was none',
	signIndex( $appData )[ $paged['id'] ]['page'] === '/club/documents' && signIndex( $appData )[ $plain['id'] ]['page'] === '' && $paged['page'] === '/club/documents' && $remindedPaged === true && $remindedPlain === true
	&& str_ends_with( $linkOf( $mails[$pagedMails] ), '&return=%2Fclub%2Fdocuments' ) === true && str_contains( $linkOf( $mails[$pagedMails + 1] ), 'return' ) === false );
\Nino\Modules\Sign::remove( $appData, $paged['id'] );
\Nino\Modules\Sign::remove( $appData, $plain['id'] );

$long = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'title' => str_repeat( 'ä', 150 ) ] );
check( 'a title is cut at 120 characters, counted as characters', mb_strlen( $long['title'] ) === 120 );
\Nino\Modules\Sign::remove( $appData, $long['id'] );
$messy = \Nino\Modules\Sign::request( $appData, 'contract-one', [ 'tokens' => [ 'client' => [ 'name' => 'Ada', 'object' => new stdClass() ], 'a' => [ 'b' => [ 'c' => [ 'd' => 'kept', 'e' => [ 'too deep' ] ] ] ], 'n' => 5, 'f' => 1.5, 'b' => true ],
	'slots' => [ 'a' => '<p>x</p>', 'b' => [ 1 ], 'c' => 5 ], 'inputs' => [ 'note' => 'n', 'other' => [ 'x' ] ], 'extra' => 'dropped' ], $ada['id'] );
check( 'what a caller hands to the PDF feature is kept as it is given, cleaned: strings and numbers in the tokens, down to four keys, and strings alone in the slots and the inputs - no object, no flag, nothing the PDF feature does not read',
	is_array( $messy ) && signIndex( $appData )[ $messy['id'] ]['data'] === [ 'tokens' => [ 'client' => [ 'name' => 'Ada' ], 'a' => [ 'b' => [ 'c' => [ 'd' => 'kept' ] ] ], 'n' => 5, 'f' => 1.5 ], 'slots' => [ 'a' => '<p>x</p>' ], 'inputs' => [ 'note' => 'n' ] ] );
\Nino\Modules\Sign::remove( $appData, $messy['id'] );

// The mail that is not delivered leaves the request, and a warning that names the request and no address
signFresh( $appData );
$delivery = false;
ninoWarnings();
$unsent = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
$warned = ninoWarnings();
$delivery = true;
check( 'a mail that is not delivered leaves the request there, says so in "mailed", and warns - naming the request by its id and no address', is_array( $unsent ) && $unsent['mailed'] === false && isset( signIndex( $appData )[ $unsent['id'] ] ) === true
	&& count( $warned ) === 1 && str_contains( $warned[0], $unsent['id'] ) === true && str_contains( $warned[0], 'bob@example.org' ) === false && str_contains( $warned[0], 'example.org' ) === false );
\Nino\Modules\Sign::remove( $appData, $unsent['id'] );

\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => '' ], '*' );
$mailsBefore = count( $mails );
$noAddress = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
$warned = ninoWarnings();
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => 'example.org/club' ], '*' );
check( 'a site with no address of its own has no link to mail: the request is there, no mail is sent, and the warning says why', is_array( $noAddress ) && $noAddress['mailed'] === false && count( $mails ) === $mailsBefore
	&& count( $warned ) === 1 && str_contains( $warned[0], 'has no address' ) === true );
\Nino\Modules\Sign::remove( $appData, $noAddress['id'] );

// A write that cannot be made is the request that is not made: 'write', no directory left behind, no mail
signFresh( $appData );
$indexPath = \Nino\Filesystem::path( $appData, '/data/sign/requests.php' );
$indexBytes = (string) file_get_contents( $indexPath );
unlink( $indexPath );
mkdir( $indexPath );
$mailsBefore = count( $mails );
$notWritten = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
$directories = array_values( array_diff( array_map( 'basename', glob( \Nino\Filesystem::path( $appData, '/data/sign' ). '/*', GLOB_ONLYDIR ) ?: [] ), [ 'requests.php' ] ) );
rmdir( $indexPath );
file_put_contents( $indexPath, $indexBytes );
ninoWarnings();
check( 'a request that cannot be written is "write": the index as it was, no directory of the request left, no mail', $notWritten === 'write' && file_get_contents( $indexPath ) === $indexBytes && count( $mails ) === $mailsBefore
	&& $directories === array_values( array_filter( $directories, static fn( string $name ): bool => isset( signIndex( $GLOBALS['appData'] )[$name] ) ) ) );

// get(), requests(), path(), and the reading of a file that anybody can edit
$second = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'title' => 'Second' ] );
$all = \Nino\Modules\Sign::requests( $appData );
check( 'requests() lists the requests, the newest first, and those of one account when asked; get() answers one by its id, null for an id nobody has or that is none',
	$all[0]['id'] === $second['id'] && count( $all ) === count( signIndex( $appData ) ) && count( array_filter( $all, static fn( array $request ): bool => $request['account'] === $GLOBALS['ada']['id'] ) ) === 1
	&& array_column( \Nino\Modules\Sign::requests( $appData, $ada['id'] ), 'id' ) === [ $id ] && \Nino\Modules\Sign::requests( $appData, 'ffffffffffff' ) === []
	&& \Nino\Modules\Sign::get( $appData, $id )['id'] === $id && \Nino\Modules\Sign::get( $appData, 'ffffffffffff' ) === null && \Nino\Modules\Sign::get( $appData, '../'. $id ) === null );
check( '...path() answers the absolute path of a PDF that is there - shown or signed, nothing else - and null for the rest: signed before the signing, a kind that is none, an id nobody has',
	\Nino\Modules\Sign::path( $appData, $id, 'shown' ) === $pdfPath && \Nino\Modules\Sign::path( $appData, $id, 'signed' ) === null && \Nino\Modules\Sign::path( $appData, $id, 'signature' ) === null
	&& \Nino\Modules\Sign::path( $appData, 'ffffffffffff', 'shown' ) === null && \Nino\Modules\Sign::path( $appData, '../x', '../x' ) === null );

// A link in the directory of a request is no file of it: it could point anywhere
$linked = dirname( $pdfPath ). '/signed.pdf';
$linkMade = symlink( $pdfPath, $linked );
check( '...a file that is a link is no file of the request: signed.pdf as a link to shown.pdf is not given out', $linkMade === true && is_file( $linked ) === true && \Nino\Modules\Sign::path( $appData, $id, 'signed' ) === null );
@unlink( $linked );

$edited = signIndex( $appData );
$edited['junk'] = [ 'template' => 'x' ];
$edited[$second['id']]['status'] = 'nonsense';

// Entries that are good in all but one thing: a key that is no id, an account, a status, a token, a time, a hash, a template
$flawed = [ 'not-an-id' => [], 'aaaaaaaaaaa1' => [ 'account' => 'not-an-id' ], 'aaaaaaaaaaa2' => [ 'status' => 'nonsense' ], 'aaaaaaaaaaa3' => [ 'token' => 'plain' ],
	'aaaaaaaaaaa4' => [ 'expires' => 'soon' ], 'aaaaaaaaaaa5' => [ 'hashShown' => 'x' ], 'aaaaaaaaaaa6' => [ 'template' => 'Not a template' ] ];

foreach( $flawed as $key => $flaw )
	$edited[$key] = $flaw + $stored;

\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $edited );
check( 'the index is plain php anybody can edit: an entry that is not what it has to be - a key that is no id, an account, a status, a token, a time, a hash or a template that is none - is left out of what the API reads, each on its own',
	array_column( \Nino\Modules\Sign::requests( $appData ), 'id' ) === array_values( array_diff( array_column( $all, 'id' ), [ $second['id'] ] ) )
	&& array_intersect( array_column( \Nino\Modules\Sign::requests( $appData ), 'id' ), array_keys( $flawed ) ) === [] );
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', array_diff_key( $edited, $flawed + [ 'junk' => 1 ] ) + [ $second['id'] => signIndex( $appData )[$second['id']] ] );
$restored = signIndex( $appData );
$restored[$second['id']]['status'] = 'requested';
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $restored );

check( 'a request is put in the index once: the same id again is refused, and so is an entry that is not one', \Nino\Modules\Sign\Store::add( $appData, $id, $stored ) === false
	&& \Nino\Modules\Sign\Store::add( $appData, 'bbbbbbbbbbbb', [ 'template' => 'x' ] ) === false && isset( signIndex( $appData )['bbbbbbbbbbbb'] ) === false );
echo "\n";

// --- GET /.sign - the page of a request ------------------------------------------------------------------------

echo "GET /.sign - the page the link opens, and what a GET changes\n";

// Two members, each signed in once and keeping the session of the browser they signed in with, to be put back when the test speaks as them
$sessions = [];

foreach( [ 'ada' => $ada, 'bob' => $bob ] as $who => $account ) {
	signSignIn( $appData, $account );
	$sessions[$who] = $_SESSION;
}

// Each session, put back, is its member's and nobody else's
$whose = [];

foreach( [ 'ada' => $ada, 'bob' => $bob ] as $who => $account ) {
	signAs( $who );
	$whose[$who] = ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $account['id'];
}

signVisitor();
check( 'the two members are signed in, each in a session of their own: put back, each is its member and nobody else, and a visitor is nobody', \Nino\Modules\Members::current( $appData ) === null && count( $sessions ) === 2 && $sessions['ada'] !== $sessions['bob'] && $whose === [ 'ada' => true, 'bob' => true ] );

// A third member, with a name: the check that lets a token in is not made on a member who has none
$carla = \Nino\Modules\Members::add( $appData, 'carla@example.org', 'Carla Named', 'guests' );
signSignIn( $appData, $carla );
$sessions['carla'] = $_SESSION;
signVisitor();

// The page template of the project and the frames it stands in: stand-ins of the base unit's, which a project has from its wizard
\Nino\Filesystem::putFileContent( $appData, '/templates/html-header.tpl', '<main>' );
\Nino\Filesystem::putFileContent( $appData, '/templates/html-footer.tpl', '</main>' );

$viewed = static fn( string $of ): ?string => signIndex( $GLOBALS['appData'] )[$of]['viewed'] ?? null;
check( 'a request nobody has opened has no time of viewing, and waits', $viewed( $id ) === null && signIndex( $appData )[$id]['status'] === 'requested' );

$head = signServe( $appData, '/.sign?t='. $token, 'HEAD' );
check( 'a HEAD marks nothing: the same page answers, the request is as it was', $head['/nino/http/response']['statusCode'] === 200 && $viewed( $id ) === null && signIndex( $appData )[$id]['status'] === 'requested' );

$page = signServe( $appData, '/.sign?t='. $token );
$response = $page['/nino/http/response'];
$form = \Nino\Html::renderHtml( $appData, '[sign-form]' );
if( getenv("SIGN_DUMP") ) file_put_contents( "/tmp/claude-0/form.html", $form );
check( 'the link answers the page in place: 200, the page template of the unit as the body, kept out of every cache, and no referrer - the address carries the token', $response['statusCode'] === 200 && $response['body'] === '[template /templates/page-sign]'
	&& $response['header']['Cache-Control'] === 'no-store' && $response['header']['Referrer-Policy'] === 'no-referrer' && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-sign.tpl' ) ), '[sign-form]' ) === true );
$localeNow = $appData['./nino/locales/current'] ?? null;
$ownWords = [];
foreach( [ 'en_US', 'de_DE' ] as $locale )
	foreach( [ 'name', 'title', 'description' ] as $detail )
		$ownWords[$locale][$detail] = trim( (string) ( \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] )['[[/_nino/webpage/.sign/'. $detail. ']]'] ?? '' ) );
check( 'the page has details of its own: it is headed as the route /.sign - not as the start page of the members - and the unit brings the name, the title and the description of that page in both languages, which the head reads as its title',
	$response['uri'] === '/.sign' && array_filter( array_merge( $ownWords['en_US'], $ownWords['de_DE'] ), static fn( string $word ): bool => $word === '' ) === [] && $ownWords['en_US']['title'] !== $ownWords['de_DE']['title']
	&& \Nino\Html::renderHtml( $appData, '[[/_nino/webpage'. $response['uri']. '/title]]' ) === $ownWords[ $appData['./nino/locales/current'] ?? 'de_DE' ]['title'] );
check( '...it marks the request as viewed - the one write a GET makes: a time, the status viewed, and nothing else of the request changed', $viewed( $id ) !== null && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) $viewed( $id ) ) === 1 && signIndex( $appData )[$id]['status'] === 'viewed'
	&& array_keys( array_filter( signIndex( $appData )[$id], static fn( mixed $value, string $key ): bool => $GLOBALS['stored'][$key] !== $value, ARRAY_FILTER_USE_BOTH ) ) === [ 'status', 'viewed' ] );
// The time of the first visit is set back, so that a visit that wrote it again - in the same second or not - would show
\Nino\Filesystem::mutate( $appData, '/data/sign/requests.php', static function( mixed $state ) use ( $id ): array {
	$state[$id]['viewed'] = '2020-01-01T00:00:00Z';
	return $state;
}, [] );
$firstView = $viewed( $id );
signServe( $appData, '/.sign?t='. $token );
check( '...and the first time stays: a second visit writes nothing', $firstView === '2020-01-01T00:00:00Z' && $viewed( $id ) === $firstView );

$dom = static fn( string $query ): array => signFind( $GLOBALS['form'], $query );
check( 'the form is the component: the title, the document in a frame that asks for the PDF with the token, a link that opens it, the sentence with a box that has to be ticked, the pad and the field of the typed name',
	signContent( $dom( '//h2[@class="sign-form-title"]' ) ) === 'Service & contract 2026' && signAttr( $dom( '//iframe' ), 'src' ) === '/.sign/pdf?t='. $token. '&kind=shown'
	&& signAttr( $dom( '//a[@target="_blank"]' ), 'href' ) === '/.sign/pdf?t='. $token. '&kind=shown' && signHas( $dom( '//input[@type="checkbox"][@name="consent"]' ), 'required' ) === true
	&& str_contains( signContent( $dom( '//label[@for="'. signAttr( $dom( '//input[@name="consent"]' ), 'id' ). '"]' ) ), 'Ich habe das Dokument gelesen' ) === true
	&& count( $dom( '//canvas[@data-sign-pad]' ) ) === 1 && signHas( $dom( '//div[@data-sign-draw]' ), 'hidden' ) === true && count( $dom( '//button[@data-sign-clear]' ) ) === 1
	&& signAttr( $dom( '//input[@name="name"]' ), 'maxlength' ) === '60' && signHas( $dom( '//div[@data-sign-name]' ), 'hidden' ) === false );
check( '...the words that ask for the box and the signature stand in the form, which shows them only while there is something to sign - not in the page of the unit, which a request that is over shares',
	count( $dom( '//p[@class="sign-form-intro"]' ) ) === 1 && signContent( $dom( '//p[@class="sign-form-intro"]' ) ) === \Nino\Html::renderHtml( $appData, '[[/feature/sign/form/intro]]' ) && signContent( $dom( '//p[@class="sign-form-intro"]' ) ) !== ''
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-sign.tpl' ) ), 'intro/text' ) === false && str_contains( (string) file_get_contents( $dir. '/install/templates/page-sign.tpl' ), '[[/feature/sign/form/intro]]' ) === false );
check( '...it posts to /.sign with the token, the return page, the csrf token of the session and a picture field the script fills; the buttons are sign and decline, and decline needs no box ticked',
	signAttr( $dom( '//form' ), 'method' ) === 'post' && signAttr( $dom( '//form' ), 'action' ) === '/.sign' && signAttr( $dom( '//input[@name="t"]' ), 'value' ) === $token
	&& signAttr( $dom( '//input[@name="return"]' ), 'value' ) === '' && signAttr( $dom( '//input[@name="_csrf"]' ), 'value' ) === \Nino\Csrf::getToken( $appData )
	&& signAttr( $dom( '//input[@name="image"]' ), 'type' ) === 'hidden' && signHas( $dom( '//button[@name="action"][@value="sign"]' ), 'formnovalidate' ) === false
	&& signHas( $dom( '//button[@name="action"][@value="decline"]' ), 'formnovalidate' ) === true && count( $dom( '//input[@name="r"]' ) ) === 0 && str_contains( signAttr( $dom( '//form' ), 'class' ), 'nino-form' ) === false );
check( '...the form works without the script: the typed name is a field of its own with a label, and the markup holds no inline handler and no script', count( $dom( '//*[@onclick or @onchange or @onsubmit]' ) ) === 0 && count( $dom( '//script' ) ) === 0
	&& count( $dom( '//label[@for="'. signAttr( $dom( '//input[@name="name"]' ), 'id' ). '"]' ) ) === 1 );
signTouchChecks( $form, (string) file_get_contents( $dir. '/groups/sign/part/part.css' ) );
$whole = \Nino\Html::renderHtml( $appData, '[template /templates/page-sign]' );
check( '...and the page of the unit has it between the frames, with the title and the words of the page', str_contains( $whole, '<main>' ) === true && str_ends_with( trim( $whole ), '</main>' ) === true && str_contains( $whole, 'Deine Unterschrift' ) === true && str_contains( $whole, '<form' ) === true );

// A page is the form with the return page the link names, and none that is not one
$returned = signServe( $appData, '/.sign?t='. $token. '&return=%2Fclub%2Fdocuments%3Fx%3D1' );
$formReturned = \Nino\Html::renderHtml( $appData, '[sign-form]' );
$returnedBad = signServe( $appData, '/.sign?t='. $token. '&return=%2F%2Fevil.example' );
check( 'a return page the link names is in the form, as a path of the site without its query; one that is none is left out', signAttr( signFind( $formReturned, '//input[@name="return"]' ), 'value' ) === '/club/documents'
	&& signAttr( signFind( \Nino\Html::renderHtml( $appData, '[sign-form]' ), '//input[@name="return"]' ), 'value' ) === '' && $returnedBad['/nino/http/response']['statusCode'] === 200 );

// The member the request is of, signed in, opens it by its id
signAs( 'ada' );
$byId = signServe( $appData, '/.sign?r='. $id );
$formById = \Nino\Html::renderHtml( $appData, '[sign-form]' );
check( 'a member who is signed in opens their own request by its id: the same page, the key in the form and in the frame is r', $byId['/nino/http/response']['statusCode'] === 200 && signAttr( signFind( $formById, '//input[@name="r"]' ), 'value' ) === $id
	&& count( signFind( $formById, '//input[@name="t"]' ) ) === 0 && signAttr( signFind( $formById, '//iframe' ), 'src' ) === '/.sign/pdf?r='. $id. '&kind=shown' );

// Nothing says whether an id or a token exists: a stranger, another member, a visitor and a token nobody has are the same page
$strangers = [];
$strangerAnswer = static function( string $uri ) use ( &$appData ): array {
	$request = signServe( $appData, $uri );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['header']['Cache-Control'], \Nino\Html::renderHtml( $appData, '[sign-form]' ) ];
};
signAs( 'bob' );
$strangers['another member by the id'] = $strangerAnswer( '/.sign?r='. $id );
$strangers['another member with the token of the request'] = $strangerAnswer( '/.sign?t='. $token );
signAs( 'carla' );
$strangers['another member with a name, with the token of the request'] = $strangerAnswer( '/.sign?t='. $token );
signAs( 'bob' );
signVisitor();
$strangers['a visitor by the id'] = $strangerAnswer( '/.sign?r='. $id );
$strangers['an id nobody has'] = $strangerAnswer( '/.sign?r=ffffffffffff' );
$strangers['something that is no id'] = $strangerAnswer( '/.sign?r=..%2F'. $id );
$strangers['a token nobody has'] = $strangerAnswer( '/.sign?t='. str_repeat( 'A', 43 ) );
$strangers['something that is no token'] = $strangerAnswer( '/.sign?t=x' );
$strangers['no key at all'] = $strangerAnswer( '/.sign' );
$strangers['keys that are lists'] = $strangerAnswer( '/.sign?t[]=x&r[]=y' );
$same = array_filter( $strangers, static fn( array $answer ): bool => $answer === $GLOBALS['strangers']['an id nobody has'] );
check( 'another member (by the id, and with the token of the request), a visitor, an id nobody has, something that is no id, a token nobody has, no token and no key, and keys that are lists are all the same page - invalid, a 404, no form, no frame, no title',
	count( $same ) === count( $strangers ) && $strangers['an id nobody has'][0] === 404 && $strangers['an id nobody has'][1] === 'no-store' && str_contains( $strangers['an id nobody has'][2], 'sign-form-closed' ) === true
	&& str_contains( $strangers['an id nobody has'][2], 'data-sign-state="invalid"' ) === true && str_contains( $strangers['an id nobody has'][2], '<form' ) === false && str_contains( $strangers['an id nobody has'][2], '<iframe' ) === false
	&& str_contains( $strangers['an id nobody has'][2], 'Service' ) === false && str_contains( $strangers['an id nobody has'][2], 'Dieser Link führt zu keinem Dokument' ) === true );
$closedClass = preg_split( '/\s+/', trim( signAttr( signFind( $strangers['an id nobody has'][2], '//div[contains(@class,"sign-form-closed")]' ), 'class' ) ) ) ?: [];
check( 'a form that is closed carries the class sign-form once, with sign-form-closed - the class of the component is not written twice', $closedClass === [ 'sign-form', 'sign-form-closed' ] && substr_count( $strangers['an id nobody has'][2], 'class="sign-form sign-form-closed"' ) === 1 );
check( '...and none of them marked a request as viewed or wrote anything', $viewed( $id ) === $firstView && count( signIndex( $appData ) ) === count( array_filter( signIndex( $appData ) ) ) );

// The states a request is in: the page says it, there is no form, and the request is not touched
$base = signIndex( $appData );
$states = [];
foreach( [ 'expired' => static fn( array $e ): array => [ 'expires' => time() - 10 ] + $e, 'cancelled' => static fn( array $e ): array => [ 'status' => 'cancelled' ] + $e, 'declined' => static fn( array $e ): array => [ 'status' => 'declined', 'declined' => '2026-01-01T00:00:00Z' ] + $e ] as $name => $change ) {
	$changed = $base;
	$changed[$id] = $change( $base[$id] );
	\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
	$request = signServe( $appData, '/.sign?t='. $token );
	$states[$name] = [ $request['/nino/http/response']['statusCode'], \Nino\Html::renderHtml( $appData, '[sign-form]' ) ];
}
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $base );
check( 'a request that ran out, was cancelled or declined answers the page with that state and no form (200) - the title is shown, the frame and the buttons are not', array_keys( $states ) === [ 'expired', 'cancelled', 'declined' ]
	&& array_filter( $states, static fn( array $each, string $name ): bool => $each[0] !== 200 || str_contains( $each[1], 'data-sign-state="'. $name. '"' ) === false || str_contains( $each[1], '<form' ) === true || str_contains( $each[1], '<iframe' ) === true
		|| str_contains( $each[1], 'Service &amp; contract 2026' ) === false || str_contains( $each[1], 'sign-form-link' ) === true, ARRAY_FILTER_USE_BOTH ) === [] && signIndex( $appData ) === $base );

// The second the time is up is already over: a request whose time is this very second (and one second further on) is seen as expired by the status, the page and the post
$instant = [];
foreach( [ 0, 1 ] as $ahead ) {
	do {
		$now = time();
		$changed = $base;
		$changed[$id]['expires'] = $now + $ahead;
		\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
		$instant[$ahead] = [ \Nino\Modules\Sign\Store::status( $changed[$id] ), \Nino\Modules\Sign\Store::live( $changed[$id] ), signServe( $appData, '/.sign?t='. $token )['/nino/http/response']['statusCode'] ];
		$instantForm = \Nino\Html::renderHtml( $appData, '[sign-form]' );
		$instantPost = $ahead === 0 ? signServe( $appData, '/.sign', 'POST', [ 't' => $token, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ] )['/nino/http/response']['statusCode'] : 0;
		$instant[$ahead][] = str_contains( $instantForm, 'data-sign-state="expired"' );
		$instant[$ahead][] = $instantPost === 303;
		$instant[$ahead][] = glob( \Nino\Filesystem::path( $appData, '/data/sign/'. $id ). '/*' ) === [ \Nino\Filesystem::path( $appData, '/data/sign/'. $id ). '/shown.pdf' ];
		$moved = time() !== $now;
		\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $base );
	} while( $moved === true );
}
check( 'a request whose time is up this very second is expired: the status says so, it is not live, the page shows the state expired with no form, and a post for it signs nothing - and a request that has one second left is live',
	$instant[0] === [ 'expired', false, 200, true, false, true ] && $instant[1][0] !== 'expired' && $instant[1][1] === true && $instant[1][3] === false && signIndex( $appData ) === $base );

// A member who is gone, or switched off: the live request is not a page any more
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => false ] );
$inactive = signServe( $appData, '/.sign?t='. $token );
$inactiveForm = \Nino\Html::renderHtml( $appData, '[sign-form]' );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => true ] );
signResign( $appData, $ada, 'ada' );
check( 'a request whose account is switched off is no page to sign on: invalid, a 404, no form - and it is a page again when the account is on', $inactive['/nino/http/response']['statusCode'] === 404 && str_contains( $inactiveForm, '<form' ) === false
	&& signServe( $appData, '/.sign?t='. $token )['/nino/http/response']['statusCode'] === 200 );

// The component shows nothing where no route filled it
unset( $appData['./sign/request'] );
check( '[sign-form] on any other page shows nothing', \Nino\Html::renderHtml( $appData, '[sign-form]' ) === '' );
echo "\n";

// --- GET /.sign/pdf - the PDFs the kernel streams -----------------------------------------------------------------

echo "GET /.sign/pdf - the file the kernel streams, and who gets it\n";

$byToken = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown' )['/nino/http/response'];
check( 'the holder of the token is answered with the PDF named for the kernel: its absolute path, which this feature resolved, the type, shown in the browser under the cleaned title, and kept out of every cache but the browser\'s own',
	$byToken['statusCode'] === 200 && $byToken['file'] === $pdfPath && $byToken['header']['Content-Type'] === 'application/pdf' && $byToken['header']['Cache-Control'] === 'private, no-store' && $byToken['header']['X-Content-Type-Options'] === 'nosniff'
	&& $byToken['header']['Referrer-Policy'] === 'no-referrer' && $byToken['header']['Content-Disposition'] === 'inline; filename="Service _ contract 2026.pdf"; filename*=UTF-8\'\'Service%20%26%20contract%202026.pdf' );
check( '...the bytes are the kernel\'s to send: the handler reads none and sets no length, no range and no body', array_key_exists( 'Content-Length', $byToken['header'] ) === false && array_key_exists( 'Accept-Ranges', $byToken['header'] ) === false && ( $byToken['body'] ?? '' ) === '' );

$headPdf = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown', 'HEAD' );
$finalize = new ReflectionMethod( '\Nino\Http', '_finalizeResponse' );
$whole = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown' );
$finalize->invokeArgs( null, [ &$whole ] );
$ranged = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown', 'GET', [], true, [ 'HTTP_RANGE' => 'bytes=0-99' ] );
$finalize->invokeArgs( null, [ &$ranged ] );
check( 'the kernel answers the file named: the whole of it with its length and Accept-Ranges, a 206 for one range, and the headers of the handler stand; a HEAD is the headers of the GET',
	$whole['/nino/http/response']['statusCode'] === 200 && $whole['/nino/http/response']['header']['Content-Length'] === (string) strlen( $pdf ) && $whole['/nino/http/response']['header']['Accept-Ranges'] === 'bytes' && $whole['/nino/http/response']['header']['Content-Type'] === 'application/pdf'
	&& $whole['./nino/http/stream'] === [ 'path' => $pdfPath, 'start' => 0, 'length' => strlen( $pdf ) ] && $ranged['/nino/http/response']['statusCode'] === 206 && $ranged['/nino/http/response']['header']['Content-Range'] === 'bytes 0-99/'. strlen( $pdf )
	&& $headPdf['/nino/http/response']['file'] === $pdfPath );

$notFound = [];
$deny = static function( string $uri ) use ( &$appData ): array {
	$answer = signServe( $appData, $uri )['/nino/http/response'];
	return [ $answer['statusCode'], $answer['body'], $answer['header']['Content-Type'], $answer['header']['Cache-Control'], array_key_exists( 'file', $answer ), array_key_exists( 'Content-Disposition', $answer['header'] ) ];
};
$notFound['signed before the signing, by the token'] = $deny( '/.sign/pdf?t='. $token. '&kind=signed' );
$notFound['a kind that is none'] = $deny( '/.sign/pdf?t='. $token. '&kind=signature' );
$notFound['no kind'] = $deny( '/.sign/pdf?t='. $token );
$notFound['a token nobody has'] = $deny( '/.sign/pdf?t='. str_repeat( 'B', 43 ). '&kind=shown' );
$notFound['something that is no token'] = $deny( '/.sign/pdf?t=..%2F..%2Fetc&kind=shown' );
$notFound['an id, as a visitor'] = $deny( '/.sign/pdf?r='. $id. '&kind=shown' );
$notFound['no key'] = $deny( '/.sign/pdf?kind=shown' );
$notFound['keys that are lists'] = $deny( '/.sign/pdf?t[]=x&kind[]=shown' );
signAs( 'bob' );
$notFound['an id, as another member'] = $deny( '/.sign/pdf?r='. $id. '&kind=shown' );
$notFound['the token, as another member, shown'] = $deny( '/.sign/pdf?t='. $token. '&kind=shown' );
signAs( 'carla' );
$notFound['the token, as another member with a name, shown'] = $deny( '/.sign/pdf?t='. $token. '&kind=shown' );
signAs( 'bob' );
signWorkbench( 'plain' );
$notFound['an id, as an account at the workbench without the permission'] = $deny( '/.sign/pdf?r='. $id. '&kind=shown' );
signVisitor();
$visitorAnswer = $notFound['no key'];
check( 'a visitor, a token nobody has, another member, an account at the workbench without the permission, a kind that is none and signed.pdf before the signing are all the same 404 with a plain body - nothing tells that an id exists',
	$visitorAnswer[0] === 404 && $visitorAnswer[1] === 'Not Found' && str_starts_with( $visitorAnswer[2], 'text/plain' ) && $visitorAnswer[3] === 'no-store' && $visitorAnswer[4] === false && $visitorAnswer[5] === false
	&& count( array_filter( $notFound, static fn( array $answer ): bool => $answer === $GLOBALS['visitorAnswer'] ) ) === count( $notFound ) );

signAs( 'ada' );
$byMember = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=shown' )['/nino/http/response'];
signVisitor();
check( 'the member the request is of, signed in, is answered with the PDF by its id', $byMember['statusCode'] === 200 && $byMember['file'] === $pdfPath );

// The owner: the permission of the panel opens any file there is, in any state
$owner = [];
foreach( [ 'admin' => 'admin', 'plain' => 'plain' ] as $name => $who ) {
	signWorkbench( $who );
	$owner[$name] = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=shown' )['/nino/http/response']['statusCode'];
}
$changed = $base;
$changed[$id]['status'] = 'cancelled';
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
signWorkbench( 'admin' );
$ownerCancelled = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=shown' )['/nino/http/response']['statusCode'];
$ownerNoSigned = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=signed' )['/nino/http/response']['statusCode'];
signVisitor();
$tokenCancelled = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown' )['/nino/http/response']['statusCode'];
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $base );
check( 'a workbench session that holds the permission of the panel is answered with the file - in any state, a cancelled request included, and the holder of the token is not any more; an account without the permission is not; a file that is not there is a 404 for the owner too',
	$owner === [ 'admin' => 200, 'plain' => 404 ] && $ownerCancelled === 200 && $tokenCancelled === 404 && $ownerNoSigned === 404 );
$byPermission = [];

foreach( [ 'sign-manage@example.com' => [ '/_admin/sign/manage' ], 'sign-all@example.com' => [ '/_admin/sign/*' ], 'admin-all@example.com' => [ '/_admin/*' ], 'hello-only@example.com' => [ '/_admin/hello/manage' ] ] as $mail => $held ) {
	\Nino\Auth::insertUser( $appData, $mail, 'a long password', $held );
	signVisitor();
	\Nino\Auth::loginUser( $appData, $mail, 'a long password' );
	$byPermission[$mail] = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=shown' )['/nino/http/response']['statusCode'];
}

signVisitor();
check( 'the permission is the panel\'s own - held exactly, by the parent \'/_admin/sign/*\' or from above by \'/_admin/*\' - and the permission of another panel is none',
	$byPermission === [ 'sign-manage@example.com' => 200, 'sign-all@example.com' => 200, 'admin-all@example.com' => 200, 'hello-only@example.com' => 404 ] );

// The states in which the holder of the token is not answered: the request is over
$over = [];
foreach( [ 'expired' => static fn( array $e ): array => [ 'expires' => time() - 10 ] + $e, 'cancelled' => static fn( array $e ): array => [ 'status' => 'cancelled' ] + $e, 'declined' => static fn( array $e ): array => [ 'status' => 'declined' ] + $e ] as $name => $change ) {
	$changed = $base;
	$changed[$id] = $change( $base[$id] );
	\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
	$over[$name] = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown' )['/nino/http/response']['statusCode'];
}
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $base );
check( 'a request that ran out, was cancelled or declined gives the holder of its token nothing: the same 404', $over === [ 'expired' => 404, 'cancelled' => 404, 'declined' => 404 ] );

// A file that is in the index and not on the disk is the 404 of the others
$gonePath = \Nino\Modules\Sign::path( $appData, $second['id'], 'shown' );
$secondToken = '';
unlink( (string) $gonePath );
signWorkbench( 'admin' );
$goneForOwner = signServe( $appData, '/.sign/pdf?r='. $second['id']. '&kind=shown' )['/nino/http/response'];
signVisitor();
check( 'a file whose bytes are gone is the 404 of the others, not an error - for the owner too', $goneForOwner['statusCode'] === 404 && $goneForOwner['body'] === 'Not Found' && array_key_exists( 'file', $goneForOwner ) === false );
echo "\n";

// --- POST /.sign - signing and declining --------------------------------------------------------------------------

echo "POST /.sign - the buttons of the form: sign, decline, and every way they are refused\n";

$agent = "Mozilla/5.0 (Test)\x07 Browser/1.0";
$post = static function( array $fields, bool $csrf = true, array $server = [] ) use ( &$appData ): array {
	return signServe( $appData, '/.sign', 'POST', $fields + ( isset( $fields['r'] ) === true ? [] : [ 't' => $GLOBALS['token'] ] ), $csrf, $server + [ 'HTTP_USER_AGENT' => $GLOBALS['agent'] ] );
};
$answer = static function( array $request ) use ( &$appData ): array {
	$response = $request['/nino/http/response'];
	return [ $response['statusCode'], (string) ( $response['header']['Location'] ?? '' ), \Nino\Html::renderHtml( $appData, '[sign-form]' ) ];
};
$flash = static function( string $html ): string {
	$found = signFind( $html, '//p[@class="sign-form-state"]' );
	return $found === [] ? '' : $found[0]->getAttribute( 'data-sign-state' );
};
$drawn = signUrl( signPng() );
$counter = static fn(): array => ninoCounter( $GLOBALS['appData'], 'sign-tries' );
$files = static fn( string $of ): array => array_map( 'basename', glob( \Nino\Filesystem::path( $GLOBALS['appData'], '/data/sign/'. $of ). '/*' ) ?: [] );
$untouched = static fn(): bool => signIndex( $GLOBALS['appData'] ) === $GLOBALS['base'] && $GLOBALS['files']( $GLOBALS['id'] ) === [ 'shown.pdf' ];

// A link is the proof of whom it was sent to: another member who is signed in is no holder of it - the post, like the page and the PDF, is the answer for a token nobody has
signFresh( $appData );
$mailsAtStranger = count( $mails );
signAs( 'bob' );
$nobodyHas = $answer( $post( [ 'action' => 'sign', 'consent' => '1', 'image' => $drawn, 't' => str_repeat( 'C', 43 ) ] ) );
$byStranger = [ 'sign' => $answer( $post( [ 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) ), 'decline' => $answer( $post( [ 'action' => 'decline' ] ) ) ];
signAs( 'carla' );
$byStranger['named sign'] = $answer( $post( [ 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
$byStranger['named decline'] = $answer( $post( [ 'action' => 'decline' ] ) );
signVisitor();
check( 'another member who is signed in cannot sign or decline with the token of a request that is not theirs: both posts are the answer for a token nobody has - the form is not offered, nothing is signed or declined, no file is made, and no mail goes out',
	$byStranger['sign'] === $nobodyHas && $byStranger['decline'] === $nobodyHas && $byStranger['named sign'] === $nobodyHas && $byStranger['named decline'] === $nobodyHas && $nobodyHas[0] === 404 && $nobodyHas[1] === '' && str_contains( $nobodyHas[2], '<form' ) === false && $untouched() && count( $mails ) === $mailsAtStranger );
$mailsBefore = count( $mails );
$told = [];
signFresh( $appData );

[ $status, , $html ] = $answer( $post( [ 'action' => 'sign', 'consent' => '1', 'image' => $drawn ], false ) );
check( 'a post without the csrf token is the page again with the state again (403 - the session ran out, which is no link that ran out) and a form that carries the token of the session as it is now; nothing is used up, nothing is counted',
	$status === 403 && $flash( $html ) === 'again' && signAttr( signFind( $html, '//input[@name="t"]' ), 'value' ) === $token && signAttr( signFind( $html, '//input[@name="_csrf"]' ), 'value' ) === \Nino\Csrf::getToken( $appData )
	&& $untouched() && $counter() === [] && count( $mails ) === $mailsBefore && $told === [] );

[ $status, $location, $html ] = $answer( $post( [ 'action' => 'sign', 'image' => $drawn ] ) );
check( 'the box is not ticked: the page again with the state consent (400), and the request is as it was - the signature drawn is not kept', $status === 400 && $location === '' && $flash( $html ) === 'consent' && $untouched() );
check( '...the state is said where a reader of the screen hears it: a paragraph with the role status, and the word of the state in it', signAttr( signFind( $html, '//p[@class="sign-form-state"]' ), 'role' ) === 'status' && signContent( signFind( $html, '//p[@class="sign-form-state"]' ) ) !== '' );

$nothing = [
	'no picture and no name' => [],
	'a name of blanks' => [ 'name' => " \t\n " ],
	'a name of control characters' => [ 'name' => "\x01\x02\x03" ],
	'a picture that is no data url' => [ 'image' => 'not a picture' ],
	'a data url that is no picture' => [ 'image' => signUrl( 'this is no picture' ) ],
	'a jpeg' => [ 'image' => 'data:image/jpeg;base64,'. base64_encode( (string) ( function() { $image = imagecreatetruecolor( 20, 20 ); ob_start(); imagejpeg( $image ); return ob_get_clean(); } )() ) ],
	'a gif with the type of a png' => [ 'image' => signUrl( (string) ( function() { $image = imagecreatetruecolor( 20, 20 ); ob_start(); imagegif( $image ); return ob_get_clean(); } )() ) ],
	'a canvas nobody drew on' => [ 'image' => signUrl( signPng( 600, 200, false ) ) ],
	'a picture wider than 2000 pixels' => [ 'image' => signUrl( signPng( 2001, 100 ) ) ],
	'a picture higher than 1000 pixels' => [ 'image' => signUrl( signPng( 100, 1001 ) ) ],
	'a picture and a name beside it that is good, when the picture is none' => [ 'image' => 'data:image/png;base64,!!!!', 'name' => 'Ada Lovelace' ],
];
$empty = [];
foreach( $nothing as $label => $fields ) {
	[ $status, $location, $html ] = $answer( $post( [ 'action' => 'sign', 'consent' => '1' ] + $fields ) );
	$empty[$label] = [ $status, $location, $flash( $html ) ];
}
check( 'no signature is the state empty (400): no picture and no name, a name of blanks or control characters, a picture that is none, a jpeg, a gif, a canvas nobody drew on, one wider than 2000 pixels or higher than 1000 - and a bad picture is not saved by the name beside it',
	array_filter( $empty, static fn( array $each ): bool => $each !== [ 400, '', 'empty' ] ) === [] && count( $empty ) === count( $nothing ) && $untouched() );

// A name of 61 characters is a state of its own - not the empty one - with the name field marked and described by it; 60 is signed elsewhere
foreach( [ 'x' => str_repeat( 'x', 61 ), 'umlauts' => str_repeat( 'ä', 61 ), 'padded by blanks' => '  '. str_repeat( 'y', 61 ). '  ' ] as $label => $long ) {
	[ $status, $location, $html ] = $answer( $post( [ 'action' => 'sign', 'consent' => '1', 'name' => $long ] ) );
	$nameField = signFind( $html, '//input[@name="name"]' );
	check( 'a typed name of 61 characters ('. $label. ') is the state long (400), not empty: nothing is stored, and the name field is the one that is marked invalid and described by that sentence',
		$status === 400 && $location === '' && $flash( $html ) === 'long' && $untouched() && signAttr( $nameField, 'aria-invalid' ) === 'true'
		&& signAttr( $nameField, 'aria-describedby' ) === signAttr( signFind( $html, '//p[@class="sign-form-state"]' ), 'id' ) );
}
$wordsOf = static fn( string $locale, string $state ): string => (string) ( $GLOBALS['unitWords'][$locale]['[[/feature/sign/state/'. $state. ']]'] ?? '' );
check( 'the sentence of a name that is too long is its own in both languages: it is not the one of the empty state, and it names the limit', $wordsOf( 'en_US', 'long' ) !== '' && $wordsOf( 'de_DE', 'long' ) !== ''
	&& $wordsOf( 'en_US', 'long' ) !== $wordsOf( 'en_US', 'empty' ) && $wordsOf( 'de_DE', 'long' ) !== $wordsOf( 'de_DE', 'empty' )
	&& str_contains( $wordsOf( 'en_US', 'long' ), '60' ) && str_contains( $wordsOf( 'de_DE', 'long' ), '60' ) );

[ $status, , $html ] = $answer( $post( [ 'action' => 'launch', 'consent' => '1', 'image' => $drawn ] ) );
[ $statusNone, , ] = $answer( $post( [ 'consent' => '1', 'image' => $drawn ] ) );
check( 'an action that is neither sign nor decline, and none, is the page again with no word (400)', $status === 400 && $flash( $html ) === '' && signFind( $html, '//form' ) !== [] && $statusNone === 400 && $untouched() );
check( '...and every post that got as far as the counter was counted for the visitor, whatever came of it', count( $counter() ) === 1 && array_values( $counter() )[0]['tries'] === count( $nothing ) + 6 );
echo "\n";

// --- Signing: a drawn picture ----------------------------------------------------------------------------------------

$before = signIndex( $appData )[$id];
$shownHash = hash_file( 'sha256', $pdfPath );
$signedDay = [ gmdate( 'Y-m-d' ) ];
$told = [];
signFresh( $appData );
$mailsBefore = count( $mails );
$posted = $post( [ 'action' => 'sign', 'consent' => '1', 'image' => $drawn, 'return' => '/club/documents' ] );
$signedDay[] = gmdate( 'Y-m-d' );
[ $status, $location, ] = $answer( $posted );
$after = signIndex( $appData )[$id];
$signedPath = \Nino\Modules\Sign::path( $appData, $id, 'signed' );
$signed = (string) file_get_contents( (string) $signedPath );
$sentence = (string) \Nino\Html::resolveTextfill( $appData, '/feature/sign/form/consent' );

check( 'signing answers a redirect (303) to the return page with the id and the state in the query - and nothing of the token in it', $status === 303 && $location === '/club/documents?sign='. $id. '&sign-state=signed' && str_contains( $location, $token ) === false );
check( '...the request is signed in one write: status signed, the time, the hash of the signed PDF, how it was signed - and nothing else of it changed',
	$after['status'] === 'signed' && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) $after['signed'] ) === 1 && in_array( substr( (string) $after['signed'], 0, 10 ), $signedDay, true ) === true
	&& $after['hashSigned'] === hash( 'sha256', $signed ) && $after['method'] === 'drawn' && array_diff_key( $after, array_flip( [ 'status', 'signed', 'hashSigned', 'method', 'record' ] ) ) === array_diff_key( $before, array_flip( [ 'status', 'signed', 'hashSigned', 'method', 'record' ] ) ) );
check( '...with the record of the signing: the address, the browser as it was sent but for its control characters, and the sentence the member agreed to - as the page showed it, in the language of the signing',
	$after['record'] === [ 'ip' => '127.0.0.1', 'agent' => 'Mozilla/5.0 (Test) Browser/1.0', 'consent' => $sentence ] && str_contains( $sentence, 'Ich habe das Dokument gelesen' ) === true );
check( '...both PDFs and the picture are kept, each once: shown.pdf as it was, the picture as it was posted, signed.pdf - and path() gives out the two PDFs and not the picture, which is there now', $files( $id ) === [ 'shown.pdf', 'signature.png', 'signed.pdf' ] && hash_file( 'sha256', $pdfPath ) === $shownHash
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/data/sign/'. $id. '/signature.png' ) ) === signPng() && $after['hashShown'] === $shownHash
	&& \Nino\Modules\Sign::path( $appData, $id, 'signed' ) === dirname( $pdfPath ). '/signed.pdf' && \Nino\Modules\Sign::path( $appData, $id, 'shown' ) === $pdfPath && \Nino\Modules\Sign::path( $appData, $id, 'signature' ) === null );
check( '...the signed document is the document again with one page more - the record -, the owner\'s signature and the member\'s on the page of their fields, and the day of the signing and the title in the file',
	signPages( $signed ) === signPages( $pdf ) + 1 && signPages( $signed ) === 2 && substr_count( $signed, '/Subtype /Image' ) === 4
	&& str_contains( $signed, '/CreationDate (D:'. str_replace( '-', '', substr( (string) $after['signed'], 0, 10 ) ). '000000Z)' ) === true && str_contains( $signed, '/Title (Service & contract 2026)' ) === true );

$pageOf = static function( string $pdf, int $number ): string {

	$object = static fn( int $at ): string => preg_match( '/\n'. $at. " 0 obj\n(.*?)\nendobj\n/s", $pdf, $found ) === 1 ? $found[1] : '';

	preg_match_all( '/\n(\d+) 0 obj\n<< \/Type \/Page \/Parent[^\n]*\n/', $pdf, $pages );
	preg_match( '#/Contents (\d+) 0 R#', $object( (int) $pages[1][$number] ), $contents );

	$stream = $object( (int) $contents[1] );
	$start = (int) strpos( $stream, "stream\n" );

	return (string) gzuncompress( substr( $stream, $start + 7, (int) strrpos( $stream, "\nendstream" ) - $start - 7 ) );
};
check( '...the first page draws two pictures - the owner\'s and the member\'s - each in the box of its field, and the record page none', substr_count( $pageOf( $signed, 0 ), ' Do Q' ) === 2 && substr_count( $pageOf( $signed, 1 ), ' Do Q' ) === 0 );
check( 'the callback /sign/signed is told once, with the request as get() has it - the id, the status, the record - and no hash of the token', count( $told ) === 1 && $told[0][0] === '/sign/signed' && $told[0][1] === \Nino\Modules\Sign::get( $appData, $id )
	&& $told[0][1]['status'] === 'signed' && array_key_exists( 'token', $told[0][1] ) === false );
$sentMails = array_slice( $mails, $mailsBefore );
usort( $sentMails, static fn( array $a, array $b ): int => strcmp( (string) $a['to'], (string) $b['to'] ) );
check( 'two mails go out for the signing, each queued - the outbox sends them in an order of its own, told apart here by the address: the member gets the link to the signed document on the site - by the id - in the language of the page, the owner the title, who signed and a link to the panel in the native one',
	count( $sentMails ) === 2 && $sentMails[0]['to'] === 'ada@example.org' && $sentMails[0]['subject'] === 'Dein unterschriebenes Dokument' && str_contains( $sentMails[0]['body'], 'href="https://example.org/club/.sign?r='. $id. '"' ) === true
	&& str_contains( $sentMails[0]['body'], 'Service &amp; contract 2026' ) === true && $sentMails[1]['to'] === 'owner@example.org' && $sentMails[1]['subject'] === 'Ein Mitglied hat ein Dokument unterschrieben'
	&& str_contains( $sentMails[1]['body'], '<strong>Ada Lovelace</strong> hat unterschrieben' ) === true && str_contains( $sentMails[1]['body'], 'href="https://example.org/club/_admin#sign"' ) === true
	&& str_contains( $sentMails[1]['body'], $token ) === false && str_contains( $sentMails[0]['body'], $token ) === false && str_contains( $sentMails[1]['body'], '127.0.0.1' ) === false );

if( signNoPdftotext( 'the record page' ) === false ) {
	$record = preg_replace( '/\s+/', ' ', signText( $signed, '-f 2 -l 2' ) );
	$first = preg_replace( '/\s+/', ' ', signText( $signed, '-f 1 -l 1' ) );
	check( 'the record page says what the plan asks of it: who (the name and the address of the account), when, from where, which document by its hash, how, the sentence agreed to - and the first page is the document',
		str_contains( $record, 'Beleg der Unterzeichnung' ) === true && str_contains( $record, 'Eine einfache elektronische Signatur' ) === true && str_contains( $record, 'Service & contract 2026' ) === true && str_contains( $record, $id ) === true && str_contains( $record, 'Ada Lovelace (ada@example.org)' ) === true
		&& preg_match( '/Zeit \(UTC\) (\d{4}-\d{2}-\d{2}) \d{2}:\d{2}:\d{2} UTC/', $record, $time ) === 1 && in_array( $time[1], $signedDay, true ) === true && str_contains( $record, 'Adresse 127.0.0.1' ) === true
		&& str_contains( $record, 'Browser Mozilla/5.0 (Test) Browser/1.0' ) === true && str_contains( $record, substr( $shownHash, 0, 32 ) ) === true && str_contains( $record, substr( $shownHash, 32 ) ) === true
		&& str_contains( $record, 'Auf der Seite gezeichnet' ) === true && str_contains( $record, $sentence ) === true && str_contains( $first, 'Between the club and Ada Lovelace' ) === true && str_contains( $first, 'Beleg' ) === false );
	check( '...and the footer of the layout counts the record page: page 1 of 2 and page 2 of 2', str_contains( $first, 'Page 1 / 2' ) === true && str_contains( $record, 'Page 2 / 2' ) === true );
}

// A second signing of a signed request: the page with its state, nothing rewritten
$signedMtime = filemtime( (string) $signedPath );
$indexNow = signIndex( $appData );
$filesNow = [ hash_file( 'sha256', (string) $signedPath ), hash_file( 'sha256', $pdfPath ), hash_file( 'sha256', \Nino\Filesystem::path( $appData, '/data/sign/'. $id. '/signature.png' ) ) ];
$toldBefore = count( $told );
$mailsBefore = count( $mails );
[ $status, $location, $html ] = $answer( $post( [ 'action' => 'sign', 'consent' => '1', 'image' => signUrl( signPng( 300, 100 ) ) ] ) );
[ $declineStatus, , $declineHtml ] = $answer( $post( [ 'action' => 'decline' ] ) );
check( 'a second post of a signed request is the page with its state signed (200) and the link to the signed document: nothing is rewritten - the index, the three files and their hashes are as they were - and nobody is told or mailed',
	$status === 200 && $location === '' && $flash( $html ) === 'signed' && str_contains( $html, 'kind=signed' ) === true && signIndex( $appData ) === $indexNow && filemtime( (string) $signedPath ) === $signedMtime
	&& $filesNow === [ hash_file( 'sha256', (string) $signedPath ), hash_file( 'sha256', $pdfPath ), hash_file( 'sha256', \Nino\Filesystem::path( $appData, '/data/sign/'. $id. '/signature.png' ) ) ]
	&& count( $told ) === $toldBefore && count( $mails ) === $mailsBefore && $declineStatus === 200 && $flash( $declineHtml ) === 'signed' && signIndex( $appData )[$id]['status'] === 'signed' );

// What the link shows now, and who gets the signed document
$afterGet = signServe( $appData, '/.sign?t='. $token );
$afterForm = \Nino\Html::renderHtml( $appData, '[sign-form]' );
check( 'the link of the mail shows the request as signed: the state, the title and the link to the signed document - by the token - and no form', $afterGet['/nino/http/response']['statusCode'] === 200 && str_contains( $afterForm, 'data-sign-state="signed"' ) === true
	&& signAttr( signFind( $afterForm, '//p[@class="sign-form-link"]/a' ), 'href' ) === '/.sign/pdf?t='. $token. '&kind=signed' && str_contains( $afterForm, '<form' ) === false && str_contains( $afterForm, 'Service &amp; contract 2026' ) === true );
$both = [ 'the token, shown' => signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown' ), 'the token, signed' => signServe( $appData, '/.sign/pdf?t='. $token. '&kind=signed' ) ];
signAs( 'ada' );
$both['the member with the token, signed'] = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=signed' );
$memberPage = signServe( $appData, '/.sign?t='. $token )['/nino/http/response']['statusCode'];
$both['the member, signed'] = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=signed' );
$memberForm = signServe( $appData, '/.sign?r='. $id );
$memberLink = signFind( \Nino\Html::renderHtml( $appData, '[sign-form]' ), '//p[@class="sign-form-link"]/a' );
signAs( 'bob' );
$both['another member, signed'] = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=signed' );
$both['another member with the token, signed'] = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=signed' );
$both['another member with the token, shown'] = signServe( $appData, '/.sign/pdf?t='. $token. '&kind=shown' );
$strangerPage = signServe( $appData, '/.sign?t='. $token )['/nino/http/response']['statusCode'];
signWorkbench( 'admin' );
$both['the owner, signed'] = signServe( $appData, '/.sign/pdf?r='. $id. '&kind=signed' );
signVisitor();
$statuses = array_map( static fn( array $each ): int => $each['/nino/http/response']['statusCode'], $both );
check( 'the signed document is the holder\'s, the member\'s and the owner\'s: signed.pdf is the file the kernel is told to stream, under the cleaned title with -signed, and shown.pdf is still there to be had; another member gets the 404',
	$statuses === [ 'the token, shown' => 200, 'the token, signed' => 200, 'the member with the token, signed' => 200, 'the member, signed' => 200, 'another member, signed' => 404, 'another member with the token, signed' => 404, 'another member with the token, shown' => 404, 'the owner, signed' => 200 ] && $strangerPage === 404 && $memberPage === 200 && $both['the token, signed']['/nino/http/response']['file'] === $signedPath
	&& $both['the token, shown']['/nino/http/response']['file'] === $pdfPath && $both['the token, signed']['/nino/http/response']['header']['Content-Disposition'] === 'inline; filename="Service _ contract 2026-signed.pdf"; filename*=UTF-8\'\'Service%20%26%20contract%202026-signed.pdf'
	&& $both['the owner, signed']['/nino/http/response']['file'] === $signedPath && $memberLink !== [] && $memberLink[0]->getAttribute( 'href' ) === '/.sign/pdf?r='. $id. '&kind=signed' );

// The page the redirect lands on: with no key it says the state, and gives the link to the member it is of
$landing = [];
$landed = static function( string $query ) use ( &$appData ): array {
	$request = signServe( $appData, '/.sign?'. $query );
	return [ $request['/nino/http/response']['statusCode'], \Nino\Html::renderHtml( $appData, '[sign-form]' ) ];
};
$landing['visitor'] = $landed( 'sign='. $id. '&sign-state=signed' );
signAs( 'ada' );
$landing['the member'] = $landed( 'sign='. $id. '&sign-state=signed' );
$landing['the member, an id that is none'] = $landed( 'sign=nonsense&sign-state=signed' );
signAs( 'bob' );
$landing['another member'] = $landed( 'sign='. $id. '&sign-state=signed' );
signVisitor();
$landing['declined'] = $landed( 'sign='. $id. '&sign-state=declined' );
$landing['a state that is none'] = $landed( 'sign='. $id. '&sign-state=consent' );
check( 'with no key the page says the state ?sign-state= names - signed or declined - and nothing of any request: the same for a visitor and for another member; only to the member it is of it gives the link to the signed document, and a state that is none is invalid',
	$landing['visitor'][0] === 200 && str_contains( $landing['visitor'][1], 'data-sign-state="signed"' ) === true && str_contains( $landing['visitor'][1], 'kind=signed' ) === false && $landing['visitor'] === $landing['another member']
	&& str_contains( $landing['the member'][1], '/.sign/pdf?r='. $id. '&amp;kind=signed' ) === true && str_contains( $landing['the member'][1], 'Service &amp; contract 2026' ) === true && str_contains( $landing['the member, an id that is none'][1], 'kind=signed' ) === false
	&& str_contains( $landing['declined'][1], 'data-sign-state="declined"' ) === true && $landing['a state that is none'][0] === 404 );
echo "\n";

// --- Signing: a typed name, and the picture made of it --------------------------------------------------------------

$ask = static function( string $template, array $data, array $account, array $options = [] ) use ( &$appData, &$mails ): array {

	signFresh( $appData );
	$made = \Nino\Modules\Sign::request( $appData, $template, $data, $account['id'], $options );
	preg_match( '~\.sign\?t=([A-Za-z0-9_-]{43})~', (string) ( end( $mails )['body'] ?? '' ), $found );

	return [ $made, $found[1] ?? '' ];
};
$stateOf = static fn( string $of ): string => signIndex( $GLOBALS['appData'] )[$of]['status'] ?? '';

// The language of an account, where a module knows one: the mail that confirms its signing is written in it, the owner's in the native language
\Nino\Callbacks::registerCallback( $appData, '/members/language', static function( array &$appData, array &$ask ) use ( $bob ): void { $ask['locale'] = $ask['account'] === $bob['id'] ? 'en_US' : ''; } );
[ $worded, $wordedBobToken ] = $ask( 'two-fields', [], $bob, [ 'title' => 'Language form' ] );
signServe( $appData, '/.sign?t='. $wordedBobToken );
$mailsBefore = count( $mails );
signServe( $appData, '/.sign', 'POST', [ 't' => $wordedBobToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Bob' ] );
$confirmMails = array_slice( $mails, $mailsBefore );
// The outbox delivers the mails in the order it likes: the member's and the owner's are told apart by their addresses
usort( $confirmMails, static fn( array $a, array $b ): int => strcmp( (string) $a['to'], (string) $b['to'] ) );
unset( $appData['./nino/callbacks']['/members/language'] );
check( 'the mail that confirms a signing is written in the language of the account where a module names one - the owner\'s mail is not', count( $confirmMails ) === 2 && $confirmMails[0]['to'] === $bob['email']
	&& $confirmMails[0]['subject'] === (string) \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/sign/mail/signed-subject]]'] && $confirmMails[0]['subject'] !== (string) \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/sign/mail/signed-subject]]']
	&& $confirmMails[1]['subject'] === (string) \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/sign/mail/owner-signed-subject]]'] && $appData['./nino/locales/current'] === 'de_DE' );

\Nino\Modules\Sign::remove( $appData, $worded['id'] );
[ $typed, $typedToken ] = $ask( 'two-fields', [], $bob, [ 'title' => 'Privacy form' ] );
$typedId = $typed['id'];
signServe( $appData, '/.sign?t='. $typedToken );
$mailsBefore = count( $mails );
$told = [];
[ $status, $location, ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $typedToken, 'action' => 'sign', 'consent' => 'on', 'name' => "  Zoë\tMüller  ", 'return' => '//evil.example' ] ) );
$typedStored = signIndex( $appData )[$typedId];
$typedSigned = (string) file_get_contents( (string) \Nino\Modules\Sign::path( $appData, $typedId, 'signed' ) );
$typedPng = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/sign/'. $typedId. '/signature.png' ) );
$typedSize = \Nino\Images::size( $typedPng );
check( 'a typed name signs when nothing is drawn: the redirect goes to the signing page when the return page is none, the status is signed and the method typed, and the box may be ticked as a browser ticks it ("on")',
	$status === 303 && $location === '/.sign?sign='. $typedId. '&sign-state=signed' && $typedStored['status'] === 'signed' && $typedStored['method'] === 'typed' && $typedStored['hashSigned'] === hash( 'sha256', $typedSigned ) && count( $told ) === 1 );
check( '...the picture is the name drawn with the built-in font of GD, three times as large, on a transparent ground: a PNG as wide as ten letters of nine pixels three times over and as high as one of fifteen, and it is the same picture every time',
	$typedSize !== false && $typedSize['type'] === IMAGETYPE_PNG && $typedSize['width'] === 270 && $typedSize['height'] === 45 && $typedPng === \Nino\Modules\Sign\Signature::typed( 'Zoë Müller' ) && $typedPng !== \Nino\Modules\Sign\Signature::typed( 'Zoe Muller' ) );
$typedImage = imagecreatefromstring( $typedPng );
$inked = 0;
for( $y = 0; $y < 45; $y++ )
	for( $x = 0; $x < 270; $x++ )
		if( ( ( imagecolorat( $typedImage, $x, $y ) >> 24 ) & 0x7F ) < 127 )
			$inked++;
check( '...most of it is ground - the corners are fully transparent - and some of it is ink', ( ( imagecolorat( $typedImage, 0, 0 ) >> 24 ) & 0x7F ) === 127 && ( ( imagecolorat( $typedImage, 269, 44 ) >> 24 ) & 0x7F ) === 127 && $inked > 100 && $inked < 270 * 45 / 2 );
check( '...it is drawn on every field of the member the template has: two fields, two pictures on the first page, and the record on the second - which names the account by its address where it has no name',
	signPages( $typedSigned ) === 2 && substr_count( $pageOf( $typedSigned, 0 ), ' Do Q' ) === 2 && count( array_slice( $mails, $mailsBefore ) ) === 2
	&& ( signNoPdftotext( 'pin 1' ) || str_contains( preg_replace( '/\s+/', ' ', signText( $typedSigned, '-f 2 -l 2' ) ), 'Unterschrieben von bob@example.org (bob@example.org)' ) === true ) );
check( 'the typed name is cleaned before it is drawn: control characters and runs of blanks are one space, the letters the font has stay (the umlauts), a letter it lacks is a question mark, and a name is 60 characters at most',
	\Nino\Modules\Sign\Signature::clean( "  Zoë\t\tMüller\x07\n  " ) === 'Zoë Müller' && \Nino\Modules\Sign\Signature::clean( "\x01\x02" ) === '' && \Nino\Modules\Sign\Signature::clean( "a\xFFb" ) === "a\u{FFFD}b"
	&& \Nino\Images::size( (string) \Nino\Modules\Sign\Signature::typed( 'Łukasz Kowalski' ) )['width'] === 15 * 27 && \Nino\Modules\Sign\Signature::typed( '' ) === null && \Nino\Modules\Sign\Signature::typed( str_repeat( 'x', 61 ) ) === null
	&& \Nino\Images::size( (string) \Nino\Modules\Sign\Signature::typed( str_repeat( 'x', 60 ) ) )['width'] === 60 * 27 );
// The picture a browser posts is never kept as it came: it is a PNG of the measure the page makes, and what is kept is what GD wrote of its pixels
$chunk = static fn( string $type, string $data ): string => pack( 'N', strlen( $data ) ). $type. $data. pack( 'N', crc32( $type. $data ) );
$withChunk = static fn( string $png, string $type, string $data ): string => substr( $png, 0, 33 ). $chunk( $type, $data ). substr( $png, 33 );
$kept = \Nino\Modules\Sign\Signature::drawn( $drawn );
$keptImage = $kept === null ? false : imagecreatefromstring( $kept );
$sameInk = $keptImage !== false && ( ( imagecolorat( $keptImage, 0, 0 ) >> 24 ) & 0x7F ) === 127 && ( imagecolorat( $keptImage, 115, 80 ) & 0xFFFFFF ) === 0x10162f && ( ( imagecolorat( $keptImage, 115, 80 ) >> 24 ) & 0x7F ) < 127;
check( 'a picture the pad drew is decoded and written anew: the PNG of the page comes back as a PNG of 600 by 200 with the strokes where they were, the corners transparent', $kept !== null && str_starts_with( $kept, "\x89PNG\r\n\x1a\n" ) === true && \Nino\Images::size( $kept )['width'] === 600 && \Nino\Images::size( $kept )['height'] === 200 && $sameInk === true );
$tainted = signPng() . '<?php system($_GET[x]); ?>TRAILING-JUNK';
$texted = $withChunk( signPng(), 'tEXt', "Comment\0<script>alert(1)</script>" );
$keptTainted = \Nino\Modules\Sign\Signature::drawn( signUrl( $tainted ) );
$keptTexted = \Nino\Modules\Sign\Signature::drawn( signUrl( $texted ) );
check( '...what a PNG carries beside its pixels is not kept: a text chunk and bytes behind the image are gone from what is stored, and the pixels are the same', $keptTainted !== null && $keptTexted !== null && str_contains( $keptTainted, 'TRAILING-JUNK' ) === false && str_contains( $keptTainted, '<?php' ) === false
	&& str_contains( $keptTexted, 'tEXt' ) === false && str_contains( $keptTexted, '<script>' ) === false && $keptTainted === $kept && $keptTexted === $kept );
$gif = ( static function(): string { $image = imagecreatetruecolor( 600, 200 ); imagefilledrectangle( $image, 10, 10, 200, 100, 0x10162f ); ob_start(); imagegif( $image ); return (string) ob_get_clean(); } )();
$jpeg = ( static function(): string { $image = imagecreatetruecolor( 600, 200 ); imagefilledrectangle( $image, 10, 10, 200, 100, 0x10162f ); ob_start(); imagejpeg( $image ); return (string) ob_get_clean(); } )();
$bomb = substr( $withChunk( signPng(), 'tEXt', 'x' ), 0, 16 ). pack( 'N', 60000 ). pack( 'N', 60000 ). substr( $withChunk( signPng(), 'tEXt', 'x' ), 24 );
$refused = [ 'a GIF under the name of a PNG' => $gif, 'a JPEG under the name of a PNG' => $jpeg, 'no picture' => '', 'a header and nothing more' => substr( signPng(), 0, 40 ), 'a canvas nobody drew on' => signPng( 600, 200, false ),
	'a picture of one pixel' => signPng( 1, 1 ), 'a picture below the measure' => signPng( 100, 30 ), 'a picture above the measure' => signPng( 2000, 1000 ), 'a picture higher than wide' => signPng( 200, 600 ), 'a strip' => signPng( 1200, 100 ),
	'a header that says 60000 by 60000' => $bomb, 'bytes of 600 KB' => signPng() . str_repeat( 'x', 600000 ) ];
$answers = array_map( static fn( string $bytes ): ?string => \Nino\Modules\Sign\Signature::drawn( $bytes === '' ? '' : signUrl( $bytes ) ), $refused );
check( '...a picture that is not the pad\'s is refused: another type, nothing, a canvas nobody drew on, a measure the page does not make - checked in the header before anything is decoded - and bytes over 512 KB', array_filter( $answers, static fn( ?string $answer ): bool => $answer !== null ) === [] && array_keys( $answers ) === array_keys( $refused ) && ninoWarnings() === [] );
check( '...and the edges of the measure are in: 120 by 40 and 1200 by 600', \Nino\Modules\Sign\Signature::drawn( signUrl( signPng( 120, 40 ) ) ) !== null && \Nino\Modules\Sign\Signature::drawn( signUrl( signPng( 1200, 600 ) ) ) !== null && \Nino\Modules\Sign\Signature::drawn( signUrl( signPng( 600, 600 ) ) ) !== null );
check( '...a data URL that is none is refused: tampered, empty, with a blank in front, not base64, with another type', \Nino\Modules\Sign\Signature::drawn( substr( $drawn, 0, -12 ). '!!!' ) === null && \Nino\Modules\Sign\Signature::drawn( '' ) === null && \Nino\Modules\Sign\Signature::drawn( 'data:image/png;base64,' ) === null
	&& \Nino\Modules\Sign\Signature::drawn( ' '. $drawn ) === null && \Nino\Modules\Sign\Signature::drawn( 'data:image/png;base64,!!!' ) === null && \Nino\Modules\Sign\Signature::drawn( 'data:image/jpeg;base64,'. base64_encode( signPng() ) ) === null && \Nino\Modules\Sign\Signature::drawn( 'data:image/png;base64,'. str_repeat( 'A', 800000 ) ) === null );

// --- What a person typed is text ------------------------------------------------------------------------------------------------

// A name, a title and a browser that hold markup, a fill and a shortcode: the record page and the mails write them as they were
// typed, and the browser is cut to what the record keeps of it
$eve = \Nino\Modules\Members::add( $appData, 'eve@example.org', 'Eve <b>X</b> [[/project/company/general/name]] [sign-list]', 'staff' );
$eveName = (string) ( \Nino\Modules\Members::account( $appData, $eve['id'] )['name'] ?? '' );
[ $texty, $textyToken ] = $ask( 'two-fields', [], $eve, [ 'title' => 'Title <i>y</i> [[/project/company/general/name]]' ] );
$textyMails = count( $mails );
signServe( $appData, '/.sign?t='. $textyToken );
signServe( $appData, '/.sign', 'POST', [ 't' => $textyToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Eve' ], true, [ 'HTTP_USER_AGENT' => str_repeat( 'A', 300 ). " <i>z</i>\x07" ] );
$textySigned = (string) file_get_contents( (string) \Nino\Modules\Sign::path( $appData, $texty['id'], 'signed' ) );
$textyRecord = signIndex( $appData )[$texty['id']]['record'] ?? [ 'agent' => '' ];
$textyMail = array_slice( $mails, $textyMails );
usort( $textyMail, static fn( array $a, array $b ): int => strcmp( (string) $a['to'], (string) $b['to'] ) );
check( 'what a person typed is text: a name, a title and a browser with markup, a fill and a shortcode in them stand on the record page as they were typed - not run, not made bold - and in the mails; the browser is cut to 255 characters and loses its control characters',
	str_contains( $eveName, '[[/project/company/general/name]]' ) === true && mb_strlen( $textyRecord['agent'] ) === 255 && str_contains( $textyRecord['agent'], "\x07" ) === false
	&& ( signNoPdftotext( 'pin 2' ) || ( str_contains( preg_replace( '/\s+/', ' ', signText( $textySigned, '-f 2 -l 2' ) ), $eveName. ' (eve@example.org)' ) === true
		&& str_contains( preg_replace( '/\s+/', ' ', signText( $textySigned, '-f 2 -l 2' ) ), 'Title <i>y</i> [[/project/company/general/name]]' ) === true ) )
	&& count( $textyMail ) === 2 && str_contains( (string) $textyMail[0]['body'], 'Title &lt;i&gt;y&lt;/i&gt; [[/project/company/general/name]]' ) === true
	&& str_contains( (string) $textyMail[0]['body'], 'Hallo Eve &lt;b&gt;X&lt;/b&gt; [[/project/company/general/name]] [sign-list],' ) === true && str_contains( (string) $textyMail[0]['body'], '<i>y</i>' ) === false );
$told = [];
echo "\n";

// --- Two languages: the site's own and the one a person reads it in -------------------------------------------------------------

// The document and the mail of a request are the site's, in its native language, whoever asks and in whichever language the
// site is read; what the member is shown and what is recorded of the signing are in the language the member reads it in
\Nino\Html::addFills( $appData, [ '[[/test/word]]' => 'Wort' ], 'de_DE' );
\Nino\Html::addFills( $appData, [ '[[/test/word]]' => 'word' ], 'en_US' );
\Nino\Modules\Pdf\Store::saveTemplate( $appData, [ 'key' => 'native-word', 'isNew' => true, 'name' => 'Native word', 'tag' => 'contract', 'blocks' => [
	[ 'type' => 'html', 'html' => '<p>Word: [[/test/word]]</p>' ], $field( 'buyer', 'member', 20 ),
] ] );

$appData['./nino/locales/current'] = 'en_US';
$mailsBefore = count( $mails );
[ $worded, $wordedToken ] = $ask( 'native-word', [], $ada, [ 'title' => 'Wording' ] );
$requestMail = $mails[$mailsBefore] ?? [];
$wordedShown = (string) file_get_contents( (string) \Nino\Modules\Sign::path( $appData, $worded['id'], 'shown' ) );
$told = [];
signServe( $appData, '/.sign?t='. $wordedToken );
$formIn = \Nino\Html::renderHtml( $appData, '[sign-form]' );
$mailsBefore = count( $mails );
[ $status, , ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $wordedToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ] ) );
$wordedSigned = (string) file_get_contents( (string) \Nino\Modules\Sign::path( $appData, $worded['id'], 'signed' ) );
$wordedMails = array_slice( $mails, $mailsBefore );
$appData['./nino/locales/current'] = 'de_DE';

check( 'a request is made in the site\'s native language whatever the language it is read in: the mail to the member and the document are German while English is the language in use',
	$requestMail['subject'] === 'Ein Dokument wartet auf Deine Unterschrift' && str_contains( (string) $requestMail['body'], 'Hallo Ada Lovelace,' ) === true && str_contains( $formIn, 'I have read the document' ) === true
	&& ( signNoPdftotext( 'pin 3' ) || ( str_contains( signText( $wordedShown ), 'Word: Wort' ) === true && str_contains( signText( $wordedShown ), 'Word: word' ) === false ) ) );
check( '...and the member signs it in the language of the page without the document being "changed": the same document is made again in the native language - signed, German - while the member\'s mail is in the language of the page and the owner\'s in the native one',
	$status === 303 && signIndex( $appData )[$worded['id']]['status'] === 'signed' && count( $wordedMails ) === 2 && $wordedMails[0]['to'] === 'ada@example.org' && $wordedMails[0]['subject'] === 'Your signed document'
	&& $wordedMails[1]['to'] === 'owner@example.org' && $wordedMails[1]['subject'] === 'Ein Mitglied hat ein Dokument unterschrieben'
	&& ( signNoPdftotext( 'pin 4' ) || ( str_contains( signText( $wordedSigned, '-f 1 -l 1' ), 'Word: Wort' ) === true && str_contains( signText( $wordedSigned, '-f 1 -l 1' ), 'Word: word' ) === false ) ) );
check( '...the record of the signing is in the language the member signed in, with the sentence the page showed: English words, and the sentence of the English page',
	signNoPdftotext( 'pin 5' ) || ( str_contains( signText( $wordedSigned, '-f 2 -l 2' ), 'Record of the signing' ) === true && str_contains( signText( $wordedSigned, '-f 2 -l 2' ), 'Beleg der Unterzeichnung' ) === false
		&& str_contains( preg_replace( '/\s+/', ' ', signText( $wordedSigned, '-f 2 -l 2' ) ), 'I have read the document' ) === true ) );
\Nino\Modules\Pdf\Store::deleteTemplate( $appData, 'native-word' );
$told = [];
echo "\n";

// --- The document that was shown is the one that is signed ----------------------------------------------------------------

$saveTemplate = static fn( string $text ): array => \Nino\Modules\Pdf\Store::saveTemplate( $GLOBALS['appData'], [ 'key' => 'contract-one', 'isNew' => false, 'name' => 'Service contract', 'tag' => 'contract', 'blocks' => [
	[ 'type' => 'html', 'html' => $text ], [ 'type' => 'input', 'name' => 'note', 'label' => 'A note', 'multiline' => true, 'required' => true ],
	$GLOBALS['field']( 'seller', 'owner', 20 ), $GLOBALS['field']( 'buyer', 'member', 110 ) ] ] );
$plainText = '<h1>Service contract</h1><p>Between the club and [[client]].</p>';
$layoutNow = \Nino\Filesystem::getFileContent( $appData, '/data/pdf/layout.php', [] );
$spoilt = [];
$mailsBefore = count( $mails );
$told = [];

/*	One request of its own for each way a document can stop being the one that was shown: it is asked, the document is spoiled, the member signs, and the
	document is mended again. What the page says, what the index holds and what was mailed is kept for the pins	*/
$tryToSign = static function( string $why, callable $spoil, callable $mend, array $options = [] ) use ( &$spoilt, &$mails, $ask, $post, $answer, $drawn, $ada, $input, $flash ): void {

	[ $made, $changeToken ] = $ask( 'contract-one', $input, $ada, $options );
	$spoil( $made['id'] );
	signFresh( $GLOBALS['appData'] );
	$before = count( $mails );
	[ $status, , $html ] = $answer( signServe( $GLOBALS['appData'], '/.sign', 'POST', [ 't' => $changeToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn, '_csrf' => \Nino\Csrf::getToken( $GLOBALS['appData'] ) ], true, [ 'HTTP_USER_AGENT' => $GLOBALS['agent'] ] ) );
	$mend( $made['id'] );
	$entry = signIndex( $GLOBALS['appData'] )[$made['id']];
	$spoilt[$why] = [ 'id' => $made['id'], 'token' => $changeToken, 'status' => $status, 'flash' => $flash( $html ), 'stored' => $entry['status'], 'time' => $entry['changed'],
		'files' => array_map( 'basename', glob( \Nino\Filesystem::path( $GLOBALS['appData'], '/data/sign/'. $made['id'] ). '/*' ) ?: [] ), 'mails' => array_slice( $mails, $before ),
		'form' => count( signFind( $html, '//form' ) ), 'buttons' => count( signFind( $html, '//button' ) ), 'html' => $html, 'told' => $GLOBALS['told'] ];
};
$tryToSign( 'a template the owner changed', fn() => $saveTemplate( '<h1>Service contract</h1><p>Between the club and [[client]]. Changed.</p>' ), fn() => $saveTemplate( $plainText ) );
$tryToSign( 'a layout the owner changed', fn() => \Nino\Filesystem::putFileContent( $GLOBALS['appData'], '/data/pdf/layout.php', [ 'footer' => '<p>Another footer</p>' ] + $layoutNow ), fn() => \Nino\Filesystem::putFileContent( $GLOBALS['appData'], '/data/pdf/layout.php', $layoutNow ) );
$shownBytes = [];
$tryToSign( 'a shown.pdf that is not what it was', function( string $id ) use ( &$shownBytes ): void {
	$file = (string) \Nino\Modules\Sign::path( $GLOBALS['appData'], $id, 'shown' );
	$shownBytes[$id] = (string) file_get_contents( $file );
	file_put_contents( $file, $shownBytes[$id]. ' ' );
}, fn( string $id ) => file_put_contents( (string) \Nino\Modules\Sign::path( $GLOBALS['appData'], $id, 'shown' ), $shownBytes[$id] ) );
$firstChanged = $spoilt['a template the owner changed'];

check( 'a document that is not the one that was shown is not signed: the template changed, the layout changed or shown.pdf changed on the disk is the state changed (409), written with its time - nothing is signed, no file is added, nobody is told',
	array_map( static fn( array $one ): array => [ $one['status'], $one['flash'], $one['stored'], $one['files'], preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) $one['time'] ) === 1, $one['told'] ], $spoilt )
		=== array_fill_keys( array_keys( $spoilt ), [ 409, 'changed', 'changed', [ 'shown.pdf' ], true, [] ] ) && count( $spoilt ) === 3 );
check( '...the page says it and has no form: no frame, no box, no pad, no button - Decline is not offered either, the document is not the one that was shown - and the title is shown',
	$firstChanged['form'] === 0 && $firstChanged['buttons'] === 0 && signFind( $firstChanged['html'], '//iframe' ) === [] && signFind( $firstChanged['html'], '//p[@class="sign-form-intro"]' ) === [] && str_contains( $firstChanged['html'], 'sign-form-title' ) === true
	&& signContent( signFind( $firstChanged['html'], '//p[@class="sign-form-state"]' ) ) === \Nino\Html::renderHtml( $appData, '[[/feature/sign/state/changed]]' ) );
check( '...the owner is mailed, once, in the native language, with the title and who - the member gets no mail - and nothing in it is the address of the client or the token',
	array_map( static fn( array $one ): int => count( $one['mails'] ), $spoilt ) === array_fill_keys( array_keys( $spoilt ), 1 ) && $firstChanged['mails'][0]['to'] === 'owner@example.org'
	&& $firstChanged['mails'][0]['subject'] === 'Ein Dokument hat sich geändert, bevor ein Mitglied es unterschreiben konnte' && str_contains( $firstChanged['mails'][0]['body'], '<strong>Ada Lovelace</strong> wollte unterschreiben' ) === true
	&& str_contains( $firstChanged['mails'][0]['body'], 'Ein Dokument hat sich geändert</h1>' ) === true && str_contains( $firstChanged['mails'][0]['body'], $firstChanged['token'] ) === false && str_contains( $firstChanged['mails'][0]['body'], '127.0.0.1' ) === false );

$mailsHeld = count( $mails );
signServe( $appData, '/.sign?t='. $firstChanged['token'] );
$again = \Nino\Html::renderHtml( $appData, '[sign-form]' );
[ $status, , $html ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $firstChanged['token'], 'action' => 'sign', 'consent' => '1', 'image' => $drawn, '_csrf' => \Nino\Csrf::getToken( $appData ) ], true, [ 'HTTP_USER_AGENT' => $agent ] ) );
check( '...a link that is opened again, or a post again, shows the same state with no form (200 and nothing written): a changed request is over, and the files, the index and the mails stay as they were',
	$flash( $again ) === 'changed' && signFind( $again, '//form' ) === [] && $status === 200 && $flash( $html ) === 'changed' && signFind( $html, '//form' ) === [] && signIndex( $appData )[$firstChanged['id']]['status'] === 'changed'
	&& array_map( 'basename', glob( \Nino\Filesystem::path( $appData, '/data/sign/'. $firstChanged['id'] ). '/*' ) ?: [] ) === [ 'shown.pdf' ] && count( $mails ) === $mailsHeld && $told === [] );

$remindMails = count( $mails );
check( '...it is not reminded, and not cancelled: false, and no mail', \Nino\Modules\Sign::remind( $appData, $firstChanged['id'] ) === false && \Nino\Modules\Sign::cancel( $appData, $firstChanged['id'] ) === false && count( $mails ) === $remindMails
	&& \Nino\Modules\Sign::get( $appData, $firstChanged['id'] )['status'] === 'changed' );

// A mail that is not delivered leaves a warning that names the request - and the request is changed all the same
$delivery = false;
ninoWarnings();
$tryToSign( 'a template changed, and no mail taken', fn() => $saveTemplate( '<h1>Service contract</h1><p>Between the club and [[client]]. Changed again.</p>' ), fn() => $saveTemplate( $plainText ) );
$delivery = true;
$warned = implode( ' ', ninoWarnings() );
check( '...a mail of the owner that is not delivered is a warning that names the request by its id and no address - and the request is changed all the same',
	$spoilt['a template changed, and no mail taken']['stored'] === 'changed' && $spoilt['a template changed, and no mail taken']['flash'] === 'changed' && str_contains( $warned, $spoilt['a template changed, and no mail taken']['id'] ) === true
	&& str_contains( $warned, 'owner@example.org' ) === false && str_contains( $warned, 'ada@example.org' ) === false );

// ...with the setting notifyOwner off nobody is mailed
\Nino\Features::saveSettings( $appData, 'sign', [ 'notifyOwner' => '0' ] );
$tryToSign( 'a template changed, the owner not told', fn() => $saveTemplate( '<h1>Service contract</h1><p>Between the club and [[client]]. Changed once more.</p>' ), fn() => $saveTemplate( $plainText ) );
\Nino\Features::saveSettings( $appData, 'sign', [ 'notifyOwner' => '1' ] );
check( '...with the setting notifyOwner off the owner is not mailed, the request is changed all the same', $spoilt['a template changed, the owner not told']['mails'] === [] && $spoilt['a template changed, the owner not told']['stored'] === 'changed' );

// An update of the PDF feature that leaves the document as it was ends nothing: the file carries no version of it
[ $updated, $updatedToken ] = $ask( 'contract-one', $input, $ada );
\Nino\Features::all( $appData );
$appData['./nino/features/all']['pdf']['version'] = '9.9.9';
signServe( $appData, '/.sign?t='. $updatedToken );
$signedAfter = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $updatedToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn, '_csrf' => \Nino\Csrf::getToken( $appData ) ], true, [ 'HTTP_USER_AGENT' => $agent ] ) );
$bumped = \Nino\Features::get( $appData, 'pdf' )['version'];
unset( $appData['./nino/features/all'] );
check( 'a pending request is signed after the PDF feature was updated - the document made again is the one that was shown, its file names no version - and is not changed', $signedAfter[0] === 303 && ( signIndex( $appData )[$updated['id']]['status'] ?? '' ) === 'signed' && $bumped === '9.9.9' );
$changingId = $firstChanged['id'];
$mailsBefore = count( $mails );
$told = [];
echo "\n";
echo "\n";

// --- Declining, running out, the cap, the mails, the writes -------------------------------------------------------------

[ $declining, $decliningToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'To decline' ] );
$decliningId = $declining['id'];
signServe( $appData, '/.sign?t='. $decliningToken );
$mailsBefore = count( $mails );
$told = [];
[ $status, $location, ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $decliningToken, 'action' => 'decline' ] ) );
$declined = signIndex( $appData )[$decliningId];
$sent = array_slice( $mails, $mailsBefore );
check( 'declining needs no box ticked and no signature: the redirect says declined, the status turns to declined with the time, nothing else of the request changes, and no file is written',
	$status === 303 && $location === '/.sign?sign='. $decliningId. '&sign-state=declined' && $declined['status'] === 'declined' && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) $declined['declined'] ) === 1 && $declined['signed'] === null
	&& $declined['hashSigned'] === '' && $declined['record'] === [ 'ip' => '', 'agent' => '', 'consent' => '' ] && $files( $decliningId ) === [ 'shown.pdf' ] );
check( '...the callback /sign/declined is told with the request, and only the owner is mailed - in the native language, that the member declined; the member gets none', count( $told ) === 1 && $told[0][0] === '/sign/declined' && $told[0][1]['status'] === 'declined'
	&& count( $sent ) === 1 && $sent[0]['to'] === 'owner@example.org' && $sent[0]['subject'] === 'Ein Mitglied hat die Unterschrift eines Dokuments abgelehnt' && str_contains( $sent[0]['body'], '<strong>Ada Lovelace</strong> hat die Unterschrift abgelehnt' ) === true && str_contains( $sent[0]['body'], 'To decline' ) === true );
$afterDecline = signServe( $appData, '/.sign?t='. $decliningToken );
$stateHtml = \Nino\Html::renderHtml( $appData, '[sign-form]' );
$mailsBefore = count( $mails );
$told = [];
[ $againStatus, , $againHtml ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $decliningToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
check( 'a declined request shows its state on the page and cannot be signed after: the page again with the state declined, nothing is written, nobody is told', $afterDecline['/nino/http/response']['statusCode'] === 200 && str_contains( $stateHtml, 'data-sign-state="declined"' ) === true
	&& $againStatus === 200 && $flash( $againHtml ) === 'declined' && signIndex( $appData )[$decliningId] === $declined && $files( $decliningId ) === [ 'shown.pdf' ] && $told === [] && count( $mails ) === $mailsBefore );

// A request whose time is up: expired is computed from the time and never written
[ $running, $runningToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Running out' ] );
$runningId = $running['id'];
$changed = signIndex( $appData );
$changed[$runningId]['expires'] = time() - 5;
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
$mailsBefore = count( $mails );
$told = [];
[ $status, $location, $html ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $runningToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
check( 'a request whose time is up answers a post with the page and the state expired (200), writes nothing and tells nobody; the status in the file stays what it was, and the API says expired',
	$status === 200 && $location === '' && $flash( $html ) === 'expired' && signIndex( $appData )[$runningId]['status'] === 'requested' && $files( $runningId ) === [ 'shown.pdf' ] && $told === [] && count( $mails ) === $mailsBefore
	&& \Nino\Modules\Sign::get( $appData, $runningId )['status'] === 'expired' && signServe( $appData, '/.sign?t='. $runningToken )['/nino/http/response']['statusCode'] === 200 && str_contains( \Nino\Html::renderHtml( $appData, '[sign-form]' ), 'data-sign-state="expired"' ) === true );

// The cap: posts per visitor and hour, counted before anything is looked at
\Nino\Features::saveSettings( $appData, 'sign', [ 'attempts' => '3' ] );
[ $capped, $cappedToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Capped' ] );
$cappedId = $capped['id'];
$limits = [];
for( $try = 1; $try <= 5; $try++ ) {
	[ $status, , $html ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $cappedToken, 'action' => 'sign', 'consent' => '0' ] ) );
	$limits[] = $status. ' '. $flash( $html );
}
$_SERVER['REMOTE_ADDR'] = '10.9.9.9';
[ $otherStatus, , $otherHtml ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $cappedToken, 'action' => 'sign', 'consent' => '0' ] ) );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
[ $goodStatus, $goodLocation, ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $cappedToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
check( 'the setting attempts caps the posts of a visitor in an hour: the first three are answered, the next ones are the state limit (429) - even a good one, which signs nothing - and another visitor is not held back',
	$limits === [ '400 consent', '400 consent', '400 consent', '429 limit', '429 limit' ] && $otherStatus === 400 && $flash( $otherHtml ) === 'consent' && $goodStatus === 429 && $goodLocation === '' && $stateOf( $cappedId ) !== 'signed' );
check( '...the visitor is counted by a hash and for an hour: the address is not in the file of the counter, and the window of each count closes an hour from the first try', count( $counter() ) === 2 && array_filter( $counter(), static fn( array $entry ): bool => abs( (int) $entry['reset'] - ( time() + 3600 ) ) > 5 ) === [] && str_contains( implode( array_map( 'file_get_contents', glob( \Nino\Filesystem::path( $appData, '/data/.counters/sign-tries' ). '/*.php' ) ?: [] ) ), '127.0.0.1' ) === false
	&& str_contains( implode( array_map( 'file_get_contents', glob( \Nino\Filesystem::path( $appData, '/data/.counters/sign-tries' ). '/*.php' ) ?: [] ) ), '10.9.9.9' ) === false );
signFresh( $appData );
[ $afterReset, , ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $cappedToken, 'action' => 'decline' ] ) );
check( '...and a new hour is a new count', $afterReset === 303 && $stateOf( $cappedId ) === 'declined' );
\Nino\Features::saveSettings( $appData, 'sign', [ 'attempts' => '20' ] );

// The mails of a signing: the owner's can be switched off and has to have an address; a mail that is not delivered does not undo the signing
$mailing = [];
foreach( [ 'the owner is not told when the setting says so' => 'off', 'no contact address' => 'nocontact', 'a mail that is not delivered' => 'undelivered' ] as $label => $how ) {

	[ $each, $eachToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Mails' ] );
	$mailsBefore = count( $mails );
	ninoWarnings();

	if( $how === 'off' )
		\Nino\Features::saveSettings( $appData, 'sign', [ 'notifyOwner' => 'false' ] );
	else if( $how === 'nocontact' )
		\Nino\Html::addFills( $appData, [ '[[/project/company/contact/email]]' => '' ], '*' );
	else {
		// The outbox does not take the member's mail: the visitor has spent the try an hour that the cap of mails of the kernel gives
		$capBefore = $appData['/nino/mail/cap'] ?? null;
		$appData['/nino/mail/cap'] = 1;
		\Nino\Feature::count( $appData, 'ratelimit', '127.0.0.1', 1, 3600 );
	}

	[ $status, , ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $eachToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada Lovelace' ] ) );
	$mailing[$label] = [ $status, $stateOf( $each['id'] ), array_column( array_slice( $mails, $mailsBefore ), 'to' ), ninoWarnings() ];

	\Nino\Features::saveSettings( $appData, 'sign', [ 'notifyOwner' => 'true' ] );
	\Nino\Html::addFills( $appData, [ '[[/project/company/contact/email]]' => 'owner@example.org' ], '*' );

	if( $how === 'undelivered' )
		$capBefore === null ? ( $appData['/nino/mail/cap'] = \Nino\AppData::DEFAULTS['/nino/mail/cap'] ) : ( $appData['/nino/mail/cap'] = $capBefore );

	$mailing[$label][] = $each['id'];
}
check( 'with the setting notifyOwner off only the member is mailed', $mailing['the owner is not told when the setting says so'][0] === 303 && $mailing['the owner is not told when the setting says so'][1] === 'signed' && $mailing['the owner is not told when the setting says so'][2] === [ 'ada@example.org' ]
	&& $mailing['the owner is not told when the setting says so'][3] === [] );
check( '...with no contact address only the member is, and a warning says why without naming an address', $mailing['no contact address'][1] === 'signed' && $mailing['no contact address'][2] === [ 'ada@example.org' ] && count( $mailing['no contact address'][3] ) === 1
	&& str_contains( $mailing['no contact address'][3][0], 'no contact address' ) === true && str_contains( $mailing['no contact address'][3][0], '@' ) === false );
check( '...a mail that the outbox does not take is a warning that names the request by its id, says why and names no address - and the signing stands, and the owner is told all the same: the member\'s try an hour is spent, the owner\'s notice is the system\'s', $mailing['a mail that is not delivered'][1] === 'signed' && $mailing['a mail that is not delivered'][0] === 303 && count( $mailing['a mail that is not delivered'][3] ) === 1
	&& array_filter( $mailing['a mail that is not delivered'][3], static fn( string $warning ): bool => str_contains( $warning, $GLOBALS['mailing']['a mail that is not delivered'][4] ) === false || str_contains( $warning, '@' ) === true || str_contains( $warning, 'ratelimited' ) === false ) === []
	&& $mailing['a mail that is not delivered'][2] === [ 'owner@example.org' ] );

// A write that cannot be made at the signing is the state write (500): the request waits, and no file of the signing is left
[ $unwritable, $unwritableToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Unwritable' ] );
$unwritableId = $unwritable['id'];
$blocker = \Nino\Filesystem::path( $appData, '/data/sign/'. $unwritableId. '/signed.pdf' );
mkdir( $blocker );
$mailsBefore = count( $mails );
$told = [];
[ $status, $location, $html ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $unwritableToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
$leftOver = $files( $unwritableId );
rmdir( $blocker );
check( 'a signing that cannot be written is the state write (500): the request still waits, the picture that was written is taken away again, nobody is told or mailed', $status === 500 && $location === '' && $flash( $html ) === 'write' && $stateOf( $unwritableId ) === 'requested'
	&& $leftOver === [ 'shown.pdf', 'signed.pdf' ] && $told === [] && count( $mails ) === $mailsBefore );
[ $status, , ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $unwritableToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
check( '...and once it can be written the same request is signed', $status === 303 && $stateOf( $unwritableId ) === 'signed' && $files( $unwritableId ) === [ 'shown.pdf', 'signature.png', 'signed.pdf' ] );

// The key r: the signed-in member signs by the id, and nobody else can
[ $byKey, ] = $ask( 'two-fields', [], $ada, [ 'title' => 'By the id' ] );
signAs( 'bob' );
[ $strangerStatus, , $strangerHtml ] = $answer( signServe( $appData, '/.sign', 'POST', [ 'r' => $byKey['id'], 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
signAs( 'ada' );
[ $memberStatus, $memberLocation, ] = $answer( signServe( $appData, '/.sign', 'POST', [ 'r' => $byKey['id'], 'action' => 'sign', 'consent' => '1', 'image' => $drawn, 'return' => '/club/documents' ] ) );
signVisitor();
check( 'the member signs by the id when signed in: the redirect goes to the return page; another member and a visitor get the invalid page (404) and sign nothing', $strangerStatus === 404 && $flash( $strangerHtml ) === 'invalid' && $memberStatus === 303 && $memberLocation === '/club/documents?sign='. $byKey['id']. '&sign-state=signed'
	&& $stateOf( $byKey['id'] ) === 'signed' );

// An account that is switched off between the mail and the post
[ $gone, $goneToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Account off' ] );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => false ] );
[ $status, , $html ] = $answer( signServe( $appData, '/.sign', 'POST', [ 't' => $goneToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ] ) );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => true ] );
signResign( $appData, $ada, 'ada' );
check( 'an account that is switched off signs nothing: the page again, invalid (404)', $status === 404 && $flash( $html ) === 'invalid' && $stateOf( $gone['id'] ) === 'requested' && $files( $gone['id'] ) === [ 'shown.pdf' ] );
echo "\n";

// --- remind(), cancel() and remove() -------------------------------------------------------------------------------------

echo "Modules\\Sign - remind(), cancel() and remove()\n";

$tokenOf = static function( int $at ) use ( &$mails ): string {
	preg_match( '~\.sign\?t=([A-Za-z0-9_-]{43})~', (string) ( $mails[$at]['body'] ?? '' ), $found );
	return $found[1] ?? '';
};
[ $asked, $askedToken ] = $ask( 'contract-one', $input, $ada, [ 'title' => 'To remind' ] );
$askedId = $asked['id'];
signServe( $appData, '/.sign?t='. $askedToken );
$viewedAt = signIndex( $appData )[$askedId]['viewed'];
$was = signIndex( $appData )[$askedId];
$changed = signIndex( $appData );
$changed[$askedId]['expires'] = time() + 3600;
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
signFresh( $appData );
$mailsBefore = count( $mails );
$reminded = \Nino\Modules\Sign::remind( $appData, $askedId );
$newToken = $tokenOf( $mailsBefore );
$now = signIndex( $appData )[$askedId];
check( 'remind() mails the link again, in the native language, with a new token and the days of the setting from now - the status and the time of the viewing are as they were, and nothing else of the request changed',
	$reminded === true && count( $mails ) === $mailsBefore + 1 && $mails[$mailsBefore]['to'] === 'ada@example.org' && $mails[$mailsBefore]['subject'] === 'Ein Dokument wartet auf Deine Unterschrift' && preg_match( '/^[A-Za-z0-9_-]{43}$/D', $newToken ) === 1 && $newToken !== $askedToken
	&& $now['token'] === hash( 'sha256', $newToken ) && abs( $now['expires'] - ( time() + 14 * 86400 ) ) < 5 && $now['viewed'] === $viewedAt && $now['status'] === 'viewed' && array_diff_key( $now, [ 'token' => 1, 'expires' => 1 ] ) === array_diff_key( $was, [ 'token' => 1, 'expires' => 1 ] ) );
check( '...the old link is dead - the page, the PDF - and the new one opens', signServe( $appData, '/.sign?t='. $askedToken )['/nino/http/response']['statusCode'] === 404 && signServe( $appData, '/.sign/pdf?t='. $askedToken. '&kind=shown' )['/nino/http/response']['statusCode'] === 404
	&& signServe( $appData, '/.sign?t='. $newToken )['/nino/http/response']['statusCode'] === 200 && signServe( $appData, '/.sign/pdf?t='. $newToken. '&kind=shown' )['/nino/http/response']['statusCode'] === 200 );

$changed = signIndex( $appData );
$changed[$askedId]['expires'] = time() - 5;
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
$expiredBefore = \Nino\Modules\Sign::get( $appData, $askedId )['status'];
signFresh( $appData );
$remindedExpired = \Nino\Modules\Sign::remind( $appData, $askedId );
check( 'a request whose time is up is reminded, and is good again', $expiredBefore === 'expired' && $remindedExpired === true && \Nino\Modules\Sign::get( $appData, $askedId )['status'] === 'viewed' );

$delivery = false;
signFresh( $appData );
ninoWarnings();
$tokenKept = signIndex( $appData )[$askedId]['token'];
$expiresKept = signIndex( $appData )[$askedId]['expires'];
$remindedUnsent = \Nino\Modules\Sign::remind( $appData, $askedId );
$warned = ninoWarnings();
$delivery = true;
check( 'a reminder whose mail is not delivered changes nothing - the link the member has still works - and warns, naming the request and no address', $remindedUnsent === false && signIndex( $appData )[$askedId]['token'] === $tokenKept && signIndex( $appData )[$askedId]['expires'] === $expiresKept
	&& count( $warned ) === 1 && str_contains( $warned[0], $askedId ) === true && str_contains( $warned[0], '@' ) === false );

$mailsBefore = count( $mails );
$refused = [
	'a request that was signed' => \Nino\Modules\Sign::remind( $appData, $id ),
	'one that was declined' => \Nino\Modules\Sign::remind( $appData, $decliningId ),
	'an id nobody has' => \Nino\Modules\Sign::remind( $appData, 'ffffffffffff' ),
	'something that is no id' => \Nino\Modules\Sign::remind( $appData, '../'. $askedId ),
];
check( 'a request that is over - signed or declined - is not reminded, nor is an id nobody has: false, and no mail', array_filter( $refused ) === [] && count( $mails ) === $mailsBefore );

$cancelled = \Nino\Modules\Sign::cancel( $appData, $askedId );
$afterCancel = signServe( $appData, '/.sign?t='. $tokenOf( $mailsBefore - 1 ) );
signFresh( $appData );
check( 'cancel() takes a request back: true, the status is cancelled and nothing else of it changed; the link shows the state, the PDF is gone for the holder, and it is not reminded or cancelled again',
	$cancelled === true && signIndex( $appData )[$askedId]['status'] === 'cancelled' && \Nino\Modules\Sign::get( $appData, $askedId )['status'] === 'cancelled' && \Nino\Modules\Sign::remind( $appData, $askedId ) === false && \Nino\Modules\Sign::cancel( $appData, $askedId ) === false );
$cancelList = [ 'a request that was signed' => \Nino\Modules\Sign::cancel( $appData, $id ), 'one that was declined' => \Nino\Modules\Sign::cancel( $appData, $decliningId ), 'an id nobody has' => \Nino\Modules\Sign::cancel( $appData, 'ffffffffffff' ) ];
check( '...a request that was signed or declined cannot be taken back, nor an id nobody has: false, and they are as they were', array_filter( $cancelList ) === [] && signIndex( $appData )[$id]['status'] === 'signed' && signIndex( $appData )[$decliningId]['status'] === 'declined' );

[ $running, ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Ran out' ] );
$changed = signIndex( $appData );
$changed[$running['id']]['expires'] = time() - 5;
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $changed );
check( '...but one whose time is up can', \Nino\Modules\Sign::cancel( $appData, $running['id'] ) === true && signIndex( $appData )[ $running['id'] ]['status'] === 'cancelled' );

// remove(): the entry first, then the directory with the files - that request and no other
\Nino\Filesystem::putFileContent( $appData, '/data/sign/keep.txt', 'stays' );
$others = array_diff_key( signIndex( $appData ), [ $id => 1 ] );
$directory = \Nino\Filesystem::path( $appData, '/data/sign/'. $id );
$removed = \Nino\Modules\Sign::remove( $appData, $id );
check( 'remove() deletes a request whatever its state - a signed one with its three files -, the entry and the directory, that request and no other, and a file beside them stays',
	$removed === true && isset( signIndex( $appData )[$id] ) === false && is_dir( $directory ) === false && signIndex( $appData ) === $others && is_file( \Nino\Filesystem::path( $appData, '/data/sign/keep.txt' ) ) === true
	&& \Nino\Modules\Sign::get( $appData, $id ) === null && signServe( $appData, '/.sign?t='. $token )['/nino/http/response']['statusCode'] === 404 );
check( '...an id nobody has, or that is none, is false and removes nothing', \Nino\Modules\Sign::remove( $appData, $id ) === false && \Nino\Modules\Sign::remove( $appData, '../data' ) === false && \Nino\Modules\Sign::remove( $appData, '' ) === false
	&& signIndex( $appData ) === $others && is_dir( \Nino\Filesystem::path( $appData, '/data/sign' ) ) === true );
$removedHere = \Nino\Modules\Sign::requests( $appData );
echo "\n";

// --- The components -------------------------------------------------------------------------------------------------------------

echo "The components - what a member is shown\n";

$mine = \Nino\Modules\Sign::requests( $appData, $ada['id'] );
[ $titled, ] = $ask( 'two-fields', [], $ada, [ 'title' => '<b>Bold</b> [[/project/company/general/name]] [button] "quoted"' ] );
signAs( 'ada' );
signServe( $appData, '/.sign?r=ffffffffffff' );
$list = \Nino\Html::renderHtml( $appData, '[sign-list]' );
$rows = signFind( $list, '//li[contains(@class,"sign-list-row")]' );
$listed = array_map( static fn( DOMElement $row ): string => $row->getAttribute( 'data-sign-id' ), $rows );
$openOnes = array_values( array_filter( \Nino\Modules\Sign::requests( $appData, $ada['id'] ), static fn( array $request ): bool => $request['status'] !== 'signed' ) );
check( '[sign-list] shows the signed-in member the requests that were made of them and are not signed, the newest first - one row each, with the id and the state it is in',
	$listed === array_column( $openOnes, 'id' ) && $listed[0] === $titled['id'] && count( $rows ) >= 4 && array_filter( $rows, static fn( DOMElement $row ): bool => $row->getAttribute( 'data-sign-state' ) === 'signed' ) === []
	&& array_diff( [ 'declined', 'expired', 'cancelled', 'requested' ], array_map( static fn( DOMElement $row ): string => $row->getAttribute( 'data-sign-state' ), $rows ) ) === [] );
check( '...a row has the title, how it stands in words, the day and a link to the page of the request - and for a request that is not signed no link to the signed document',
	signContent( signFind( $list, '//li[@data-sign-id="'. $decliningId. '"]/span[@class="sign-list-title"]' ) ) === 'To decline' && signContent( signFind( $list, '//li[@data-sign-id="'. $decliningId. '"]/span[@class="sign-list-state"]' ) ) === 'Abgelehnt'
	&& signAttr( signFind( $list, '//li[@data-sign-id="'. $decliningId. '"]/a[@class="sign-list-page"]' ), 'href' ) === '/.sign?r='. $decliningId && signContent( signFind( $list, '//li[@data-sign-id="'. $decliningId. '"]/time' ) ) === substr( signIndex( $appData )[$decliningId]['created'], 0, 10 )
	&& count( signFind( $list, '//a[@class="sign-list-signed"]' ) ) === 0 );
check( '...a title is text and nothing else: markup, a fill and a shortcode in it are written out as they were typed, and are not run', signContent( signFind( $list, '//li[@data-sign-id="'. $titled['id']. '"]/span[@class="sign-list-title"]' ) ) === '<b>Bold</b> [[/project/company/general/name]] [button] "quoted"'
	&& count( signFind( $list, '//li//b' ) ) === 0 && str_contains( $list, 'The Club' ) === false );

$all = \Nino\Html::renderHtml( $appData, '[sign-list open="0"]' );
$signedRow = signFind( $all, '//li[@data-sign-state="signed"]' );
if( getenv("SIGN_DUMP") ) file_put_contents( "/tmp/claude-0/all.html", $all. "\n". json_encode( array_column( \Nino\Modules\Sign::requests( $appData, $ada["id"] ), "status", "id" ) ) );
check( 'with open off the signed requests are listed too, each with the link to the signed document - by the id -', count( signFind( $all, '//div[contains(@class,"sign-list")]' ) ) === 1
	&& count( signFind( $all, '//li[contains(@class,"sign-list-row")]' ) ) === count( \Nino\Modules\Sign::requests( $appData, $ada['id'] ) ) && $signedRow !== []
	&& signFind( $all, '//li[@data-sign-id="'. $typedId. '"]' ) === [] && signAttr( signFind( $all, '//li[@data-sign-state="signed"]/a[@class="sign-list-signed"]' ), 'href' ) === '/.sign/pdf?r='. $signedRow[0]->getAttribute( 'data-sign-id' ). '&kind=signed' );
signAs( 'bob' );
$bobs = signFind( \Nino\Html::renderHtml( $appData, '[sign-list open="0"]' ), '//li[contains(@class,"sign-list-row")]' );
check( '...another member sees theirs only', array_filter( $bobs, static fn( DOMElement $row ): bool => in_array( $row->getAttribute( 'data-sign-id' ), array_column( \Nino\Modules\Sign::requests( $GLOBALS['appData'], $GLOBALS['bob']['id'] ), 'id' ), true ) === false ) === [] && count( $bobs ) === count( \Nino\Modules\Sign::requests( $appData, $bob['id'] ) ) );
signVisitor();
check( '...a visitor sees nothing', \Nino\Html::renderHtml( $appData, '[sign-list]' ) === '' && \Nino\Html::renderHtml( $appData, '[sign-list open="0"]' ) === '' );

signAs( 'ada' );
foreach( \Nino\Modules\Sign::requests( $appData, $ada['id'] ) as $each )
	\Nino\Modules\Sign::remove( $appData, $each['id'] );
signServe( $appData, '/.sign?sign=x&sign-state=signed' );
$empty = \Nino\Html::renderHtml( $appData, '[sign-list]' );
check( 'a member with no requests is told so in a line, and the list has nothing else', signContent( signFind( $empty, '//li[@class="sign-list-none"]' ) ) === 'Es gibt keine Dokumente, die Du unterschreiben müsstest.' && count( signFind( $empty, '//li[contains(@class,"sign-list-row")]' ) ) === 0 );
$landed = signServe( $appData, '/.sign?sign=x&sign-state=declined' );
$withState = \Nino\Html::renderHtml( $appData, '[sign-list]' );
$landedBad = signServe( $appData, '/.sign?sign-state=%3Cb%3E' );
$stateless = \Nino\Html::renderHtml( $appData, '[sign-list]' );
signVisitor();
check( 'the state a signing was redirected with, ?sign-state=, is said above the list to the member - signed or declined and nothing else - and not to a visitor', str_contains( $withState, 'data-sign-state="declined"' ) === true && str_contains( $stateless, 'sign-form-state' ) === false
	&& str_contains( \Nino\Html::renderHtml( $appData, '[sign-list]' ), 'sign-form-state' ) === false );
echo "\n";

// --- The panel ------------------------------------------------------------------------------------------------------------------

echo "Modules\\Sign\\Admin - asking, reminding, cancelling and reading, and what the panel says\n";

signWorkbench( 'admin' );
$call = static fn( string $action, array $data = [] ): array => ninoCall( $GLOBALS['appData'], $GLOBALS['panel'], $data, $action );
$panels = \Nino\Admin\Admin::panels( $appData );
check( 'the panel is in the registry while the feature is active, in the rail\'s group of the features, with a permission of its own and a script that is there, two levels - the directory and the view that asks - and the words in both languages',
	isset( $panels['sign'] ) === true && $panels['sign']['group'] === 'features' && $panels['sign']['perm'] === '/_admin/sign/manage' && \Nino\Modules\Sign\Admin::MANAGE_PERM === '/_admin/sign/manage'
	&& array_filter( \Nino\Modules\Sign\Admin::assets(), static fn( string $asset ): bool => is_file( \Nino\Filesystem::path( $GLOBALS['appData'], $asset ) ) === false ) === [] && count( \Nino\Modules\Sign\Admin::assets() ) === 1 && \Nino\Modules\Sign\Admin::panes() === [ 'sign-list', 'sign-form' ]
	&& is_file( \Nino\Filesystem::path( $appData, \Nino\Modules\Sign\Admin::text(). '/en_US.php' ) ) === true && is_file( \Nino\Filesystem::path( $appData, \Nino\Modules\Sign\Admin::text(). '/de_DE.php' ) ) === true
	&& method_exists( \Nino\Modules\Sign\Admin::class, 'template' ) === false );
check( '...the script draws into the two mounts the pane has, asks the server through Nino.adminUi.api and writes no address by hand', preg_match( "/getElementById\\('sign-list'\\)/", (string) file_get_contents( $dir. '/assets/admin.js' ) ) === 1 && preg_match( "/getElementById\\('sign-form'\\)/", (string) file_get_contents( $dir. '/assets/admin.js' ) ) === 1
	&& str_contains( (string) file_get_contents( $dir. '/assets/admin.js' ), 'Nino.http.sendRequest' ) === false && preg_match( "/['\"]#['\"]\\s*\\+|location\\.hash/", (string) file_get_contents( $dir. '/assets/admin.js' ) ) !== 1 );

$denied = [];
signWorkbench( 'plain' );
foreach( array_keys( \Nino\Modules\Sign\Admin::actions() ) as $action )
	if( $call( $action )[0] !== 403 )
		$denied[] = $action;
signVisitor();
$unsigned = $call( 'sign/list' );
signWorkbench( 'admin' );
check( 'every panel action is 403 for an account without the permission'. ( $denied === [] ? '' : ' - '. implode( ', ', $denied ) ). ' and 401 with the code session for nobody', $denied === [] && count( \Nino\Modules\Sign\Admin::actions() ) === 7
	&& $unsigned[0] === 401 && ( $unsigned[1]['code'] ?? '' ) === 'session' );

// What the form offers
[ $status, $options ] = $call( 'sign/options' );
check( 'sign/options offers the active members - not the one that is switched off - and, with no tag in the setting, every template that has a field for a member, each with the free texts it asks for; and the limits',
	$status === 200 && array_column( $options['accounts'], 'id' ) === array_column( array_filter( \Nino\Modules\Members::accounts( $appData ), static fn( array $account ): bool => $account['active'] === true ), 'id' ) && in_array( $cyd['id'], array_column( $options['accounts'], 'id' ), true ) === false
	&& array_column( $options['templates'], 'key' ) === [ 'other-tag', 'contract-one', 'two-fields' ] && $options['templates'][1]['inputs'] === [ [ 'name' => 'note', 'label' => 'A note', 'multiline' => true, 'required' => true ] ] && $options['templates'][2]['inputs'] === []
	&& $options['templates'][1]['name'] === 'Service contract' && $options['templates'][1]['tag'] === 'contract' && $options['limits'] === [ 'title' => 120, 'input' => 5000 ] );
\Nino\Features::saveSettings( $appData, 'sign', [ 'tags' => "offer\nBad Tag\noffer" ] );
check( '...the tags are the lines of the setting that are slugs, each once: another tag offers its templates and drops the others', array_column( $call( 'sign/options' )[1]['templates'], 'key' ) === [ 'other-tag' ] );
\Nino\Features::saveSettings( $appData, 'sign', [ 'tags' => '' ] );

// Ask for a signature
signFresh( $appData );
$mailsBefore = count( $mails );
$requestsBefore = count( \Nino\Modules\Sign::requests( $appData ) );
$asking = $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'contract-one', 'inputs' => [ 'note' => 'From the panel', 'other' => 'dropped' ], 'title' => ' From the panel ' ] );
$askedHere = \Nino\Modules\Sign::get( $appData, (string) ( $asking[1]['id'] ?? '' ) );
check( 'sign/request makes the request: the answer has its id, the request is as Sign::request() makes it - the title cleaned, only the free texts the template has - and the member is mailed in the native language',
	$asking[0] === 200 && $askedHere !== null && $askedHere['title'] === 'From the panel' && $askedHere['account'] === $ada['id'] && $askedHere['data'] === [ 'inputs' => [ 'note' => 'From the panel' ] ] && count( $mails ) === $mailsBefore + 1
	&& $mails[$mailsBefore]['to'] === 'ada@example.org' && $mails[$mailsBefore]['subject'] === 'Ein Dokument wartet auf Deine Unterschrift' );
$noTitle = $call( 'sign/request', [ 'account' => $bob['id'], 'template' => 'two-fields' ] );
check( '...with no title it is the name of the template, and a template with no free text needs none', $noTitle[0] === 200 && \Nino\Modules\Sign::get( $appData, $noTitle[1]['id'] )['title'] === 'Two fields' );

$refusals = [
	'a template nobody has' => $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'nowhere', 'inputs' => [ 'note' => 'x' ] ] ),
	'a template with no field for a member' => $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'owner-only' ] ),
	'an account nobody has' => $call( 'sign/request', [ 'account' => 'ffffffffffff', 'template' => 'two-fields' ] ),
	'an account that is switched off' => $call( 'sign/request', [ 'account' => $cyd['id'], 'template' => 'two-fields' ] ),
	'an account that is a list' => $call( 'sign/request', [ 'account' => [ $ada['id'] ], 'template' => 'two-fields' ] ),
	'a free text that is missing' => $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'contract-one', 'inputs' => [] ] ),
	'a free text that is a list' => $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'contract-one', 'inputs' => [ 'note' => [ 'x' ] ] ] ),
	'a free text of 5001 characters' => $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'contract-one', 'inputs' => [ 'note' => str_repeat( 'x', 5001 ) ] ] ),
	'a title of 121 characters' => $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'title' => str_repeat( 'x', 121 ) ] ),
];
$told = array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ). ' '. ( $answer[1]['field'] ?? '' ), $refusals );
check( 'the refusals a person can cause carry their codes, the status and the field: a template nobody has sign_template, one with no field for a member sign_member, an account nobody has or that is off sign_account, a free text sign_input with the label and the limit, a long title sign_long',
	$told === [ 'a template nobody has' => '400 sign_template template', 'a template with no field for a member' => '400 sign_member template', 'an account nobody has' => '400 sign_account account', 'an account that is switched off' => '400 sign_account account',
		'an account that is a list' => '400 sign_account account', 'a free text that is missing' => '400 sign_input input-note', 'a free text that is a list' => '400 sign_input input-note', 'a free text of 5001 characters' => '400 sign_input input-note',
		'a title of 121 characters' => '400 sign_long title' ] && $refusals['a free text that is missing'][1]['params'] === [ 'A note', 5000 ] && $refusals['a title of 121 characters'][1]['params'] === [ 120 ] );
check( '...and a refusal makes no request and sends no mail', count( \Nino\Modules\Sign::requests( $appData ) ) === $requestsBefore + 2 && count( $mails ) === $mailsBefore + 2 );

$delivery = false;
signFresh( $appData );
$unsentAsk = $call( 'sign/request', [ 'account' => $bob['id'], 'template' => 'two-fields', 'title' => 'No mail' ] );
$delivery = true;
signFresh( $appData );
for( $each = 0; $each < 5; $each++ )
	\Nino\Feature::count( $appData, 'ratelimit', '127.0.0.1', 5, 3600 );
$limitedAsk = $call( 'sign/request', [ 'account' => $bob['id'], 'template' => 'two-fields', 'title' => 'Capped mail' ] );
signFresh( $appData );
ninoWarnings();
$byTitle = static fn( string $title ): array => array_values( array_filter( \Nino\Modules\Sign::requests( $GLOBALS['appData'] ), static fn( array $request ): bool => $request['title'] === $title ) );
check( 'a mail that could not be sent leaves the request there and is a 500 with the code sign_mail; the kernel\'s cap of mails is a 429 with sign_ratelimit - the request is there, and the row has Remind',
	$unsentAsk[0] === 500 && $unsentAsk[1]['code'] === 'sign_mail' && count( $byTitle( 'No mail' ) ) === 1 && $limitedAsk[0] === 429 && $limitedAsk[1]['code'] === 'sign_ratelimit' && count( $byTitle( 'Capped mail' ) ) === 1 );
$logged = json_encode( \Nino\Modules\Logs\Admin::recentLines( $appData, 100 ) );
check( '...and the request that was made is in the activity log all the same, by its id and with nothing else of it: for the mail that failed and for the one the cap refused', str_contains( (string) $logged, $byTitle( 'No mail' )[0]['id'] ) === true
	&& str_contains( (string) $logged, $byTitle( 'Capped mail' )[0]['id'] ) === true && str_contains( (string) $logged, 'No mail' ) === false && str_contains( (string) $logged, 'bob@example.org' ) === false );

// The list
$listing = $call( 'sign/list' );
$listed = $listing[1]['rows'];
$ofAsking = array_values( array_filter( $listed, static fn( array $request ): bool => $request['id'] === $asking[1]['id'] ) )[0];
$appData['/nino/dir'] = '/club';
$inDirectory = $call( 'sign/list' )[1]['rows'][0]['urls'];
$appData['/nino/dir'] = '';
check( 'sign/list answers every request, the newest first, with the member it is of, the name of the template, how it stands, the times, the record and the hashes - never the hash of the token - and the days of a link',
	$listing[0] === 200 && array_column( $listed, 'id' ) === array_column( \Nino\Modules\Sign::requests( $appData ), 'id' ) && $listing[1]['days'] === 14 && array_keys( $ofAsking ) === [ 'key', 'id', 'title', 'template', 'templateName', 'account', 'member', 'status', 'created', 'viewed', 'signed', 'declined', 'changed', 'expires', 'hashShown', 'hashSigned', 'method', 'record', 'files', 'urls' ]
	&& $ofAsking['member'] === [ 'name' => 'Ada Lovelace', 'email' => 'ada@example.org', 'label' => 'Ada Lovelace' ] && $ofAsking['templateName'] === 'Service contract' && $ofAsking['status'] === 'requested' && $ofAsking['files'] === [ 'shown' => true, 'signed' => false ]
	&& str_contains( json_encode( $listed ), 'token' ) === false );
check( '...with the addresses its two PDFs are opened at - the route\'s, below the directory the project is served from', $ofAsking['urls'] === [ 'shown' => '/.sign/pdf?r='. $ofAsking['id']. '&kind=shown', 'signed' => '/.sign/pdf?r='. $ofAsking['id']. '&kind=signed' ]
	&& str_starts_with( $inDirectory['shown'], '/club/.sign/pdf?r=' ) === true );

// A signed one carries its record
[ $forRecord, $forRecordToken ] = $ask( 'two-fields', [], $bob, [ 'title' => 'For the record' ] );
signServe( $appData, '/.sign', 'POST', [ 't' => $forRecordToken, 'action' => 'sign', 'consent' => '1', 'image' => $drawn ], true, [ 'HTTP_USER_AGENT' => 'Agent/1.0' ] );
signWorkbench( 'admin' );
$recorded = array_values( array_filter( $call( 'sign/list' )[1]['rows'], static fn( array $request ): bool => $request['id'] === $forRecord['id'] ) )[0];
check( '...and once signed the row has the record: the address, the browser, the sentence, how it was signed, both hashes and both PDFs', $recorded['status'] === 'signed' && $recorded['record'] === [ 'ip' => '127.0.0.1', 'agent' => 'Agent/1.0', 'consent' => (string) \Nino\Html::resolveTextfill( $appData, '/feature/sign/form/consent' ) ]
	&& $recorded['method'] === 'drawn' && preg_match( '/^[a-f0-9]{64}$/D', $recorded['hashSigned'] ) === 1 && $recorded['files'] === [ 'shown' => true, 'signed' => true ] );

// Remind, cancel, delete
signFresh( $appData );
$remindCall = $call( 'sign/remind', [ 'id' => $asking[1]['id'] ] );
$cancelCall = $call( 'sign/cancel', [ 'id' => $asking[1]['id'] ] );
$remindOver = $call( 'sign/remind', [ 'id' => $asking[1]['id'] ] );
$cancelOver = $call( 'sign/cancel', [ 'id' => $asking[1]['id'] ] );
$signedOver = [ $call( 'sign/remind', [ 'id' => $forRecord['id'] ] ), $call( 'sign/cancel', [ 'id' => $forRecord['id'] ] ) ];
$unknown = [ $call( 'sign/remind', [ 'id' => 'ffffffffffff' ] ), $call( 'sign/cancel', [ 'id' => '../x' ] ), $call( 'sign/delete', [ 'id' => [ 'x' ] ] ), $call( 'sign/delete', [ 'id' => 'ffffffffffff' ] ) ];
check( 'sign/remind sends the link again, sign/cancel takes the request back; a request that is over is 409 sign_state for both - and an id nobody has, or that is none, is 404 sign_unknown',
	$remindCall[0] === 200 && $remindCall[1] === [ 'sent' => true ] && $cancelCall[0] === 200 && \Nino\Modules\Sign::get( $appData, $asking[1]['id'] )['status'] === 'cancelled' && $remindOver[0] === 409 && $remindOver[1]['code'] === 'sign_state'
	&& $cancelOver[0] === 409 && $signedOver[0][0] === 409 && $signedOver[1][0] === 409 && array_map( static fn( array $answer ): string => $answer[0]. ' '. $answer[1]['code'], $unknown ) === [ '404 sign_unknown', '404 sign_unknown', '404 sign_unknown', '404 sign_unknown' ] );

$delivery = false;
signFresh( $appData );
$unsentRemind = $call( 'sign/remind', [ 'id' => $unsentAsk[1]['id'] ?? $byTitle( 'No mail' )[0]['id'] ] );
$delivery = true;
signFresh( $appData );
for( $each = 0; $each < 5; $each++ )
	\Nino\Feature::count( $appData, 'ratelimit', '127.0.0.1', 5, 3600 );
$limitedRemind = $call( 'sign/remind', [ 'id' => $byTitle( 'Capped mail' )[0]['id'] ] );
signFresh( $appData );
ninoWarnings();
check( 'a reminder whose mail could not be sent is a 500 with the code sign_mail, or a 429 with sign_ratelimit where the kernel\'s cap of mails refused it', $unsentRemind[0] === 500 && $unsentRemind[1]['code'] === 'sign_mail' && $limitedRemind[0] === 429 && $limitedRemind[1]['code'] === 'sign_ratelimit' );

$neighbours = array_diff_key( signIndex( $appData ), [ $forRecord['id'] => 1 ] );
$directoryOf = \Nino\Filesystem::path( $appData, '/data/sign/'. $forRecord['id'] );
$deleteCall = $call( 'sign/delete', [ 'id' => $forRecord['id'] ] );
check( 'sign/delete removes a request with its files - that one and no other', $deleteCall[0] === 200 && isset( signIndex( $appData )[ $forRecord['id'] ] ) === false && is_dir( $directoryOf ) === false && signIndex( $appData ) === $neighbours );

// The log, the tile
$log = static fn( string $action, array $data = [] ): string => \Nino\Modules\Sign\Admin::log( $action, $data );
$identified = [ 'id' => $asking[1]['id'], 'account' => $ada['id'], 'title' => 'Secret title', 'inputs' => [ 'note' => 'secret' ] ];
check( 'the activity log has a line for each action that writes - the id of the request, never the title, the member or a text - and none for an action that reads',
	array_sum( array_map( static fn( string $action ): int => $log( $action, $identified ) !== '' ? 1 : 0, [ 'sign/request', 'sign/remind', 'sign/cancel', 'sign/delete' ] ) ) === 4 && $log( 'sign/list', $identified ) === '' && $log( 'sign/options', $identified ) === ''
	&& str_contains( $log( 'sign/remind', $identified ), $asking[1]['id'] ) === true && str_contains( $log( 'sign/delete', $identified ), $asking[1]['id'] ) === true && str_contains( $log( 'sign/cancel', $identified ), $asking[1]['id'] ) === true
	&& array_filter( [ 'sign/request', 'sign/remind', 'sign/cancel', 'sign/delete' ], static fn( string $action ): bool => preg_match( '/Secret|secret|Ada|ada@/', $GLOBALS['log']( $action, $GLOBALS['identified'] ) ) === 1 ) === []
	&& count( array_unique( array_map( static fn( string $action ): string => $GLOBALS['log']( $action, $GLOBALS['identified'] ), [ 'sign/request', 'sign/remind', 'sign/cancel', 'sign/delete' ] ) ) ) === 4 );
$tell = static fn(): int => count( array_filter( \Nino\Modules\Sign::requests( $GLOBALS['appData'] ), static fn( array $request ): bool => in_array( $request['status'], [ 'requested', 'viewed' ], true ) ) );
$tryToSign( 'for the panel', fn() => $saveTemplate( '<h1>Service contract</h1><p>Between the club and [[client]]. Changed for the panel.</p>' ), fn() => $saveTemplate( $plainText ), [ 'page' => '/club/documents?x=1', 'title' => 'Asked again' ] );
$forAgain = $spoilt['for the panel'];
$waiting = $tell() + count( array_filter( \Nino\Modules\Sign::requests( $appData ), static fn( array $request ): bool => $request['status'] === 'changed' ) );
check( 'the Dashboard tile counts the requests that wait for a signature and the changed ones, which wait for the owner to ask again', \Nino\Modules\Sign\Admin::summary( $appData )['value'] === $waiting && $tell() < $waiting && $waiting > 0 && \Nino\Modules\Sign\Admin::summary( $appData )['label'] === '/_admin/sign/label/open' );

$row = array_column( $call( 'sign/list' )[1]['rows'], null, 'id' )[ $forAgain['id'] ] ?? [];
check( 'sign/list shows a changed request as changed, with the time it was found changed', ( $row['status'] ?? '' ) === 'changed' && ( $row['changed'] ?? '' ) === $forAgain['time'] && ( $row['files'] ?? [] ) === [ 'shown' => true, 'signed' => false ] );
$remindRefused = $call( 'sign/remind', [ 'id' => $forAgain['id'] ] );
check( '...it is not reminded (409, sign_state) and not cancelled', $remindRefused[0] === 409 && $remindRefused[1]['code'] === 'sign_state' && $call( 'sign/cancel', [ 'id' => $forAgain['id'] ] )[0] === 409 );

$before = array_keys( signIndex( $appData ) );
$oldEntry = signIndex( $appData )[ $forAgain['id'] ];
$mailsBefore = count( $mails );
signFresh( $appData );
$asked = $call( 'sign/again', [ 'id' => $forAgain['id'] ] );
$made = \Nino\Modules\Sign::get( $appData, (string) ( $asked[1]['id'] ?? '' ) );
$madeMail = $mails[$mailsBefore] ?? [ 'to' => '', 'body' => '' ];
check( 'sign/again makes a new request of the same template, data, member, title and page in one call - it waits, its member is mailed the link, which returns to the page the old one named - and the changed one stays as it is',
	$asked[0] === 200 && $made !== null && $made['id'] !== $forAgain['id'] && $made['status'] === 'requested' && [ $made['template'], $made['data'], $made['account'], $made['title'] ] === [ $oldEntry['template'], $oldEntry['data'], $oldEntry['account'], $oldEntry['title'] ]
	&& count( $mails ) === $mailsBefore + 1 && $madeMail['to'] === 'ada@example.org' && str_contains( $madeMail['body'], '&amp;return=%2Fclub%2Fdocuments' ) === true && count( array_diff( array_keys( signIndex( $appData ) ), $before ) ) === 1
	&& signIndex( $appData )[ $forAgain['id'] ] === $oldEntry && \Nino\Modules\Sign\Store::read( $appData )[ $made['id'] ]['page'] === '/club/documents' );
$notChanged = $call( 'sign/again', [ 'id' => $made['id'] ?? '' ] );
$unknownAgain = $call( 'sign/again', [ 'id' => 'ffffffffffff' ] );
check( '...only a changed request is asked again (409, sign_state), and an id nobody has is 404 sign_unknown', $notChanged[0] === 409 && $notChanged[1]['code'] === 'sign_state' && $unknownAgain[0] === 404 && $unknownAgain[1]['code'] === 'sign_unknown' && count( array_diff( array_keys( signIndex( $appData ) ), $before ) ) === 1 );
check( '...and its line in the activity log names the old request and the new one by their ids', str_contains( \Nino\Modules\Sign\Admin::log( 'sign/again', [ 'id' => $forAgain['id'], 'account' => $ada['id'] ] ), $forAgain['id'] ) === true && str_contains( \Nino\Modules\Sign\Admin::log( 'sign/again', [ 'id' => $forAgain['id'] ] ), $made['id'] ) === true
	&& str_contains( \Nino\Modules\Sign\Admin::log( 'sign/again', [ 'id' => $forAgain['id'], 'account' => $ada['id'] ] ), $ada['id'] ) === false );
\Nino\Modules\Sign::remove( $appData, $made['id'] );

// Asked not to mail: the request is made, nobody is mailed, the link is the answer - once
signFresh( $appData );
$mailsBefore = count( $mails );
$quiet = $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'title' => 'Quiet ask', 'notify' => false ] );
$quietLink = (string) ( $quiet[1]['link'] ?? '' );
$quietToken = preg_match( '~^https://example\.org/club/\.sign\?t=([A-Za-z0-9_-]{43})$~D', $quietLink, $quietFound ) === 1 ? $quietFound[1] : '';
$quietEntry = signIndex( $appData )[ $quiet[1]['id'] ?? '' ] ?? [];
check( 'sign/request with notify false makes the request, mails nobody and answers the id with the link as the mail would have carried it: the address of the site, /.sign and a token of 43 characters',
	$quiet[0] === 200 && array_keys( $quiet[1] ) === [ 'id', 'link' ] && $quietToken !== '' && count( $mails ) === $mailsBefore && ( $quietEntry['status'] ?? '' ) === 'requested' && ( $quietEntry['title'] ?? '' ) === 'Quiet ask' );
check( '...the index keeps the sha256 of that token and not the token, and the link opens the page for the same days as a mailed one',
	( $quietEntry['token'] ?? '' ) === hash( 'sha256', $quietToken ) && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/sign/requests.php' ) ), $quietToken ) === false
	&& abs( ( $quietEntry['expires'] ?? 0 ) - ( time() + 14 * 86400 ) ) < 5 && signServe( $appData, '/.sign?t='. $quietToken )['/nino/http/response']['statusCode'] === 200 );
$listed = $call( 'sign/list' );
$logged = \Nino\Modules\Sign\Admin::log( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'notify' => false ] );
check( '...the link is in that answer and nowhere else: not in the list of requests, not in the line of the activity log - which says that no mail was sent', str_contains( json_encode( $listed ), $quietToken ) === false && str_contains( $logged, $quietToken ) === false
	&& str_contains( $logged, 'without mail' ) === true && str_contains( \Nino\Modules\Sign\Admin::log( 'sign/request', [ 'account' => $ada['id'] ] ), 'without mail' ) === false );

$loud = [ $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'notify' => true ] ), $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'notify' => 'false' ] ), $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'notify' => 0 ] ) ];
check( '...while true, absent, and anything that is no false mail as before and answer no link', array_map( static fn( array $answer ): bool => $answer[0] === 200 && isset( $answer[1]['link'] ) === false, $loud ) === [ true, true, true ] && count( $mails ) === $mailsBefore + 3 );

$mailsBefore = count( $mails );
$quietRemind = $call( 'sign/remind', [ 'id' => $quiet[1]['id'], 'notify' => false ] );
$remindToken = preg_match( '~^https://example\.org/club/\.sign\?t=([A-Za-z0-9_-]{43})$~D', (string) ( $quietRemind[1]['link'] ?? '' ), $remindFound ) === 1 ? $remindFound[1] : '';
check( 'sign/remind with notify false mails nobody and answers the new link: the request holds the new token, whose sha256 is in the index, the old link is dead and the new one opens',
	$quietRemind[0] === 200 && $quietRemind[1]['sent'] === false && $remindToken !== '' && $remindToken !== $quietToken && count( $mails ) === $mailsBefore && ( signIndex( $appData )[ $quiet[1]['id'] ]['token'] ?? '' ) === hash( 'sha256', $remindToken )
	&& signServe( $appData, '/.sign?t='. $quietToken )['/nino/http/response']['statusCode'] === 404 && signServe( $appData, '/.sign?t='. $remindToken )['/nino/http/response']['statusCode'] === 200 );
$quietGone = $call( 'sign/cancel', [ 'id' => $quiet[1]['id'] ] );
check( '...a request that is over is refused without a link (409, sign_state), and the line of the log says that no mail was sent', $quietGone[0] === 200 && $call( 'sign/remind', [ 'id' => $quiet[1]['id'], 'notify' => false ] )[0] === 409
	&& str_contains( \Nino\Modules\Sign\Admin::log( 'sign/remind', [ 'id' => $quiet[1]['id'], 'notify' => false ] ), 'without mail' ) === true && str_contains( \Nino\Modules\Sign\Admin::log( 'sign/again', [ 'id' => $quiet[1]['id'], 'notify' => false ] ), 'without mail' ) === true );

signFresh( $appData );
$mailsBefore = count( $mails );
$viaClass = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'notify' => false, 'page' => '/club/documents' ] );
$classToken = preg_match( '~^https://example\.org/club/\.sign\?t=([A-Za-z0-9_-]{43})&return=%2Fclub%2Fdocuments$~D', (string) ( $viaClass['link'] ?? '' ), $classFound ) === 1 ? $classFound[1] : '';
$linkOut = \Nino\Modules\Sign::remind( $appData, $viaClass['id'], false );
$classLoud = \Nino\Modules\Sign::remind( $appData, $viaClass['id'], true );
check( 'Sign::request() with the option notify false is the request, mailed false and the link - with the page to return to, as the mail has it; Sign::remind() with false answers the new link, the same way, and with true answers true',
	is_array( $viaClass ) && $viaClass['mailed'] === false && $classToken !== '' && is_string( $linkOut ) === true && str_ends_with( $linkOut, '&return=%2Fclub%2Fdocuments' ) === true && str_contains( $linkOut, $classToken ) === false
	&& $classLoud === true && count( $mails ) === $mailsBefore + 1 );
\Nino\Modules\Sign::remove( $appData, $viaClass['id'] );

// ...a link without the mail is charged to the cap of mails an hour as a mail is: the sixth is refused, the flag of a refused mail is set, and nothing is made
signFresh( $appData );
$requestsBefore = count( signIndex( $appData ) );
$quiets = [];
for( $each = 0; $each < 6; $each++ )
	$quiets[] = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'notify' => false ] );
check( 'the sixth request in an hour whose link is not mailed is refused with "ratelimit" as the sixth mail is, makes no request, and flags the refusal like a refused mail', array_map( static fn( array|string $each ): string => is_array( $each ) === true ? 'made' : $each, $quiets ) === [ 'made', 'made', 'made', 'made', 'made', 'ratelimit' ]
	&& count( signIndex( $appData ) ) === $requestsBefore + 5 && \Nino\Mail::rateLimited( $appData ) === true );
$mailsBefore = count( $mails );
$mailedAfterFive = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
check( '...the same counter: a request that is mailed after five links is refused by it too', is_array( $mailedAfterFive ) && $mailedAfterFive['mailed'] === false && count( $mails ) === $mailsBefore );
signFresh( $appData );
foreach( $quiets as $each )
	if( is_array( $each ) === true )
		\Nino\Modules\Sign::remove( $appData, $each['id'] );
$kept = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
signFresh( $appData );
$reminds = [];
for( $each = 0; $each < 6; $each++ )
	$reminds[] = \Nino\Modules\Sign::remind( $appData, $kept['id'], false );
check( '...and the sixth link made again without a mail is false and the request keeps the token of the fifth', array_map( static fn( bool|string $each ): string => is_string( $each ) === true ? 'link' : 'false', $reminds ) === [ 'link', 'link', 'link', 'link', 'link', 'false' ] && \Nino\Mail::rateLimited( $appData ) === true
	&& \Nino\Modules\Sign\Store::read( $appData )[ $kept['id'] ]['token'] === hash( 'sha256', preg_match( '~t=([A-Za-z0-9_-]{43})~', $reminds[4], $found ) === 1 ? $found[1] : '' ) );
$panelQuiet = [];
signFresh( $appData );
signWorkbench( 'admin' );
for( $each = 0; $each < 6; $each++ )
	$panelQuiet[] = ninoCall( $appData, $panel, [ 'account' => $bob['id'], 'template' => 'two-fields', 'notify' => false ], 'sign/request' );
check( '...through the panel the sixth is a 429 with sign_ratelimit', array_column( $panelQuiet, 0 ) === [ 200, 200, 200, 200, 200, 429 ] && ( $panelQuiet[5][1]['code'] ?? '' ) === 'sign_ratelimit' );
$remindQuiet = ninoCall( $appData, $panel, [ 'id' => $kept['id'], 'notify' => false ], 'sign/remind' );
check( '...and the link made again through the panel is refused by the cap the same way', $remindQuiet[0] === 429 && ( $remindQuiet[1]['code'] ?? '' ) === 'sign_ratelimit' );
signFresh( $appData );

// ...the cap is the configured one: at 2 the third request is refused, at 100 and at 0 (no cap) the sixth goes through
$signCaps = [];
foreach( [ 2 => 3, 100 => 6, 0 => 6 ] as $cap => $times ) {
	signFresh( $appData );
	$appData['/nino/mail/cap'] = $cap;
	$made = [];
	for( $each = 0; $each < $times; $each++ )
		$made[] = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'], [ 'notify' => false ] );
	$signCaps[$cap] = array_map( static fn( array|string $one ): string => is_array( $one ) === true ? 'made' : $one, $made );
	foreach( $made as $one )
		if( is_array( $one ) === true )
			\Nino\Modules\Sign::remove( $appData, $one['id'] );
}
unset( $appData['/nino/mail/cap'] );
signFresh( $appData );
check( 'a request whose link is not mailed is charged against the configured cap: at 2 the third is refused with "ratelimit", at 100 and at 0 (no cap) six are made',
	$signCaps === [ 2 => [ 'made', 'made', 'ratelimit' ], 100 => array_fill( 0, 6, 'made' ), 0 => array_fill( 0, 6, 'made' ) ] );
$forRemind = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $bob['id'] );
$appData['/nino/mail/cap'] = 2;
signFresh( $appData );
$remindRuns = array_map( static fn( bool|string $one ): string => is_string( $one ) === true ? 'link' : 'false', [ \Nino\Modules\Sign::remind( $appData, $forRemind['id'], false ), \Nino\Modules\Sign::remind( $appData, $forRemind['id'], false ), \Nino\Modules\Sign::remind( $appData, $forRemind['id'], false ) ] );
unset( $appData['/nino/mail/cap'] );
signFresh( $appData );
check( '...and so is the link made again: at cap 2 the third is false', $remindRuns === [ 'link', 'link', 'false' ] );
\Nino\Modules\Sign::remove( $appData, $forRemind['id'] );

// ...and a request asked again: the same option
signFresh( $appData );
$tryToSign( 'for the quiet again', fn() => $saveTemplate( '<h1>Service contract</h1><p>Between the club and [[client]]. Changed quietly.</p>' ), fn() => $saveTemplate( $plainText ), [ 'title' => 'Asked again quietly' ] );
$forQuiet = $spoilt['for the quiet again'];
$mailsBefore = count( $mails );
signFresh( $appData );
$quietAgain = $call( 'sign/again', [ 'id' => $forQuiet['id'], 'notify' => false ] );
check( 'sign/again takes notify the same way: a new request, no mail, and its link in the answer', $quietAgain[0] === 200 && preg_match( '~^https://example\.org/club/\.sign\?t=[A-Za-z0-9_-]{43}~', (string) ( $quietAgain[1]['link'] ?? '' ) ) === 1 && count( $mails ) === $mailsBefore
	&& ( \Nino\Modules\Sign::get( $appData, (string) ( $quietAgain[1]['id'] ?? '' ) )['status'] ?? '' ) === 'requested' );

// The sentences of the codes, and the words of the script, in both languages
$codes = [];
preg_match_all( "/'(sign_[a-z]+)'/", (string) file_get_contents( $dir. '/Admin/Admin.php' ), $inCode );
$codes = array_values( array_unique( $inCode[1] ) );
$missingCode = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$fills = \Nino\Admin\Admin::textFills( $appData, '/features/Sign/text', $locale );
	foreach( $codes as $code )
		if( trim( (string) ( $fills['[[/_admin/error/'. $code. ']]'] ?? '' ) ) === '' )
			$missingCode[] = $locale. ' '. $code;
}
$enKeys = array_keys( \Nino\Admin\Admin::textFills( $appData, '/features/Sign/text', 'en_US' ) );
$deKeys = array_keys( \Nino\Admin\Admin::textFills( $appData, '/features/Sign/text', 'de_DE' ) );
check( 'every code the panel answers with has its sentence in both interface languages ('. count( $codes ). ' of them), and the two languages have the same keys',
	$missingCode === [] && count( $codes ) === 9 && $enKeys === $deKeys && count( $enKeys ) > 60 && str_contains( \Nino\Admin\Admin::textFills( $appData, '/features/Sign/text', 'en_US' )['[[/_admin/error/sign_input]]'], '%s' ) === true );
foreach( [ 'en_US', 'de_DE' ] as $locale )
	check( "...the sentence of sign_ratelimit in $locale is not empty and names the hour", preg_match( '/Stunde|hour/', trim( \Nino\Admin\Admin::textFills( $appData, '/features/Sign/text', $locale )['[[/_admin/error/sign_ratelimit]]'] ?? '' ) ) === 1 );
echo "\n";

// --- A template with an open request: /pdf/template/uses -----------------------------------------------------------------------

echo "\nA template with an open request - /pdf/template/uses\n";

$usesTemplate = static fn( string $key ): array => \Nino\Modules\Pdf\Store::saveTemplate( $GLOBALS['appData'], [ 'key' => $key, 'isNew' => true, 'name' => 'Uses '. $key, 'tag' => 'privacy', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>Sign here.</p>' ], $GLOBALS['field']( 'first', 'member', 20 ) ] ] );
$usesOf = static fn( string $key ): array => \Nino\Modules\Pdf::uses( $GLOBALS['appData'] )['uses'][$key] ?? [];
$prober = \Nino\Modules\Members::add( $appData, 'prober@example.org', 'Prober', 'club' );
$usesTemplate( 'uses-probe' );
check( 'a template that no request names is not counted by Sign, and can be deleted: the callback of the PDF feature is heard and says nothing for it', $usesOf( 'uses-probe' ) === [] && \Nino\Modules\Pdf::uses( $appData )['failed'] === [] && \Nino\Modules\Pdf::uses( $appData )['unknown'] === []
	&& \Nino\Modules\Pdf\Store::deleteTemplate( $appData, 'uses-probe' ) === [ 'ok' => true ] );

$usesTemplate( 'uses-probe' );
[ $openOne, $openToken ] = $ask( 'uses-probe', [], $prober, [ 'title' => 'Open one' ] );
$refused = \Nino\Modules\Pdf\Store::deleteTemplate( $appData, 'uses-probe' );
check( 'a template with a request that waits for a signature is a use for the package sign: counted once, and not deletable - 409 pdf_template_used, sign (1) in the params - the file stays', $usesOf( 'uses-probe' ) === [ 'sign' => 1 ]
	&& ( $refused['fail'][0] ?? 0 ) === 409 && ( $refused['fail'][2] ?? '' ) === 'pdf_template_used' && ( $refused['fail'][3] ?? [] ) === [ 'sign (1)' ] && \Nino\Modules\Pdf::template( $appData, 'uses-probe' ) !== null );

signServe( $appData, '/.sign?t='. $openToken );
[ $openTwo, $openTwoToken ] = $ask( 'uses-probe', [], $prober, [ 'title' => 'Open two' ] );
check( '...a request that was opened is still waiting and counts, and a second request makes two', $stateOf( (string) ( $openOne['id'] ?? '' ) ) === 'viewed' && $usesOf( 'uses-probe' ) === [ 'sign' => 2 ] );

signServe( $appData, '/.sign', 'POST', [ 't' => $openToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Prober' ] );
check( '...a request that is signed is over and counts no more: one is left', $stateOf( (string) ( $openOne['id'] ?? '' ) ) === 'signed' && $usesOf( 'uses-probe' ) === [ 'sign' => 1 ] );

signServe( $appData, '/.sign', 'POST', [ 't' => $openTwoToken, 'action' => 'decline' ] );
check( '...nor one that is declined: nothing is left, and the template is deleted', $stateOf( (string) ( $openTwo['id'] ?? '' ) ) === 'declined' && $usesOf( 'uses-probe' ) === [] && \Nino\Modules\Pdf\Store::deleteTemplate( $appData, 'uses-probe' ) === [ 'ok' => true ] );

$usesTemplate( 'uses-probe' );
[ $withdrawn ] = $ask( 'uses-probe', [], $prober, [ 'title' => 'Withdrawn' ] );
[ $lapsed ] = $ask( 'uses-probe', [], $prober, [ 'title' => 'Lapsed' ] );
\Nino\Modules\Sign::cancel( $appData, (string) ( $withdrawn['id'] ?? '' ) );
\Nino\Filesystem::mutate( $appData, '/data/sign/requests.php', static function( mixed $state ) use ( $lapsed ): array {
	$state[ (string) ( $lapsed['id'] ?? '' ) ]['expires'] = time() - 60;
	return $state;
}, [] );
check( 'a request that was cancelled and one that ran out count no more: the template has no use and is deleted', $stateOf( (string) ( $withdrawn['id'] ?? '' ) ) === 'cancelled' && ( \Nino\Modules\Sign::get( $appData, (string) ( $lapsed['id'] ?? '' ) )['status'] ?? '' ) === 'expired' && $usesOf( 'uses-probe' ) === []
	&& \Nino\Modules\Pdf\Store::deleteTemplate( $appData, 'uses-probe' ) === [ 'ok' => true ] );
check( 'the callback has a line in the manual, in both languages', isset( \Nino\Features::manifest( dirname( __DIR__ ) )['manual']['callbacks']['/pdf/template/uses']['en_US'], \Nino\Features::manifest( dirname( __DIR__ ) )['manual']['callbacks']['/pdf/template/uses']['de_DE'] ) );

// --- The restore of a backup, and deactivation ----------------------------------------------------------------------------------

echo "The restore of a backup, and deactivation\n";

[ $keptA, ] = $ask( 'two-fields', [], $ada, [ 'title' => 'In the backup' ] );
[ $keptB, ] = $ask( 'two-fields', [], $ada, [ 'title' => 'On the disk' ] );
[ $lostC, ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Nowhere' ] );

// The ids of two of the three are only digits - a key of an array is then a number: the restore and the pin on it are run on the ids that trip a strict comparison of keys, whatever the random ids were
foreach( [ 'keptA' => '100000000011', 'keptB' => '100000000012' ] as $name => $digits ) {
	$oldId = ${$name}['id'];
	rename( \Nino\Filesystem::path( $appData, '/data/sign/'. $oldId ), \Nino\Filesystem::path( $appData, '/data/sign/'. $digits ) );
	\Nino\Filesystem::mutate( $appData, '/data/sign/requests.php', static function( mixed $state ) use ( $oldId, $digits ): array {
		$state = (array) $state;
		$entry = $state[$oldId];
		unset( $state[$oldId] );
		$state[$digits] = $entry;
		return $state;
	}, [] );
	${$name}['id'] = $digits;
}
$staging = ninoSandboxDir( $appData ). '/staging';
$dataDir = \Nino\Filesystem::path( $appData, '/data' );
mkdir( $staging. '/data/sign/'. $keptA['id'], 0755, true );
copy( $dataDir. '/sign/'. $keptA['id']. '/shown.pdf', $staging. '/data/sign/'. $keptA['id']. '/shown.pdf' );
unlink( $dataDir. '/sign/'. $keptA['id']. '/shown.pdf' );
unlink( $dataDir. '/sign/'. $lostC['id']. '/shown.pdf' );
$stagedIndex = array_intersect_key( \Nino\Modules\Sign\Store::read( $appData ), array_flip( [ $keptA['id'], $keptB['id'], $lostC['id'] ] ) );
file_put_contents( $staging. '/data/sign/requests.php', '<?php return '. var_export( $stagedIndex, true ). ';' );
$liveIndexBytes = (string) file_get_contents( $dataDir. '/sign/requests.php' );
$restoreArgs = [ 'dataDir' => $dataDir, 'staging' => $staging ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $restoreArgs );
$restoredIndex = include $staging. '/data/sign/requests.php';
check( 'a restore keeps the requests of the staged index whose shown.pdf is in the backup or on the disk, and drops those whose is nowhere; the live index is not touched, the callback is the one init() registered',
	array_map( 'strval', array_keys( $restoredIndex ) ) === [ $keptA['id'], $keptB['id'] ] && $restoredIndex[ $keptA['id'] ] === $stagedIndex[ $keptA['id'] ] && file_get_contents( $dataDir. '/sign/requests.php' ) === $liveIndexBytes );

unlink( $staging. '/data/sign/requests.php' );
$noIndexArgs = [ 'dataDir' => $dataDir, 'staging' => $staging ];
\Nino\Modules\Sign::callbackRestore( $appData, $noIndexArgs );
$emptyArgs = [ 'dataDir' => '', 'staging' => '' ];
\Nino\Modules\Sign::callbackRestore( $appData, $emptyArgs );
check( '...a backup that carries no index is left as it is, and a call with nothing to go by does nothing', is_file( $staging. '/data/sign/requests.php' ) === false );

$kept = [ 'data' => glob( $dataDir. '/sign/*' ), 'index' => signIndex( $appData ) ];
check( 'the features it requires cannot be switched off while it is on', is_string( \Nino\Features::deactivate( $appData, 'pdf' ) ) === true && is_string( \Nino\Features::deactivate( $appData, 'members' ) ) === true );
check( 'deactivation takes the panel away and keeps /data/sign: the requests, their PDFs and their pictures', \Nino\Features::deactivate( $appData, 'sign' ) === true && isset( \Nino\Admin\Admin::panels( $appData )['sign'] ) === false
	&& glob( $dataDir. '/sign/*' ) === $kept['data'] && signIndex( $appData ) === $kept['index'] && in_array( '\\Nino\\Modules\\Sign', (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( '...and switched on again it finds them as they were', \Nino\Features::activate( $appData, 'sign' ) === true && signIndex( $appData ) === $kept['index'] && isset( \Nino\Admin\Admin::panels( $appData )['sign'] ) === true );
echo "\n";


// --- The probes of the plan: what makes a signature worth keeping ---------------------------------------------------------------

echo "The probes of the plan\n";

// The token is kept as its sha256, and compared with hash_equals: a token that is nearly right is nobody's
$source = (string) file_get_contents( $dir. '/Store/Store.php' );
check( 'the token is compared with hash_equals() against the hashes of the index - never with == or ===', preg_match( '/hash_equals\(\s*\$entry\[\'token\'\],\s*\$hash\s*\)/', $source ) === 1 && preg_match( '/\$entry\[.token.\]\s*[=!]==?\s*\$/', $source ) !== 1 );
[ $probe, $probeToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Probe' ] );
$near = [ 'the last character changed' => substr( $probeToken, 0, 42 ). ( substr( $probeToken, -1 ) === 'A' ? 'B' : 'A' ), 'a blank behind it' => $probeToken. ' ', 'a blank before it' => ' '. $probeToken, 'in lower case' => strtolower( $probeToken ), 'a null byte behind it' => $probeToken. "\0",
	'the hash that is kept' => hash( 'sha256', $probeToken ), 'the token twice' => $probeToken. $probeToken, 'a path' => '../'. $probeToken ];
$nearAnswers = [];
foreach( $near as $label => $value )
	$nearAnswers[$label] = [ signServe( $appData, '/.sign?t='. rawurlencode( $value ) )['/nino/http/response']['statusCode'], signServe( $appData, '/.sign/pdf?kind=shown&t='. rawurlencode( $value ) )['/nino/http/response']['statusCode'],
		signServe( $appData, '/.sign', 'POST', [ 't' => $value, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ] )['/nino/http/response']['statusCode'] ];
check( '...a token that is nearly right - the last character changed, blanks, case, a null byte, the hash that is kept, twice, a path - opens nothing: no page, no PDF, no signing - all the same 404, and the request is as it was',
	array_values( array_unique( array_map( 'json_encode', $nearAnswers ) ) ) === [ json_encode( [ 404, 404, 404 ] ) ] && $stateOf( $probe['id'] ) === 'requested' && signIndex( $appData )[$probe['id']]['viewed'] === null );
check( '...and the right one still does', signServe( $appData, '/.sign?t='. $probeToken )['/nino/http/response']['statusCode'] === 200 );

// The days of the link: a request is good for them and no longer, for the page, the PDF and the post
$aged = signIndex( $appData );
$aged[$probe['id']]['expires'] = time() + 5;
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $aged );
$beforeEnd = [ signServe( $appData, '/.sign?t='. $probeToken )['/nino/http/response']['statusCode'], signServe( $appData, '/.sign/pdf?kind=shown&t='. $probeToken )['/nino/http/response']['statusCode'] ];
$aged[$probe['id']]['expires'] = time() - 1;
\Nino\Filesystem::putFileContent( $appData, '/data/sign/requests.php', $aged );
$afterEnd = [ signServe( $appData, '/.sign?t='. $probeToken ), signServe( $appData, '/.sign/pdf?kind=shown&t='. $probeToken ), signServe( $appData, '/.sign', 'POST', [ 't' => $probeToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ] ) ];
check( 'a request is good for its days and not a second longer: the page and the PDF open before the end', $beforeEnd === [ 200, 200 ] );
$expiredPage = signServe( $appData, '/.sign?t='. $probeToken );
$expiredForm = \Nino\Html::renderHtml( $appData, '[sign-form]' );
$expiredPost = signServe( $appData, '/.sign', 'POST', [ 't' => $probeToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ] );
check( '...in detail: expired, no form, no signing, no file, and the status in the index is still the one that was written', str_contains( $expiredForm, 'data-sign-state="expired"' ) === true && str_contains( $expiredForm, '<form' ) === false && $expiredPost['/nino/http/response']['statusCode'] === 200
	&& \Nino\Modules\Sign::path( $appData, $probe['id'], 'signed' ) === null && signIndex( $appData )[$probe['id']]['status'] !== 'signed' && $afterEnd[1]['/nino/http/response']['statusCode'] === 404 );

// Only the member the request was asked of: the account that is gone, or switched off, is "there is none"
[ $gone, $goneToken ] = $ask( 'two-fields', [], $bob, [ 'title' => 'Gone' ] );
$deletedMember = \Nino\Modules\Members::add( $appData, 'deleted@example.org', 'Deleted', '' );
[ $deletedRequest, $deletedToken ] = $ask( 'two-fields', [], $deletedMember, [ 'title' => 'Deleted account' ] );
\Nino\Modules\Members::remove( $appData, $deletedMember['id'] );
$ofDeleted = [ signServe( $appData, '/.sign?t='. $deletedToken )['/nino/http/response']['statusCode'], signServe( $appData, '/.sign/pdf?kind=shown&t='. $deletedToken )['/nino/http/response']['statusCode'] ];
check( 'a request whose account was deleted is no page and no PDF: the same 404 as a token nobody has - and the request was taken away with the account (/members/removed), so that nothing of it is live', $ofDeleted === [ 404, 404 ] && $stateOf( $deletedRequest['id'] ) === '' );
signServe( $appData, '/.sign?t='. $deletedToken );
$deletedPost = signServe( $appData, '/.sign', 'POST', [ 't' => $deletedToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Deleted' ] );
check( '...and a post for it signs nothing', \Nino\Modules\Sign::path( $appData, $deletedRequest['id'], 'signed' ) === null && $stateOf( $deletedRequest['id'] ) === '' && $deletedPost['/nino/http/response']['statusCode'] !== 303 );

// A request is signed once: eight processes post the same signing at the same moment, and one of them wins
[ $race, $raceToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Race' ] );
$go = $sandbox. '/go-signal';
@unlink( $go );
@unlink( $go. '.mails' );
$processes = [];
$cursor = 0;
foreach( [ 'sign', 'sign', 'sign', 'sign', 'sign', 'sign', 'decline', 'decline' ] as $index => $action ) {
	$processes[$index] = proc_open( [ PHP_BINARY, __FILE__, 'sign-worker', $sandbox, '203.0.113.'. ( 10 + $index ), $go, $raceToken, $action ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
	$processes[$index] = [ $processes[$index], $pipes ];
}
usleep( 700000 );
file_put_contents( $go, '1' );
$answers = [];
foreach( $processes as $index => [ $process, $pipes ] ) {
	$said = '';
	for( $waited = 0; $waited < 20000; $waited += 20 ) {
		$said .= (string) stream_get_contents( $pipes[1] );
		if( proc_get_status( $process )['running'] === false )
			break;
		usleep( 20000 );
	}
	$said .= (string) stream_get_contents( $pipes[1] );
	proc_close( $process );
	$answers[$index] = json_decode( $said, true ) ?? [ 'raw' => $said ];
}
unset( $appData['./nino/filesystem/cache'] );
$won = array_filter( $answers, static fn( array $answer ): bool => ( $answer['status'] ?? 0 ) === 303 );
$told = array_merge( ...array_map( static fn( array $answer ): array => $answer['told'] ?? [], $answers ) );
$mailed = is_file( $go. '.mails' ) === true ? array_filter( explode( "\n", (string) file_get_contents( $go. '.mails' ) ) ) : [];
$end = signIndex( $appData )[$race['id']];
check( 'two requests at once - eight processes, six that sign and two that decline - end in one: exactly one of them is answered with the redirect, the others with the page and its state, none with an error', count( $won ) === 1 && count( array_filter( $answers, static fn( array $answer ): bool => in_array( $answer['status'] ?? 0, [ 303, 200 ], true ) ) ) === 8 );
check( '...the index has one end - signed or declined, as the winner posted - and the files agree with it: signed.pdf and the picture only for a signing, and the hash of the signed file is the one in the index', in_array( $end['status'], [ 'signed', 'declined' ], true ) === true
	&& ( $end['status'] === 'signed' ) === ( \Nino\Modules\Sign::path( $appData, $race['id'], 'signed' ) !== null ) && ( $end['status'] === 'signed' ) === ( \Nino\Modules\Sign\Store::path( $appData, $race['id'], 'signature' ) !== null )
	&& ( $end['status'] !== 'signed' || hash_file( 'sha256', (string) \Nino\Modules\Sign::path( $appData, $race['id'], 'signed' ) ) === $end['hashSigned'] ) );
check( '...the callback of the feature was told once - and what the end sets off went out once: the member and the owner for a signing, the owner for a decline - after the answer', count( $told ) === 1 && $told[0] === ( $end['status'] === 'signed' ? '/sign/signed' : '/sign/declined' )
	&& count( $mailed ) + count( signOutbox( $appData ) ) === ( $end['status'] === 'signed' ? 2 : 1 ) && count( array_unique( array_merge( array_map( static fn( string $line ): string => (string) ( json_decode( $line, true )['to'] ?? '' ), $mailed ), array_column( array_values( signOutbox( $appData ) ), 'to' ) ) ) ) === ( $end['status'] === 'signed' ? 2 : 1 ) );

// A request that is over stays over: signed, declined, cancelled - each refuses a second end, and nothing of its files or hashes moves
$over = [];
foreach( [ 'signed' => [ 'two-fields', 'sign' ], 'declined' => [ 'two-fields', 'decline' ], 'cancelled' => [ 'two-fields', 'cancel' ] ] as $label => [ $template, $how ] ) {
	[ $one, $oneToken ] = $ask( $template, [], $ada, [ 'title' => 'Over '. $label ] );
	if( $how === 'cancel' )
		\Nino\Modules\Sign::cancel( $appData, $one['id'] );
	else
		signServe( $appData, '/.sign', 'POST', [ 't' => $oneToken, 'action' => $how, 'consent' => '1', 'name' => 'Ada' ] );
	$kept = [ signIndex( $appData )[$one['id']], is_file( \Nino\Filesystem::path( $appData, '/data/sign/'. $one['id']. '/signed.pdf' ) ) ? hash_file( 'sha256', \Nino\Filesystem::path( $appData, '/data/sign/'. $one['id']. '/signed.pdf' ) ) : '' ];
	$tries = [ signServe( $appData, '/.sign', 'POST', [ 't' => $oneToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Another' ] )['/nino/http/response']['statusCode'], signServe( $appData, '/.sign', 'POST', [ 't' => $oneToken, 'action' => 'decline' ] )['/nino/http/response']['statusCode'] ];
	$over[$label] = [ $tries, signIndex( $appData )[$one['id']] === $kept[0], ( is_file( \Nino\Filesystem::path( $appData, '/data/sign/'. $one['id']. '/signed.pdf' ) ) ? hash_file( 'sha256', \Nino\Filesystem::path( $appData, '/data/sign/'. $one['id']. '/signed.pdf' ) ) : '' ) === $kept[1],
		\Nino\Modules\Sign::remind( $appData, $one['id'] ), \Nino\Modules\Sign::cancel( $appData, $one['id'] ) ];
}
check( 'after a signing, a decline and a cancel the request is closed: another signing or decline is the page with its state and no redirect, the index and the signed file are as they were, a reminder is refused, and so is a cancel',
	array_filter( $over, static fn( array $each ): bool => $each[0][0] === 303 || $each[0][1] === 303 || $each[1] !== true || $each[2] !== true || $each[3] !== false || $each[4] !== false ) === [] );

// The name that is typed: control characters, length
$typedPost = static function( string $name ) use ( &$appData, $ask, $ada ): array {
	[ $each, $eachToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Typed' ] );
	$answer = signServe( $appData, '/.sign', 'POST', [ 't' => $eachToken, 'action' => 'sign', 'consent' => '1', 'name' => $name ] );
	return [ $answer['/nino/http/response']['statusCode'], signIndex( $appData )[$each['id']]['status'], \Nino\Modules\Sign\Store::path( $appData, $each['id'], 'signature' ) ];
};
$cleaned = [ 'with control characters' => $typedPost( "Ada\x00\x1b[31m\x07Lovelace\r\n\t" ), 'plain' => $typedPost( 'Ada [31m Lovelace' ) ];
$sameInk = is_string( $cleaned['with control characters'][2] ) && is_string( $cleaned['plain'][2] ) && hash_file( 'sha256', $cleaned['with control characters'][2] ) === hash_file( 'sha256', $cleaned['plain'][2] );
$tooLong = $typedPost( str_repeat( 'x', 61 ) );
$onlyControl = $typedPost( "\x00\x01\x02\r\n" );
$sixty = $typedPost( str_repeat( 'x', 60 ) );
check( 'a typed name is cleaned of control characters - a null byte, an escape, a bell, line breaks - before it is drawn: it signs, as the same picture as the name with spaces in their place; 60 characters sign, 61 do not, and a name of nothing but control characters is no name',
	$cleaned['with control characters'][0] === 303 && $sameInk === true && $tooLong[0] === 400 && $tooLong[1] === 'requested' && $onlyControl[0] === 400 && $onlyControl[1] === 'requested' && $sixty[0] === 303 && $sixty[1] === 'signed' );

// The post carries the token of the session: without it nothing happens, whatever the rest says
[ $csrfRequest, $csrfToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Csrf' ] );
$beforeCsrf = signIndex( $appData );
$withoutToken = [ signServe( $appData, '/.sign', 'POST', [ 't' => $csrfToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ], false ), signServe( $appData, '/.sign', 'POST', [ 't' => $csrfToken, 'action' => 'decline', '_csrf' => 'forged' ], false ) ];
check( 'a post without the token of the session, or with a forged one, is the page again with the state "again" and a 403: nothing is signed or declined, nothing is counted - the index is as it was', array_map( static fn( array $request ): int => $request['/nino/http/response']['statusCode'], $withoutToken ) === [ 403, 403 ]
	&& signIndex( $appData ) === $beforeCsrf && \Nino\Modules\Sign::path( $appData, $csrfRequest['id'], 'signed' ) === null );

// What the page says at the field it is about: aria-invalid, and aria-describedby on the sentence, which has the id
[ $ariaRequest, $ariaToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Aria' ] );
signServe( $appData, '/.sign', 'POST', [ 't' => $ariaToken, 'action' => 'sign', 'name' => 'Ada' ] );
$consentForm = \Nino\Html::renderHtml( $appData, '[sign-form]' );
signServe( $appData, '/.sign', 'POST', [ 't' => $ariaToken, 'action' => 'sign', 'consent' => '1' ] );
$emptyForm = \Nino\Html::renderHtml( $appData, '[sign-form]' );
$describedBy = static fn( string $form, string $field ): string => signAttr( signFind( $form, '//*[@name="'. $field. '"]' ), 'aria-describedby' );
check( 'a refusal is said at its field: without the box ticked the checkbox is aria-invalid and described by the sentence, which has that id - and the name is not marked',
	signAttr( signFind( $consentForm, '//input[@name="consent"]' ), 'aria-invalid' ) === 'true' && count( signFind( $consentForm, '//p[@id="'. $describedBy( $consentForm, 'consent' ). '"][@data-sign-state="consent"]' ) ) === 1 && $describedBy( $consentForm, 'consent' ) !== ''
	&& signAttr( signFind( $consentForm, '//input[@name="name"]' ), 'aria-invalid' ) === '' );
check( '...with nothing to sign with the typed name is aria-invalid and described by the sentence of the state "empty", and the box is not marked', signAttr( signFind( $emptyForm, '//input[@name="name"]' ), 'aria-invalid' ) === 'true'
	&& count( signFind( $emptyForm, '//p[@id="'. $describedBy( $emptyForm, 'name' ). '"][@data-sign-state="empty"]' ) ) === 1 && signAttr( signFind( $emptyForm, '//input[@name="consent"]' ), 'aria-invalid' ) === '' );

// The typed name is a way of its own, as good as the pad: the field is in the form, not hidden, and a form with no script is signed by it with the keyboard alone
check( 'the typed name is an equal way to sign: its field is there without the script - not hidden, not disabled, reachable by the keyboard, named by a label - and the pad is the one that waits for the script to show itself',
	count( signFind( $consentForm, '//input[@name="name"][not(@hidden)][not(@disabled)][not(@tabindex)]' ) ) === 1 && signHas( signFind( $consentForm, '//div[@data-sign-name]' ), 'hidden' ) === false && signHas( signFind( $consentForm, '//div[@data-sign-draw]' ), 'hidden' ) === true
	&& count( signFind( $consentForm, '//label[@for="'. signAttr( signFind( $consentForm, '//input[@name="name"]' ), 'id' ). '"]' ) ) === 1 );

// What the browser is told of a PDF it shows
[ $headerRequest, $headerToken ] = $ask( 'two-fields', [], $ada, [ 'title' => "Evil \"; x=y\r\nSet-Cookie: a=b ../../etc/passwd\u{202E}fdp.exe\x00 <b> \"tail\"; q=1" ] );
$headers = signServe( $appData, '/.sign/pdf?kind=shown&t='. $headerToken )['/nino/http/response']['header'];
$disposition = (string) $headers['Content-Disposition'];
$live = [ 'status' => 'requested', 'expires' => time() + 60 ];
check( 'a request is live while it waits and its time is not up: Store::status() says expired for a waiting request that ran out - and live() says no - and signed stays signed, whatever the time', \Nino\Modules\Sign\Store::live( $live ) === true && \Nino\Modules\Sign\Store::live( [ 'expires' => time() - 1 ] + $live ) === false
	&& \Nino\Modules\Sign\Store::status( [ 'expires' => time() - 1 ] + $live ) === 'expired' && \Nino\Modules\Sign\Store::status( [ 'status' => 'signed', 'expires' => time() - 1 ] ) === 'signed' && \Nino\Modules\Sign\Store::live( [ 'status' => 'signed', 'expires' => time() + 60 ] ) === false );
check( 'the PDF is sent with a sandbox policy, nosniff and a name made of the title with nothing a header could not hold: no quote, no line break, no semicolon, no path, no mark that turns the text round, no null byte',
	$headers['Content-Security-Policy'] === 'sandbox' && $headers['X-Content-Type-Options'] === 'nosniff' && $headers['Content-Type'] === 'application/pdf' && str_starts_with( $disposition, 'inline; filename="' ) === true
	&& preg_match( '/[\x00-\x1f\x7f]/', $disposition ) !== 1 && str_contains( $disposition, 'passwd' ) === true && preg_match( '/^inline; filename="[A-Za-z0-9._ -]+"; filename\*=UTF-8\'\'[A-Za-z0-9%._~-]+$/D', $disposition ) === 1
	&& str_contains( $disposition, '%E2%80%AE' ) === false && str_contains( $disposition, '..%2F' ) === false && str_contains( $disposition, '%22' ) === false && str_contains( $disposition, '%0D' ) === false && str_contains( $disposition, '%00' ) === false
	&& str_ends_with( $disposition, '.pdf' ) === true );
$notFoundHeaders = signServe( $appData, '/.sign/pdf?kind=shown&t=x' )['/nino/http/response']['header'];
check( '...and the 404 that answers anybody else is a plain text, with no file and no disposition', ( $notFoundHeaders['Content-Type'] ?? '' ) === 'text/plain; charset=utf-8' && array_key_exists( 'Content-Disposition', $notFoundHeaders ) === false );

// Ids that are no ids: a path out of the directory, a null byte, a list - for the page, the PDF and every action of the panel
$badIds = [ '../'. $race['id'], $race['id']. "\0", "\0". $race['id'], signCapitals( $race['id'] ), $race['id']. '/', '..', '.', '', '%2e%2e%2f', substr( $race['id'], 0, 11 ) ];
check( 'the id in capitals is never the id itself - not for an id of only digits either, which has no letter to turn: the probes of an id that is none cannot run against the real one', signCapitals( '123456789012' ) !== '123456789012' && signCapitals( 'abcdef012345' ) === 'ABCDEF012345' && signCapitals( '0123456789ab' ) !== '0123456789ab' );
$badAnswers = [];
signAs( 'ada' );
foreach( $badIds as $badId ) {
	$badAnswers[] = [ signServe( $appData, '/.sign?r='. rawurlencode( $badId ) )['/nino/http/response']['statusCode'], signServe( $appData, '/.sign/pdf?kind=shown&r='. rawurlencode( $badId ) )['/nino/http/response']['statusCode'] ];
	foreach( [ 'sign/remind', 'sign/cancel', 'sign/delete', 'sign/again' ] as $action ) {
		signWorkbench( 'admin' );
		$badAnswers[] = [ $call( $action, [ 'id' => $badId ] )[0] ];
		signAs( 'ada' );
	}
}
signVisitor();
check( 'an id with a path in it, a null byte, capital letters, a trailing slash, eleven characters or none is the same 404 for the page and the PDF - and for every action of the panel that names a request: nothing is found, nothing outside /data/sign is touched',
	array_values( array_unique( array_map( 'json_encode', $badAnswers ) ) ) === [ json_encode( [ 404, 404 ] ), json_encode( [ 404 ] ) ] && is_file( \Nino\Filesystem::path( $appData, '/data/sign/requests.php' ) ) === true && is_dir( \Nino\Filesystem::path( $appData, '/data/sign' ) ) === true );
$kindProbes = [ 'shown/../signed', '../shown', 'shown%00', 'SHOWN', 'shown ', '' ];
check( '...and a kind that is not "shown" or "signed" in exactly that spelling is the 404 too', array_filter( $kindProbes, static fn( string $kind ): bool => signServe( $GLOBALS['appData'], '/.sign/pdf?t='. $GLOBALS['headerToken']. '&kind='. rawurlencode( $kind ) )['/nino/http/response']['statusCode'] !== 404 ) === [] );

// The panel without login, without the right and without the token of the session: nothing happens
$beforePanel = signIndex( $appData );
$panelDenied = [];
foreach( array_keys( \Nino\Modules\Sign\Admin::actions() ) as $action ) {
	signVisitor();
	$visitor = ninoCall( $appData, $panel, [ 'id' => $race['id'], 'account' => $ada['id'], 'template' => 'two-fields' ], $action );
	signWorkbench( 'plain' );
	$plain = ninoCall( $appData, $panel, [ 'id' => $race['id'], 'account' => $ada['id'], 'template' => 'two-fields' ], $action );
	signWorkbench( 'admin' );
	$forged = ninoCall( $appData, $panel, [ 'id' => $race['id'], 'account' => $ada['id'], 'template' => 'two-fields' ], $action, 403 );
	$panelDenied[$action] = [ $visitor[0], $plain[0], $forged[0] ];
}
check( 'every action of the panel answers 401 to nobody, 403 to an account without the permission and 403 to a request that failed the check of the token - and none of them changes a thing: the index is as it was', array_values( array_unique( array_map( 'json_encode', $panelDenied ) ) ) === [ json_encode( [ 401, 403, 403 ] ) ] && signIndex( $appData ) === $beforePanel
	&& count( $panelDenied ) === 7 );


// --- The accounts that are deleted: /members/removed ----------------------------------------------------------------------------

echo "\nAn account that is deleted - /members/removed\n";

$leaving = \Nino\Modules\Members::add( $appData, 'leaving@example.org', 'Leaving', 'club' );
$staying = \Nino\Modules\Members::add( $appData, 'staying@example.org', 'Staying', 'club' );
[ $waitA ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Waiting' ] );
[ $waitB, $waitBToken ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Opened' ] );
signServe( $appData, '/.sign?t='. $waitBToken );
[ $signedOne, $signedOneToken ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Signed before' ] );
signServe( $appData, '/.sign', 'POST', [ 't' => $signedOneToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Leaving' ] );
[ $declinedOne, $declinedToken ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Declined before' ] );
signServe( $appData, '/.sign', 'POST', [ 't' => $declinedToken, 'action' => 'decline' ] );
[ $changedOne ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Changed before' ] );
[ $otherOne ] = $ask( 'two-fields', [], $staying, [ 'title' => 'Of another account' ] );
[ $cancelledOne ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Withdrawn before' ] );
[ $expiredOne ] = $ask( 'two-fields', [], $leaving, [ 'title' => 'Ran out before' ] );
\Nino\Filesystem::mutate( $appData, '/data/sign/requests.php', static function( mixed $state ) use ( $changedOne, $cancelledOne, $expiredOne ): array {
	$state[ $changedOne['id'] ]['status'] = 'changed';
	$state[ $changedOne['id'] ]['changed'] = '2026-01-01T00:00:00Z';
	$state[ $cancelledOne['id'] ]['status'] = 'cancelled';
	$state[ $expiredOne['id'] ]['expires'] = time() - 60;
	return $state;
}, [] );
$filesOf = static function( string $id ) use ( &$appData ): array {
	$dir = \Nino\Filesystem::path( $appData, '/data/sign/'. $id );
	$files = [];
	foreach( is_dir( $dir ) === true ? scandir( $dir ) : [] as $name )
		if( $name !== '.' && $name !== '..' )
			$files[$name] = hash_file( 'sha256', $dir. '/'. $name );
	ksort( $files );
	return $files;
};
$signedFiles = $filesOf( $signedOne['id'] );
$openFiles = [ $waitA['id'] => $filesOf( $waitA['id'] ), $waitB['id'] => $filesOf( $waitB['id'] ), $declinedOne['id'] => $filesOf( $declinedOne['id'] ), $changedOne['id'] => $filesOf( $changedOne['id'] ), $cancelledOne['id'] => $filesOf( $cancelledOne['id'] ), $expiredOne['id'] => $filesOf( $expiredOne['id'] ) ];
$signedFile = (string) \Nino\Modules\Sign::path( $appData, $signedOne['id'], 'signed' );
$signedBytes = (string) file_get_contents( $signedFile );
$signedEntry = signIndex( $appData )[ $signedOne['id'] ];
$before = signIndex( $appData );
ninoWarnings();
$removedTold = [];
\Nino\Callbacks::registerCallback( $appData, '/members/removed', static function( array &$appData, array &$account ) use ( &$removedTold ): void { $removedTold[] = $account['id'] ?? ''; } );

// Things that are no account: nothing happens, and nothing is said
foreach( [ [ 'id' => '../'. $leaving['id'] ], [ 'id' => [ $leaving['id'] ] ], [ 'id' => signCapitals( $leaving['id'] ) ], [ 'id' => '' ], [], [ 'id' => 5 ], [ 'id' => $leaving['id']. "\0" ] ] as $broken ) {
	$told = $broken;
	\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $told );
}
$removedTold = [];
check( 'what a call with no id, an id with a path, in capitals, a number or with a null byte says is no account: nothing is taken back, nothing is said', signIndex( $appData ) === $before && ninoWarnings() === [] );

check( 'the account is deleted by the Members: it is gone and the kernel told its id, address and group to the listeners', \Nino\Modules\Members::remove( $appData, $leaving['id'] ) === 'ok' && $removedTold === [ $leaving['id'] ] && \Nino\Modules\Members::account( $appData, $leaving['id'] ) === null );
$after = signIndex( $appData );
check( 'only the signed request of the account stays: every other one - one that waits (requested, viewed), one that ran out, one that was withdrawn, declined or changed - is out of the index with the account', array_map( 'strval', array_keys( array_filter( $after, static fn( array $each ): bool => $each['account'] === $leaving['id'] ) ) ) === [ $signedOne['id'] ]
	&& array_map( 'strval', array_keys( array_diff_key( $before, $after ) ) ) === array_map( 'strval', array_keys( array_intersect_key( $before, array_flip( [ $waitA['id'], $waitB['id'], $declinedOne['id'], $changedOne['id'], $cancelledOne['id'], $expiredOne['id'] ] ) ) ) ) && count( array_diff_key( $before, $after ) ) === 6 );
foreach( [ 'requested' => $waitA, 'viewed' => $waitB, 'declined' => $declinedOne, 'changed' => $changedOne, 'withdrawn' => $cancelledOne, 'run out' => $expiredOne ] as $label => $gone ) {
	check( 'the request of the account that is '. $label. ' is gone with the account: not in the index, not on the disk with any of its files, and not a page or a PDF', isset( $after[ $gone['id'] ] ) === false && is_dir( \Nino\Filesystem::path( $appData, '/data/sign/'. $gone['id'] ) ) === false && $openFiles[ $gone['id'] ] !== [] && \Nino\Modules\Sign::get( $appData, $gone['id'] ) === null );
}
check( 'a request that was signed stays as the owner\'s record: the entry as it was, and signed.pdf, signature.png and shown.pdf with the same bytes - the member is gone, the evidence is not', $after[ $signedOne['id'] ] === $signedEntry && (string) file_get_contents( $signedFile ) === $signedBytes
	&& is_file( \Nino\Filesystem::path( $appData, '/data/sign/'. $signedOne['id']. '/signature.png' ) ) === true && is_file( \Nino\Filesystem::path( $appData, '/data/sign/'. $signedOne['id']. '/shown.pdf' ) ) === true
	&& count( $signedFiles ) === 3 && $filesOf( $signedOne['id'] ) === $signedFiles );
check( 'the request of another account is as it was, with its files', $after[ $otherOne['id'] ] === $before[ $otherOne['id'] ] && $filesOf( $otherOne['id'] ) !== [] && $stateOf( $otherOne['id'] ) === 'requested' );
check( '...and a link of a request that was taken away is dead: the PDF of a deleted account is a 404 for whoever holds the token, the signed one too', signServe( $appData, '/.sign/pdf?kind=shown&t='. $waitBToken )['/nino/http/response']['statusCode'] === 404
	&& signServe( $appData, '/.sign/pdf?kind=signed&t='. $signedOneToken )['/nino/http/response']['statusCode'] === 404 && ninoWarnings() === [] );

// Several accounts at once: a group is deleted, and each of its accounts is told
$g1 = \Nino\Modules\Members::add( $appData, 'g1@example.org', 'G1', 'doomed' );
$g2 = \Nino\Modules\Members::add( $appData, 'g2@example.org', 'G2', 'doomed' );
[ $g1Req ] = $ask( 'two-fields', [], $g1, [ 'title' => 'G1' ] );
[ $g2Req ] = $ask( 'two-fields', [], $g2, [ 'title' => 'G2' ] );
[ $safeReq ] = $ask( 'two-fields', [], $staying, [ 'title' => 'Safe' ] );
$gone = \Nino\Modules\Members::removeGroups( $appData, static fn( string $group ): bool => $group === 'doomed' );
check( 'a group that is deleted takes away what is not signed of each of its accounts - and for no other', is_array( $gone ) && count( $gone ) === 2 && $stateOf( $g1Req['id'] ) === '' && $stateOf( $g2Req['id'] ) === '' && $stateOf( $safeReq['id'] ) === 'requested' && $stateOf( $otherOne['id'] ) === 'requested' );

// Ids that are only digits: a key of an array is then a number, and a comparison that is strict against the keys finds nothing
$digitAccount = '100000000001';
\Nino\Filesystem::mutate( $appData, '/data/members.php', static function( mixed $state ) use ( $digitAccount ): array {
	$state['accounts'][$digitAccount] = [ 'email' => 'digits@example.org', 'name' => 'Digits', 'group' => '', 'active' => true, 'created' => '2026-01-01T00:00:00Z', 'login' => null ];
	return $state;
}, [] );
[ $digitRequest, $digitToken ] = $ask( 'two-fields', [], [ 'id' => $digitAccount ], [ 'title' => 'Digits' ] );
$digitId = '123456789012';
rename( \Nino\Filesystem::path( $appData, '/data/sign/'. $digitRequest['id'] ), \Nino\Filesystem::path( $appData, '/data/sign/'. $digitId ) );
\Nino\Filesystem::mutate( $appData, '/data/sign/requests.php', static function( mixed $state ) use ( $digitRequest, $digitId ): array { $entry = $state[ $digitRequest['id'] ]; unset( $state[ $digitRequest['id'] ] ); $state[$digitId] = $entry; return $state; }, [] );
$digitList = \Nino\Modules\Sign::requests( $appData );
$digitRows = $call( 'sign/list' )[1]['rows'] ?? [];
$digitRow = array_values( array_filter( $digitRows, static fn( array $row ): bool => $row['title'] === 'Digits' ) )[0] ?? [];
check( 'a request whose id is only digits is a string everywhere: get(), requests(), path(), the row of the panel with its key - and the JSON of the list says so', \Nino\Modules\Sign::get( $appData, $digitId )['id'] === $digitId && array_filter( array_column( $digitList, 'id' ), 'is_string' ) === array_column( $digitList, 'id' )
	&& \Nino\Modules\Sign::path( $appData, $digitId, 'shown' ) !== null && ( $digitRow['key'] ?? null ) === $digitId && ( $digitRow['id'] ?? null ) === $digitId && str_contains( json_encode( $digitRows ), '"key":"'. $digitId. '"' ) === true );
check( '...the page, the PDF and the three things the owner does answer for it: the token opens it, the id of a member who is signed in as well, remind and cancel and delete find it - and the account of only digits is the account of the request', signServe( $appData, '/.sign?t='. $digitToken )['/nino/http/response']['statusCode'] === 200
	&& signServe( $appData, '/.sign/pdf?kind=shown&t='. $digitToken )['/nino/http/response']['statusCode'] === 200 && $call( 'sign/remind', [ 'id' => $digitId ] )[0] === 200 && \Nino\Modules\Sign::requests( $appData, $digitAccount ) !== [] && \Nino\Modules\Sign::get( $appData, $digitId )['account'] === $digitAccount );
\Nino\Modules\Members::remove( $appData, $digitAccount );
check( '...and the deletion of an account of only digits takes its request away, with its files: a strict comparison of the keys would have found none', \Nino\Modules\Sign::get( $appData, $digitId ) === null && $stateOf( $digitId ) === '' && is_dir( \Nino\Filesystem::path( $appData, '/data/sign/'. $digitId ) ) === false );
check( '...and there is nothing left for the owner to cancel or delete: both find no request', $call( 'sign/cancel', [ 'id' => $digitId ] )[0] === 404 && $call( 'sign/delete', [ 'id' => $digitId ] )[0] === 404 );

// A listener whose write fails says so - as root, who may write anything but not through a lock that is a directory, and as another user, who may not write at all
$failing = \Nino\Modules\Members::add( $appData, 'failing@example.org', 'Failing', '' );
[ $failRequest ] = $ask( 'two-fields', [], $failing, [ 'title' => 'Cannot be taken back' ] );
$failIndex = signIndex( $appData );
$canonical = ( new ReflectionMethod( \Nino\Filesystem::class, '_canonicalPath' ) )->invoke( null, $appData, '/data/sign/requests.php' );
$lockPath = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( $canonical ). '.lock';
$warnings = [];
foreach( [ 'root' => 'a lock that cannot be taken', 'other' => 'a user who may not write' ] as $mode => $why ) {
	$warnFile = $sandbox. '/removed-'. $mode. '.txt';
	// The file that takes what the process says: a user who may write nothing else may write that
	file_put_contents( $warnFile, '' );
	chmod( $warnFile, 0666 );
	$prepared = [];
	$data = \Nino\Filesystem::path( $appData, '/data' );

	if( $mode === 'root' ) {
		@unlink( $lockPath );
		$prepared[] = mkdir( $lockPath );
	}
	else
		exec( 'chmod -R a-w '. escapeshellarg( $data ), $unused, $chmod );

	$process = proc_open( [ PHP_BINARY, __FILE__, 'removed-worker', $sandbox, '203.0.113.99', $mode, $warnFile, $failing['id'] ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
	$said = stream_get_contents( $pipes[1] );
	stream_get_contents( $pipes[2] );
	proc_close( $process );

	if( $mode === 'root' )
		rmdir( $lockPath );
	else
		exec( 'chmod -R u+w '. escapeshellarg( $data ) );

	$warnings[$mode] = [ trim( (string) $said ), is_file( $warnFile ) === true ? array_filter( explode( "\n", trim( (string) file_get_contents( $warnFile ) ) ) ) : [] ];
}
unset( $appData['./nino/filesystem/cache'] );
check( 'a write that cannot be made is said: as one who may write anything but takes no lock, and as another user who may not write at all, the listener ends and warns once - the account is gone from the members whether this worked or not', array_map( static fn( array $each ): string => $each[0], $warnings ) === [ 'root' => 'done', 'other' => 'done' ]
	&& count( array_filter( $warnings['root'][1], static fn( string $line ): bool => str_starts_with( $line, 'Sign: ' ) ) ) === 1 && count( array_filter( $warnings['other'][1], static fn( string $line ): bool => str_starts_with( $line, 'Sign: ' ) ) ) === 1 );
check( '...the warning names no address and no account - nothing a log must not hold - and what waited is as it was, for the owner to see', array_filter( array_merge( $warnings['root'][1], $warnings['other'][1] ), static fn( string $line ): bool => str_starts_with( $line, 'Sign: ' ) === true && ( str_contains( $line, '@' ) === true || str_contains( $line, $failing['id'] ) === true ) ) === []
	&& signIndex( $appData ) === $failIndex && $stateOf( $failRequest['id'] ) === 'requested' );

// The counter of the posts is the one of the network: an IPv6 address is its /64, an IPv4 address mapped into IPv6 is the IPv4 address
\Nino\Features::saveSettings( $appData, 'sign', [ 'attempts' => '2' ] );
[ $capRequest, $capToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Cap' ] );
$post = static function( string $address ) use ( &$appData, $capToken ): int {
	$_SERVER['REMOTE_ADDR'] = $address;
	$answer = signServe( $appData, '/.sign', 'POST', [ 't' => $capToken, 'action' => 'sign', 'name' => 'Ada' ] )['/nino/http/response']['statusCode'];
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	return $answer;
};
signFresh( $appData );
$sameNetwork = [ $post( '2001:db8:1:1::1' ), $post( '2001:db8:1:1:ffff:eeee:dddd:1' ), $post( '2001:db8:1:1::99' ) ];
$otherNetwork = $post( '2001:db8:1:2::1' );
$tries = ninoCounter( $appData, 'sign-tries' );
check( 'the posts are counted by the network of the client: three addresses of one /64 are one visitor - the third post of two is refused, 429 - and another /64 is another visitor', $sameNetwork === [ 400, 400, 429 ] && $otherNetwork === 400 );
check( '...the counter is kept under the sha256 of the network as the kernel names it - not of the address, not of a prefix - and not in clear', isset( $tries[ hash( 'sha256', '2001:db8:1:1::/64' ) ] ) === true && isset( $tries[ hash( 'sha256', 'visitor|2001:db8:1:1::1' ) ] ) === false
	&& str_contains( implode( array_map( 'file_get_contents', glob( \Nino\Filesystem::path( $appData, '/data/.counters/sign-tries' ). '/*.php' ) ?: [] ) ), '2001:db8' ) === false );
signFresh( $appData );
$mapped = [ $post( '203.0.113.5' ), $post( '::ffff:203.0.113.5' ), $post( '203.0.113.5' ) ];
check( '...an IPv4 address and the same address mapped into IPv6 are one visitor', $mapped === [ 400, 400, 429 ] );

// Behind a proxy this site trusts the client is the address the proxy wrote, and its network is counted: two addresses of one /64 are one visitor, another /64 behind the same proxy is another
$appData['/nino/http/proxies'] = [ '203.0.113.7' ];
$viaProxy = static function( string $client ) use ( &$appData, $capToken ): int {
	$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
	$_SERVER['HTTP_X_FORWARDED_FOR'] = $client;
	$answer = signServe( $appData, '/.sign', 'POST', [ 't' => $capToken, 'action' => 'sign', 'name' => 'Ada' ] )['/nino/http/response']['statusCode'];
	unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	return $answer;
};
signFresh( $appData );
$behindSame = [ $viaProxy( '2001:db8:7:7::1' ), $viaProxy( '2001:db8:7:7:aaaa:bbbb:cccc:2' ), $viaProxy( '2001:db8:7:7::3' ) ];
$behindOther = $viaProxy( '2001:db8:7:8::1' );
$behindTries = ninoCounter( $appData, 'sign-tries' );
check( 'behind a proxy the site trusts the client is the one the proxy names, counted by its network: two addresses of one /64 are one visitor - the third post of two is refused, 429 - and another /64 behind the same proxy is another',
	$behindSame === [ 400, 400, 429 ] && $behindOther === 400 && isset( $behindTries[ hash( 'sha256', '2001:db8:7:7::/64' ) ] ) === true && isset( $behindTries[ hash( 'sha256', '203.0.113.7' ) ] ) === false && isset( $behindTries[ hash( 'sha256', '2001:db8:7:8::/64' ) ] ) === true );
unset( $appData['/nino/http/proxies'] );
signFresh( $appData );
\Nino\Features::saveSettings( $appData, 'sign', [ 'attempts' => '20' ] );
signFresh( $appData );

// What a member's name, a title and a text hold is text in one pass: a placeholder of the mail written in them is itself and nothing else
$tricky = \Nino\Modules\Members::add( $appData, 'tricky@example.org', '[[link]] [[days]] [[document]] [[name]]', '' );
signFresh( $appData );
$mailsBefore = count( $mails );
$trickyMade = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $tricky['id'], [ 'title' => 'T [[name]] [[link]] [[days]]' ] );
$trickyBody = (string) ( $mails[$mailsBefore]['body'] ?? '' );
preg_match( '~href="(https://example\.org/club/\.sign\?t=[A-Za-z0-9_-]{43})"~', $trickyBody, $trickyLink );
check( 'a name and a title that are written like the placeholders of a mail are text: each stands once as it was typed, the link is in the mail once, and the days are the days', str_contains( $trickyBody, 'Hallo [[link]] [[days]] [[document]] [[name]],' ) === true && str_contains( $trickyBody, 'T [[name]] [[link]] [[days]]' ) === true
	&& substr_count( $trickyBody, 'https://example.org/club/.sign?t=' ) === 1 && isset( $trickyLink[1] ) === true && str_contains( $trickyBody, 'gültig für 14 Tage.' ) === true );
$trickyToken = substr( (string) ( $trickyLink[1] ?? '' ), -43 );
signServe( $appData, '/.sign?t='. $trickyToken );
signServe( $appData, '/.sign', 'POST', [ 't' => $trickyToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Tricky' ] );
$trickySigned = (string) \Nino\Modules\Sign::path( $appData, $trickyMade['id'], 'signed' );
$trickyText = $trickySigned === '' ? '' : preg_replace( '/\s+/', ' ', signText( (string) file_get_contents( $trickySigned ), '-f 2 -l 2' ) );
check( '...the record of the signing is written the same way: the name stands on its page as typed - and the address of the client is on it once, not where the name spells a placeholder of it', $trickySigned !== '' && ( signNoPdftotext( 'pin 6' ) || ( str_contains( $trickyText, '[[link]] [[days]] [[document]] [[name]] (tricky@example.org)' ) === true
	&& substr_count( $trickyText, '127.0.0.1' ) === 1 ) ) );

// The record page is written in one pass too: a name, a title and a browser that spell the tokens of the record stand on the page as they were typed - read from the page itself, not through a program that may not be there
$spelled = '[[time]] [[ip]] [[agent]] [[hash1]] [[hash2]] [[consent]] [[id]] [[email]] [[document]] [[name]]';
$spellMember = \Nino\Modules\Members::add( $appData, 'spell@example.org', $spelled, '' );
signFresh( $appData );
$spellMade = \Nino\Modules\Sign::request( $appData, 'two-fields', [], $spellMember['id'], [ 'title' => '[[ip]] [[time]] [[agent]] [[name]]' ] );
preg_match( '~\.sign\?t=([A-Za-z0-9_-]{43})~', (string) ( end( $mails )['body'] ?? '' ), $spellLink );
signServe( $appData, '/.sign?t='. ( $spellLink[1] ?? '' ) );
signServe( $appData, '/.sign', 'POST', [ 't' => $spellLink[1] ?? '', 'action' => 'sign', 'consent' => '1', 'name' => 'Spell' ], true, [ 'HTTP_USER_AGENT' => 'Agent [[time]] [[ip]]' ] );
$spellPdf = is_array( $spellMade ) === true && \Nino\Modules\Sign::path( $appData, $spellMade['id'], 'signed' ) !== null ? (string) file_get_contents( (string) \Nino\Modules\Sign::path( $appData, $spellMade['id'], 'signed' ) ) : '';
$spellPage = $spellPdf === '' ? '' : $pageOf( $spellPdf, 1 );
$spellPlain = $spellPage === '' ? '' : preg_replace_callback( '/\\\\(\d{3})/', static fn( array $each ): string => chr( (int) octdec( $each[1] ) ), implode( ' ', preg_match_all( '/\((.*?)\) Tj ET/', $spellPage, $spellLines ) > 0 ? $spellLines[1] : [] ) );
check( 'the record page of a signing says what was typed as it was typed - read from the bytes of the page: the name that spells every token of the record, the title that spells some and the browser stand on it, and the real time and the real address of the client stand once each',
	$spellPdf !== '' && signPages( $spellPdf ) === 2 && str_contains( $spellPlain, $spelled ) === true && str_contains( $spellPlain, '[[ip]] [[time]] [[agent]] [[name]]' ) === true && str_contains( $spellPlain, 'Agent [[time]] [[ip]]' ) === true
	&& substr_count( $spellPlain, '127.0.0.1' ) === 1 && preg_match_all( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} UTC/', $spellPlain ) === 1 );

// What a post sets off is queued while the answer is made and sent once it is out - and where the kernel drops the work after the answer, it waits in the outbox for the next request
[ $afterRequest, $afterToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'After' ] );
$mailsBefore = count( $mails );
$_POST = [ 't' => $afterToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
$afterVisit = ninoVisit( $appData, '/.sign', 'POST' );
\Nino\Http::response( $appData, $afterVisit );
$whileAnswering = count( $mails ) - $mailsBefore;
$queuedByThen = array_values( signOutbox( $appData ) );
$work = \Nino\Http::prepareAfter( $appData, $afterVisit );
foreach( $work as $each )
	$each();
$_POST = [];
$sentTo = array_column( array_slice( $mails, $mailsBefore ), 'to' );
sort( $sentTo );
$queuedTo = array_column( $queuedByThen, 'to' );
sort( $queuedTo );
check( 'the mails of a signing are queued while the answer is made and not sent: the member\'s - of the kind client, under the key of its request - and the owner\'s - of the kind system - are in the outbox by the time the answer is finished, and the kernel is asked for work after it', $whileAnswering === 0 && $queuedTo === [ 'ada@example.org', 'owner@example.org' ]
	&& count( array_filter( $queuedByThen, static fn( array $mail ): bool => $mail['kind'] === ( $mail['to'] === 'owner@example.org' ? 'system' : 'client' ) && $mail['key'] !== '' ) ) === 2 && count( $work ) >= 1 );
check( '...which sends the two once, to the member and to the owner', count( $mails ) - $mailsBefore === 2 && $sentTo === [ 'ada@example.org', 'owner@example.org' ] && signOutbox( $appData ) === [] );

// The same post twice - a double click, a retry of the browser - is the same mail: the key of a request and a kind names it, and the second queueing is the first mail
$signedAgain = \Nino\Modules\Sign::get( $appData, $afterRequest['id'] );
signFresh( $appData );
$queueTwice = new \ReflectionMethod( \Nino\Modules\Sign::class, '_mailSigned' );
$firstCall = [ &$appData, $signedAgain ];
$queueTwice->invokeArgs( null, $firstCall );
$onceQueued = array_keys( signOutbox( $appData ) );
ninoWarnings();
$secondCall = [ &$appData, $signedAgain ];
$queueTwice->invokeArgs( null, $secondCall );
check( 'the mails a signing sets off are queued under the key of the request and of their kind: queued a second time they are the mails that wait, nothing is added, and no warning says a mail was refused', count( $onceQueued ) === 2 && array_keys( signOutbox( $appData ) ) === $onceQueued && ninoWarnings() === [] );

// The owner's notices are told apart by what happened: the key of a request and an outcome. One request of one id can come to two ends only by force - declined, then (the index put back by hand) signed - and each end has its mail
[ $twoEnds, ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Two ends' ] );
signFresh( $appData );
$ownerMail = new \ReflectionMethod( \Nino\Modules\Sign::class, '_mailOwner' );
ninoWarnings();
$twoEndsEntry = \Nino\Modules\Sign::get( $appData, $twoEnds['id'] );
$asDeclined = [ &$appData, $twoEndsEntry, 'declined' ];
$ownerMail->invokeArgs( null, $asDeclined );
$asSigned = [ &$appData, $twoEndsEntry, 'signed' ];
$ownerMail->invokeArgs( null, $asSigned );
$asChanged = [ &$appData, $twoEndsEntry, 'changed' ];
$ownerMail->invokeArgs( null, $asChanged );
$ownerKeys = [];
foreach( signOutbox( $appData ) as $queuedMail )
	$ownerKeys[] = $queuedMail['to']. ' '. $queuedMail['kind'];
$keyFile = (string) @file_get_contents( \Nino\Filesystem::path( $appData, '/data/mail-keys.php' ) );
check( 'a decline, a signing and a change of one request are three mails of the owner, each under a key of its own - the key names the outcome - and a mail with the key of another outcome is never taken for it: all three wait, of the kind system, and no warning says one was refused',
	count( signOutbox( $appData ) ) === 3 && $ownerKeys === array_fill( 0, 3, 'owner@example.org system' ) && ninoWarnings() === [] && count( array_unique( array_column( signOutbox( $appData ), 'subject' ) ) ) === 3 );
ninoWarnings();

// What the outbox holds is what nobody has to be let in by: the mails that carry the link to sign - the request, the reminder, asked for in the panel or from code - are sent in the request, never queued, and the mails that are queued carry no link that signs
signFresh( $appData );
$mailsBefore = count( $mails );
[ $linked, $linkedToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Linked' ] );
$reminded = \Nino\Modules\Sign::remind( $appData, $linked['id'] );
$viaPanel = $call( 'sign/remind', [ 'id' => $linked['id'], 'notify' => true ] );
[ $panelAsked ] = [ $call( 'sign/request', [ 'account' => $ada['id'], 'template' => 'two-fields', 'title' => 'Asked in the panel', 'notify' => true ] ) ];
$linkMails = array_slice( $mails, $mailsBefore );
check( 'the request, the reminder and the panel\'s Remind and Ask send the link in the request and put nothing in the outbox: the mails are sent at once, each with the link of the token',
	$reminded === true && $viaPanel[0] === 200 && $panelAsked[0] === 200 && signOutbox( $appData ) === [] && count( $linkMails ) === 4 && count( array_filter( $linkMails, static fn( array $mail ): bool => preg_match( '~\.sign\?t=[A-Za-z0-9_-]{43}~', (string) $mail['body'] ) === 1 ) ) === 4 );
// A reminder makes a new link: the third mail is the panel's reminder of the request, and holds the token that is good
preg_match( '~\\.sign\\?t=([A-Za-z0-9_-]{43})~', (string) $linkMails[2]['body'], $lastLink );
$linkedToken = $lastLink[1] ?? '';
signServe( $appData, '/.sign?t='. $linkedToken );
signServe( $appData, '/.sign', 'POST', [ 't' => $linkedToken, 'action' => 'sign', 'consent' => '1', 'name' => 'Ada' ] );
check( '...and what the signing queues - the confirmation and the notice - holds no link that lets anybody in: no token, no address with t= in it', count( array_filter( signOutbox( $appData ), static fn( array $mail ): bool => preg_match( '~[?&]t=|\.sign\?t~', (string) $mail['body'] ) === 1 || str_contains( (string) $mail['body'], $GLOBALS['linkedToken'] ) === true ) ) === 0 && count( signOutbox( $appData ) ) + count( array_slice( $mails, $mailsBefore + 4 ) ) === 2 );
signFresh( $appData );

// A decline tells the owner: one mail, of the kind system, which the answer does not wait for
[ $declineMade, $declineToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Decline queued' ] );
$mailsBefore = count( $mails );
signFresh( $appData );
$_POST = [ 't' => $declineToken, 'action' => 'decline', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
$declineVisit = ninoVisit( $appData, '/.sign', 'POST' );
\Nino\Http::response( $appData, $declineVisit );
$_POST = [];
$declineQueued = array_values( signOutbox( $appData ) );
check( 'a decline queues one mail, to the owner, of the kind system, and sends nothing while the answer is made', count( $declineQueued ) === 1 && $declineQueued[0]['to'] === 'owner@example.org' && $declineQueued[0]['kind'] === 'system' && $declineQueued[0]['key'] !== '' && count( $mails ) === $mailsBefore );
signFresh( $appData );
[ $dropRequest, $dropToken ] = $ask( 'two-fields', [], $ada, [ 'title' => 'Dropped' ] );
@unlink( $sandbox. '/drop-go' );
@unlink( $sandbox. '/drop-go.mails' );
file_put_contents( $sandbox. '/drop-go', '1' );
$process = proc_open( [ PHP_BINARY, __FILE__, 'sign-worker', $sandbox, '203.0.113.77', $sandbox. '/drop-go', $dropToken, 'sign', 'drop' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
$droppedSaid = stream_get_contents( $pipes[1] );
stream_get_contents( $pipes[2] );
proc_close( $process );
$droppedMails = is_file( $sandbox. '/drop-go.mails' ) === true ? array_filter( explode( "\n", trim( (string) file_get_contents( $sandbox. '/drop-go.mails' ) ) ) ) : [];
$waitingTo = array_column( array_values( signOutbox( $appData ) ), 'to' );
sort( $waitingTo );
check( '...where the kernel cannot let the client go before - a length somebody else set - it hands nothing back: the mails are not sent and not lost, both wait in the outbox, finished, for the member and for the owner', ( json_decode( (string) $droppedSaid, true )['status'] ?? 0 ) === 303 && $droppedMails === [] && $waitingTo === [ 'ada@example.org', 'owner@example.org' ] );
$mailsBefore = count( $mails );
$delivered = \Nino\Mail::deliverQueued( $appData );
$deliveredTo = array_column( array_slice( $mails, $mailsBefore ), 'to' );
sort( $deliveredTo );
check( '...and the next request that delivers them sends each once, and the outbox is empty', $delivered['delivered'] === 2 && $deliveredTo === [ 'ada@example.org', 'owner@example.org' ] && signOutbox( $appData ) === [] );


// --- A set of the source: the data a project of Nino 1 holds, read, kept and written back ------------------------------------------

echo "\nThe data the source writes\n";

// tests/fixtures/source-requests.php is a file /data/sign/requests.php as the Sign feature of Nino 1.12 writes it, and source-parsed.json what that feature's own Store::parse() made of it
$sourceFile = $dir. '/tests/fixtures/source-requests.php';
$sourceParsed = json_decode( (string) file_get_contents( $dir. '/tests/fixtures/source-parsed.json' ), true );
$roundtrip = ninoSandbox( 'sign-source' );
$roundtrip['/nino/dir'] = '';
$roundtrip['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Filesystem::forceDir( $roundtrip, '/data/sign' );
copy( $sourceFile, \Nino\Filesystem::path( $roundtrip, '/data/sign/requests.php' ) );
foreach( array_keys( $sourceParsed ) as $kept ) {
	\Nino\Filesystem::putFileContent( $roundtrip, '/data/sign/'. $kept. '/shown.pdf', '%PDF-1.4 shown '. $kept );
	$sourceParsed[$kept]['status'] === 'signed' && \Nino\Filesystem::putFileContent( $roundtrip, '/data/sign/'. $kept. '/signed.pdf', '%PDF-1.4 signed '. $kept ) && \Nino\Filesystem::putFileContent( $roundtrip, '/data/sign/'. $kept. '/signature.png', signPng() );
}
$readSource = \Nino\Modules\Sign\Store::read( $roundtrip );
check( 'the index of the source is read as the source read it: the same entries, in the same order, each with the keys of the source in its order - an id of only digits among them', $readSource === $sourceParsed && array_map( 'strval', array_keys( $readSource ) ) === [ 'a1b2c3d4e5f6', '123456789012', 'ffeeddccbbaa' ]
	&& array_map( 'array_keys', $readSource ) === array_map( 'array_keys', $sourceParsed ) );
$viewed = \Nino\Modules\Sign::get( $roundtrip, 'a1b2c3d4e5f6' );
check( 'get() gives the request with the keys of the plan - id, template, data, title, account, status, expires, created, viewed, signed, declined, changed, hashShown, hashSigned, method, record, page - and never the hash of the token',
	array_keys( $viewed ) === [ 'id', 'template', 'data', 'title', 'account', 'status', 'expires', 'created', 'viewed', 'signed', 'declined', 'changed', 'hashShown', 'hashSigned', 'method', 'record', 'page' ] && $viewed['status'] === 'signed' && $viewed['record']['ip'] === '203.0.113.7' );
check( '...path() gives the files of a signed request and not of one that waits, and get() of the id of digits is a string', is_string( \Nino\Modules\Sign::path( $roundtrip, 'a1b2c3d4e5f6', 'signed' ) ) && \Nino\Modules\Sign::path( $roundtrip, '123456789012', 'signed' ) === null && \Nino\Modules\Sign::get( $roundtrip, '123456789012' )['id'] === '123456789012' );
// Written back: a change that changes nothing, and one that does - the file the kernel writes is read again as the source's own parse reads it
\Nino\Modules\Sign\Store::change( $roundtrip, 'ffeeddccbbaa', static fn( array $entry ): array => $entry );
\Nino\Modules\Sign::cancel( $roundtrip, '123456789012' );
unset( $roundtrip['./nino/filesystem/cache'] );
$rewritten = include \Nino\Filesystem::path( $roundtrip, '/data/sign/requests.php' );
$expected = $sourceParsed;
$expected['123456789012']['status'] = 'cancelled';
check( 'what the feature writes back is what the source would have read: every entry as it was but the one that was cancelled, with its keys in the order of the source, and the types of the source - the time an integer, the times that are not there null', is_array( $rewritten ) && json_encode( $rewritten ) === json_encode( $expected )
	&& is_int( $rewritten['a1b2c3d4e5f6']['expires'] ) === true && $rewritten['a1b2c3d4e5f6']['declined'] === null && $rewritten['a1b2c3d4e5f6']['record'] === $sourceParsed['a1b2c3d4e5f6']['record'] );
check( '...and remove() takes the entry and its directory of the set, and no other', \Nino\Modules\Sign::remove( $roundtrip, 'a1b2c3d4e5f6' ) === true && is_dir( \Nino\Filesystem::path( $roundtrip, '/data/sign/a1b2c3d4e5f6' ) ) === false && is_dir( \Nino\Filesystem::path( $roundtrip, '/data/sign/123456789012' ) ) === true
	&& array_map( 'strval', array_keys( \Nino\Modules\Sign\Store::read( $roundtrip ) ) ) === [ '123456789012', 'ffeeddccbbaa' ] );
\Nino\Filesystem::removeDir( (string) $roundtrip['./nino/test/sandbox'] );
unset( $appData['./nino/filesystem/cache'] );

// --- The scripts, where node is on the path ------------------------------------------------------------------------------------

// sign-js-smoke.js beside this file draws the panel over the kernel's own Nino.admin.js and a stand-in for the rest of Nino - the
// checkout this suite runs against, which is handed on - and runs sign.js, the pad, over a stand-in canvas; this suite runs it too
// where node is on the path, so that bin/check.sh and CI cover both halves in one go
// The place of the band at the head of the mail: [mail-head], which a module of the project answers - the feature itself names none
$count = 0;
$bandOf = static function() use ( &$appData, &$mails, $input, $ada, &$count ): string {

	$mails = [];
	signFresh( $appData );
	\Nino\Modules\Sign::request( $appData, 'contract-one', $input, $ada['id'], [ 'title' => 'Band '. ++$count ] );

	return (string) ( ( end( $mails ) ?: [] )['body'] ?? '' );
};
$noBand = $bandOf();
\Nino\Html::addShortcode( $appData, 'mail-head', static fn( array &$appData, mixed $args ): string => '<p data-band="head">BAND</p>' );
$withBand = $bandOf();
array_pop( $appData['./nino/callbacks']['/nino/html/shortcode/mail-head'][5] );
check( 'the feature registers [mail-head] itself, and where no module answers it the mail asking for a signature goes out with nothing in its place: no shortcode as text, no picture, no table of a band', in_array( 'mail-head', \Nino\Html::shortcodes( $appData ), true ) === true && $noBand !== '' && preg_match( '~\[mail-head\]~', $noBand ) === 0
	&& str_contains( $noBand, 'data-band' ) === false && str_contains( $noBand, '<img' ) === false && str_contains( $noBand, '<table role="presentation" width="100%"' ) === false );
check( '...and where a module answers it, its answer stands in the place, once, before the heading - the feature leaves the place to it', substr_count( $withBand, '<p data-band="head">BAND</p>' ) === 1 && strpos( $withBand, 'data-band' ) < strpos( $withBand, '<h1' ) && preg_match( '~\[mail-head\]~', $withBand ) === 0 );

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: sign-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	foreach( [ 'sign-js-smoke.js', 'sign-pad-js-smoke.js' ] as $script ) {
		$output = [];
		$exit = 1;
		exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/'. $script ). ' 2>&1', $output, $exit );
		check( $script. ' passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
	}
}

echo "\n";
echo ( $GLOBALS['pdfNotes'] ?? 0 ) === 0 ? '' : "\n  note - ". $GLOBALS['pdfNotes']. " pins on the words of a document were NOT run: pdftotext is not on the path\n";
ninoDone( $appData );

/**
 *	What the form asks of a finger: the box of the sentence in the label that names it, the canvas an image for a reader, and the sizes of what is pressed or typed in
 *	the standard part - read as css, not looked up as words
 *
 *	@param		string		$form					The rendered form
 *	@param		string		$sheet				The standard part
 *
 *	@return 	void
 */
function signTouchChecks( string $form, string $sheet ): void {

	echo "\nThe form for a finger\n";

	$rule = static function( string $selector ) use ( $sheet ): string {

		$found = preg_match_all( '/(?:^|\n)'. preg_quote( $selector, '/' ). ' \{([^}]*)\}/', $sheet, $matches ) > 0 ? $matches[1] : [];

		return implode( "\n", $found );
	};
	$id = signAttr( signFind( $form, '//input[@name="consent"]' ), 'id' );

	check( 'the box of the sentence is inside the label that names it - the label stands for it, so that the whole sentence ticks the box - and the input is still the one the label points at', $id !== '' && count( signFind( $form, '//p[@class="sign-form-consent"]/label[@for="'. $id. '"]/input[@id="'. $id. '"]' ) ) === 1
		&& count( signFind( $form, '//input[@type="checkbox"]' ) ) === 1 );
	check( '...the canvas is an image for a reader and keeps the name its label gives it', count( signFind( $form, '//canvas[@data-sign-pad][@role="img"][@aria-labelledby]' ) ) === 1
		&& count( signFind( $form, '//p[@id="'. signAttr( signFind( $form, '//canvas[@data-sign-pad]' ), 'aria-labelledby' ). '"]' ) ) === 1 );
	check( 'the box is 1.5rem square, and the label that holds it is a flex line that shows a hand', preg_match( '/width:\s*1\.5rem;\s*height:\s*1\.5rem;/', $rule( '.sign-form-check' ) ) === 1 && preg_match( '/display:\s*flex;[^}]*cursor:\s*pointer;/', $rule( '.sign-form-consent label' ) ) === 1 );
	check( '...the buttons of the form are 2.75rem high', preg_match( '/min-height:\s*2\.75rem;/', $rule( '.sign-form-button' ) ) === 1 );
	check( '...the field of the typed name is 2.75rem high and 16px at the least, so that a phone does not zoom into it', preg_match( '/min-height:\s*2\.75rem;/', $rule( '.sign-form-input' ) ) === 1 && preg_match( '/font-size:\s*max\( 16px, 1em \);/', $rule( '.sign-form-input' ) ) === 1 );
	check( '...the links of the form - the one that opens the document, the one to the signed document - are underlined and as high as a line a finger hits', preg_match( '/display:\s*inline-block;\s*min-height:\s*1\.75rem;[^}]*text-decoration:\s*underline;/', $rule( '.sign-form-open a, .sign-form-link a, .sign-list a' ) ) === 1 );
	check( '...and the frame of the document, the field, the buttons and the pad have a ring of focus', preg_match( '/outline:\s*2px solid var\(--brand-accent\);/', $rule( '.sign-form-frame:focus-visible, .sign-form-input:focus-visible, .sign-form-button:focus-visible, .sign-form-check:focus-visible, .sign-form-pad-canvas:focus-visible, .sign-form-open a:focus-visible, .sign-list a:focus-visible, .sign-form-link a:focus-visible' ) ) === 1 );
}
