<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-alone-smoke.php		The proof that the feature Crm runs alone: a project whose features directory holds nothing but the core - no package of the CRM, none of members, files, pdf, sign, events
 *												or review - answers the page /crm and the endpoint, and nothing that goes out to the browser names a package: not the page, the login, the page that says there is no permission, the
 *												second app, the manifest, the icon, the style sheet and the script of the login, nor the templates and the words they are made of. The page carries no entry of the rail, no data and no
 *												file of a package, and every action of the endpoint is asked with nothing and with ids over a project that has clients, projects, tasks and a logbook: none answers with a failure of the
 *												server and none raises a warning. One pin for each behaviour, over the structure and never the words. It travels with the feature and runs against the checkout three levels up, or the
 *												one NINO_ROOT names.
 *
 *	Usage: php features/Crm/tests/crm-alone-smoke.php
 *	       NINO_ROOT=../nino php features/Crm/tests/crm-alone-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$source = dirname( __DIR__ );

/**
 *	Copy a folder with what is in it
 *
 *	@param		string		$from
 *	@param		string		$to
 *
 *	@return 	void
 */
function aloCopy( string $from, string $to ): void {

	mkdir( $to, 0755, true );

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $from, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST ) as $item ) {

		$target = $to. '/'. substr( (string) $item->getPathname(), strlen( $from ) + 1 );

		if( $item->isDir() === true )
			mkdir( $target, 0755, true );
		else
			copy( (string) $item->getPathname(), $target );
	}
}

/**
 *	Take a folder of the suite away with what is in it
 *
 *	@param		string		$folder
 *
 *	@return 	void
 */
function aloRemove( string $folder ): void {

	if( is_dir( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

/*	The features directory of the suite: a folder of its own with a copy of the core and nothing else. A script of the core that is not written yet is a file of the suite in the copy	*/
$features = sys_get_temp_dir(). '/nino-crm-alone-features-'. bin2hex( random_bytes( 6 ) );
mkdir( $features. '/Crm', 0755, true );

foreach( [ 'Crm.php', 'feature.php' ] as $file )
	copy( $source. '/'. $file, $features. '/Crm/'. $file );

foreach( array_diff( (array) scandir( $source ), [ '.', '..', 'tests' ] ) as $folder )
	if( is_dir( $source. '/'. $folder ) === true )
		aloCopy( $source. '/'. $folder, $features. '/Crm/'. $folder );

foreach( [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ] as $file )
	if( is_file( $features. '/Crm/Surface/assets/'. $file ) === false )
		file_put_contents( $features. '/Crm/Surface/assets/'. $file, '/* a file of the suite */'. "\n" );

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-alone-smoke.php names its own features directory: NINO_FEATURES_DIR is defined already\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

/**
 *	The app data of a request of a project that exists
 *
 *	@param		string		$sandbox
 *	@param		array 		|null	$session
 *
 *	@return 	array
 */
function aloRequest( string $sandbox, ?array $session = null ): array {

	$_SESSION = $session ?? [];
	$_COOKIE = [];

	$appData = [ './nino/uid' => $sandbox ];
	\Nino\AppData::prepare( $appData );
	$appData['./nino/filesystem/path']				= $sandbox;
	$appData['./nino/filesystem/configpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/contentpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/publicpath']	= $sandbox. '/public';
	$appData['./nino/locales/current']				= 'de_DE';
	$appData['/nino/locales/native']					= 'de_DE';
	$appData['/nino/locales/available']				= [ 'de_DE', 'en_US' ];
	$appData['/nino/dir']											= '';
	$config																		= (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
	$appData['/nino/modules']									= (array) ( $config['/nino/modules'] ?? [] );
	$appData['/nino/auth/user']								= (array) ( $config['/nino/auth/user'] ?? [] );
	$appData['./nino/test/sandbox']						= $sandbox;

	\Nino\Html::init( $appData );
	\Nino\Csrf::init( $appData );
	\Nino\Auth::init( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

/**
 *	One GET with the callbacks of the response run
 *
 *	@param		string		$where
 *	@param		string		$uri
 *	@param		array 		|null	$session
 *
 *	@return 	array											[ status, body, headers, request ]
 */
function aloGet( string $where, string $uri, ?array $session = null ): array {

	$a = aloRequest( $where, $session );
	$request = ninoVisit( $a, $uri, 'GET', [ 'REMOTE_ADDR' => '127.0.0.1' ] );

	\Nino\Http::response( $a, $request );

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request['/nino/http/response']['header'] ?? [], $request ];
}

/**
 *	One post to the endpoint, the way the page makes it
 *
 *	@param		string		$where
 *	@param		array 		|null	$session
 *	@param		string		$action
 *	@param		mixed			$data
 *
 *	@return 	array											[ status, body ]
 */
function aloPost( string $where, ?array $session, string $action, mixed $data = [] ): array {

	$a = aloRequest( $where, $session );
	$server = [ 'REMOTE_ADDR' => '127.0.0.1', 'CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => \Nino\Csrf::getToken( $a ) ];
	$kept = $_SERVER;
	$_SERVER = $server + $_SERVER;
	$posted = $_POST;
	$_POST = [];

	try {
		$request = ninoVisit( $a, '/crm/api', 'POST', $server );
		$request['/nino/http/request']['body'] = json_encode( [ 'action' => $action, 'data' => $data ] );

		\Nino\Http::response( $a, $request );
	}
	finally {
		$_POST = $posted;
		$_SERVER = $kept;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

/**
 *	The JSON block of a page
 *
 *	@param		mixed			$body
 *
 *	@return 	array
 */
function aloData( mixed $body ): array {

	return preg_match( '~<script type="application/json" id="crm-data">(.*?)</script>~s', (string) $body, $found ) === 1 ? (array) json_decode( $found[1], true ) : [];
}

/**
 *	What in a text names a package of the CRM or a feature the core does not need: the key of a package, the class of a module of the old applications, the key of an action, a hash or a class of the markup that is
 *	a package's. A word of the language of the people (document, file, date) is no name; the names are spelt as the code spells them
 *
 *	@param		string		$text
 *
 *	@return 	array											The names found
 */
function aloNames( string $text ): array {

	$found = [];

	foreach( [
		'the key of a package' => '/\bcrm-(?:documents|portal|files|dates|mediareview|workshops|pdf|sign|members|events|review|probe)\b/i',
		'the class of a module' => '/\\\\Nino\\\\Modules\\\\(?:Members|Files|Pdf|Sign|Events|Review|CrmWorkshops|Crm[A-Z][A-Za-z]*)\b/',
		'an action of a package' => '/[\'"](?:crm-documents|crm-portal|crm-files|crm-dates|crm-termine|crm-dateien|crm-uploads|crm-review|crm-pdf|crm-sign|crm-members|crm-events)\//',
		'a hash of a package' => '/#(?:documents|document-|draft-|new-|dates|date-|files|uploads|pdf|pdf-|portal)\b/',
		'a class of a package' => '/(?:\.|\bclass="|\bid=")(?:totals|docline|erechnung|tagline|tpl-grid-pdf|up-list|up-row|cm-list|cm-reply|folder-mark|fb-bar|accounts|consent-lines|link-btn|link-icon|mega-icon|pay-icon|b-paid|b-overdue|b-sent|b-draft|figs|figures|dl-mark)\b/',
		'a cell of a package' => '/data-cards="(?:belege|cdocs|lines|termine|ctermine|uploads|pfiles|dclients|dfolders|dfavs|dfiles|fb)"/',
	] as $what => $pattern )
		if( preg_match_all( $pattern, $text, $matches ) > 0 )
			$found[$what] = $matches[0];

	return $found;
}

$GLOBALS['aloSandboxes'] = [];

/*	The core is alone	*/
$project = ninoSandbox( 'alone' );
$GLOBALS['aloSandboxes'][] = ninoSandboxDir( $project );
\Nino\Features::activate( $project, 'crm' );
$where = ninoSandboxDir( $project );
$a = aloRequest( $where );
\Nino\Auth::insertUser( $a, 'crm@example.org', 'crm password', [ '/_admin/crm/manage', '/_admin/elements/manage', '/_admin/text/manage' ] );
\Nino\Auth::insertUser( $a, 'plain@example.org', 'plain password', [] );
$sessions = [];

foreach( [ 'crm@example.org' => 'crm password', 'plain@example.org' => 'plain password' ] as $mail => $password ) {
	$b = aloRequest( $where );
	\Nino\Auth::loginUser( $b, $mail, $password );
	$sessions[$mail] = $_SESSION;
}

ninoWarnings();
$crm = $sessions['crm@example.org'];

try {

	$a = aloRequest( $where );
	$expected = [ 'Crm' ];

	check( 'the features directory of the suite holds the core and nothing else', array_values( array_diff( array_map( 'basename', (array) glob( $features. '/*', GLOB_ONLYDIR ) ), [] ) ) === $expected );
	check( 'no feature of a package of the CRM, of members, files, pdf, sign, events or review is there: none has a manifest and none is on', ( static function() use ( &$a ): bool {

		foreach( [ 'crm-documents', 'crm-portal', 'crm-files', 'crm-dates', 'crm-mediareview', 'crm-workshops', 'members', 'files', 'pdf', 'sign', 'events', 'review' ] as $key )
			if( \Nino\Features::get( $a, $key ) !== null || \Nino\Modules\Crm::featureOn( $a, $key ) === true )
				return false;

		return \Nino\Features::get( $a, 'crm' ) !== null && \Nino\Modules\Crm::featureOn( $a, 'crm' ) === true;
	} )() );

	[ $status, $body, $header ] = aloGet( $where, '/crm' );
	check( 'the page answers for a visitor with the login', $status === 200 && str_contains( (string) $body, 'id="login"' ) === true && $header['Cache-Control'] === 'private, no-store' );
	[ $status, $body ] = aloGet( $where, '/crm', $crm );
	$data = aloData( $body );
	check( 'the page answers for an account with the permission: the rail of the core with its five entries, no entry of a package, no data of one, and the files of the core alone', $status === 200 && str_contains( (string) $body, 'class="rail"' ) === true && $data['menu'] === [] && $data['packages'] === []
		&& preg_match_all( '~<a href="[^"]*" data-(?:r|page)="([a-z0-9-]+)"~', (string) $body, $nav ) === 5 && $nav[1] === [ 'overview', 'clients', 'projects', 'settings', 'values' ] && preg_match_all( '~<(?:script src|link rel="stylesheet" href)="[^"]*"~', (string) $body ) === 6 );
	[ $status, $body ] = aloGet( $where, '/crm', $sessions['plain@example.org'] );
	check( 'the page that says there is no permission answers', $status === 403 );
	[ $status, $body ] = aloGet( $where, '/crm/todo', $crm );
	check( 'the second app answers', $status === 200 && ( aloData( $body )['app'] ?? '' ) === 'todo' );
	check( 'a page of a package is none: the pages and the apps of the surface are the two of the core', \Nino\Modules\Crm\Surface\Devices::pages( $a ) === [ '/crm', '/crm/todo' ] && array_keys( \Nino\Modules\Crm\Surface\Apps::all( $a ) ) === [ 'todo' ] && \Nino\Modules\Crm\Surface\Menu::entries( $a ) === [] && \Nino\Modules\Crm\Surface\Scripts::entries( $a ) === []
		&& \Nino\Modules\Crm\Surface\Data::all( $a ) === [] );

	// The endpoint, over a project that has something in it
	[ $status, $client ] = aloPost( $where, $crm, 'crm-clients/save', [ 'name' => 'Ada Example', 'city' => 'Berlin' ] );
	$clientId = (int) ( $client['client']['id'] ?? 0 );
	$number = (string) ( $client['client']['number'] ?? '' );
	check( 'the endpoint answers: a client is made', $status === 200 && $clientId > 0 && $number !== '' );
	[ $status, $made ] = aloPost( $where, $crm, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Album', 'client' => $number ] ] );
	$projectId = \Nino\Elements::getElementUriFromUri( (string) ( $made['element']['.uri'] ?? '' ) );
	check( 'and a project', $status === 200 && $projectId !== '' );
	[ $status, $task ] = aloPost( $where, $crm, 'crm-tasks/add', [ 'project' => $projectId, 'text' => 'Choose the paper' ] );
	[ $status2 ] = aloPost( $where, $crm, 'crm-logbook/add', [ 'client' => $clientId, 'text' => 'A call' ] );
	check( 'and a task and a line of the logbook', $status === 200 && $status2 === 200 );
	[ $status, $overview ] = aloPost( $where, $crm, 'crm/overview' );
	check( 'the overview answers with what the core knows: no part of a package, no need of one', $status === 200 && (array) ( $overview['packages'] ?? [] ) === [] && (array) ( $overview['needs'] ?? [] ) === [] );

	$names = array_keys( \Nino\Modules\Crm\Api::actions( $a ) );
	$late = array_values( array_filter( $names, static fn( string $name ): bool => str_contains( $name, 'delete' ) === true || str_contains( $name, 'wipe' ) === true ) );
	$first = array_values( array_diff( $names, $late ) );
	$bodies = [
		'nothing' => [],
		'ids' => [ 'id' => $clientId, 'client' => $number, 'clientId' => $clientId, 'project' => $projectId, 'number' => $number, 'type' => 'crm-projects', 'uri' => $projectId, 'locale' => 'de_DE', 'text' => 'x' ],
		'ids that are none' => [ 'id' => 987654, 'client' => 'K-9999', 'project' => '987654', 'type' => 'crm-nothing', 'uri' => 'nothing', 'locale' => 'xx_XX' ],
	];
	$failed = [];
	$warned = [];
	ninoWarnings();

	foreach( array_merge( $first, $late ) as $name )
		foreach( $bodies as $what => $data ) {
			[ $status ] = aloPost( $where, $crm, $name, $data );
			$lines = ninoWarnings();

			if( $status >= 500 )
				$failed[] = $name. ' '. $what. ' '. $status;

			if( $lines !== [] )
				$warned[] = $name. ' '. $what;
		}

	check( 'every action of the endpoint - '. count( $names ). ' of them - asked with nothing, with ids and with ids that are none answers with no failure of the server', $failed === [] && count( $names ) === 42 );
	check( 'and none raises a warning', $warned === [] );
	[ $status, $body ] = aloPost( $where, $crm, 'crm-documents/list' );
	check( 'an action of a package is no action: 404 or 400 and no failure', $status >= 400 && $status < 500 );

	// What goes out names no package
	$pages = [];
	[ , $pages['the login'] ] = aloGet( $where, '/crm' );
	[ , $pages['the page'] ] = aloGet( $where, '/crm', $crm );
	[ , $pages['the page that says there is no permission'] ] = aloGet( $where, '/crm', $sessions['plain@example.org'] );
	[ , $pages['the second app'] ] = aloGet( $where, '/crm/todo', $crm );
	[ , $pages['the login of the second app'] ] = aloGet( $where, '/crm/todo' );
	[ , $pages['the manifest'] ] = aloGet( $where, '/crm/manifest.webmanifest' );
	[ , $pages['the manifest of the second app'] ] = aloGet( $where, '/crm/todo/manifest.webmanifest' );
	[ , $pages['the icon'] ] = aloGet( $where, '/crm/icon.svg' );
	$named = [];

	foreach( $pages as $what => $page )
		if( aloNames( (string) $page ) !== [] )
			$named[$what] = aloNames( (string) $page );

	check( 'nothing the surface answers names a package: the login, the page, the page that says there is no permission, the second app, the manifests and the icon - '. count( $pages ). ' answers', $named === [] );

	$data = aloData( $pages['the page'] );
	$keys = array_unique( array_merge( array_keys( $data['texts'] ), array_keys( (array) require $source. '/Surface/text/de_DE.php' ), array_keys( (array) require $source. '/Surface/text/en_US.php' ) ) );
	$words = array_filter( $keys, static fn( string|int $key ): bool => preg_match( '/^(?:pdf|tok|sign|export|figures|document|finalise|remind|chip|unitcode|totals|vat|erechnung|preview|paid|invoice|credit|accept|decline|payment|overdue|reminded|expired|cdocs|dateien|uploads|files|editor|approval|events|acc|cportal|plink|portal-extra|activity|privacy|greeting|bug-report|portal-host|portal-legal|invite|consents|consent|portal|here)\./', (string) $key ) === 1 );
	check( 'no word of the surface is a word of a package: none of the groups that leave the core with their package is among the keys of the page or of the two language files', $words === [] && count( $keys ) > 20 );

	// The files the surface serves and the markup they are made of
	$files = [];

	foreach( [ 'crm.css' => 'Surface/assets/crm.css', 'login.js' => 'Surface/assets/login.js' ] as $name => $path )
		$files[$name] = (string) file_get_contents( $source. '/'. $path );

	foreach( [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ] as $name )
		if( is_file( $source. '/Surface/assets/'. $name ) === true )
			$files[$name] = (string) file_get_contents( $source. '/Surface/assets/'. $name );

	foreach( (array) glob( $source. '/templates/surface-*.tpl' ) as $template )
		$files[basename( (string) $template )] = (string) file_get_contents( (string) $template );

	foreach( (array) glob( $source. '/Surface/text/*.php' ) as $text )
		$files[basename( (string) $text )] = (string) file_get_contents( (string) $text );

	$named = [];

	foreach( $files as $name => $text )
		if( aloNames( $text ) !== [] )
			$named[$name] = aloNames( $text );

	check( 'no file of the surface that goes to the browser names a package: the style sheet, the scripts, the templates and the words - '. count( $files ). ' files', $named === [] && count( $files ) >= 8 );

	// Every file of the code of the surface - php, scripts, style sheet, templates, comments included, the words excluded - says no thing of a package in either language and is English: the nouns of what the packages are (German and
	// English), and the words of the German language. "offer" and "document" are left out of the list on purpose: they are the verb of the install offer of the browser and the document of the DOM, which the script says
	$code = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source. '/Surface', \FilesystemIterator::SKIP_DOTS ) ) as $item )
		if( $item->isFile() === true && str_contains( (string) $item->getPathname(), '/Surface/text/' ) === false && preg_match( '/\.(?:php|js|css)$/D', (string) $item->getPathname() ) === 1 )
			$code[substr( (string) $item->getPathname(), strlen( $source ) + 1 )] = (string) file_get_contents( (string) $item->getPathname() );

	foreach( (array) glob( $source. '/templates/surface-*.tpl' ) as $template )
		$code['templates/'. basename( (string) $template )] = (string) file_get_contents( (string) $template );

	$nouns = '/\b(?:dateien|datei|belege?|rechnung(?:en)?|angebote?|portal|termine?|workshops?|erechnung|zugferd|file browser|invoices?|documents|reviews?|appointments?|uploads?|members?area)\b/i';
	$german = '/\b(?:nicht|und|der|die|das|wird|werden|oder|eine|einen|kein|keine|sind|nur|beim|zum|zur|vom|ist|mit|für|auf|dem|den|des|ein|auch|wenn|bei|aus|nach|über|stammdaten|bearbeiten|abmelden|freigaben?|ausblenden|kunden?|projekte?|aufgaben?|speichern|löschen|zurück)\b|[äöüÄÖÜß]/iu';
	$nounHits = [];
	$germanHits = [];

	foreach( $code as $name => $text ) {

		if( preg_match_all( $nouns, $text, $found ) > 0 )
			$nounHits[$name] = array_unique( $found[0] );

		// The names of the keys of the files of the surface are English words; the German function words are looked for in what is written, with no word of a string that is a key
		if( preg_match_all( $german, $text, $found ) > 0 )
			$germanHits[$name] = array_unique( $found[0] );
	}

	check( 'no file of the code of the surface names a thing of a package, in German or in English (files, documents, invoices, offers, dates, portal, workshops, review): '. count( $code ). ' files, comments included', $nounHits === [] && count( $code ) >= 14 );
	check( 'and none is written in German: no umlaut and no word of the German language anywhere in the code of the surface, comments included', $germanHits === [] );

	$css = $files['crm.css'];
	check( 'the style sheet is one that has its own colours and no token of the kernel and no class of the workbench: every custom property it uses it declares, and it names no nino- class', ( static function() use ( $css ): bool {

		preg_match_all( '/var\(\s*(--[a-z0-9-]+)/', $css, $used );
		preg_match_all( '/(--[a-z0-9-]+)\s*:/', $css, $declared );

		return array_diff( array_unique( $used[1] ), array_unique( $declared[1] ) ) === [] && preg_match( '/\.nino-|--nino-/', $css ) === 0;
	} )() );
	preg_match_all( '~/\*(.*?)\*/~s', $css, $comments );
	check( 'the style sheet and the script of the login are English: no umlaut anywhere and no German word in a comment', preg_match( '/[äöüÄÖÜß]/u', $css. $files['login.js'] ) === 0
		&& preg_match( '/\b(?:nicht|und|der|die|das|wird|werden|oder|eine|einen|kein|keine|sind|nur|beim|zum|zur|vom|ist)\b/', implode( ' ', $comments[1] ) ) === 0 );
}
catch( \Throwable $e ) {

	if( $e instanceof \Nino\Failure )
		throw $e;

	check( 'the proof ran to its end: it stopped with '. get_class( $e ). ' at line '. $e->getLine(), false );
	fwrite( STDERR, $e->getMessage(). "\n" );
}

foreach( (array) $GLOBALS['aloSandboxes'] as $folder )
	aloRemove( (string) $folder );

aloRemove( $features );

$done = []; ninoDone( $done );
