<?php
declare(strict_types=1);

// The Greeting feature's contract test. Runs against the checkout three levels up,
// or the one NINO_ROOT names
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'greeting' );
$appData['/nino/install/completed'] = true;
$appData['/nino/dir'] = '';
$appData['/nino/auth/user'] = [];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Elements' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

$seed = include dirname( __DIR__ ). '/install/elements/greetings.php';
$ids 	= array_keys( $seed['de_DE'] );

echo "Greeting - the feature\n";

check( 'the manifest validates without a warning', is_array( \Nino\Features::manifest( dirname( __DIR__ ) ) ) && ninoWarnings() === [] );
check( 'activation lists the class and records the version, and puts nothing of the project in place: no type, no key of config.php', \Nino\Features::activate( $appData, 'greeting' ) === true
	&& \Nino\Features::get( $appData, 'greeting' )['installed'] === '1.0.0' && is_file( ninoSandboxDir( $appData ). '/private/elements/greetings.php' ) === false
	&& array_intersect_key( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ), array_flip( array_values( \Nino\Modules\Greeting::LINES ) ) ) === [] );
check( 'the panel is there while the feature is on, with its two actions behind its own permission', isset( \Nino\Admin\Admin::panels( $appData )['greeting'] ) === true
	&& array_keys( \Nino\Modules\Greeting\Admin::actions() ) === [ 'greeting/list', 'greeting/save' ] && \Nino\Modules\Greeting\Admin::perm() === '/_admin/greeting/manage' );

\Nino\Modules::callModules( $appData, 'init' );

check( 'the three lines are keys of config.php, named as a project that holds them has them', \Nino\Modules\Greeting::LINES === [
	'hello' => '/nino/admin/greeting/hello', 'sentence' => '/nino/admin/greeting/sentence', 'facts' => '/nino/admin/greeting/facts' ] );

echo "\n";


// --- The head of the Dashboard ---------------------------------------------------

// A Nino that has a head at its Dashboard (the callback of \Nino\Modules\Dashboard\Admin::HEAD) draws the card from the
// feature; one that has none draws its own greeting and the feature adds nothing, so there is never a second card
$head = defined( '\\Nino\\Modules\\Dashboard\\Admin::HEAD' ) === true;

if( $head === true ) {

	echo "Greeting - the card in the answer of the Dashboard\n";

	pinPreviousLogin( $appData );
	pinActivity( $appData );
	pinBackup( $appData );
	pinSentences( $appData, $seed, $ids );
	pinSwitches( $appData );
	pinProjectOfBefore( $seed );
	pinPanel( $appData );
	pinDeactivated( $appData );
}
else {

	echo "Greeting - a Nino whose Dashboard has no head: the card stays the Dashboard's own\n";
	echo "  --  - this Nino has no head at its Dashboard: the checks of the card were NOT run\n";

	pinWithoutHead( $appData );
	pinPanel( $appData, false );
	pinUnitFile( $seed );
}

echo "\n";


// --- The panel's script, where node is on the path -----------------------------------

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: greeting-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/greeting-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'greeting-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );

/** The summary of the Dashboard as the workbench answers it */
function summary( array &$appData ): array {
	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [], 'dashboard/summary' );
}

/** The card of the summary, or null where the answer has none */
function cardOf( array &$appData ): ?array {
	[ $status, $body ] = summary( $appData );
	return $status === 200 && is_array( $body['head']['greeting'] ?? null ) === true ? $body['head']['greeting'] : null;
}

/** Make the logins of an account older, as if they had been that many seconds ago */
function ageLogins( array &$appData, string $mail, int $seconds ): void {

	$appData['/nino/auth/user'][$mail]['lastLogin'] = ( $appData['/nino/auth/user'][$mail]['lastLogin'] ?? time() ) - $seconds;

	foreach( $appData['/nino/auth/user'][$mail]['sessions'] ?? [] as $token => $session )
		$appData['/nino/auth/user'][$mail]['sessions'][$token]['time'] = $session['time'] - $seconds;
}

/** The login before this one is told, and stays what it was for the whole session: 0 for the first login of an account */
function pinPreviousLogin( array &$appData ): void {

	\Nino\Auth::insertUser( $appData, 'visits@example.com', 'visits password', [ '/*' ], '', 'Vera' );
	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );

	$card = cardOf( $appData );
	check( 'the summary carries the card in its head, with the name the account has', $card !== null && $card['name'] === 'Vera' );
	check( '...and for the first login of an account the login before it is 0', ( $card['previousLogin'] ?? null ) === 0 );

	$first = \Nino\Auth::lastLogin( \Nino\Auth::getUser( $appData, 'visits@example.com' ) );
	ageLogins( $appData, 'visits@example.com', 5000 );
	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );

	check( 'a second login tells the time of the first one: the login before the running one', ( cardOf( $appData )['previousLogin'] ?? null ) === $first - 5000 );
	check( '...the same in the next request of the session', ( cardOf( $appData )['previousLogin'] ?? null ) === $first - 5000 );

	$second = \Nino\Auth::lastLogin( \Nino\Auth::getUser( $appData, 'visits@example.com' ) );
	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
	check( '...and after a third login it is the second', ( cardOf( $appData )['previousLogin'] ?? null ) === $second );

	\Nino\Runtime::unsetSessionValue( $appData, './nino/auth/previous' );
	check( 'a session that holds none says null, not 0 - nobody is told it is their first visit', array_key_exists( 'previousLogin', cardOf( $appData ) ?? [] ) === true && cardOf( $appData )['previousLogin'] === null );

	ninoUser( $appData, 'nameless@example.com', [ '/*' ], 'nameless password' );
	check( 'an account without a name is greeted with an empty one', cardOf( $appData )['name'] === '' );

	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
}

/** The log lines since the last visit are counted for whoever may read the log, and for nobody else */
function pinActivity( array &$appData ): void {

	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
	ageLogins( $appData, 'visits@example.com', 5000 );
	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );

	$before = cardOf( $appData )['activitySince'] ?? null;
	check( 'an account that may read the log is told how many lines it holds since the last visit', is_int( $before ) === true && $before >= 0 );

	\Nino\Modules\Logs\Admin::record( $appData, 'other@example.com', 'Edit Something' );
	\Nino\Modules\Logs\Admin::record( $appData, 'other@example.com', 'Edit Something else' );
	check( '...two lines more are two more', ( cardOf( $appData )['activitySince'] ?? null ) === $before + 2 );

	\Nino\Modules\Logs\Admin::record( $appData, 'visits@example.com', 'Login' );
	check( '...the sign-in of the account itself is the visit and is not counted', ( cardOf( $appData )['activitySince'] ?? null ) === $before + 2 );
	\Nino\Modules\Logs\Admin::record( $appData, 'visits@example.com', 'Edit Something' );
	check( '...a line of its own that is none of the sign-in is', ( cardOf( $appData )['activitySince'] ?? null ) === $before + 3 );

	\Nino\Auth::insertUser( $appData, 'nologs@example.com', 'nologs password', [ \Nino\Modules\Elements\Admin::MANAGE_PERM ] );
	\Nino\Auth::loginUser( $appData, 'nologs@example.com', 'nologs password' );
	ageLogins( $appData, 'nologs@example.com', 5000 );
	\Nino\Auth::loginUser( $appData, 'nologs@example.com', 'nologs password' );
	$card = cardOf( $appData );
	check( 'an account that may not read the log is told nothing of it - the key is left out', $card !== null && array_key_exists( 'activitySince', $card ) === false && $card['previousLogin'] > 0 );

	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
}

/** The age of the last backup is told while there is one, and left out when there is none */
function pinBackup( array &$appData ): void {

	// The workbench writes the day's backup itself while the switch is on
	$appData['/nino/admin/backups'] = false;
	$dir = \Nino\Modules\Backups::dir( $appData );
	foreach( glob( $dir. '/*' ) ?: [] as $file )
		unlink( $file );
	@mkdir( $dir, 0755, true );

	check( 'with no backup at all the key is left out', array_key_exists( 'backupDays', cardOf( $appData ) ?? [ 'backupDays' => 0 ] ) === false );

	file_put_contents( $dir. '/'. date( 'Y-m-d' ). '.php', '' );
	check( 'the backup of today is 0 days old', ( cardOf( $appData )['backupDays'] ?? null ) === 0 );

	foreach( glob( $dir. '/*' ) ?: [] as $file )
		unlink( $file );
	file_put_contents( $dir. '/'. date( 'Y-m-d', strtotime( '-10 days' ) ). '.php', '' );
	file_put_contents( $dir. '/'. date( 'Y-m-d', strtotime( '-30 days' ) ). '.php', '' );
	check( 'the newest backup counts: ten days', ( cardOf( $appData )['backupDays'] ?? null ) === 10 );

	foreach( glob( $dir. '/*' ) ?: [] as $file )
		unlink( $file );
	$appData['/nino/admin/backups'] = true;
}

/** The sentences are the elements of the type /greetings, put in once, in the language of the interface, hidden ones left out */
function pinSentences( array &$appData, array $seed, array $ids ): void {

	$ignored = ninoWarnings();
	check( 'the type exists once the Dashboard has been asked, with the model of the unit file', is_file( ninoSandboxDir( $appData ). '/private/elements/greetings.php' ) === true
		&& \Nino\Elements::getElementModel( $appData, '/greetings' )['phase']['options'] === [ 'any', 'early', 'morning', 'noon', 'afternoon', 'evening', 'night' ]
		&& \Nino\Elements::getElementModel( $appData, '/greetings' )['day']['options'] === [ 'any', 'monday', 'friday', 'weekend' ]
		&& \Nino\Elements::getElementModel( $appData, '/greetings' )['occasion']['options'] === [ 'none', 'christmas', 'newyear' ]
		&& \Nino\Elements::getElementModel( $appData, '/greetings' )['text']['locale'] === true );

	\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'en_US' );
	$card = cardOf( $appData );
	check( 'the card holds every sentence of the unit, each with its text and when it fits', $card !== null && count( $card['sentences'] ) === count( $ids )
		&& array_diff( array_column( $card['sentences'], 'phase' ), [ 'any', 'early', 'morning', 'noon', 'afternoon', 'evening', 'night' ] ) === []
		&& array_diff( array_column( $card['sentences'], 'day' ), [ 'any', 'monday', 'friday', 'weekend' ] ) === []
		&& array_diff( array_column( $card['sentences'], 'occasion' ), [ 'none', 'christmas', 'newyear' ] ) === []
		&& count( array_filter( $card['sentences'], static fn( array $sentence ): bool => $sentence['text'] !== '' ) ) === count( $ids ) );
	check( '...in English for an English interface', in_array( $seed['en_US']['early-01']['text'], array_column( $card['sentences'], 'text' ), true ) === true
		&& in_array( $seed['de_DE']['early-01']['text'], array_column( $card['sentences'], 'text' ), true ) === false );

	\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'de_DE' );
	check( '...and in German for a German one', in_array( $seed['de_DE']['early-01']['text'], array_column( cardOf( $appData )['sentences'], 'text' ), true ) === true );

	check( 'an element carries the three fields as the unit wrote them', ( static function() use ( $card ): bool {
		$christmas = array_values( array_filter( $card['sentences'], static fn( array $sentence ): bool => $sentence['occasion'] === 'christmas' ) );
		$monday = array_values( array_filter( $card['sentences'], static fn( array $sentence ): bool => $sentence['day'] === 'monday' ) );
		$night = array_values( array_filter( $card['sentences'], static fn( array $sentence ): bool => $sentence['phase'] === 'night' ) );
		return count( $christmas ) >= 4 && count( $monday ) >= 6 && count( $night ) >= 10;
	} )() );

	\Nino\Elements::updateElement( $appData, '/greetings/early-01', [ 'hidden' => true ] );
	$texts = array_column( cardOf( $appData )['sentences'], 'text' );
	check( 'a hidden element is not delivered', in_array( $seed['de_DE']['early-01']['text'], $texts, true ) === false && count( $texts ) === count( $ids ) - 1 );

	\Nino\Elements::updateElement( $appData, '/greetings/early-02', [ 'text' => 'Mein eigener Satz.' ], 'de_DE' );
	check( 'a sentence an editor changed is the editor\'s', in_array( 'Mein eigener Satz.', array_column( cardOf( $appData )['sentences'], 'text' ), true ) === true );

	// A file edited by hand can hold a word the model does not offer: it is delivered as 'any', never as it stands
	$typeFile = ninoSandboxDir( $appData ). '/private/elements/greetings.php';
	$type = include $typeFile;
	$type['*']['early-04']['phase'] = 'bogus';
	$type['*']['early-04']['day'] = [ 'x' ];
	$type['*']['early-05']['occasion'] = 'bogus';
	file_put_contents( $typeFile, '<?php return '. var_export( $type, true ). ';' );
	unset( $appData['./nino/elements/cache'] );
	$odd = array_column( cardOf( $appData )['sentences'], null, 'text' );
	check( 'a word of phase, day or occasion that is none of the options is delivered as any or none', ( $odd[ $seed['de_DE']['early-04']['text'] ] ?? [] )['phase'] === 'any' && ( $odd[ $seed['de_DE']['early-04']['text'] ] ?? [] )['day'] === 'any'
		&& ( $odd[ $seed['de_DE']['early-05']['text'] ] ?? [] )['occasion'] === 'none' );

	\Nino\Elements::deleteElement( $appData, '/greetings/early-03', '*' );
	$count = count( cardOf( $appData )['sentences'] );
	check( 'an element an editor deleted does not come back with the next summary - the unit only adds', $count === count( $ids ) - 2 && count( cardOf( $appData )['sentences'] ) === $count );

	$bytes = (string) file_get_contents( $typeFile );
	cardOf( $appData );
	check( 'a type that is there is not written again', (string) file_get_contents( $typeFile ) === $bytes );

	// What a feature that is switched on again, or updated, does to the project's sentences: nothing
	check( 'activating the feature again leaves the type as the editors left it, a deleted sentence deleted', \Nino\Features::activate( $appData, 'greeting' ) === true
		&& (string) file_get_contents( $typeFile ) === $bytes );

	$removed = static fn(): array => (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )[\Nino\Elements::REMOVED] ?? [] );
	[ $status ] = ninoCall( $appData, [ \Nino\Modules\Elements\Types::class, 'apiDelete' ], [ 'uri' => 'greetings', 'confirm' => 'greetings' ] );
	check( 'the editors delete the type', $status === 200 && is_file( $typeFile ) === false && isset( $removed()['greetings'] ) === true );
	$card = cardOf( $appData );
	check( '...and it does not come back: no sentences, no type file, the card is there, and the Dashboard has no warning to give for a type that is gone', $card !== null && $card['sentences'] === [] && is_file( $typeFile ) === false
		&& ninoWarnings() === [] );

	// A project that never had the type gets it with its first summary - seeded in both languages
	$fresh = ninoSandbox( 'greetings' );
	$fresh['/nino/modules'] = [];
	check( 'a project with no type and no tombstone is given the type', \Nino\Modules\Greeting::seed( $fresh, [ 'de_DE', 'en_US' ] ) === true
		&& count( \Nino\Elements::queryElements( $fresh, '/greetings', [], 'de_DE', [] ) ) === count( $ids )
		&& count( \Nino\Elements::queryElements( $fresh, '/greetings', [], 'en_US', [] ) ) === count( $ids ) );
	$bytes = (string) file_get_contents( ninoSandboxDir( $fresh ). '/private/elements/greetings.php' );
	check( '...a second run writes nothing', \Nino\Modules\Greeting::seed( $fresh, [ 'de_DE', 'en_US' ] ) === true && (string) file_get_contents( ninoSandboxDir( $fresh ). '/private/elements/greetings.php' ) === $bytes );
	exec( 'rm -rf '. escapeshellarg( ninoSandboxDir( $fresh ) ) );

	// The next pins start from the type in place again
	unset( $appData[ \Nino\Elements::REMOVED ]['greetings'] );
	\Nino\Modules\Greeting::seed( $appData, [ 'de_DE', 'en_US' ] );
}

/** Each of the three lines is a key of config.php; with all three off the answer has no card at all */
function pinSwitches( array &$appData ): void {

	foreach( \Nino\Modules\Greeting::LINES as $key )
		unset( $appData[$key] );

	$card = cardOf( $appData );
	check( 'the card says which lines are on - all three where config.php has no word of them', $card !== null && $card['show'] === [ 'hello' => true, 'sentence' => true, 'facts' => true ] );

	$appData['/nino/admin/greeting/facts'] = false;
	$card = cardOf( $appData );
	check( 'with the facts off the log is not counted and the backup not read - and the rest is there', $card['show']['facts'] === false && array_key_exists( 'activitySince', $card ) === false
		&& array_key_exists( 'backupDays', $card ) === false && array_key_exists( 'sentences', $card ) === true );

	$appData['/nino/admin/greeting/sentence'] = false;
	$card = cardOf( $appData );
	check( 'with the sentence off the elements are not sent', $card['show'] === [ 'hello' => true, 'sentence' => false, 'facts' => false ] && array_key_exists( 'sentences', $card ) === false );

	$appData['/nino/admin/greeting/hello'] = false;
	[ $status, $body ] = summary( $appData );
	check( 'with all three off there is no area in the head, and everything else is in the answer', $status === 200 && array_key_exists( 'head', $body ) === false && array_key_exists( 'tiles', $body ) === true && array_key_exists( 'notices', $body ) === true );

	foreach( \Nino\Modules\Greeting::LINES as $key )
		unset( $appData[$key] );
	check( 'a switch that is not set is on', cardOf( $appData )['show'] === [ 'hello' => true, 'sentence' => true, 'facts' => true ] );

	$appData['/nino/admin/greeting/hello'] = 0;
	check( 'only false switches a line off', cardOf( $appData )['show']['hello'] === true );
	unset( $appData['/nino/admin/greeting/hello'] );
}

/**
 *	A project the feature finds as it was left: the three switches in config.php, a type of sentences with
 *	a sentence an editor changed, one it hid and one it deleted - and the card is drawn from them, the
 *	feature is switched on without writing a byte of it
 */
function pinProjectOfBefore( array $seed ): void {

	$project = ninoSandbox( 'greeting-project' );
	$project['/nino/install/completed'] = true;
	$project['/nino/dir'] = '';
	$project['/nino/modules'] = [];
	\Nino\Filesystem::putFileContent( $project, '/config.php', [
		'/nino/modules' => [], '/nino/locales/available' => [ 'de_DE', 'en_US' ], '/nino/locales/native' => 'de_DE',
		'/nino/admin/greeting/hello' => true, '/nino/admin/greeting/sentence' => true, '/nino/admin/greeting/facts' => false,
	] );
	// What a boot of the project reads from its config.php
	$project['/nino/admin/greeting/facts'] = false;
	\Nino\Modules\Greeting::seed( $project, [ 'de_DE', 'en_US' ] );
	\Nino\Elements::updateElement( $project, '/greetings/night-01', [ 'text' => 'Mein Satz.' ], 'de_DE' );
	\Nino\Elements::updateElement( $project, '/greetings/night-02', [ 'hidden' => true ] );
	\Nino\Elements::deleteElement( $project, '/greetings/night-03', '*' );

	$dir = ninoSandboxDir( $project ). '/private';
	$before = [ 'config' => (string) file_get_contents( $dir. '/config.php' ), 'type' => (string) file_get_contents( $dir. '/elements/greetings.php' ) ];

	$activated = \Nino\Features::activate( $project, 'greeting' );
	check( 'the feature is switched on in that project'. ( $activated === true ? '' : ' - '. $activated ), $activated === true && ninoWarnings() === [] );
	$after = [ 'config' => \Nino\Filesystem::getFileContent( $project, '/config.php', [] ), 'type' => (string) file_get_contents( $dir. '/elements/greetings.php' ) ];
	check( '...it writes the version and the class to config.php and leaves the three switches and the type of sentences as they were',
		$after['type'] === $before['type'] && $after['config']['/nino/admin/greeting/facts'] === false && $after['config']['/nino/admin/greeting/hello'] === true
		&& array_keys( $after['config'] ) === array_keys( array_merge( include $dir. '/config.php' ) ) && in_array( '\\Nino\\Modules\\Greeting', $after['config']['/nino/modules'], true ) === true );

	\Nino\Modules::callModules( $project, 'init' );
	ninoUser( $project, 'old@example.com', [ '/*' ], 'old password' );
	\Nino\Runtime::setSessionValue( $project, './admin/locale', 'de_DE' );
	$card = cardOf( $project );
	$texts = array_column( $card['sentences'] ?? [], 'text' );
	check( 'the card is the one the project had: the switch it turned off stays off, the sentences are its own', $card !== null && $card['show'] === [ 'hello' => true, 'sentence' => true, 'facts' => false ]
		&& in_array( 'Mein Satz.', $texts, true ) === true && in_array( $seed['de_DE']['night-02']['text'], $texts, true ) === false
		&& in_array( $seed['de_DE']['night-03']['text'], $texts, true ) === false && count( $texts ) === count( $seed['de_DE'] ) - 2 );

	exec( 'rm -rf '. escapeshellarg( ninoSandboxDir( $project ) ) );
}

/** The panel: three switches that write the keys of config.php, each on its own, behind its own permission */
function pinPanel( array &$appData, bool $head = true ): void {

	$call = static function( string $action, array $data = [] ) use ( &$appData ): array {
		return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
	};

	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
	foreach( \Nino\Modules\Greeting::LINES as $key )
		unset( $appData[$key] );
	\Nino\Filesystem::mutate( $appData, '/config.php', static function( array $config ): array {
		foreach( \Nino\Modules\Greeting::LINES as $key )
			unset( $config[$key] );
		return $config;
	} );

	[ $status, $body ] = $call( 'greeting/list' );
	check( 'the panel lists the three lines, on where config.php says nothing', $status === 200 && $body === [ 'lines' => [ 'hello' => true, 'sentence' => true, 'facts' => true ] ] );

	$appData['/nino/admin/greeting/hello'] = false;
	[ , $body ] = $call( 'greeting/list' );
	check( 'the list is what config.php holds, not what a module made of the line for this request alone', $body['lines']['hello'] === true );
	unset( $appData['/nino/admin/greeting/hello'] );

	[ $status, $body ] = $call( 'greeting/save', [ 'lines' => [ 'sentence' => false ] ] );
	$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
	check( 'switching one off writes that one, and the other two are left as they are', $status === 200 && $stored['/nino/admin/greeting/sentence'] === false
		&& array_key_exists( '/nino/admin/greeting/hello', $stored ) === false && array_key_exists( '/nino/admin/greeting/facts', $stored ) === false
		&& $body === [ 'lines' => [ 'hello' => true, 'sentence' => false, 'facts' => true ] ] );
	if( $head === true )
		check( '...and the card has that line off at once', cardOf( $appData )['show'] === [ 'hello' => true, 'sentence' => false, 'facts' => true ] );

	[ $status, $body ] = $call( 'greeting/save', [ 'lines' => [ 'facts' => 'yes', 'hello' => false ] ] );
	check( 'a value that is no boolean is refused with its code and the line, and nothing of the request is written', $status === 400 && $body['code'] === 'bool' && $body['field'] === 'facts'
		&& array_key_exists( '/nino/admin/greeting/hello', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) ) === false );

	[ $status ] = $call( 'greeting/save', [ 'lines' => [ 'other' => true ] ] );
	check( 'a line nobody knows is refused', $status === 400 );
	[ $status ] = $call( 'greeting/save', [ 'lines' => 'all' ] );
	check( '...and so is a body that is no list of lines', $status === 400 );

	[ $status, $body ] = $call( 'greeting/save', [ 'lines' => [ 'hello' => 'false', 'sentence' => '1', 'facts' => 0 ] ] );
	check( 'the strings and numbers a form posts are read as the booleans they stand for', $status === 200 && $body['lines'] === [ 'hello' => false, 'sentence' => true, 'facts' => false ] );
	$call( 'greeting/save', [ 'lines' => [ 'hello' => true, 'facts' => true ] ] );

	ninoUser( $appData, 'plain@example.com', [], 'plain password' );
	[ $status ] = $call( 'greeting/list' );
	[ $saved ] = $call( 'greeting/save', [ 'lines' => [ 'hello' => false ] ] );
	check( 'an account without the permission of the panel is refused, for the list and for the save, and sees the card all the same', $status === 403 && $saved === 403
		&& ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/admin/greeting/hello'] ?? true ) === true && ( $head === false || cardOf( $appData ) !== null ) );

	check( 'a save is logged with the lines it names, and a list is not', \Nino\Modules\Greeting\Admin::log( 'greeting/save', [ 'lines' => [ 'hello' => false ] ] ) === 'Edit Greeting hello' && \Nino\Modules\Greeting\Admin::log( 'greeting/list', [] ) === '' );

	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$words = \Nino\Admin\Admin::textFills( $appData, '/features/Greeting/text', $locale );
		$missing = [];
		foreach( [ 'nav', 'intro', 'label/hello', 'hint/hello', 'label/sentence', 'hint/sentence', 'label/facts', 'hint/facts', 'label/save', 'error/load', 'error/save', 'hint/head',
			'hello/early', 'hello/morning', 'hello/noon', 'hello/afternoon', 'hello/evening', 'hello/night',
			'fact/first', 'fact/today', 'fact/yesterday', 'fact/days', 'fact/activity', 'fact/activity-one', 'fact/backup' ] as $key )
			if( trim( (string) ( $words['[[/_admin/greeting/'. $key. ']]'] ?? '' ) ) === '' )
				$missing[] = $key;
		check( "every word of the panel and of the card is there in $locale". ( $missing === [] ? '' : ' - missing: '. implode( ', ', $missing ) ), $missing === [] );

		$typeWords = [ '/_admin/elements/type/greetings/hint' ];
		$model = include dirname( __DIR__ ). '/install/elements/greetings.php';
		foreach( $model['model'] as $field => $definition ) {
			$typeWords[] = '/_admin/elements/field/greetings/'. $field;
			foreach( $definition['options'] ?? [] as $option )
				$typeWords[] = '/_admin/elements/option/greetings/'. $field. '/'. $option;
		}
		$lacking = array_filter( $typeWords, static fn( string $key ): bool => trim( (string) ( $words['[['. $key. ']]'] ?? '' ) ) === '' );
		check( "...and the Elements panel's words for the type of the sentences in $locale". ( $lacking === [] ? '' : ' - missing: '. implode( ', ', $lacking ) ), $lacking === [] && count( $typeWords ) >= 20 );
	}

	$blanks = [];
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$words = \Nino\Admin\Admin::textFills( $appData, '/features/Greeting/text', $locale );
		foreach( [ 'days', 'activity', 'backup' ] as $fact )
			if( substr_count( (string) $words['[[/_admin/greeting/fact/'. $fact. ']]'], '%s' ) !== 1 )
				$blanks[] = $fact. ' ('. $locale. ')';
		foreach( [ 'first', 'today', 'yesterday', 'activity-one' ] as $fact )
			if( str_contains( (string) $words['[[/_admin/greeting/fact/'. $fact. ']]'], '%s' ) === true )
				$blanks[] = $fact. ' ('. $locale. ')';
	}
	check( 'the facts that take a number have one blank each, the ones that do not have none'. ( $blanks === [] ? '' : ' - '. implode( ', ', $blanks ) ), $blanks === [] );
}

/** What the unit file ships: the model, how many sentences in each pool, in both languages, and what a sentence may be */
function pinUnitFile( array $seed ): void {

	$model = $seed['model'];
	check( 'the model has the fields of the type, the three of them with their options and the default the pool is read as', array_keys( $model ) === [ 'text', 'phase', 'day', 'occasion', 'hidden' ]
		&& $model['text']['locale'] === true && $model['text']['required'] === true && $model['text']['maxlength'] === 200 && isset( $model['text']['html'] ) === false
		&& $model['phase']['options'] === [ 'any', 'early', 'morning', 'noon', 'afternoon', 'evening', 'night' ] && $model['phase']['default'] === 'any'
		&& $model['day']['options'] === [ 'any', 'monday', 'friday', 'weekend' ] && $model['day']['default'] === 'any'
		&& $model['occasion']['options'] === [ 'none', 'christmas', 'newyear' ] && $model['occasion']['default'] === 'none' && $model['hidden']['type'] === 'boolean' );
	check( '...the type has a title in both languages', trim( (string) ( $seed['title']['de_DE'] ?? '' ) ) !== '' && trim( (string) ( $seed['title']['en_US'] ?? '' ) ) !== '' );

	$ids = array_keys( $seed['de_DE'] );
	check( 'both languages carry the same elements, each once, named as a slug', $ids === array_keys( $seed['en_US'] ) && count( $ids ) === count( array_unique( $ids ) )
		&& count( array_filter( $ids, static fn( string $id ): bool => preg_match( '/^[a-z0-9]+-[0-9]{2}$/', $id ) !== 1 ) ) === 0 );

	$pools = [];
	foreach( $ids as $id ) {
		$when = $seed['*'][$id] ?? [];
		$field = array_keys( $when );
		$pool = $field === [ 'phase' ] ? 'phase:'. $when['phase'] : ( $field === [ 'day' ] ? 'day:'. $when['day'] : ( $field === [ 'occasion' ] ? 'occasion:'. $when['occasion'] : '?' ) );
		$pools[$pool][] = $id;
	}

	$short = [];
	foreach( [ 'phase:early' => 10, 'phase:morning' => 10, 'phase:noon' => 10, 'phase:afternoon' => 10, 'phase:evening' => 10, 'phase:night' => 10, 'day:monday' => 6, 'day:friday' => 6, 'day:weekend' => 6, 'occasion:christmas' => 4, 'occasion:newyear' => 4 ] as $pool => $least ) {
		$count = count( $pools[$pool] ?? [] );
		if( $count < $least )
			$short[] = $pool. ' '. $count;
		foreach( [ 'de_DE', 'en_US' ] as $locale ) {
			$without = count( array_filter( $pools[$pool] ?? [], static fn( string $id ): bool => str_contains( $seed[$locale][$id]['text'], '%s' ) === false ) );
			if( $without < 4 )
				$short[] = $pool. ' without a name in '. $locale. ' '. $without;
		}
	}
	check( 'every pool has its least number of sentences: ten for an hour, six for a weekday, four for an occasion, and four of them need no name'. ( $short === [] ? '' : ' - '. implode( ', ', $short ) ), $short === [] && isset( $pools['?'] ) === false );

	$bad = [];
	foreach( $ids as $id ) {
		foreach( [ 'de_DE', 'en_US' ] as $locale ) {
			$text = $seed[$locale][$id]['text'] ?? '';
			if( $text === '' || mb_strlen( $text ) > 200 || substr_count( $text, '%s' ) > 1 || preg_match( '/[<>\[\]&#\n]/', $text ) === 1 )
				$bad[] = $id. ' '. $locale;
		}
		if( str_contains( $seed['de_DE'][$id]['text'], '%s' ) !== str_contains( $seed['en_US'][$id]['text'], '%s' ) )
			$bad[] = $id. ' blank';
		foreach( $seed['*'][$id] ?? [] as $field => $value )
			if( in_array( $value, $model[$field]['options'] ?? [], true ) === false || $value === $model[$field]['default'] )
				$bad[] = $id. ' '. $field;
	}
	check( 'a sentence is one line of text of at most 200 characters, with a blank for the name at most once, the same in both languages, and says when it fits in words the model has'. ( $bad === [] ? '' : ' - '. implode( ', ', array_slice( $bad, 0, 6 ) ) ), $bad === [] );
}

/** A feature that is off takes the panel and the card away and keeps what the project holds */
function pinDeactivated( array &$appData ): void {

	pinUnitFile( include dirname( __DIR__ ). '/install/elements/greetings.php' );

	$file = ninoSandboxDir( $appData ). '/private/elements/greetings.php';
	$bytes = (string) file_get_contents( $file );

	check( 'deactivation takes the panel away, and keeps the type of sentences and the keys of config.php', \Nino\Features::deactivate( $appData, 'greeting' ) === true
		&& isset( \Nino\Admin\Admin::panels( $appData )['greeting'] ) === false && (string) file_get_contents( $file ) === $bytes );

	$appData['./nino/callbacks'] = [];
	\Nino\Modules::callModules( $appData, 'init' );
	[ $status, $body ] = summary( $appData );
	check( '...and the Dashboard is answered without a head: the class is no module any more, so nothing fires for it', $status === 200 && array_key_exists( 'head', $body ) === false );
}

/** Where the Dashboard has no head the answer carries the Dashboard's own greeting and no area of the feature, and the switches are the ones that greeting reads */
function pinWithoutHead( array &$appData ): void {

	\Nino\Auth::insertUser( $appData, 'visits@example.com', 'visits password', [ '/*' ], '', 'Vera' );
	\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );

	[ $status, $body ] = summary( $appData );
	check( 'the answer has no head and the Dashboard\'s own greeting, once', $status === 200 && array_key_exists( 'head', $body ) === false && is_array( $body['greeting'] ?? null ) === true );

	[ $status ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'lines' => [ 'sentence' => false ] ], 'greeting/save' );
	[ , $body ] = summary( $appData );
	check( 'a line switched off in the panel is off in that greeting: the keys are the ones it reads', $status === 200 && $body['greeting']['show'] === [ 'hello' => true, 'sentence' => false, 'facts' => true ] );
}
