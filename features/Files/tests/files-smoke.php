<?php
declare(strict_types=1);

/**
 *	Nino features
 *	files-smoke.php	The test of the feature Files: the manifest and the activation, the data layer and the interface other features call - what
 *									is declared, by method and by the source's callers -, the download route over the file response of the kernel, the upload in
 *									chunks - the owner's through the panel, a member's through the public route, and the plain post without script -, the
 *									probes of the security rules (a stranger, a path, a name, a type that is not what its name says, an upload out of order, a
 *									token that is missing, the cap of requests), the two components and their standard part, the panel's actions and
 *									the permission each of them needs, the mails and the one that goes after the answer, the hook of an account that is
 *									deleted, the backup (the index, not the bytes) and the restore - and a stock of data the source wrote, read and written
 *									back byte for byte, and - where node is on the path - the scripts' own tests. One pin for each behaviour, over the structure
 *									and never the words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Files/tests/files-smoke.php
 *	       NINO_ROOT=../nino php features/Files/tests/files-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'files' );
$appData['./nino/filesystem/path'] = $root;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$sandbox = ninoSandboxDir( $appData );

// The project directory and its fill, which \Nino\request() registers from the same key on every request - empty
// here, the way a site at the domain root has it - and the facts of the website the mails are built on: its
// address, which every link of a mail starts with (\Nino\Html::absoluteUrl()), and the contact address
$appData['/nino/dir'] = '';

// The look of the site's mails: the base unit's words, which a project has from its wizard
if( is_file( $root. '/_admin/install/library/base/text/global.php' ) === true )
	\Nino\Html::addFills( $appData, (array) include $root. '/_admin/install/library/base/text/global.php', '*' );

\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club', '[[/project/company/contact/email]]' => 'owner@example.org', '[[/project/company/general/name]]' => 'The Club' ], '*' );
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

// The workbench's entry point, which ninoCall() hands a panel action
$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];

/*	A transport takes the mail so nothing is actually sent - the same \Nino\Mail::TRANSPORT callback the Mailer
	feature registers. $delivery is what the stub says about the mail, so that a refusal is as easy to make as a delivery	*/
$mails = [];
$delivery = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$delivery ): void {
	$mails[] = $mail;
	$mail['sent'] = $delivery;
} );

// What the features that build on this one are told: the entry of a file that came in or went
$told = [];
foreach( [ '/files/upload', '/files/add', '/files/delete' ] as $event )
	\Nino\Callbacks::registerCallback( $appData, $event, static function( array &$appData, array &$entry ) use ( &$told, $event ): void {
		$told[] = [ $event, $entry ];
	} );

/**
 *	The data files of the feature as they are on disk, read fresh
 *
 *	@param		array 		&$appData
 *
 *	@return 	array										The index as it is stored
 */
function filesIndex( array &$appData ): array {

	$state = \Nino\Filesystem::getFileContent( $appData, '/data/files.php', [] );

	return is_array( $state ) === true ? $state : [];
}

/**
 *	Switch the feature on - the way a project does: Members and Basis first, the unit applied add-only, the class listed in /nino/modules, the
 *	version recorded - and boot what a request boots: the register of the components, the guard of the token, the modules
 *
 *	@param		array 		&$appData
 *
 *	@return 	true|string							True, or why not
 */
function filesActivate( array &$appData ): true|string {

	$activated = \Nino\Features::activate( $appData, 'files' );

	if( $activated !== true )
		return $activated;

	$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
	$appData['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Csrf' ], (array) ( $stored['/nino/modules'] ?? [] ) );
	$appData['/nino/features'] = (array) ( $stored['/nino/features'] ?? [] );
	unset( $appData['./nino/features/all'] );

	return true;
}

/**
 *	A file part as php hands it over: a stored chunk the test made
 *
 *	@param		array 		&$appData
 *	@param		string		$bytes
 *
 *	@return 	array										The entry of $_FILES
 */
function filesPart( array &$appData, string $bytes ): array {

	$path = ninoSandboxDir( $appData ). '/chunk-'. bin2hex( random_bytes( 4 ) );
	file_put_contents( $path, $bytes );

	return [ 'name' => 'blob', 'type' => 'application/octet-stream', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
}

/**
 *	The elements of a rendered piece of html by xpath, as a document reads them
 *
 *	@param		string		$html
 *	@param		string		$query
 *
 *	@return 	array										The DOMElements
 */
function filesFind( string $html, string $query ): array {

	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8"><body>'. $html. '</body>' );
	libxml_clear_errors();

	return iterator_to_array( ( new DOMXPath( $document ) )->query( $query ) ?: [] );
}

/**
 *	@param		DOMElement	$element
 *
 *	@return 	array										The classes of the element
 */
function filesClasses( DOMElement $element ): array {

	return array_values( array_filter( preg_split( '/\s+/', $element->getAttribute( 'class' ) ) ?: [] ) );
}


/**
 *	A request of the site the way the web server hands it over, answered by the routes and the callbacks that are
 *	registered: the request built, the global callbacks of the response run - the csrf guard and the gate of Members
 *	among them - and then the route's own. A post carries the csrf token of the session unless $csrf is false.
 *	Nothing is rendered
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$method
 *	@param		array 		$post
 *	@param		bool			$csrf					Whether a post carries the token
 *	@param		array 		$files				What php makes of the file parts, $_FILES
 *	@param		array 		$server				More of $_SERVER: a Range as HTTP_RANGE, the address of the client
 *
 *	@return 	array										The request, response and all
 */
function filesServe( array &$appData, string $uri, string $method = 'GET', array $post = [], bool $csrf = true, array $files = [], array $server = [] ): array {

	// What a request keeps for itself alone is gone before the next one begins
	unset( $appData['./files/forms'] );

	$_POST = $post + ( $method === 'POST' && $csrf === true ? [ '_csrf' => \Nino\Csrf::getToken( $appData ) ] : [] );
	$_FILES = $files;
	$request = ninoVisit( $appData, $uri, $method, $server );
	\Nino\Http::response( $appData, $request );
	$_POST = [];
	$_FILES = [];

	// What \Nino\Http::output() does once the answer is finished: it asks which work may run after it, and runs that once the client is let go. What the answer was
	// when it was finished - the mails that had gone, the work that was left - is kept for a test to read
	// The response is finished the way output() finishes it, on a copy the test goes on reading as data: what a module hears of the answer going out, and a body that is data made into its json
	$finished = $request;
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $finished );

	if( is_array( $finished['/nino/http/response']['body'] ?? null ) === true )
		$finished['/nino/http/response']['body'] = (string) json_encode( $finished['/nino/http/response']['body'] );

	$work = \Nino\Http::prepareAfter( $appData, $finished );
	$GLOBALS['filesAfter'] = [ 'mails' => count( $GLOBALS['mails'] ), 'work' => count( $work ) ];

	foreach( $work as $each )
		$each();

	return $request;
}

/**
 *	What a response says in the places a visitor and a test read it
 *
 *	@param		array 		$request
 *
 *	@return 	array										[ status, Location, Content-Type, body ]
 */
function filesAnswer( array $request ): array {

	$response = $request['/nino/http/response'];

	return [ $response['statusCode'], (string) ( $response['header']['Location'] ?? '' ), (string) ( $response['header']['Content-Type'] ?? '' ), $response['body'] ?? '' ];
}

/**
 *	A new window for every cap: the counter of the feature and the mail cap of the kernel, which counts the same client
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function filesFresh( array &$appData ): void {

	ninoCounterSet( $appData, 'files-tries', [] );
	ninoCounterSet( $appData, 'members-tries', [] );
	ninoCounterSet( $appData, 'ratelimit', [] );
}

/**
 *	A visitor who has never been here: no session, nobody signed in
 *
 *	@return 	void
 */
function filesVisitor(): void {

	$_SESSION = [];
	unset( $GLOBALS['appData']['./nino/auth/current'] );
}

/**
 *	Who is at the workbench: nobody, the plain account (which may do nothing there) or the full-access one. The kernel
 *	keeps who is signed in for the request, so a change of person drops that first
 *
 *	@param		string		$who					'', 'plain' or 'admin'
 *
 *	@return 	void
 */
function filesWorkbench( string $who ): void {

	global $appData;

	filesVisitor();

	if( $who === 'admin' )
		\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );
	else if( $who === 'plain' )
		\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );
}

/**
 *	Sign an account in the way a member does: a link is mailed, the page it opens asks, the button of that page signs in
 *
 *	@param		array 		&$appData
 *	@param		array 		$account
 *
 *	@return 	bool											Whether the member is signed in afterwards
 */
function filesSignIn( array &$appData, array $account ): bool {

	global $mails;

	filesFresh( $appData );
	filesVisitor();

	$count = count( $mails );

	if( \Nino\Modules\Members::link( $appData, $account, 'login' ) !== true || preg_match( '~href="https://example\.org/club/\.members/login\?t=([A-Za-z0-9_-]+)"~', (string) ( $mails[$count]['body'] ?? '' ), $found ) !== 1 )
		return false;

	filesServe( $appData, '/.members/login?t='. $found[1] );
	filesServe( $appData, '/.members/confirm', 'POST', [ 'token' => $found[1] ] );

	return ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $account['id'];
}

// --- The feature - manifest and activation -------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "files", written for this kernel, and without a warning', is_array( $manifest ) && $manifest['key'] === 'files' && ninoWarnings() === []
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false && isset( $manifest['stacks'] ) === false );
check( 'class, version and category are what the plan says, and it requires Members and Basis and the extension that sniffs the bytes', is_array( $manifest ) && $manifest['module'] === '\\Nino\\Modules\\Files' && $manifest['version'] === '2.0.0'
	&& $manifest['category'] === 'content' && $manifest['nino'] === '^2.0' && $manifest['requires'] === [ 'members', 'basis' ] && $manifest['php'] === [ 'ext' => [ 'fileinfo' ] ] );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it owns the index only - not the bytes, which the daily backup could not carry, and not the counter, which is an hour long - and its script is the one asset: the look is the standard part of its group', is_array( $manifest )
	&& $manifest['data'] === [ '/data/files.php' ] && $manifest['assets'] === [ '/features/Files/assets/files.js' ] );
check( 'the settings are the seven of the plan, each with its type, its default and its bounds', is_array( $manifest ) && array_keys( $manifest['settings'] ) === [ 'types', 'maxSize', 'chunk', 'quota', 'attempts', 'notifyMembers', 'notifyOwner' ]
	&& $manifest['settings']['types']['type'] === 'lines'
	&& [ $manifest['settings']['maxSize']['default'], $manifest['settings']['maxSize']['min'], $manifest['settings']['maxSize']['max'], $manifest['settings']['maxSize']['unit'] ] === [ 500, 1, 4096, 'megabytes' ]
	&& [ $manifest['settings']['chunk']['default'], $manifest['settings']['chunk']['min'], $manifest['settings']['chunk']['max'], $manifest['settings']['chunk']['unit'] ] === [ 4, 1, 32, 'megabytes' ]
	&& [ $manifest['settings']['quota']['default'], $manifest['settings']['quota']['min'], $manifest['settings']['quota']['max'] ] === [ 0, 0, 100000 ]
	&& [ $manifest['settings']['attempts']['default'], $manifest['settings']['attempts']['min'], $manifest['settings']['attempts']['max'], $manifest['settings']['attempts']['unit'] ] === [ 300, 1, 1000, 'per-hour' ]
	&& $manifest['settings']['notifyMembers']['type'] === 'bool' && $manifest['settings']['notifyMembers']['default'] === false && $manifest['settings']['notifyOwner']['type'] === 'bool' && $manifest['settings']['notifyOwner']['default'] === false );

// The default types are neutral, and every type of the default - and every type the feature knows - is checked against the bytes: no extension without a rule for its content
$types = (array) $manifest['settings']['types']['default'];
check( 'the default list of types is neutral - pictures, pdf, documents, text, zip, common audio and video - and every one of them has a rule for its bytes', array_diff( $types, array_keys( \Nino\Modules\Files\Store::MIME ) ) === []
	&& array_diff( [ 'jpg', 'png', 'pdf', 'docx', 'xlsx', 'txt', 'zip', 'mp3', 'mp4' ], $types ) === [] && count( array_filter( $types, static fn( string $type ): bool => in_array( $type, [ 'flac', 'aiff', 'aif', 'opus', 'aac', 'm4v' ], true ) ) ) === 0
	&& count( array_filter( $types, static fn( string $type ): bool => preg_match( '/^(?:mp3|wav|flac|aiff|aif|m4a|ogg|opus|aac|mp4|m4v|mov|webm)$/D', $type ) === 1 ) ) < count( $types ) / 2 );
check( 'no type of the list is one a browser runs: no html, svg, xml, js, php or htaccess, and every MIME rule is a list of names with a slash in it',
	array_diff( array_keys( \Nino\Modules\Files\Store::MIME ), [ 'html', 'htm', 'svg', 'xml', 'js', 'php', 'phtml', 'htaccess', 'xhtml' ] ) === array_keys( \Nino\Modules\Files\Store::MIME )
	&& array_filter( \Nino\Modules\Files\Store::MIME, static fn( array $names ): bool => $names === [] || array_filter( $names, static fn( string $name ): bool => preg_match( '#^[a-z]+/[a-z0-9.+-]+$#D', $name ) !== 1 ) !== [] ) === [] );
check( 'it says all six sections of its manual in both languages, and the lines of the install section for the index, the bytes and its section of the privacy policy', is_array( $manifest ) && (function() use ( $manifest ): bool {
	foreach( \Nino\Features::MANUAL_SECTIONS as $section )
		foreach( (array) ( $manifest['manual'][$section] ?? [] ) as $line )
			if( is_array( $line ) === false || trim( (string) ( $line['en_US'] ?? '' ) ) === '' || trim( (string) ( $line['de_DE'] ?? '' ) ) === '' )
				return false;
	return isset( $manifest['manual']['install']['elements/privacy.php'], $manifest['manual']['install']['/data/files.php'], $manifest['manual']['install']['/data/files/'] ) === true && count( $manifest['manual']['shortcodes'] ) >= 4
		&& count( $manifest['manual']['routes'] ) === 5 && count( $manifest['manual']['panel'] ) >= 3;
} )() );
check( 'the callbacks of the manual are the ones it fires and hears, and the lines "for other features" name the classes - nothing the code does not have',
	array_diff( [ '/files/upload', '/files/add', '/files/delete', '/files/granted', '/files/allowed', '/members/removed', '/nino/admin/restore' ], array_keys( $manifest['manual']['callbacks'] ) ) === []
	&& isset( $manifest['manual']['callbacks']['For other features: \Nino\Modules\Files, read'], $manifest['manual']['callbacks']['For other features: \Nino\Modules\Files\Store, data'], $manifest['manual']['callbacks']['For other features: \Nino\Modules\Files, the owner\'s operations'] ) === true );

// The catalogue's preview reads every manifest through the kernel, and is a script of the catalogue: it is there when this test runs in the
// repository, not when the feature has been dropped into a project - bin/check.sh and the CI run it as a step of their own
$preview = dirname( __DIR__, 3 ). '/bin/catalogue.php';

if( is_file( $preview ) === false )
	echo "  note - no bin/catalogue.php beside this feature: the catalogue's preview was not asked\n";
else {
	$listed = json_decode( (string) shell_exec( escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $preview ). ' '. escapeshellarg( $root ). ' 2>/dev/null' ), true );
	check( 'the catalogue preview lists it, with its category, its version and Members and Basis as what it requires', is_array( $listed ) && count( array_filter( (array) ( $listed['packages'] ?? $listed['features'] ?? [] ), static fn( mixed $entry ): bool => is_array( $entry ) === true
		&& ( $entry['key'] ?? '' ) === 'files' && ( $entry['category'] ?? '' ) === 'content' && ( $entry['version'] ?? '' ) === '2.0.0'
		&& array_diff( [ 'members', 'basis' ], (array) ( $entry['requires']['features'] ?? $entry['requires'] ?? [] ) ) === [] ) ) === 1 );
}

$privacyType = $root. '/_nino/Nino/Modules/Legal';

// The Legal module's type is what the feature's section is added to: put there first, where the kernel has the module
if( class_exists( '\Nino\Modules\Legal' ) === true ) {
	$routes = [];
	$blacklist = [];
	$config = [];
	\Nino\Features::applyUnit( $appData, $privacyType. '/install', [ 'de_DE', 'en_US' ], $routes, $blacklist, $config, true );
}

ninoWarnings();
check( 'the registry lists it, inactive, with nothing in the way', ( \Nino\Features::get( $appData, 'files' )['active'] ?? null ) === false && ( \Nino\Features::get( $appData, 'files' )['problems'] ?? null ) === [] );
check( 'activation succeeds - Members and Basis first', filesActivate( $appData ) === true && \Nino\Features::get( $appData, 'members' )['active'] === true && \Nino\Features::get( $appData, 'basis' )['active'] === true );
check( 'the class is listed in /nino/modules, and the version is recorded', in_array( '\\Nino\\Modules\\Files', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'files' )['installed'] === '2.0.0' && in_array( '\\Nino\\Modules\\Members', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true );

// The words of the unit: the same keys in both languages, none empty, and no more and no fewer than the templates and the class read
$unitWords = [ 'de_DE' => (array) include $dir. '/install/text/de_DE.php', 'en_US' => (array) include $dir. '/install/text/en_US.php' ];
$read = [];

foreach( array_merge( glob( $dir. '/templates/*.tpl' ) ?: [], glob( $dir. '/components/*/template.tpl' ) ?: [] ) as $template ) {

	preg_match_all( '~\[\[(/feature/[a-z0-9-]+/[a-z0-9-]+/[a-z0-9-]+)\]\]~', (string) file_get_contents( $template ), $keys );

	foreach( $keys[1] as $key )
		$read[] = '[['. $key. ']]';
}

foreach( [ 'sent', 'folder', 'type', 'size', 'post', 'quota', 'chunk', 'limit', 'write' ] as $state )
	$read[] = '[[/feature/files/state/'. $state. ']]';

foreach( [ 'upload', 'new' ] as $kind )
	$read[] = '[[/feature/files/mail/'. $kind. '-subject]]';

foreach( [ 'byte', 'kilobyte', 'megabyte', 'gigabyte', 'separator' ] as $unit )
	$read[] = '[[/feature/files/unit/'. $unit. ']]';

$read[] = '[[/feature/files/download/removed]]';
$script = [];
preg_match_all( "~word\(\s*'(script/[a-z-]+)'~", (string) file_get_contents( $dir. '/assets/files.js' ), $found );

foreach( array_unique( $found[1] ) as $name )
	$script[] = '[[/feature/files/'. $name. ']]';

$read = array_values( array_unique( array_merge( $read, $script ) ) );
sort( $read );
$delivered = array_keys( $unitWords['de_DE'] );
sort( $delivered );
check( 'the words of the unit are the same keys in German and English, none of them empty, and the keys the templates, the class and the script read - every one delivered and none left over',
	array_keys( $unitWords['de_DE'] ) === array_keys( $unitWords['en_US'] ) && array_filter( $unitWords['de_DE'] + $unitWords['en_US'], static fn( mixed $text ): bool => trim( (string) $text ) === '' ) === [] && $read === $delivered );
check( 'no word of the unit holds a place nobody fills: the source named the owner in curly brackets, which no feature of this catalogue puts in', array_filter( array_merge( array_values( $unitWords['de_DE'] ), array_values( $unitWords['en_US'] ) ), static fn( string $text ): bool => str_contains( $text, '{' ) === true ) === []
	&& array_filter( [ 'de_DE', 'en_US' ], static fn( string $locale ): bool => trim( (string) ( \Nino\Filesystem::getFileContent( $GLOBALS['appData'], '/text/'. $locale. '.php', [] )['[[/feature/files/upload/button]]'] ?? '' ) ) === '' ) === [] );

if( class_exists( '\Nino\Modules\Legal' ) === false )
	echo "  note - this Nino has no \\Nino\\Modules\\Legal: the section of the privacy policy was not looked for\n";
else {
	$privacy = \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] );
	check( 'the section of the privacy policy is there in both languages, at its position, and says that the backup carries the list of the files and not the files and that a deleted account leaves its uploads without a name', isset( $privacy['de_DE']['files']['title'], $privacy['en_US']['files']['text'] ) === true && ( $privacy['*']['files']['order'] ?? null ) === 840
		&& str_contains( (string) $privacy['en_US']['files']['text'], 'holds the list of the files and not the files themselves' ) === true && str_contains( (string) $privacy['de_DE']['files']['text'], 'enthält die Liste der Dateien, nicht die Dateien selbst' ) === true
		&& str_contains( (string) $privacy['en_US']['files']['text'], 'names no account any more' ) === true && str_contains( (string) $privacy['de_DE']['files']['text'], 'nennt aber kein Konto mehr' ) === true );

	$privacy['en_US']['files']['text'] = '<p>The editor changed this.</p>';
	\Nino\Filesystem::putFileContent( $appData, '/elements/privacy.php', $privacy );
	unset( $appData['./nino/features/all'] );
	\Nino\Features::activate( $appData, 'files' );
	check( 'a second activation changes nothing of it - a section the editor changed stays', \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] ) === $privacy );
}

// The kernel boots as \Nino\init() does it: the register reads the folders of the features that are on, then the modules run, then the parts are read
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Csrf::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
$ignored = ninoWarnings();

check( 'init registers the two routes with their handlers, the merge of a restore and the hook of a deleted account - and nothing of its own at the output of the answer, no shortcode of its own beside the components of the folders',
	( $appData['/nino/http/routes']['GET://.files/*']['uri'] ?? null ) === '/.files/*' && ( $appData['/nino/http/routes']['POST://.files/upload']['uri'] ?? null ) === '/.files/upload'
	&& isset( $appData['./nino/callbacks']['/nino/http/response/GET://.files/*'], $appData['./nino/callbacks']['/nino/http/response/POST://.files/upload'], $appData['./nino/callbacks']['/nino/admin/restore'], $appData['./nino/callbacks']['/members/removed'] ) === true
	&& array_filter( \Nino\Callbacks::registered( $appData, '/nino/http/output' ), static fn( mixed $callback ): bool => is_array( $callback ) === true && ( $callback[0] ?? null ) === \Nino\Modules\Files::class ) === [] && $ignored === [] );

echo "\n";


// --- The data layer and the API -------------------------------------------------

echo "Modules\\Files\\Store and the API - folders, files and what a caller reads\n";

check( 'a project with no folder lists none, for any group - and the index is not there yet', \Nino\Modules\Files::folders( $appData, 'staff' ) === [] && \Nino\Modules\Files::files( $appData, 'aaaaaaaaaaaa' ) === []
	&& \Nino\Modules\Files::file( $appData, 'aaaaaaaaaaaa' ) === null && \Nino\Modules\Files::path( $appData, 'aaaaaaaaaaaa' ) === null && filesIndex( $appData ) === [] );

$staff = \Nino\Modules\Files\Store::addFolder( $appData, 'staff', "  Mixes\x07 and masters  " );
$guests = \Nino\Modules\Files\Store::addFolder( $appData, 'guests', 'Guest folder' );
$stored = filesIndex( $appData );

check( 'a folder is an id of 12 hex characters with its group, its cleaned name, no upload mark yet and the time it was made',
	is_array( $staff ) && preg_match( '/^[a-f0-9]{12}$/D', $staff['id'] ) === 1 && array_keys( $stored['folders'][$staff['id']] ) === [ 'group', 'name', 'upload', 'created' ]
	&& $stored['folders'][$staff['id']]['group'] === 'staff' && $stored['folders'][$staff['id']]['name'] === 'Mixes and masters' && $stored['folders'][$staff['id']]['upload'] === false
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $stored['folders'][$staff['id']]['created'] ) === 1 && count( $stored['folders'] ) === 2 );
check( 'a name of more than 80 characters, an empty one and a group that is no slug are refused - 80 of them, counted as characters, are fine', \Nino\Modules\Files\Store::addFolder( $appData, 'staff', str_repeat( 'x', 81 ) ) === 'long'
	&& \Nino\Modules\Files\Store::addFolder( $appData, 'staff', " \t " ) === 'empty' && \Nino\Modules\Files\Store::addFolder( $appData, 'Not A Slug', 'x' ) === 'group'
	&& is_array( \Nino\Modules\Files\Store::addFolder( $appData, 'edge', str_repeat( 'ü', 80 ) ) ) && count( filesIndex( $appData )['folders'] ) === 3 );

$more = \Nino\Modules\Files\Store::addFolder( $appData, 'staff', 'Alpha' );
check( 'the folders of a group are listed with their ids, sorted by name without regard to case, and a folder of one group is not listed for another',
	array_column( \Nino\Modules\Files::folders( $appData, 'staff' ), 'name' ) === [ 'Alpha', 'Mixes and masters' ] && array_column( \Nino\Modules\Files::folders( $appData, 'guests' ), 'id' ) === [ $guests['id'] ]
	&& \Nino\Modules\Files::folders( $appData, 'nobody' ) === [] && array_keys( \Nino\Modules\Files::folders( $appData, 'staff' )[0] ) === [ 'id', 'group', 'name', 'upload', 'created' ] );

// The order is the order of the letters, whatever the case of the first one
$sorting = [];
foreach( [ 'Banana', 'apple', 'Cherry' ] as $each )
	$sorting[] = \Nino\Modules\Files\Store::addFolder( $appData, 'sorting', $each );
$sorted = array_column( \Nino\Modules\Files::folders( $appData, 'sorting' ), 'name' );
foreach( $sorting as $each )
	\Nino\Modules\Files\Store::deleteFolder( $appData, $each['id'] );
check( '...whatever the case the names begin with: apple comes before Banana, which comes before Cherry', $sorted === [ 'apple', 'Banana', 'Cherry' ] && \Nino\Modules\Files::folders( $appData, 'sorting' ) === [] );

// The mark: one folder of a group carries it, and marking another moves it - in one write
$first = \Nino\Modules\Files\Store::markUpload( $appData, $staff['id'], true );
$moved = \Nino\Modules\Files\Store::markUpload( $appData, $more['id'], true );
$marks = array_map( static fn( array $folder ): bool => $folder['upload'], array_column( filesIndex( $appData )['folders'], null ) );
check( 'exactly one folder of the group carries the upload mark after another is marked - the mark moves, nothing is refused - and the folders of other groups keep theirs',
	$first['upload'] === true && $moved['upload'] === true && filesIndex( $appData )['folders'][$staff['id']]['upload'] === false && filesIndex( $appData )['folders'][$more['id']]['upload'] === true
	&& array_sum( array_map( 'intval', $marks ) ) === 1 );
\Nino\Modules\Files\Store::markUpload( $appData, $guests['id'], true );
\Nino\Modules\Files\Store::markUpload( $appData, $more['id'], false );
check( '...the mark can be taken off, and a mark on another group stays', filesIndex( $appData )['folders'][$more['id']]['upload'] === false && filesIndex( $appData )['folders'][$guests['id']]['upload'] === true
	&& \Nino\Modules\Files\Store::markUpload( $appData, 'ffffffffffff', true ) === 'unknown' );

$renamed = \Nino\Modules\Files\Store::renameFolder( $appData, $more['id'], ' Beta ' );
check( 'a folder is renamed, with the rules of a name', $renamed['name'] === 'Beta' && \Nino\Modules\Files\Store::renameFolder( $appData, $more['id'], '' ) === 'empty' && \Nino\Modules\Files\Store::renameFolder( $appData, $more['id'], str_repeat( 'x', 81 ) ) === 'long'
	&& \Nino\Modules\Files\Store::renameFolder( $appData, 'ffffffffffff', 'x' ) === 'unknown' && filesIndex( $appData )['folders'][$more['id']]['name'] === 'Beta' );

// Files: put straight into the index with bytes beside them, as the upload leaves them
$bytesDir = \Nino\Filesystem::path( $appData, '/data/files' );
\Nino\Filesystem::forceDir( $appData, '/data/files' );

/**
 *	A file in the index with its bytes on the disk
 *
 *	@param		array 		&$appData
 *	@param		string		$folder
 *	@param		string		$name
 *	@param		string		$bytes
 *	@param		array 		$by
 *	@param		bool			$visible
 *	@param		string		$mime
 *	@param		int				$age					Seconds ago the file was put there
 *
 *	@return 	array										The entry with its id
 */
function filesMake( array &$appData, string $folder, string $name, string $bytes, array $by = [ 'kind' => 'owner', 'id' => '' ], bool $visible = true, string $mime = 'audio/mpeg', int $age = 5 ): array {

	[ $clean, $ext ] = \Nino\Modules\Files\Store::cleanName( $name );
	$id = bin2hex( random_bytes( 6 ) );

	file_put_contents( (string) \Nino\Modules\Files\Store::bytes( $appData, $id, $ext ), $bytes );

	$entry = \Nino\Modules\Files\Store::addFile( $appData, $id, [ 'folder' => $folder, 'name' => $clean, 'ext' => $ext, 'size' => strlen( $bytes ), 'mime' => $mime, 'sha256' => hash( 'sha256', $bytes ),
		'by' => $by, 'visible' => $visible, 'final' => false, 'created' => gmdate( \Nino\Modules\Files\Store::TIME, time() - $age ) ], 0 );

	return is_array( $entry ) === true ? $entry : [];
}

$demo = filesMake( $appData, $staff['id'], 'Demo.mp3', 'DEMO-BYTES' );
$hidden = filesMake( $appData, $staff['id'], 'Secret.mp3', 'HIDDEN-BYTES', [ 'kind' => 'owner', 'id' => '' ], false );

check( 'a file is an id of 12 hex characters in its folder, with its name, extension, size, MIME type, sha256, who put it there, whether it is visible and when',
	array_keys( filesIndex( $appData )['files'][$demo['id']] ) === [ 'folder', 'name', 'ext', 'size', 'mime', 'sha256', 'by', 'visible', 'final', 'created' ] && filesIndex( $appData )['files'][$demo['id']]['by'] === [ 'kind' => 'owner', 'id' => '' ]
	&& filesIndex( $appData )['files'][$demo['id']]['size'] === 10 && filesIndex( $appData )['files'][$demo['id']]['sha256'] === hash( 'sha256', 'DEMO-BYTES' ) );
check( 'files() lists the files of a folder, the hidden ones too, each with its id - file() answers one, path() the absolute path of its bytes, url() the address it is downloaded at',
	count( \Nino\Modules\Files::files( $appData, $staff['id'] ) ) === 2 && array_diff( array_column( [ $demo, $hidden ], 'id' ), array_column( \Nino\Modules\Files::files( $appData, $staff['id'] ), 'id' ) ) === [] && array_keys( \Nino\Modules\Files::files( $appData, $staff['id'] )[0] ) === [ 'id', 'folder', 'name', 'ext', 'size', 'mime', 'sha256', 'by', 'visible', 'final', 'created' ] );
check( '...one file by its id, its bytes, its address', \Nino\Modules\Files::file( $appData, $demo['id'] ) === $demo && \Nino\Modules\Files::path( $appData, $demo['id'] ) === $bytesDir. '/'. $demo['id']. '.mp3'
	&& file_get_contents( (string) \Nino\Modules\Files::path( $appData, $demo['id'] ) ) === 'DEMO-BYTES' && \Nino\Modules\Files::url( $appData, $demo['id'] ) === '/.files/'. $demo['id']
	&& \Nino\Modules\Files::files( $appData, $guests['id'] ) === [] );
// The newest first, whatever the names
$aged = \Nino\Modules\Files\Store::addFolder( $appData, 'aged', 'Aged' );
filesMake( $appData, $aged['id'], 'Older.mp3', 'OLD', [ 'kind' => 'owner', 'id' => '' ], true, 'audio/mpeg', 86400 );
filesMake( $appData, $aged['id'], 'Newer.mp3', 'NEW' );
filesMake( $appData, $aged['id'], 'Middle.mp3', 'MID', [ 'kind' => 'owner', 'id' => '' ], true, 'audio/mpeg', 3600 );
$newestFirst = array_column( \Nino\Modules\Files::files( $appData, $aged['id'] ), 'name' );
\Nino\Modules\Files\Store::deleteFolder( $appData, $aged['id'] );
check( '...the newest first: a file of yesterday comes after one of an hour ago, whatever the names', $newestFirst === [ 'Newer.mp3', 'Middle.mp3', 'Older.mp3' ] );

// The address starts with the directory the project is served from
$appData['/nino/dir'] = '/club';
$underDirectory = \Nino\Modules\Files::url( $appData, $demo['id'] );
$appData['/nino/dir'] = '';
check( '...an address that starts with the directory the project is served from', $underDirectory === '/club/.files/'. $demo['id'] );

check( '...and an id that is none, or that nobody has, is nothing - not a path, not an address', \Nino\Modules\Files::file( $appData, '../../etc/passwd' ) === null && \Nino\Modules\Files::path( $appData, '../../etc/passwd' ) === null
	&& \Nino\Modules\Files::path( $appData, 'ffffffffffff' ) === null && \Nino\Modules\Files::url( $appData, 'not-an-id' ) === '' && \Nino\Modules\Files::url( $appData, "{$demo['id']}/../x" ) === ''
	&& \Nino\Modules\Files\Store::bytes( $appData, $demo['id'], '../x' ) === null && \Nino\Modules\Files\Store::bytes( $appData, 'zz', 'mp3' ) === null );

// A file the index names and the disk has not
unlink( $bytesDir. '/'. $demo['id']. '.mp3' );
check( 'a file whose bytes are gone has an entry and no path', \Nino\Modules\Files::file( $appData, $demo['id'] ) === $demo && \Nino\Modules\Files::path( $appData, $demo['id'] ) === null );
file_put_contents( $bytesDir. '/'. $demo['id']. '.mp3', 'DEMO-BYTES' );

// The index is plain php anybody can edit: what is not an entry is left out when it is read
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [ 'folders' => [ 'aaaaaaaaaaaa' => [ 'group' => 'x', 'name' => 'Ok', 'upload' => 'yes', 'created' => 5 ], 'bad' => [ 'group' => 'x' ], 'bbbbbbbbbbbb' => [ 'group' => 'Not A Slug' ] ],
	'files' => [ 'cccccccccccc' => [ 'folder' => 'aaaaaaaaaaaa', 'name' => 'a.mp3', 'ext' => 'mp3', 'size' => 3, 'mime' => 'audio/mpeg', 'sha256' => 'x', 'by' => [ 'kind' => 'member', 'id' => 'dddddddddddd' ], 'visible' => 1 ],
		'eeeeeeeeeeee' => [ 'folder' => 'zzzzzzzzzzzz', 'name' => 'b.mp3', 'ext' => 'mp3', 'size' => 3, 'by' => [ 'kind' => 'owner' ] ], 'ffffffffffff' => [ 'folder' => 'aaaaaaaaaaaa', 'name' => 'c.mp3', 'ext' => '../x', 'size' => 3, 'by' => [ 'kind' => 'owner' ] ],
		'111111111111' => [ 'folder' => 'aaaaaaaaaaaa', 'name' => 'd.mp3', 'ext' => 'mp3', 'size' => '3', 'by' => [ 'kind' => 'owner' ] ], '222222222222' => [ 'folder' => 'aaaaaaaaaaaa', 'name' => 'e.mp3', 'ext' => 'mp3', 'size' => 3, 'by' => [ 'kind' => 'robot' ] ] ] ] );
$read = \Nino\Modules\Files\Store::read( $appData );
check( 'an index that was edited by hand is read for what it has to be: an entry with a bad id, a bad group, a missing folder, a bad extension, a size that is no whole number or a maker that is none is left out - and what is left reads as strings, bools and ints',
	array_keys( $read['folders'] ) === [ 'aaaaaaaaaaaa' ] && $read['folders']['aaaaaaaaaaaa'] === [ 'group' => 'x', 'name' => 'Ok', 'upload' => false, 'created' => '' ]
	&& array_keys( $read['files'] ) === [ 'cccccccccccc' ] && $read['files']['cccccccccccc']['visible'] === false && $read['files']['cccccccccccc']['by'] === [ 'kind' => 'member', 'id' => 'dddddddddddd' ] && $read['files']['cccccccccccc']['mime'] === 'audio/mpeg' );
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [] );

// The index is parsed once for the state it is in: a page asks for a folder and for a file once for each file it draws. What was made of a state is kept with it, in the app data of the request, and a state that is not the one before is parsed anew
$kept = (string) ( new \ReflectionClassConstant( \Nino\Modules\Files\Store::class, 'PARSED' ) )->getValue();
$folderOf = static fn( string $group ): array => [ 'group' => $group, 'name' => 'Ordner', 'upload' => false, 'created' => '' ];
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [ 'folders' => [ 'aaaaaaaaaaaa' => $folderOf( 'one' ) ], 'files' => [] ] );
$parsedFirst = \Nino\Modules\Files\Store::read( $appData );
$appData[$kept]['index']['folders']['bbbbbbbbbbbb'] = $folderOf( 'marked' );
$parsedAgain = \Nino\Modules\Files\Store::read( $appData );
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [ 'folders' => [ 'cccccccccccc' => $folderOf( 'two' ) ], 'files' => [] ] );
$parsedChanged = \Nino\Modules\Files\Store::read( $appData );
check( 'an index is parsed once for the state it is in: the second read of the same state is the one that was kept (it holds what was put into the kept copy), and a read after the index was written has the new state and nothing of the kept one',
	array_keys( $parsedFirst['folders'] ) === [ 'aaaaaaaaaaaa' ] && array_keys( $parsedAgain['folders'] ) === [ 'aaaaaaaaaaaa', 'bbbbbbbbbbbb' ] && array_keys( $parsedChanged['folders'] ) === [ 'cccccccccccc' ] );
unset( $appData[$kept] );
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [] );

// An id of twelve digits is a whole number as a php array key: everything that walks the index has to read it back as the id it is
$digitFolder = '123456789012';
$digitOther = '234567890123';
$digitFile = '345678901234';
$digitRecord = [ 'folder' => $digitFolder, 'name' => 'x.mp3', 'ext' => 'mp3', 'size' => 3, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '2026-01-01T00:00:00Z' ];
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [ 'folders' => [ $digitFolder => [ 'group' => 'digits', 'name' => 'A', 'upload' => false, 'created' => '' ], $digitOther => [ 'group' => 'digits', 'name' => 'B', 'upload' => true, 'created' => '' ] ],
	'files' => [ $digitFile => $digitRecord ] ] );
file_put_contents( (string) \Nino\Modules\Files\Store::bytes( $appData, $digitFile, 'mp3' ), 'abc' );
$digitFolders = array_column( \Nino\Modules\Files::folders( $appData, 'digits' ), 'id' );
$digitFiles = array_column( \Nino\Modules\Files::files( $appData, $digitFolder ), 'id' );
$digitMark = \Nino\Modules\Files\Store::markUpload( $appData, $digitFolder, true );
$digitMoved = \Nino\Modules\Files\Store::changeFile( $appData, $digitFile, [ 'folder' => $digitOther, 'visible' => false ] );
$digitAdded = \Nino\Modules\Files\Store::addFile( $appData, '456789012345', [ 'folder' => $digitOther ] + $digitRecord, 0 );
$digitFolderGone = \Nino\Modules\Files\Store::deleteFolder( $appData, $digitOther );
check( 'an id of twelve digits, which php makes a whole number of as an array key, is read back as the id it is: listed, marked, moved, added and deleted with its files',
	$digitFolders === [ $digitFolder, $digitOther ] && $digitFiles === [ $digitFile ] && is_array( $digitMark ) && $digitMark['id'] === $digitFolder && $digitMark['upload'] === true
	&& is_array( $digitMoved ) && $digitMoved['file']['folder'] === $digitOther && $digitMoved['before']['folder'] === $digitFolder && is_array( $digitAdded ) && $digitAdded['id'] === '456789012345'
	&& is_array( $digitFolderGone ) && $digitFolderGone['folder']['id'] === $digitOther && array_column( $digitFolderGone['files'], 'id' ) === [ $digitFile, '456789012345' ] && is_file( (string) \Nino\Modules\Files\Store::bytes( $appData, $digitFile, 'mp3' ) ) === false );
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [] );

echo "\n";


// --- The versions of a title - read from the names ------------------------------------
//
// Title.NN.ext: the version is one to three digits between two dots, after a title and before an extension. Pure functions: nothing is looked up

$read = static fn( string $name ): array => \Nino\Modules\Files::version( $name );

check( 'a name that says a version is read as title, the digits as written and the number: TobiaRegner-Istillburn.03.wav',
	$read( 'TobiaRegner-Istillburn.03.wav' ) === [ 'title' => 'TobiaRegner-Istillburn', 'version' => '03', 'number' => 3 ] );
check( '...one, two and three digits are a version, four are not - the number is the integer of them, and a version of nothing but zeros is the number 0',
	$read( 'Song.1.mp3' )['number'] === 1 && $read( 'Song.12.mp3' )['number'] === 12 && $read( 'Song.123.mp3' ) === [ 'title' => 'Song', 'version' => '123', 'number' => 123 ]
	&& $read( 'Song.1234.mp3' ) === [ 'title' => 'Song.1234', 'version' => '', 'number' => null ] && $read( 'Song.0.mp3' )['number'] === 0 && $read( 'Song.000.mp3' ) === [ 'title' => 'Song', 'version' => '000', 'number' => 0 ] );
check( '...a name with no extension after the digits - Song.03 - says none, and its title is what stands before the last dot',
	$read( 'Song.03' ) === [ 'title' => 'Song', 'version' => '', 'number' => null ] && $read( 'Song' ) === [ 'title' => 'Song', 'version' => '', 'number' => null ] );
check( '...the extension is read in any case: Song.03.MP3 is the version 03 of Song',
	$read( 'Song.03.MP3' ) === [ 'title' => 'Song', 'version' => '03', 'number' => 3 ] );
check( '...a title with dots keeps them: the last version before the extension is the one - Kunde-Titel.final.03.wav is the title Kunde-Titel.final, and Song.03.04.mp3 the title Song.03',
	$read( 'Kunde-Titel.final.03.wav' )['title'] === 'Kunde-Titel.final' && $read( 'Song.03.04.mp3' ) === [ 'title' => 'Song.03', 'version' => '04', 'number' => 4 ] );
check( '...nothing but digits, a leading version and a doubled dot are no version the way they are: 123.mp3, .03.mp3, Song..mp3 and Song.a1.mp3 are titles of their own',
	$read( '123.mp3' ) === [ 'title' => '123', 'version' => '', 'number' => null ] && $read( '.03.mp3' )['version'] === '' && $read( 'Song..mp3' )['version'] === '' && $read( 'Song.a1.mp3' )['version'] === ''
	&& $read( 'Notizen.txt' ) === [ 'title' => 'Notizen', 'version' => '', 'number' => null ] );
check( '...markup, a bracket and a null byte in a name are text: they stay in the title as they are and make no version of their own',
	$read( 'a<b>.01.mp3' ) === [ 'title' => 'a<b>', 'version' => '01', 'number' => 1 ] && $read( 'x[[/a/b/c/d]].02.mp3' )['title'] === 'x[[/a/b/c/d]]' && $read( "Song\0.1.mp3" )['title'] === "Song\0" && $read( "Song.1\0.mp3" )['version'] === ''
	&& $read( '.1.mp3' )['version'] === '' && $read( ' .1.mp3' )['version'] === '' );

$entry = static fn( string $name, string $created ): array => [ 'id' => substr( md5( $name. $created ), 0, 12 ), 'name' => $name, 'ext' => strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) ), 'created' => $created ];
$titles = \Nino\Modules\Files::grouped( [
	$entry( 'Song.03.mp3', '2026-10-05T10:00:00Z' ), $entry( 'Notizen.txt', '2026-10-04T10:00:00Z' ), $entry( 'Song.03.wav', '2026-10-03T10:00:00Z' ),
	$entry( 'Song.01.mp3', '2026-10-01T10:00:00Z' ), $entry( 'Song.02.mp3', '2026-10-02T10:00:00Z' ),
] );
$view = static fn( array $titles ): array => array_map( static fn( array $title ): string => $title['title']. '.'. $title['ext']. ': '. implode( ',', array_column( $title['versions'], 'name' ) ), $titles );

check( 'grouped puts the versions of a title together, the highest number first, and the titles in the order of their newest file',
	$view( $titles ) === [ 'Song.mp3: Song.03.mp3,Song.02.mp3,Song.01.mp3', 'Notizen.txt: Notizen.txt', 'Song.wav: Song.03.wav' ] );
check( '...Song.03.wav and Song.03.mp3 are two titles - the extension is part of what makes files one title - and a file keeps what it was given, with version and number added',
	count( $titles ) === 3 && $titles[2]['versions'][0]['name'] === 'Song.03.wav' && $titles[0]['versions'][0]['version'] === '03' && $titles[0]['versions'][0]['number'] === 3 && $titles[0]['versions'][0]['id'] === $entry( 'Song.03.mp3', '2026-10-05T10:00:00Z' )['id']
	&& $titles[1]['versions'] === [ $entry( 'Notizen.txt', '2026-10-04T10:00:00Z' ) + [ 'version' => '', 'number' => null ] ] );
check( '...a file without a version is a title of its own with one version that has no number',
	$titles[1]['title'] === 'Notizen' && count( $titles[1]['versions'] ) === 1 && $titles[1]['versions'][0]['number'] === null );

$same = \Nino\Modules\Files::grouped( [ $entry( 'Song.02.mp3', '2026-10-01T10:00:00Z' ), $entry( 'Song.02.mp3', '2026-10-09T10:00:00Z' ), $entry( 'Song.mp3', '2026-10-20T10:00:00Z' ), $entry( 'Song.10.mp3', '2026-09-01T10:00:00Z' ) ] );
check( '...two files of the same number: the one that came later first; a file with no version goes after the numbered ones, though it is the newest; ten is above two',
	count( $same ) === 1 && array_column( $same[0]['versions'], 'created' ) === [ '2026-09-01T10:00:00Z', '2026-10-09T10:00:00Z', '2026-10-01T10:00:00Z', '2026-10-20T10:00:00Z' ] );
check( '...the title with the newest file is first even when it is listed last, and of two titles with a file of the very same time the one listed first stays first',
	$view( \Nino\Modules\Files::grouped( [ $entry( 'A.01.mp3', '2026-10-01T10:00:00Z' ), $entry( 'B.01.mp3', '2026-10-01T10:00:00Z' ), $entry( 'C.01.mp3', '2026-10-02T10:00:00Z' ) ] ) ) === [ 'C.mp3: C.01.mp3', 'A.mp3: A.01.mp3', 'B.mp3: B.01.mp3' ]
	&& \Nino\Modules\Files::grouped( [] ) === [] );
$orderShown = \Nino\Modules\Files::grouped( [ $entry( 'A.mp3', '2026-10-20T10:00:00Z' ), $entry( 'A.01.mp3', '2026-10-01T10:00:00Z' ), $entry( 'B.01.mp3', '2026-10-10T10:00:00Z' ) ] );
$goneFirst = [ $entry( 'C.02.mp3', '2026-10-30T10:00:00Z' ) + [ 'gone' => '2026-11-01T00:00:00Z' ], $entry( 'C.01.mp3', '2026-10-02T10:00:00Z' ), $entry( 'D.01.mp3', '2026-10-05T10:00:00Z' ) ];
check( '...the titles are in the order of the day of the version the list shows - the highest number: a title with a younger file that has no number comes after one whose shown version is younger - and where that version was removed from the server, of the next one',
	$view( $orderShown ) === [ 'B.mp3: B.01.mp3', 'A.mp3: A.01.mp3,A.mp3' ] && array_column( \Nino\Modules\Files::grouped( $goneFirst ), 'title' ) === [ 'D', 'C' ] );
check( '...the case of a title counts as it stands - Song.01.mp3 and song.02.mp3 are two titles - and a title with a null byte is not the title before it',
	count( \Nino\Modules\Files::grouped( [ $entry( 'Song.01.mp3', '2026-10-01T10:00:00Z' ), $entry( 'song.02.mp3', '2026-10-02T10:00:00Z' ), $entry( "Song\0.01.mp3", '2026-10-03T10:00:00Z' ) ] ) ) === 3 );

// --- The download ---------------------------------------------------------------

echo "GET /.files/<id> - the file the kernel streams\n";

// Three members: two of the group the folder belongs to, one of another. Each is signed in once and keeps the
// session of the browser they signed in with, to be put back when the test speaks as them
$ada = \Nino\Modules\Members::add( $appData, 'ada@example.org', 'Ada Lovelace', 'staff' );
$bob = \Nino\Modules\Members::add( $appData, 'bob@example.org', 'Bob', 'guests' );
$cyd = \Nino\Modules\Members::add( $appData, 'cyd@example.org', 'Cyd', 'staff' );
$sessions = [];

foreach( [ 'ada' => $ada, 'bob' => $bob, 'cyd' => $cyd ] as $who => $account ) {
	filesSignIn( $appData, $account );
	$sessions[$who] = $_SESSION;
}

/**
 *	Speak as somebody: the session of the browser they signed in with, or none
 *
 *	@param		string		$who					ada, bob, cyd or '' for a visitor
 *
 *	@return 	void
 */
function filesAs( string $who ): void {

	$_SESSION = $who === '' ? [] : $GLOBALS['sessions'][$who];
	unset( $GLOBALS['appData']['./nino/auth/current'] );
}

$signedIn = [];

foreach( [ 'ada' => $ada, 'bob' => $bob, 'cyd' => $cyd ] as $who => $account ) {
	filesAs( $who );
	$signedIn[$who] = ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $account['id'];
}

filesVisitor();
check( 'the three members are signed in, each in a session of their own', array_filter( $signedIn ) === $signedIn && count( $signedIn ) === 3 && \Nino\Modules\Members::current( $appData ) === null );

$song = \Nino\Modules\Files\Store::folder( $appData, $staff['id'] );
$content = str_repeat( 'ABCDEFGHIJ', 300 );
\Nino\Filesystem::putFileContent( $appData, '/data/files.php', [] );
$room = \Nino\Modules\Files\Store::addFolder( $appData, 'staff', 'Room' );
$lobby = \Nino\Modules\Files\Store::addFolder( $appData, 'guests', 'Lobby' );
$track = filesMake( $appData, $room['id'], "M\u{00fc}ller\\..\\Demo \"One\".mp3", $content );
$secret = filesMake( $appData, $room['id'], 'Secret.mp3', 'HIDDEN-BYTES', [ 'kind' => 'owner', 'id' => '' ], false );
$cydsOwn = filesMake( $appData, $room['id'], 'Cyds.mp3', 'CYD-BYTES', [ 'kind' => 'member', 'id' => $cyd['id'] ], false );
$pack = filesMake( $appData, $room['id'], 'Pack.zip', 'PK-BYTES', [ 'kind' => 'owner', 'id' => '' ], true, 'application/zip' );
$guestFile = filesMake( $appData, $lobby['id'], 'Guest.mp3', 'GUEST-BYTES' );

check( 'a name is cleaned when a file is made: no path, no quote that was in front of a separator, the extension in lower case', $track['name'] === 'Demo "One".mp3' && \Nino\Modules\Files\Store::cleanName( 'a.MP3' ) === [ 'a.mp3', 'mp3' ] );

$notFound = [];
$answers = [];

foreach( [ 'a visitor' => '', 'a member of another group' => 'bob' ] as $kind => $who ) {
	filesAs( $who );
	$request = filesServe( $appData, '/.files/'. $track['id'] );
	$answers[$kind] = $request['/nino/http/response'];
}

filesAs( 'ada' );

foreach( [ 'an id nobody has' => '/.files/ffffffffffff', 'something that is no id' => '/.files/..%2f..%2fetc%2fpasswd', 'an id with a path behind it' => '/.files/'. $track['id']. '/x', 'the id of a file in the index whose bytes are gone' => '/.files/'. $guestFile['id'] ] as $kind => $uri )
	$answers[$kind] = filesServe( $appData, $uri )['/nino/http/response'];

$visitorAnswer = $answers['a visitor'];
$same = array_filter( $answers, static fn( array $answer ): bool => $answer['statusCode'] === $visitorAnswer['statusCode'] && $answer['body'] === $visitorAnswer['body'] && $answer['header']['Content-Type'] === $visitorAnswer['header']['Content-Type']
	&& array_key_exists( 'file', $answer ) === false && array_key_exists( 'Content-Disposition', $answer['header'] ) === false );
check( 'a visitor, a member of another group, an id nobody has, something that is no id, an id with a path behind it and a file whose bytes are gone are the same 404 with a plain body - nothing tells that an id exists',
	$visitorAnswer['statusCode'] === 404 && $visitorAnswer['body'] === 'Not Found' && str_starts_with( $visitorAnswer['header']['Content-Type'], 'text/plain' ) && $visitorAnswer['header']['Cache-Control'] === 'no-store' && count( $same ) === count( $answers ) );

// The owner's workbench session: with the permission of the panel it is answered with any file that has bytes, a hidden one and one of
// another group included - without it, an account at the workbench is a stranger. Both accounts are made here, the panel's tests use them below
filesVisitor();
ninoUser( $appData, 'admin@example.com' );
ninoUser( $appData, 'plain@example.com', [], 'plain password' );
filesWorkbench( 'admin' );
$ownerAnswers = [];

foreach( [ 'a visible file of a group' => $track, 'a hidden file of the owner' => $secret, 'a hidden file of a member' => $cydsOwn, 'a file of another group' => $guestFile ] as $kind => $each )
	$ownerAnswers[$kind] = filesServe( $appData, '/.files/'. $each['id'] )['/nino/http/response'];

$ownerSaved = filesServe( $appData, '/.files/'. $track['id']. '?download=1' )['/nino/http/response'];
$ownerNothing = [ 'an id nobody has' => filesServe( $appData, '/.files/ffffffffffff' )['/nino/http/response'], 'something that is no id' => filesServe( $appData, '/.files/..%2f..%2fetc%2fpasswd' )['/nino/http/response'] ];
$ownerAllowed = array_map( static fn( array $each ): bool => \Nino\Modules\Files::allowed( $GLOBALS['appData'], $each ), [ $track, $secret, $guestFile ] );
$ownerBrowser = \Nino\Html::renderHtml( $appData, '[files-browser]' );
filesWorkbench( 'plain' );
$plainAnswers = [ 'a visible file of a group' => filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response'], 'a hidden file of the owner' => filesServe( $appData, '/.files/'. $secret['id'] )['/nino/http/response'] ];
filesVisitor();
$expected = [ 'a visible file of a group' => $track, 'a hidden file of the owner' => $secret, 'a hidden file of a member' => $cydsOwn, 'a file of another group' => $guestFile ];
check( 'the workbench session of an account that holds the permission of the panel is answered with the file named for the kernel - a hidden file of the owner, a hidden file of a member and a file of another group among them',
	array_keys( $ownerAnswers ) === array_keys( $expected ) && array_filter( $ownerAnswers, static fn( array $answer, string $kind ): bool => $answer['statusCode'] !== 200 || ( $answer['file'] ?? '' ) !== \Nino\Modules\Files::path( $GLOBALS['appData'], $GLOBALS['expected'][$kind]['id'] )
		|| $answer['header']['Content-Type'] !== 'audio/mpeg' || $answer['header']['Cache-Control'] !== 'private, no-store' || str_starts_with( $answer['header']['Content-Disposition'], 'inline;' ) === false, ARRAY_FILTER_USE_BOTH ) === [] );
check( '...with ?download=1 it is saved, as for a member - and an id nobody has, or that is none, is the 404 of the others',
	$ownerSaved['statusCode'] === 200 && str_starts_with( $ownerSaved['header']['Content-Disposition'], 'attachment; filename="Demo _One_.mp3"; filename*=' ) === true && $ownerSaved['file'] === $ownerAnswers['a visible file of a group']['file']
	&& array_filter( $ownerNothing, static fn( array $answer ): bool => $answer['statusCode'] !== 404 || $answer['body'] !== 'Not Found' || array_key_exists( 'file', $answer ) === true ) === [] );
check( 'an account at the workbench without the permission of the panel is a stranger: the same 404 as a visitor gets, for a visible file and for a hidden one',
	array_filter( $plainAnswers, static fn( array $answer ): bool => $answer['statusCode'] !== $GLOBALS['visitorAnswer']['statusCode'] || $answer['body'] !== $GLOBALS['visitorAnswer']['body'] || $answer['header']['Content-Type'] !== $GLOBALS['visitorAnswer']['header']['Content-Type']
		|| $answer['header']['Cache-Control'] !== $GLOBALS['visitorAnswer']['header']['Cache-Control'] || array_key_exists( 'file', $answer ) === true || array_key_exists( 'Content-Disposition', $answer['header'] ) === true ) === [] && count( $plainAnswers ) === 2 );
// The permission is the panel's own: held exactly, or by the parent of it ('/_admin/files/*') or from above ('/_admin/*') - another panel's is none
$byPermission = [];

foreach( [ 'files-manage@example.com' => [ '/_admin/files/manage' ], 'files-all@example.com' => [ '/_admin/files/*' ], 'admin-all@example.com' => [ '/_admin/*' ], 'hello-only@example.com' => [ '/_admin/hello/manage' ] ] as $mail => $held ) {
	\Nino\Auth::insertUser( $appData, $mail, 'a long password', $held );
	filesVisitor();
	\Nino\Auth::loginUser( $appData, $mail, 'a long password' );
	$byPermission[$mail] = filesServe( $appData, '/.files/'. $secret['id'] )['/nino/http/response']['statusCode'];
}

filesVisitor();
check( 'the permission is the panel\'s own - held exactly, by the parent \'/_admin/files/*\' or from above by \'/_admin/*\' - and the permission of another panel is none',
	$byPermission === [ 'files-manage@example.com' => 200, 'files-all@example.com' => 200, 'admin-all@example.com' => 200, 'hello-only@example.com' => 404 ] );
check( 'allowed() stays the rule for a member: the owner\'s workbench session is allowed nothing by it, and the browser component shows that session nothing - the route answers the owner on its own', $ownerAllowed === [ false, false, false ] && $ownerBrowser === '' );

filesAs( 'ada' );
$streamed = filesServe( $appData, '/.files/'. $track['id'] );
$response = $streamed['/nino/http/response'];
check( 'a member of the group is answered with the file named for the kernel: its absolute path, the type of the index, shown in the browser for audio, the name twice, kept out of every cache but the browser\'s',
	$response['statusCode'] === 200 && $response['file'] === \Nino\Modules\Files::path( $appData, $track['id'] ) && $response['header']['Content-Type'] === 'audio/mpeg'
	&& $response['header']['Content-Disposition'] === 'inline; filename="Demo _One_.mp3"; filename*=UTF-8\'\'Demo%20%22One%22.mp3' && $response['header']['Cache-Control'] === 'private, no-store' && $response['header']['X-Content-Type-Options'] === 'nosniff' );
check( '...a name with nothing in it is given a plain name of its own, and the type decides what is shown', str_starts_with( \Nino\Modules\Files::disposition( '', true ), 'inline; filename="file";' ) === true && str_starts_with( \Nino\Modules\Files::disposition( ' ', false ), 'attachment; filename="file";' ) === true );
check( '...the bytes are the kernel\'s to send: the handler reads none and sets no length, no range and no body', array_key_exists( 'Content-Length', $response['header'] ) === false && array_key_exists( 'Accept-Ranges', $response['header'] ) === false && ( $response['body'] ?? '' ) === '' );

$saved = filesServe( $appData, '/.files/'. $track['id']. '?download=1' )['/nino/http/response'];
$other = filesServe( $appData, '/.files/'. $track['id']. '?download=0' )['/nino/http/response'];
$zip = filesServe( $appData, '/.files/'. $pack['id'] )['/nino/http/response'];
check( 'with ?download=1 the file is always saved, and a type that is not audio, video, an image or pdf is saved without asking - no other value of download is read', str_starts_with( $saved['header']['Content-Disposition'], 'attachment; filename="Demo _One_.mp3"; filename*=' ) === true
	&& $saved['file'] === $response['file'] && str_starts_with( $zip['header']['Content-Disposition'], 'attachment;' ) === true && $zip['header']['Content-Type'] === 'application/zip' && str_starts_with( $other['header']['Content-Disposition'], 'inline;' ) === true );

$umlaut = filesMake( $appData, $room['id'], "M\u{00fc}ller \u{2013} Mix.mp3", 'UMLAUT' );
$umlautResponse = filesServe( $appData, '/.files/'. $umlaut['id'] )['/nino/http/response'];
check( 'a name with letters outside ASCII has its plain form cut down and its UTF-8 form percent-encoded (filename*, RFC 5987)', str_contains( $umlautResponse['header']['Content-Disposition'], 'filename="M_ller _ Mix.mp3"' ) === true
	&& str_contains( $umlautResponse['header']['Content-Disposition'], "filename*=UTF-8''M%C3%BCller%20%E2%80%93%20Mix.mp3" ) === true && preg_match( '/[^\x20-\x7e]/', $umlautResponse['header']['Content-Disposition'] ) !== 1 );

foreach( [ 'image/svg+xml' => false, 'image/png' => true, 'video/mp4' => true, 'application/pdf' => true, 'text/plain' => false, 'application/zip' => false, 'audio/ogg' => true ] as $type => $inline )
	$shown[$type] = \Nino\Modules\Files\Store::inline( $type ) === $inline;

check( 'audio, video, images and pdf are shown, everything else is saved - and a vector image is saved, since it runs scripts of its own', array_filter( $shown, static fn( bool $same ): bool => $same === false ) === [] && count( $shown ) === 7 );

// Hidden: nobody sees it but the member who put it there
filesAs( 'ada' );
$hiddenForAda = filesServe( $appData, '/.files/'. $secret['id'] )['/nino/http/response']['statusCode'];
$cydsForAda = filesServe( $appData, '/.files/'. $cydsOwn['id'] )['/nino/http/response']['statusCode'];
filesAs( 'cyd' );
$cydsForCyd = filesServe( $appData, '/.files/'. $cydsOwn['id'] );
$secretForCyd = filesServe( $appData, '/.files/'. $secret['id'] )['/nino/http/response']['statusCode'];
check( 'a hidden file is a 404 for a member of the group - and answered for the member who uploaded it, who always sees their own; the owner\'s hidden file is nobody\'s',
	$hiddenForAda === 404 && $cydsForAda === 404 && $cydsForCyd['/nino/http/response']['statusCode'] === 200 && $cydsForCyd['/nino/http/response']['file'] === \Nino\Modules\Files::path( $appData, $cydsOwn['id'] ) && $secretForCyd === 404 );

// ...and the uploader keeps it when the group changes
\Nino\Modules\Members::change( $appData, $cyd['id'], [ 'group' => 'guests' ] );
$afterMove = filesServe( $appData, '/.files/'. $cydsOwn['id'] )['/nino/http/response']['statusCode'];
$afterMoveOther = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
\Nino\Modules\Members::change( $appData, $cyd['id'], [ 'group' => 'staff' ] );
check( 'the uploader keeps their own file when they have left the group, and loses the files of the group', $afterMove === 200 && $afterMoveOther === 404 );

// The API says the same as the route
$rule = [];
foreach( [ 'ada' => 'ada', 'bob' => 'bob', 'cyd' => 'cyd', 'a visitor' => '' ] as $kind => $who ) {
	filesAs( $who );
	$rule[$kind] = array_map( static fn( array $file ): bool => \Nino\Modules\Files::allowed( $GLOBALS['appData'], $file ), [ $track, $secret, $cydsOwn, $guestFile ] );
}
check( 'allowed() is the rule of the route for the member who is signed in: the group, the visibility, the uploader - and nobody at all for a visitor',
	$rule === [ 'ada' => [ true, false, false, false ], 'bob' => [ false, false, false, true ], 'cyd' => [ true, false, true, false ], 'a visitor' => [ false, false, false, false ] ] );

// The hook '/files/allowed': asked when the rule would say yes, with the file, the member, whether the request is a download and allowed (true); it can take the file away, never give it
$asked = [];
$hookNo = [];
$hook = static function( array &$appData, array &$args ) use ( &$asked, &$hookNo ): void {
	$asked[] = [ $args['file']['id'], $args['member']['id'], $args['download'], $args['allowed'] ];

	if( in_array( $args['file']['id'], $hookNo, true ) === true )
		$args['allowed'] = false;
};
\Nino\Callbacks::registerCallback( $appData, '/files/allowed', $hook );

filesAs( 'ada' );
$viewed = filesServe( $appData, '/.files/'. $track['id'] );
$saved = filesServe( $appData, '/.files/'. $track['id']. '?download=1' );
check( 'the hook is fired when the rule says yes, with the file, the member, whether it is a download - false for the view, true for ?download=1 - and allowed true; the answer stays a 200', $viewed['/nino/http/response']['statusCode'] === 200 && $saved['/nino/http/response']['statusCode'] === 200
	&& $asked === [ [ $track['id'], $ada['id'], false, true ], [ $track['id'], $ada['id'], true, true ] ] );
$asked = [];
check( '...allowed() fires it with download false by default and takes it as the third argument', \Nino\Modules\Files::allowed( $appData, $track ) === true && \Nino\Modules\Files::allowed( $appData, $track, true ) === true && $asked === [ [ $track['id'], $ada['id'], false, true ], [ $track['id'], $ada['id'], true, true ] ] );
filesListingChecks( $appData, $track );
$asked = [];
filesAs( 'bob' );
$notHis = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
filesAs( '' );
$visitor = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
check( '...a no of the rule is not asked about: another group, a visitor - the hook is not fired and the answer is the 404 it was', $notHis === 404 && $visitor === 404 && $asked === [] );

$hookNo = [ $track['id'] ];
filesAs( 'ada' );
$asked = [];
$taken = filesServe( $appData, '/.files/'. $track['id'] );
$takenSaved = filesServe( $appData, '/.files/'. $track['id']. '?download=1' );
check( 'a callback that sets allowed to false takes the file away: the route answers the 404 of a stranger, inline and as a download, with no file named - and allowed() says the same', $taken['/nino/http/response']['statusCode'] === 404 && $takenSaved['/nino/http/response']['statusCode'] === 404 && isset( $taken['/nino/http/response']['file'] ) === false && ( $taken['/nino/http/response']['body'] ?? '' ) === 'Not Found'
	&& \Nino\Modules\Files::allowed( $appData, $track ) === false && \Nino\Modules\Files::allowed( $appData, $track, true ) === false );
$listed = array_map( static fn( array $file ): string => $file['id'], array_filter( \Nino\Modules\Files::files( $appData, $track['folder'] ), static fn( array $file ): bool => \Nino\Modules\Files::allowed( $appData, $file ) === true ) );
check( '...everything that asks the rule gets the same answer: the files a member may see no longer hold it', in_array( $track['id'], $listed, true ) === false );
filesWorkbench( 'admin' );
$asOwner = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
filesWorkbench( '' );
check( '...the owner\'s workbench session is not asked: the permission of the panel opens the file whatever the hook says', $asOwner === 200 );

// ...and a callback never gives: true is no yes - not for a file the rule refuses, not after another callback said no
$hookNo = [];
$yes = static function( array &$appData, array &$args ): void {
	$args['allowed'] = true;
};
\Nino\Callbacks::registerCallback( $appData, '/files/allowed', $yes );
filesAs( 'bob' );
$bobYes = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
filesAs( 'ada' );
$hookNo = [ $track['id'] ];
$afterNo = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
$hookNo = [];
$adaYes = filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'];
check( 'a callback that sets allowed to true gives nothing: a member of another group still has the 404, a no of an earlier callback stays one, and a file that was allowed is allowed all the same', $bobYes === 404 && $afterNo === 404 && $adaYes === 200 );
\Nino\Callbacks::removeCallbacks( $appData, '/files/allowed' );
$veto = static fn( array &$appData, array &$args ): bool => false;
\Nino\Callbacks::registerCallback( $appData, '/files/allowed', $veto );
filesAs( 'ada' );
check( 'a callback that answers false - the veto of the kernel\'s callbacks - takes the file away as one that sets allowed to false does: 404 for the view and the download, allowed() says no', filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'] === 404 && filesServe( $appData, '/.files/'. $track['id']. '?download=1' )['/nino/http/response']['statusCode'] === 404 && \Nino\Modules\Files::allowed( $appData, $track ) === false );
\Nino\Callbacks::removeCallbacks( $appData, '/files/allowed' );
filesAs( 'ada' );
check( '...and with no callback the rule is the answer, as before', filesServe( $appData, '/.files/'. $track['id'] )['/nino/http/response']['statusCode'] === 200 && \Nino\Modules\Files::allowed( $appData, $track ) === true );
check( 'the manual of the feature names the hook with its four arguments, in both languages', isset( $manifest['manual']['callbacks']['/files/allowed'] ) === true && array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => array_filter( [ 'file', 'member', 'download', 'allowed' ], static fn( string $name ): bool => str_contains( (string) $manifest['manual']['callbacks']['/files/allowed'][$locale], $name ) === false ) !== [] ) === [] );

// The kernel's part, pinned once: a regression of the file response shows here
filesAs( 'ada' );
$finalize = new ReflectionMethod( '\Nino\Http', '_finalizeResponse' );
$whole = filesServe( $appData, '/.files/'. $track['id'] );
$finalize->invokeArgs( null, [ &$whole ] );
$ranged = filesServe( $appData, '/.files/'. $track['id'], 'GET', [], true, [], [ 'HTTP_RANGE' => 'bytes=1000-1999' ] );
$finalize->invokeArgs( null, [ &$ranged ] );
$headed = filesServe( $appData, '/.files/'. $track['id'], 'HEAD' );
$finalize->invokeArgs( null, [ &$headed ] );
check( 'the kernel answers the file named: the whole of it with its length and Accept-Ranges, a 206 for one range with the Content-Range of the part, and the headers of the handler stand',
	$whole['/nino/http/response']['statusCode'] === 200 && $whole['/nino/http/response']['header']['Content-Length'] === (string) strlen( $content ) && $whole['/nino/http/response']['header']['Accept-Ranges'] === 'bytes'
	&& $whole['/nino/http/response']['header']['Content-Type'] === 'audio/mpeg' && str_starts_with( $whole['/nino/http/response']['header']['Content-Disposition'], 'inline;' ) === true
	&& $whole['./nino/http/stream'] === [ 'path' => $response['file'], 'start' => 0, 'length' => strlen( $content ) ]
	&& $ranged['/nino/http/response']['statusCode'] === 206 && $ranged['/nino/http/response']['header']['Content-Range'] === 'bytes 1000-1999/'. strlen( $content ) && $ranged['/nino/http/response']['header']['Content-Length'] === '1000'
	&& $ranged['./nino/http/stream'] === [ 'path' => $response['file'], 'start' => 1000, 'length' => 1000 ] && $headed['/nino/http/response']['statusCode'] === 200 );

// A file that is in the index and not on the disk is a 404, the same one
unlink( (string) \Nino\Modules\Files::path( $appData, $guestFile['id'] ) );
filesAs( 'bob' );
$gone = filesServe( $appData, '/.files/'. $guestFile['id'] )['/nino/http/response'];
filesWorkbench( 'admin' );
$goneForOwner = filesServe( $appData, '/.files/'. $guestFile['id'] )['/nino/http/response'];
check( 'a file whose bytes are gone is the 404 of the others, not an error - for the owner too', $gone['statusCode'] === 404 && $gone['body'] === 'Not Found' && array_key_exists( 'file', $gone ) === false
	&& $goneForOwner['statusCode'] === 404 && $goneForOwner['body'] === 'Not Found' && array_key_exists( 'file', $goneForOwner ) === false );
filesVisitor();

// A file removed from the server: the bytes go, the row stays with the time they went
$removable = filesMake( $appData, $room['id'], 'Remove Me.mp3', 'REMOVE-BYTES' );
$removablePath = (string) \Nino\Modules\Files::path( $appData, $removable['id'] );
$usedBefore = \Nino\Modules\Files\Store::used( $appData, 'staff' );
$removed = \Nino\Modules\Files\Store::removeBytes( $appData, $removable['id'] );
$removedEntry = is_array( $removed ) === true ? $removed['file'] : [];
check( 'removeBytes unlinks the bytes and keeps the row: the time they went is in gone, the name, the size, the sha256, who put it there, whether it is visible and when it came are as they were - and the answer says the entry changed',
	is_array( $removed ) === true && $removed['changed'] === true && is_file( $removablePath ) === false && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) ( $removedEntry['gone'] ?? '' ) ) === 1
	&& array_diff_key( $removedEntry, [ 'gone' => 1 ] ) === $removable && filesIndex( $appData )['files'][$removable['id']] === array_diff_key( $removedEntry, [ 'id' => 1 ] ) );
check( '...the API carries it - file() and files() have gone, path() is null for a file that was removed, and a file that was not has no gone at all',
	\Nino\Modules\Files::file( $appData, $removable['id'] ) === $removedEntry && in_array( $removedEntry, \Nino\Modules\Files::files( $appData, $room['id'] ), true ) === true && \Nino\Modules\Files::path( $appData, $removable['id'] ) === null
	&& array_key_exists( 'gone', \Nino\Modules\Files::file( $appData, $track['id'] ) ?? [] ) === false );
$removedIndex = filesIndex( $appData );
$removedAgain = \Nino\Modules\Files\Store::removeBytes( $appData, $removable['id'] );
check( '...removing it again changes nothing - the same entry with its first time, the index not rewritten',
	is_array( $removedAgain ) === true && $removedAgain['changed'] === false && $removedAgain['file'] === $removedEntry && filesIndex( $appData ) === $removedIndex );
file_put_contents( $removablePath, 'LEFT-OVER' );
$strayPath = \Nino\Modules\Files\Store::path( $appData, $removable['id'] );
$finished = \Nino\Modules\Files\Store::removeBytes( $appData, $removable['id'] );
check( '...bytes put back under its name are no path while the entry says it was removed, and the next remove unlinks them - which is how a remove that could not unlink them is finished',
	$strayPath === null && is_array( $finished ) === true && $finished['changed'] === false && is_file( $removablePath ) === false && \Nino\Modules\Files\Store::path( $appData, $removable['id'] ) === null );
check( 'an id nobody has, or that is none, is "unknown" and writes nothing', \Nino\Modules\Files\Store::removeBytes( $appData, 'ffffffffffff' ) === 'unknown' && \Nino\Modules\Files\Store::removeBytes( $appData, '../etc/passwd' ) === 'unknown' && filesIndex( $appData ) === $removedIndex );
check( 'the files of a group that were removed take no room: used() counts the bytes that are there', \Nino\Modules\Files\Store::used( $appData, 'staff' ) === $usedBefore - strlen( 'REMOVE-BYTES' ) );

$handmade = filesIndex( $appData );
$handmade['files'][$track['id']]['gone'] = 12345;
$handmade['files'][$secret['id']]['gone'] = '';
$handmade['files'][$cydsOwn['id']]['gone'] = '2026-03-04T05:06:07Z';
$parsed = \Nino\Modules\Files\Store::parse( $handmade );
check( 'an index that was edited by hand is read for gone as for the rest: a string that is something is the time, a number and an empty string are none, and the entry is left in for all three',
	array_key_exists( 'gone', $parsed['files'][$track['id']] ) === false && array_key_exists( 'gone', $parsed['files'][$secret['id']] ) === false && $parsed['files'][$cydsOwn['id']]['gone'] === '2026-03-04T05:06:07Z' );

// The route: 410 for whoever may have the file, the 404 of the others for everybody else
$sentence = trim( (string) \Nino\Html::resolveTextfill( $appData, '/feature/files/download/removed' ) );
$removedAnswers = [];

foreach( [ 'a member of the group' => 'ada', 'a member of another group' => 'bob', 'a visitor' => '' ] as $kind => $who ) {
	filesAs( $who );
	$removedAnswers[$kind] = filesServe( $appData, '/.files/'. $removable['id'] )['/nino/http/response'];
}

filesVisitor();
filesWorkbench( 'admin' );
$removedAnswers['the workbench with the permission'] = filesServe( $appData, '/.files/'. $removable['id'] )['/nino/http/response'];
$removedAnswers['the workbench saving it'] = filesServe( $appData, '/.files/'. $removable['id']. '?download=1' )['/nino/http/response'];
filesWorkbench( 'plain' );
$removedAnswers['the workbench without the permission'] = filesServe( $appData, '/.files/'. $removable['id'] )['/nino/http/response'];
filesVisitor();
$isGone = static fn( array $answer ): bool => $answer['statusCode'] === 410 && $answer['body'] === $GLOBALS['sentence'] && $GLOBALS['sentence'] !== '' && array_key_exists( 'file', $answer ) === false && array_key_exists( 'Content-Disposition', $answer['header'] ) === false
	&& str_starts_with( $answer['header']['Content-Type'], 'text/plain' ) === true && $answer['header']['Cache-Control'] === 'no-store';
$isStranger = static fn( array $answer ): bool => $answer['statusCode'] === 404 && $answer['body'] === 'Not Found' && array_key_exists( 'file', $answer ) === false;
check( 'a file that was removed is answered 410 with the sentence of the words, as a plain text no cache keeps, to a member who may have it and to the workbench with the permission - ?download=1 too - and with no file named for the kernel',
	$isGone( $removedAnswers['a member of the group'] ) && $isGone( $removedAnswers['the workbench with the permission'] ) && $isGone( $removedAnswers['the workbench saving it'] ) );
check( '...a member of another group, a visitor and an account at the workbench without the permission get the 404 of every other id: nothing tells them that it was ever there',
	$isStranger( $removedAnswers['a member of another group'] ) && $isStranger( $removedAnswers['a visitor'] ) && $isStranger( $removedAnswers['the workbench without the permission'] ) );

filesAs( 'ada' );
$browserHtml = \Nino\Html::renderHtml( $appData, '[files-browser]' );
filesVisitor();
check( '...and the browser of the group does not list it', str_contains( $browserHtml, $track['id'] ) === true && str_contains( $browserHtml, $removable['id'] ) === false );

$told = [];
$deletedRemoved = \Nino\Modules\Files\Store::deleteFile( $appData, $removable['id'] );
check( 'a removed file can still be deleted whole - the row goes, nothing is left to unlink', is_array( $deletedRemoved ) === true && $deletedRemoved === $removedEntry && \Nino\Modules\Files::file( $appData, $removable['id'] ) === null );

echo "\n";

// --- The upload - the owner's, through the panel ----------------------------------

echo "Modules\\Files\\Upload - the owner's upload in chunks, through the panel action files/upload\n";

// A set-up project with a plain account and a full-access one, the way the wizard leaves them
$appData['/nino/install/completed'] = true;
filesVisitor();
filesWorkbench( 'admin' );

/**
 *	One chunk through the panel: the file part is set the way php sets it and the action is called as the workbench calls it
 *
 *	@param		array 		&$appData
 *	@param		array 		$fields				folder, name, size, upload, index, count and, for the owner, visible
 *	@param		array 		$part					The entry of $_FILES
 *
 *	@return 	array										[ status, body, request ]
 */
function filesOwnerSend( array &$appData, array $fields, array $part ): array {

	$_FILES = [ 'chunk' => $part ];
	$answer = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $fields, 'files/upload' );
	$_FILES = [];

	return $answer;
}

/**
 *	One chunk through the route of the members, as the script posts it
 *
 *	@param		array 		&$appData
 *	@param		array 		$fields
 *	@param		array 		$part
 *
 *	@return 	array										[ status, body, request ]
 */
function filesMemberSend( array &$appData, array $fields, array $part ): array {

	// A post carries strings, whatever the script means by them
	$request = filesServe( $appData, '/.files/upload', 'POST', array_map( static fn( mixed $value ): mixed => is_int( $value ) === true ? (string) $value : $value, $fields ), true, [ 'chunk' => $part ] );

	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request ];
}

/**
 *	A file posted in chunks, in order, the way the script does it: the answer of one carries the upload id of the next
 *
 *	@param		array 		&$appData
 *	@param		string		$send					The function that posts a chunk: filesOwnerSend or filesMemberSend
 *	@param		string		$name
 *	@param		string		$bytes
 *	@param		array 		$extra				More fields, folder among them
 *
 *	@return 	array										The answers [ status, body, request ] of the chunks that were posted - it stops at the first that is not a 200
 */
function filesUpload( array &$appData, string $send, string $name, string $bytes, array $extra = [] ): array {

	$chunk = \Nino\Modules\Files\Store::chunkBytes( $appData );
	$count = max( 1, (int) ceil( strlen( $bytes ) / $chunk ) );
	$upload = '';
	$answers = [];

	for( $index = 0; $index < $count; $index++ ) {

		$answer = $send( $appData, $extra + [ 'name' => $name, 'size' => strlen( $bytes ), 'upload' => $upload, 'index' => $index, 'count' => $count ], filesPart( $appData, substr( $bytes, $index * $chunk, $chunk ) ) );
		$answers[] = $answer;

		if( $answer[0] !== 200 )
			break;

		$upload = (string) ( $answer[1]['upload'] ?? '' );
	}

	return $answers;
}

/**
 *	A file posted in chunks of a size of the script's choosing (a script that found the web server takes less than the setting): the first chunk sets
 *	the chunk of the upload
 *
 *	@param		array 		&$appData
 *	@param		string		$send					filesOwnerSend or filesMemberSend
 *	@param		string		$name
 *	@param		string		$bytes
 *	@param		int				$chunk				The bytes of a chunk
 *	@param		array 		$extra				More fields, folder among them
 *
 *	@return 	array										The answers [ status, body, request ] - it stops at the first that is not a 200
 */
function filesUploadIn( array &$appData, string $send, string $name, string $bytes, int $chunk, array $extra = [] ): array {

	$count = max( 1, (int) ceil( strlen( $bytes ) / $chunk ) );
	$upload = '';
	$answers = [];

	for( $index = 0; $index < $count; $index++ ) {

		$answer = $send( $appData, $extra + [ 'name' => $name, 'size' => strlen( $bytes ), 'upload' => $upload, 'index' => $index, 'count' => $count ], filesPart( $appData, substr( $bytes, $index * $chunk, $chunk ) ) );
		$answers[] = $answer;

		if( $answer[0] !== 200 )
			break;

		$upload = (string) ( $answer[1]['upload'] ?? '' );
	}

	return $answers;
}

/**
 *	@param		array 		&$appData
 *
 *
 *	@return 	array										The upload parts that lie in the directory, by name (the .chunk files beside them are filesKept()'s)
 */
function filesParts( array &$appData ): array {

	return array_map( 'basename', glob( \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/*.part' ) ?: [] );
}

/**
 *	@param		array 		&$appData
 *
 *	@return 	array										The files that keep the length of a first chunk beside the parts, by name
 */
function filesKept( array &$appData ): array {

	return array_map( 'basename', glob( \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/*.chunk' ) ?: [] );
}

/**
 *	@param		string		$path
 *
 *	@return 	int											The size of a file as it is now - php keeps the size it saw last, and a part grows between two looks
 */
function filesSize( string $path ): int {

	clearstatcache( true, $path );

	return (int) @filesize( $path );
}

// The setting chunk is in megabytes: one, which php takes here (upload_max_filesize is 2M in a bare cli)
$block = '';
for( $each = 0; $each < 64; $each++ )
	$block .= hash( 'sha512', (string) $each, true );
$mp3 = "\xFF\xFB\x90\x00". substr( str_repeat( $block, 700 ), 0, 2499996 );
$chunkBytes = 1048576;

check( 'the settings of the test are the ones the plan names, changed through the kernel: a chunk of one megabyte, a file of at most five',
	\Nino\Features::saveSettings( $appData, 'files', [ 'chunk' => 1, 'maxSize' => 5 ] ) === [] && \Nino\Modules\Files\Store::chunkBytes( $appData ) === $chunkBytes && \Nino\Modules\Files\Store::maxBytes( $appData ) === 5 * $chunkBytes
	&& \Nino\Modules\Files\Store::quotaBytes( $appData ) === 0 && strlen( $mp3 ) === 2500000 );

// Denied: the action is the panel's
filesWorkbench( '' );
$denied = [ 'signed out' => ninoCall( $appData, $panel, [], 'files/list' ), 'without the permission' => null ];
filesWorkbench( 'plain' );
$denied['without the permission'] = ninoCall( $appData, $panel, [], 'files/list' );
filesWorkbench( 'admin' );

$posts = [ 'files/list' => [], 'files/folder/add' => [ 'group' => 'staff', 'name' => 'x' ], 'files/folder/save' => [ 'id' => 'ffffffffffff', 'name' => 'x' ], 'files/folder/delete' => [ 'id' => 'ffffffffffff' ],
	'files/folder/upload-target' => [ 'id' => 'ffffffffffff' ], 'files/upload' => [ 'folder' => $room['id'], 'name' => 'a.mp3', 'size' => 1, 'upload' => '', 'index' => 0, 'count' => 1 ], 'files/save' => [ 'id' => 'ffffffffffff', 'visible' => true ],
	'files/notify' => [ 'ids' => [ 'ffffffffffff' ] ], 'files/delete' => [ 'id' => 'ffffffffffff' ], 'files/remove' => [ 'id' => 'ffffffffffff' ] ];
$refused = [ 'signed out' => [], 'without the permission' => [] ];
$before = filesIndex( $appData );

foreach( $posts as $action => $data ) {
	filesWorkbench( '' );
	$refused['signed out'][$action] = ninoCall( $appData, $panel, $data, $action )[0];
	filesWorkbench( 'plain' );
	$refused['without the permission'][$action] = ninoCall( $appData, $panel, $data, $action )[0];
}

filesWorkbench( 'admin' );
check( 'every action refuses a visitor with 401 and an account without the permission with 403, and writes nothing', array_values( array_unique( $refused['signed out'] ) ) === [ 401 ] && array_values( array_unique( $refused['without the permission'] ) ) === [ 403 ]
	&& count( $refused['signed out'] ) === 10 && $denied['signed out'][1]['code'] === 'session' && filesIndex( $appData ) === $before && filesParts( $appData ) === [] );
check( '...and the actions are the ten there are - none of them downloads, that is the route\'s - all under the slug of the panel', array_keys( array_filter( \Nino\Admin\Admin::actions( $appData ), static fn( string $action ): bool => str_starts_with( $action, 'files/' ), ARRAY_FILTER_USE_KEY ) )
	=== [ 'files/list', 'files/folder/add', 'files/folder/save', 'files/folder/delete', 'files/folder/upload-target', 'files/upload', 'files/save', 'files/notify', 'files/delete', 'files/remove' ] );

// Three chunks of a known file give one entry
$told = [];
$owner = filesUpload( $appData, 'filesOwnerSend', 'Take One.MP3', $mp3, [ 'folder' => $room['id'] ] );
$uploaded = $owner[2][1] ?? [];
$entry = \Nino\Modules\Files::file( $appData, (string) ( $uploaded['id'] ?? '' ) );
check( 'three chunks of a known file are answered in order - the first with the upload id (16 hex characters), the second with the same, the last with the id, the name and the size of the file',
	array_column( $owner, 0 ) === [ 200, 200, 200 ] && preg_match( '/^[a-f0-9]{16}$/D', $owner[0][1]['upload'] ) === 1 && $owner[0][1] === [ 'upload' => $owner[0][1]['upload'], 'index' => 0 ] && $owner[1][1] === [ 'upload' => $owner[0][1]['upload'], 'index' => 1 ]
	&& array_keys( $uploaded ) === [ 'id', 'name', 'size', 'mailed' ] && $uploaded['name'] === 'Take One.mp3' && $uploaded['size'] === 2500000 && preg_match( '/^[a-f0-9]{12}$/D', $uploaded['id'] ) === 1 );
check( '...the entry has the right size, sha256, MIME type and name, is the owner\'s, visible and in the folder, and the bytes on disk are the fixture',
	$entry !== null && $entry['size'] === 2500000 && $entry['sha256'] === hash( 'sha256', $mp3 ) && $entry['mime'] === 'audio/mpeg' && $entry['name'] === 'Take One.mp3' && $entry['ext'] === 'mp3'
	&& $entry['by'] === [ 'kind' => 'owner', 'id' => '' ] && $entry['visible'] === true && $entry['folder'] === $room['id'] && hash_file( 'sha256', (string) \Nino\Modules\Files::path( $appData, $entry['id'] ) ) === hash( 'sha256', $mp3 )
	&& file_get_contents( (string) \Nino\Modules\Files::path( $appData, $entry['id'] ) ) === $mp3 );
check( '...the upload leaves no part behind, the callback /files/add is told once with the entry, and nothing is told as a member\'s upload', filesParts( $appData ) === [] && $told === [ [ '/files/add', $entry ] ]
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $entry['created'] ) === 1 );

// While it is under way the part is on the disk and as long as the chunks that came
$told = [];
$first = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => 3 ], filesPart( $appData, substr( $mp3, 0, $chunkBytes ) ) );
$partFile = \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/'. ( $first[1]['upload'] ?? 'x' ). '.part';
check( 'while an upload is under way, its part lies in /data/files/.upload and is as long as the chunks that came', $first[0] === 200 && is_file( $partFile ) === true && filesSize( $partFile ) === $chunkBytes && $told === [] );

// Out of order, twice, short, long and gone
$upload = $first[1]['upload'];
$outOfOrder = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => $upload, 'index' => 2, 'count' => 3 ], filesPart( $appData, substr( $mp3, 2 * $chunkBytes ) ) );
$twice = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => $upload, 'index' => 0, 'count' => 3 ], filesPart( $appData, substr( $mp3, 0, $chunkBytes ) ) );
$long = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => $upload, 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, $chunkBytes + 1 ) ) );
$short = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => $upload, 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, 1000 ) ) );
$unknownUpload = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => 'aaaaaaaaaaaaaaaa', 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, $chunkBytes ) ) );
$badUpload = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => '../../x', 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, $chunkBytes ) ) );
check( 'a chunk out of order, a first chunk that names an upload, a chunk that is longer or shorter than its place asks, one of an upload that is not there and one that names a path are all 400 files_chunk - and the part stays as it was',
	array_values( array_unique( array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ), [ $outOfOrder, $twice, $long, $short, $unknownUpload, $badUpload ] ) ) ) === [ '400 files_chunk' ] && filesSize( $partFile ) === $chunkBytes && filesParts( $appData ) === [ basename( $partFile ) ] );

// A part that was cut on the disk between two chunks
$filesBefore = count( filesIndex( $appData )['files'] );
file_put_contents( $partFile, 'cut', LOCK_EX );
$cut = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Second.mp3', 'size' => strlen( $mp3 ), 'upload' => $upload, 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, $chunkBytes ) ) );
check( 'a part that is not as long as the chunks before it should have made it - cut on the disk - is 400 files_chunk as well, and no file comes of it', $cut[0] === 400 && $cut[1]['code'] === 'files_chunk' && count( filesIndex( $appData )['files'] ) === $filesBefore );
unlink( $partFile );

// The stale parts: older than a day they are gone when the next upload starts
$stale = \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/0123456789abcdef.part';
$fresh = \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/fedcba9876543210.part';
file_put_contents( $stale, 'old' );
file_put_contents( $fresh, 'new' );
touch( $stale, time() - 86400 - 60 );
touch( $fresh, time() - 86400 + 600 );
$starting = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Third.mp3', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => 3 ], filesPart( $appData, substr( $mp3, 0, $chunkBytes ) ) );
check( 'the next upload that starts removes the parts that are older than 24 hours and leaves the others', is_file( $stale ) === false && is_file( $fresh ) === true && $starting[0] === 200 && count( filesParts( $appData ) ) === 2 );
unlink( $fresh );
unlink( \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/'. $starting[1]['upload']. '.part' );
\Nino\Modules\Files\Store::dropParts( $appData );

// The rest of the protocol: the numbers
$numbers = [];
foreach( [ 'a count that does not fit the size' => [ 'size' => strlen( $mp3 ), 'count' => 2, 'index' => 0 ], 'an index past the count' => [ 'size' => strlen( $mp3 ), 'count' => 3, 'index' => 3 ],
	'a size of nothing' => [ 'size' => 0, 'count' => 1, 'index' => 0 ], 'a size that is no number' => [ 'size' => 'a lot', 'count' => 1, 'index' => 0 ], 'a negative index' => [ 'size' => 5, 'count' => 1, 'index' => -1 ],
	'a list for the count' => [ 'size' => 5, 'count' => [ 3 ], 'index' => 0 ] ] as $kind => $fields ) {
	$answer = filesOwnerSend( $appData, $fields + [ 'folder' => $room['id'], 'name' => 'Numbers.mp3', 'upload' => '' ], filesPart( $appData, 'abcde' ) );
	$numbers[$kind] = $answer[0]. ' '. ( $answer[1]['code'] ?? '' );
}
check( 'numbers that do not fit - a count that is not what the size asks for, an index past the count, a size of nothing or of a word, a negative index, a list - are 400 files_chunk, and no part is made', array_values( array_unique( $numbers ) ) === [ '400 files_chunk' ] && filesParts( $appData ) === [] );

// ...a size of thirty digits is no number, and a count that would let the last chunk grow past the chunk of the setting is refused though the first chunk is the length the count asks for
$digits = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Digits.mp3', 'size' => str_repeat( '9', 30 ), 'upload' => '', 'index' => 0, 'count' => 1 ], filesPart( $appData, 'abcde' ) );
$growing = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Growing.mp3', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => 2 ], filesPart( $appData, substr( $mp3, 0, $chunkBytes ) ) );
check( '...a size of thirty digits is no number, and a count that lets the last chunk grow past the chunk of the setting is refused though the first chunk fits it - both 400 files_chunk, no part made', $digits[0] === 400 && $digits[1]['code'] === 'files_chunk'
	&& $growing[0] === 400 && $growing[1]['code'] === 'files_chunk' && filesParts( $appData ) === [] );

// An upload id that is a path to a file that is there: that file is no part, and it is left as it is
$stray = \Nino\Filesystem::path( $appData, '/data/files' ). '/stray.part';
file_put_contents( $stray, str_repeat( 's', $chunkBytes ) );
$viaPath = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Path.mp3', 'size' => strlen( $mp3 ), 'upload' => '../stray', 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, $chunkBytes ) ) );
check( 'an upload id that is a path to a file that is there is refused all the same - 400 files_chunk - and the file is not touched', $viaPath[0] === 400 && $viaPath[1]['code'] === 'files_chunk' && filesSize( $stray ) === $chunkBytes && file_get_contents( $stray ) === str_repeat( 's', $chunkBytes ) );
unlink( $stray );

// A name that is a list is no name, and raises no warning
ninoWarnings();
$listName = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => [ 'x.mp3' ], 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], filesPart( $appData, 'abcde' ) );
check( 'a name that is a list is no name: 400 files_type, and no warning', $listName[0] === 400 && $listName[1]['code'] === 'files_type' && ninoWarnings() === [] && filesParts( $appData ) === [] );

// A chunk whose bytes are fewer than its part says (the part php stored is cut short): a first one leaves no part, a later one leaves the part as long as it was
$partsDir = \Nino\Filesystem::path( $appData, '/data/files/.upload' );
$lying = filesPart( $appData, 'only ten b' );
$lying['size'] = 100;
$lieFirst = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Lie.txt', 'size' => 100, 'upload' => '', 'index' => 0, 'count' => 1 ], $lying );
check( 'a chunk with fewer bytes than its part says is a 500 files_write, and a first chunk leaves no part behind', $lieFirst[0] === 500 && $lieFirst[1]['code'] === 'files_write' && filesParts( $appData ) === [] );

$honest = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Lie.mp3', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => 3 ], filesPart( $appData, substr( $mp3, 0, $chunkBytes ) ) );
$lyingSecond = filesPart( $appData, 'only ten b' );
$lyingSecond['size'] = $chunkBytes;
$lieSecond = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Lie.mp3', 'size' => strlen( $mp3 ), 'upload' => $honest[1]['upload'], 'index' => 1, 'count' => 3 ], $lyingSecond );
$lieFile = $partsDir. '/'. $honest[1]['upload']. '.part';
check( '...a later chunk like that leaves the part as long as it was, so that the same chunk can be sent again', $lieSecond[0] === 500 && $lieSecond[1]['code'] === 'files_write' && filesSize( $lieFile ) === $chunkBytes );
$again = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Lie.mp3', 'size' => strlen( $mp3 ), 'upload' => $honest[1]['upload'], 'index' => 1, 'count' => 3 ], filesPart( $appData, substr( $mp3, $chunkBytes, $chunkBytes ) ) );
check( '...and it is taken when it comes whole', $again[0] === 200 && filesSize( $lieFile ) === 2 * $chunkBytes );
unlink( $lieFile );
unlink( $partsDir. '/'. $honest[1]['upload']. '.chunk' );
\Nino\Modules\Files\Store::dropParts( $appData );

// The directory of the parts is a file: nothing can be put there, and that is a 500 files_write - the directory is made again when the file is gone
rmdir( $partsDir );
file_put_contents( $partsDir, 'in the way' );
$blocked = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Blocked.txt', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], filesPart( $appData, 'abcde' ) );
unlink( $partsDir );
$unblocked = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Unblocked.txt', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], filesPart( $appData, 'abcde' ) );
check( 'where the directory of the parts cannot be made the upload is a 500 files_write - and it works again once the way is free', $blocked[0] === 500 && $blocked[1]['code'] === 'files_write' && $unblocked[0] === 200 );
\Nino\Modules\Files\Store::deleteFile( $appData, (string) ( $unblocked[1]['id'] ?? '' ) );

// The last step does not trust the size it was told: a part that is not as long as the file says is refused and removed
$assemble = new ReflectionMethod( '\Nino\Modules\Files\Upload', '_assemble' );
$oddPart = $partsDir. '/0123456789abcdef.part';
file_put_contents( $oddPart, 'ten bytes!' );
$filesNow = count( filesIndex( $appData )['files'] );
$assembled = $assemble->invokeArgs( null, [ &$appData, $room, $oddPart, [ 'name' => 'odd.txt', 'ext' => 'txt', 'size' => 20 ], [ 'kind' => 'owner', 'id' => '' ], true, 0 ] );
check( 'the last step of an upload does not trust the size it was told: a part that is not as long as the file says is 400 files_chunk, the part is removed and no file comes of it', ( $assembled['fail'][0] ?? 0 ) === 400 && ( $assembled['fail'][2] ?? '' ) === 'files_chunk'
	&& is_file( $oddPart ) === false && count( filesIndex( $appData )['files'] ) === $filesNow );

// A folder that is deleted while the last chunk is on its way: the file is not kept
$bytesNow = count( glob( $bytesDir. '/*' ) ?: [] );
$shortLived = \Nino\Modules\Files\Store::addFolder( $appData, 'staff', 'Short-lived' );
$staleFolder = \Nino\Modules\Files\Store::folder( $appData, $shortLived['id'] );
\Nino\Modules\Files\Store::deleteFolder( $appData, $shortLived['id'] );
$lateAnswer = \Nino\Modules\Files\Upload::receive( $appData, $staleFolder, [ 'name' => 'late.txt', 'size' => '6', 'upload' => '', 'index' => '0', 'count' => '1' ], filesPart( $appData, "Notes\n" ), [ 'kind' => 'owner', 'id' => '' ], true );
check( 'a folder that is deleted while the last chunk is on its way: the file is refused as a 404 files_folder, and neither its bytes nor its part are kept', ( $lateAnswer['fail'][0] ?? 0 ) === 404 && ( $lateAnswer['fail'][2] ?? '' ) === 'files_folder'
	&& count( glob( $bytesDir. '/*' ) ?: [] ) === $bytesNow && filesParts( $appData ) === [] );

// What the name says
$filesBefore = count( filesIndex( $appData )['files'] );
$names = [];
foreach( [ 'virus.exe', 'noextension', '.mp3', 'photo.mp3.exe', '', 'archive.tar.gz', 'double..' ] as $name ) {
	$answer = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => $name, 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], filesPart( $appData, 'abcde' ) );
	$names[$name] = $answer[0]. ' '. ( $answer[1]['code'] ?? '' ). ' '. ( $answer[1]['field'] ?? '' );
}
check( 'an extension outside the types, a name with none, one that is only an extension, and a name that is empty are 400 files_type with the field name - and nothing is made', array_values( array_unique( $names ) ) === [ '400 files_type name' ] && filesParts( $appData ) === []
	&& count( filesIndex( $appData )['files'] ) === $filesBefore );

// The setting types: a dot, blanks and capitals are forgiven, an extension the feature has no rule for is not taken, and none is taken twice
$defaultTypes = $manifest['settings']['types']['default'];
\Nino\Features::saveSettings( $appData, 'files', [ 'types' => [ '.MP3', ' wav ', 'exe', 'mp3', 'Flac', 'x y' ] ] );
$typesRead = \Nino\Modules\Files\Store::types( $appData );
check( 'the types are read as the setting says: a dot, blanks and capitals forgiven, an extension the feature has no rule for left out, and none twice', $typesRead === [ 'mp3', 'wav', 'flac' ] );

// ...and an extension the feature knows but the setting does not list is refused at the first chunk, before a part is made
\Nino\Features::saveSettings( $appData, 'files', [ 'types' => [ 'mp3' ] ] );
$notListed = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Song.wav', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => 3 ], filesPart( $appData, substr( $mp3, 0, $chunkBytes ) ) );
\Nino\Features::saveSettings( $appData, 'files', [ 'types' => $defaultTypes ] );
check( '...an extension the feature knows but the setting types does not list is 400 files_type at the first chunk, with the field name - and no part is made', $notListed[0] === 400 && $notListed[1]['code'] === 'files_type' && $notListed[1]['field'] === 'name' && filesParts( $appData ) === [] );

$dirty = filesUpload( $appData, 'filesOwnerSend', "..\\..\\evil\\sub/dir/Track\x07\x00 Two.MP3", "\xFF\xFB\x90\x00". str_repeat( 'z', 50 ), [ 'folder' => $room['id'] ] );
$dirtyEntry = \Nino\Modules\Files::file( $appData, (string) ( $dirty[0][1]['id'] ?? '' ) );
check( 'a name with a path and control characters is cleaned: the last segment, without the characters, the extension in lower case - and the file is kept under the id, not under the name', $dirty[0][0] === 200 && ( $dirtyEntry['name'] ?? '' ) === 'Track Two.mp3' && $dirtyEntry['ext'] === 'mp3'
	&& basename( (string) \Nino\Modules\Files::path( $appData, $dirtyEntry['id'] ) ) === $dirtyEntry['id']. '.mp3' );

$longName = filesUpload( $appData, 'filesOwnerSend', str_repeat( 'n', 300 ). '.MP3', "\xFF\xFB\x90\x00". str_repeat( 'z', 50 ), [ 'folder' => $room['id'] ] );
$longEntry = \Nino\Modules\Files::file( $appData, (string) ( $longName[0][1]['id'] ?? '' ) );
check( 'a name of more than 160 characters is cut to 160 with the extension kept whole', mb_strlen( $longEntry['name'] ?? '' ) === 160 && str_ends_with( $longEntry['name'] ?? '', 'nnn.mp3' ) === true );
check( 'the cleaning, on its own: nothing but the last segment, no control or format character that turns a name around, an extension of one to eight letters and digits', \Nino\Modules\Files\Store::cleanName( "a/b\\c.PDF" ) === [ 'c.pdf', 'pdf' ]
	&& \Nino\Modules\Files\Store::cleanName( "evil\u{202e}fdp.exe" ) === [ 'evilfdp.exe', 'exe' ] && \Nino\Modules\Files\Store::cleanName( 'x.verylongext' ) === [ '', '' ] && \Nino\Modules\Files\Store::cleanName( '...' ) === [ '', '' ]
	&& \Nino\Modules\Files\Store::cleanName( "bad\xffbyte.mp3" )[1] === 'mp3' && mb_check_encoding( \Nino\Modules\Files\Store::cleanName( "bad\xffbyte.mp3" )[0], 'UTF-8' ) === true
	&& \Nino\Modules\Files\Store::cleanName( '..hidden.mp3' ) === [ 'hidden.mp3', 'mp3' ] && \Nino\Modules\Files\Store::cleanName( '. .mp3' ) === [ '', '' ] );

// Sniffed: what the bytes are decides
$filesBefore = count( filesIndex( $appData )['files'] );
$bytesBefore = count( glob( $bytesDir. '/*' ) ?: [] );
$fake = filesUpload( $appData, 'filesOwnerSend', 'archive.zip', "this is plain text, no zip\n", [ 'folder' => $room['id'] ] );
$php = filesUpload( $appData, 'filesOwnerSend', 'photo.jpg', "<?php echo 'x';", [ 'folder' => $room['id'] ] );
$html = filesUpload( $appData, 'filesOwnerSend', 'song.mp3', '<!DOCTYPE html><html><body>x</body></html>', [ 'folder' => $room['id'] ] );
check( 'a .zip whose bytes are a text file, a .jpg that is php and an .mp3 that is a page are 400 files_type - sniffed by finfo from the bytes - the part is removed and no file, no bytes and no entry is left',
	array_values( array_unique( array_map( static fn( array $answers ): string => $answers[0][0]. ' '. $answers[0][1]['code'], [ $fake, $php, $html ] ) ) ) === [ '400 files_type' ] && filesParts( $appData ) === [] && count( filesIndex( $appData )['files'] ) === $filesBefore && count( glob( $bytesDir. '/*' ) ?: [] ) === $bytesBefore );

$text = filesUpload( $appData, 'filesOwnerSend', 'notes.txt', "Notes\nof the day\n", [ 'folder' => $room['id'] ] );
$textEntry = \Nino\Modules\Files::file( $appData, (string) ( $text[0][1]['id'] ?? '' ) );
check( 'a file of one chunk is one request, and a text file is text/plain', count( $text ) === 1 && $text[0][0] === 200 && $textEntry['mime'] === 'text/plain' && $textEntry['size'] === strlen( "Notes\nof the day\n" ) );

// Lines with commas in them: libmagic calls such a text text/csv whatever the name says, and a .txt or an .md of it is what it says
$comma = "a,b,c\n1,2,3\n4,5,6\n";
\Nino\Features::saveSettings( $appData, 'files', [ 'types' => array_merge( $defaultTypes, [ 'md' ] ) ] );
$csvTxt = filesUpload( $appData, 'filesOwnerSend', 'Lines.txt', $comma, [ 'folder' => $room['id'] ] );
$csvMd = filesUpload( $appData, 'filesOwnerSend', 'Lines.md', $comma, [ 'folder' => $room['id'] ] );
\Nino\Features::saveSettings( $appData, 'files', [ 'types' => $defaultTypes ] );
$csvTxtEntry = \Nino\Modules\Files::file( $appData, (string) ( $csvTxt[0][1]['id'] ?? '' ) );
$csvMdEntry = \Nino\Modules\Files::file( $appData, (string) ( $csvMd[0][1]['id'] ?? '' ) );
$commaPath = \Nino\Filesystem::path( $appData, '/data/files' ). '/comma-test.txt';
file_put_contents( $commaPath, $comma );
$commaTypes = [ 'txt' => \Nino\Modules\Files\Store::sniff( $commaPath, 'txt' ), 'md' => \Nino\Modules\Files\Store::sniff( $commaPath, 'md' ) ];
unlink( $commaPath );
check( 'a .txt and an .md of comma-separated lines are taken though finfo may call them text/csv - the type of the entry is the one finfo gave, one the map lists for the extension',
	$csvTxt[0][0] === 200 && $csvMd[0][0] === 200 && in_array( $csvTxtEntry['mime'] ?? '', \Nino\Modules\Files\Store::MIME['txt'], true ) === true && in_array( $csvMdEntry['mime'] ?? '', \Nino\Modules\Files\Store::MIME['md'], true ) === true
	&& $commaTypes['txt'] !== '' && $commaTypes['md'] !== '' && in_array( 'text/csv', \Nino\Modules\Files\Store::MIME['txt'], true ) === true && in_array( 'text/csv', \Nino\Modules\Files\Store::MIME['md'], true ) === true );

// The limits
$tooBig = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Big.mp3', 'size' => 5 * $chunkBytes + 1, 'upload' => '', 'index' => 0, 'count' => 6 ], filesPart( $appData, str_repeat( 'a', $chunkBytes ) ) );
check( 'a file over the largest size is 400 files_size, with the limit in megabytes and the field size, before a part is made', $tooBig[0] === 400 && $tooBig[1]['code'] === 'files_size' && $tooBig[1]['params'] === [ 5 ] && $tooBig[1]['field'] === 'size' && filesParts( $appData ) === [] );
\Nino\Features::saveSettings( $appData, 'files', [ 'maxSize' => 4 ] );
$edge = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Edge.mp3', 'size' => 4 * $chunkBytes, 'upload' => '', 'index' => 0, 'count' => 4 ], filesPart( $appData, str_repeat( 'a', $chunkBytes ) ) );
$over = filesOwnerSend( $appData, [ 'folder' => $room['id'], 'name' => 'Edge.mp3', 'size' => 4 * $chunkBytes + 1, 'upload' => '', 'index' => 0, 'count' => 5 ], filesPart( $appData, str_repeat( 'a', $chunkBytes ) ) );
unlink( \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/'. $edge[1]['upload']. '.part' );
\Nino\Modules\Files\Store::dropParts( $appData );
check( '...a file of exactly the largest size is taken and one byte more is not - the number is the setting\'s', $edge[0] === 200 && $over[0] === 400 && $over[1]['code'] === 'files_size' && $over[1]['params'] === [ 4 ] );

\Nino\Features::saveSettings( $appData, 'files', [ 'maxSize' => 5, 'quota' => 3 ] );
$small = "\xFF\xFB\x90\x00". str_repeat( 'f', 400000 );
$fitting = filesUpload( $appData, 'filesOwnerSend', 'Fits.mp3', $small, [ 'folder' => $room['id'] ] );
$crossing = filesUpload( $appData, 'filesOwnerSend', 'Crosses.mp3', $mp3, [ 'folder' => $room['id'] ] );
$another = filesUpload( $appData, 'filesOwnerSend', 'Another.mp3', $small, [ 'folder' => $room['id'] ] );
$elsewhere = filesUpload( $appData, 'filesOwnerSend', 'Other.mp3', $small, [ 'folder' => $lobby['id'] ] );
check( 'the quota of the group refuses the file that would cross it - 400 files_quota with the quota in megabytes, before a part is made - and takes the one that fits; the other group has its own room',
	$fitting[0][0] === 200 && $crossing[0][0] === 400 && $crossing[0][1]['code'] === 'files_quota' && $crossing[0][1]['params'] === [ 3 ] && $another[0][0] === 400 && $another[0][1]['code'] === 'files_quota' && $elsewhere[0][0] === 200 && filesParts( $appData ) === [] );

// ...and inside the write: the quota is decided where two uploads at once cannot both fit
$members = \Nino\Modules\Files\Store::addFile( $appData, 'abababababab', [ 'folder' => $room['id'], 'name' => 'x.mp3', 'ext' => 'mp3', 'size' => 500000, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '' ], 3 * $chunkBytes );
$nowhere = \Nino\Modules\Files\Store::addFile( $appData, 'acacacacacac', [ 'folder' => 'ffffffffffff', 'name' => 'x.mp3', 'ext' => 'mp3', 'size' => 1, 'mime' => '', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '' ], 0 );
$taken = \Nino\Modules\Files\Store::addFile( $appData, $entry['id'], [ 'folder' => $room['id'], 'name' => 'x.mp3', 'ext' => 'mp3', 'size' => 1, 'mime' => '', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '' ], 0 );
check( 'the store itself refuses a file that crosses the quota, one for a folder that is gone and one under an id in use - and writes none', $members === 'quota' && $nowhere === 'folder' && $taken === 'taken' && isset( filesIndex( $appData )['files']['abababababab'] ) === false );
$usedNow = \Nino\Modules\Files\Store::used( $appData, 'staff' );
$exactFit = \Nino\Modules\Files\Store::addFile( $appData, 'adadadadadad', [ 'folder' => $room['id'], 'name' => 'x.mp3', 'ext' => 'mp3', 'size' => 1, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '' ], $usedNow + 1 );
\Nino\Modules\Files\Store::deleteFile( $appData, 'adadadadadad' );
$room_left = 3 * 1048576 - $usedNow;
$exact = filesUpload( $appData, 'filesOwnerSend', 'Exact.txt', str_repeat( 'x', $room_left ), [ 'folder' => $room['id'] ] );
$oneMore = filesUpload( $appData, 'filesOwnerSend', 'One more.txt', 'x', [ 'folder' => $room['id'] ] );
$exactFull = \Nino\Modules\Files\Store::used( $appData, 'staff' );
\Nino\Modules\Files\Store::deleteFile( $appData, (string) ( end( $exact )[1]['id'] ?? '' ) );
check( 'the quota is a limit one may reach: a file that fills the room of the group exactly is taken - by the upload and by the store - and one byte more is not', is_array( $exactFit ) === true && $room_left > 0 && end( $exact )[0] === 200 && $exactFull === 3 * 1048576
	&& $oneMore[0][0] === 400 && $oneMore[0][1]['code'] === 'files_quota' && \Nino\Modules\Files\Store::used( $appData, 'staff' ) === $usedNow );
\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 0 ] );

// A part that did not arrive is the shell's refusal
$missing = ninoCall( $appData, $panel, [ 'folder' => $room['id'], 'name' => 'a.mp3', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], 'files/upload' );
$_FILES = [ 'chunk' => [ 'name' => 'blob', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0 ] ];
$iniSize = ninoCall( $appData, $panel, [ 'folder' => $room['id'], 'name' => 'a.mp3', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], 'files/upload' );
$_FILES = [ 'chunk' => [ 'name' => 'blob', 'tmp_name' => '', 'error' => UPLOAD_ERR_PARTIAL, 'size' => 0 ] ];
$partial = ninoCall( $appData, $panel, [ 'folder' => $room['id'], 'name' => 'a.mp3', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], 'files/upload' );
$_FILES = [];
check( 'a chunk that did not arrive is answered with the shell\'s codes - none received 400 upload_missing, over the limit of php 413 upload_too_large with the limit, cut off 400 upload_partial - and the field chunk',
	[ $missing[0], $missing[1]['code'], $missing[1]['field'] ] === [ 400, 'upload_missing', 'chunk' ] && [ $iniSize[0], $iniSize[1]['code'] ] === [ 413, 'upload_too_large' ] && $iniSize[1]['params'] === [ \Nino\Admin\Admin::megabytes( \Nino\Images::limits()['php'] ) ]
	&& [ $partial[0], $partial[1]['code'] ] === [ 400, 'upload_partial' ] );

$noFolder = [];
foreach( [ 'a folder nobody has' => 'ffffffffffff', 'something that is no id' => '../x', 'nothing' => '' ] as $kind => $folder ) {
	$answer = filesOwnerSend( $appData, [ 'folder' => $folder, 'name' => 'a.mp3', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 1 ], filesPart( $appData, 'abcde' ) );
	$noFolder[$kind] = $answer[0]. ' '. ( $answer[1]['code'] ?? '' );
}
check( 'a folder nobody has - or an id that is none - is 404 files_folder', array_values( array_unique( $noFolder ) ) === [ '404 files_folder' ] && filesParts( $appData ) === [] );

// The owner may leave the file hidden
$hiddenUpload = filesUpload( $appData, 'filesOwnerSend', 'Draft.mp3', $small, [ 'folder' => $room['id'], 'visible' => false ] );
check( 'with visible false the owner\'s upload is kept from the members: the entry is hidden', \Nino\Modules\Files::file( $appData, (string) ( $hiddenUpload[0][1]['id'] ?? '' ) )['visible'] === false );

echo "\n";


// --- The upload - a member's, through the route -----------------------------------

echo "POST /.files/upload - a member's upload, the plain post and the caps\n";

ninoWarnings();
$before = filesIndex( $appData );
$fields = [ 'name' => 'Member Take.mp3', 'size' => 100, 'upload' => '', 'index' => 0, 'count' => 1 ];
$aimed = [];

// ...the room is not the upload folder of any group yet: nobody gets in
foreach( [ 'a visitor' => '', 'a member of the group before the folder is marked' => 'ada', 'a member of another group' => 'bob' ] as $kind => $who ) {
	filesAs( $who );
	filesFresh( $appData );
	$answer = filesMemberSend( $appData, $fields + [ 'folder' => $room['id'] ], filesPart( $appData, str_repeat( 'a', 100 ) ) );
	$aimed[$kind] = $answer[0]. ' '. ( $answer[1]['code'] ?? '' );
}
check( 'a visitor, a member of the group at a folder that is not marked as the upload folder, and a member of another group are all the same 404 files_folder', array_values( array_unique( $aimed ) ) === [ '404 files_folder' ] && filesIndex( $appData ) === $before && filesParts( $appData ) === [] );

\Nino\Modules\Files\Store::markUpload( $appData, $room['id'], true );
$before = filesIndex( $appData );
$aimed = [];
foreach( [ 'a member of another group' => [ 'bob', $room['id'] ], 'their own group\'s folder that is not the upload folder' => [ 'ada', $staff['id'] ], 'a folder of another group' => [ 'ada', $lobby['id'] ], 'a folder nobody has' => [ 'ada', 'ffffffffffff' ], 'no folder' => [ 'ada', '' ] ] as $kind => [ $who, $folder ] ) {
	filesAs( $who );
	filesFresh( $appData );
	$answer = filesMemberSend( $appData, $fields + [ 'folder' => $folder ], filesPart( $appData, str_repeat( 'a', 100 ) ) );
	$aimed[$kind] = $answer[0]. ' '. ( $answer[1]['code'] ?? '' );
}
filesFresh( $appData );
filesAs( 'ada' );
$list = filesServe( $appData, '/.files/upload', 'POST', [ 'folder' => [ $room['id'] ] ] + array_map( 'strval', $fields ), true, [ 'chunk' => filesPart( $appData, str_repeat( 'a', 100 ) ) ] )['/nino/http/response'];
check( 'once the room is the upload folder: a member of another group, a folder of their own group that is not marked, a folder of another group, one nobody has and none are 404 files_folder - and a list for the folder is no warning but the same answer',
	array_values( array_unique( $aimed ) ) === [ '404 files_folder' ] && $list['statusCode'] === 404 && $list['body']['code'] === 'files_folder' && filesIndex( $appData ) === $before && ninoWarnings() === [] );

filesGrantChecks( $appData, $room, $track, $secret );

// The right member's three chunks
filesAs( 'ada' );
filesFresh( $appData );
$told = [];
$member = filesUpload( $appData, 'filesMemberSend', 'Ada Take.mp3', $mp3, [ 'folder' => $room['id'] ] );
$memberDone = $member[2][1] ?? [];
$memberEntry = \Nino\Modules\Files::file( $appData, (string) ( $memberDone['id'] ?? '' ) );
check( 'the right member\'s three chunks give the entry: the upload id first, with the index of the chunk, then the id, the name and the size', array_column( $member, 0 ) === [ 200, 200, 200 ] && preg_match( '/^[a-f0-9]{16}$/D', $member[0][1]['upload'] ) === 1 && $member[0][1] === [ 'upload' => $member[0][1]['upload'], 'index' => 0 ]
	&& $member[1][1] === [ 'upload' => $member[0][1]['upload'], 'index' => 1 ] && array_keys( $memberDone ) === [ 'id', 'name', 'size' ]
	&& $memberDone['name'] === 'Ada Take.mp3' && $memberDone['size'] === 2500000 );
check( '...by that member, visible, in the folder, with the sha256, the MIME type and the bytes of the fixture - and the callback /files/upload is told once, /files/add not at all',
	$memberEntry !== null && $memberEntry['by'] === [ 'kind' => 'member', 'id' => $ada['id'] ] && $memberEntry['visible'] === true && $memberEntry['folder'] === $room['id'] && $memberEntry['sha256'] === hash( 'sha256', $mp3 ) && $memberEntry['mime'] === 'audio/mpeg'
	&& hash_file( 'sha256', (string) \Nino\Modules\Files::path( $appData, $memberEntry['id'] ) ) === hash( 'sha256', $mp3 ) && $told === [ [ '/files/upload', $memberEntry ] ] && filesParts( $appData ) === [] );
check( '...the member sees it in the route of the download at once, and a member of another group does not', ( function() use ( $memberEntry, &$appData ): bool {
	filesAs( 'ada' );
	$mine = filesServe( $appData, '/.files/'. $memberEntry['id'] )['/nino/http/response']['statusCode'];
	filesAs( 'bob' );
	$theirs = filesServe( $appData, '/.files/'. $memberEntry['id'] )['/nino/http/response']['statusCode'];
	filesAs( 'ada' );
	return $mine === 200 && $theirs === 404;
} )() );

// The rules of the upload hold for a member as they do for the owner
filesAs( 'ada' );
filesFresh( $appData );
$memberRules = [
	'the type' => filesUpload( $appData, 'filesMemberSend', 'virus.exe', 'MZ', [ 'folder' => $room['id'] ] ),
	'the sniffed type' => filesUpload( $appData, 'filesMemberSend', 'fake.zip', 'only text', [ 'folder' => $room['id'] ] ),
	'the size' => filesUpload( $appData, 'filesMemberSend', 'Huge.mp3', str_repeat( 'a', 5 * $chunkBytes + 1 ), [ 'folder' => $room['id'] ] ),
];
check( 'the types, the sniffing and the largest size are held for a member as for the owner: files_type, files_type, files_size', array_map( static fn( array $answers ): string => $answers[0][1]['code'], $memberRules ) === [ 'the type' => 'files_type', 'the sniffed type' => 'files_type', 'the size' => 'files_size' ]
	&& filesParts( $appData ) === [] );

// A part php did not take whole is no chunk, though its size is the size the chunk has to have
$partial = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Partial.txt', 'size' => '5', 'upload' => '', 'index' => '0', 'count' => '1' ], array_replace( filesPart( $appData, 'abcde' ), [ 'error' => UPLOAD_ERR_PARTIAL ] ) );
check( 'a part with an error on it (php took it partly) is 400 files_chunk though its size is the size of the chunk - and nothing is made', $partial[0] === 400 && ( $partial[1]['code'] ?? '' ) === 'files_chunk' && filesParts( $appData ) === [] );

// The csrf token
filesFresh( $appData );
$counterBefore = ninoCounter( $appData, 'files-tries' );
$indexBefore = filesIndex( $appData );
$noToken = filesServe( $appData, '/.files/upload', 'POST', $fields + [ 'folder' => $room['id'] ], false, [ 'chunk' => filesPart( $appData, str_repeat( 'a', 100 ) ) ] )['/nino/http/response'];
$wrongToken = filesServe( $appData, '/.files/upload', 'POST', $fields + [ 'folder' => $room['id'], '_csrf' => 'not-the-token' ], false, [ 'chunk' => filesPart( $appData, str_repeat( 'a', 100 ) ) ] )['/nino/http/response'];
check( 'a request without the csrf token - or with another - is refused with the 403 and the empty body the kernel answers, counts for nothing and writes nothing', [ $noToken['statusCode'], $noToken['body'] ] === [ 403, false ] && [ $wrongToken['statusCode'], $wrongToken['body'] ] === [ 403, false ]
	&& ninoCounter( $appData, 'files-tries' ) === $counterBefore && filesIndex( $appData ) === $indexBefore && filesParts( $appData ) === [] );

// The cap: chunk requests per visitor and hour
\Nino\Features::saveSettings( $appData, 'files', [ 'attempts' => 3 ] );
filesFresh( $appData );
filesVisitor();
$answers = [];
for( $try = 1; $try <= 5; $try++ )
	$answers[] = filesMemberSend( $appData, $fields + [ 'folder' => $room['id'] ], filesPart( $appData, str_repeat( 'a', 100 ) ) );
check( 'the fourth request of a visitor in an hour - the setting attempts says three - is 429 files_limit, a visitor who is no member counted like the rest', array_map( static fn( array $answer ): int => $answer[0], $answers ) === [ 404, 404, 404, 429, 429 ] && $answers[3][1]['code'] === 'files_limit' );

\Nino\Features::saveSettings( $appData, 'files', [ 'attempts' => 300 ] );
filesFresh( $appData );
$statuses = [];
for( $try = 1; $try <= 301; $try++ )
	$statuses[] = filesMemberSend( $appData, $fields + [ 'folder' => $room['id'] ], filesPart( $appData, 'a' ) )[0];
$tries = ninoCounter( $appData, 'files-tries' );
check( 'with the default of 300 the 301st chunk request of a visitor in an hour is 429 files_limit, and the 300 before it were answered', count( array_unique( array_slice( $statuses, 0, 300 ) ) ) === 1 && $statuses[299] === 404 && $statuses[300] === 429 );
check( '...the counter has a folder of its own - not the index - hashed: neither the address of the visitor nor a name is written in it, and the index is what it was', is_dir( \Nino\Filesystem::path( $appData, '/data/.counters/files-tries' ) ) === true
	&& count( $tries ) === 1 && str_contains( implode( array_map( 'file_get_contents', glob( \Nino\Filesystem::path( $appData, '/data/.counters/files-tries' ). '/*.php' ) ?: [] ) ), '127.0.0.1' ) === false && filesIndex( $appData ) === $indexBefore && count( filesIndex( $appData )['files'] ) > 5 );
filesFresh( $appData );

// The chunk of an upload is the one its first chunk had: a script that found the web server takes less than the setting sends smaller chunks
filesAs( 'ada' );
filesFresh( $appData );
$told = [];
$small = filesUploadIn( $appData, 'filesMemberSend', 'Small Parts.mp3', $mp3, 300000, [ 'folder' => $room['id'] ] );
$smallDone = $small[8][1] ?? [];
$smallEntry = \Nino\Modules\Files::file( $appData, (string) ( $smallDone['id'] ?? '' ) );
check( 'an upload in chunks smaller than the setting is taken and assembled: 2500000 bytes in chunks of 300000 are nine requests, the last has what is left, and the entry has the size, the sha256 and the bytes of the file',
	array_column( $small, 0 ) === array_fill( 0, 9, 200 ) && $smallEntry !== null && $smallEntry['size'] === strlen( $mp3 ) && $smallEntry['sha256'] === hash( 'sha256', $mp3 ) && $smallEntry['mime'] === 'audio/mpeg'
	&& file_get_contents( (string) \Nino\Modules\Files::path( $appData, $smallEntry['id'] ) ) === $mp3 && filesParts( $appData ) === [] );

filesWorkbench( 'admin' );
$ownerSmall = filesUploadIn( $appData, 'filesOwnerSend', 'Owner Small.mp3', $mp3, 700000, [ 'folder' => $room['id'] ] );
$ownerSmallEntry = \Nino\Modules\Files::file( $appData, (string) ( $ownerSmall[3][1]['id'] ?? '' ) );
check( '...the owner\'s upload through the panel is the same protocol: chunks of 700000 are four requests and the file is whole', array_column( $ownerSmall, 0 ) === [ 200, 200, 200, 200 ] && $ownerSmallEntry !== null && $ownerSmallEntry['sha256'] === hash( 'sha256', $mp3 ) && filesParts( $appData ) === [] );

filesAs( 'ada' );
filesFresh( $appData );
$tiny = filesUploadIn( $appData, 'filesMemberSend', 'Tiny.txt', 'abc', 1, [ 'folder' => $room['id'] ] );
$tinyEntry = \Nino\Modules\Files::file( $appData, (string) ( $tiny[2][1]['id'] ?? '' ) );
check( '...a chunk can be one byte long: three requests make a file of three bytes', array_column( $tiny, 0 ) === [ 200, 200, 200 ] && $tinyEntry !== null && $tinyEntry['size'] === 3 && $tinyEntry['sha256'] === hash( 'sha256', 'abc' ) );

// The first chunk sets the size: every later one but the last has to be as long
$first = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => 9 ], filesPart( $appData, substr( $mp3, 0, 300000 ) ) );
$partId = (string) ( $first[1]['upload'] ?? '' );
$partFile = \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/'. $partId. '.part';
$other = [];

foreach( [ 'shorter' => 299999, 'longer' => 300001, 'the setting' => 1048576 ] as $kind => $length )
	$other[$kind] = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => 1, 'count' => 9 ], filesPart( $appData, substr( $mp3, 300000, $length ) ) );

$other['count up'] = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => 1, 'count' => 10 ], filesPart( $appData, substr( $mp3, 300000, 300000 ) ) );
$other['count down'] = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => 1, 'count' => 8 ], filesPart( $appData, substr( $mp3, 300000, 300000 ) ) );
$other['last too long'] = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => 8, 'count' => 9 ], filesPart( $appData, substr( $mp3, 2400000, 300000 ) ) );
check( 'a later chunk of another length than the first (shorter, longer, the setting\'s), a count that does not fit the chunk (one more, one less) and a last chunk that is not what is left are all 400 files_chunk - and the part stays as the first chunk made it',
	array_values( array_unique( array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ), $other ) ) ) === [ '400 files_chunk' ] && count( $other ) === 6 && filesSize( $partFile ) === 300000 );

check( '...the length of the first chunk is kept beside the part for as long as the upload is under way', filesKept( $appData ) === [ $partId. '.chunk' ] && file_get_contents( dirname( $partFile ). '/'. $partId. '.chunk' ) === '300000' );

// The chunk of the upload is the first chunk's, not what the part makes of it: a skipped index with a new count is refused
$skip = [
	'an index skipped, with the count of another chunk' => [ 'index' => 2, 'count' => 17, 'bytes' => substr( $mp3, 300000, 150000 ) ],
	'the next index with that count' => [ 'index' => 1, 'count' => 17, 'bytes' => substr( $mp3, 300000, 150000 ) ],
	'an index skipped, as long as the first' => [ 'index' => 2, 'count' => 9, 'bytes' => substr( $mp3, 600000, 300000 ) ],
];
$skipped = [];

foreach( $skip as $kind => $each )
	$skipped[$kind] = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => $each['index'], 'count' => $each['count'] ], filesPart( $appData, $each['bytes'] ) );

check( 'after a first chunk of 300000 bytes an index that is skipped is 400 files_chunk whatever the count and the length it brings (150000 and 17 chunks included) - the part stays 300000 bytes',
	array_values( array_unique( array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ), $skipped ) ) ) === [ '400 files_chunk' ] && filesSize( $partFile ) === 300000 && filesKept( $appData ) === [ $partId. '.chunk' ] );

// Each refusal says what is wrong
$tail = static fn( int $index, int $count, string $bytes, string $upload = '' ): array => filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $upload, 'index' => $index, 'count' => $count ], filesPart( $appData, $bytes ) );
$said = [
	'over the setting' => $tail( 0, 3, substr( $mp3, 0, 1048577 ) ),
	'another size' => $tail( 1, 9, substr( $mp3, 300000, 299999 ), $partId ),
	'out of order' => $tail( 2, 9, substr( $mp3, 600000, 300000 ), $partId ),
	'a count that does not fit' => $tail( 1, 10, substr( $mp3, 300000, 300000 ), $partId ),
	'no such upload' => $tail( 1, 9, substr( $mp3, 300000, 300000 ), 'aaaaaaaaaaaaaaaa' ),
	'a chunk that did not arrive' => filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => 1, 'count' => 9 ], array_replace( filesPart( $appData, substr( $mp3, 300000, 300000 ) ), [ 'error' => UPLOAD_ERR_PARTIAL ] ) ),
];
$errors = array_map( static fn( array $answer ): string => ( $answer[0] === 400 && ( $answer[1]['code'] ?? '' ) === 'files_chunk' ) ? (string) ( $answer[1]['error'] ?? '' ) : '', $said );
check( '...and every refusal has its own sentence, the code staying files_chunk: a first chunk over the setting, a chunk of another size, one out of order, a count that does not fit, an upload that is not there and a chunk that did not arrive whole are six different words',
	count( array_unique( $errors ) ) === 6 && in_array( '', $errors, true ) === false );
$goes = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => $partId, 'index' => 1, 'count' => 9 ], filesPart( $appData, substr( $mp3, 300000, 300000 ) ) );
check( '...and the chunk that fits is taken after all of them', $goes[0] === 200 && filesSize( $partFile ) === 600000 );
@unlink( $partFile );
@unlink( dirname( $partFile ). '/'. $partId. '.chunk' );
\Nino\Modules\Files\Store::dropParts( $appData );

// The last chunk longer than what is left has its own word as well, and the part stays
$lastA = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Last.txt', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 3 ], filesPart( $appData, 'ab' ) );
filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Last.txt', 'size' => 5, 'upload' => $lastA[1]['upload'] ?? '', 'index' => 1, 'count' => 3 ], filesPart( $appData, 'cd' ) );
$lastLong = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Last.txt', 'size' => 5, 'upload' => $lastA[1]['upload'] ?? '', 'index' => 2, 'count' => 3 ], filesPart( $appData, 'efg' ) );
check( '...the last chunk that is longer than what is left has a word of its own, other than the six', $lastLong[0] === 400 && ( $lastLong[1]['code'] ?? '' ) === 'files_chunk' && in_array( (string) ( $lastLong[1]['error'] ?? '' ), $errors, true ) === false && $lastLong[1]['error'] !== '' );
filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Last.txt', 'size' => 5, 'upload' => $lastA[1]['upload'] ?? '', 'index' => 2, 'count' => 3 ], filesPart( $appData, 'e' ) );
check( '...and nothing is kept once an upload is whole: no part and no kept length', filesParts( $appData ) === [] && filesKept( $appData ) === [] );

// A part without the length of its first chunk is no upload of this protocol; a length without a part is swept
$bare = dirname( $partFile ). '/bbbbbbbbbbbbbbbb.part';
file_put_contents( $bare, str_repeat( 'x', 300000 ) );
file_put_contents( dirname( $partFile ). '/cccccccccccccccc.chunk', '300000' );
$noKept = $tail( 1, 9, substr( $mp3, 300000, 300000 ), 'bbbbbbbbbbbbbbbb' );
unlink( $bare );
$tail( 0, 9, substr( $mp3, 0, 300000 ) );
check( 'a part with no length kept beside it is refused as an upload that is not there, and a length without its part is swept when the next upload starts', $noKept[0] === 400 && $noKept[1]['code'] === 'files_chunk' && is_file( dirname( $partFile ). '/cccccccccccccccc.chunk' ) === false && count( filesKept( $appData ) ) === 1 );
$stalePair = dirname( $partFile ). '/dddddddddddddddd';
file_put_contents( $stalePair. '.part', 'x' );
file_put_contents( $stalePair. '.chunk', '1' );
touch( $stalePair. '.part', time() - 90000 );
$tail( 0, 9, substr( $mp3, 0, 300000 ) );
check( '...a stale part goes with the length kept beside it', is_file( $stalePair. '.part' ) === false && is_file( $stalePair. '.chunk' ) === false );

foreach( glob( dirname( $partFile ). '/*' ) ?: [] as $left )
	@unlink( $left );

// A first chunk that does not fit the file or the setting
$firsts = [
	'a count that does not fit' => [ 'count' => 8, 'length' => 300000 ],
	'a count that is one too many' => [ 'count' => 10, 'length' => 300000 ],
	'longer than the setting' => [ 'count' => 3, 'length' => 1048577 ],
	'one chunk that is not the file' => [ 'count' => 1, 'length' => 300000 ],
];
$refusedFirst = [];

foreach( $firsts as $kind => $each )
	$refusedFirst[$kind] = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Parts.mp3', 'size' => strlen( $mp3 ), 'upload' => '', 'index' => 0, 'count' => $each['count'] ], filesPart( $appData, substr( $mp3, 0, $each['length'] ) ) );

// A last chunk longer than what is left is refused at once, and the part stays so that the right one can follow
$tail = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Tail.txt', 'size' => 5, 'upload' => '', 'index' => 0, 'count' => 3 ], filesPart( $appData, 'ab' ) );
$tailPart = \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/'. ( $tail[1]['upload'] ?? 'x' ). '.part';
$tailMid = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Tail.txt', 'size' => 5, 'upload' => $tail[1]['upload'] ?? '', 'index' => 1, 'count' => 3 ], filesPart( $appData, 'cd' ) );
$tailLong = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Tail.txt', 'size' => 5, 'upload' => $tail[1]['upload'] ?? '', 'index' => 2, 'count' => 3 ], filesPart( $appData, 'efg' ) );
$tailState = [ is_file( $tailPart ), filesSize( $tailPart ) ];
$tailEnd = filesMemberSend( $appData, [ 'folder' => $room['id'], 'name' => 'Tail.txt', 'size' => 5, 'upload' => $tail[1]['upload'] ?? '', 'index' => 2, 'count' => 3 ], filesPart( $appData, 'e' ) );
check( '...a last chunk longer than what is left is 400 files_chunk before it is written - the part stays as long as it was and the right last chunk is taken after it', [ $tail[0], $tailMid[0], $tailLong[0], $tailLong[1]['code'] ?? '' ] === [ 200, 200, 400, 'files_chunk' ] && $tailState === [ true, 4 ] && $tailEnd[0] === 200 && ( $tailEnd[1]['size'] ?? 0 ) === 5 );

check( 'a first chunk is checked against the file it starts: a count that does not fit its length, a chunk longer than the setting and a single chunk that is not the whole file are 400 files_chunk, and no part is made',
	array_values( array_unique( array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ), $refusedFirst ) ) ) === [ '400 files_chunk' ] && filesParts( $appData ) === [] );
filesFresh( $appData );

// The one-step post without script
filesAs( 'ada' );
filesFresh( $appData );
$plain = static function( string $name, string $bytes, array $post = [], array $part = [] ) use ( &$appData, $room ): array {
	$part = array_replace( filesPart( $appData, $bytes ), [ 'name' => $name ], $part );
	$request = filesServe( $appData, '/.files/upload', 'POST', $post + [ 'folder' => $room['id'], 'count' => '1', 'return' => '/club/files?x=1#top' ], true, [ 'chunk' => $part ] );
	return [ $request['/nino/http/response']['statusCode'], (string) ( $request['/nino/http/response']['header']['Location'] ?? '' ), $request['/nino/http/response']['body'] ?? '' ];
};
$told = [];
$sent = $plain( 'plain.txt', "Plain notes\n" );
$plainEntry = array_values( array_filter( \Nino\Modules\Files::files( $appData, $room['id'] ), static fn( array $file ): bool => $file['name'] === 'plain.txt' ) )[0] ?? [];
check( 'the plain post - one chunk that is the whole file, count 1, no name and no size of its own - is taken with the name and the size the file carries and answers a redirect (303) to the page it was posted from with ?files=sent',
	$sent === [ 303, '/club/files?files=sent', '' ] && ( $plainEntry['by'] ?? null ) === [ 'kind' => 'member', 'id' => $ada['id'] ] && ( $plainEntry['size'] ?? 0 ) === 12 && ( $plainEntry['mime'] ?? '' ) === 'text/plain' && count( $told ) === 1 && $told[0][0] === '/files/upload' );

$refusals = [
	'files_type' => $plain( 'virus.exe', 'MZ' ), 'files_folder' => $plain( 'a.txt', 'abc', [ 'folder' => $lobby['id'] ] ), 'files_post' => $plain( 'big.txt', str_repeat( 'a', $chunkBytes + 1 ) ),
];
// A file php refused for its size comes with an error on its part and no size and no bytes
$phpRefused = [
	'upload_max_filesize' => $plain( 'huge.txt', '', [], [ 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0, 'tmp_name' => '' ] ),
	'the size field of the form' => $plain( 'huge.txt', '', [], [ 'error' => UPLOAD_ERR_FORM_SIZE, 'size' => 0, 'tmp_name' => '' ] ),
	'a part that came partly' => $plain( 'cut.txt', '', [], [ 'error' => UPLOAD_ERR_PARTIAL, 'size' => 0, 'tmp_name' => '' ] ),
];
check( 'a plain post that php refused for its size - upload_max_filesize or the size field of the form - is a file that is longer than one post takes, ?files=files_post - not "larger than allowed", which it is not; a part that came partly is still a chunk that did not arrive, ?files=files_chunk',
	array_map( static fn( array $answer ): string => $answer[0]. ' '. $answer[1], $phpRefused ) === [ 'upload_max_filesize' => '303 /club/files?files=files_post', 'the size field of the form' => '303 /club/files?files=files_post', 'a part that came partly' => '303 /club/files?files=files_chunk' ] );
check( 'a plain post that is refused is a redirect (303) to the same page with ?files=<code> - the type, a folder that is not the group\'s, a file longer than one post takes', $refusals['files_type'][1] === '/club/files?files=files_type' && $refusals['files_folder'][1] === '/club/files?files=files_folder'
	&& $refusals['files_post'][1] === '/club/files?files=files_post' && array_values( array_unique( array_column( $refusals, 0 ) ) ) === [ 303 ] );

$returns = [];
foreach( [ '//evil.example/x', '/\\evil.example', 'https://evil.example', '/.members/login', "/a b", '', 'relative/path', '/a/b:c', '/javascript:alert(1)' ] as $return )
	$returns[$return] = $plain( 'r.txt', 'abc', [ 'return' => $return ] )[1];
check( 'the page the plain post goes back to is a path of this site and no endpoint of a module - the front page for anything else', $returns === [ '//evil.example/x' => '/?files=sent', '/\\evil.example' => '/?files=sent', 'https://evil.example' => '/?files=sent', '/.members/login' => '/?files=sent',
	'/a b' => '/?files=sent', '' => '/?files=sent', 'relative/path' => '/?files=sent', '/a/b:c' => '/a/b:c?files=sent', '/javascript:alert(1)' => '/?files=sent' ] );

$noFile = filesServe( $appData, '/.files/upload', 'POST', [ 'folder' => $room['id'], 'count' => '1', 'return' => '/club/files' ] )['/nino/http/response'];
check( 'a plain post with no file in it is a refusal like the others, not an error', $noFile['statusCode'] === 303 && str_starts_with( $noFile['header']['Location'], '/club/files?files=files_' ) === true && ninoWarnings() === [] );
filesVisitor();

echo "\n";

// --- The components --------------------------------------------------------------

echo "The components - registry, and what a visitor and a member are shown\n";

$registry = \Nino\Components::components( $appData );
$schemas = array_intersect_key( $registry, array_flip( [ 'files-browser', 'files-upload' ] ) );

check( 'the folders are read without a reason or a warning, and the registry knows the two in their group, labelled in both languages, each with an icon of the table and a picture of its own, and the group has a standard part and a demo',
	\Nino\Features::componentProblems( $appData, 'files' ) === [] && array_keys( $schemas ) === [ 'files-browser', 'files-upload' ] && array_unique( array_column( $schemas, 'group' ) ) === [ 'files' ]
	&& array_filter( $schemas, static fn( array $schema ): bool => isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === false || in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& array_values( array_column( $schemas, 'preview' ) ) === [ 'block', 'block' ] && ( \Nino\Components::groups( $appData )['files']['part'] ?? false ) === true && trim( (string) ( \Nino\Components::groups( $appData )['files']['demo'] ?? '' ) ) !== ''
	&& array_filter( $schemas, static fn( array $schema ): bool => $schema['source'] !== 'none' ) === [] );
check( '...one attribute each, a flag that is on by default - the link that saves, several files at once - and neither is a class, so the markup says what the call said and nothing of the defaults', array_keys( $schemas['files-browser']['attributes'] ) === [ 'downloads' ] && array_keys( $schemas['files-upload']['attributes'] ) === [ 'multiple' ]
	&& $schemas['files-browser']['attributes']['downloads']['default'] === true && $schemas['files-upload']['attributes']['multiple']['default'] === true );
check( 'a tool offers both in the flow of a column, and the standard part keeps the rule of mixing: its own tokens and classes, the tokens of the brand and of the page and nothing else',
	array_filter( [ 'files-browser', 'files-upload' ], static fn( string $name ): bool => in_array( $name, \Nino\Components::allowed( $appData, 'section-col' ), true ) === false ) === []
	&& \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/files/part/part.css' ), [ 'files-browser', 'files-upload' ] ) === [] );
check( '...the renderers are the methods the register derives their names to, and the class registers no shortcode of its own beside them', is_callable( [ '\\Nino\\Modules\\Files', 'componentFilesBrowser' ] ) === true && is_callable( [ '\\Nino\\Modules\\Files', 'componentFilesUpload' ] ) === true );

// The words of the sizes are the unit's: 1.5 KB in the language of the page
$words = [ 'b' => 'B', 'kb' => 'KB', 'mb' => 'MB', 'gb' => 'GB', 'separator' => ',' ];
check( 'a size is told in the largest unit that leaves a number of one digit or more - one decimal under ten, none above - with the unit and the separator of the language',
	\Nino\Modules\Files\Store::size( 0, $words ) === '0 B' && \Nino\Modules\Files\Store::size( 1023, $words ) === '1023 B' && \Nino\Modules\Files\Store::size( 1536, $words ) === '1,5 KB' && \Nino\Modules\Files\Store::size( 1024, $words ) === '1 KB'
	&& \Nino\Modules\Files\Store::size( 3000, $words ) === '2,9 KB' && \Nino\Modules\Files\Store::size( 10 * 1024, $words ) === '10 KB' && \Nino\Modules\Files\Store::size( 500 * 1048576, [ 'mb' => 'MB', 'separator' => '.' ] ) === '500 MB'
	&& \Nino\Modules\Files\Store::size( 5 * 1073741824 + 1, [ 'gb' => 'GB', 'separator' => '.' ] ) === '5 GB' && \Nino\Modules\Files\Store::size( 2500000, [ 'mb' => 'MB', 'separator' => '.' ] ) === '2.4 MB'
	&& \Nino\Modules\Files\Store::size( 12900000, [ 'mb' => 'MB', 'separator' => '.' ] ) === '12 MB' );

check( 'the size of a chunk is the setting in megabytes, held under what php takes in a request less the room for the fields - and a limit of nothing is no limit',
	\Nino\Modules\Files\Store::clamp( 4, 0 ) === 4194304 && \Nino\Modules\Files\Store::clamp( 32, 2097152 ) === 2097152 - 65536 && \Nino\Modules\Files\Store::clamp( 1, 10485760 ) === 1048576 && \Nino\Modules\Files\Store::clamp( 4, 1000 ) === 65536 && \Nino\Modules\Files\Store::clamp( 0, 0 ) === 1048576 );

$group = static fn( string $group ): string => $group;
$staffFolders = array_column( \Nino\Modules\Files::folders( $appData, 'staff' ), 'id' );

// ...for a visitor, nothing at all
filesVisitor();
ninoVisit( $appData, '/club/files' );
check( 'a visitor is shown nothing of either - not an empty frame, not a form', \Nino\Html::renderHtml( $appData, '[files-browser]' ) === '' && \Nino\Html::renderHtml( $appData, '[files-upload]' ) === '' && \Nino\Html::renderHtml( $appData, '[files-browser downloads="0"][files-upload multiple="0"]' ) === '' );

// ...the browser, for a member of the group
filesAs( 'ada' );
ninoVisit( $appData, '/club/files' );
$html = \Nino\Html::renderHtml( $appData, '[files-browser]' );
$browsers = filesFind( $html, '//div[contains(concat(" ", normalize-space(@class), " "), " files-browser ")]' );
$sections = filesFind( $html, '//section[contains(@class, "files-browser-folder")]' );
$rows = filesFind( $html, '//li[contains(@class, "files-browser-row")]' );
$ids = array_map( static fn( DOMElement $row ): string => $row->getAttribute( 'data-files-id' ), $rows );
check( 'a member is shown one browser and one section for each folder of their group - in the order of the names, and no folder of another group',
	count( $browsers ) === 1 && filesClasses( $browsers[0] ) === [ 'files-browser' ] && array_map( static fn( DOMElement $section ): string => $section->getAttribute( 'data-files-folder' ), $sections ) === $staffFolders
	&& in_array( $lobby['id'], $staffFolders, true ) === false && str_contains( $html, $lobby['id'] ) === false && str_contains( $html, '[[' ) === false );

$seen = [];

foreach( $staffFolders as $folderId )
	foreach( \Nino\Modules\Files::files( $appData, $folderId ) as $file )
		if( \Nino\Modules\Files::allowed( $appData, $file ) === true )
			$seen[] = $file['id'];

check( '...in each the files that are visible to the member - the ones the rule of the download allows, no hidden file and no file of the owner that is not shown - each row with the id and the MIME type of its file',
	$ids !== [] && in_array( $secret['id'], $ids, true ) === false && in_array( $cydsOwn['id'], $ids, true ) === false && in_array( $track['id'], $ids, true ) === true && in_array( $pack['id'], $ids, true ) === true
	&& array_diff( $seen, $ids ) === [] && array_diff( $ids, $seen ) === [] && count( $ids ) === count( $seen )
	&& array_filter( $rows, static fn( DOMElement $row ): bool => preg_match( '/^[a-f0-9]{12}$/D', $row->getAttribute( 'data-files-id' ) ) !== 1 || $row->getAttribute( 'data-files-mime' ) === '' ) === [] );

$trackRow = array_values( array_filter( $rows, static fn( DOMElement $row ): bool => $row->getAttribute( 'data-files-id' ) === $track['id'] ) )[0];
$packRow = array_values( array_filter( $rows, static fn( DOMElement $row ): bool => $row->getAttribute( 'data-files-id' ) === $pack['id'] ) )[0];
$cell = static function( DOMElement $row, string $class ): ?DOMElement {
	foreach( $row->getElementsByTagName( '*' ) as $child )
		if( in_array( $class, filesClasses( $child ), true ) === true )
			return $child;
	return null;
};
check( '...a row has the name as a link to the file, its size in the unit of the page, the day it was put there as a time, and the link that saves it - the MIME type on the row',
	$trackRow->getAttribute( 'data-files-mime' ) === 'audio/mpeg' && $packRow->getAttribute( 'data-files-mime' ) === 'application/zip' && $cell( $trackRow, 'files-browser-link' )->getAttribute( 'href' ) === '/.files/'. $track['id']
	&& $cell( $trackRow, 'files-browser-link' )->textContent === 'Demo "One".mp3' && $cell( $trackRow, 'files-browser-size' )->textContent === '2,9 KB' && $cell( $trackRow, 'files-browser-date' )->tagName === 'time'
	&& $cell( $trackRow, 'files-browser-date' )->getAttribute( 'datetime' ) === \Nino\Modules\Files::file( $appData, $track['id'] )['created'] && $cell( $trackRow, 'files-browser-date' )->textContent === substr( \Nino\Modules\Files::file( $appData, $track['id'] )['created'], 0, 10 )
	&& $cell( $trackRow, 'files-browser-download' )->getAttribute( 'href' ) === '/.files/'. $track['id']. '?download=1' );
$counts = array_map( static fn( DOMElement $section ): string => trim( $cell( $section, 'files-browser-count' )->textContent ), $sections );
check( '...each folder says its name and the number of files the member sees in it', array_map( static fn( DOMElement $section ): string => trim( $cell( $section, 'files-browser-name' )->textContent ), $sections ) === array_column( \Nino\Modules\Files::folders( $appData, 'staff' ), 'name' )
	&& $counts === array_map( static fn( string $folder ): string => (string) count( array_filter( \Nino\Modules\Files::files( $GLOBALS['appData'], $folder ), static fn( array $file ): bool => \Nino\Modules\Files::allowed( $GLOBALS['appData'], $file ) ) ), $staffFolders ) );

$plainBrowser = \Nino\Html::renderHtml( $appData, '[files-browser downloads="0"]' );
check( 'without the attribute downloads there is no link that saves, the name stays a link', filesFind( $plainBrowser, '//a[contains(@class, "files-browser-download")]' ) === [] && count( filesFind( $plainBrowser, '//a[contains(@class, "files-browser-link")]' ) ) === count( $rows ) );

// A folder without a visible file, a member without a group, a group without a folder
$emptyFolder = \Nino\Modules\Files\Store::addFolder( $appData, 'staff', 'Empty one' );
$emptyHtml = \Nino\Html::renderHtml( $appData, '[files-browser]' );
$emptySection = array_values( array_filter( filesFind( $emptyHtml, '//section[contains(@class, "files-browser-folder")]' ), static fn( DOMElement $section ): bool => $section->getAttribute( 'data-files-folder' ) === $emptyFolder['id'] ) )[0];
\Nino\Modules\Files\Store::deleteFolder( $appData, $emptyFolder['id'] );
check( 'a folder without a file the member may see says so, and counts none', trim( $cell( $emptySection, 'files-browser-count' )->textContent ) === '0' && $cell( $emptySection, 'files-browser-empty' ) !== null );

\Nino\Modules\Members::change( $appData, $ada['id'], [ 'group' => '' ] );
$loner = \Nino\Html::renderHtml( $appData, '[files-browser][files-upload]' );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'group' => 'tourists' ] );
$nowhere = \Nino\Html::renderHtml( $appData, '[files-browser][files-upload]' );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'group' => 'staff' ] );
check( 'a member with no group, and one whose group has no folder, are shown the browser with a line that says there is nothing - and no form', count( filesFind( $loner, '//p[contains(@class, "files-browser-none")]' ) ) === 1
	&& count( filesFind( $nowhere, '//p[contains(@class, "files-browser-none")]' ) ) === 1 && filesFind( $loner. $nowhere, '//form' ) === [] && filesFind( $loner. $nowhere, '//section' ) === [] );

filesAs( 'bob' );
$bobHtml = \Nino\Html::renderHtml( $appData, '[files-browser]' );
check( 'a member of another group is shown the folders of theirs and nothing of the staff\'s', array_map( static fn( DOMElement $section ): string => $section->getAttribute( 'data-files-folder' ), filesFind( $bobHtml, '//section' ) ) === [ $lobby['id'] ]
	&& str_contains( $bobHtml, $track['id'] ) === false && str_contains( $bobHtml, $room['id'] ) === false );

// Names are text wherever they are written
filesAs( 'ada' );
$hostile = [];
foreach( [ '<img src=x onerror=alert(1)>', '[[/project/company/general/name]]', '[files-upload]' ] as $name ) {
	$folderHostile = \Nino\Modules\Files\Store::addFolder( $appData, 'staff', $name );
	filesMake( $appData, $folderHostile['id'], $name. '.mp3', 'x' );
	$markup = \Nino\Html::renderHtml( $appData, '[files-browser]' );
	$texts = array_map( static fn( DOMElement $each ): string => $each->textContent, filesFind( $markup, '//*[contains(@class, "files-browser-name")]' ) );
	$hostile[$name] = filesFind( $markup, '//img' ) === [] && filesFind( $markup, '//@onerror' ) === [] && str_contains( $markup, 'The Club' ) === false && filesFind( $markup, '//form' ) === [] && in_array( $name, $texts, true ) === true
		&& in_array( \Nino\Modules\Files\Store::cleanName( $name. '.mp3' )[0], array_map( static fn( DOMElement $each ): string => $each->textContent, filesFind( $markup, '//a[contains(@class, "files-browser-link")]' ) ), true ) === true;
	\Nino\Modules\Files\Store::deleteFolder( $appData, $folderHostile['id'] );
}
check( 'the name of a folder and of a file is text: markup in it is not an element, and a key or a shortcode in it is neither filled in nor run', array_filter( $hostile, static fn( bool $safe ): bool => $safe === false ) === [] && count( $hostile ) === 3 );

echo "\n";

echo "The component [files-upload] - the form, and the states\n";

ninoVisit( $appData, '/join?files=sent&x=1' );
$form = \Nino\Html::renderHtml( $appData, '[files-upload]' );
$forms = filesFind( $form, '//form' );
$fields = [];
foreach( filesFind( $form, '//form//input' ) as $field )
	$fields[ $field->getAttribute( 'name' ) ] = $field;
check( 'for a member whose group has an upload folder [files-upload] is one form that posts to /.files/upload as multipart, with the hidden folder, count 1 and the page it is on, the csrf field of the session, a file input and a button',
	count( $forms ) === 1 && strtolower( $forms[0]->getAttribute( 'method' ) ) === 'post' && $forms[0]->getAttribute( 'action' ) === '/.files/upload' && strtolower( $forms[0]->getAttribute( 'enctype' ) ) === 'multipart/form-data'
	&& array_keys( $fields ) === [ 'folder', 'count', 'return', '_csrf', 'chunk' ] && $fields['folder']->getAttribute( 'value' ) === $room['id'] && $fields['count']->getAttribute( 'value' ) === '1' && $fields['return']->getAttribute( 'value' ) === '/join'
	&& $fields['_csrf']->getAttribute( 'value' ) === \Nino\Csrf::getToken( $appData ) && $fields['chunk']->getAttribute( 'type' ) === 'file' && $fields['chunk']->hasAttribute( 'required' ) === true
	&& count( filesFind( $form, '//form//button[@type="submit"]' ) ) === 1 );
check( '...it is no .nino-form (the kernel\'s script would post it by XHR), its label belongs to its file input, and the list the script fills is there and hidden', in_array( 'nino-form', filesClasses( $forms[0] ), true ) === false && in_array( 'files-upload', filesClasses( $forms[0] ), true ) === true
	&& filesFind( $form, '//label' )[0]->getAttribute( 'for' ) === $fields['chunk']->getAttribute( 'id' ) && $fields['chunk']->getAttribute( 'id' ) !== '' && count( filesFind( $form, '//ul[contains(@class, "files-upload-list")][@hidden]' ) ) === 1 );
check( '...it carries the size of a chunk - the setting - and of the largest file in bytes for the script, and it asks for several files by a data attribute and not by the attribute multiple, which php would keep one part of',
	$forms[0]->getAttribute( 'data-files-chunk' ) === (string) $chunkBytes && $forms[0]->getAttribute( 'data-files-max' ) === (string) ( 5 * $chunkBytes ) && $forms[0]->getAttribute( 'data-files-multiple' ) === '1' && $fields['chunk']->hasAttribute( 'multiple' ) === false );

$hints = filesFind( $form, '//form/p[contains(@class, "files-upload-hint")]' );
check( '...it tells the largest file and the types that are taken - two lines of hint, the second naming each type of the setting', count( $hints ) === 2 && array_diff( \Nino\Modules\Files\Store::types( $appData ), preg_split( '/[\s,:]+/', $hints[1]->textContent ) ?: [] ) === []
	&& \Nino\Modules\Files\Store::types( $appData ) !== [] && trim( $hints[0]->textContent ) !== '' );

\Nino\Features::saveSettings( $appData, 'files', [ 'chunk' => 32 ] );
$clamped = filesFind( \Nino\Html::renderHtml( $appData, '[files-upload]' ), '//form' )[0]->getAttribute( 'data-files-chunk' );
\Nino\Features::saveSettings( $appData, 'files', [ 'chunk' => 1 ] );
check( '...and a setting that php would not take in one request is held under it, which is the number the script cuts by', $clamped === (string) \Nino\Modules\Files\Store::clamp( 32, \Nino\Images::limits()['php'] ) && (int) $clamped <= \Nino\Images::limits()['php'] );

ninoVisit( $appData, '/join' );
$twice = \Nino\Html::renderHtml( $appData, '[files-upload multiple="0"][files-upload]' );
$both = filesFind( $twice, '//form' );
check( 'multiple off - and two forms on a page do not share an id', count( $both ) === 2 && $both[0]->getAttribute( 'data-files-multiple' ) === '0' && $both[1]->getAttribute( 'data-files-multiple' ) === '1' && in_array( 'files-upload', filesClasses( $both[0] ), true ) === true && count( array_unique( array_map( static fn( DOMElement $label ): string => $label->getAttribute( 'for' ), filesFind( $twice, '//label' ) ) ) ) === 2
	&& count( filesFind( $twice, '//input[@id="'. filesFind( $twice, '//label' )[0]->getAttribute( 'for' ). '"]' ) ) === 1 );
ninoVisit( $appData, '/join"onfocus="x' );
$hostile = filesFind( \Nino\Html::renderHtml( $appData, '[files-upload]' ), '//form//input[@name="return"]' );
ninoVisit( $appData, '/join' );
check( 'the page the form stands on is text in an attribute: whatever the address holds, it does not end the attribute or add one', count( $hostile ) === 1 && str_contains( $hostile[0]->getAttribute( 'value' ), '"' ) === false && str_starts_with( $hostile[0]->getAttribute( 'value' ), '/join' ) === true
	&& $hostile[0]->hasAttribute( 'onfocus' ) === false );
check( 'a hand-written call with an attribute nobody declared still renders', count( filesFind( \Nino\Html::renderHtml( $appData, '[files-upload nonsense="1"]' ), '//form' ) ) === 1 );

// The states: the query says what the form says, of the list and of nothing else
$states = [];
$english = [];
$language = \Nino\Locales::getCurrentLocale( $appData );

foreach( [ 'sent', 'files_folder', 'files_type', 'files_size', 'files_post', 'files_quota', 'files_chunk', 'files_limit', 'files_write' ] as $state ) {
	ninoVisit( $appData, '/join?files='. $state );
	$states[$state] = filesFind( \Nino\Html::renderHtml( $appData, '[files-upload]' ), '//form/p[contains(@class, "files-upload-state")]' );
	\Nino\Locales::useLocale( $appData, 'en_US' );
	$english[$state] = filesFind( \Nino\Html::renderHtml( $appData, '[files-upload]' ), '//form/p[contains(@class, "files-upload-state")]' );
	\Nino\Locales::useLocale( $appData, $language );
}

check( 'the nine states - sent, and each code of a refusal - have a paragraph of their own that is not empty and holds no key left unresolved, in the language of the page',
	array_filter( $states, static fn( array $found ): bool => count( $found ) !== 1 || trim( $found[0]->textContent ) === '' || str_contains( $found[0]->textContent, '[[' ) || $found[0]->getAttribute( 'role' ) !== 'status' ) === [] && count( array_unique( array_map( static fn( array $found ): string => trim( $found[0]->textContent ), $states ) ) ) === 9
	&& preg_match( '/\d/', $states['files_post'][0]->textContent ) === 1 && str_contains( $states['files_post'][0]->textContent, '%' ) === false && str_contains( $english['files_post'][0]->textContent, 'up to' ) === true
	&& $language !== 'en_US' && array_filter( $states, static fn( array $found, string $state ): bool => count( $english[$state] ) !== 1 || trim( $english[$state][0]->textContent ) === trim( $found[0]->textContent ), ARRAY_FILTER_USE_BOTH ) === [] );

// The field that takes the file carries the refusal: invalid where the form says one, described by the limits and by the sentence of the state - each by an id that is on the page
$fieldOf = static function( string $state ) use ( &$appData ): array {
	ninoVisit( $appData, '/join'. ( $state === '' ? '' : '?files='. $state ) );
	$html = \Nino\Html::renderHtml( $appData, '[files-upload]' );
	$input = filesFind( $html, '//form//input[@type="file"]' )[0] ?? null;
	$ids = array_map( static fn( DOMElement $element ): string => $element->getAttribute( 'id' ), filesFind( $html, '//form//*[@id]' ) );
	$described = $input === null ? [] : array_filter( explode( ' ', $input->getAttribute( 'aria-describedby' ) ) );

	return [ $input === null ? '' : $input->getAttribute( 'aria-invalid' ), $described !== [] && array_diff( $described, $ids ) === [], count( $described ) ];
};
check( 'the field of the form says whether it is invalid and what describes it: with no state valid and described by the two limits; with a refusal invalid and described by the sentence of the state as well; with "sent" valid - every id it names is on the page',
	$fieldOf( '' ) === [ 'false', true, 2 ] && $fieldOf( 'files_type' ) === [ 'true', true, 3 ] && $fieldOf( 'files_post' ) === [ 'true', true, 3 ] && $fieldOf( 'sent' ) === [ 'false', true, 3 ] );
check( 'a refusal that stands in the progress list of the site at 390 pixels has the whole width of its row: the status of a failed file spans the grid',
	preg_match( '/\.files-upload-item\[data-state="failed"\] \.files-upload-status\s*\{[^}]*grid-column:\s*1 \/ -1/', (string) file_get_contents( $dir. '/groups/files/part/part.css' ) ) === 1 );

$none = [];
foreach( [ '', '?files=', '?files=SENT', '?files=other', '?files[]=sent', '?files=sent%20', '?files=<b>x</b>', '?files=type', '?files=files_' ] as $query ) {
	ninoVisit( $appData, '/join'. $query );
	$none[$query] = count( filesFind( \Nino\Html::renderHtml( $appData, '[files-upload]' ), '//form/p[contains(@class, "files-upload-state")]' ) );
}
check( 'anything else in the query - an array, a made-up word, a code without its prefix - is no state: there is no paragraph', array_filter( $none ) === [] && count( $none ) === 9 );

// Nothing where there is nowhere to upload to
filesAs( 'bob' );
ninoVisit( $appData, '/join' );
$bobForm = \Nino\Html::renderHtml( $appData, '[files-upload]' );
\Nino\Modules\Files\Store::markUpload( $appData, $room['id'], false );
filesAs( 'ada' );
$unmarked = \Nino\Html::renderHtml( $appData, '[files-upload]' );
\Nino\Modules\Files\Store::markUpload( $appData, $room['id'], true );
check( 'a member whose group has no upload folder is shown no form - one of another group does not get the form of the staff\'s, and a folder that loses its mark takes the form away', $bobForm === '' && $unmarked === '' && count( filesFind( \Nino\Html::renderHtml( $appData, '[files-upload]' ), '//form' ) ) === 1 );
filesVisitor();

echo "\n";

// --- The panel -------------------------------------------------------------------

echo "Modules\\Files\\Admin - the folders and the files, and what the panel says\n";

filesWorkbench( 'admin' );
$panels = \Nino\Admin\Admin::panels( $appData );
check( 'the panel is in the registry while the feature is active, in the rail\'s group of the features, with a permission of its own, two levels - the directory and the folder - and a script and a stylesheet that are there',
	isset( $panels['files'] ) === true && $panels['files']['group'] === 'features' && $panels['files']['perm'] === '/_admin/files/manage' && \Nino\Modules\Files\Admin::panes() === [ 'files-list', 'files-folder' ]
	&& array_filter( \Nino\Modules\Files\Admin::assets(), static fn( string $asset ): bool => is_file( \Nino\Filesystem::path( $GLOBALS['appData'], $asset ) ) === false ) === [] && count( \Nino\Modules\Files\Admin::assets() ) === 2
	&& is_file( \Nino\Filesystem::path( $appData, \Nino\Modules\Files\Admin::text(). '/en_US.php' ) ) === true && is_file( \Nino\Filesystem::path( $appData, \Nino\Modules\Files\Admin::text(). '/de_DE.php' ) ) === true
	&& \Nino\Modules\Files\Admin::MANAGE_PERM === \Nino\Modules\Files::MANAGE_PERM && file_exists( $dir. '/templates/panel.tpl' ) === false );
check( '...the script draws into the first level and the pane has both', str_contains( (string) file_get_contents( $dir. '/assets/admin.js' ), "getElementById('files-list')" ) === true && str_contains( (string) file_get_contents( $dir. '/assets/admin.js' ), "'files-folder'" ) === true );

// The list: the groups of the members and of the folders, every folder with its number of files and the bytes it holds, and the files of the folder that is open
$leftBehind = \Nino\Modules\Files\Store::addFolder( $appData, 'tourists', 'Left behind' );
$firstGroup = \Nino\Modules\Files\Store::addFolder( $appData, 'aardvark', 'First of the alphabet' );
$list = ninoCall( $appData, $panel, [], 'files/list' );
$listed = $list[1];
check( 'the list is the groups (those the members have and those that only a folder has) and the groups the members have, the folders of every group with the number of their files and the bytes on the server, the types and the limits - and no file where no folder is open',
	$list[0] === 200 && $listed['groups'] === [ 'aardvark', 'guests', 'staff', 'tourists' ] && $listed['members'] === [ 'guests', 'staff' ] && $listed['group'] === '' && array_keys( $listed['folders'][0] ) === [ 'id', 'key', 'group', 'name', 'upload', 'created', 'count', 'bytes' ]
	&& array_column( $listed['folders'], 'group' ) === array_values( array_column( $listed['folders'], 'group' ) ) && count( $listed['folders'] ) === count( filesIndex( $appData )['folders'] ) && $listed['files'] === [] && $listed['types'] === \Nino\Modules\Files\Store::types( $appData )
	&& array_column( $listed['folders'], 'count', 'id' ) === array_map( static fn( string $id ): int => count( \Nino\Modules\Files::files( $GLOBALS['appData'], (string) $id ) ), array_combine( array_column( $listed['folders'], 'id' ), array_column( $listed['folders'], 'id' ) ) )
	&& $listed['limits'] === [ 'chunk' => $chunkBytes, 'max' => 5 * $chunkBytes, 'php' => \Nino\Images::limits()['php'], 'quota' => 0, 'used' => 0, 'held' => 0 ] );
check( '...the folders stand in the order of their group and then of their names, and the bytes of a folder are the bytes of the files that are on the server', array_column( $listed['folders'], 'group' ) === array_values( array_column( $listed['folders'], 'group' ) )
	&& array_map( static fn( array $folder ): array => [ $folder['group'], mb_strtolower( $folder['name'] ) ], $listed['folders'] ) === ( static function( array $folders ): array { usort( $folders, static fn( array $a, array $b ): int => $a <=> $b ); return $folders; } )( array_map( static fn( array $folder ): array => [ $folder['group'], mb_strtolower( $folder['name'] ) ], $listed['folders'] ) )
	&& array_filter( $listed['folders'], static fn( array $folder ): bool => $folder['bytes'] !== (int) array_sum( array_column( array_filter( \Nino\Modules\Files::files( $GLOBALS['appData'], $folder['id'] ), static fn( array $file ): bool => isset( $file['gone'] ) === false ), 'size' ) ) ) === [] );
$staffOnly = ninoCall( $appData, $panel, [ 'group' => 'staff' ], 'files/list' )[1];
check( 'a group narrows the folders to its own - and, with no folder named, the files of all of them; the quota line counts what the group holds',
	$staffOnly['group'] === 'staff' && array_column( $staffOnly['folders'], 'id' ) === array_column( \Nino\Modules\Files::folders( $appData, 'staff' ), 'id' ) && $staffOnly['files'] !== [] && $staffOnly['limits']['used'] === \Nino\Modules\Files\Store::used( $appData, 'staff' )
	&& array_diff( array_unique( array_column( $staffOnly['files'], 'folder' ) ), array_column( $staffOnly['folders'], 'id' ) ) === [] );
$listed = ninoCall( $appData, $panel, [ 'folder' => $room['id'] ], 'files/list' )[1];
check( '...a folder that is named brings its files and no other folder\'s - each with who put it there and the address it is downloaded at', $listed['files'] !== [] && array_unique( array_column( $listed['files'], 'folder' ) ) === [ $room['id'] ] && count( $listed['files'] ) === count( \Nino\Modules\Files::files( $appData, $room['id'] ) )
	&& array_filter( $listed['files'], static fn( array $file ): bool => $file['url'] !== \Nino\Modules\Files::url( $GLOBALS['appData'], $file['id'] ) ) === [] && $listed['limits']['used'] === \Nino\Modules\Files\Store::used( $appData, 'staff' ) );
$byAda = array_values( array_filter( $listed['files'], static fn( array $file ): bool => $file['by']['kind'] === 'member' && $file['by']['id'] === $GLOBALS['ada']['id'] ) )[0] ?? null;
$appData['/nino/dir'] = '/club';
$inDirectory = ninoCall( $appData, $panel, [ 'folder' => $room['id'] ], 'files/list' )[1]['files'];
$appData['/nino/dir'] = '';
check( '...the address is the one of the API, below the directory the project is served from, and a file put there by a member carries the name of that member for the screen, one put there by the owner none', $inDirectory !== [] && array_filter( $inDirectory, static fn( array $file ): bool => $file['url'] !== '/club/.files/'. $file['id'] ) === []
	&& array_filter( $listed['files'], static fn( array $file ): bool => $file['by']['kind'] === 'owner' && $file['by']['label'] !== '' ) === [] && ( $byAda['by']['label'] ?? '' ) === 'Ada Lovelace' );
$strange = [ 'a group that is not one' => ninoCall( $appData, $panel, [ 'group' => 'nobody' ], 'files/list' )[1], 'a group that is a list' => ninoCall( $appData, $panel, [ 'group' => [ 'staff' ] ], 'files/list' )[1], 'a folder that is none' => ninoCall( $appData, $panel, [ 'folder' => '../x' ], 'files/list' )[1],
	'a folder nobody has' => ninoCall( $appData, $panel, [ 'folder' => 'ffffffffffff' ], 'files/list' )[1], 'a folder that is a list' => ninoCall( $appData, $panel, [ 'folder' => [ $room['id'] ] ], 'files/list' )[1] ];
check( 'a group nobody has, a folder that is none or that nobody has, and what is posted as a list are no group and no folder: every folder, no file, and no error', array_filter( $strange, static fn( array $answer ): bool => $answer['group'] !== '' || $answer['files'] !== [] || count( $answer['folders'] ) !== count( $GLOBALS['listed']['folders'] ) + 0 && count( $answer['folders'] ) !== count( filesIndex( $GLOBALS['appData'] )['folders'] ) ) === [] && ninoWarnings() === [] );
$tourists = ninoCall( $appData, $panel, [ 'group' => 'tourists' ], 'files/list' )[1];
check( 'a group that only a folder has is listed, so that its folder can still be seen and deleted', $tourists['group'] === 'tourists' && array_column( $tourists['folders'], 'id' ) === [ $leftBehind['id'] ] && in_array( 'tourists', $tourists['groups'], true ) === true && in_array( 'tourists', $tourists['members'], true ) === false );
ninoCall( $appData, $panel, [ 'id' => $leftBehind['id'] ], 'files/folder/delete' );
ninoCall( $appData, $panel, [ 'id' => $firstGroup['id'] ], 'files/folder/delete' );

// Folders: add
$added = ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => "  Concert\x07 tapes " ], 'files/folder/add' );
$newId = (string) ( $added[1]['folder']['id'] ?? '' );
check( 'a folder is added to a group of the members: the answer has it with its id, the name cleaned, no upload mark, and it is in the index after a fresh read', $added[0] === 200 && preg_match( '/^[a-f0-9]{12}$/D', $newId ) === 1 && $added[1]['folder']['name'] === 'Concert tapes' && $added[1]['folder']['upload'] === false
	&& filesIndex( $appData )['folders'][$newId]['group'] === 'staff' && filesIndex( $appData )['folders'][$newId]['name'] === 'Concert tapes' );

$refusals = [
	'a group the members do not have' => ninoCall( $appData, $panel, [ 'group' => 'tourists', 'name' => 'x' ], 'files/folder/add' ),
	'a group that is a list' => ninoCall( $appData, $panel, [ 'group' => [ 'staff' ], 'name' => 'x' ], 'files/folder/add' ),
	'no name' => ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => " \t " ], 'files/folder/add' ),
	'a name that is a list' => ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => [ 'x' ] ], 'files/folder/add' ),
	'a name of 81 characters' => ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => str_repeat( 'x', 81 ) ], 'files/folder/add' ),
];
$edgeFolder = ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => str_repeat( 'ü', 80 ) ], 'files/folder/add' );
check( 'a group the members do not have is 400 files_group with the field group, a name that is empty 400 files_name, one of 81 characters 400 files_long with the limit and the field name - 80 characters, counted as characters, are fine',
	array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ). ' '. ( $answer[1]['field'] ?? '' ), $refusals ) === [ 'a group the members do not have' => '400 files_group group', 'a group that is a list' => '400 files_group group',
		'no name' => '400 files_name name', 'a name that is a list' => '400 files_name name', 'a name of 81 characters' => '400 files_long name' ] && $refusals['a name of 81 characters'][1]['params'] === [ 80 ] && $edgeFolder[0] === 200 );
ninoCall( $appData, $panel, [ 'id' => $edgeFolder[1]['folder']['id'] ], 'files/folder/delete' );

// A write that cannot be made is the server's failure, not a person's: a 500 with no code, and the index as it was
$indexPath = \Nino\Filesystem::path( $appData, '/data/files.php' );
$indexBytes = (string) file_get_contents( $indexPath );
unlink( $indexPath );
mkdir( $indexPath );
$writeFailed = ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => 'Cannot be written' ], 'files/folder/add' );
rmdir( $indexPath );
file_put_contents( $indexPath, $indexBytes );
check( 'a write that cannot be made is a 500 with no code - and nothing of it is in the index after', $writeFailed[0] === 500 && isset( $writeFailed[1]['code'] ) === false && trim( (string) ( $writeFailed[1]['error'] ?? '' ) ) !== ''
	&& file_get_contents( $indexPath ) === $indexBytes && array_filter( filesIndex( $appData )['folders'], static fn( array $folder ): bool => $folder['name'] === 'Cannot be written' ) === [] );

// Rename
$renamed = ninoCall( $appData, $panel, [ 'id' => $newId, 'name' => ' Tapes of the concert ' ], 'files/folder/save' );
$renameRefused = [ ninoCall( $appData, $panel, [ 'id' => $newId, 'name' => '' ], 'files/folder/save' ), ninoCall( $appData, $panel, [ 'id' => $newId, 'name' => str_repeat( 'x', 81 ) ], 'files/folder/save' ),
	ninoCall( $appData, $panel, [ 'id' => 'ffffffffffff', 'name' => 'x' ], 'files/folder/save' ), ninoCall( $appData, $panel, [ 'id' => '../x', 'name' => 'x' ], 'files/folder/save' ) ];
check( 'a folder is renamed, with the rules of a name; an id nobody has, or that is none, is 404 files_unknown - and the folder keeps its name through the refusals',
	$renamed[0] === 200 && $renamed[1]['folder']['name'] === 'Tapes of the concert' && array_map( static fn( array $answer ): string => $answer[0]. ' '. $answer[1]['code'], $renameRefused ) === [ '400 files_name', '400 files_long', '404 files_unknown', '404 files_unknown' ]
	&& filesIndex( $appData )['folders'][$newId]['name'] === 'Tapes of the concert' );

// The mark: exactly one folder of a group has it after another is marked
$secondId = (string) ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => 'Second' ], 'files/folder/add' )[1]['folder']['id'];
ninoCall( $appData, $panel, [ 'id' => $newId ], 'files/folder/upload-target' );
$moved = ninoCall( $appData, $panel, [ 'id' => $secondId ], 'files/folder/upload-target' );
$staffMarks = array_filter( filesIndex( $appData )['folders'], static fn( array $folder ): bool => $folder['group'] === 'staff' && $folder['upload'] === true );
check( 'marking a second folder of a group does not refuse and does not leave two: exactly one folder of the group carries the mark, the one marked last, and the answer says so - the folders of other groups keep theirs',
	$moved[0] === 200 && $moved[1]['folder']['upload'] === true && array_map( 'strval', array_keys( $staffMarks ) ) === [ $secondId ] && filesIndex( $appData )['folders'][$lobby['id']]['upload'] === false );
$unmarked = ninoCall( $appData, $panel, [ 'id' => $secondId, 'on' => false ], 'files/folder/upload-target' );
$unknownMark = ninoCall( $appData, $panel, [ 'id' => 'ffffffffffff' ], 'files/folder/upload-target' );
check( '...the mark is taken off with on false, and a folder nobody has is 404 files_unknown', $unmarked[0] === 200 && $unmarked[1]['folder']['upload'] === false && $unknownMark[0] === 404 && $unknownMark[1]['code'] === 'files_unknown'
	&& array_filter( filesIndex( $appData )['folders'], static fn( array $folder ): bool => $folder['group'] === 'staff' && $folder['upload'] === true ) === [] );
ninoCall( $appData, $panel, [ 'id' => $room['id'] ], 'files/folder/upload-target' );

// Files: change
$toggle = filesMake( $appData, $secondId, 'Toggle.mp3', 'TOGGLE-BYTES', [ 'kind' => 'owner', 'id' => '' ], false );
$otherFile = filesMake( $appData, $newId, 'Other.mp3', 'OTHER-BYTES' );
$shown = ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'visible' => true ], 'files/save' );
check( 'a file is made visible: the answer has it, the index says so after a fresh read, and nothing else of it changed', $shown[0] === 200 && $shown[1]['file']['visible'] === true && filesIndex( $appData )['files'][$toggle['id']]['visible'] === true
	&& array_diff_assoc( filesIndex( $appData )['files'][$toggle['id']], $toggle ) === [ 'visible' => true ] );
ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'visible' => false ], 'files/save' );
$ignored = ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'visible' => 'yes', 'size' => 1, 'mime' => 'text/html', 'ext' => 'html', 'folder' => 7, 'by' => [ 'kind' => 'member', 'id' => $ada['id'] ] ], 'files/save' );
check( '...and hidden again; what is posted as something else than a flag changes nothing, and neither does anything but the flags, the folder and the name - the size, the type, the extension and who put it there are not the panel\'s to change',
	filesIndex( $appData )['files'][$toggle['id']]['visible'] === false && $ignored[0] === 200 && filesIndex( $appData )['files'][$toggle['id']] === array_diff_key( $toggle, [ 'id' => 1 ] ) );

// The name of a file: renamed in the record, with the rules of a name - the extension stays, a version stays a version, nothing is a name of nothing
$renamedFile = ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'name' => ' Renamed One.MP3 ' ], 'files/save' );
$renameRules = [ 'no name' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'name' => '..' ], 'files/save' ), 'another extension' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'name' => 'Toggle.wav' ], 'files/save' ),
	'a path' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'name' => '../../etc/Evil.mp3' ], 'files/save' ), 'a list' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'name' => [ 'x.mp3' ] ], 'files/save' ) ];
check( 'a file is renamed: the name is cleaned as an upload\'s is, the extension is the one it has, the bytes stay where they are - an empty name and another extension are 400 files_filename and files_extension at the field name, a path is cut to its last segment, a list is no name',
	$renamedFile[0] === 200 && $renamedFile[1]['file']['name'] === 'Renamed One.mp3' && $renamedFile[1]['file']['ext'] === 'mp3' && $renameRules['no name'][0] === 400 && $renameRules['no name'][1]['code'] === 'files_filename' && $renameRules['no name'][1]['field'] === 'name'
	&& $renameRules['another extension'][1]['code'] === 'files_extension' && $renameRules['a path'][0] === 200 && $renameRules['a path'][1]['file']['name'] === 'Evil.mp3' && $renameRules['a list'][0] === 200 && $renameRules['a list'][1]['file']['name'] === 'Evil.mp3'
	&& is_file( (string) \Nino\Modules\Files::path( $appData, $toggle['id'] ) ) === true );
ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'name' => 'Toggle.mp3' ], 'files/save' );
$versioned = filesMake( $appData, $secondId, 'Song.03.mp3', 'VERSION-BYTES' );
$versionBroken = ninoCall( $appData, $panel, [ 'id' => $versioned['id'], 'name' => 'Song.mp3' ], 'files/save' );
$finalSet = ninoCall( $appData, $panel, [ 'id' => $versioned['id'], 'final' => true, 'visible' => 'x' ], 'files/save' );
check( '...a name that says a version has to keep saying one (400 files_version), and the mark final is a flag the panel sets: true is the mark, anything else changes nothing', $versionBroken[0] === 400 && $versionBroken[1]['code'] === 'files_version' && $finalSet[0] === 200 && $finalSet[1]['file']['final'] === true
	&& \Nino\Modules\Files::file( $appData, $versioned['id'] )['name'] === 'Song.03.mp3' );
\Nino\Modules\Files\Store::deleteFile( $appData, $versioned['id'] );

$movedFile = ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'folder' => $newId ], 'files/save' );
$moveRefused = [ 'a folder of another group' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'folder' => $lobby['id'] ], 'files/save' ), 'a folder nobody has' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'folder' => 'ffffffffffff' ], 'files/save' ),
	'something that is no folder' => ninoCall( $appData, $panel, [ 'id' => $toggle['id'], 'folder' => '../x' ], 'files/save' ), 'a file nobody has' => ninoCall( $appData, $panel, [ 'id' => 'ffffffffffff', 'visible' => true ], 'files/save' ) ];
check( 'a file moves to another folder of its group; a folder of another group, one nobody has and one that is none are 400 files_target with the field folder, a file nobody has 404 files_unknown - and the file stays where it was',
	$movedFile[0] === 200 && $movedFile[1]['file']['folder'] === $newId && array_map( static fn( array $answer ): string => $answer[0]. ' '. $answer[1]['code'], $moveRefused ) === [ 'a folder of another group' => '400 files_target', 'a folder nobody has' => '400 files_target',
		'something that is no folder' => '400 files_target', 'a file nobody has' => '404 files_unknown' ] && $moveRefused['a folder nobody has'][1]['field'] === 'folder' && filesIndex( $appData )['files'][$toggle['id']]['folder'] === $newId );

// Delete: a file, then a folder with files
$told = [];
$before = filesIndex( $appData );
$path = (string) \Nino\Modules\Files::path( $appData, $otherFile['id'] );
$deleted = ninoCall( $appData, $panel, [ 'id' => $otherFile['id'] ], 'files/delete' );
check( 'delete removes the entry and the bytes - that file and no other - and tells /files/delete with the entry as it was', $deleted[0] === 200 && isset( filesIndex( $appData )['files'][$otherFile['id']] ) === false && is_file( $path ) === false
	&& filesIndex( $appData )['files'] === array_diff_key( $before['files'], [ $otherFile['id'] => 1 ] ) && filesIndex( $appData )['folders'] === $before['folders'] && $told === [ [ '/files/delete', $otherFile ] ] );
$deleteAgain = ninoCall( $appData, $panel, [ 'id' => $otherFile['id'] ], 'files/delete' );
$deleteBad = ninoCall( $appData, $panel, [ 'id' => '../etc/passwd' ], 'files/delete' );
check( '...the same file again, and an id that is none, are 404 files_unknown and change nothing', [ $deleteAgain[0], $deleteAgain[1]['code'], $deleteBad[0], $deleteBad[1]['code'] ] === [ 404, 'files_unknown', 404, 'files_unknown' ] );

// Remove from the server: the bytes go, the row stays, nobody is told of a deletion
$taking = filesMake( $appData, $room['id'], 'Take Away.mp3', 'TAKE-AWAY-BYTES', [ 'kind' => 'owner', 'id' => '' ], false );
$takingPath = (string) \Nino\Modules\Files::path( $appData, $taking['id'] );
$told = [];
$mailsBefore = count( $mails );
$beforeRemove = filesIndex( $appData );
$taken = ninoCall( $appData, $panel, [ 'id' => $taking['id'] ], 'files/remove' );
$takenLine = \Nino\Modules\Files\Admin::log( 'files/remove', [ 'id' => $taking['id'], 'name' => 'ada@example.org' ] );
check( 'remove takes the bytes of that file and no other: the answer has the entry with gone and says it changed, the index differs from before in that one field, the bytes are gone - and no callback hears of a deletion',
	$taken[0] === 200 && $taken[1]['changed'] === true && ( $taken[1]['file']['gone'] ?? '' ) !== '' && is_file( $takingPath ) === false && filesIndex( $appData )['files'][$taking['id']] === array_diff_key( $taken[1]['file'], [ 'id' => 1 ] )
	&& array_diff_key( filesIndex( $appData )['files'], [ $taking['id'] => 1 ] ) === array_diff_key( $beforeRemove['files'], [ $taking['id'] => 1 ] ) && filesIndex( $appData )['folders'] === $beforeRemove['folders'] && $told === [] );
check( '...its line of the log names the id and neither the name nor the address in the post',
	str_contains( $takenLine, $taking['id'] ) === true && str_contains( $takenLine, 'ada@example.org' ) === false && str_contains( $takenLine, 'Take Away' ) === false );
$takenAgain = ninoCall( $appData, $panel, [ 'id' => $taking['id'] ], 'files/remove' );
$againIndex = filesIndex( $appData );
check( '...the same file again is 200 with the same entry, says it changed nothing, writes the index no further - and has no line of the log, since nothing happened',
	$takenAgain[0] === 200 && $takenAgain[1]['changed'] === false && $takenAgain[1]['file'] === $taken[1]['file'] && $againIndex === filesIndex( $appData ) && $againIndex['files'][$taking['id']]['gone'] === $taken[1]['file']['gone']
	&& \Nino\Modules\Files\Admin::log( 'files/remove', [ 'id' => $taking['id'] ] ) === '' );
$takenBad = [ ninoCall( $appData, $panel, [ 'id' => 'ffffffffffff' ], 'files/remove' ), ninoCall( $appData, $panel, [ 'id' => '../etc/passwd' ], 'files/remove' ), ninoCall( $appData, $panel, [], 'files/remove' ) ];
check( '...an id nobody has, one that is none and none at all are 404 files_unknown and change nothing', array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ), $takenBad ) === [ '404 files_unknown', '404 files_unknown', '404 files_unknown' ] && filesIndex( $appData ) === $againIndex );

$listed = ninoCall( $appData, $panel, [ 'group' => 'staff' ], 'files/list' );
$listedTaking = array_values( array_filter( $listed[1]['files'], static fn( array $file ): bool => $file['id'] === $GLOBALS['taking']['id'] ) )[0] ?? [];
$listedOther = array_values( array_filter( $listed[1]['files'], static fn( array $file ): bool => $file['id'] === $GLOBALS['track']['id'] ) )[0] ?? [];
check( 'the list of the panel carries gone for a file that was removed and none for one that was not - and a file that was removed is made visible without a mail to the group, which a file with its bytes is not spared',
	( $listedTaking['gone'] ?? '' ) === $taken[1]['file']['gone'] && array_key_exists( 'gone', $listedOther ) === false && $listedOther !== []
	&& ( ninoCall( $appData, $panel, [ 'id' => $taking['id'], 'visible' => true, 'notify' => true ], 'files/save' )[0] === 200 && count( $mails ) === $mailsBefore ) );
$bulk = filesMake( $appData, $room['id'], 'Bulk.mp3', str_repeat( 'x', 3 * 1048576 ) );
$tileFull = \Nino\Modules\Files\Admin::summary( $appData )['value'];
ninoCall( $appData, $panel, [ 'id' => $bulk['id'] ], 'files/remove' );
$tileTaken = \Nino\Modules\Files\Admin::summary( $appData )['value'];
$fills = \Nino\Admin\Admin::textFills( $appData, '/features/Files/text', \Nino\Admin\Admin::sessionLocale( $appData ) );
$unitWords = [];

foreach( [ 'b' => 'byte', 'kb' => 'kilobyte', 'mb' => 'megabyte', 'gb' => 'gigabyte', 'separator' => 'separator' ] as $key => $name )
	$unitWords[$key] = (string) ( $fills['[[/_admin/files/unit/'. $name. ']]'] ?? '' );

$keptBytes = static fn(): int => (int) array_sum( array_column( array_filter( filesIndex( $GLOBALS['appData'] )['files'], static fn( array $file ): bool => isset( $file['gone'] ) === false ), 'size' ) );
$entries = count( filesIndex( $appData )['files'] );
check( 'the tile of the dashboard counts the entries and the bytes that are on the server: removing a file of 3 MB leaves the number and takes the size off',
	$tileFull === $entries. ' ('. \Nino\Modules\Files\Store::size( $keptBytes() + 3 * 1048576, $unitWords ). ')' && $tileTaken === $entries. ' ('. \Nino\Modules\Files\Store::size( $keptBytes(), $unitWords ). ')' && $tileFull !== $tileTaken );
$deletedAfter = ninoCall( $appData, $panel, [ 'id' => $taking['id'] ], 'files/delete' );
check( '...and a file that was removed is deleted by the panel whole, with the callback told of it as it was', $deletedAfter[0] === 200 && \Nino\Modules\Files::file( $appData, $taking['id'] ) === null && count( $told ) === 1 && $told[0][0] === '/files/delete' && ( $told[0][1]['gone'] ?? '' ) !== '' );

$kept = filesMake( $appData, $room['id'], 'Survivor.mp3', 'SURVIVOR' );
$told = [];
$inFolder = \Nino\Modules\Files::files( $appData, $newId );
$bytesOfFolder = array_map( static fn( array $file ): string => (string) \Nino\Modules\Files::path( $GLOBALS['appData'], $file['id'] ), $inFolder );
$deletedFolder = ninoCall( $appData, $panel, [ 'id' => $newId ], 'files/folder/delete' );
check( 'a folder is deleted with its files, entries and bytes - and /files/delete is told of each; the files of other folders and the other folders survive',
	$deletedFolder[0] === 200 && $deletedFolder[1] === [ 'deleted' => count( $inFolder ) ] && $inFolder !== [] && isset( filesIndex( $appData )['folders'][$newId] ) === false && array_diff( array_column( $inFolder, 'id' ), array_keys( filesIndex( $appData )['files'] ) ) === array_column( $inFolder, 'id' )
	&& array_filter( $bytesOfFolder, 'is_file' ) === [] && count( $told ) === count( $inFolder ) && \Nino\Modules\Files::file( $appData, $kept['id'] ) === $kept && is_file( (string) \Nino\Modules\Files::path( $appData, $kept['id'] ) ) === true );
$folderAgain = ninoCall( $appData, $panel, [ 'id' => $newId ], 'files/folder/delete' );
check( '...and the same folder again is 404 files_unknown', [ $folderAgain[0], $folderAgain[1]['code'] ] === [ 404, 'files_unknown' ] );

// The log and the tile
$lines = [];
foreach( [ 'files/folder/add', 'files/folder/save', 'files/folder/delete', 'files/folder/upload-target', 'files/save', 'files/delete', 'files/download', 'files/list' ] as $action )
	$lines[$action] = \Nino\Modules\Files\Admin::log( $action, [ 'id' => $kept['id'], 'name' => 'ada@example.org', 'email' => 'ada@example.org' ] );
$lines['files/upload'] = \Nino\Modules\Files\Admin::log( 'files/upload', [ 'id' => $kept['id'], 'name' => 'ada@example.org' ] );
check( 'the activity log has a line for every action that changes something - none for the list - and neither the address nor the name of a member nor the name of a file in any of them, only the id',
	$lines['files/list'] === '' && $lines['files/download'] === '' && array_filter( array_diff_key( $lines, [ 'files/list' => 1, 'files/download' => 1, 'files/upload' => 1 ] ), static fn( string $line ): bool => $line === '' ) === [] && array_filter( $lines, static fn( string $line ): bool => str_contains( $line, 'ada@example.org' ) ) === []
	&& str_contains( $lines['files/delete'], $kept['id'] ) === true && \Nino\Modules\Files\Admin::log( 'files/delete', [ 'id' => '<script>' ] ) === \Nino\Modules\Files\Admin::log( 'files/delete', [] ) );

$loggedFolder = ninoCall( $appData, $panel, [ 'group' => 'staff', 'name' => 'Logged folder' ], 'files/folder/add' );
$addLine = \Nino\Modules\Files\Admin::log( 'files/folder/add', [ 'group' => 'staff', 'name' => 'Logged folder' ] );
$markOn = \Nino\Modules\Files\Admin::log( 'files/folder/upload-target', [ 'id' => $kept['id'], 'on' => true ] );
$markOff = \Nino\Modules\Files\Admin::log( 'files/folder/upload-target', [ 'id' => $kept['id'], 'on' => false ] );
ninoCall( $appData, $panel, [ 'id' => $loggedFolder[1]['folder']['id'] ], 'files/folder/delete' );
check( '...a new folder is logged with its id, and marking a folder and taking the mark off are two lines that name the folder', $addLine !== '' && str_ends_with( $addLine, $loggedFolder[1]['folder']['id'] ) === true && $markOn !== $markOff && str_contains( $markOn, $kept['id'] ) === true && str_contains( $markOff, $kept['id'] ) === true );

$afterEnd = ninoCall( $appData, $panel, [ 'folder' => $room['id'], 'name' => 'Logged.mp3', 'size' => 54, 'upload' => '', 'index' => 0, 'count' => 1, 'visible' => true ], 'files/upload' );
$_FILES = [ 'chunk' => filesPart( $appData, "\xFF\xFB\x90\x00". str_repeat( 'z', 50 ) ) ];
$loggedDone = ninoCall( $appData, $panel, [ 'folder' => $room['id'], 'name' => 'Logged.mp3', 'size' => 54, 'upload' => '', 'index' => 0, 'count' => 1 ], 'files/upload' );
$_FILES = [];
$doneLine = \Nino\Modules\Files\Admin::log( 'files/upload', [ 'folder' => $room['id'], 'name' => 'Logged.mp3' ] );
$_FILES = [ 'chunk' => filesPart( $appData, str_repeat( 'a', $chunkBytes ) ) ];
ninoCall( $appData, $panel, [ 'folder' => $room['id'], 'name' => 'Partial.mp3', 'size' => 2 * $chunkBytes, 'upload' => '', 'index' => 0, 'count' => 2 ], 'files/upload' );
$_FILES = [];
$partLine = \Nino\Modules\Files\Admin::log( 'files/upload', [ 'folder' => $room['id'], 'name' => 'Partial.mp3' ] );
foreach( glob( \Nino\Filesystem::path( $appData, '/data/files/.upload' ). '/*.part' ) ?: [] as $leftover )
	unlink( $leftover );
\Nino\Modules\Files\Store::dropParts( $appData );
check( 'an upload writes its line once, when the last chunk is in, and none for the chunks before it', $loggedDone[0] === 200 && $doneLine !== '' && str_ends_with( $doneLine, $loggedDone[1]['id'] ) === true && $partLine === '' && $afterEnd[0] === 400 );

$tile = \Nino\Modules\Files\Admin::summary( $appData );
check( 'the Dashboard\'s tile counts the files and tells how much room they take, with a label that has its sentence in both languages', preg_match( '/^(\d+) \((.+ (B|KB|MB|GB))\)$/D', $tile['value'], $found ) === 1 && (int) $found[1] === count( filesIndex( $appData )['files'] )
	&& array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => trim( (string) ( \Nino\Admin\Admin::textFills( $GLOBALS['appData'], '/features/Files/text', $locale )['[['. \Nino\Modules\Files\Admin::summary( $GLOBALS['appData'] )['label']. ']]'] ?? '' ) ) === '' ) === [] );

// Every code has its sentence in both languages, every word the panel asks for is a fill of both, and the two files declare the same keys
$sources = (string) file_get_contents( $dir. '/Files.php' ). (string) file_get_contents( $dir. '/Upload/Upload.php' ). (string) file_get_contents( $dir. '/Admin/Admin.php' );
preg_match_all( "/'(files_[a-z]+)'/", $sources, $codes );
// files_post is the code of the plain form only: its sentence is the unit's (state/post), the panel never answers it
$codes = array_values( array_diff( array_unique( $codes[1] ), [ 'files_post' ] ) );
sort( $codes );
$fills = [ 'en_US' => \Nino\Admin\Admin::textFills( $appData, '/features/Files/text', 'en_US' ), 'de_DE' => \Nino\Admin\Admin::textFills( $appData, '/features/Files/text', 'de_DE' ) ];
$missingCodes = [];
foreach( $codes as $code )
	foreach( $fills as $locale => $each )
		if( trim( (string) ( $each['[[/_admin/error/'. $code. ']]'] ?? '' ) ) === '' )
			$missingCodes[] = $code. ' '. $locale;
check( 'the code of every refusal - '. implode( ', ', $codes ). ' - has its sentence in both languages', $codes === [ 'files_chunk', 'files_extension', 'files_filename', 'files_folder', 'files_group', 'files_limit', 'files_long', 'files_name', 'files_quota', 'files_size', 'files_target', 'files_type', 'files_unknown', 'files_version', 'files_write' ] && $missingCodes === [] );
foreach( [ 'files_long', 'files_size', 'files_quota' ] as $code )
	foreach( $fills as $locale => $each )
		check( "...the sentence of $code in $locale takes its number", str_contains( $each['[[/_admin/error/'. $code. ']]'], '%s' ) === true );

// The words the script reads: w( 'name' ) with a name, the ones it puts together from a prefix (the units, the title of a question, the state of a file, the problem of a file) and the keys it names in full
$script = (string) file_get_contents( $dir. '/assets/admin.js' );
preg_match_all( "~\bw\(\s*'([a-z0-9/-]+)'\s*\)~", $script, $named );
preg_match_all( "~'(/_admin/files/[a-z0-9/-]+)'~", $script. (string) file_get_contents( $dir. '/Admin/Admin.php' ), $literal );
$asked = array_map( static fn( string $name ): string => '/_admin/files/'. $name, array_values( array_unique( $named[1] ) ) );
$asked = array_values( array_unique( array_merge( $asked, array_diff( $literal[1], [ '/_admin/files/' ] ),
	array_map( static fn( string $unit ): string => '/_admin/files/unit/'. $unit, [ 'byte', 'kilobyte', 'megabyte', 'gigabyte', 'separator' ] ),
	array_map( static fn( string $kind ): string => '/_admin/files/confirm/'. $kind. '-title', [ 'remove', 'delete' ] ),
	array_map( static fn( string $state ): string => '/_admin/files/state/'. $state, [ 'visible', 'hidden', 'removed' ] ),
	array_map( static fn( string $problem ): string => '/_admin/files/error/'. $problem, [ 'empty', 'type', 'size' ] ), [ '/_admin/files/nav' ] ) ) );
$notThere = [];
foreach( $asked as $key )
	foreach( $fills as $locale => $each )
		if( isset( $each['[['. $key. ']]'] ) === false || trim( (string) $each['[['. $key. ']]'] ) === '' )
			$notThere[] = $key. ' '. $locale;
$declared = [ 'en_US' => array_keys( (array) include $dir. '/text/en_US.php' ), 'de_DE' => array_keys( (array) include $dir. '/text/de_DE.php' ) ];
$unused = array_filter( $declared['en_US'], static fn( string $key ): bool => in_array( substr( $key, 2, -2 ), $GLOBALS['asked'], true ) === false && str_contains( $key, '/_admin/error/files_' ) === false && str_contains( $key, '/files/error/load' ) === false && str_contains( $key, '/files/confirm/' ) === false
	&& str_contains( $GLOBALS['script'], "'". substr( $key, strlen( '[[/_admin/files/' ), -2 ). "'" ) === false );
check( 'every word the panel\'s script and class ask for ('. count( $asked ). ') is a fill both languages define, both files declare the same keys, and the one that nobody asks for is none: '. implode( ', ', array_slice( array_values( $unused ), 0, 5 ) ),
	$notThere === [] && count( $asked ) > 60 && $declared['en_US'] === $declared['de_DE'] && $unused === [] );

// The words of the site: the same keys in both languages, and the ones the code and the templates ask for are there
$siteEn = array_keys( (array) include $dir. '/install/text/en_US.php' );
$siteDe = array_keys( (array) include $dir. '/install/text/de_DE.php' );
$siteAsked = [];
foreach( array_merge( glob( $dir. '/templates/*.tpl' ) ?: [], [ $dir. '/Files.php', $dir. '/assets/files.js' ] ) as $source )
	preg_match_all( "~[\[(']/feature/files/([a-z0-9-]+/[a-z0-9-]+)~", (string) file_get_contents( $source ), $keys ) && $siteAsked = array_merge( $siteAsked, $keys[1] );
$siteAsked = array_values( array_unique( $siteAsked ) );
$wanted = array_map( static fn( string $key ): string => '[[/feature/files/'. $key. ']]', $siteAsked );
$units = [ '[[/feature/files/unit/byte]]', '[[/feature/files/unit/kilobyte]]', '[[/feature/files/unit/megabyte]]', '[[/feature/files/unit/gigabyte]]', '[[/feature/files/unit/separator]]' ];
$states = array_map( static fn( string $name ): string => '[[/feature/files/state/'. $name. ']]', [ 'sent', 'folder', 'type', 'size', 'quota', 'chunk', 'limit', 'write' ] );
check( 'the unit brings the words of the site in both languages with the same keys; the units, the eight states and every word a template, the class or the script names are among them', $siteEn === $siteDe && array_diff( array_merge( $units, $states ), $siteEn ) === []
	&& array_diff( array_filter( $wanted, static fn( string $key ): bool => str_contains( $key, '[[' ) === true && preg_match( '~/mail/|/state/|/unit/|/script/|/upload/|/browser/~', $key ) === 1 && preg_match( '~/mail/(upload|new)-subject~', $key ) !== 1 ), $siteEn ) === [] );

echo "\n";

// --- The mails ---------------------------------------------------------------------

echo "The mails - to the owner when a member uploads, to the group when files are there\n";

filesWorkbench( 'admin' );
$smallFile = "\xFF\xFB\x90\x00". str_repeat( 'm', 100 );

/**
 *	A member's upload of one small file through the route
 *
 *	@param		array 		&$appData
 *	@param		string		$who					ada or cyd
 *	@param		string		$name
 *	@param		string		$folder
 *	@param		string		$bytes
 *
 *	@return 	int											The status of the answer
 */
function filesMemberUpload( array &$appData, string $who, string $name, string $folder, string $bytes ): int {

	filesAs( $who );
	filesFresh( $appData );

	return filesUpload( $appData, 'filesMemberSend', $name, $bytes, [ 'folder' => $folder ] )[0][0];
}

/**
 *	One small file through the panel's action
 *
 *	@param		array 		&$appData
 *	@param		string		$name
 *	@param		string		$folder
 *	@param		array 		$extra				visible
 *
 *	@return 	array										The answer [ status, body ]
 */
function filesOwnerUpload( array &$appData, string $name, string $folder, array $extra = [] ): array {

	filesWorkbench( 'admin' );
	filesFresh( $appData );

	return filesUpload( $appData, 'filesOwnerSend', $name, "\xFF\xFB\x90\x00". str_repeat( 'm', 100 ), [ 'folder' => $folder ] + $extra )[0];
}

$mails = [];
$offStatus = filesMemberUpload( $appData, 'ada', 'Silent.mp3', $room['id'], $smallFile );
check( 'with the setting notifyOwner off - the default - a member\'s upload sends no mail', $offStatus === 200 && $mails === [] );

\Nino\Features::saveSettings( $appData, 'files', [ 'notifyOwner' => true ] );
$mails = [];
$onStatus = filesMemberUpload( $appData, 'ada', 'Mail Take.mp3', $room['id'], $smallFile );
$mail = $mails[0] ?? [];
$text = strip_tags( (string) ( $mail['body'] ?? '' ) );
check( 'with it on one mail goes to the contact address of the website after a member\'s upload: the file named, its folder, the member, a link to the panel on the website\'s address, no key or token left in it',
	$onStatus === 200 && count( $mails ) === 1 && $mail['to'] === 'owner@example.org' && trim( (string) $mail['subject'] ) !== '' && str_contains( (string) $mail['subject'], '[[' ) === false && str_contains( (string) $mail['body'], '[[' ) === false
	&& str_contains( $text, 'Mail Take.mp3' ) === true && str_contains( $text, 'Room' ) === true && str_contains( $text, 'Ada Lovelace' ) === true && str_contains( (string) $mail['body'], 'href="https://example.org/club/_admin#files/files/'. $room['id']. '"' ) === true );

$mails = [];
filesAs( 'ada' );
filesFresh( $appData );
$threeChunks = filesUpload( $appData, 'filesMemberSend', 'Three.mp3', $mp3, [ 'folder' => $room['id'] ] );
check( '...one mail for each upload, and not for each chunk of it', array_column( $threeChunks, 0 ) === [ 200, 200, 200 ] && count( $mails ) === 1 );

$mails = [];
filesAs( 'ada' );
filesFresh( $appData );
$timed = filesUpload( $appData, 'filesMemberSend', 'Timed.mp3', $smallFile, [ 'folder' => $room['id'] ] );
check( 'the mail to the owner is work that is left after the answer: when the response is finished no mail has gone and one piece of work is waiting; it goes once that work has run', $timed[0][0] === 200 && $GLOBALS['filesAfter'] === [ 'mails' => 0, 'work' => 1 ] && count( $mails ) === 1 );

$mails = [];
filesMemberUpload( $appData, 'ada', '<b>bold<b> [csrf] [[x]].mp3', $room['id'], $smallFile );
check( 'a name is text in the mail: markup in it is not an element, and a key in it stays what it was written, as text', count( $mails ) === 1 && filesFind( (string) $mails[0]['body'], '//b' ) === [] && str_contains( strip_tags( (string) $mails[0]['body'] ), '[csrf] [[x]].mp3' ) === true && str_contains( (string) $mails[0]['body'], '_csrf' ) === false
	&& str_contains( (string) $mails[0]['body'], '&lt;b&gt;bold&lt;b&gt;' ) === true );

$mails = [];
$delivery = false;
ninoWarnings();
$refusedMail = filesMemberUpload( $appData, 'ada', 'Unsent.mp3', $room['id'], $smallFile );
$delivery = true;
$unsentWarnings = ninoWarnings();
$unsentId = array_values( array_filter( \Nino\Modules\Files::files( $appData, $room['id'] ), static fn( array $file ): bool => $file['name'] === 'Unsent.mp3' ) )[0]['id'] ?? '';
$waitingOutbox = \Nino\Mail::queued( $appData );
check( 'a mail no transport takes does not undo the upload: the file is in, the answer is the same - and the mail is not lost: it waits in the outbox of the kernel for its next attempt',
	$refusedMail === 200 && $unsentId !== '' && $waitingOutbox['waiting'] === 1 );
foreach( [ \Nino\MailQueue::WAITING, \Nino\MailQueue::DUE ] as $outbox )
	@unlink( \Nino\Filesystem::path( $appData, $outbox ) );
$mails = [];
check( '...and the feature has nothing to say about it: the attempts and the end of them are the outbox\'s - no warning of its own', $unsentWarnings === [] );

// What the outbox refuses is said at the call: the upload is in, one warning names the file by its id and the reason and holds no address
$capBefore = $appData['/nino/mail/cap'] ?? null;
$appData['/nino/mail/cap'] = 1000;
filesFresh( $appData );
for( $n = 0; $n < \Nino\MailQueue::MAX_CLIENT; $n++ )
	\Nino\Mail::queue( $appData, [ 'to' => 'fill'. $n. '@example.net', 'subject' => 's', 'body' => 'b', 'replyTo' => '' ], \Nino\Mail::CLIENT, 'fill/n'. $n );
ninoWarnings();
$fullAnswer = filesUpload( $appData, 'filesMemberSend', 'Full.mp3', $smallFile, [ 'folder' => $room['id'] ] );
$fullWarnings = ninoWarnings();
$fullId = array_values( array_filter( \Nino\Modules\Files::files( $appData, $room['id'] ), static fn( array $file ): bool => $file['name'] === 'Full.mp3' ) )[0]['id'] ?? '';
check( 'a mail the outbox refuses (it holds the most mails of visitors it keeps) does not undo the upload either: the answer is 200, the file is in, and one warning names the file by its id and "full" and holds no address',
	$fullAnswer[0][0] === 200 && $fullId !== '' && count( $fullWarnings ) === 1 && str_contains( $fullWarnings[0], $fullId ) === true && str_contains( $fullWarnings[0], 'full' ) === true && str_contains( $fullWarnings[0], '@' ) === false );
foreach( [ \Nino\MailQueue::WAITING, \Nino\MailQueue::DUE ] as $outbox )
	@unlink( \Nino\Filesystem::path( $appData, $outbox ) );

// The cap of mails an hour of the client is charged when the mail is queued: over it the mail is refused as "ratelimited", the upload is in
$appData['/nino/mail/cap'] = 1;
filesAs( 'ada' );
filesFresh( $appData );
filesUpload( $appData, 'filesMemberSend', 'Cap One.mp3', $smallFile, [ 'folder' => $room['id'] ] );
ninoWarnings();
$capAnswer = filesUpload( $appData, 'filesMemberSend', 'Cap Two.mp3', $smallFile, [ 'folder' => $room['id'] ] )[0][0];
$capWarnings = ninoWarnings();
check( 'over the cap of mails an hour the mail of an upload is refused by the outbox as "ratelimited": the answer is 200 and one warning says so with the id of the file and no address',
	$capAnswer === 200 && count( $capWarnings ) === 1 && str_contains( $capWarnings[0], 'ratelimited' ) === true && str_contains( $capWarnings[0], '@' ) === false );
foreach( [ \Nino\MailQueue::WAITING, \Nino\MailQueue::DUE ] as $outbox )
	@unlink( \Nino\Filesystem::path( $appData, $outbox ) );
if( $capBefore === null )
	unset( $appData['/nino/mail/cap'] );
else
	$appData['/nino/mail/cap'] = $capBefore;
filesFresh( $appData );
$mails = [];

// A request that comes twice - a double click, a retry of the browser - is one mail: the key is the name of the feature and the id of the file
check( 'the mail of an upload is queued under a key that begins with the name of the feature and holds the id of the file, so a request that comes twice is one mail',
	preg_match( '~Mail::queue\( \$appData, \[[^;]*\'files/upload-\'\s*\.\s*\$file\[\'id\'\]~', (string) file_get_contents( dirname( __DIR__ ). '/Files.php' ) ) === 1 );

// ...and so does a mail the kernel's cap of mails an hour refuses: nothing is sent, the upload is in, one warning
filesAs( 'ada' );
filesFresh( $appData );

for( $filler = 0; $filler < 5; $filler++ )
	\Nino\Mail::send( $appData, 'filler@example.org', 'Filler', 'Filler', '' );

$mails = [];
ninoWarnings();
$capped = filesUpload( $appData, 'filesMemberSend', 'Capped.mp3', $smallFile, [ 'folder' => $room['id'] ] )[0];
$cappedWarnings = ninoWarnings();
$cappedId = array_values( array_filter( \Nino\Modules\Files::files( $appData, $room['id'] ), static fn( array $file ): bool => $file['name'] === 'Capped.mp3' ) )[0]['id'] ?? '';
check( '...a mail the cap of mails an hour refuses is told the same way: nothing was sent, the upload is in, and one warning names the file by its id without an address', $capped[0] === 200 && $mails === [] && \Nino\Mail::rateLimited( $appData ) === true
	&& $cappedId !== '' && count( $cappedWarnings ) === 1 && str_contains( $cappedWarnings[0], $cappedId ) === true && str_contains( $cappedWarnings[0], '@' ) === false );
filesFresh( $appData );

ninoWarnings();
$mails = [];
\Nino\Html::addFills( $appData, [ '[[/project/company/contact/email]]' => '' ], '*' );
filesMemberUpload( $appData, 'ada', 'Nobody.mp3', $room['id'], $smallFile );
$noContact = $mails;
$warned = ninoWarnings();
$nobodyId = array_values( array_filter( \Nino\Modules\Files::files( $appData, $room['id'] ), static fn( array $file ): bool => $file['name'] === 'Nobody.mp3' ) )[0]['id'] ?? '';
\Nino\Html::addFills( $appData, [ '[[/project/company/contact/email]]' => 'owner@example.org' ], '*' );
check( 'a website with no contact address sends none, and says so in a warning for the log that names no address - and no file: no mail was tried, so it is not the warning of a mail that failed',
	$noContact === [] && $nobodyId !== '' && count( $warned ) >= 1 && array_filter( $warned, static fn( string $warning ): bool => str_contains( $warning, '@' ) || str_contains( $warning, $GLOBALS['nobodyId'] ) ) === [] );
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyOwner' => false ] );

// To the group
$members = [ $ada, $cyd ];
foreach( [ 'dan' => false, 'eve' => true, 'fay' => true, 'gus' => true, 'hal' => true, 'ivy' => true ] as $name => $active ) {
	$account = \Nino\Modules\Members::add( $appData, $name. '@example.org', ucfirst( $name ), 'staff' );
	\Nino\Modules\Members::change( $appData, $account['id'], [ 'active' => $active ] );
}

$mails = [];
$silent = filesOwnerUpload( $appData, 'Quiet.mp3', $room['id'] );
check( 'with the setting notifyMembers off - the default - the owner\'s upload sends no mail', $silent[0] === 200 && $mails === [] );

$mails = [];
check( '...nothing to send is no failure: with the setting off the group is told nothing and that is true, and so with no file - while for the owner, with the setting off, no mail was delivered: false',
	\Nino\Modules\Files::notifyMembers( $appData, $room, [ $kept ] ) === true && \Nino\Modules\Files::notifyMembers( $appData, $room, [] ) === true && \Nino\Modules\Files::notifyOwner( $appData, $kept, $room, $ada ) === false && $mails === [] );

\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );
$mails = [];
$notified = filesOwnerUpload( $appData, 'Fresh Mix.mp3', $room['id'] );
$recipients = array_column( $mails, 'to' );
sort( $recipients );
$forAda = array_values( array_filter( $mails, static fn( array $each ): bool => $each['to'] === 'ada@example.org' ) )[0] ?? [];
check( 'with it on the owner\'s visible upload sends one mail to each active member of the group - not to the members of other groups, not to one whose account is switched off',
	$notified[0] === 200 && $recipients === [ 'ada@example.org', 'cyd@example.org', 'eve@example.org', 'fay@example.org', 'gus@example.org', 'hal@example.org', 'ivy@example.org' ] && count( $mails ) === 7 );
check( '...each names the file and the folder, greets the member by name, links the website and carries the reply address of the contact - and is in the site\'s language, whatever language the request came in',
	str_contains( strip_tags( (string) $forAda['body'] ), 'Fresh Mix.mp3' ) === true && str_contains( strip_tags( (string) $forAda['body'] ), 'Room' ) === true && str_contains( strip_tags( (string) $forAda['body'] ), 'Ada Lovelace' ) === true
	&& str_contains( (string) $forAda['body'], 'href="https://example.org/club/"' ) === true && $forAda['replyTo'] === 'owner@example.org' && str_contains( (string) $forAda['body'], '[[' ) === false );
// The language of an account, where a module knows one: its mail is written in it, the others' in the native language of the site
$adaId = (string) ( array_values( array_filter( \Nino\Modules\Members::accounts( $appData ), static fn( array $account ): bool => $account['email'] === 'ada@example.org' ) )[0]['id'] ?? '' );
\Nino\Callbacks::registerCallback( $appData, '/members/language', static function( array &$appData, array &$ask ) use ( $adaId ): void { $ask['locale'] = $ask['account'] === $adaId ? 'en_US' : ''; } );
$mails = [];
filesOwnerUpload( $appData, 'Language.mp3', $room['id'] );
unset( $appData['./nino/callbacks']['/members/language'] );
$byAddress = array_column( $mails, null, 'to' );
check( '...an account whose language a module names is mailed in it, and the others of the group in the native language of the site', $adaId !== '' && count( $mails ) === 7 && ( $byAddress['ada@example.org']['subject'] ?? '' ) === (string) \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/files/mail/new-subject]]']
	&& ( $byAddress['cyd@example.org']['subject'] ?? '' ) === (string) \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/files/mail/new-subject]]'] && $byAddress['ada@example.org']['subject'] !== $byAddress['cyd@example.org']['subject'] && $appData['./nino/locales/current'] === 'de_DE' );
$mails = [];
filesOwnerUpload( $appData, '<u>under<u> [csrf] [[x]].mp3', $room['id'] );
check( '...a name is text in the mail to the group as well: markup in it is not an element, and a key in it stays what it was written, as text', count( $mails ) === 7 && filesFind( (string) $mails[0]['body'], '//u' ) === [] && str_contains( (string) $mails[0]['body'], '&lt;u&gt;under' ) === true
	&& str_contains( strip_tags( (string) $mails[0]['body'] ), '[csrf] [[x]].mp3' ) === true && str_contains( (string) $mails[0]['body'], '_csrf' ) === false );
check( '...and the size of each file stands beside its name', preg_match( '/Fresh Mix\.mp3\s*\(\d+ B\)/', strip_tags( (string) $forAda['body'] ) ) === 1 );

// The place of the band at the head of the mail: [mail-head], which a module of the project answers - the feature itself names none
$count = 0;
$bandOf = static function() use ( &$appData, &$mails, $room, &$count ): string {

	$mails = [];
	filesOwnerUpload( $appData, 'Band'. ++$count. '.mp3', $room['id'] );

	return (string) ( ( end( $mails ) ?: [] )['body'] ?? '' );
};
$noBand = $bandOf();
\Nino\Html::addShortcode( $appData, 'mail-head', static fn( array &$appData, mixed $args ): string => '<p data-band="head">BAND</p>' );
$withBand = $bandOf();
array_pop( $appData['./nino/callbacks']['/nino/html/shortcode/mail-head'][5] );
check( 'the feature registers [mail-head] itself, and where no module answers it the mail to the group about an upload goes out with nothing in its place: no shortcode as text, no picture, no table of a band', in_array( 'mail-head', \Nino\Html::shortcodes( $appData ), true ) === true && $noBand !== '' && preg_match( '~\[mail-head\]~', $noBand ) === 0
	&& str_contains( $noBand, 'data-band' ) === false && str_contains( $noBand, '<img' ) === false && str_contains( $noBand, '<table role="presentation" width="100%"' ) === false );
check( '...and where a module answers it, its answer stands in the place, once, before the heading - the feature leaves the place to it', substr_count( $withBand, '<p data-band="head">BAND</p>' ) === 1 && strpos( $withBand, 'data-band' ) < strpos( $withBand, '<h1' ) && preg_match( '~\[mail-head\]~', $withBand ) === 0 );

\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/members-area' ] );
$mails = [];
filesOwnerUpload( $appData, 'Start.mp3', $room['id'] );
\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/' ] );
check( '...and the link is the page the members land on after they sign in, the setting start of the Members feature', count( $mails ) === 7 && str_contains( (string) $mails[0]['body'], 'href="https://example.org/club/members-area"' ) === true );

$mails = [];
$noFiles = \Nino\Modules\Files::notifyMembers( $appData, $room, [] );
$nobodyThere = \Nino\Modules\Files::notifyMembers( $appData, [ 'id' => 'ffffffffffff', 'group' => 'nogroup', 'name' => 'Empty' ], [ $kept ] );
check( '...a group with no active member is mailed nothing, which is no failure - and so is nothing to tell', $nobodyThere === true && $noFiles === true && $mails === [] );

$appData['./nino/locales/current'] = 'en_US';
$mails = [];
filesOwnerUpload( $appData, 'Language.mp3', $room['id'] );
$native = \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/files/mail/new-subject]]'];
$other = \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/files/mail/new-subject]]'];
$currentAfter = $appData['./nino/locales/current'];
$appData['./nino/locales/current'] = 'de_DE';
check( 'the mail goes out in the native language of the site, and the language of the request is back afterwards', $native !== $other && ( $mails[0]['subject'] ?? '' ) === $native && $currentAfter === 'en_US' );

check( 'a group of seven is mailed in full, though the default cap of the kernel is five mails an hour from one address: one upload is one hit of the cap', count( $mails ) === 7 && \Nino\Mail::rateLimited( $appData ) === false );
filesNotifyChecks( $appData, $room, $panel );
filesMoveChecks( $appData );
filesCopyChecks( $appData );
filesRenameChecks( $appData );
filesWriteChecks( $appData );

$mails = [];
$hiddenOne = filesOwnerUpload( $appData, 'Hidden Mix.mp3', $room['id'], [ 'visible' => false ] );
check( 'a hidden upload sends none', $hiddenOne[0] === 200 && $mails === [] );

$mails = [];
filesWorkbench( 'admin' );
filesFresh( $appData );
ninoCall( $appData, $panel, [ 'id' => $hiddenOne[1]['id'], 'visible' => true ], 'files/save' );
$madeVisible = count( $mails );
$mails = [];
filesFresh( $appData );
ninoCall( $appData, $panel, [ 'id' => $hiddenOne[1]['id'], 'visible' => true ], 'files/save' );
ninoCall( $appData, $panel, [ 'id' => $hiddenOne[1]['id'], 'visible' => false ], 'files/save' );
check( 'making a file visible sends one mail to each active member - once; a file that is visible already, and one that is hidden again, send none', $madeVisible === 7 && $mails === [] );

$mails = [];
filesFresh( $appData );
$delivery = false;
ninoWarnings();
$undelivered = filesOwnerUpload( $appData, 'Unsent Mix.mp3', $room['id'] );
$delivery = true;
$undeliveredWarnings = ninoWarnings();
check( 'mails no transport takes do not undo the upload either', $undelivered[0] === 200 && \Nino\Modules\Files::file( $appData, (string) $undelivered[1]['id'] ) !== null );
check( '...they leave one warning for the lot, which names the file by its id and holds no address', count( $undeliveredWarnings ) === 1 && str_contains( $undeliveredWarnings[0], (string) $undelivered[1]['id'] ) === true && str_contains( $undeliveredWarnings[0], '@' ) === false );

// ...and when the cap of mails an hour refuses the whole call, the same
filesWorkbench( 'admin' );
filesFresh( $appData );

for( $filler = 0; $filler < 5; $filler++ )
	\Nino\Mail::send( $appData, 'filler@example.org', 'Filler', 'Filler', '' );

$mails = [];
ninoWarnings();
$cappedGroup = filesUpload( $appData, 'filesOwnerSend', 'Capped Mix.mp3', "\xFF\xFB\x90\x00". str_repeat( 'm', 100 ), [ 'folder' => $room['id'] ] )[0];
$cappedGroupWarnings = ninoWarnings();
check( '...a call the cap refuses sends none of the seven mails and leaves one warning with the id of the file and no address', $cappedGroup[0] === 200 && $mails === [] && \Nino\Mail::rateLimited( $appData ) === true && count( $cappedGroupWarnings ) === 1
	&& str_contains( $cappedGroupWarnings[0], (string) $cappedGroup[1]['id'] ) === true && str_contains( $cappedGroupWarnings[0], '@' ) === false );
filesFresh( $appData );

ninoWarnings();
$mails = [];
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => '' ], '*' );
$noAddress = filesOwnerUpload( $appData, 'No Address.mp3', $room['id'] );
$warnedNoAddress = ninoWarnings();
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => 'example.org/club' ], '*' );
check( 'a site without an address has no link to put in a mail: none is sent, the upload is in, and a warning says so without naming a member', $noAddress[0] === 200 && $mails === [] && count( $warnedNoAddress ) >= 1
	&& array_filter( $warnedNoAddress, static fn( string $warning ): bool => str_contains( $warning, '@' ) ) === [] );
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => false ] );

// The owner's choice for one action: notify true mails whatever the setting says, false mails nobody whatever it says, absent - or anything but a bool - is the setting
$mails = [];
filesOwnerUpload( $appData, 'Choice Yes.mp3', $room['id'], [ 'notify' => true ] );
$yesWithSettingOff = count( $mails );
$mails = [];
filesOwnerUpload( $appData, 'Choice Absent Off.mp3', $room['id'] );
$absentWithSettingOff = count( $mails );
$mails = [];
filesOwnerUpload( $appData, 'Choice Text Off.mp3', $room['id'], [ 'notify' => 'true' ] );
$textWithSettingOff = count( $mails );
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );
$mails = [];
filesOwnerUpload( $appData, 'Choice No.mp3', $room['id'], [ 'notify' => false ] );
$noWithSettingOn = count( $mails );
$mails = [];
filesOwnerUpload( $appData, 'Choice Absent On.mp3', $room['id'] );
$absentWithSettingOn = count( $mails );
$mails = [];
filesOwnerUpload( $appData, 'Choice Text On.mp3', $room['id'], [ 'notify' => 'false' ] );
$textWithSettingOn = count( $mails );
check( 'the upload takes notify: true mails the group with the setting off, false mails nobody with the setting on, and absent is the setting either way - as is a notify that is no bool',
	$yesWithSettingOff === 7 && $absentWithSettingOff === 0 && $textWithSettingOff === 0 && $noWithSettingOn === 0 && $absentWithSettingOn === 7 && $textWithSettingOn === 7 );

$mails = [];
$silentOne = filesOwnerUpload( $appData, 'Choice Chunked.mp3', $room['id'], [ 'notify' => false ] );
check( '...a file that is not mailed is in all the same, visible', $silentOne[0] === 200 && ( \Nino\Modules\Files::file( $appData, (string) $silentOne[1]['id'] )['visible'] ?? null ) === true );

$hiddenTwo = filesOwnerUpload( $appData, 'Choice Hidden.mp3', $room['id'], [ 'visible' => false, 'notify' => true ] );
check( '...and a hidden file sends none whatever notify says - there is nothing for the members to see', $hiddenTwo[0] === 200 && $mails === [] );

$swapped = [];
foreach( [ [ true, false ], [ false, true ], [ null, true ] ] as [ $notify, $setting ] ) {
	\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => $setting ] );
	$again = filesOwnerUpload( $appData, 'Choice Swap '. count( $swapped ). '.mp3', $room['id'], [ 'visible' => false ] );
	$mails = [];
	filesWorkbench( 'admin' );
	filesFresh( $appData );
	ninoCall( $appData, $panel, [ 'id' => $again[1]['id'], 'visible' => true ] + ( $notify === null ? [] : [ 'notify' => $notify ] ), 'files/save' );
	$swapped[] = count( $mails );
}
check( 'making a file visible takes notify the same way: true mails with the setting off, false mails nobody with it on, absent is the setting', $swapped === [ 7, 0, 7 ] );

// What the panel answers says whether the members were told: mailed, the number of mails delivered
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => false ] );
$told = filesOwnerUpload( $appData, 'Told.mp3', $room['id'], [ 'notify' => true ] );
$toldLog = \Nino\Modules\Files\Admin::log( 'files/upload', [ 'notify' => true ] );
$untold = filesOwnerUpload( $appData, 'Untold.mp3', $room['id'], [ 'notify' => false ] );
$untoldLog = \Nino\Modules\Files\Admin::log( 'files/upload', [ 'notify' => false ] );
$noMail = filesOwnerUpload( $appData, 'No Mail.mp3', $room['id'] );
$hiddenAgain = filesOwnerUpload( $appData, 'Hidden Again.mp3', $room['id'], [ 'visible' => false, 'notify' => true ] );
filesWorkbench( 'admin' );
filesFresh( $appData );
$savedTold = ninoCall( $appData, $panel, [ 'id' => $hiddenAgain[1]['id'], 'visible' => true, 'notify' => true ], 'files/save' );
$savedPlain = ninoCall( $appData, $panel, [ 'id' => $hiddenAgain[1]['id'], 'folder' => $room['id'] ], 'files/save' );
check( 'the answer of the last chunk and of a switch to visible says how many mails were delivered - 7 where the group was told, 0 where nobody was, whatever the box said', ( $told[1]['mailed'] ?? -1 ) === 7 && ( $untold[1]['mailed'] ?? -1 ) === 0 && ( $noMail[1]['mailed'] ?? -1 ) === 0
	&& ( $savedTold[1]['mailed'] ?? -1 ) === 7 && ( $savedPlain[1]['mailed'] ?? -1 ) === 0 );
check( '...and the line of the log for an upload that was not mailed says so, where a mailed one does not', str_contains( $untoldLog, 'not mailed' ) === true && str_contains( $toldLog, 'not mailed' ) === false );
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );

// The page the mail links: a caller that knows a better one names it - the field page of the owner's upload and of the switch to visible, the argument of notifyMembers() - and a text that is no path of the site is the page members land on
$linkOf = static function( array $mail ): string {

	return preg_match( '~href="(https://example\.org/club[^"]*)"~', (string) ( $mail['body'] ?? '' ), $found ) === 1 ? $found[1] : '';
};
$mails = [];
filesOwnerUpload( $appData, 'Page One.mp3', $room['id'], [ 'page' => '/portal/projekte?projekt=00001&tab=dateien' ] );
$pageLinks = array_unique( array_map( $linkOf, $mails ) );
$mails = [];
filesOwnerUpload( $appData, 'Page Two.mp3', $room['id'] );
$startLinks = array_unique( array_map( $linkOf, $mails ) );
check( 'the owner\'s upload takes page: the mail to each member links that page - its query escaped for the markup - and without it the page members land on, the start page', count( $pageLinks ) === 1 && array_values( $pageLinks ) === [ 'https://example.org/club/portal/projekte?projekt=00001&amp;tab=dateien' ]
	&& array_values( $startLinks ) === [ 'https://example.org/club/' ] );

$refusedPages = [];

foreach( [ 'another host' => '//evil.example/x', 'a scheme' => 'https://evil.example/', 'a colon before the next slash' => '/javascript:alert(1)', 'a backslash' => '/\\evil.example', 'an endpoint' => '/.members/logout', 'a line break' => "/portal\nBcc: a@example.org", 'a null byte' => "/portal\0", 'a blank' => '/portal x', 'no slash' => 'portal', 'more than '. \Nino\Modules\Files::PAGE_MAX. ' characters' => '/p?x='. str_repeat( 'a', \Nino\Modules\Files::PAGE_MAX ), 'a number' => 5, 'an array' => [ '/portal' ] ] as $label => $page ) {
	$mails = [];
	filesOwnerUpload( $appData, 'Refused Page.mp3', $room['id'], [ 'page' => $page ] );
	$refusedPages[$label] = count( $mails ) === 7 && array_values( array_unique( array_map( $linkOf, $mails ) ) ) === [ 'https://example.org/club/' ];
}

check( '...a page that is none of the site\'s - another host, a scheme, a colon before the next slash, a backslash, an endpoint, a line break, a null byte, a blank, no slash, too long, a number, an array - is the start page, and the upload is in all the same', array_keys( array_filter( $refusedPages, static fn( bool $same ): bool => $same === false ) ) === [] && count( $refusedPages ) === 12 );
check( '...the rule is page(): 500 characters pass, 501 do not, nor does a path with an endpoint or two slashes at its head', \Nino\Modules\Files::page( '/'. str_repeat( 'a', \Nino\Modules\Files::PAGE_MAX - 1 ) ) === true && \Nino\Modules\Files::page( '/'. str_repeat( 'a', \Nino\Modules\Files::PAGE_MAX ) ) === false && \Nino\Modules\Files::page( '/portal' ) === true
	&& \Nino\Modules\Files::page( '/portal?a=1&b=%20' ) === true && \Nino\Modules\Files::page( '/.files/x' ) === false && \Nino\Modules\Files::page( '//portal' ) === false && \Nino\Modules\Files::page( '' ) === false );

$mails = [];
\Nino\Modules\Files::notifyMembers( $appData, $room, [ $kept ], true, '/portal/projekte?projekt=00002&tab=dateien' );
$direct = array_values( array_unique( array_map( $linkOf, $mails ) ) );
$mails = [];
\Nino\Modules\Files::notifyMembers( $appData, $room, [ $kept ], true, '//evil.example' );
$directRefused = array_values( array_unique( array_map( $linkOf, $mails ) ) );
$mails = [];
\Nino\Modules\Files::notifyMembers( $appData, $room, [ $kept ], true, null );
$directNone = array_values( array_unique( array_map( $linkOf, $mails ) ) );
check( '...notifyMembers() takes it as its fifth argument: a page, a text that is none, and null', $direct === [ 'https://example.org/club/portal/projekte?projekt=00002&amp;tab=dateien' ] && $directRefused === [ 'https://example.org/club/' ] && $directNone === [ 'https://example.org/club/' ] );

\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => false ] );
$hiddenPage = filesOwnerUpload( $appData, 'Hidden Page.mp3', $room['id'], [ 'visible' => false ] );
$mails = [];
filesWorkbench( 'admin' );
filesFresh( $appData );
ninoCall( $appData, $panel, [ 'id' => $hiddenPage[1]['id'], 'visible' => true, 'notify' => true, 'page' => '/portal/projekte?projekt=00003&tab=dateien' ], 'files/save' );
check( '...and so does the switch to visible: the mail links the page the action names', count( $mails ) === 7 && array_values( array_unique( array_map( $linkOf, $mails ) ) ) === [ 'https://example.org/club/portal/projekte?projekt=00003&amp;tab=dateien' ] );
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );

$mails = [];
filesFresh( $appData );
$moved = ninoCall( $appData, $panel, [ 'id' => $hiddenTwo[1]['id'], 'visible' => true, 'notify' => 1 ], 'files/save' );
check( '...and a notify that is no bool is the setting there too', count( $mails ) === 7 && $moved[0] === 200 );

\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );
filesFresh( $appData );
$listed = ninoCall( $appData, $panel, [ 'group' => 'staff' ], 'files/list' );
\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => false ] );
filesFresh( $appData );
$listedOff = ninoCall( $appData, $panel, [ 'group' => 'staff' ], 'files/list' );
check( 'the list says the setting as notify, for the box of the screen to start from', ( $listed[1]['notify'] ?? null ) === true && ( $listedOff[1]['notify'] ?? null ) === false );

echo "\n";


// --- Delete, restore, deactivation --------------------------------------------------

echo "The restore of a backup, and deactivation\n";

/**
 *	A staging directory that can be read and not written, for the restore that cannot put its filtered index back: the stream
 *	wrapper "filesblock" answers the staged index and refuses every open for writing. (A permission cannot do this here -
 *	the suite may run as the account that owns every file)
 */
class FilesBlockedStage {

	public mixed $context = null;
	public static string $index = '';
	private int $at = 0;

	public function url_stat( string $path, int $flags ): array|false {
		return str_ends_with( $path, '/data/files.php' ) === true ? [ 'mode' => 0100644, 'size' => strlen( self::$index ) ] : false;
	}

	public function stream_open( string $path, string $mode, int $options, ?string &$opened ): bool {
		$this->at = 0;
		return str_starts_with( $mode, 'r' ) === true;
	}

	public function stream_read( int $count ): string|false {
		$chunk = substr( self::$index, $this->at, $count );
		$this->at += strlen( $chunk );
		return $chunk;
	}

	public function stream_eof(): bool {
		return $this->at >= strlen( self::$index );
	}

	public function stream_stat(): array|false {
		return [ 'mode' => 0100644, 'size' => strlen( self::$index ) ];
	}

	public function stream_set_option( int $option, int $arg1, ?int $arg2 ): bool {
		return false;
	}
}

stream_wrapper_register( 'filesblock', 'FilesBlockedStage' );

// A backup brings the index of its day and not the bytes: what a restore keeps is what has bytes
$staging = $sandbox. '/restore-staging';
mkdir( $staging. '/data', 0755, true );
$dataDir = \Nino\Filesystem::path( $appData, '/data' );
$live = \Nino\Modules\Files::files( $appData, $room['id'] )[0];
$staged = [ 'folders' => [ $room['id'] => [ 'group' => 'staff', 'name' => 'Room', 'upload' => true, 'created' => '2026-01-01T00:00:00Z' ] ], 'files' => [
	$live['id'] => array_diff_key( $live, [ 'id' => 1 ] ),
	'aaaaaaaaaaaa' => [ 'folder' => $room['id'], 'name' => 'Gone.mp3', 'ext' => 'mp3', 'size' => 5, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '2026-01-01T00:00:00Z' ],
	'bbbbbbbbbbbb' => [ 'folder' => $room['id'], 'name' => 'Hostile.mp3', 'ext' => '../x', 'size' => 5, 'mime' => '', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '' ],
	// An id of digits only, which php reads back as a whole number: its bytes are there, so it stays
	'567890123456' => [ 'folder' => $room['id'], 'name' => 'Digits.mp3', 'ext' => 'mp3', 'size' => 3, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '' ],
] ];
file_put_contents( $dataDir. '/files/567890123456.mp3', 'abc' );
file_put_contents( $staging. '/data/files.php', '<?php return '. var_export( $staged, true ). ';' );
$args = [ 'dataDir' => $dataDir, 'staging' => $staging ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $args );
$restored = (array) include $staging. '/data/files.php';
unlink( $dataDir. '/files/567890123456.mp3' );
check( 'a restore keeps every entry of the backup that is an entry (an id of digits among them; one that names an extension that is none is dropped) and leaves the folders: an entry whose bytes are there stays as it is, one whose bytes are not on the server is marked as removed from it, with the time of the restore - the row stays',
	array_map( 'strval', array_keys( $restored['files'] ) ) === [ $live['id'], 'aaaaaaaaaaaa', '567890123456' ] && $restored['folders'] === $staged['folders'] && $restored['files'][$live['id']] === array_diff_key( $live, [ 'id' => 1 ] )
	&& $restored['files']['567890123456'] === \Nino\Modules\Files\Store::parse( [ 'folders' => $staged['folders'], 'files' => [ '567890123456' => $staged['files']['567890123456'] ] ] )['files']['567890123456']
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) ( $restored['files']['aaaaaaaaaaaa']['gone'] ?? '' ) ) === 1 && array_diff_key( $restored['files']['aaaaaaaaaaaa'], [ 'gone' => 1 ] ) === array_diff_key( \Nino\Modules\Files\Store::parse( $staged )['files']['aaaaaaaaaaaa'], [ 'gone' => 1 ] ) );
// An entry that was removed from the server has no bytes and stays - and so does one the live index has removed since the day of the backup
$staging2 = $sandbox. '/restore-removed';
mkdir( $staging2. '/data', 0755, true );
$since = filesMake( $appData, $room['id'], 'Removed Since.mp3', 'SINCE-BYTES' );
\Nino\Modules\Files\Store::removeBytes( $appData, $since['id'] );
$liveSince = \Nino\Modules\Files::file( $appData, $since['id'] );
$entryOf = static fn( array $file ): array => array_diff_key( $file, [ 'id' => 1 ] );
$stagedRemoved = [ 'folders' => $staged['folders'], 'files' => [
	'dddddddddddd' => [ 'folder' => $room['id'], 'name' => 'Removed Then.mp3', 'ext' => 'mp3', 'size' => 5, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'created' => '2026-01-01T00:00:00Z', 'gone' => '2026-02-02T02:02:02Z' ],
	$since['id'] => $entryOf( $since ),
	'aaaaaaaaaaaa' => $staged['files']['aaaaaaaaaaaa'],
] ];
file_put_contents( $staging2. '/data/files.php', '<?php return '. var_export( $stagedRemoved, true ). ';' );
$argsRemoved = [ 'dataDir' => $dataDir, 'staging' => $staging2 ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $argsRemoved );
$restoredRemoved = (array) include $staging2. '/data/files.php';
check( 'a restore keeps an entry that was removed from the server, with the time it carries, and takes over the removal of a file the live index has removed since the backup - and marks the entry that has neither bytes nor a removal as removed',
	array_map( 'strval', array_keys( $restoredRemoved['files'] ) ) === [ 'dddddddddddd', $since['id'], 'aaaaaaaaaaaa' ] && isset( $restoredRemoved['files']['aaaaaaaaaaaa']['gone'] ) === true && $restoredRemoved['files']['dddddddddddd']['gone'] === '2026-02-02T02:02:02Z' && $restoredRemoved['files'][$since['id']]['gone'] === $liveSince['gone'] );
\Nino\Modules\Files\Store::deleteFile( $appData, $since['id'] );

// A staged index that can be read and not written: the filtered index cannot be put back. The write is not silenced - php says so in a warning -
// and the staged index is what it was, since a failed open does not touch it
FilesBlockedStage::$index = '<?php return '. var_export( $staged, true ). ';';
$blockedBefore = FilesBlockedStage::$index;
$blockedArgs = [ 'dataDir' => $dataDir, 'staging' => 'filesblock://stage' ];
$blockedErrors = ninoErrors( static function() use ( &$appData, &$blockedArgs ): void {
	\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $blockedArgs );
} );
check( 'a restore that cannot write the index it filtered says so - php\'s own warning, not silence - and the staged index stays as it was', count( $blockedErrors ) === 1 && $blockedErrors[0][0] === E_WARNING && str_contains( $blockedErrors[0][1], 'file_put_contents' ) === true
	&& FilesBlockedStage::$index === $blockedBefore );

$untouched = $sandbox. '/restore-empty';
mkdir( $untouched. '/data', 0755, true );
$noArgs = [ 'dataDir' => $dataDir, 'staging' => $untouched ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $noArgs );
$noStaging = [ 'dataDir' => $dataDir, 'staging' => '' ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $noStaging );
check( 'a backup without an index, and a call without a staging directory, change nothing and create nothing', scandir( $untouched ) === [ '.', '..', 'data' ] && scandir( $untouched. '/data' ) === [ '.', '..' ] );

filesSpecChecks( $appData, $room, $panel );

// Deactivation takes the panel away and keeps what the project has
$kept = [ 'index' => filesIndex( $appData ), 'bytes' => count( glob( $dataDir. '/files/*.*' ) ?: [] ), 'counter' => is_dir( $dataDir. '/.counters/files-tries' ) ];
filesWorkbench( 'admin' );
check( 'the feature the Members feature\'s sibling needs cannot be switched off from under it: Members is refused while Files is active', is_string( \Nino\Features::deactivate( $appData, 'members' ) ) === true );
check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'files' ) === true );
check( '...the panel is not in the registry any more, and its actions are unknown', isset( \Nino\Admin\Admin::panels( $appData )['files'] ) === false && ninoCall( $appData, $panel, [], 'files/list' )[0] === 404 );
check( '...the index, the bytes and the counter stay as they were', $kept['index'] === filesIndex( $appData ) && $kept['index']['files'] !== [] && $kept['bytes'] === count( glob( $dataDir. '/files/*.*' ) ?: [] ) && $kept['bytes'] > 0 && $kept['counter'] === is_dir( $dataDir. '/.counters/files-tries' ) );
check( '...and the class is out of /nino/modules', in_array( '\\Nino\\Modules\\Files', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );

echo "\n";


// --- The scripts, where node is on the path -----------------------------------------
//
// files-admin-js-smoke.js beside this file draws the panel over the kernel's own Nino.admin.js (the real router, the directory and the
// record) and files-js-smoke.js runs files.js of the site over a stand-in of Nino - both against the checkout this suite runs against, which
// is handed on; this suite runs them too where node is on the path, so that bin/check.sh and CI cover all halves in one go
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

foreach( [ 'files-admin-js-smoke.js', 'files-js-smoke.js' ] as $script ) {

	if( $node === '' || function_exists( 'exec' ) === false ) {
		echo "  --  - node is not available here: ". $script. " was NOT run\n";
		continue;
	}

	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/'. $script ). ' 2>&1', $output, $exit );
	check( $script. ' passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

echo "\n";
/**
 *	The files of one upload are one mail: they are put in one after the other with notify false, and files/notify tells the group of all of them - one mail to each active member, every
 *	file in its list, the names escaped, the sign-in said as the code. It refuses what is not a list of files that are kept, visible and in one folder, before a mail is made
 *
 *	@param		array 		&$appData
 *	@param		array 		$room						The folder of the group the uploads go into
 *	@param		array 		$panel					The callable of the panel's actions
 *
 *	@return 	void
 */
function filesNotifyChecks( array &$appData, array $room, array $panel ): void {

	global $mails;

	\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );
	$mails = [];
	$names = [ 'Erste <u>&.mp3', 'Zweite [[x]].mp3', 'Dritte.mp3' ];
	$ids = [];

	foreach( $names as $name )
		$ids[] = (string) ( filesOwnerUpload( $appData, $name, $room['id'], [ 'notify' => false ] )[1]['id'] ?? '' );

	check( 'the files of an upload sent with notify false mail nobody, whatever the setting says', count( array_filter( $ids ) ) === 3 && $mails === [] );

	filesFresh( $appData );
	[ $status, $body ] = ninoCall( $appData, $panel, [ 'ids' => $ids ], 'files/notify' );
	$to = array_column( $mails, 'to' );
	sort( $to );
	$one = array_values( array_filter( $mails, static fn( array $mail ): bool => $mail['to'] === 'ada@example.org' ) )[0] ?? [];
	$text = strip_tags( (string) ( $one['body'] ?? '' ) );

	check( 'files/notify mails the group once for all the files: one mail to each active member - the number it says - and every file named in each, the names escaped as text and no key left in it',
		$status === 200 && $body['mailed'] === 7 && count( $mails ) === 7 && $to === array_values( array_unique( $to ) ) && array_filter( $names, static fn( string $name ): bool => str_contains( (string) ( $one['body'] ?? '' ), \Nino\Components::escape( $name ) ) === false ) === []
		&& filesFind( (string) $one['body'], '//u' ) === [] && str_contains( (string) $one['body'], '[[/feature' ) === false && str_contains( $text, 'Room' ) === true );

	$words = \Nino\Html::getFills( $appData, 'de_DE' );

	check( '...it says to sign in with the code, in the words of the site, which the unit brings in both languages', str_contains( $text, (string) $words['[[/feature/files/mail/new-notice-code]]'] ) === true && trim( (string) $words['[[/feature/files/mail/new-notice-code]]'] ) !== ''
		&& isset( ( include dirname( __DIR__ ). '/install/text/de_DE.php' )['[[/feature/files/mail/new-notice]]'] ) === false );

	$mails = [];
	filesFresh( $appData );
	[ $twiceStatus ] = ninoCall( $appData, $panel, [ 'ids' => [ $ids[0], $ids[1], $ids[0], $ids[0] ], 'notify' => true ], 'files/notify' );
	$twiceBody = (string) ( $mails[0]['body'] ?? '' );

	check( '...a file named twice in the list is told once: its name stands once in the mail, the other once too, and the group gets one mail each', $twiceStatus === 200 && count( $mails ) === 7 && substr_count( $twiceBody, \Nino\Components::escape( $names[0] ) ) === 1 && substr_count( $twiceBody, \Nino\Components::escape( $names[1] ) ) === 1 );

	$mails = [];
	filesFresh( $appData );
	[ $status, $body ] = ninoCall( $appData, $panel, [ 'ids' => $ids, 'notify' => false ], 'files/notify' );
	check( '...with notify false nobody is mailed - 200, mailed 0 - and with it absent the setting decides: off, nobody', $status === 200 && $body['mailed'] === 0 && $mails === []
		&& ( \Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => false ] ) || true ) && ninoCall( $appData, $panel, [ 'ids' => $ids ], 'files/notify' )[1]['mailed'] === 0 && $mails === [] );

	\Nino\Features::saveSettings( $appData, 'files', [ 'notifyMembers' => true ] );
	$other = \Nino\Modules\Files\Store::addFolder( $appData, $room['group'], 'Second' );
	$elsewhere = (string) ( filesOwnerUpload( $appData, 'Elsewhere.mp3', $other['id'], [ 'notify' => false ] )[1]['id'] ?? '' );
	$hidden = (string) ( filesOwnerUpload( $appData, 'Hidden.mp3', $room['id'], [ 'notify' => false, 'visible' => false ] )[1]['id'] ?? '' );
	$refused = [
		'no list'                   => [ 'ids' => $ids[0] ],
		'an empty list'             => [ 'ids' => [] ],
		'no ids at all'             => [],
		'a thing that is no id'     => [ 'ids' => [ $ids[0], [ 'x' ], 7 ] ],
		'an id nobody has'          => [ 'ids' => [ $ids[0], 'cccccccccccc' ] ],
		'a path'                    => [ 'ids' => [ '../'. $ids[0] ] ],
		'files of two folders'      => [ 'ids' => [ $ids[0], $elsewhere ] ],
		'a file that is hidden'     => [ 'ids' => [ $ids[0], $hidden ] ],
		'more than the panel names' => [ 'ids' => array_fill( 0, \Nino\Modules\Files::NOTIFY_MAX + 1, $ids[0] ) ],
	];
	$mails = [];
	filesFresh( $appData );
	$answers = array_map( static function( array $data ) use ( &$appData, $panel ): string {
		$answer = ninoCall( $appData, $panel, $data, 'files/notify' );

		return $answer[0]. ' '. ( $answer[1]['code'] ?? '' );
	}, $refused );

	check( '...a list that is none, an id that is none, a file nobody has, files of two folders, a file that is hidden and a list that is too long are 404 files_unknown - and nobody is mailed', array_values( array_unique( $answers ) ) === [ '404 files_unknown' ] && $mails === [] );

	// A file taken off the server has no bytes to tell of
	\Nino\Modules\Files\Store::changeFile( $appData, $ids[2], [ 'visible' => true ] );
	$removed = ninoCall( $appData, $panel, [ 'id' => $ids[2] ], 'files/remove' );
	filesFresh( $appData );
	$gone = ninoCall( $appData, $panel, [ 'ids' => [ $ids[0], $ids[2] ] ], 'files/notify' );

	check( '...so is a file that was taken off the server', $removed[0] === 200 && $gone[0] === 404 && $gone[1]['code'] === 'files_unknown' && $mails === [] );
	check( '...and the log line of an upload and of the mail holds the ids and the folder, no name and no address', str_contains( \Nino\Modules\Files\Admin::log( 'files/notify', [ 'ids' => $ids ] ), '@' ) === false && str_contains( \Nino\Modules\Files\Admin::log( 'files/notify', [ 'ids' => $ids ] ), 'Erste' ) === false );

	foreach( array_merge( $ids, [ $elsewhere, $hidden ] ) as $id )
		\Nino\Modules\Files\Store::deleteFile( $appData, $id );

	\Nino\Modules\Files\Store::deleteFolder( $appData, $other['id'] );
	$mails = [];
}

/**
 *	A file moves into another folder - of its group, or of another group, which changeFile() refuses: the entry keeps its id, its bytes, whether it is visible and who put it there
 *	(so whatever hangs on the id stays), the quota of the group it goes to is asked only for a move into another group, and a refusal changes nothing
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function filesMoveChecks( array &$appData ): void {

	$store = \Nino\Modules\Files\Store::class;
	$one = $store::addFolder( $appData, 'moveone', 'One' );
	$oneTwo = $store::addFolder( $appData, 'moveone', 'One too' );
	$two = $store::addFolder( $appData, 'movetwo', 'Two' );
	$member = [ 'kind' => 'member', 'id' => 'cccccccccccc' ];
	$file = filesMake( $appData, $one['id'], 'Song.03.mp3', 'MOVE-BYTES', $member, false );
	$bytes = (string) $store::path( $appData, $file['id'] );

	$within = $store::moveFile( $appData, $file['id'], $oneTwo['id'] );
	$kept = is_array( $within ) === true ? $within['file'] : [];
	check( 'moveFile puts a file into another folder of its group - the entry keeps its id, its name, its bytes, who put it there and that it is hidden; before says where it was',
		is_array( $within ) === true && $kept['folder'] === $oneTwo['id'] && $within['before']['folder'] === $one['id'] && $kept['id'] === $file['id'] && $kept['name'] === 'Song.03.mp3' && $kept['visible'] === false && $kept['by'] === $member
		&& $store::path( $appData, $file['id'] ) === $bytes && file_get_contents( $bytes ) === 'MOVE-BYTES' && $store::files( $appData, $one['id'] ) === [] );

	$refusedByChange = $store::changeFile( $appData, $file['id'], [ 'folder' => $two['id'] ] );
	$across = $store::moveFile( $appData, $file['id'], $two['id'] );
	check( 'moveFile goes into a folder of another group, which changeFile refuses - and the entry is the same one: the id, the bytes and that it is hidden stay, and the owner is its sender now, not the member who put it there',
		$refusedByChange === 'folder' && is_array( $across ) === true && $across['file']['folder'] === $two['id'] && $across['file']['id'] === $file['id'] && $across['file']['visible'] === false && $across['file']['by'] === [ 'kind' => 'owner', 'id' => '' ] && $across['before']['by'] === $member
		&& $store::path( $appData, $file['id'] ) === $bytes && $store::used( $appData, 'movetwo' ) === 10 && $store::used( $appData, 'moveone' ) === 0 );

	// A member who sent a file reaches it wherever it is - unless it leaves the group: then it is the owner's, and the member gets the 404 of a stranger
	global $mails;
	$sender = \Nino\Modules\Members::add( $appData, 'mover@example.org', 'Mover', 'moveone' );
	$byMover = [ 'kind' => 'member', 'id' => $sender['id'] ];
	$sent = filesMake( $appData, $one['id'], 'Sent.mp3', 'SENT-BYTES', $byMover, false );
	$signed = filesSignIn( $appData, $sender );
	$reachBefore = filesAnswer( filesServe( $appData, '/.files/'. $sent['id'] ) )[0];
	$stays = $store::moveFile( $appData, $sent['id'], $oneTwo['id'] );
	filesFresh( $appData );
	$reachInGroup = filesAnswer( filesServe( $appData, '/.files/'. $sent['id'] ) )[0];
	$leaves = $store::moveFile( $appData, $sent['id'], $two['id'] );
	filesFresh( $appData );
	$reachAfter = filesAnswer( filesServe( $appData, '/.files/'. $sent['id'] ) )[0];
	$reachDownload = filesAnswer( filesServe( $appData, '/.files/'. $sent['id']. '?download=1' ) )[0];
	check( 'moveFile within the group leaves the member as the sender, who reaches the file; into another group the owner is the sender and the member is answered 404 as a stranger is - the file and the download alike',
		$signed === true && $reachBefore === 200 && is_array( $stays ) === true && $stays['file']['by'] === $byMover && $reachInGroup === 200 && is_array( $leaves ) === true && $leaves['file']['by'] === [ 'kind' => 'owner', 'id' => '' ] && $leaves['before']['by'] === $byMover
		&& $reachAfter === 404 && $reachDownload === 404 && $store::file( $appData, $sent['id'] )['by'] === [ 'kind' => 'owner', 'id' => '' ] );
	check( '...a file of the owner stays the owner\'s wherever it goes', is_array( $store::moveFile( $appData, $sent['id'], $oneTwo['id'] ) ) === true && $store::file( $appData, $sent['id'] )['by'] === [ 'kind' => 'owner', 'id' => '' ] );
	filesVisitor();

	$shown = filesMake( $appData, $one['id'], 'Shown.mp3', 'SHOWN', [ 'kind' => 'owner', 'id' => '' ], true );
	$shownAcross = $store::moveFile( $appData, $shown['id'], $two['id'] );
	check( '...a file that is visible stays visible in the other group - the move does not decide who sees it',
		is_array( $shownAcross ) === true && $shownAcross['file']['visible'] === true && $store::file( $appData, $shown['id'] )['folder'] === $two['id'] );

	$before = filesIndex( $appData );
	$refusals = [
		'a file nobody has'     => $store::moveFile( $appData, 'ffffffffffff', $two['id'] ),
		'an id that is a path'  => $store::moveFile( $appData, '../'. $file['id'], $two['id'] ),
		'a folder nobody has'   => $store::moveFile( $appData, $file['id'], 'ffffffffffff' ),
		'a folder that is a path' => $store::moveFile( $appData, $file['id'], '../'. $two['id'] ),
		'no folder'             => $store::moveFile( $appData, $file['id'], '' ),
	];
	check( 'moveFile refuses a file nobody has and a folder nobody has - an id that is a path included - with unknown and folder, and the index is as it was',
		array_values( $refusals ) === [ 'unknown', 'unknown', 'folder', 'folder', 'folder' ] && filesIndex( $appData ) === $before );

	// The quota: asked of the group the file goes to, when that group is another one
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 1 ] );
	$big = filesMake( $appData, $one['id'], 'Big.mp3', str_repeat( 'B', 700000 ) );
	$fill = filesMake( $appData, $two['id'], 'Fill.mp3', str_repeat( 'F', 500000 ) );
	$crossing = $store::moveFile( $appData, $big['id'], $two['id'] );
	$stayed = $store::file( $appData, $big['id'] )['folder'] === $one['id'];
	$sameGroup = $store::moveFile( $appData, $big['id'], $oneTwo['id'] );
	check( 'moveFile into another group refuses a file that would take that group over its quota - quota, nothing moves - and into a folder of its own group the quota is not asked',
		$crossing === 'quota' && $stayed === true && is_array( $sameGroup ) === true && $sameGroup['file']['folder'] === $oneTwo['id'] );

	$exact = filesMake( $appData, $one['id'], 'Exact.mp3', str_repeat( 'E', 1048576 - 500000 - 10 - 5 ) );
	$fits = $store::moveFile( $appData, $exact['id'], $two['id'] );
	$oneMore = filesMake( $appData, $one['id'], 'More.mp3', 'MM' );
	$over = $store::moveFile( $appData, $oneMore['id'], $two['id'] );
	check( '...a file that fills the room of the group exactly goes in, one byte more does not - the files that were removed from the server take no room, so a removed file goes where nothing else would',
		is_array( $fits ) === true && $store::used( $appData, 'movetwo' ) === 1048576 && $over === 'quota' && $store::removeBytes( $appData, $big['id'] ) !== 'write' && is_array( $store::moveFile( $appData, $big['id'], $two['id'] ) ) === true );

	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 0 ] );

	foreach( [ $one, $oneTwo, $two ] as $folder )
		$store::deleteFolder( $appData, $folder['id'] );
}

/**
 *	A file is copied into a folder under a name: new id, copied bytes, the sha256 and the size of the original, visible as it is, put there by the owner whoever put the original there;
 *	a refusal leaves neither an entry nor bytes behind, and the quota of the group of the folder is decided inside the write
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function filesCopyChecks( array &$appData ): void {

	$store = \Nino\Modules\Files\Store::class;
	$files = count( glob( \Nino\Filesystem::path( $appData, $store::BYTES ). '/*.mp3' ) ?: [] );
	$one = $store::addFolder( $appData, 'copyone', 'One' );
	$two = $store::addFolder( $appData, 'copytwo', 'Two' );
	$member = [ 'kind' => 'member', 'id' => 'cccccccccccc' ];
	$file = filesMake( $appData, $one['id'], 'Song.03.mp3', 'COPY-BYTES', $member, true );

	$copy = $store::copyFile( $appData, $file['id'], $one['id'], 'Song.04.mp3' );
	check( 'copyFile makes a new file: another id, the same bytes in a file of their own, the size, the type and the sha256 of the original, the name that was given, in the folder that was given',
		is_array( $copy ) === true && $copy['id'] !== $file['id'] && preg_match( $store::ID, $copy['id'] ) === 1 && $copy['name'] === 'Song.04.mp3' && $copy['folder'] === $one['id'] && $copy['size'] === 10 && $copy['sha256'] === $file['sha256'] && $copy['mime'] === 'audio/mpeg'
		&& file_get_contents( (string) $store::path( $appData, $copy['id'] ) ) === 'COPY-BYTES' && $store::path( $appData, $copy['id'] ) !== $store::path( $appData, $file['id'] ) && $store::file( $appData, $copy['id'] ) === $copy );
	check( '...it is visible as the original is and it is the owner\'s: the member who put the original there does not get a file in another folder by it - the original is as it was',
		$copy['visible'] === true && $copy['by'] === [ 'kind' => 'owner', 'id' => '' ] && $store::file( $appData, $file['id'] ) === $file && $store::file( $appData, $file['id'] )['by'] === $member );

	$hiddenFile = filesMake( $appData, $one['id'], 'Hidden.mp3', 'HH', $member, false );
	$hiddenCopy = $store::copyFile( $appData, $hiddenFile['id'], $two['id'], 'Hidden.mp3' );
	check( '...a hidden file is copied hidden, into a folder of another group too - the name may stay what it was', is_array( $hiddenCopy ) === true && $hiddenCopy['visible'] === false && $hiddenCopy['folder'] === $two['id'] && $hiddenCopy['name'] === 'Hidden.mp3' && $store::used( $appData, 'copytwo' ) === 2 );

	$deleted = $store::deleteFile( $appData, $copy['id'] );
	check( 'the copy and the original live apart: deleting the copy takes its bytes and not the original\'s', is_array( $deleted ) === true && $store::path( $appData, $copy['id'] ) === null && (string) @file_get_contents( (string) ( $store::path( $appData, $file['id'] ) ?? '/nonexistent' ) ) === 'COPY-BYTES' );

	// The name is cleaned as an upload's is
	$cleaned = [];
	foreach( [ 'a path' => '../../etc/Song.05.mp3', 'a backslash path' => 'C:\\Users\\x\\Song.05.mp3', 'a null byte' => "Song\0.05.mp3", 'markup' => '<b>Song<b>.05.mp3' ] as $why => $name ) {
		$made = $store::copyFile( $appData, $file['id'], $one['id'], $name );
		$cleaned[$why] = is_array( $made ) === true ? $made['name'] : $made;
		$cleaned[$why. ' on disk'] = is_array( $made ) === true && str_contains( (string) $store::path( $appData, $made['id'] ), '..' ) === false && basename( (string) $store::path( $appData, $made['id'] ) ) === $made['id']. '.mp3';
	}
	check( 'copyFile keeps the name as an upload does: no path in it (a slash and a backslash cut it), no null byte, the bytes lie in the directory of the bytes under their id - and markup stays text',
		$cleaned['a path'] === 'Song.05.mp3' && $cleaned['a backslash path'] === 'Song.05.mp3' && $cleaned['a null byte'] === 'Song.05.mp3' && $cleaned['markup'] === '<b>Song<b>.05.mp3' && array_filter( $cleaned, static fn( mixed $one ): bool => $one === false ) === [] );

	$long = $store::copyFile( $appData, $file['id'], $one['id'], str_repeat( 'x', 400 ). '.mp3' );
	check( '...a name of 400 characters is cut to the length of a name with its extension whole', is_array( $long ) === true && mb_strlen( $long['name'], 'UTF-8' ) === $store::NAME_LENGTH && str_ends_with( $long['name'], '.mp3' ) === true );

	$before = filesIndex( $appData );
	$diskBefore = scandir( \Nino\Filesystem::path( $appData, $store::BYTES ) );
	$refusals = [
		'a file nobody has'     => $store::copyFile( $appData, 'ffffffffffff', $one['id'], 'X.mp3' ),
		'an id that is a path'  => $store::copyFile( $appData, '../'. $file['id'], $one['id'], 'X.mp3' ),
		'a folder nobody has'   => $store::copyFile( $appData, $file['id'], 'ffffffffffff', 'X.mp3' ),
		'a folder that is a path' => $store::copyFile( $appData, $file['id'], '../'. $one['id'], 'X.mp3' ),
		'an empty name'         => $store::copyFile( $appData, $file['id'], $one['id'], '' ),
		'a name with no extension' => $store::copyFile( $appData, $file['id'], $one['id'], 'Song' ),
		'a name of dots'        => $store::copyFile( $appData, $file['id'], $one['id'], '...mp3' ),
		'another extension'     => $store::copyFile( $appData, $file['id'], $one['id'], 'Song.wav' ),
		'a php extension'       => $store::copyFile( $appData, $file['id'], $one['id'], 'Song.php' ),
	];
	check( 'copyFile refuses a file or a folder nobody has (an id that is a path included), a name that is none and a name with another extension than the original\'s - with unknown, folder, name and ext - and leaves neither an entry nor a byte behind',
		array_values( $refusals ) === [ 'unknown', 'unknown', 'folder', 'folder', 'name', 'name', 'name', 'ext', 'ext' ] && filesIndex( $appData ) === $before && scandir( \Nino\Filesystem::path( $appData, $store::BYTES ) ) === $diskBefore );

	// The bytes of a file that was removed from the server are not there to copy
	$gone = filesMake( $appData, $one['id'], 'Gone.mp3', 'GG' );
	$store::removeBytes( $appData, $gone['id'] );
	$goneCopy = $store::copyFile( $appData, $gone['id'], $one['id'], 'Gone.01.mp3' );
	$lost = filesMake( $appData, $one['id'], 'Lost.mp3', 'LL' );
	unlink( (string) $store::path( $appData, $lost['id'] ) );
	check( 'copyFile has nothing to copy of a file whose bytes were removed from the server or are not on the disk - gone - and makes no entry', $goneCopy === 'gone' && $store::copyFile( $appData, $lost['id'], $one['id'], 'Lost.01.mp3' ) === 'gone'
		&& array_filter( $store::files( $appData, $one['id'] ), static fn( array $entry ): bool => in_array( $entry['name'], [ 'Gone.01.mp3', 'Lost.01.mp3' ], true ) ) === [] );

	// The quota of the group of the folder the copy goes to, decided in the write
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 1 ] );
	$big = filesMake( $appData, $one['id'], 'Big.mp3', str_repeat( 'B', 700000 ) );
	$used = $store::used( $appData, 'copyone' );
	$tooMuch = $store::copyFile( $appData, $big['id'], $one['id'], 'Big.01.mp3' );
	$room = $store::copyFile( $appData, $big['id'], $two['id'], 'Big.01.mp3' );
	$disk = count( glob( \Nino\Filesystem::path( $appData, $store::BYTES ). '/*.mp3' ) ?: [] );
	check( 'copyFile refuses a copy that would take the group of the folder over its quota - quota, no entry, and the bytes it had copied are gone again - and takes it where that group has room',
		$tooMuch === 'quota' && $store::used( $appData, 'copyone' ) === $used && is_array( $room ) === true && $room['folder'] === $two['id'] && filesParts( $appData ) === []
		&& $disk === count( array_filter( $store::read( $appData )['files'], static fn( array $entry ): bool => $entry['ext'] === 'mp3' && isset( $entry['gone'] ) === false ) ) );
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 0 ] );

	$copied = count( glob( \Nino\Filesystem::path( $appData, $store::BYTES ). '/*.mp3' ) ?: [] );
	$deletedOne = $store::deleteFolder( $appData, $one['id'] );
	$deletedTwo = $store::deleteFolder( $appData, $two['id'] );

	check( 'a file that was copied is a file like any other: deleting its folder takes it with its bytes, and the directory of the bytes is as it was before the copies',
		$copied > $files && is_array( $deletedOne ) === true && is_array( $deletedTwo ) === true && count( glob( \Nino\Filesystem::path( $appData, $store::BYTES ). '/*.mp3' ) ?: [] ) === $files );
}

/**
 *	A file is renamed: the name is cleaned as an upload's, the extension stays the file's, a name that says a version keeps saying one - the folder, whether it is visible and the bytes stay
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function filesRenameChecks( array &$appData ): void {

	$store = \Nino\Modules\Files\Store::class;
	$folder = $store::addFolder( $appData, 'renamed', 'Renamed' );
	$versioned = filesMake( $appData, $folder['id'], 'Song.03.mp3', 'RR', [ 'kind' => 'owner', 'id' => '' ], false );
	$plain = filesMake( $appData, $folder['id'], 'Plain.mp3', 'PP' );

	$renamed = $store::renameFile( $appData, $versioned['id'], 'Other.07.MP3' );
	check( 'renameFile changes the name and nothing else - the extension in lower case, the folder, that the file is hidden, the id and the bytes stay; before has the old name',
		is_array( $renamed ) === true && $renamed['file']['name'] === 'Other.07.mp3' && $renamed['before']['name'] === 'Song.03.mp3' && $renamed['file']['folder'] === $folder['id'] && $renamed['file']['visible'] === false && $renamed['file']['id'] === $versioned['id']
		&& file_get_contents( (string) $store::path( $appData, $versioned['id'] ) ) === 'RR' && $store::file( $appData, $versioned['id'] )['name'] === 'Other.07.mp3' );

	$cleaned = $store::renameFile( $appData, $plain['id'], "../../x/<b>New<b>\0.mp3" );
	check( '...the name is cleaned as an upload\'s is: no path, no null byte, markup stays text', is_array( $cleaned ) === true && $cleaned['file']['name'] === '<b>New<b>.mp3' );

	$before = filesIndex( $appData );
	$refusals = [
		'a file nobody has'      => $store::renameFile( $appData, 'ffffffffffff', 'X.mp3' ),
		'an id that is a path'   => $store::renameFile( $appData, '../'. $plain['id'], 'X.mp3' ),
		'an empty name'          => $store::renameFile( $appData, $plain['id'], '' ),
		'no extension'           => $store::renameFile( $appData, $plain['id'], 'Plain' ),
		'another extension'      => $store::renameFile( $appData, $plain['id'], 'Plain.wav' ),
		'a version that is gone' => $store::renameFile( $appData, $versioned['id'], 'Other.mp3' ),
		'four digits'            => $store::renameFile( $appData, $versioned['id'], 'Other.0007.mp3' ),
	];
	check( 'renameFile refuses a file nobody has, a name that is none, another extension and - for a file that is a version - a name that says none: unknown, name, ext and version, and the index is as it was',
		array_values( $refusals ) === [ 'unknown', 'unknown', 'name', 'name', 'ext', 'version', 'version' ] && filesIndex( $appData ) === $before );

	$becomes = $store::renameFile( $appData, $plain['id'], 'Plain.02.mp3' );
	check( '...a file that is no version may be given one', is_array( $becomes ) === true && \Nino\Modules\Files::version( $becomes['file']['name'] )['number'] === 2 );

	$store::deleteFolder( $appData, $folder['id'] );
}

/**
 *	The bytes of a file are replaced: written to a part and renamed over the old bytes inside the write of the index, the size, the sha256 and the day follow, the rest of the entry stays,
 *	a sha256 that is not the file's any more is refused, the quota is asked for growth only, and a refusal leaves the bytes, the index and the directory of the parts as they were
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function filesWriteChecks( array &$appData ): void {

	$store = \Nino\Modules\Files\Store::class;
	$folder = $store::addFolder( $appData, 'writeone', 'One' );
	$other = $store::addFolder( $appData, 'writetwo', 'Two' );
	$member = [ 'kind' => 'member', 'id' => 'cccccccccccc' ];
	$file = filesMake( $appData, $folder['id'], 'Notes.txt', "OLD\nBYTES", $member, false, 'text/plain', 86400 );
	$path = (string) $store::path( $appData, $file['id'] );
	clearstatcache( true, $path );
	$inode = fileinode( $path );

	$written = $store::writeBytes( $appData, $file['id'], "NEW\nBYTES, LONGER\n" );
	$now = $store::file( $appData, $file['id'] );
	clearstatcache( true, $path );
	check( 'writeBytes puts the new bytes in place of the old ones: the size, the sha256 and the day follow the bytes, and the answer is the entry as it is now with the one it was before',
		is_array( $written ) === true && file_get_contents( $path ) === "NEW\nBYTES, LONGER\n" && $now['size'] === 18 && $now['sha256'] === hash( 'sha256', "NEW\nBYTES, LONGER\n" ) && $now['created'] > $file['created'] && substr( $now['created'], 0, 10 ) === gmdate( 'Y-m-d' )
		&& $written['file'] === $now && $written['before'] === $file );
	check( '...the name, the folder, who put the file there, that it is hidden, the extension, the id and the type stay',
		array_diff_key( $now, array_flip( [ 'size', 'sha256', 'created' ] ) ) === array_diff_key( $file, array_flip( [ 'size', 'sha256', 'created' ] ) ) && $now['by'] === $member && $now['visible'] === false );
	check( 'the bytes are written to a part and renamed over the old ones, not written into them: the file at the path is another file afterwards and no part is left',
		fileinode( $path ) !== $inode && filesParts( $appData ) === [] && $store::path( $appData, $file['id'] ) === $path );

	$empty = $store::writeBytes( $appData, $file['id'], '' );
	check( '...an empty text is a file of no bytes with the sha256 of nothing', is_array( $empty ) === true && filesSize( $path ) === 0 && $store::file( $appData, $file['id'] )['size'] === 0 && $store::file( $appData, $file['id'] )['sha256'] === hash( 'sha256', '' ) );

	// The type: what finfo says of the new bytes when the extension carries it, the old type when it does not
	$store::writeBytes( $appData, $file['id'], "a,b,c\n1,2,3\n4,5,6\n" );
	$csv = $store::file( $appData, $file['id'] )['mime'];
	$store::writeBytes( $appData, $file['id'], '<?php echo 1;' );
	$php = $store::file( $appData, $file['id'] )['mime'];
	$store::writeBytes( $appData, $file['id'], "\x00\x01\x02\xff" );
	$binary = $store::file( $appData, $file['id'] )['mime'];
	check( 'the type follows the bytes where the extension may carry it (text/csv for lines of commas in a txt) and stays what it was where finfo names one the extension does not (php, binary): the download is never typed as what the extension is not',
		$csv === 'text/csv' && $php === 'text/csv' && $binary === 'text/csv' );

	// The sha256 that the caller read
	$base = $store::writeBytes( $appData, $file['id'], 'BASE' );
	$sha = $store::file( $appData, $file['id'] )['sha256'];
	$before = filesIndex( $appData );
	$stale = $store::writeBytes( $appData, $file['id'], 'STALE', hash( 'sha256', 'something else' ) );
	check( 'writeBytes with the sha256 the caller read writes only if the file still has it: another one is changed, nothing is written - the bytes, the index and the parts are as they were',
		is_array( $base ) === true && $stale === 'changed' && file_get_contents( $path ) === 'BASE' && filesIndex( $appData ) === $before && filesParts( $appData ) === [] );
	$fresh = $store::writeBytes( $appData, $file['id'], 'FRESH', $sha );
	$again = $store::writeBytes( $appData, $file['id'], 'AGAIN', $sha );
	check( '...the right one writes, and it is spent by it: the same sha256 again is a file that was changed since', is_array( $fresh ) === true && $again === 'changed' && file_get_contents( $path ) === 'FRESH' );

	// The quota: only growth is asked
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 1 ] );
	$fill = filesMake( $appData, $folder['id'], 'Fill.txt', str_repeat( 'F', 500000 ), $member, true, 'text/plain' );
	$grown = filesMake( $appData, $folder['id'], 'Grow.txt', str_repeat( 'G', 400000 ), $member, true, 'text/plain' );
	$used = $store::used( $appData, 'writeone' );
	$room = 1048576 - $used;
	$full = $store::writeBytes( $appData, $grown['id'], str_repeat( 'H', 400000 + $room ) );
	$over = $store::writeBytes( $appData, $grown['id'], str_repeat( 'I', 400000 + $room + 1 ) );
	$kept = $store::file( $appData, $grown['id'] );
	check( 'writeBytes lets a file grow into the room its group has left, to the byte, and not one byte further: quota, and the bytes and the index are as they were',
		is_array( $full ) === true && $store::used( $appData, 'writeone' ) === 1048576 && $over === 'quota' && $kept['size'] === 400000 + $room && $kept['sha256'] === hash( 'sha256', str_repeat( 'H', 400000 + $room ) ) && filesParts( $appData ) === [] );
	$smaller = $store::writeBytes( $appData, $grown['id'], 'SMALL' );
	$overFull = filesMake( $appData, $other['id'], 'Other.txt', str_repeat( 'O', 1300000 ), $member, true, 'text/plain' );
	$shrunk = $store::writeBytes( $appData, $overFull['id'], str_repeat( 'S', 1299000 ) );
	$same = $store::writeBytes( $appData, $overFull['id'], str_repeat( 'T', 1299000 ) );
	$growsOne = $store::writeBytes( $appData, $overFull['id'], str_repeat( 'U', 1299001 ) );
	check( '...a file gets smaller or stays as large however far over its quota the group is - the quota is asked of growth only - and a group with no quota is asked nothing',
		is_array( $smaller ) === true && is_array( $shrunk ) === true && is_array( $same ) === true && $growsOne === 'quota' );
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 0 ] );
	$unlimited = $store::writeBytes( $appData, $overFull['id'], str_repeat( 'V', 2000000 ) );
	check( '...and with no quota at all it grows as far as it likes', is_array( $unlimited ) === true && $store::file( $appData, $overFull['id'] )['size'] === 2000000 );

	// What cannot be written
	$gone = filesMake( $appData, $folder['id'], 'Gone.txt', 'GG', $member, true, 'text/plain' );
	$lost = filesMake( $appData, $folder['id'], 'Lost.txt', 'LL', $member, true, 'text/plain' );
	$store::removeBytes( $appData, $gone['id'] );
	unlink( (string) $store::path( $appData, $lost['id'] ) );
	$before = filesIndex( $appData );
	$disk = scandir( \Nino\Filesystem::path( $appData, $store::BYTES ) );
	$refusals = [
		'a file nobody has'      => $store::writeBytes( $appData, 'ffffffffffff', 'X' ),
		'an id that is a path'   => $store::writeBytes( $appData, '../'. $file['id'], 'X' ),
		'an empty id'            => $store::writeBytes( $appData, '', 'X' ),
		'bytes that were removed' => $store::writeBytes( $appData, $gone['id'], 'X' ),
		'bytes that are not there' => $store::writeBytes( $appData, $lost['id'], 'X' ),
	];
	check( 'writeBytes refuses a file nobody has (an id that is a path included) with unknown, and a file whose bytes were removed from the server or are not on the disk with gone - the index, the directory of the bytes and the parts are as they were',
		array_values( $refusals ) === [ 'unknown', 'unknown', 'unknown', 'gone', 'gone' ] && filesIndex( $appData ) === $before && scandir( \Nino\Filesystem::path( $appData, $store::BYTES ) ) === $disk && filesParts( $appData ) === [] );

	foreach( [ $folder, $other ] as $one )
		$store::deleteFolder( $appData, $one['id'] );
}
filesChosenChecks( $appData, $form, $root );
filesFinalChecks( $appData );
filesWipeChecks( $appData );

ninoDone( $appData );

/**
 *	The markup of the files chosen before the submit: a list and a sentence under it, both hidden until the script has something to show, in the form and before its button - and the words of
 *	the sentence and of the button that takes a file out of the selection, in both languages
 *
 *	@param		array 		&$appData
 *	@param		string		$form					The markup of [files-upload]
 *	@param		string		$root					The root of the checkout
 *
 *	@return 	void
 */
function filesChosenChecks( array &$appData, string $form, string $root ): void {

	echo "\nThe files chosen before they are sent\n";

	$list = filesFind( $form, '//form/ul[contains(@class, "files-upload-chosen")][@hidden]' );
	$note = filesFind( $form, '//form/p[contains(@class, "files-upload-chosen-hint")][@hidden]' );
	$order = [ 'files-upload-chosen', 'files-upload-chosen-hint', 'files-upload-list' ];
	$at = array_map( static fn( string $class ): int|false => strpos( $form, $class ), $order );

	check( 'the form has the list of the chosen files and the sentence under it, both hidden, in the form: the list, then the sentence, before the list of the progress - the sentence has no text of its own, the script writes it, and it is no hint of the limits', count( $list ) === 1 && count( $note ) === 1 && $at[0] !== false && $at[0] < $at[1] && $at[1] < $at[2]
		&& filesFind( $form, '//form/p[contains(@class, "files-upload-chosen-hint")][contains(@class, "files-upload-hint")]' ) === [] && trim( $note[0]->textContent ) === '' && str_contains( $form, '/feature/files/upload/chosen' ) === false );

	$words = [];

	foreach( [ 'de_DE', 'en_US' ] as $locale )
		$words[$locale] = (array) require dirname( __DIR__ ). '/install/text/'. $locale. '.php';

	$keys = [ '[[/feature/files/script/chosen]]', '[[/feature/files/script/remove]]' ];

	check( '...the sentence and the word of the button are texts of the unit in both languages, none empty, the German with Du, and the script may ask for the word of the button: it is published with the script words', array_diff( $keys, array_keys( $words['de_DE'] ), array_keys( $words['en_US'] ) ) === []
		&& array_filter( array_merge( array_map( static fn( string $key ): string => $words['de_DE'][$key], $keys ), array_map( static fn( string $key ): string => $words['en_US'][$key], $keys ) ), static fn( string $text ): bool => trim( $text ) === '' ) === []
		&& preg_match( '/\b(Sie|Ihre|Ihnen|Ihr)\b/', implode( ' ', array_map( static fn( string $key ): string => $words['de_DE'][$key], $keys ) ) ) !== 1 && str_starts_with( $keys[0], '[[/feature/files/script/' ) === true && str_starts_with( $keys[1], '[[/feature/files/script/' ) === true );
}

/**
 *	The mark Final of a file: the owner sets it with Store::changeFile(), it is read back from a fresh index, it is no part of what a member sees of the rule, and a flag that is none changes nothing
 */
function filesFinalChecks( array &$appData ): void {

	echo "\nThe mark Final\n";

	$room = \Nino\Modules\Files\Store::addFolder( $appData, 'finals', 'Finals' );
	$track = filesMake( $appData, $room['id'], 'Mix.01.wav', 'FINAL-BYTES', [ 'kind' => 'owner', 'id' => '' ], true, 'audio/wav' );
	$set = \Nino\Modules\Files\Store::changeFile( $appData, $track['id'], [ 'final' => true ] );
	$read = \Nino\Modules\Files::file( $appData, $track['id'] );

	check( 'a file is not final to begin with, and the owner marks it: the answer has it, a fresh read of the index says so, and nothing else of the file changed',
		$track['final'] === false && is_array( $set ) === true && $set['before']['final'] === false && $set['file']['final'] === true && ( $read['final'] ?? null ) === true
		&& array_diff_key( $read ?? [], [ 'final' => 1 ] ) === array_diff_key( $track, [ 'final' => 1 ] ) );

	$kept = \Nino\Modules\Files\Store::changeFile( $appData, $track['id'], [ 'final' => 'yes', 'visible' => false ] );

	check( '...what is posted as something else than a flag does not change the mark, the other flag does what it did', is_array( $kept ) === true && $kept['file']['final'] === true && $kept['file']['visible'] === false );

	$unset = \Nino\Modules\Files\Store::changeFile( $appData, $track['id'], [ 'final' => false ] );

	check( '...and the mark is taken off again', is_array( $unset ) === true && $unset['file']['final'] === false && ( \Nino\Modules\Files::file( $appData, $track['id'] )['final'] ?? null ) === false );

	$raw = \Nino\Modules\Files\Store::parse( [ 'folders' => [ $room['id'] => [ 'group' => 'finals', 'name' => 'Finals' ] ],
		'files' => [ 'aaaaaaaaaaaa' => [ 'folder' => $room['id'], 'name' => 'a.mp3', 'ext' => 'mp3', 'size' => 1, 'by' => [ 'kind' => 'owner', 'id' => '' ], 'final' => 1 ],
			'bbbbbbbbbbbb' => [ 'folder' => $room['id'], 'name' => 'b.mp3', 'ext' => 'mp3', 'size' => 1, 'by' => [ 'kind' => 'owner', 'id' => '' ] ] ] ] );

	check( '...an index written by hand reads as not final, whatever else it says: only true is the mark, and an entry without it is not final', $raw['files']['aaaaaaaaaaaa']['final'] === false && $raw['files']['bbbbbbbbbbbb']['final'] === false );

	$store = \Nino\Modules\Files\Store::class;
	$other = $store::addFolder( $appData, 'finals', 'Finals zwei' );
	$store::changeFile( $appData, $track['id'], [ 'final' => true ] );
	$moved = $store::moveFile( $appData, $track['id'], $other['id'] );
	$renamed = $store::renameFile( $appData, $track['id'], 'Mix.02.wav' );
	$copy = $store::copyFile( $appData, $track['id'], $room['id'], 'Mix.03.wav' );

	check( 'a move and a rename leave the mark as it is, and a copy of a final file is not final - the copy is a file of its own that the owner marks or does not', is_array( $moved ) === true && ( \Nino\Modules\Files::file( $appData, $track['id'] )['final'] ?? null ) === true && is_array( $renamed ) === true && is_array( $copy ) === true && $copy['final'] === false
		&& ( \Nino\Modules\Files::file( $appData, $copy['id'] )['final'] ?? null ) === false );

	$store::deleteFile( $appData, $copy['id'] );
	$store::deleteFile( $appData, $track['id'] );
	$store::deleteFolder( $appData, $other['id'] );
	\Nino\Modules\Files\Store::deleteFolder( $appData, $room['id'] );
}

/**
 *	The hook '/files/granted': asked only where the rule says no for a visible file, with the file, the member, download, listing and granted (false); a callback that sets granted to true gives READ access
 *	- the route, allowed() and the listing - and nothing else: a hidden file is not asked about, the upload stays shut, '/files/allowed' takes a granted file away again; with no callback nothing changes
 *
 *	@param		array 		&$appData
 *	@param		array 		$room					The folder of the group staff, marked as its upload folder
 *	@param		array 		$track				A visible file in it
 *	@param		array 		$secret				A file in it that is not visible
 *
 *	@return 	void
 */
function filesGrantChecks( array &$appData, array $room, array $track, array $secret ): void {

	echo "\nThe hook /files/granted - read access for a member of another group\n";

	$bob = \Nino\Modules\Members::account( $appData, \Nino\Modules\Members::accounts( $appData )[array_search( 'bob@example.org', array_column( \Nino\Modules\Members::accounts( $appData ), 'email' ), true )]['id'] );
	$status = static function( array &$appData, string $who, string $file, string $query = '' ): int {
		filesAs( $who );

		return filesServe( $appData, '/.files/'. $file. $query )['/nino/http/response']['statusCode'];
	};

	// With no callback registered nothing is granted: the rule is the answer
	$before = [ $status( $appData, 'bob', $track['id'] ), $status( $appData, 'bob', $secret['id'] ) ];
	filesAs( 'bob' );
	check( 'with no callback registered a member of another group has the 404 and allowed() says no - the rule is the answer', $before === [ 404, 404 ] && \Nino\Modules\Files::allowed( $appData, $track ) === false );

	$asked = [];
	$ids = [];
	$listener = static function( array &$appData, array &$args ) use ( &$asked, &$ids ): void {
		$asked[] = [ $args['file']['id'], $args['member']['id'], $args['download'], $args['listing'], $args['granted'] ];

		if( in_array( $args['file']['id'], $ids, true ) === true )
			$args['granted'] = true;
	};
	\Nino\Callbacks::registerCallback( $appData, '/files/granted', $listener );

	$none = $status( $appData, 'bob', $track['id'] );
	check( 'a callback that grants nothing changes nothing, and it is asked with the file, the member, download false, listing false and granted false', $none === 404 && $asked === [ [ $track['id'], $bob['id'], false, false, false ] ] );

	$asked = [];
	$status( $appData, 'ada', $track['id'] );
	$status( $appData, '', $track['id'] );
	$status( $appData, 'bob', $secret['id'] );
	check( 'it is asked only where the rule says no for a visible file: not for a member the rule allows, not for a visitor, not for a file that is hidden', $asked === [] );

	$ids = [ $track['id'] ];
	$asked = [];
	$view = $status( $appData, 'bob', $track['id'] );
	$saved = $status( $appData, 'bob', $track['id'], '?download=1' );
	filesAs( 'bob' );
	$listed = \Nino\Modules\Files::allowed( $appData, $track, false, true );
	check( 'a callback that sets granted to true gives the member the file: the view, the download and the listing, and the callback is told download and listing as they are', $view === 200 && $saved === 200 && $listed === true && \Nino\Modules\Files::allowed( $appData, $track ) === true
		&& array_slice( $asked, 0, 3 ) === [ [ $track['id'], $bob['id'], false, false, false ], [ $track['id'], $bob['id'], true, false, false ], [ $track['id'], $bob['id'], false, true, false ] ] );

	$asked = [];
	check( '...a grant is the file it names: another file of the folder is the 404, a hidden file is not granted and not asked about', $status( $appData, 'bob', $secret['id'] ) === 404 && in_array( $secret['id'], array_column( $asked, 0 ), true ) === false );

	// A grant is read access: the upload stays shut
	$index = filesIndex( $appData );
	filesFresh( $appData );
	filesAs( 'bob' );
	$posted = filesMemberSend( $appData, [ 'name' => 'Grant Take.mp3', 'size' => 100, 'upload' => '', 'index' => 0, 'count' => 1, 'folder' => $room['id'] ], filesPart( $appData, str_repeat( 'a', 100 ) ) );
	check( 'a grant opens no upload: a member of another group posting into the folder of the file is the 404 files_folder and nothing is stored', $posted[0] === 404 && ( $posted[1]['code'] ?? '' ) === 'files_folder' && filesIndex( $appData ) === $index );

	// Only a true is a yes; one yes is enough; '/files/allowed' still takes the file away
	\Nino\Callbacks::removeCallbacks( $appData, '/files/granted' );
	$maybe = static function( array &$appData, array &$args ): void {
		$args['granted'] = 'yes';
	};
	\Nino\Callbacks::registerCallback( $appData, '/files/granted', $maybe );
	$notTrue = $status( $appData, 'bob', $track['id'] );
	$ids = [ $track['id'] ];
	\Nino\Callbacks::registerCallback( $appData, '/files/granted', $listener );
	$oneYes = $status( $appData, 'bob', $track['id'] );
	check( 'only the value true grants: "yes" is no grant, and where one callback does not grant and another does the file is granted', $notTrue === 404 && $oneYes === 200 );

	$withhold = static function( array &$appData, array &$args ): void {
		$args['allowed'] = false;
	};
	\Nino\Callbacks::registerCallback( $appData, '/files/allowed', $withhold );
	$taken = $status( $appData, 'bob', $track['id'] );
	\Nino\Callbacks::removeCallbacks( $appData, '/files/allowed' );
	check( 'after a grant the callback /files/allowed is asked as for any file and takes it away again: the 404', $taken === 404 );

	\Nino\Callbacks::removeCallbacks( $appData, '/files/granted' );
	check( 'with the callbacks gone the rule is the answer again, for the member of another group and for the group', $status( $appData, 'bob', $track['id'] ) === 404 && $status( $appData, 'ada', $track['id'] ) === 200 );
	check( 'the manual of the feature names the hook with its five arguments, in both languages', isset( $GLOBALS['manifest']['manual']['callbacks']['/files/granted'] ) === true && array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => array_filter( [ 'file', 'member', 'download', 'listing', 'granted' ], static fn( string $name ): bool => str_contains( (string) $GLOBALS['manifest']['manual']['callbacks']['/files/granted'][$locale], $name ) === false ) !== [] ) === [] );
}

/**
 *	The argument listing of allowed(): the callback is told whether a page lists the file and does not hand it out, false by default and on the route, so that a project can withhold the bytes of a file and still have its row to show
 *
 *	@param		array 		&$appData
 *	@param		array 		$file					A file the signed-in member may have
 *
 *	@return 	void
 */
function filesListingChecks( array &$appData, array $file ): void {

	echo "\nThe argument listing\n";

	$told = [];
	$on = true;
	$listener = static function( array &$appData, array &$args ) use ( &$told, &$on ): void {
		if( $on === true )
			$told[] = $args['listing'] ?? null;
	};
	\Nino\Callbacks::registerCallback( $appData, '/files/allowed', $listener );

	$plain = \Nino\Modules\Files::allowed( $appData, $file );
	$saved = \Nino\Modules\Files::allowed( $appData, $file, true );
	$listed = \Nino\Modules\Files::allowed( $appData, $file, false, true );
	$route = filesServe( $appData, '/.files/'. $file['id'] );

	check( 'the callback is told listing: false for allowed() as it was, false for a download and for the route, true where a page lists the file - and the answer is the rule\'s whatever it is', $plain === true && $saved === true && $listed === true && $route['/nino/http/response']['statusCode'] === 200 && array_slice( $told, 0, 3 ) === [ false, false, true ] && end( $told ) === false );
	$on = false;
}

/**
 *	Store::wipeGroups() and Store::dropParts(): the folders of the groups a test picks go with their files, the entries in one write and the bytes after it; a group nobody picks keeps its folders, files and bytes; a pick that matches nothing
 *	writes nothing; the unfinished uploads of every folder go whatever their age
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function filesWipeChecks( array &$appData ): void {

	echo "\nThe wipe of groups\n";

	$store = \Nino\Modules\Files\Store::class;
	$make = static function( string $group, string $name ) use ( &$appData, $store ): array {
		$folder = $store::addFolder( $appData, $group, $name );
		$files = [];

		foreach( [ 'a', 'b' ] as $letter ) {
			$id = bin2hex( random_bytes( 6 ) );
			\Nino\Filesystem::forceDir( $appData, $store::BYTES );
			file_put_contents( (string) $store::bytes( $appData, $id, 'txt' ), 'WIPE'. $letter );
			$store::addFile( $appData, $id, [ 'folder' => $folder['id'], 'name' => $letter. '.txt', 'ext' => 'txt', 'size' => 5, 'mime' => 'text/plain', 'sha256' => hash( 'sha256', 'WIPE'. $letter ), 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'final' => $letter === 'a', 'created' => '2026-10-01T10:00:00Z' ], 0 );
			$files[] = $id;
		}

		return [ 'folder' => $folder, 'files' => $files ];
	};
	$goes = $make( 'wipe-goes', 'Geht' );
	$goesToo = $make( 'wipe-goes', 'Geht auch' );
	$stays = $make( 'wipe-stays', 'Bleibt' );
	$bytesOf = static fn( string $id ): bool => is_file( (string) $store::bytes( $appData, $id, 'txt' ) );
	$indexBefore = \Nino\Filesystem::getFileContent( $appData, $store::INDEX, [] );

	$none = $store::wipeGroups( $appData, static fn( string $group ): bool => $group === 'wipe-nobody' );
	check( 'a pick that matches no group deletes nothing and answers empty lists: the index is as it was, byte for byte', $none === [ 'folders' => [], 'files' => [] ] && \Nino\Filesystem::getFileContent( $appData, $store::INDEX, [] ) === $indexBefore );

	$asked = [];
	$lost = $store::wipeGroups( $appData, static function( string $group ) use ( &$asked ): bool { $asked[] = $group; return $group === 'wipe-goes'; } );
	check( 'the pick is asked for the group of every folder; the folders of the group picked go with their files - the answer lists both with their ids, the files with their final mark - and the group not picked keeps its folder, its files and its bytes',
		is_array( $lost ) === true && count( $lost['folders'] ) === 2 && count( $lost['files'] ) === 4 && in_array( 'wipe-stays', $asked, true ) === true && array_column( $lost['folders'], 'id' ) === [ $goes['folder']['id'], $goesToo['folder']['id'] ] && count( array_filter( $lost['files'], static fn( array $file ): bool => $file['final'] === true ) ) === 2
		&& $store::folder( $appData, $goes['folder']['id'] ) === null && $store::folder( $appData, $goesToo['folder']['id'] ) === null && array_filter( array_merge( $goes['files'], $goesToo['files'] ), static fn( string $id ): bool => $store::file( $appData, $id ) !== null || $bytesOf( $id ) === true ) === []
		&& $store::folder( $appData, $stays['folder']['id'] ) !== null && array_filter( $stays['files'], static fn( string $id ): bool => $store::file( $appData, $id ) === null || $bytesOf( $id ) === false ) === [] );

	$again = $store::wipeGroups( $appData, static fn( string $group ): bool => $group === 'wipe-goes' );
	check( '...a second pick of the same group finds nothing', $again === [ 'folders' => [], 'files' => [] ] );

	$parts = \Nino\Filesystem::path( $appData, $store::PARTS );
	\Nino\Filesystem::forceDir( $appData, $store::PARTS );
	file_put_contents( $parts. '/00112233aabbccdd.part', 'X' );
	file_put_contents( $parts. '/00112233aabbccdd.chunk', '1' );
	file_put_contents( $parts. '/ffeeddccbbaa9988.part', 'Y' );
	touch( $parts. '/ffeeddccbbaa9988.part', time() - 3 * 86400 );
	check( 'dropParts() removes every upload that is not finished, the new and the old, with the length kept beside it, and counts the parts', $store::dropParts( $appData ) === 2 && glob( $parts. '/*' ) === [] && $store::dropParts( $appData ) === 0 );

	$store::wipeGroups( $appData, static fn( string $group ): bool => $group === 'wipe-stays' );
}

/**
 *	Calls of Upload::receive() that run at the same moment in processes of their own, over the sandbox of this suite: every process waits at a gate until all are
 *	there, so that the calls meet. What one process does is the whole of a request of the upload
 *
 *	@param		array 		&$appData
 *	@param		string		$root					The checkout the suite runs against
 *	@param		array 		$jobs					Each: folder (the entry), by, post, bytes
 *
 *	@return 	array										For each job the answer of Upload::receive() - [] for a process that gave none
 */
function filesRace( array &$appData, string $root, array $jobs ): array {

	$sandbox = ninoSandboxDir( $appData );
	$script = $sandbox. '/race.php';
	$gate = $sandbox. '/race.gate';
	@unlink( $gate );

	file_put_contents( $script, '<?php
define( "NINO_FEATURES_DIR", '. var_export( NINO_FEATURES_DIR, true ). ' );
require '. var_export( $root. '/tests/harness.php', true ). ';
$sandbox = '. var_export( $sandbox, true ). ';
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$appData = [ "./nino/uid" => $sandbox ];
\Nino\AppData::prepare( $appData );
$appData["./nino/filesystem/path"] = $sandbox;
$appData["./nino/filesystem/configpath"] = $sandbox. "/private";
$appData["./nino/filesystem/contentpath"] = $sandbox. "/private";
$appData["./nino/filesystem/publicpath"] = $sandbox. "/public";
$appData["./nino/test/sandbox"] = $sandbox;
\Nino\Filesystem::init( $appData );
\Nino\AppData::init( $appData );
$job = json_decode( (string) file_get_contents( $argv[1] ), true );
$tmp = $argv[1]. ".bytes";
file_put_contents( $tmp, base64_decode( $job["bytes"] ) );
while( is_file( '. var_export( $gate, true ). ' ) === false )
	usleep( 200 );
$answer = \Nino\Modules\Files\Upload::receive( $appData, $job["folder"], $job["post"], [ "error" => 0, "tmp_name" => $tmp, "size" => strlen( base64_decode( $job["bytes"] ) ) ], $job["by"], true );
echo json_encode( $answer );
' );

	$running = [];

	foreach( $jobs as $at => $job ) {
		$file = $sandbox. '/race-'. $at. '.json';
		file_put_contents( $file, json_encode( [ 'folder' => $job['folder'], 'by' => $job['by'], 'post' => $job['post'], 'bytes' => base64_encode( $job['bytes'] ) ] ) );
		$process = proc_open( [ PHP_BINARY, $script, $file ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
		$running[$at] = [ $process, $pipes ];
	}

	// Every process is at the gate once it has read its job: the gate opens and they run together
	usleep( 600000 );
	touch( $gate );

	$answers = [];

	foreach( $running as $at => [ $process, $pipes ] ) {
		$answers[$at] = (array) json_decode( (string) stream_get_contents( $pipes[1] ), true );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );
	}

	return $answers;
}

/**
 *	A deleted account's hook run by a process that may read the data and take the locks and may not write a file of it: what the hook says of the write that fails
 *
 *	@param		array 		&$appData
 *	@param		string		$root					The checkout the suite runs against
 *	@param		string		$id						The id of the account
 *
 *	@return 	array										The warnings the hook raised, as texts - [ 'not run' ] for a process that gave none
 */
function filesAsNobody( array &$appData, string $root, string $id ): array {

	$sandbox = ninoSandboxDir( $appData );
	$script = $sandbox. '/nobody.php';
	$lockDir = \Nino\Filesystem::path( $appData, '/data' ). '/.locks';

	// The locks are taken by whoever reads: the directory and the files of them are everybody's
	\Nino\Filesystem::mutate( $appData, \Nino\Modules\Files\Store::INDEX, static fn( mixed $state ): ?array => null, [] );
	@chmod( $lockDir, 0777 );

	foreach( glob( $lockDir. '/*.lock' ) ?: [] as $lockFile )
		@chmod( $lockFile, 0666 );

	file_put_contents( $script, '<?php
define( "NINO_FEATURES_DIR", '. var_export( NINO_FEATURES_DIR, true ). ' );
require '. var_export( $root. '/tests/harness.php', true ). ';
$sandbox = '. var_export( $sandbox, true ). ';
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$appData = [ "./nino/uid" => $sandbox ];
\Nino\AppData::prepare( $appData );
$appData["./nino/filesystem/path"] = $sandbox;
$appData["./nino/filesystem/configpath"] = $sandbox. "/private";
$appData["./nino/filesystem/contentpath"] = $sandbox. "/private";
$appData["./nino/filesystem/publicpath"] = $sandbox. "/public";
$appData["./nino/test/sandbox"] = $sandbox;
\Nino\Filesystem::init( $appData );
\Nino\AppData::init( $appData );
$said = [];
set_error_handler( static function( int $level, string $message ) use ( &$said ): bool { $said[] = $message; return true; } );
posix_setgid( 65534 );
posix_setuid( 65534 );
$account = [ "id" => $argv[1], "email" => "gone@example.org", "group" => "staff" ];
\Nino\Modules\Files::callbackRemoved( $appData, $account );
echo json_encode( $said );
' );

	$out = (string) shell_exec( escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $script ). ' '. escapeshellarg( $id ). ' 2>/dev/null' );
	$said = json_decode( $out, true );

	return is_array( $said ) === true ? $said : [ 'not run' ];
}

/**
 *	What the feature declares and promises, one pin for each: the interface (every public method of the two classes, named in the manual), the hooks (what the
 *	code fires and hears, as the manual lists it), a deleted account (its uploads stay, the sender has no name), what the kernel's backup carries, a member who
 *	is switched off, the names of a file that are an attack, every default type held to its bytes, the methods of the owner that check for themselves, and the
 *	panel without the token of the page
 *
 *	@param		array 		&$appData
 *	@param		array 		$room					A folder of the group staff that has the upload mark
 *	@param		callable	$panel				The workbench's entry point
 *
 *	@return 	void
 */
function filesSpecChecks( array &$appData, array $room, callable $panel ): void {

	global $mails, $sessions, $smallFile, $root;

	echo "\nThe interface, the hooks, the backup and the attacks on names\n";

	$feature = (array) include dirname( __DIR__ ). '/feature.php';
	// The three entries that declare the interface: the names are looked for there and nowhere else in the manual, in each language
	$entries = array_filter( $feature['manual']['callbacks'], static fn( string $key ): bool => str_starts_with( $key, 'For other features' ), ARRAY_FILTER_USE_KEY );
	$manuals = [ 'en_US' => implode( ' ', array_column( $entries, 'en_US' ) ), 'de_DE' => implode( ' ', array_column( $entries, 'de_DE' ) ) ];
	$store = \Nino\Modules\Files\Store::class;

	// --- the interface: every public method of the two classes is named in the manual, except the ones the kernel calls
	$methods = static function( string $class ): array {
		$list = array_map( static fn( ReflectionMethod $method ): string => $method->getName(), array_filter( ( new ReflectionClass( $class ) )->getMethods( ReflectionMethod::IS_PUBLIC ), static fn( ReflectionMethod $method ): bool => $method->getDeclaringClass()->getName() === ltrim( $class, '\\' ) ) );
		sort( $list );

		return array_values( $list );
	};
	$kernel = [ 'adminPanels', 'callbackDownload', 'callbackRemoved', 'callbackRestore', 'callbackUpload', 'componentFilesBrowser', 'componentFilesUpload', 'doShortcodeMailHead', 'init' ];
	$ours = $methods( \Nino\Modules\Files::class );
	$data = $methods( $store );
	$unnamed = array_values( array_unique( array_merge( ...array_values( array_map( static fn( string $text ): array => array_filter( array_merge( $ours, $data ), static fn( string $name ): bool => str_contains( $text, $name ) === false ), $manuals ) ) ) ) );

	check( 'the interface of \Nino\Modules\Files is these 31 public methods, those of the Store these 37 - the manual names every one of them in both languages but the ones the kernel calls (the init, the callbacks, the components and the panels), which it says are no interface',
		$ours === [ 'adminPanels', 'allowed', 'callbackDownload', 'callbackRemoved', 'callbackRestore', 'callbackUpload', 'change', 'componentFilesBrowser', 'componentFilesUpload', 'createFolder', 'deleteFile', 'deleteFolder', 'disposition',
			'doShortcodeMailHead', 'file', 'files', 'folders', 'grouped', 'init', 'markFolder', 'notify', 'notifyMembers', 'notifyOwner', 'overview', 'page', 'path', 'removeFile', 'renameFolder', 'upload', 'url', 'version' ]
		&& count( $data ) === 37 && array_diff( $unnamed, $kernel ) === [] && count( array_intersect( $unnamed, $kernel ) ) === count( $unnamed ) );

	// --- the hooks: what the code fires and hears is what the manual lists, and nothing is fired that the manual does not know
	$code = (string) file_get_contents( dirname( __DIR__ ). '/Files.php' ). (string) file_get_contents( dirname( __DIR__ ). '/Store/Store.php' ). (string) file_get_contents( dirname( __DIR__ ). '/Upload/Upload.php' );
	preg_match_all( "~(?:doCallbacks|registered|registerCallback)\( \\\$appData, '(/[a-z/]+)'~", $code, $found );
	$used = array_values( array_unique( $found[1] ) );
	sort( $used );
	$listed = array_values( array_filter( array_keys( $feature['manual']['callbacks'] ), static fn( string $key ): bool => str_starts_with( $key, '/' ) ) );
	sort( $listed );

	check( 'the hooks of the manual are the ones the code fires, hears or asks - /files/upload, /files/add, /files/delete, /files/granted, /files/allowed, /members/removed, and /nino/admin/restore - and none besides',
		$used === $listed && $listed === [ '/files/add', '/files/allowed', '/files/delete', '/files/granted', '/files/upload', '/members/removed', '/nino/admin/restore' ] );

	// --- a deleted account: the uploads stay and the sender has no name
	$dora = \Nino\Modules\Members::add( $appData, 'dora@example.org', 'Dora', 'staff' );
	$mine = filesMake( $appData, $room['id'], 'Dora.mp3', 'DORA-BYTES', [ 'kind' => 'member', 'id' => $dora['id'] ] );
	$mine2 = filesMake( $appData, $room['id'], 'Dora Two.mp3', 'DORA-TWO', [ 'kind' => 'member', 'id' => $dora['id'] ] );
	$ada = \Nino\Modules\Members::accounts( $appData )[array_search( 'ada@example.org', array_column( \Nino\Modules\Members::accounts( $appData ), 'email' ), true )];
	$adas = filesMake( $appData, $room['id'], 'Ada.mp3', 'ADA-BYTES', [ 'kind' => 'member', 'id' => $ada['id'] ] );
	$owners = filesMake( $appData, $room['id'], 'Owner.mp3', 'OWNER-BYTES' );
	$mine3 = filesMake( $appData, $room['id'], 'Dora Three.mp3', 'DORA-THREE', [ 'kind' => 'member', 'id' => $dora['id'] ] );
	$told = [];
	\Nino\Callbacks::registerCallback( $appData, '/members/removed', static function( array &$appData, array &$account ) use ( &$told ): void { $told[] = $account; } );
	$removed = \Nino\Modules\Members::remove( $appData, $dora['id'] );
	$after = filesIndex( $appData )['files'];

	check( 'a deleted account tells /members/removed once - id, address, group - and each of the three files it uploaded stays with its bytes, its name, its size and its folder; the sender is a member without an id, nobody else\'s upload and not the owner\'s changes',
		$removed === 'ok' && count( $told ) === 1 && $told[0] === [ 'id' => $dora['id'], 'email' => 'dora@example.org', 'group' => 'staff' ] && isset( $after[$mine['id']] ) === true && $after[$mine['id']]['by'] === [ 'kind' => 'member', 'id' => '' ]
		&& array_diff_key( $after[$mine['id']], [ 'by' => 1 ] ) === array_diff_key( array_diff_key( $mine, [ 'id' => 1 ] ), [ 'by' => 1 ] ) && is_file( (string) \Nino\Modules\Files::path( $appData, $mine['id'] ) ) === true
		&& $after[$mine2['id']]['by'] === [ 'kind' => 'member', 'id' => '' ] && $after[$mine3['id']]['by'] === [ 'kind' => 'member', 'id' => '' ] && is_file( (string) \Nino\Modules\Files::path( $appData, $mine3['id'] ) ) === true
		&& $after[$adas['id']]['by'] === [ 'kind' => 'member', 'id' => $ada['id'] ] && $after[$owners['id']]['by'] === [ 'kind' => 'owner', 'id' => '' ] );
	check( '...the list of the panel names the sender of that file as nobody and the others by their name - and the file is still served to the group',
		( static function() use ( &$appData, $panel, $room, $mine, $adas ): bool {
			filesWorkbench( 'admin' );
			$listed = ninoCall( $appData, $panel, [ 'folder' => $room['id'] ], 'files/list' ); $files = array_column( $listed[1]['files'] ?? [], 'by', 'id' );

			return ( $files[$mine['id']]['label'] ?? 'x' ) === '' && ( $files[$adas['id']]['label'] ?? '' ) === 'Ada Lovelace';
		} )() && ( static function() use ( &$appData, $mine ): bool { filesAs( 'cyd' ); return filesServe( $appData, '/.files/'. $mine['id'] )['/nino/http/response']['statusCode'] === 200; } )() );

	$before = file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) );
	$none = [ 'id' => 'ffffffffffff', 'email' => 'nobody@example.org', 'group' => 'staff' ];
	\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $none );
	$empty = [ 'id' => '', 'email' => '', 'group' => '' ];
	\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $empty );
	check( 'an account that uploaded nothing, and a call with no id, write nothing: the index is the same bytes as before', file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) ) === $before );
	$store::deleteFile( $appData, $mine['id'] );
	$store::deleteFile( $appData, $mine2['id'] );
	$store::deleteFile( $appData, $mine3['id'] );
	$store::deleteFile( $appData, $adas['id'] );
	$store::deleteFile( $appData, $owners['id'] );

	// --- what the kernel's backup carries: the index, not the bytes (the archive is read into memory whole), and nothing of an upload that is not finished
	$keep = filesMake( $appData, $room['id'], 'Backup.mp3', 'BACKUP-BYTES' );
	\Nino\Filesystem::forceDir( $appData, $store::PARTS );
	file_put_contents( \Nino\Filesystem::path( $appData, $store::PARTS ). '/00112233aabbccdd.part', 'HALF' );
	$carried = array_values( \Nino\Backup::manifest( $appData ) );
	$files = array_values( array_filter( $carried, static fn( string $name ): bool => str_starts_with( $name, 'data/files' ) ) );
	$store::dropParts( $appData );

	check( 'the kernel\'s backup carries the index and none of the bytes by the manifest\'s data - /data/files.php only - so that the daily archive does not grow with the files; the manual says that the operator saves /data/files, in both languages',
		$feature['data'] === [ '/data/files.php' ] && $files === [ 'data/files.php' ] && in_array( 'data/files/'. $keep['id']. '.mp3', $carried, true ) === false
		&& str_contains( $feature['manual']['install']['/data/files/']['en_US'], 'saves data/files with the server' ) === true && str_contains( $feature['manual']['install']['/data/files/']['de_DE'], 'sichert der Betreiber mit dem Server' ) === true );

	// --- a restore of an index whose bytes are not on the server: the row stays, as removed from the server - the owner sees it, a member who may have it is told it is removed, nobody is offered bytes that are not there
	$livePath = \Nino\Filesystem::path( $appData, $store::INDEX );
	$liveText = (string) file_get_contents( $livePath );
	$lost = filesMake( $appData, $room['id'], 'Lost.mp3', 'LOST-BYTES' );
	$kept = filesMake( $appData, $room['id'], 'Kept.mp3', 'KEPT-BYTES' );
	$backupDir = ninoSandboxDir( $appData ). '/restore-lost';
	@mkdir( $backupDir. '/data', 0755, true );
	copy( $livePath, $backupDir. '/data/files.php' );
	unlink( (string) \Nino\Modules\Files::path( $appData, $lost['id'] ) );
	$args = [ 'dataDir' => \Nino\Filesystem::path( $appData, '/data' ), 'staging' => $backupDir ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $args );
	copy( $backupDir. '/data/files.php', $livePath );
	filesWorkbench( 'admin' );
	$rowsOf = array_column( ninoCall( $appData, $panel, [ 'folder' => $room['id'] ], 'files/list' )[1]['files'], null, 'id' );
	filesAs( 'ada' );
	$answerOf = static fn( string $id ): int => filesServe( $GLOBALS['appData'], '/.files/'. $id )['/nino/http/response']['statusCode'];

	check( 'a restore of an index whose bytes are gone from the server keeps the row as removed from it: the panel lists it with the time it went and no download, a member who may have it is answered 410 and a file whose bytes are there is still served 200',
		isset( $rowsOf[$lost['id']]['gone'] ) === true && isset( $rowsOf[$kept['id']]['gone'] ) === false && $answerOf( $lost['id'] ) === 410 && $answerOf( $kept['id'] ) === 200 && \Nino\Modules\Files::path( $appData, $lost['id'] ) === null );
	file_put_contents( $livePath, $liveText );
	$store::deleteFile( $appData, $lost['id'] );
	$store::deleteFile( $appData, $kept['id'] );
	$store::deleteFile( $appData, $keep['id'] );

	// --- the data as the source wrote it: a stock of folders and files, one file removed from the server, read and written without a change to what was not touched
	$stock = [
		'folders' => [
			'a1a1a1a1a1a1' => [ 'group' => 'staff', 'name' => 'Proben & Mitschnitte', 'upload' => true, 'created' => '2025-03-01T10:00:00Z' ],
			'b2b2b2b2b2b2' => [ 'group' => 'guests', 'name' => 'Gäste', 'upload' => false, 'created' => '2025-03-02T10:00:00Z' ],
		],
		'files' => [
			'c3c3c3c3c3c3' => [ 'folder' => 'a1a1a1a1a1a1', 'name' => 'Probe.03.mp3', 'ext' => 'mp3', 'size' => 2500000, 'mime' => 'audio/mpeg', 'sha256' => str_repeat( 'a', 64 ), 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'final' => true, 'created' => '2025-03-03T10:00:00Z' ],
			'd4d4d4d4d4d4' => [ 'folder' => 'a1a1a1a1a1a1', 'name' => 'Alt.mp3', 'ext' => 'mp3', 'size' => 100, 'mime' => 'audio/mpeg', 'sha256' => str_repeat( 'b', 64 ), 'by' => [ 'kind' => 'member', 'id' => 'e5e5e5e5e5e5' ], 'visible' => false, 'final' => false, 'created' => '2025-03-04T10:00:00Z', 'gone' => '2025-06-01T08:00:00Z' ],
			'f6f6f6f6f6f6' => [ 'folder' => 'b2b2b2b2b2b2', 'name' => 'Gästeliste.pdf', 'ext' => 'pdf', 'size' => 4096, 'mime' => 'application/pdf', 'sha256' => str_repeat( 'c', 64 ), 'by' => [ 'kind' => 'member', 'id' => '' ], 'visible' => true, 'final' => false, 'created' => '2025-03-05T10:00:00Z' ],
		],
	];
	$live = file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) );
	\Nino\Filesystem::putFileContent( $appData, $store::INDEX, $stock );
	$read = $store::read( $appData );
	$changed = $store::changeFile( $appData, 'c3c3c3c3c3c3', [ 'visible' => false ] );
	$written = (array) include \Nino\Filesystem::path( $appData, $store::INDEX );
	$expected = $stock;
	$expected['files']['c3c3c3c3c3c3']['visible'] = false;

	check( 'a stock of data the source wrote - two folders, three files, one of them removed from the server, a sender that is no one - is read as it is, a change to one file writes the stock back with that one value changed, the removed file keeps its time, and the order and the keys of every entry are the same',
		$read === $stock && is_array( $changed ) === true && $written === $expected && array_keys( $written['files'] ) === array_keys( $stock['files'] ) && $written['files']['d4d4d4d4d4d4']['gone'] === '2025-06-01T08:00:00Z'
		&& array_map( 'array_keys', $written['files'] ) === array_map( 'array_keys', $stock['files'] ) && $written['folders'] === $stock['folders'] );
	check( '...the bytes belong to <id>.<ext> in /data/files, the unfinished uploads to /data/files/.upload - the two places the source used',
		$store::BYTES === '/data/files' && $store::PARTS === '/data/files/.upload' && (string) $store::bytes( $appData, 'c3c3c3c3c3c3', 'mp3' ) === \Nino\Filesystem::path( $appData, '/data/files' ). '/c3c3c3c3c3c3.mp3' );
	file_put_contents( \Nino\Filesystem::path( $appData, $store::INDEX ), $live );

	// --- the cap of the chunk requests counts a client as the kernel does: the addresses of one IPv6 /64 behind a trusted proxy are one counter
	$appData['/nino/http/proxies'] = [ '127.0.0.1' ];
	\Nino\Features::saveSettings( $appData, 'files', [ 'attempts' => 3 ] );
	filesFresh( $appData );
	filesVisitor();
	$fieldsOf = [ 'folder' => $room['id'], 'name' => 'Cap.txt', 'size' => '1', 'upload' => '', 'index' => '0', 'count' => '1' ];
	$viaProxy = static function( string $address ) use ( &$appData, $fieldsOf ): int {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = $address;
		$status = filesServe( $appData, '/.files/upload', 'POST', $fieldsOf, true, [ 'chunk' => filesPart( $appData, 'a' ) ] )['/nino/http/response']['statusCode'];
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		return $status;
	};
	$network = array_map( $viaProxy, [ '2001:db8:7:7::1', '2001:db8:7:7:aaaa::2', '2001:db8:7:7:bbbb:cccc::3', '2001:db8:7:7:dddd::4', '2001:db8:7:7::5' ] );
	$other = $viaProxy( '2001:db8:7:8::1' );
	filesFresh( $appData );
	$single = array_map( $viaProxy, [ '203.0.113.9', '203.0.113.9', '203.0.113.9', '203.0.113.9' ] );
	unset( $appData['/nino/http/proxies'] );
	\Nino\Features::saveSettings( $appData, 'files', [ 'attempts' => 300 ] );
	filesFresh( $appData );

	check( 'the cap of chunk requests is kept by the /64 of an IPv6 address behind a trusted proxy: five addresses of one network are one visitor (the fourth is 429), another network and a single IPv4 address start their own count',
		$network === [ 404, 404, 404, 429, 429 ] && $other === 404 && $single === [ 404, 404, 404, 429 ] );

	// --- a mail reads nothing twice: a name that holds the tokens of the mail is text in it, once, whichever token comes first
	$tokenName = 'x[[link]][[who]][[folder]].mp3';
	\Nino\Features::saveSettings( $appData, 'files', [ 'notifyOwner' => true, 'notifyMembers' => true ] );
	$mails = [];
	filesMemberUpload( $appData, 'ada', $tokenName, $room['id'], $smallFile );
	$ownerMail = strip_tags( (string) ( $mails[0]['body'] ?? '' ) );
	$ownerLinks = substr_count( (string) ( $mails[0]['body'] ?? '' ), 'href="https://example.org/club/_admin#files/files/' );
	$mails = [];
	filesWorkbench( 'admin' );
	filesOwnerUpload( $appData, $tokenName, $room['id'] );
	$groupMail = (string) ( $mails[0]['body'] ?? '' );
	\Nino\Features::saveSettings( $appData, 'files', [ 'notifyOwner' => false, 'notifyMembers' => false ] );

	check( 'a file named x[[link]][[who]][[folder]].mp3 is that name in the mail to the owner and in the mail to the group - once, as text - and no token of the mail is filled again from it: the link of the mail is there once, the name of the member once, the name of the folder once',
		str_contains( $ownerMail, $tokenName ) === true && $ownerLinks === 1 && substr_count( $ownerMail, 'Ada Lovelace' ) === 1 && substr_count( $ownerMail, 'xhttps' ) === 0
		&& str_contains( strip_tags( $groupMail ), $tokenName ) === true && substr_count( strip_tags( $groupMail ), 'xRoom' ) === 0 && substr_count( $groupMail, 'x[[' ) >= 1 );
	$mails = [];

	// --- a hook that cannot write says so: the id of the deleted account stays in the index, a warning names no address - and a hook that writes says nothing
	if( function_exists( 'posix_setuid' ) === false || posix_getuid() !== 0 )
		echo "  note - this process cannot give up its rights (posix, root): the write of the hook that fails was not tried\n";
	else {
		$cleared = filesMake( $appData, $room['id'], 'Cleared.mp3', 'CLEARED', [ 'kind' => 'member', 'id' => 'e1e1e1e1e1e1' ] );
		$indexNow = (string) file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) );
		$warned = filesAsNobody( $appData, $root, 'e1e1e1e1e1e1' );
		$unchanged = (string) file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) ) === $indexNow && $store::file( $appData, $cleared['id'] )['by']['id'] === 'e1e1e1e1e1e1';
		$account = [ 'id' => 'e1e1e1e1e1e1', 'email' => 'gone@example.org', 'group' => 'staff' ];
		$quiet = ninoErrors( static function() use ( &$appData, &$account ): void { \Nino\Callbacks::doCallbacks( $appData, '/members/removed', $account ); } );

		check( 'a hook that cannot write the index says so - the warning of the kernel about the file and one of its own that names no address -, and the index and the id in it stay as they were; the same hook that can write says nothing and makes the upload nameless',
			count( array_filter( $warned, static fn( string $each ): bool => str_starts_with( $each, 'Files:' ) ) ) === 1 && array_filter( $warned, static fn( string $each ): bool => str_contains( $each, '@' ) ) === [] && $unchanged === true && $quiet === [] && $store::file( $appData, $cleared['id'] )['by'] === [ 'kind' => 'member', 'id' => '' ] );
		$store::deleteFile( $appData, $cleared['id'] );
	}

	// --- what a browser may do with the bytes: a pdf that is shown runs in a sandbox, everything else is let run nothing
	$pdf = filesMake( $appData, $room['id'], 'Shown.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n", [ 'kind' => 'owner', 'id' => '' ], true, 'application/pdf' );
	$image = filesMake( $appData, $room['id'], 'Shown.png', $samples['png'] ?? "\x89PNG\r\n\x1a\n", [ 'kind' => 'owner', 'id' => '' ], true, 'image/png' );
	$sound = filesMake( $appData, $room['id'], 'Shown.mp3', 'SOUND', [ 'kind' => 'owner', 'id' => '' ], true, 'audio/mpeg' );
	$notes = filesMake( $appData, $room['id'], 'Shown.txt', 'TEXT', [ 'kind' => 'owner', 'id' => '' ], true, 'text/plain' );
	filesAs( 'ada' );
	// What a file may load when it is opened by itself: the picture, the sound or the film of its own address and the one style a media page sets - nothing else, and no script
	$quiet = "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox allow-same-origin";
	$csp = static fn( string $id, string $query = '' ): string => (string) ( filesServe( $GLOBALS['appData'], '/.files/'. $id. $query )['/nino/http/response']['header']['Content-Security-Policy'] ?? '' );

	check( 'a pdf that is shown is answered with the Content-Security-Policy sandbox; a pdf that is saved, a picture, a sound and a text - shown or saved - with the policy that lets the file load its own picture, sound or film and nothing else, in a sandbox: no file runs anything as this site',
		$csp( $pdf['id'] ) === 'sandbox' && $csp( $pdf['id'], '?download=1' ) === $quiet && $csp( $image['id'] ) === $quiet && $csp( $sound['id'] ) === $quiet && $csp( $notes['id'] ) === $quiet && $csp( $sound['id'], '?download=1' ) === $quiet );
	foreach( [ $pdf, $image, $sound, $notes ] as $each )
		$store::deleteFile( $appData, $each['id'] );

	// --- an upload belongs to the account and the folder that began it
	$upload = \Nino\Modules\Files\Upload::class;
	$adaId = (string) $ada['id'];
	$bobEntry = \Nino\Modules\Members::accounts( $appData )[array_search( 'bob@example.org', array_column( \Nino\Modules\Members::accounts( $appData ), 'email' ), true )];
	$adaBy = [ 'kind' => 'member', 'id' => $adaId ];
	$bobBy = [ 'kind' => 'member', 'id' => (string) $bobEntry['id'] ];
	$bobsFolder = $store::addFolder( $appData, 'guests', 'Bobs' );
	$sideFolder = $store::addFolder( $appData, 'staff', 'Side' );
	$post = static fn( int $index, string $up, int $size = 10, int $count = 2 ): array => [ 'name' => 'Bind.txt', 'size' => (string) $size, 'upload' => $up, 'index' => (string) $index, 'count' => (string) $count ];
	$begin = $upload::receive( $appData, $room, $post( 0, '' ), filesPart( $appData, 'abcde' ), $adaBy, true );
	$began = (string) ( $begin['part']['upload'] ?? '' );
	$nobody = $upload::receive( $appData, $room, $post( 1, '0123456789abcdef' ), filesPart( $appData, 'fghij' ), $adaBy, true );
	$strangers = [
		'another account of another group in its own folder' => $upload::receive( $appData, $bobsFolder, $post( 1, $began ), filesPart( $appData, 'fghij' ), $bobBy, true ),
		'the same account in another folder' => $upload::receive( $appData, $sideFolder, $post( 1, $began ), filesPart( $appData, 'fghij' ), $adaBy, true ),
		'another account in the same folder' => $upload::receive( $appData, $room, $post( 1, $began ), filesPart( $appData, 'fghij' ), $bobBy, true ),
		'the owner' => $upload::receive( $appData, $room, $post( 1, $began ), filesPart( $appData, 'fghij' ), [ 'kind' => 'owner', 'id' => '' ], true ),
		'another size' => $upload::receive( $appData, $room, $post( 1, $began, 12, 3 ), filesPart( $appData, 'fghij' ), $adaBy, true ),
	];
	$partPath = \Nino\Filesystem::path( $appData, $store::PARTS ). '/'. $began. '.part';
	$sizeBefore = filesSize( $partPath );
	$finished = $upload::receive( $appData, $room, $post( 1, $began ), filesPart( $appData, 'fghij' ), $adaBy, true );

	check( 'an upload belongs to the account and the folder that began it, and to the size it said: another account, the owner, another folder, another size - each is answered as an upload nobody began, the part stays as long as it was, and whoever began it can finish it',
		isset( $begin['part'] ) === true && $nobody === [ 'fail' => [ 400, 'there is no such upload', 'files_chunk', [], '' ] ] && array_filter( $strangers, static fn( array $answer ): bool => $answer !== $nobody ) === []
		&& $sizeBefore === 5 && isset( $finished['file'] ) === true && $finished['file']['by'] === $adaBy );
	$store::deleteFile( $appData, (string) ( $finished['file']['id'] ?? 'ffffffffffff' ) );

	// a gap is answered as a gap whatever else is wrong with the chunk, and an id that is a path names no upload even where files are laid out for it
	$gap = $upload::receive( $appData, $room, $post( 0, '', 15, 3 ), filesPart( $appData, 'abcde' ), $adaBy, true );
	$gapAnswer = $upload::receive( $appData, $room, $post( 2, (string) ( $gap['part']['upload'] ?? '' ), 15, 3 ), filesPart( $appData, 'z' ), $adaBy, true );
	$store::dropParts( $appData );
	$dataDir = \Nino\Filesystem::path( $appData, '/data/files' );
	file_put_contents( $dataDir. '/x.part', 'abcde' );
	file_put_contents( $dataDir. '/x.chunk', '5' );
	file_put_contents( $dataDir. '/x.owner', json_encode( [ 'folder' => $room['id'], 'kind' => 'member', 'id' => $adaId, 'size' => 10 ] ) );
	$path = $upload::receive( $appData, $room, $post( 1, '../x' ), filesPart( $appData, 'fghij' ), $adaBy, true );
	$laid = file_get_contents( $dataDir. '/x.part' );
	array_map( 'unlink', [ $dataDir. '/x.part', $dataDir. '/x.chunk', $dataDir. '/x.owner' ] );

	check( 'a chunk that skips one is "not the next of the upload" even when it is also too short, and an id that is a path ("../x") is no upload though a part, its length and its owner are laid out beside the parts: nothing is appended',
		( $gapAnswer['fail'][1] ?? '' ) === 'the chunk is not the next of the upload' && $path === [ 'fail' => [ 400, 'there is no such upload', 'files_chunk', [], '' ] ] && $laid === 'abcde' );

	// nothing of an upload that did not become a file is left: a refused assembly, a first chunk that could not be kept, and a reservation without a part that has been there for a while
	$parts = static fn(): array => array_map( 'basename', glob( \Nino\Filesystem::path( $appData, $store::PARTS ). '/*' ) ?: [] );
	$store::dropParts( $appData );
	$refusedFile = $upload::receive( $appData, $room, [ 'name' => 'Refused.jpg', 'size' => '16', 'upload' => '', 'index' => '0', 'count' => '1' ], filesPart( $appData, '<?php echo 1; ?>' ), $adaBy, true );
	$afterRefusal = $parts();
	$lostChunk = $upload::receive( $appData, $room, $post( 0, '' ), [ 'error' => UPLOAD_ERR_OK, 'tmp_name' => ninoSandboxDir( $appData ). '/not-there', 'size' => 5 ], $adaBy, true );
	$afterLost = $parts();
	file_put_contents( \Nino\Filesystem::path( $appData, $store::PARTS ). '/aaaaaaaaaaaaaaaa.owner', json_encode( [ 'folder' => $room['id'], 'kind' => 'member', 'id' => $adaId, 'size' => 10 ] ) );
	file_put_contents( \Nino\Filesystem::path( $appData, $store::PARTS ). '/bbbbbbbbbbbbbbbb.owner', json_encode( [ 'folder' => $room['id'], 'kind' => 'member', 'id' => $adaId, 'size' => 10 ] ) );
	touch( \Nino\Filesystem::path( $appData, $store::PARTS ). '/aaaaaaaaaaaaaaaa.owner', time() - 11 * 60 );
	touch( \Nino\Filesystem::path( $appData, $store::PARTS ). '/bbbbbbbbbbbbbbbb.owner', time() - 5 * 60 );
	$store::sweep( $appData );
	$afterSweep = $parts();
	$store::dropParts( $appData );

	check( 'a refused assembly (php as a jpg) leaves no part, no length and no reservation; a first chunk that cannot be kept is a 500 and leaves none; a reservation with no part is let be for five minutes and swept after eleven',
		( $refusedFile['fail'][2] ?? '' ) === 'files_type' && $afterRefusal === [] && ( $lostChunk['fail'][2] ?? '' ) === 'files_write' && $afterLost === [] && $afterSweep === [ 'bbbbbbbbbbbbbbbb.owner' ] );

	// the data layer does not rely on its caller for an upload id: an id that is a path or has a null byte is no upload for either method
	$dataDir = \Nino\Filesystem::path( $appData, '/data/files' );
	file_put_contents( $dataDir. '/x.owner', json_encode( [ 'folder' => $room['id'], 'kind' => 'member', 'id' => $adaId, 'size' => 10 ] ) );
	$tried = static function( callable $call ): mixed {
		try {
			return $call();
		}
		catch( \Throwable $e ) {
			return 'threw';
		}
	};
	$outside = $tried( static fn() => $store::reservation( $GLOBALS['appData'], '../x' ) );
	$nulled = $tried( static fn() => $store::reservation( $GLOBALS['appData'], "aaaaaaaaaaaaaaa\0" ) );
	$reservedPath = $tried( static fn() => $store::reserve( $GLOBALS['appData'], $room, '../y', $adaBy, 10, 0 ) );
	$reservedNull = $tried( static fn() => $store::reserve( $GLOBALS['appData'], $room, "cccccccccccccc\0\0", $adaBy, 10, 0 ) );
	$written = array_filter( [ $dataDir. '/y.owner', $dataDir. '/.upload/cccccccccccccc.owner' ], 'is_file' );
	unlink( $dataDir. '/x.owner' );

	check( 'reservation() and reserve() hold the upload id to the protocol themselves: "../x" with a reservation laid out beside the parts and an id with a null byte are no reservation and make none',
		$outside === null && $nulled === null && $reservedPath === 'write' && $reservedNull === 'write' && $written === [] );

	// a restore believes a file only when it is as long as the row says: another file under the name of a row is no file of the row, and bytes of an older backup are looked at as well
	$stagedDir = ninoSandboxDir( $appData ). '/restore-size';
	@mkdir( $stagedDir. '/data/files', 0755, true );
	$entryOf = static fn( string $name, int $size ): array => [ 'folder' => $room['id'], 'name' => $name, 'ext' => 'mp3', 'size' => $size, 'mime' => 'audio/mpeg', 'sha256' => '', 'by' => [ 'kind' => 'owner', 'id' => '' ], 'visible' => true, 'final' => false, 'created' => '2026-01-01T00:00:00Z' ];
	file_put_contents( $dataDir. '/121212121212.mp3', '<html><script>x</script></html>' );
	file_put_contents( $dataDir. '/343434343434.mp3', 'LIVE' );
	file_put_contents( $stagedDir. '/data/files/565656565656.mp3', 'BACKUP!' );
	file_put_contents( $stagedDir. '/data/files/787878787878.mp3', 'SHORT' );
	file_put_contents( $stagedDir. '/data/files.php', '<?php return '. var_export( [ 'folders' => [ $room['id'] => [ 'group' => 'staff', 'name' => 'Room', 'upload' => true, 'created' => '' ] ], 'files' => [
		'121212121212' => $entryOf( 'Stranger.mp3', 4004 ), '343434343434' => $entryOf( 'Live.mp3', 4 ), '565656565656' => $entryOf( 'Backup.mp3', 7 ), '787878787878' => $entryOf( 'Short.mp3', 99 ) ] ], true ). ';' );
	$restoreArgs = [ 'dataDir' => \Nino\Filesystem::path( $appData, '/data' ), 'staging' => $stagedDir ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/admin/restore', $restoreArgs );
	$restoredSize = (array) include $stagedDir. '/data/files.php';
	array_map( 'unlink', [ $dataDir. '/121212121212.mp3', $dataDir. '/343434343434.mp3' ] );

	check( 'a restore marks a row as removed when the file under its name is not as long as the row says (a page of 31 bytes under a row of 4004), keeps a row whose file is as long - on the server or in an older backup that has the bytes - and marks one whose backup file is too short',
		isset( $restoredSize['files']['121212121212']['gone'] ) === true && isset( $restoredSize['files']['343434343434']['gone'] ) === false && isset( $restoredSize['files']['565656565656']['gone'] ) === false && isset( $restoredSize['files']['787878787878']['gone'] ) === true );

	// the chunks of unfinished uploads count against the quota: the declared size is held from the first chunk, in the write of the index, for as long as chunks come
	// A group of its own, so that what the other tests put into the groups of the members is no part of the room: nothing in it
	$tight = $store::addFolder( $appData, 'tight', 'Tight' );
	$loose = $store::addFolder( $appData, 'loose', 'Loose' );
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 1, 'maxSize' => 5 ] );
	$big = static fn( int $index, string $up, string $bytes ): array => [ 'name' => 'Big.mp3', 'size' => '800000', 'upload' => $up, 'index' => (string) $index, 'count' => '2' ];
	$small = static fn( string $name ): array => [ 'name' => $name, 'size' => '400000', 'upload' => '', 'index' => '0', 'count' => '1' ];
	$mp3 = "\xFF\xFB\x90\x00". str_repeat( 'a', 399996 );
	$held = $upload::receive( $appData, $tight, $big( 0, '', '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	$crossing = $upload::receive( $appData, $tight, $small( 'Cross.txt' ), filesPart( $appData, str_repeat( 'b', 400000 ) ), $bobBy, true );
	$elsewhere = $upload::receive( $appData, $loose, $small( 'Else.txt' ), filesPart( $appData, str_repeat( 'c', 400000 ) ), $bobBy, true );
	$holding = $store::held( $appData, 'tight' );
	$heldPart = \Nino\Filesystem::path( $appData, $store::PARTS ). '/'. ( $held['part']['upload'] ?? 'x' ). '.part';
	touch( $heldPart, time() - 16 * 60 );
	$idle = $store::held( $appData, 'tight' );
	$staleOk = $upload::receive( $appData, $tight, $small( 'Late.txt' ), filesPart( $appData, str_repeat( 'd', 400000 ) ), $bobBy, true );
	touch( $heldPart );

	check( 'an upload that is begun holds its declared size against the quota of its group while its chunks come: with 800000 bytes held of 1 MB another account\'s file of 400000 is 400 files_quota, the other group has its room, held() says 800000 - and an upload whose last chunk is 16 minutes old holds nothing',
		isset( $held['part'] ) === true && ( $crossing['fail'][2] ?? '' ) === 'files_quota' && isset( $elsewhere['file'] ) === true && $holding === 800000 && $idle === 0 && isset( $staleOk['file'] ) === true );
	$store::deleteFile( $appData, (string) ( $elsewhere['file']['id'] ?? 'ffffffffffff' ) );
	$store::deleteFile( $appData, (string) ( $staleOk['file']['id'] ?? 'ffffffffffff' ) );
	$store::dropParts( $appData );

	// an account holds one unfinished upload in a folder: a new beginning gives up the old one, with its part, its length and its reservation - and the room it held
	$one = $upload::receive( $appData, $tight, $big( 0, '', '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	$two = $upload::receive( $appData, $tight, $big( 0, '', '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	$left = array_map( 'basename', glob( \Nino\Filesystem::path( $appData, $store::PARTS ). '/*' ) ?: [] );
	$again = $upload::receive( $appData, $tight, $big( 1, (string) ( $one['part']['upload'] ?? '' ), '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	sort( $left );

	check( 'a second beginning of the same account in the same folder takes the room again that the first held and gives the first up: two uploads of 800000 under 1 MB are both taken, three files are left (the part, its length, its reservation) - those of the second - and a chunk of the first is "no such upload"',
		isset( $one['part'], $two['part'] ) === true && $left === [ $two['part']['upload']. '.chunk', $two['part']['upload']. '.owner', $two['part']['upload']. '.part' ] && $again === [ 'fail' => [ 400, 'there is no such upload', 'files_chunk', [], '' ] ] );
	$store::dropParts( $appData );

	// the owner goes first: what members hold is no room against an upload of the owner - the quota is held against what lies there when a file is put together
	$members = $upload::receive( $appData, $tight, $big( 0, '', '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	$ownerFile = $upload::receive( $appData, $tight, $small( 'Owner.txt' ), filesPart( $appData, str_repeat( 'o', 400000 ) ), [ 'kind' => 'owner', 'id' => '' ], true );
	$finishing = $upload::receive( $appData, $tight, $big( 1, (string) ( $members['part']['upload'] ?? '' ), '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	$store::deleteFile( $appData, (string) ( $ownerFile['file']['id'] ?? 'ffffffffffff' ) );
	$store::dropParts( $appData );

	check( 'an upload of the owner is not held up by what members have begun: 400000 are taken with 800000 held; the member who held them, putting the file together, is held to what lies there - files_quota',
		isset( $members['part'], $ownerFile['file'] ) === true && ( $finishing['fail'][2] ?? '' ) === 'files_quota' );

	// a file that is put in directly - not by an upload - is held to the room the unfinished uploads have not taken: the quota counts what they will bring
	$first = $upload::receive( $appData, $tight, $big( 0, '', '' ), filesPart( $appData, $mp3 ), $adaBy, true );
	$direct = static fn( int $size ): array|string => $store::addFile( $appData, bin2hex( random_bytes( 6 ) ), [ 'folder' => $tight['id'], 'name' => 'Direct.txt', 'ext' => 'txt', 'size' => $size, 'mime' => 'text/plain', 'sha256' => '', 'by' => [ 'kind' => 'member', 'id' => 'b0b0b0b0b0b0' ], 'visible' => true, 'final' => false, 'created' => gmdate( $store::TIME ) ], 1048576 );
	$over = $direct( 300000 );
	$fits = $direct( 200000 );
	$store::dropParts( $appData );

	check( 'a file put in by the store for a member is held to the quota with the bytes of the unfinished uploads of the group: with 800000 held of 1 MB a file of 300000 is "quota" and one of 200000 is taken',
		isset( $first['part'] ) === true && $over === 'quota' && is_array( $fits ) === true );

	// six processes, six accounts, begin an upload of 800000 bytes at the same moment, under a quota of 1 MB: one holds the room, five are refused
	$jobs = [];
	for( $n = 0; $n < 6; $n++ )
		$jobs[] = [ 'folder' => $tight, 'by' => [ 'kind' => 'member', 'id' => 'a'. $n. 'a'. $n. 'a'. $n. 'a'. $n. 'a'. $n. 'a'. $n ], 'post' => $big( 0, '', '' ), 'bytes' => $mp3 ];
	$race = filesRace( $appData, $root, $jobs );
	$taken = count( array_filter( $race, static fn( array $answer ): bool => isset( $answer['part'] ) ) );
	$refused = count( array_filter( $race, static fn( array $answer ): bool => ( $answer['fail'][2] ?? '' ) === 'files_quota' ) );
	$reserved = glob( \Nino\Filesystem::path( $appData, $store::PARTS ). '/*.owner' ) ?: [];
	$store::dropParts( $appData );

	check( 'six uploads of six accounts begun at the same moment in six processes under a quota that holds one of them: exactly one is taken and five are 400 files_quota - and one reservation is left',
		$taken === 1 && $refused === 5 && count( $reserved ) === 1 );

	$store::deleteFolder( $appData, $tight['id'] );
	$store::deleteFolder( $appData, $loose['id'] );

	// six copies of the same chunk at the same moment: exactly one is the next chunk, the part is as long as two chunks and not longer
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 0, 'maxSize' => 5 ] );
	$chunk = "\xFF\xFB\x90\x00". str_repeat( 'a', 399996 );
	$whole = static fn( int $index, string $up ): array => [ 'name' => 'Race.mp3', 'size' => '1200000', 'upload' => $up, 'index' => (string) $index, 'count' => '3' ];
	$first = $upload::receive( $appData, $room, $whole( 0, '' ), filesPart( $appData, $chunk ), $adaBy, true );
	$jobs = [];
	for( $n = 0; $n < 6; $n++ )
		$jobs[] = [ 'folder' => $room, 'by' => $adaBy, 'post' => $whole( 1, (string) ( $first['part']['upload'] ?? '' ) ), 'bytes' => $chunk ];
	$race = filesRace( $appData, $root, $jobs );
	$taken = count( array_filter( $race, static fn( array $answer ): bool => isset( $answer['part'] ) ) );
	$length = filesSize( \Nino\Filesystem::path( $appData, $store::PARTS ). '/'. ( $first['part']['upload'] ?? 'x' ). '.part' );
	$store::dropParts( $appData );
	\Nino\Features::saveSettings( $appData, 'files', [ 'quota' => 0, 'maxSize' => 5 ] );

	check( 'six copies of the same chunk sent at the same moment by six processes: exactly one is taken, the others are "not the next of the upload", and the part is two chunks long',
		$taken === 1 && count( array_filter( $race, static fn( array $answer ): bool => ( $answer['fail'][1] ?? '' ) === 'the chunk is not the next of the upload' ) ) === 5 && $length === 2 * 400000 );
	// the order under the lock, on its own: a part that is not as long as the chunk expects is not appended to - the check that two chunks arriving together are held to
	$append = new ReflectionMethod( $upload, '_append' );
	$partFile = ninoSandboxDir( $appData ). '/append.part';
	$chunkFile = ninoSandboxDir( $appData ). '/append.chunk';
	file_put_contents( $partFile, 'abcde' );
	file_put_contents( $chunkFile, 'fghij' );
	$wrong = $append->invoke( null, $partFile, $chunkFile, 3, 5, false );
	$right = $append->invoke( null, $partFile, $chunkFile, 5, 5, false );
	$content = (string) file_get_contents( $partFile );
	array_map( 'unlink', [ $partFile, $chunkFile ] );

	check( 'a chunk is appended only to a part that is as long as it has to be: the wrong length is "order" and the part is as it was, the right one is appended once',
		$wrong === 'order' && $right === '' && $content === 'abcdefghij' );
	$store::deleteFolder( $appData, $bobsFolder['id'] );
	$store::deleteFolder( $appData, $sideFolder['id'] );

	// --- a member who is switched off is a stranger: the same answer as one who was never here
	$vera = \Nino\Modules\Members::add( $appData, 'vera@example.org', 'Vera', 'staff' );
	$known = filesMake( $appData, $room['id'], 'Staff.mp3', 'STAFF-BYTES' );
	filesSignIn( $appData, \Nino\Modules\Members::account( $appData, $vera['id'] ) );
	$veraSession = $_SESSION;
	$open = filesServe( $appData, '/.files/'. $known['id'] )['/nino/http/response'];
	\Nino\Modules\Members::change( $appData, $vera['id'], [ 'active' => false ] );
	$_SESSION = $veraSession;
	unset( $appData['./nino/auth/current'] );
	$switched = filesServe( $appData, '/.files/'. $known['id'] )['/nino/http/response'];
	filesVisitor();
	$stranger = filesServe( $appData, '/.files/'. $known['id'] )['/nino/http/response'];

	check( 'a member in the group sees the file; the same member switched off gets what a visitor gets - the 404 with the plain body, no file, no disposition',
		$open['statusCode'] === 200 && $switched['statusCode'] === 404 && $switched['body'] === $stranger['body'] && $switched['header'] === $stranger['header'] && array_key_exists( 'file', $switched ) === false );
	$store::deleteFile( $appData, $known['id'] );
	\Nino\Modules\Members::remove( $appData, $vera['id'] );

	// --- ids that are an attack on the route: all the same 404
	filesAs( 'ada' );
	$attacks = [ 'a null byte' => '/.files/aaaaaaaaaaa%00', 'a null byte before the end' => '/.files/aaaa%00aaaaaaa', 'overlong' => '/.files/'. str_repeat( 'a', 300 ), 'upper case' => '/.files/AAAAAAAAAAAA', 'a dot segment' => '/.files/..', 'two dots and a slash' => '/.files/../data/files.php', 'encoded slash' => '/.files/..%2fdata%2ffiles.php',
		'a backslash' => '/.files/..%5cdata', 'an extension' => '/.files/aaaaaaaaaaaa.php' ];
	$plain = array_map( static fn( string $uri ): array => filesAnswer( filesServe( $GLOBALS['appData'], $uri ) ), $attacks );

	check( 'an id with a null byte, one that is overlong, in capitals, a dot segment, a slash or a backslash in any encoding, with an extension, or none are the 404 of a stranger - never a file, never an error',
		array_filter( $plain, static fn( array $answer ): bool => $answer[0] === 404 && $answer[3] === 'Not Found' ) === $plain && count( $plain ) === 9 );

	// --- the names of a file that are an attack: refused, or cleaned - and nothing but what the id and the type name is on the disk
	filesWorkbench( 'admin' );
	$refusals = [];

	foreach( [ 'a.html', 'a.svg', 'a.php', '.htaccess', 'a.js', 'a.exe', 'a.php.', 'a.PHP', "a.php\0.mp3", 'a.mp3.php', 'a.mp3.html', 'a.mp3/../../x.php', 'CON.' ] as $name )
		$refusals[$name] = filesUpload( $appData, 'filesOwnerSend', $name, 'GENERAL BYTES', [ 'folder' => $room['id'] ] )[0][1]['code'] ?? '';

	check( 'a name that ends in html, svg, php, js, exe, in .htaccess, in a dot, in php after an mp3 or in a null byte is refused - files_type or files_filename - and no file, no part and no byte is left',
		array_filter( $refusals, static fn( string $code ): bool => in_array( $code, [ 'files_type', 'files_filename' ], true ) === false ) === [] && filesParts( $appData ) === [] );

	filesWorkbench( 'admin' );
	$double = filesUpload( $appData, 'filesOwnerSend', "x.php.mp3", "\xFF\xFB\x90\x00". str_repeat( "\x00", 400 ), [ 'folder' => $room['id'] ] );
	$doubleEntry = \Nino\Modules\Files::file( $appData, (string) ( $double[0][1]['id'] ?? '' ) );
	$doublePath = (string) \Nino\Modules\Files::path( $appData, (string) ( $doubleEntry['id'] ?? '' ) );
	filesAs( 'ada' );
	$served = filesServe( $appData, '/.files/'. ( $doubleEntry['id'] ?? 'x' ) )['/nino/http/response'];

	check( 'a double extension whose last extension and whose bytes are an mp3 is taken as an mp3: on the disk it is <id>.mp3, the name keeps its dots as text, it is served as audio/mpeg with nosniff, and its disposition names the cleaned name',
		$double[0][0] === 200 && ( $doubleEntry['ext'] ?? '' ) === 'mp3' && str_ends_with( $doublePath, '/'. $doubleEntry['id']. '.mp3' ) && basename( $doublePath ) === $doubleEntry['id']. '.mp3' && $served['header']['X-Content-Type-Options'] === 'nosniff'
		&& $served['header']['Content-Type'] === 'audio/mpeg' && str_contains( $served['header']['Content-Disposition'], 'filename="x.php.mp3"' ) );
	$store::deleteFile( $appData, (string) ( $doubleEntry['id'] ?? '' ) );

	// --- no type of the default is one that runs, and no MIME type of the table is shown inline unless it is a picture, a sound, a film or a pdf
	check( 'inline shows images (not svg), audio, video and pdf; every other type in the table - documents, text, archives - is a download, and an svg, html, xml, javascript or octet-stream is never inline',
		array_filter( array_unique( array_merge( ...array_values( $store::MIME ) ) ), static fn( string $mime ): bool => $store::inline( $mime ) === ( preg_match( '#^(?:audio|video|image)/#', $mime ) === 1 || $mime === 'application/pdf' ) ) === array_unique( array_merge( ...array_values( $store::MIME ) ) )
		&& array_filter( [ 'image/svg+xml', 'text/html', 'application/xhtml+xml', 'text/xml', 'application/xml', 'text/javascript', 'application/javascript', 'application/octet-stream', 'application/x-httpd-php', 'text/plain', 'text/csv', 'application/zip' ], static fn( string $mime ): bool => $store::inline( $mime ) === true ) === [] );

	// --- every type of the default is held to its bytes: a real sample is taken, the bytes of another type or a script are refused
	$samples = filesSamples();
	$defaults = $feature['settings']['types']['default'];
	$wrong = [];
	$sniffed = [];
	$text = [ 'txt', 'md', 'csv' ];

	foreach( $defaults as $ext ) {

		$path = \Nino\Filesystem::path( $appData, '/data/files' ). '/sniff-test.'. $ext;

		foreach( [ 'sample' => $samples[$ext] ?? '', 'script' => '<?php echo 1;', 'png' => $samples['png'], 'zip' => $samples['zip'], 'text' => "plain\n" ] as $kind => $content ) {
			file_put_contents( $path, $content );
			$sniffed[$ext][$kind] = $store::sniff( $path, $ext ) !== '';
		}

		unlink( $path );

		$expected = [ 'sample' => true, 'script' => false, 'png' => in_array( $ext, [ 'png' ], true ), 'zip' => in_array( $ext, [ 'zip' ], true ), 'text' => in_array( $ext, $text, true ) ];

		if( $sniffed[$ext] !== $expected )
			$wrong[$ext] = $sniffed[$ext];
	}

	check( 'the default types are '. count( $defaults ). ' and every one is held to its bytes by finfo: a real sample is taken; a script, a picture of another type and a zip are refused (text is what a txt, md and csv are), a picture is never a pdf nor a pdf a sound',
		count( $defaults ) === 23 && array_diff( $defaults, array_keys( $samples ) ) === [] && $wrong === [] );

	// --- the methods of the owner check the right and their inputs themselves, whatever the panel did before them
	filesWorkbench( '' );
	$folder = $store::addFolder( $appData, 'staff', 'Direct' );
	$entry = filesMake( $appData, $folder['id'], 'Direct.mp3', 'DIRECT-BYTES' );
	$index = file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) );
	$bytes = (string) file_get_contents( (string) \Nino\Modules\Files::path( $appData, $entry['id'] ) );
	$signedOut = [ 'overview' => \Nino\Modules\Files::overview( $appData ), 'createFolder' => \Nino\Modules\Files::createFolder( $appData, 'staff', 'x' ), 'renameFolder' => \Nino\Modules\Files::renameFolder( $appData, $folder['id'], 'y' ), 'markFolder' => \Nino\Modules\Files::markFolder( $appData, $folder['id'] ),
		'deleteFolder' => \Nino\Modules\Files::deleteFolder( $appData, $folder['id'] ), 'upload' => \Nino\Modules\Files::upload( $appData, $folder['id'], [ 'name' => 'a.mp3', 'size' => 1, 'upload' => '', 'index' => 0, 'count' => 1 ], null ), 'change' => \Nino\Modules\Files::change( $appData, $entry['id'], [ 'visible' => false ] ),
		'notify' => \Nino\Modules\Files::notify( $appData, [ $entry['id'] ] ), 'deleteFile' => \Nino\Modules\Files::deleteFile( $appData, $entry['id'] ), 'removeFile' => \Nino\Modules\Files::removeFile( $appData, $entry['id'] ) ];
	filesWorkbench( 'plain' );
	$plainOnes = [ 'overview' => \Nino\Modules\Files::overview( $appData ), 'createFolder' => \Nino\Modules\Files::createFolder( $appData, 'staff', 'x' ), 'deleteFolder' => \Nino\Modules\Files::deleteFolder( $appData, $folder['id'] ), 'change' => \Nino\Modules\Files::change( $appData, $entry['id'], [ 'visible' => false ] ),
		'deleteFile' => \Nino\Modules\Files::deleteFile( $appData, $entry['id'] ), 'removeFile' => \Nino\Modules\Files::removeFile( $appData, $entry['id'] ) ];

	check( 'each method of the owner answers denied to a visitor and to an account without the permission of the panel, and nothing is written: the index is the same bytes and the bytes of the file are there',
		array_values( array_unique( $signedOut ) ) === [ 'denied' ] && array_values( array_unique( $plainOnes ) ) === [ 'denied' ] && file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) ) === $index && is_file( (string) \Nino\Modules\Files::path( $appData, $entry['id'] ) ) === true );

	filesWorkbench( 'admin' );
	$inputs = [ 'a group nobody has' => \Nino\Modules\Files::createFolder( $appData, 'nobody', 'x' ), 'an empty name' => \Nino\Modules\Files::createFolder( $appData, 'staff', "  \x07 " ), 'a name that is too long' => \Nino\Modules\Files::createFolder( $appData, 'staff', str_repeat( 'x', 500 ) ),
		'an id that is a path' => \Nino\Modules\Files::renameFolder( $appData, '../../x', 'y' ), 'a file id that is a path' => \Nino\Modules\Files::deleteFile( $appData, '../data/files' ), 'an id nobody has' => \Nino\Modules\Files::removeFile( $appData, 'ffffffffffff' ),
		'a name that is no file name' => \Nino\Modules\Files::change( $appData, $entry['id'], [ 'name' => '../../x.php' ] ), 'a changed extension' => \Nino\Modules\Files::change( $appData, $entry['id'], [ 'name' => 'Direct.wav' ] ), 'a folder of another group' => \Nino\Modules\Files::change( $appData, $entry['id'], [ 'folder' => 'ffffffffffff' ] ) ];

	check( 'with the permission each method still holds its inputs to their rules: an unknown group, an empty and a too long name, an id that is a path or that nobody has, a name that is none, another extension and a folder that is not there are refused with a code and change nothing',
		array_filter( $inputs, static fn( mixed $answer ): bool => is_string( $answer ) === false || $answer === 'denied' ) === [] && file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) ) === $index && $bytes === (string) file_get_contents( (string) \Nino\Modules\Files::path( $appData, $entry['id'] ) ) );
	$store::deleteFolder( $appData, $folder['id'] );

	// --- the actions of the panel without the token of the page: the guard of the kernel answers 403 whoever is signed in, and nothing is written
	filesWorkbench( 'admin' );
	$appData['/nino/http/routes']['POST://_admin'] = [ 'uri' => '/_admin' ];
	\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://_admin', [ \Nino\Admin\Admin::class, 'handlePost' ] );
	$post = static function( string $action, array $data, bool $token ) use ( &$appData ): array {
		$answer = filesServe( $appData, '/_admin', 'POST', [ 'action' => $action, 'data' => json_encode( $data ) ], $token );

		$body = $answer['/nino/http/response']['body'] ?? '';

		return [ $answer['/nino/http/response']['statusCode'], is_array( $body ) === true ? (string) json_encode( $body ) : (string) $body ];
	};
	$index = file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) );
	$tokenless = [];

	foreach( [ 'files/list' => [], 'files/folder/add' => [ 'group' => 'staff', 'name' => 'Csrf' ], 'files/folder/save' => [ 'id' => $room['id'], 'name' => 'Csrf' ], 'files/folder/delete' => [ 'id' => $room['id'] ], 'files/folder/upload-target' => [ 'id' => $room['id'], 'on' => false ],
		'files/upload' => [ 'folder' => $room['id'], 'name' => 'a.mp3', 'size' => 1, 'upload' => '', 'index' => 0, 'count' => 1 ], 'files/save' => [ 'id' => 'ffffffffffff', 'visible' => false ], 'files/notify' => [ 'ids' => [ 'ffffffffffff' ] ], 'files/delete' => [ 'id' => 'ffffffffffff' ], 'files/remove' => [ 'id' => 'ffffffffffff' ] ] as $action => $data )
		$tokenless[$action] = $post( $action, $data, false )[0];

	check( 'every one of the ten actions of the panel, posted without the token of the page by an account that has the permission, is the 403 of the guard - and the index is the same bytes',
		array_values( array_unique( $tokenless ) ) === [ 403 ] && count( $tokenless ) === 10 && file_get_contents( \Nino\Filesystem::path( $appData, $store::INDEX ) ) === $index );
	check( '...and with the token the same account is answered: the list is a 200 with the folders',
		$post( 'files/list', [], true )[0] === 200 && str_contains( $post( 'files/list', [], true )[1], '"folders"' ) === true );

	echo "\n";
}

/**
 *	One real sample of each type the feature takes by default: the smallest bytes finfo calls by the type
 *
 *	@return 	array										Extension => bytes
 */
function filesSamples(): array {

	$zip = static function( array $entries, bool $stored = false ): string {
		$path = (string) tempnam( sys_get_temp_dir(), 'nino-z' );
		$archive = new ZipArchive();
		$archive->open( $path, ZipArchive::OVERWRITE | ZipArchive::CREATE );

		foreach( $entries as $name => $bytes ) {
			$archive->addFromString( $name, $bytes );

			if( $stored === true )
				$archive->setCompressionName( $name, ZipArchive::CM_STORE );
		}

		$archive->close();
		$out = (string) file_get_contents( $path );
		unlink( $path );

		return $out;
	};
	$types = static fn( string $part, string $kind ): string => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="'. $part. '" ContentType="'. $kind. '"/></Types>';
	$open = static fn( string $kind ): array => [ 'mimetype' => 'application/vnd.oasis.opendocument.'. $kind, 'content.xml' => '<office:document-content/>' ];

	$samples = [
		'jpg'		=> "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9",
		'png'		=> "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x00IEND\xaeB`\x82",
		'gif'		=> "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;",
		'webp'	=> "RIFF\x1a\x00\x00\x00WEBPVP8 \x0e\x00\x00\x00\x30\x01\x00\x9d\x01\x2a\x01\x00\x01\x00\x01\x40\x25\xa4\x00\x03\x70\x00\xfe",
		'pdf'		=> "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n",
		'docx'	=> $zip( [ '[Content_Types].xml' => $types( '/word/document.xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml' ), '_rels/.rels' => '<Relationships/>', 'word/document.xml' => '<w:document/>' ] ),
		'xlsx'	=> $zip( [ '[Content_Types].xml' => $types( '/xl/workbook.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml' ), '_rels/.rels' => '<Relationships/>', 'xl/workbook.xml' => '<workbook/>' ] ),
		'pptx'	=> $zip( [ '[Content_Types].xml' => $types( '/ppt/presentation.xml', 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml' ), '_rels/.rels' => '<Relationships/>', 'ppt/presentation.xml' => '<p:presentation/>' ] ),
		'odt'		=> $zip( $open( 'text' ), true ),
		'ods'		=> $zip( $open( 'spreadsheet' ), true ),
		'odp'		=> $zip( $open( 'presentation' ), true ),
		'txt'		=> "Notes\nof the day\n",
		'md'		=> "# Title\n\nSome text.\n",
		'csv'		=> "a,b,c\n1,2,3\n4,5,6\n",
		'zip'		=> $zip( [ 'a.txt' => 'hello' ] ),
		'mp3'		=> "\xFF\xFB\x90\x00". str_repeat( "\x00", 400 ),
		'wav'		=> "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x40\x1f\x00\x00\x80\x3e\x00\x00\x02\x00\x10\x00data\x00\x00\x00\x00",
		'm4a'		=> "\x00\x00\x00\x20ftypM4A \x00\x00\x00\x00M4A mp42isom\x00\x00\x00\x00",
		'ogg'		=> "OggS\x00\x02". str_repeat( "\x00", 8 ). "\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\x1e\x01vorbis\x00\x00\x00\x00\x01\x44\xac\x00\x00",
		'mp4'		=> "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom",
		'webm'	=> "\x1a\x45\xdf\xa3\x9f\x42\x86\x81\x01\x42\xf7\x81\x01\x42\xf2\x81\x04\x42\xf3\x81\x08\x42\x82\x84webm\x42\x87\x81\x02\x42\x85\x81\x02",
		'mov'		=> "\x00\x00\x00\x14ftypqt  \x00\x00\x00\x00qt  ",
	];
	$samples['jpeg'] = $samples['jpg'];

	return $samples;
}
