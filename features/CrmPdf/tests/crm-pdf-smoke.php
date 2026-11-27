<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-pdf-smoke.php		The test of the feature CrmPdf: the screens of the PDF feature in the CRM, held over real requests - the route, the session, the token, the permission and the endpoint of the CRM, down to the writers of the
 *											PDF feature - and over a package of the suite that answers the callbacks of the PDF feature as the documents of the CRM will (tests/fixtures/CrmPdfProbe).
 *
 *											A. the manifest, the activation (the CRM and the PDF feature are switched on with it, and the PDF feature cannot be switched off under it) and what the package enters: fifteen actions, its words, its
 *											   script and style sheet, the data of the page; and that it owns no data and writes nothing past the writers of the PDF feature
 *											B. the rights: every action, with no session, with a stale token, with the permission of the CRM alone, with the permission of the PDF panel alone, with both - and the method of an action reached
 *											   without the endpoint
 *											C. the actions over the library: the layout and its save, the pictures, the text blocks, the templates (save, duplicate, delete) - what they answer is what the feature's own views and writers give
 *											D. the use of a template: a package that uses it keeps it from being deleted, one that gives no answer does too, none that answers leaves it deletable, and a package that is gone is none that answers
 *											E. the preview: the bytes in the answer and nowhere else, the sample values of the vocabulary and nothing of a client, nothing written, never cached
 *											F. where the editor is (/pdf/editor), with the right, without it, for another surface, and without the package
 *											G. the PDF feature switched off by hand, and the CRM switched off: the package enters nothing and the endpoint says it does not know the action
 *											H. the words: both languages the same keys, and a word for every code the feature's writers refuse with
 *
 *											One pin for each behaviour, over the structure and never the words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/CrmPdf/tests/crm-pdf-smoke.php
 *	       NINO_ROOT=../nino php features/CrmPdf/tests/crm-pdf-smoke.php
 */

/*	The temporary folder of this process is one of its own from the first line: PHP keeps the folder it first answers for, so it is set before anything asks - the features, the sandboxes and every file the code under test
	could leave in the folder of a process are then in one place that this run owns and takes away, whatever the run ends in	*/
$tempRoot = rtrim( (string) ( getenv( 'TMPDIR' ) ?: '/tmp' ), '/' ). '/nino-crm-pdf-run-'. bin2hex( random_bytes( 6 ) );
mkdir( $tempRoot, 0755, true );
putenv( 'TMPDIR='. $tempRoot );
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$source = dirname( __DIR__ );
$catalogue = dirname( $source );

/**
 *	Copy a folder with what is in it, but for the folders named
 *
 *	@param		string		$from
 *	@param		string		$to
 *	@param		array 		$leave				Names of the folders at the top of $from that stay behind
 *
 *	@return 	void
 */
function cpCopy( string $from, string $to, array $leave = [] ): void {

	mkdir( $to, 0755, true );

	foreach( array_diff( (array) scandir( $from ), [ '.', '..' ] ) as $name ) {

		if( in_array( $name, $leave, true ) === true )
			continue;

		is_dir( $from. '/'. $name ) === true ? cpCopy( $from. '/'. $name, $to. '/'. $name ) : copy( $from. '/'. $name, $to. '/'. $name );
	}
}

/**
 *	Take a folder away with what is in it
 *
 *	@param		string		$folder
 *
 *	@return 	void
 */
function cpRemove( string $folder ): void {

	if( is_dir( $folder ) === false && is_link( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

/*	The features directory of the suite: a folder of its own, made for the run, with a copy of the CRM, of the PDF feature and of this package - and the package of the suite - and never the folder of the catalogue	*/
$features = $tempRoot. '/features';
mkdir( $features, 0755, true );
register_shutdown_function( static fn() => cpRemove( $GLOBALS['tempRoot'] ) );

foreach( [ 'Crm', 'Pdf', 'CrmPdf' ] as $folder )
	cpCopy( $catalogue. '/'. $folder, $features. '/'. $folder, [ 'tests' ] );

cpCopy( $source. '/tests/fixtures/CrmPdfProbe', $features. '/CrmPdfProbe' );

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-pdf-smoke.php names its own features directory: NINO_FEATURES_DIR is defined already\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

/**
 *	The app data of a request of a project that exists: the directory, the languages, the modules the project lists, and what the modules register in their init() - with the session and the cookies the caller names
 *
 *	@param		string		$sandbox							The directory of the project
 *	@param		array 		|null	$session			What the session holds: null for none (a visitor)
 *
 *	@return 	array
 */
function cpRequest( string $sandbox, ?array $session = null ): array {

	$_SESSION = $session ?? [];
	$_COOKIE = [];

	$appData = [ './nino/uid' => $sandbox ];
	\Nino\AppData::prepare( $appData );
	$appData['./nino/filesystem/path']				= $sandbox;
	$appData['./nino/filesystem/configpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/contentpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/publicpath']	= $sandbox. '/public';
	$appData['./nino/locales/current']				= $GLOBALS['cpLocale'] ?? 'de_DE';
	$appData['/nino/locales/native']					= 'de_DE';
	$appData['/nino/locales/available']				= [ 'de_DE', 'en_US' ];
	$appData['/nino/dir']											= '';
	$config																		= (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
	$appData['/nino/modules']									= (array) ( $config['/nino/modules'] ?? [] );
	$appData['/nino/auth/user']								= (array) ( $config['/nino/auth/user'] ?? [] );
	$appData['./nino/test/sandbox']						= $sandbox;
	$appData																	= ( $GLOBALS['cpSet'] ?? [] ) + $appData;

	\Nino\Html::init( $appData );
	\Nino\Csrf::init( $appData );
	\Nino\Auth::init( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

const CP_ACCOUNTS = [ 'admin@example.org' => [ 'correct horse battery staple', [ '/*' ] ], 'none@example.org' => [ 'none password', [] ], 'crm@example.org' => [ 'crm password', [ '/_admin/crm/manage' ] ],
	'pdf@example.org' => [ 'pdf password', [ '/_admin/pdf/manage' ] ], 'both@example.org' => [ 'both password', [ '/_admin/crm/manage', '/_admin/pdf/manage' ] ] ];

/**
 *	A project with the package switched on - and the package of the suite where the world says so - and the accounts of the suite
 *
 *	@param		string		$name
 *	@param		bool			$probe					Whether the package of the suite is on
 *
 *	@return 	array											[ the directory of the project, the sessions of the accounts by their address ]
 */
function cpWorld( string $name, bool $probe = false ): array {

	$project = ninoSandbox( $name );
	$GLOBALS['cpSandboxes'][] = ninoSandboxDir( $project );
	$activated = \Nino\Features::activate( $project, 'crm-pdf' );

	if( $activated !== true )
		throw new \RuntimeException( 'crm-pdf could not be activated: '. (string) $activated );

	if( $probe === true )
		\Nino\Features::activate( $project, 'crm-pdf-probe' );

	$where = ninoSandboxDir( $project );
	$a = cpRequest( $where );
	$sessions = [];

	foreach( CP_ACCOUNTS as $mail => [ $password, $perms ] )
		\Nino\Auth::insertUser( $a, $mail, $password, $perms );

	foreach( CP_ACCOUNTS as $mail => [ $password ] ) {
		$b = cpRequest( $where );
		\Nino\Auth::loginUser( $b, $mail, $password );
		$sessions[$mail] = $_SESSION;
	}

	ninoWarnings();

	return [ $where, $sessions ];
}

/**
 *	One post to the endpoint, the way the page makes it: the request of the site, the route, the guard of the token, the callbacks of the route
 *
 *	@param		string		$where
 *	@param		array 		|null	$session
 *	@param		string		$action
 *	@param		array 		$data
 *	@param		array 		$o						token (null: the token of the session; '' none; or a text), body (the body as it is)
 *
 *	@return 	array											[ status, body of the answer, the app data of the request, the request ]
 */
function cpPost( string $where, ?array $session, string $action, array $data = [], array $o = [] ): array {

	$a = cpRequest( $where, $session );
	$token = array_key_exists( 'token', $o ) === true ? (string) $o['token'] : \Nino\Csrf::getToken( $a );
	$server = [ 'REMOTE_ADDR' => '127.0.0.1', 'CONTENT_TYPE' => 'application/json' ];

	if( $token !== '' )
		$server['HTTP_X_CSRF_TOKEN'] = $token;

	$kept = $_SERVER;
	$_SERVER = $server + $_SERVER;
	$posted = $_POST;
	$_POST = [];

	try {
		$request = ninoVisit( $a, '/crm/api', 'POST', $server );
		$request['/nino/http/request']['body'] = array_key_exists( 'body', $o ) === true ? $o['body'] : json_encode( [ 'action' => $action, 'data' => $data ] );

		// The body is there when the callbacks of the request are asked, as it is in the front controller; the harness reads it in after them
		$request['/nino/http/request']['header']['Content-Length'] = (string) strlen( (string) $request['/nino/http/request']['body'] );
		\Nino\Modules\CrmPdf::callbackRequest( $a, $request );

		\Nino\Http::response( $a, $request );
	}
	finally {
		$_POST = $posted;
		$_SERVER = $kept;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $a, $request ];
}

/**
 *	One GET of the site, the way the server hands it over
 *
 *	@param		string		$where
 *	@param		string		$uri
 *	@param		array 		|null	$session
 *
 *	@return 	array											[ status, body, headers, the app data of the request, the request ]
 */
function cpGet( string $where, string $uri, ?array $session = null ): array {

	$a = cpRequest( $where, $session );
	$request = \Nino\request( $a, [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1' ] );

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request['/nino/http/response']['header'] ?? [], $a, $request ];
}

/**
 *	The JSON block of the page /crm, as the script reads it
 *
 *	@param		mixed			$body
 *
 *	@return 	array											[] where the page has none
 */
function cpData( mixed $body ): array {

	return preg_match( '~<script type="application/json" id="crm-data">(.*?)</script>~s', (string) $body, $found ) === 1 ? (array) json_decode( $found[1], true ) : [];
}

/**
 *	What lies in a folder: path => hash of the content
 *
 *	@param		string		$base
 *
 *	@return 	array
 */
function cpTree( string $base ): array {

	$files = [];

	if( is_dir( $base ) === false )
		return $files;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		$files[ substr( (string) $file, strlen( $base ) ) ] = hash( 'sha256', (string) file_get_contents( (string) $file ) );

	ksort( $files );

	return $files;
}

/**
 *	A PNG of a flat colour
 *
 *	@param		int				$width
 *	@param		int				$height
 *
 *	@return 	string
 */
function cpPicture( int $width = 40, int $height = 20 ): string {

	$image = imagecreatetruecolor( $width, $height );
	imagefill( $image, 0, 0, (int) imagecolorallocate( $image, 30, 90, 200 ) );
	ob_start();
	imagepng( $image );

	return (string) ob_get_clean();
}

/**
 *	The text a PDF shows, where pdftotext is there
 *
 *	@param		string		$pdf
 *
 *	@return 	string
 */
function cpWords( string $pdf ): string {

	$file = tempnam( sys_get_temp_dir(), 'cp' );
	file_put_contents( (string) $file, $pdf );
	$text = (string) shell_exec( 'pdftotext -layout '. escapeshellarg( (string) $file ). ' - 2>/dev/null' );
	unlink( (string) $file );

	return $text;
}

/**
 *	The lines of the activity log, the newest first, without their date and account
 *
 *	@param		string		$where
 *	@param		int				$limit
 *
 *	@return 	array
 */
function cpLog( string $where, int $limit = 3 ): array {

	$a = cpRequest( $where );

	$lines = array_map( static fn( string $line ): string => (string) preg_replace( '/^\S+ \S+  \S+  /', '', $line ), \Nino\Modules\Logs\Admin::recentLines( $a, 200 ) );

	return array_slice( array_values( array_filter( $lines, static fn( string $line ): bool => preg_match( '/\bPDF\b/', $line ) === 1 ) ), 0, $limit );
}

/**
 *	A read of the PDF feature in the project, the way a package asks it: through the app data of a request of its own
 *
 *	@param		string		$where
 *	@param		string		$what					layout, blocks, template, picture, tags, or view-layout, view-blocks, view-templates
 *	@param		string		...$arguments
 *
 *	@return 	mixed
 */
function cpRead( string $where, string $what, string ...$arguments ): mixed {

	$a = cpRequest( $where );

	return match( $what ) {
		'layout'					=> \Nino\Modules\Pdf::layout( $a ),
		'blocks'					=> \Nino\Modules\Pdf::blocks( $a ),
		'template'				=> \Nino\Modules\Pdf::template( $a, ...$arguments ),
		'picture'					=> \Nino\Modules\Pdf::picture( $a, ...$arguments ),
		'view-layout'			=> \Nino\Modules\Pdf\View::layout( $a ),
		'view-blocks'			=> \Nino\Modules\Pdf\View::blocks( $a ),
		'view-templates'	=> \Nino\Modules\Pdf\View::templates( $a ),
		'data'						=> \Nino\Filesystem::path( $a, '/data/pdf'. ( $arguments[0] ?? '' ) ),
		'sandbox'					=> ninoSandboxDir( $a ),
	};
}

$ACTIONS = [ 'context', 'layout-get', 'layout-save', 'layout-image', 'layout-image-delete', 'layout-preview', 'blocks-list', 'block-save', 'block-delete', 'templates-list', 'template-get', 'template-save', 'template-duplicate', 'template-delete', 'preview' ];

// --- A. the manifest, the activation, what the package enters -------------------------------------------------------

$manifest = \Nino\Features::manifest( $source );
check( 'the manifest validates without a warning', is_array( $manifest ) && ninoWarnings() === [] );
check( '...it is a business feature of the key crm-pdf that requires the CRM and the PDF feature, owns no data and keeps no setting, and is written for Nino 2', $manifest['key'] === 'crm-pdf' && $manifest['category'] === 'business' && $manifest['requires'] === [ 'crm', 'pdf' ]
	&& $manifest['data'] === [] && $manifest['settings'] === [] && $manifest['nino'] === '^2.0' && $manifest['version'] === '2.0.0' && isset( $manifest['components'], $manifest['stacks'], $manifest['assets'] ) === false );

[ $where, $sessions ] = cpWorld( 'crm-pdf-a', true );
$a = cpRequest( $where );
$entered = \Nino\Modules\Crm\Api::actions( $a );
$mine = array_filter( $entered, static fn( array $entry ): bool => $entry['package'] === 'crm-pdf' );

check( 'switching the package on switches the CRM and the PDF feature on with it', \Nino\Modules\Crm::featureOn( $a, 'crm' ) === true && \Nino\Modules\Crm::featureOn( $a, 'pdf' ) === true && \Nino\Modules\Crm::featureOn( $a, 'crm-pdf' ) === true );
check( 'it enters fifteen actions and no other, each named crm-pdf/<name>, each answered by a class of its own folder, none taking a file and none needing a feature', array_keys( $mine ) === array_map( static fn( string $name ): string => 'crm-pdf/'. $name, $ACTIONS )
	&& count( array_filter( $mine, static fn( array $entry ): bool => str_starts_with( ltrim( (string) $entry['target'][0], '\\' ), 'Nino\\Modules\\CrmPdf\\' ) === true && $entry['files'] === false && $entry['needs'] === '' ) ) === 15 );
check( '...and the core has no action of its name', count( array_filter( array_keys( $entered ), static fn( string $action ): bool => str_starts_with( $action, 'crm-pdf/' ) === true ) ) === 15 && ( $entered['crm/status']['package'] ?? '' ) === 'crm' );

$page = cpGet( $where, '/crm', $sessions['both@example.org'] );
$data = cpData( $page[1] );
check( 'the page of an account with both rights carries the data of the package: it may use the screens', $page[0] === 200 && ( $data['packages']['crm-pdf'] ?? null ) === [ 'allowed' => true ] );
check( '...the page of an account with the right of the CRM alone says it may not', ( cpData( cpGet( $where, '/crm', $sessions['crm@example.org'] )[1] )['packages']['crm-pdf'] ?? null ) === [ 'allowed' => false ] );
check( '...the words of the package are among the words of the page, in both languages with the same keys, and so are the sentences of the codes of the PDF feature', ( $data['texts']['pdf.title'] ?? '' ) !== '' && ( $data['texts']['error.pdf_title'] ?? '' ) !== '' );

preg_match_all( '~/crm/assets/([0-9a-f]+-crm-pdf\.(?:js|css))~', (string) $page[1], $files );
$served = [];

foreach( $files[1] as $file ) {

	$got = cpGet( $where, '/crm/assets/'. $file );
	$served[$file] = [ $got[0], (string) ( $got[4]['/nino/http/response']['file'] ?? '' ) ];
}

check( 'the script and the style sheet of the package are linked by the hash of their content and are served as they are: the files of the folder of the package, to anybody', count( $served ) === 2 && count( array_filter( $served, static fn( array $one ): bool => $one[0] === 200 ) ) === 2
	&& array_column( $served, 1 ) === [ $features. '/CrmPdf/Surface/assets/crm-pdf.css', $features. '/CrmPdf/Surface/assets/crm-pdf.js' ] && count( array_filter( array_keys( $served ), static fn( string $name ): bool => str_starts_with( $name, substr( (string) hash_file( 'sha256', $GLOBALS['features']. '/CrmPdf/Surface/assets/'. substr( $name, 13 ) ), 0, 12 ) ) === true ) ) === 2 );

// Nothing but the PDF feature's own library: no table, no file of the package. The code of the package names no writer of the file system and no database
$code = '';

foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_ends_with( (string) $file, '.php' ) === true && str_contains( (string) $file, '/tests/' ) === false )
		$code .= preg_replace( '~/\*.*?\*/|//[^\n]*~s', '', (string) file_get_contents( (string) $file ) );

check( 'the package writes nothing of its own: its code names no writer of the file system, no database and no folder of data - what is stored goes through the writers of the PDF feature', preg_match( '~\b(?:file_put_contents|fopen|fwrite|unlink|rename|mkdir|rmdir|copy|touch|chmod|tempnam|tmpfile)\s*\(|\\\\Nino\\\\Filesystem|\bPDO\b|Db::|->exec\(|sqlite~i', $code ) === 0 );
check( '...and it names the writers of the PDF feature it stores through: the eight of the Store', count( array_filter( [ 'saveLayout', 'saveImage', 'deleteImage', 'saveBlock', 'deleteBlock', 'saveTemplate', 'duplicateTemplate', 'deleteTemplate' ], static fn( string $writer ): bool => str_contains( $code, 'Store::'. $writer. '(' ) === true ) ) === 8 );

$under = \Nino\Features::deactivate( $a, 'pdf' );
$crmUnder = \Nino\Features::deactivate( $a, 'crm' );
check( 'the PDF feature and the CRM cannot be switched off under the package: the kernel refuses and names it', is_string( $under ) === true && str_contains( $under, 'crm-pdf' ) === true && is_string( $crmUnder ) === true && str_contains( $crmUnder, 'crm-pdf' ) === true );

$alone = ninoSandbox( 'crm-pdf-a2' );
$GLOBALS['cpSandboxes'][] = ninoSandboxDir( $alone );
\Nino\Features::activate( $alone, 'crm-pdf' );
$list = array_column( array_filter( \Nino\Features::all( $alone ), static fn( array $feature ): bool => $feature['active'] === true ), 'key' );
check( 'a project that switches only the package on has the CRM and the PDF feature on in the same step', in_array( 'crm', $list, true ) === true && in_array( 'pdf', $list, true ) === true && in_array( 'crm-pdf', $list, true ) === true );

// --- B. the rights --------------------------------------------------------------------------------------------------

$before = cpTree( \Nino\Filesystem::path( $a, '/data/pdf' ) );
$denied = [];
$refused = [];

foreach( $ACTIONS as $action ) {

	$payload = $action === 'layout-image' ? [ 'kind' => 'logo', 'data' => base64_encode( cpPicture() ) ] : ( $action === 'block-save' ? [ 'key' => 'denied-block', 'isNew' => true, 'title' => 'Denied', 'html' => '<p>x</p>' ]
		: ( $action === 'template-save' ? [ 'key' => 'denied-template', 'isNew' => true, 'name' => 'Denied', 'tag' => '', 'blocks' => [] ] : ( $action === 'layout-save' ? [ 'format' => 'letter', 'margins' => [ 10, 10, 10, 10 ], 'font' => 'times', 'size' => 12, 'header' => 'H', 'footer' => 'F' ] : [ 'key' => 'nothing' ] ) ) );

	$denied[$action] = [
		'none' => cpPost( $where, null, 'crm-pdf/'. $action, $payload )[0],
		'token' => cpPost( $where, $sessions['both@example.org'], 'crm-pdf/'. $action, $payload, [ 'token' => 'stale' ] )[0],
		'bare' => cpPost( $where, $sessions['none@example.org'], 'crm-pdf/'. $action, $payload )[0],
		'crm' => cpPost( $where, $sessions['crm@example.org'], 'crm-pdf/'. $action, $payload )[0],
		'pdf' => cpPost( $where, $sessions['pdf@example.org'], 'crm-pdf/'. $action, $payload )[0],
	];
	$refused[$action] = cpPost( $where, $sessions['crm@example.org'], 'crm-pdf/'. $action, $payload )[1];
}

check( 'every action is refused with no session (401), with a stale token (403) and to an account with no right (403)', count( array_filter( $denied, static fn( array $row ): bool => $row['none'] === 401 && $row['token'] === 403 && $row['bare'] === 403 ) ) === 15 );
check( 'every action is refused (403) to an account that holds the permission of the CRM alone - the PDF panel\'s right is asked on top - and to one that holds the permission of the PDF panel alone', count( array_filter( $denied, static fn( array $row ): bool => $row['crm'] === 403 && $row['pdf'] === 403 ) ) === 15 );
check( '...a refusal reads and writes nothing: it carries no data of the library, and the data of the PDF feature are as they were', count( array_filter( $refused, static fn( mixed $body ): bool => is_array( $body ) === true && array_keys( $body ) === [ 'error' ] ) ) === 15
	&& cpTree( \Nino\Filesystem::path( $a, '/data/pdf' ) ) === $before );

$direct = [];

foreach( [ 'pdf@example.org' => [ '/_admin/pdf/manage' ], 'crm@example.org' => [ '/_admin/crm/manage' ] ] as $mail => $perms ) {

	$one = cpRequest( $where, $sessions[$mail] );
	$direct[$mail] = ninoCall( $one, [ \Nino\Modules\CrmPdf\Api::class, 'apiContext' ], [], 'crm-pdf/context' )[0];
	$direct[$mail. '/save'] = ninoCall( $one, [ \Nino\Modules\CrmPdf\Api::class, 'apiBlockSave' ], [ 'key' => 'direct-block', 'isNew' => true, 'title' => 'Direct', 'html' => '<p>x</p>' ], 'crm-pdf/block-save' )[0];
}

check( 'the method of an action reached without the endpoint asks both rights itself: the PDF panel\'s alone or the CRM\'s alone is a 403 and nothing is written', $direct === [ 'pdf@example.org' => 403, 'pdf@example.org/save' => 403, 'crm@example.org' => 403, 'crm@example.org/save' => 403 ]
	&& cpTree( \Nino\Filesystem::path( $a, '/data/pdf' ) ) === $before );
$one = cpRequest( $where, $sessions['both@example.org'] );
check( '...and an account with both rights is let through the same way', ninoCall( $one, [ \Nino\Modules\CrmPdf\Api::class, 'apiBlocksList' ], [], 'crm-pdf/blocks-list' )[0] === 200 );

// --- C. the actions over the library -------------------------------------------------------------------------------

$post = static function( string $action, array $data = [] ) use ( $where, $sessions ): array {
	return cpPost( $where, $sessions['both@example.org'], 'crm-pdf/'. $action, $data );
};

$fresh = cpRequest( $where );
$layout = $post( 'layout-get' );
check( 'layout-get answers what the PDF feature\'s own view of the layout gives', $layout[0] === 200 && $layout[1] === \Nino\Modules\Pdf\View::layout( $fresh ) && ( $layout[1]['layout']['format'] ?? '' ) === 'a4' && isset( $layout[1]['sheet'], $layout[1]['ranges'], $layout[1]['images'], $layout[1]['papers'], $layout[1]['fonts'] ) );

$saved = $post( 'layout-save', [ 'format' => 'letter', 'margins' => [ 12, 14, 16, 18 ], 'font' => 'times', 'size' => 11, 'header' => '<p>Head [[page]] of [[pages]]</p>', 'footer' => '<p>Foot</p>', 'design' => 'h1 { color: #112233 }' ] );
$again = cpRequest( $where );
check( 'layout-save writes the layout through the writer of the PDF feature and answers the view again: what a read of the feature gives is what was saved', $saved[0] === 200 && \Nino\Modules\Pdf::layout( $again )['format'] === 'letter' && \Nino\Modules\Pdf::layout( $again )['margins'] === [ 12, 14, 16, 18 ]
	&& \Nino\Modules\Pdf::layout( $again )['design'] === 'h1 { color: #112233 }' && $saved[1] === \Nino\Modules\Pdf\View::layout( $again ) );
check( '...a line of the activity log says what was done and names no text of the layout', cpLog( $where, 1 ) === [ 'Save the PDF layout' ] );

$bad = [ $post( 'layout-save', [ 'format' => 'a4', 'margins' => [ 1, 14, 16, 18 ], 'font' => 'times', 'size' => 11, 'header' => '', 'footer' => '' ] ), $post( 'layout-save', [ 'format' => 'a4', 'margins' => [ 12, 14, 16, 18 ], 'font' => 'times', 'size' => 11, 'header' => '', 'footer' => '', 'design' => 'h1 { frobnicate: 1 }' ] ),
	$post( 'layout-save', [ 'format' => 'a4', 'margins' => [ 12, 14, 16, 18 ], 'font' => 'nonesuch', 'size' => 11, 'header' => '', 'footer' => '' ] ) ];
check( 'a refusal of the writer is the answer as it is: the status, the code, the params and the field - a margin, a line of the design, a font', array_map( static fn( array $one ): array => [ $one[0], $one[1]['code'] ?? '', $one[1]['field'] ?? '' ], $bad )
	=== [ [ 400, 'pdf_margin', 'margins' ], [ 400, 'pdf_design_property', 'design' ], [ 400, 'pdf_font', 'font' ] ] && $bad[1][1]['params'][0] === 1 && cpRead( $where, 'layout' )['font'] === 'times' );

// The pictures: a base64 text in the data, the writers of the feature behind it
$logo = cpPicture( 40, 20 );
$put = $post( 'layout-image', [ 'kind' => 'logo', 'data' => base64_encode( $logo ) ] );
$fresh = cpRequest( $where );
check( 'layout-image takes a picture as base64 and puts it through the writer: the layout names it, the bytes are the ones that were sent, and the answer is the view with the picture as a data address', $put[0] === 200 && \Nino\Modules\Pdf::layout( $fresh )['logo'] === 'logo.png'
	&& \Nino\Modules\Pdf::picture( $fresh, 'logo' ) === $logo && str_starts_with( (string) ( $put[1]['images']['logo'] ?? '' ), 'data:image/png;base64,' ) === true );
$wrong = [ 'kind' => $post( 'layout-image', [ 'kind' => 'banner', 'data' => base64_encode( $logo ) ] ), 'text' => $post( 'layout-image', [ 'kind' => 'logo', 'data' => base64_encode( 'not a picture at all' ) ] ),
	'base64' => $post( 'layout-image', [ 'kind' => 'logo', 'data' => '***not base64***' ] ), 'empty' => $post( 'layout-image', [ 'kind' => 'logo' ] ), 'type' => $post( 'layout-image', [ 'kind' => 'logo', 'data' => [ 'x' ] ] ),
	'pixels' => $post( 'layout-image', [ 'kind' => 'logo', 'data' => base64_encode( cpPicture( 4001, 10 ) ) ] ) ];
check( 'a picture that is no picture, no base64, none or too wide is refused with the code of the reason - and the picture the layout has stays', array_map( static fn( array $one ): array => [ $one[0], $one[1]['code'] ?? '' ], $wrong )
	=== [ 'kind' => [ 400, 'pdf_image_kind' ], 'text' => [ 400, 'image_type' ], 'base64' => [ 400, 'upload_missing' ], 'empty' => [ 400, 'upload_missing' ], 'type' => [ 400, 'upload_missing' ], 'pixels' => [ 400, 'pdf_image_pixels' ] ]
	&& cpRead( $where, 'picture', 'logo' ) === $logo );
$started = microtime( true );
$large = $post( 'layout-image', [ 'kind' => 'logo', 'data' => str_repeat( 'A', 12 * 1048576 ) ] );
check( 'a picture of more than the screens take (4 MB) is refused with 413 before it is decoded, with the number of megabytes - and the picture stays', $large[0] === 413 && ( $large[1]['code'] ?? '' ) === 'image_too_large' && ( $large[1]['params'] ?? [] ) === [ 4 ] && cpRead( $where, 'picture', 'logo' ) === $logo );

// A request longer than a picture can be is turned away by its length, before the endpoint reads the body: the callback of the request puts a short body in its place
$oneRequest = static function( string $body, string $length, string $uri = '/crm/api', string $method = 'POST' ): string {
	$a = cpRequest( $GLOBALS['where'] );
	$request = [ '/nino/http/request' => [ 'method' => $method, 'uri' => $uri, 'header' => [ 'Content-Length' => $length ], 'body' => $body ] ];
	\Nino\Callbacks::doCallbacks( $a, '/nino/http/request', $request );
	return (string) $request['/nino/http/request']['body'];
};
$big = '{"action":"crm-pdf/layout-image","data":{"kind":"logo","data":"'. str_repeat( 'A', 12 * 1048576 ). '"}}';
$short = '{"action":"crm-pdf/layout-image","data":{"kind":"logo","data":"QUJD"}}';
$stub = $oneRequest( $big, (string) strlen( $big ) );
check( 'a request for a picture whose length says it is more than a picture of 4 MB is replaced, before anything reads it, by a body of its own that names the action and says it is oversized and holds nothing of the picture', json_decode( $stub, true ) === [ 'action' => 'crm-pdf/layout-image', 'data' => [ 'oversized' => true ] ]
	&& strlen( $stub ) < 100 );
check( '...a request that is not that is left as it is: a short one, one for another action, one to another address, a GET, and a long one whose action is not at its head', $oneRequest( $short, (string) strlen( $short ) ) === $short && $oneRequest( str_replace( 'layout-image', 'layout-save', $big ), (string) strlen( $big ) ) === str_replace( 'layout-image', 'layout-save', $big )
	&& $oneRequest( $big, (string) strlen( $big ), '/crm/other' ) === $big && $oneRequest( $big, (string) strlen( $big ), '/crm/api', 'GET' ) === $big && $oneRequest( $big, '12' ) === $big && $oneRequest( $big, 'x' ) === $big
	&& $oneRequest( '{"data":{"kind":"logo"},"action":"crm-pdf/layout-save","action":"crm-pdf/layout-image"}', '12582912' ) === $stub );
$fat = str_repeat( 'A', 12 * 1048576 );
$length = (string) ( 12 * 1048576 + 200 );
$forms = [
	'blanks' => "  \n\t{ \"action\" \n :\t \"crm-pdf/layout-image\" , \"data\":{\"kind\":\"logo\",\"data\":\"". $fat. '"}}',
	'many blanks' => str_repeat( ' ', 1500 ). '{"action":"crm-pdf/layout-image","data":{"data":"'. $fat. '"}}',
	'escaped slash' => '{"action":"crm-pdf\\/layout-image","data":{"data":"'. $fat. '"}}',
	'not first' => '{"data":{"kind":"logo","braces":"}{ ]"},"id":7,"on":true,"action":"crm-pdf/layout-image","more":"'. $fat. '"}',
	'a number first' => '{"n":12,"list":[1,{"a":"}"}],"action":"crm-pdf/layout-image","data":"'. $fat. '"}',
	'unicode escape' => '{"action":"crm-pdf/layout\\u002dimage","data":"'. $fat. '"}',
];
$replaced = array_map( static fn( string $body ): bool => json_decode( $oneRequest( $body, $length ), true ) === [ 'action' => 'crm-pdf/layout-image', 'data' => [ 'oversized' => true ] ], $forms );
check( 'a request for a picture is found by its action in whatever way the json is written - blanks around the keys, a slash written \\/ or a letter as \\u002d, the action behind other keys, lists and texts with braces in them - '. count( $forms ). ' forms', array_filter( $replaced, static fn( bool $one ): bool => $one === false ) === [] );
$foreign = [
	'another action' => '{"action":"crm-pdf/layout-save","data":{"header":"'. $fat. '"}}',
	'another package' => '{"action":"crm-documents/save","data":{"x":"'. $fat. '"}}',
	'the name inside a value' => '{"action":"crm-pdf/layout-save","data":{"action":"crm-pdf/layout-image","x":"'. $fat. '"}}',
	'the name in a text' => '{"note":"{\\"action\\":\\"crm-pdf/layout-image\\"}","action":"crm-pdf/layout-save","data":"'. $fat. '"}',
	'the name in a list' => '{"list":["x",{"action":"crm-pdf/layout-image"}],"action":"crm-pdf/block-save","data":"'. $fat. '"}',
	'the name as a key without a text' => '{"action":["crm-pdf/layout-image"],"data":"'. $fat. '"}',
	'a longer name' => '{"action":"crm-pdf/layout-image-delete","data":"'. $fat. '"}',
	'the name behind the head' => '{"data":"'. str_repeat( 'B', 8192 ). '","action":"crm-pdf/layout-image","more":"'. $fat. '"}',
	'no object' => '["crm-pdf/layout-image","'. $fat. '"]',
	'no json' => 'action=crm-pdf/layout-image&data='. $fat,
];
$untouched = array_map( static fn( string $body ): bool => $oneRequest( $body, $length ) === $body, $foreign );
check( 'a long request that is not for a picture is left as it is - another action, another package, the name inside a value or a text or a list, a longer name, one that is no object or no json - and so is one that names the action only behind more than 4 KB of other keys ('. count( $foreign ). ' forms)', array_filter( $untouched, static fn( bool $one ): bool => $one === false ) === [] );
$twice = static fn( string $first, string $second ): string => $oneRequest( '{"action":"'. $first. '","k":1,"action":"'. $second. '","data":"'. $GLOBALS['fat']. '"}', $GLOBALS['length'] );
check( 'the last of two keys named action decides, as in json: a picture named first and another action after it is left alone, the other way round it is turned away', $twice( 'crm-pdf/layout-image', 'crm-pdf/layout-save' ) === '{"action":"crm-pdf/layout-image","k":1,"action":"crm-pdf/layout-save","data":"'. $fat. '"}'
	&& json_decode( $twice( 'crm-pdf/layout-save', 'crm-pdf/layout-image' ), true )['data'] === [ 'oversized' => true ] );
check( 'headAction() reads nothing from a head that is cut off: a text that is not closed, a value that is not whole, an empty head', \Nino\Modules\CrmPdf::headAction( '{"action":"crm-pdf/layout-ima' ) === '' && \Nino\Modules\CrmPdf::headAction( '{"action":"crm-pdf/layout-image","data":{"x":"' ) === 'crm-pdf/layout-image' && \Nino\Modules\CrmPdf::headAction( '' ) === '' );
$sent = cpPost( $where, $sessions['both@example.org'], 'x', [], [ 'body' => $stub ] );
$bare = cpPost( $where, $sessions['crm@example.org'], 'x', [], [ 'body' => $stub ] );
$none = cpPost( $where, null, 'x', [], [ 'body' => $stub ] );
check( 'the short body goes the way of any request: an account with both rights is answered 413 with the sentence of the code and the number, one without the PDF right 403 and one without a session 401 - the length opens no door', [ $sent[0], $sent[1]['code'] ?? '', $sent[1]['params'] ?? [] ] === [ 413, 'image_too_large', [ 4 ] ] && $bare[0] === 403 && $none[0] === 401 && cpRead( $where, 'picture', 'logo' ) === $logo );
// What a picture of the size costs: the peak of memory over the base, for the largest picture the screens take and for a request that is turned away by its length
$noise = imagecreatetruecolor( 1100, 1100 );
mt_srand( 7 );
for( $y = 0; $y < 1100; $y++ )
	for( $x = 0; $x < 1100; $x += 1 )
		imagesetpixel( $noise, $x, $y, mt_rand( 0, 0xFFFFFF ) );
ob_start();
imagepng( $noise, null, 1 );
$heavy = (string) ob_get_clean();
memory_reset_peak_usage();
$floor = memory_get_usage();
$heavyPut = $post( 'layout-image', [ 'kind' => 'logo', 'data' => base64_encode( $heavy ) ] );
$peakHeavy = memory_get_peak_usage() - $floor;
memory_reset_peak_usage();
$floor = memory_get_usage();
cpPost( $where, $sessions['both@example.org'], 'x', [], [ 'body' => $stub ] );
$peakStub = memory_get_peak_usage() - $floor;
check( 'a picture of just under 4 MB ('. strlen( $heavy ). ' bytes) is taken, and takes at most 64 MB over the base at its peak ('. round( $peakHeavy / 1048576 ). ' MB measured); the request that is turned away by its length takes a few MB ('. round( $peakStub / 1048576, 1 ). ' MB measured)', strlen( $heavy ) > 3500000 && strlen( $heavy ) <= 4194304
	&& $heavyPut[0] === 200 && $peakHeavy < 64 * 1048576 && $peakStub < 8 * 1048576 );
$post( 'layout-image', [ 'kind' => 'logo', 'data' => base64_encode( $logo ) ] );
$removed = $post( 'layout-image-delete', [ 'kind' => 'logo' ] );
check( 'layout-image-delete takes the picture away through the writer of the feature', $removed[0] === 200 && cpRead( $where, 'layout' )['logo'] === '' && ( $removed[1]['images']['logo'] ?? 'x' ) === ''
	&& $post( 'layout-image-delete', [ 'kind' => 'banner' ] )[0] === 400 );

// The text blocks
$blockSaved = $post( 'block-save', [ 'key' => 'greeting', 'isNew' => true, 'title' => 'Greeting', 'html' => '<p>Hello <b>[[/customer/name]]</b><script>alert(1)</script></p>' ] );
check( 'block-save writes a text block through the writer: it is in the library as the writer keeps it - the HTML held to the list of the feature - and the answer is the list of the blocks', $blockSaved[0] === 200 && isset( cpRead( $where, 'blocks' )['greeting'] )
	&& str_contains( cpRead( $where, 'blocks' )['greeting']['html'], '<script' ) === false && $blockSaved[1] === cpRead( $where, 'view-blocks' ) );
check( '...the same key as new again is refused, a key that is no slug is refused, a title that is empty is refused', [ $post( 'block-save', [ 'key' => 'greeting', 'isNew' => true, 'title' => 'Again', 'html' => '<p>x</p>' ] )[1]['code'] ?? '', $post( 'block-save', [ 'key' => '../x', 'isNew' => true, 'title' => 'Bad', 'html' => '<p>x</p>' ] )[1]['code'] ?? '',
	$post( 'block-save', [ 'key' => 'hollow', 'isNew' => true, 'title' => '', 'html' => '<p>x</p>' ] )[1]['code'] ?? '' ] === [ 'pdf_key_taken', 'pdf_key', 'pdf_title' ] );
$post( 'block-save', [ 'key' => 'logged-block', 'isNew' => true, 'title' => 'Logged', 'html' => '<p>x</p>' ] );
check( '...a line of the activity log names the key of a block that is a slug, and none that is not - a refusal makes no line', cpLog( $where, 1 ) === [ 'Save PDF block "logged-block"' ] && $post( 'block-save', [ 'key' => "../x\n", 'isNew' => true, 'title' => 'Bad', 'html' => '<p>x</p>' ] )[0] === 400 && cpLog( $where, 1 ) === [ 'Save PDF block "logged-block"' ] );

// The templates
$body = [ [ 'type' => 'html', 'block' => 'greeting' ], [ 'type' => 'html', 'html' => '<p>Own text</p>' ], [ 'type' => 'slot', 'name' => 'lines' ], [ 'type' => 'input', 'name' => 'note', 'label' => 'Note', 'multiline' => false, 'required' => false ], [ 'type' => 'pagebreak' ],
	[ 'type' => 'signature', 'name' => 'sign', 'party' => 'owner', 'label' => 'Signed', 'page' => 'last', 'x' => 20, 'y' => 200, 'w' => 70, 'h' => 20, 'line' => true ] ];
$tSaved = $post( 'template-save', [ 'key' => 'offer-one', 'isNew' => true, 'name' => 'Offer one', 'tag' => '', 'blocks' => $body ] );
$stored = cpRead( $where, 'template', 'offer-one' );
check( 'template-save writes a template through the writer: it is read back with its blocks, the answer names the key and is the list with what the editor offers', $tSaved[0] === 200 && ( $tSaved[1]['key'] ?? '' ) === 'offer-one' && $stored !== null && $stored['name'] === 'Offer one' && count( $stored['blocks'] ) === 6
	&& ( $tSaved[1]['templates'][0]['blocks'] ?? 0 ) === 6 && isset( $tSaved[1]['tags'], $tSaved[1]['library'], $tSaved[1]['paper'], $tSaved[1]['ranges'] ) );
$got = $post( 'template-get', [ 'key' => 'offer-one' ] );
check( 'template-get answers the template and what the editor offers; a template that is not there is a 404 with its code, and a key that is no slug is the same', $got[0] === 200 && $got[1]['template'] === $stored && $got[1]['key'] === 'offer-one' && isset( $got[1]['library'], $got[1]['paper'] )
	&& array_map( static fn( array $one ): array => [ $one[0], $one[1]['code'] ?? '' ], [ $post( 'template-get', [ 'key' => 'nonesuch' ] ), $post( 'template-get', [ 'key' => '../../etc/passwd' ] ), $post( 'template-get', [ 'key' => "a\0b" ] ), $post( 'template-get', [ 'key' => [ 'x' ] ] ) ] )
		=== array_fill( 0, 4, [ 404, 'pdf_template_missing' ] ) );
$taken = $post( 'template-save', [ 'key' => 'offer-one', 'isNew' => true, 'name' => 'Again', 'tag' => '', 'blocks' => [] ] );
$ghost = $post( 'template-save', [ 'key' => 'ghost-one', 'isNew' => false, 'name' => 'Ghost', 'tag' => '', 'blocks' => [] ] );
$wrongBlock = $post( 'template-save', [ 'key' => 'wrong-one', 'isNew' => true, 'name' => 'Wrong', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'block' => 'nonesuch' ] ] ] );
$badTag = $post( 'template-save', [ 'key' => 'tagged-one', 'isNew' => true, 'name' => 'Tagged', 'tag' => 'nonesuch', 'blocks' => [] ] );
check( 'a template that exists is not made twice, one that does not exist is not saved as old, a block that is not in the library is named with its number, a tag that is not on the list is refused at its field', [ $taken[1]['code'] ?? '', $ghost[1]['code'] ?? '', $wrongBlock[1]['code'] ?? '', $wrongBlock[1]['params'] ?? [], $badTag[1]['code'] ?? '', $badTag[1]['field'] ?? '' ]
	=== [ 'pdf_key_taken', 'pdf_template_missing', 'pdf_block_ref', [ 1 ], 'pdf_tag', 'tag' ] && cpRead( $where, 'template', 'wrong-one' ) === null );
check( '...the list of the templates is what the view of the feature gives, with the day each was changed in the context', $post( 'templates-list' )[1] === cpRead( $where, 'view-templates' ) && ( $post( 'context' )[1]['changed']['offer-one'] ?? '' ) === gmdate( 'Y-m-d' ) );
$copy = $post( 'template-duplicate', [ 'key' => 'offer-one', 'name' => 'Offer copy' ] );
check( 'template-duplicate makes a copy under a key of its own through the writer, and a name is needed; the line of the log names both keys', $copy[0] === 200 && ( $copy[1]['key'] ?? '' ) === 'offer-one-copy' && ( cpRead( $where, 'template', 'offer-one-copy' )['name'] ?? '' ) === 'Offer copy'
	&& $post( 'template-duplicate', [ 'key' => 'offer-one', 'name' => '  ' ] )[1]['code'] === 'pdf_title' && $post( 'template-duplicate', [ 'key' => 'nonesuch', 'name' => 'X' ] )[1]['code'] === 'pdf_template_missing'
	&& $post( 'template-duplicate', [ 'key' => 'offer-one', 'name' => 'Third' ] )[0] === 200 && cpLog( $where, 1 ) === [ 'Duplicate PDF template "offer-one" as "offer-one-copy-2"' ] );
$inUse = $post( 'block-delete', [ 'key' => 'greeting' ] );
check( 'a text block that a template names is not deleted: 409 with the code and the templates, and the block stays', $inUse[0] === 409 && ( $inUse[1]['code'] ?? '' ) === 'pdf_block_used' && isset( cpRead( $where, 'blocks' )['greeting'] ) );
$context = $post( 'context' )[1];
$names = (array) ( $context['blocks']['greeting'] ?? [] );
sort( $names );
check( 'the context says which templates use a block, by name, and the texts of the site a template may place: the company of the site, and nothing else', $names === [ 'Offer copy', 'Offer one', 'Third' ] && count( $context['site'] ) > 0 && count( array_filter( $context['site'], static fn( string $key ): bool => preg_match( '~^/project/company/[a-z0-9_-]+/[a-z0-9_-]+$~D', $key ) !== 1 ) ) === 0 );

// Keys that are no keys: a path, a null byte, a key of another file of the library, one that is too long, a list - every action that takes a key refuses them and the library is as it was
$libraryNow = cpTree( cpRead( $where, 'data' ) );
$odd = [ '../../x', "a\0b", '../layout', 'LAYOUT', 'a.php', str_repeat( 'a', 500 ), [ 'x' ], null, 7 ];
$statuses = [];

foreach( $odd as $key )
	foreach( [ 'template-get', 'template-delete', 'block-delete', 'template-duplicate' ] as $action )
		$statuses[] = $post( $action, [ 'key' => $key, 'name' => 'Copy' ] )[0];

check( 'a key that is a path, holds a null byte, names another file of the library, is too long or is no text is refused by every action that takes a key (400 or 404), and the library is as it was', count( $statuses ) === 36 && count( array_filter( $statuses, static fn( int $status ): bool => $status === 400 || $status === 404 ) ) === 36
	&& cpTree( cpRead( $where, 'data' ) ) === $libraryNow && is_file( cpRead( $where, 'data', '/layout.php' ) ) === true );
check( 'a body that is no object, a design that is too long and a template with more blocks than it may have are refused before anything is written', cpPost( $where, $sessions['both@example.org'], 'crm-pdf/layout-get', [], [ 'body' => json_encode( [ 'action' => 'crm-pdf/layout-get', 'data' => 'x' ] ) ] )[0] === 400
	&& $post( 'layout-save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '', 'design' => str_repeat( 'a', 8001 ) ] )[1]['code'] === 'pdf_design_long'
	&& $post( 'template-save', [ 'key' => 'many-blocks', 'isNew' => true, 'name' => 'Many', 'tag' => '', 'blocks' => array_fill( 0, 61, [ 'type' => 'pagebreak' ] ) ] )[1]['code'] === 'pdf_blocks_many' && cpTree( cpRead( $where, 'data' ) ) === $libraryNow );
check( 'a token that is missing is a 403 and not an action', cpPost( $where, $sessions['both@example.org'], 'crm-pdf/blocks-list', [], [ 'token' => '' ] )[0] === 403 );

// --- D. who uses a template ----------------------------------------------------------------------------------------

[ $where2, $sessions2 ] = cpWorld( 'crm-pdf-d', true );
$both = $sessions2['both@example.org'];
cpPost( $where2, $both, 'crm-pdf/template-save', [ 'key' => 'used-one', 'isNew' => true, 'name' => 'Used one', 'tag' => '', 'blocks' => [] ] );
cpPost( $where2, $both, 'crm-pdf/template-save', [ 'key' => 'free-one', 'isNew' => true, 'name' => 'Free one', 'tag' => '', 'blocks' => [] ] );
$kept = static fn( string $where ): array => cpTree( cpRead( $where, 'data', '/templates' ) );
$GLOBALS['cpSet'] = [ './crm-pdf-probe/uses' => [ 'used-one' => [ 'crm-documents' => 3 ] ] ];
$ctx = cpPost( $where2, $both, 'crm-pdf/context' );
$stay = $kept( $where2 );
$del = cpPost( $where2, $both, 'crm-pdf/template-delete', [ 'key' => 'used-one' ] );
check( 'a template that a package says it uses is counted in the context, by package, and is not deleted: 409 with the code and the package, and the file is as it was', ( $ctx[1]['used']['used-one'] ?? 0 ) === 3 && ( $ctx[1]['by']['used-one'] ?? [] ) === [ 'crm-documents' => 3 ] && ( $ctx[1]['used']['free-one'] ?? 0 ) === 0
	&& $del[0] === 409 && ( $del[1]['code'] ?? '' ) === 'pdf_template_used' && str_contains( (string) ( $del[1]['params'][0] ?? '' ), 'crm-documents (3)' ) === true && $kept( $where2 ) === $stay );
$free = cpPost( $where2, $both, 'crm-pdf/template-delete', [ 'key' => 'free-one' ] );
check( '...a template that nothing uses is deleted, and the answer is the list without it; the line of the log says so', $free[0] === 200 && isset( $kept( $where2 )['/free-one.php'] ) === false && isset( $kept( $where2 )['/used-one.php'] ) === true
	&& cpLog( $where2, 1 ) === [ 'Delete PDF template "free-one"' ] && count( $free[1]['templates'] ?? [] ) === 1 );

$stay = $kept( $where2 );
$answers = [];
$warned = false;
$sentences = [];

foreach( [ 'throw' => 'throw', 'garbage' => 'garbage', 'broken' => [ 'used-one' => [ 'Not A Slug' => 2 ] ], 'negative' => [ 'used-one' => [ 'crm-documents' => -1 ] ] ] as $name => $says ) {

	$GLOBALS['cpSet'] = [ './crm-pdf-probe/uses' => $says ];
	$context = cpPost( $where2, $both, 'crm-pdf/context' );
	$try = cpPost( $where2, $both, 'crm-pdf/template-delete', [ 'key' => 'used-one' ] );
	$answers[$name] = [ $try[0], $try[1]['code'] ?? '', in_array( 'used-one', $context[1]['unsure'] ?? [], true ), $kept( $where2 ) === $stay ];
	$sentences[$name] = $try[1]['params'] ?? [];
	$warned = $warned || ninoWarnings() !== [];
}

check( 'a package that answers what is no answer - it throws, gives no list, names a package that is no slug or a count below nought - keeps the template: 409 with the code that says it could not be found out, the context marks the template unsure, and nothing is deleted',
	$answers === [ 'throw' => [ 409, 'pdf_template_unknown', true, true ], 'garbage' => [ 409, 'pdf_template_unknown', true, true ], 'broken' => [ 409, 'pdf_template_unknown', true, true ], 'negative' => [ 409, 'pdf_template_unknown', true, true ] ] );
check( '...and the feature says so in a warning of its own, which the request survives', $warned === true );
check( '...the refusal is written in the log as what it is, without the names of the listeners', cpLog( $where2, 1 ) === [ 'Refused to delete PDF template "used-one": no valid answer about its use' ] );
check( 'the sentence of that refusal names no class of PHP: where a listener belongs to a package its key stands for it - the package of the suite is crm-pdf-probe - and where it belongs to none a dash', $sentences['throw'] === [ 'crm-pdf-probe' ] && $sentences['garbage'] === [ 'crm-pdf-probe' ]
	&& array_filter( $sentences, static fn( array $params ): bool => str_contains( (string) ( $params[0] ?? '' ), '\\' ) === true || str_contains( (string) ( $params[0] ?? '' ), 'Nino' ) === true ) === [] );
$GLOBALS['cpSet'] = [ './crm-pdf-probe/uses' => 'none' ];
$silent = cpPost( $where2, $both, 'crm-pdf/context' );
check( 'a package that answers nothing at all is one that says nothing is used: the template is not unsure and is deleted', in_array( 'used-one', $silent[1]['unsure'] ?? [], true ) === false && cpPost( $where2, $both, 'crm-pdf/template-delete', [ 'key' => 'used-one' ] )[0] === 200
	&& isset( $kept( $where2 )['/used-one.php'] ) === false );
unset( $GLOBALS['cpSet'] );

// The package that uses the templates is off, or its folder is gone: what the kernel does is not to ask it. Nothing then keeps the template - the PDF feature cannot know a use that nobody tells it
[ $where3, $sessions3 ] = cpWorld( 'crm-pdf-d3', true );
cpPost( $where3, $sessions3['both@example.org'], 'crm-pdf/template-save', [ 'key' => 'orphan-one', 'isNew' => true, 'name' => 'Orphan', 'tag' => '', 'blocks' => [] ] );
$off = cpRequest( $where3 );
\Nino\Features::deactivate( $off, 'crm-pdf-probe' );
$GLOBALS['cpSet'] = [ './crm-pdf-probe/uses' => [ 'orphan-one' => [ 'crm-documents' => 2 ] ] ];
$offContext = cpPost( $where3, $sessions3['both@example.org'], 'crm-pdf/context' );
check( 'with the package that uses templates switched off its listener is not asked: no use is counted, nothing is unsure, and the screens work', $offContext[0] === 200 && ( $offContext[1]['used'] ?? [ 'x' ] ) === [] && ( $offContext[1]['unsure'] ?? [ 'x' ] ) === [] && ( $offContext[1]['groups'] ?? [ 'x' ] ) === [] );
cpRemove( $features. '/CrmPdfProbe' );
$gone = cpPost( $where3, $sessions3['both@example.org'], 'crm-pdf/context' );
$goneList = cpPost( $where3, $sessions3['both@example.org'], 'crm-pdf/templates-list' );
check( 'with the package that uses templates removed from the disk - its folder gone - the kernel does not ask it either: the screens answer, and the template can be deleted', $gone[0] === 200 && $goneList[0] === 200 && ( $gone[1]['used'] ?? [ 'x' ] ) === [] && cpPost( $where3, $sessions3['both@example.org'], 'crm-pdf/template-delete', [ 'key' => 'orphan-one' ] )[0] === 200 );
cpCopy( $source. '/tests/fixtures/CrmPdfProbe', $features. '/CrmPdfProbe' );
unset( $GLOBALS['cpSet'] );

// --- E. the preview -----------------------------------------------------------------------------------------------

[ $where4, $sessions4 ] = cpWorld( 'crm-pdf-e', true );
$both4 = $sessions4['both@example.org'];
$marker = 'MK'. bin2hex( random_bytes( 5 ) );
$GLOBALS['cpSet'] = [ './crm-pdf-probe/vocabulary' => [
	'groups' => [ [ 'key' => 'document', 'label' => [ 'en_US' => 'Document', 'de_DE' => 'Beleg' ], 'tokens' => [ [ 'token' => '/document/number', 'label' => [ 'en_US' => 'Number', 'de_DE' => 'Nummer' ] ], [ 'token' => 'probe.deep.leaf', 'label' => [ 'en_US' => 'Leaf' ] ] ] ] ],
	'slots' => [ [ 'name' => 'lines', 'label' => [ 'en_US' => 'Lines', 'de_DE' => 'Positionen' ] ] ],
	'sample' => [ '/document/number' => $marker, 'probe.deep.leaf' => 'LEAF'. $marker, 'probe.deep' => 'CLASH' ],
] ];
$GLOBALS['cpLocale'] = 'de_DE';
$vocabularyContext = cpPost( $where4, $both4, 'crm-pdf/context' )[1];
check( 'the context hands on what the callers of the vocabulary say, with the labels in the language of the page - the sample values are not sent', ( $vocabularyContext['groups'][0]['label'] ?? '' ) === 'Beleg' && ( $vocabularyContext['groups'][0]['tokens'][0]['label'] ?? '' ) === 'Nummer' && ( $vocabularyContext['groups'][0]['tokens'][1]['label'] ?? '' ) === 'Leaf'
	&& ( $vocabularyContext['slots'] ?? [] ) === [ [ 'name' => 'lines', 'label' => 'Positionen' ] ] && str_contains( (string) json_encode( $vocabularyContext ), $marker ) === false );
cpPost( $where4, $both4, 'crm-pdf/layout-save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '<p>Header [[/document/number]]</p>', 'footer' => '<p>Page [[page]]</p>' ] );

$sandbox4 = cpRead( $where4, 'sandbox' );
$libraryBefore = cpTree( cpRead( $where4, 'data' ) );
$pdfFiles = static function( string $base ): array {
	return array_values( array_filter( array_keys( cpTree( $base ) ), static fn( string $file ): bool => str_starts_with( (string) file_get_contents( $base. $file ), '%PDF' ) === true ) );
};
$pdfBefore = $pdfFiles( $sandbox4 );
$tempFolder = static fn(): array => array_values( array_filter( (array) scandir( $GLOBALS['tempRoot'] ), static fn( string $name ): bool => $name !== '.' && $name !== '..' && is_file( $GLOBALS['tempRoot']. '/'. $name ) === true ) );
check( 'the folder the process keeps its temporary files in is the one of this run, and nothing lies in it yet', sys_get_temp_dir() === $tempRoot && $tempFolder() === [] );
$previewBody = [ 'name' => 'Not saved', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>Number: [[/document/number]] Leaf: [[probe.deep.leaf]] Deep: [[probe.deep]]</p>' ] ] ];
$previewed = cpPost( $where4, $both4, 'crm-pdf/preview', $previewBody );
$layoutPreview = cpPost( $where4, $both4, 'crm-pdf/layout-preview' );
$leftover = $tempFolder();
$pdf = (string) base64_decode( (string) ( $previewed[1]['pdf'] ?? '' ), true );
$words = cpWords( $pdf );
check( 'the preview of a template that is not saved is a PDF in the answer, as base64: it begins as a PDF does, names the file, and shows the sample values of the vocabulary - a dotted name nested, a name that would stand under a value left out', $previewed[0] === 200 && str_starts_with( $pdf, '%PDF-' ) === true && ( $previewed[1]['file'] ?? '' ) === 'preview.pdf'
	&& str_contains( $words, $marker ) === true && str_contains( $words, 'LEAF'. $marker ) === true && str_contains( $words, 'CLASH' ) === false && str_contains( $words, 'Header '. $marker ) === true );
check( '...the layout\'s preview is a PDF the same way', $layoutPreview[0] === 200 && str_starts_with( (string) base64_decode( (string) ( $layoutPreview[1]['pdf'] ?? '' ), true ), '%PDF-' ) === true && ( $layoutPreview[1]['file'] ?? '' ) === 'design.pdf' );
check( 'the preview writes nothing: the library of the PDF feature is as it was, the template is not stored, no file of the project is a PDF, no temporary file is left, no line of the log is made, and the answer is never cached', cpTree( cpRead( $where4, 'data' ) ) === $libraryBefore && cpRead( $where4, 'template', 'not-saved' ) === null
	&& $pdfFiles( $sandbox4 ) === $pdfBefore && $leftover === [] && cpLog( $where4, 1 ) === [ 'Save the PDF layout' ] && ( $previewed[3]['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store' );
check( '...and no file of the project holds the sample value, as text', array_filter( array_keys( cpTree( $sandbox4 ) ), static fn( string $file ): bool => str_contains( (string) file_get_contents( $GLOBALS['sandbox4']. $file ), $GLOBALS['marker'] ) === true ) === [] );
$noName = cpPost( $where4, $both4, 'crm-pdf/preview', [ 'name' => '', 'tag' => '', 'blocks' => [] ] );
$wrongBlockPreview = cpPost( $where4, $both4, 'crm-pdf/preview', [ 'name' => 'X', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'block' => 'nonesuch' ] ] ] );
$junk = cpPost( $where4, $both4, 'crm-pdf/preview', [ 'name' => [ 'x' ], 'blocks' => 'nonesuch' ] );
check( 'a preview of what the editor holds is held to what a save is: a name is needed, a block of the library that is not there is named, a body that is no template is refused - and the answer is a refusal, not a PDF', [ $noName[0], $noName[1]['code'] ?? '', $noName[1]['field'] ?? '' ] === [ 400, 'pdf_title', 'name' ]
	&& [ $wrongBlockPreview[0], $wrongBlockPreview[1]['code'] ?? '' ] === [ 400, 'pdf_block_ref' ] && $junk[0] === 400 && isset( $noName[1]['pdf'] ) === false );
$inject = cpPost( $where4, $both4, 'crm-pdf/preview', [ 'name' => '<b>[[/document/number]]</b>', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>x</p><script>alert(1)</script><p>[[/document/number]]</p>' ] ] ] );
check( 'HTML and a placeholder in a name and a text are text: the preview is made, and the script of a block does not enter the PDF', $inject[0] === 200 && str_contains( cpWords( (string) base64_decode( (string) $inject[1]['pdf'], true ) ), 'alert(1)' ) === false );
unset( $GLOBALS['cpSet'] );

// --- F. where the editor is ---------------------------------------------------------------------------------------

[ $where5, $sessions5 ] = cpWorld( 'crm-pdf-f' );
$ask = static function( array $about, string $mail ) use ( $where5, $sessions5 ): array {
	$one = cpRequest( $where5, $sessions5[$mail] );
	return \Nino\Modules\Pdf::editorAddress( $one, $about );
};
$sentence = static fn( string $part, string $key = '', string $tag = '' ): array => [ 'surface' => 'crm', 'part' => $part, 'key' => $key, 'tag' => $tag ];
$workbench = $ask( $sentence( 'templates', 'offer-one' ), 'pdf@example.org' );
check( 'where the editor of a template is, for a screen of the CRM and an account with both rights, is a hash of the page, opened inside it', $ask( $sentence( 'templates', 'offer-one' ), 'both@example.org' ) === [ 'href' => '#pdf-template-offer-one', 'inside' => true ]
	&& $ask( $sentence( 'templates', '', 'offer' ), 'admin@example.org' ) === [ 'href' => '#pdf-new-template-offer', 'inside' => true ] && $ask( $sentence( 'templates' ), 'both@example.org' ) === [ 'href' => '#pdf', 'inside' => true ] );
check( '...a block, the pictures, the layout and the design have their hashes; a key that is no slug is the list', $ask( $sentence( 'blocks', 'greeting' ), 'both@example.org' )['href'] === '#pdf-block-greeting' && $ask( $sentence( 'blocks' ), 'both@example.org' )['href'] === '#pdf-blocks'
	&& $ask( $sentence( 'pictures' ), 'both@example.org' )['href'] === '#pdf-layout' && $ask( $sentence( 'layout' ), 'both@example.org' )['href'] === '#pdf-layout' && $ask( $sentence( 'design' ), 'both@example.org' )['href'] === '#pdf-design'
	&& $ask( $sentence( 'templates', '../x' ), 'both@example.org' )['href'] === '#pdf' && $ask( $sentence( 'blocks', "a b" ), 'both@example.org' )['href'] === '#pdf-blocks' );
check( 'an account without both rights is shown the address of the workbench and not the surface - the right of the CRM alone, the right of the PDF panel alone, and none - and it does not open inside', $workbench['inside'] === false && str_starts_with( $workbench['href'], '/_admin' ) === true
	&& $ask( $sentence( 'templates', 'offer-one' ), 'crm@example.org' ) === $workbench && $ask( $sentence( 'templates', 'offer-one' ), 'none@example.org' ) === $workbench );
check( '...another surface that asks gets the address of the workbench too, and so does a part the screens have no hash for', $ask( [ 'surface' => 'sign', 'part' => 'templates', 'key' => 'offer-one', 'tag' => '' ], 'both@example.org' ) === $workbench && $ask( $sentence( 'fonts', 'offer-one' ), 'both@example.org' )['inside'] === false );
$GLOBALS['cpSet'] = [ './crm-pdf-probe/editor' => [ 'href' => '/other/place', 'inside' => false ] ];
[ $where6, $sessions6 ] = cpWorld( 'crm-pdf-f2', true );
$withProbe = static function( array $about, string $mail ) use ( $where6, $sessions6 ): array {
	$one = cpRequest( $where6, $sessions6[$mail] );
	return \Nino\Modules\Pdf::editorAddress( $one, $about );
};
check( 'what a listener answers first decides: the package of the suite stands in front of this one in the order the kernel starts them, or behind it - the answer is one of the two and never both', in_array( $withProbe( $sentence( 'templates', 'offer-one' ), 'both@example.org' ), [ [ 'href' => '/other/place', 'inside' => false ], [ 'href' => '#pdf-template-offer-one', 'inside' => true ] ], true ) === true );
unset( $GLOBALS['cpSet'] );

// --- G. the PDF feature switched off by hand, the CRM switched off ---------------------------------------------------

[ $where7, $sessions7 ] = cpWorld( 'crm-pdf-g' );
$one7 = cpRequest( $where7 );
$config = (array) \Nino\Filesystem::getFileContent( $one7, '/config.php', [] );
$modules = (array) $config['/nino/modules'];
check( 'the project lists the package, the CRM and the PDF feature as the kernel wrote it', in_array( '\\'. \Nino\Modules\CrmPdf::class, $modules, true ) === true && in_array( '\\'. \Nino\Modules\Pdf::class, $modules, true ) === true && in_array( '\\'. \Nino\Modules\Crm::class, $modules, true ) === true );
$handMade = $config;
$handMade['/nino/modules'] = array_values( array_diff( $modules, [ '\\'. \Nino\Modules\Pdf::class ] ) );
$apiBefore = cpPost( $where7, $sessions7['both@example.org'], 'crm-pdf/blocks-list' )[0];
\Nino\Filesystem::putFileContent( $one7, '/config.php', $handMade );
$noPdf = cpPost( $where7, $sessions7['both@example.org'], 'crm-pdf/blocks-list' );
$noPdfApp = cpRequest( $where7, $sessions7['both@example.org'] );
$noPdfPage = cpGet( $where7, '/crm', $sessions7['both@example.org'] );
check( 'with the PDF feature taken out of the list by hand - the kernel would not do it - the package enters nothing: the endpoint does not know the action (404), no action of the package is entered, the page carries no data and no script of the package, and no warning is made',
	$apiBefore === 200 && $noPdf[0] === 404 && ( $noPdf[1]['code'] ?? '' ) === 'action' && array_filter( array_keys( \Nino\Modules\Crm\Api::actions( $noPdfApp ) ), static fn( string $action ): bool => str_starts_with( $action, 'crm-pdf/' ) === true ) === []
	&& isset( cpData( $noPdfPage[1] )['packages']['crm-pdf'] ) === false && str_contains( (string) $noPdfPage[1], 'crm-pdf' ) === false && ninoWarnings() === [] );
check( '...and nothing of it answers the editor: the question gets the address of the workbench', \Nino\Modules\Pdf::class !== '' && class_exists( \Nino\Modules\Pdf::class ) === true && ( \Nino\Features::get( $noPdfApp, 'pdf' )['active'] ?? true ) === false );
$noCrm = $config;
$noCrm['/nino/modules'] = array_values( array_diff( $modules, [ '\\'. \Nino\Modules\Crm::class ] ) );
\Nino\Filesystem::putFileContent( $one7, '/config.php', $noCrm );
$noCrmApp = cpRequest( $where7, $sessions7['both@example.org'] );
check( 'with the CRM taken out of the list by hand the package enters nothing either, and the request has no warning and no failure', array_filter( array_keys( (array) ( \Nino\Modules\Crm::featureOn( $noCrmApp, 'crm' ) === true ? \Nino\Modules\Crm\Api::actions( $noCrmApp ) : [] ) ), static fn( string $action ): bool => str_starts_with( $action, 'crm-pdf/' ) === true ) === []
	&& ninoWarnings() === [] );
\Nino\Filesystem::putFileContent( $one7, '/config.php', $config );

// --- H. the words ----------------------------------------------------------------------------------------------------

$words = static function( string $locale ) use ( $source ): array {
	$list = (array) require $source. '/Surface/text/'. $locale. '.php';
	ksort( $list );
	return $list;
};
$de = $words( 'de_DE' );
$en = $words( 'en_US' );
check( 'the words are the same keys in both languages, every one a text, none empty, and the number of places a value goes in is the same in both', array_keys( $de ) === array_keys( $en ) && count( $de ) > 150
	&& count( array_filter( $de + $en, static fn( mixed $text ): bool => is_string( $text ) === false || $text === '' ) ) === 0
	&& count( array_filter( array_keys( $de ), static fn( string $key ): bool => substr_count( $de[$key], '%s' ) !== substr_count( $en[$key], '%s' ) ) ) === 0 );
check( '...the names a block of a new field and of a new signature gets are words that are slugs of the feature, in both languages', preg_match( \Nino\Modules\Pdf::SLUG, $de['pdf.name-input'] ) === 1 && preg_match( \Nino\Modules\Pdf::SLUG, $de['pdf.name-signature'] ) === 1
	&& preg_match( \Nino\Modules\Pdf::SLUG, $en['pdf.name-input'] ) === 1 && preg_match( \Nino\Modules\Pdf::SLUG, $en['pdf.name-signature'] ) === 1 );

// A word for every code the writers of the feature refuse with: found in their code, not listed here
$codes = [];

foreach( [ 'Store/Store.php', 'Pdf.php' ] as $file ) {
	preg_match_all( "/'(pdf_[a-z_]+)'/", (string) file_get_contents( $features. '/Pdf/'. $file ), $found );
	$codes = array_merge( $codes, $found[1] );
}

$codes = array_values( array_unique( $codes ) );
sort( $codes );
// DESIGN maps the kinds of refusal of a sheet to codes by name in the Store; the codes there are in the list above
check( 'every code the writers and the previews of the PDF feature can refuse with has its sentence among the words of the package, in both languages: '. count( $codes ). ' codes', count( $codes ) > 40 && array_filter( $codes, static fn( string $code ): bool => isset( $de['error.'. $code], $en['error.'. $code] ) === false ) === [] );
check( '...and the words of the package are the sentences of the codes of the feature - the same keys its own panel says them under', array_filter( array_keys( $de ), static fn( string $key ): bool => str_starts_with( $key, 'error.' ) === true && in_array( substr( $key, 6 ), $codes, true ) === false ) === [] );

// The words of the pictures: the codes the kernel's upload and image checks refuse with
check( 'the codes that a refused picture can carry have a word of their own, which the script says, and none of them is a key the core could have', count( array_filter( [ 'upload_missing', 'upload_too_large', 'upload_partial', 'upload_server', 'image_too_large', 'image_type', 'image_too_many_pixels' ], static fn( string $code ): bool => isset( $de['pdf.err-'. $code], $en['pdf.err-'. $code] ) === false ) ) === 0
	&& array_filter( array_keys( $de ), static fn( string $key ): bool => preg_match( '/^(?:pdf\.|error\.pdf_)/', $key ) !== 1 ) === [] );

cpRemove( $features );
ninoDone( $a );
