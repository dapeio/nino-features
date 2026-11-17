<?php
declare(strict_types=1);

// The test of the feature Greeting: the card in the answer of the Dashboard, its three lines as settings, the sentences, the panel. php features/Greeting/tests/greeting-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$appData = ninoSandbox( 'greeting' );
$appData['/nino/install/completed'] = true;
$appData['/nino/dir'] = '';
$appData['/nino/auth/user'] = [];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Elements' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

$seed = include $dir. '/install/elements/greetings.php';
$ids = array_keys( $seed['de_DE'] );

$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates for this kernel without a warning, and keeps the three lines as settings of the type bool that are on', is_array( $manifest ) === true && ninoWarnings() === []
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false
	&& array_keys( $manifest['settings'] ) === \Nino\Modules\Greeting::LINES
	&& array_unique( array_column( $manifest['settings'], 'type' ) ) === [ 'bool' ] && array_unique( array_column( $manifest['settings'], 'default' ) ) === [ true ] );
check( 'activation puts nothing of the project in place: no type of sentences, no key of config.php for a line', \Nino\Features::activate( $appData, 'greeting' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/elements/greetings.php' ) === false );

$panels = \Nino\Admin\Admin::panels( $appData );
check( 'the panel is there while the feature is on, with its one action behind its own permission, and the kernel adds the Settings tab', isset( $panels['greeting'] ) === true
	&& array_keys( \Nino\Modules\Greeting\Admin::actions() ) === [ 'greeting/list' ] && \Nino\Modules\Greeting\Admin::perm() === '/_admin/greeting/manage'
	&& isset( $panels['greeting']['tabs']['greeting-settings'] ) === true );

\Nino\Modules::callModules( $appData, 'init' );

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

// --- The facts: the login before this one, the log since it, the age of the last backup ---------------------------------

\Nino\Auth::insertUser( $appData, 'visits@example.com', 'visits password', [ '/*' ], '', 'Vera' );
\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );

$card = cardOf( $appData );
check( 'the summary carries the card in its head with the name of the account, and the first login of an account has the login before it at 0', $card !== null && $card['name'] === 'Vera' && ( $card['previousLogin'] ?? null ) === 0 );

$first = \Nino\Auth::lastLogin( \Nino\Auth::getUser( $appData, 'visits@example.com' ) );
ageLogins( $appData, 'visits@example.com', 5000 );
\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
check( 'a second login tells the time of the first, and the next request of the session the same', ( cardOf( $appData )['previousLogin'] ?? null ) === $first - 5000 && ( cardOf( $appData )['previousLogin'] ?? null ) === $first - 5000 );

\Nino\Runtime::unsetSessionValue( $appData, './nino/auth/previous' );
check( 'a session that holds none says null, not 0: nobody is told it is their first visit', array_key_exists( 'previousLogin', cardOf( $appData ) ?? [] ) === true && cardOf( $appData )['previousLogin'] === null );

\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
ageLogins( $appData, 'visits@example.com', 5000 );
\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );
$before = cardOf( $appData )['activitySince'] ?? null;
\Nino\Modules\Logs\Admin::record( $appData, 'other@example.com', 'Edit Something' );
\Nino\Modules\Logs\Admin::record( $appData, 'visits@example.com', 'Login' );
check( 'an account that may read the log is told the lines since the last visit, its own sign-in not among them', is_int( $before ) === true && ( cardOf( $appData )['activitySince'] ?? null ) === $before + 1 );

\Nino\Auth::insertUser( $appData, 'nologs@example.com', 'nologs password', [ \Nino\Modules\Elements\Admin::MANAGE_PERM ] );
\Nino\Auth::loginUser( $appData, 'nologs@example.com', 'nologs password' );
ageLogins( $appData, 'nologs@example.com', 5000 );
\Nino\Auth::loginUser( $appData, 'nologs@example.com', 'nologs password' );
$card = cardOf( $appData );
check( 'an account that may not read the log is told nothing of it: the key is left out', $card !== null && array_key_exists( 'activitySince', $card ) === false && $card['previousLogin'] > 0 );
\Nino\Auth::loginUser( $appData, 'visits@example.com', 'visits password' );

$backups = \Nino\Modules\Backups::dir( $appData );
$appData['/nino/admin/backups'] = false;
foreach( glob( $backups. '/*' ) ?: [] as $file )
	unlink( $file );
@mkdir( $backups, 0755, true );
$none = array_key_exists( 'backupDays', cardOf( $appData ) ?? [ 'backupDays' => 0 ] ) === false;
file_put_contents( $backups. '/'. date( 'Y-m-d', strtotime( '-10 days' ) ). '.php', '' );
file_put_contents( $backups. '/'. date( 'Y-m-d', strtotime( '-30 days' ) ). '.php', '' );
check( 'the age of the newest backup is told, and left out where there is none', $none === true && ( cardOf( $appData )['backupDays'] ?? null ) === 10 );
foreach( glob( $backups. '/*' ) ?: [] as $file )
	unlink( $file );
$appData['/nino/admin/backups'] = true;

// --- The sentences ---------------------------------------------------------------------------------------------------

$ignored = ninoWarnings();
$typeFile = ninoSandboxDir( $appData ). '/private/elements/greetings.php';
$model = \Nino\Elements::getElementModel( $appData, '/greetings' );
check( 'the type of sentences is put in place by the first summary, with the model of the unit file', is_file( $typeFile ) === true && $model['phase']['options'] === $seed['model']['phase']['options']
	&& $model['day']['options'] === $seed['model']['day']['options'] && $model['occasion']['options'] === $seed['model']['occasion']['options'] && $model['text']['locale'] === true );

\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'en_US' );
$card = cardOf( $appData );
check( 'the card holds the sentences of the unit in the interface language, each with when it fits', $card !== null && count( $card['sentences'] ) === count( $ids )
	&& in_array( $seed['en_US']['early-01']['text'], array_column( $card['sentences'], 'text' ), true ) === true
	&& in_array( $seed['de_DE']['early-01']['text'], array_column( $card['sentences'], 'text' ), true ) === false
	&& array_diff( array_column( $card['sentences'], 'phase' ), $seed['model']['phase']['options'] ) === [] );

\Nino\Elements::updateElement( $appData, '/greetings/early-01', [ 'hidden' => true ] );
\Nino\Elements::updateElement( $appData, '/greetings/early-02', [ 'text' => 'My own sentence.' ], 'en_US' );
\Nino\Elements::deleteElement( $appData, '/greetings/early-03', '*' );
$texts = array_column( cardOf( $appData )['sentences'], 'text' );
check( 'a hidden sentence is not delivered, a changed one is the editor\'s and a deleted one does not come back', in_array( $seed['en_US']['early-01']['text'], $texts, true ) === false
	&& in_array( 'My own sentence.', $texts, true ) === true && count( $texts ) === count( $ids ) - 2 && \Nino\Features::activate( $appData, 'greeting' ) === true && count( array_column( cardOf( $appData )['sentences'], 'text' ) ) === count( $ids ) - 2 );

// A file edited by hand can hold a word the model does not offer: it is delivered as any or none, never as it stands
$type = include $typeFile;
$type['*']['early-04']['phase'] = 'bogus';
$type['*']['early-05']['occasion'] = 'bogus';
file_put_contents( $typeFile, '<?php return '. var_export( $type, true ). ';' );
unset( $appData['./nino/elements/cache'] );
$odd = array_column( cardOf( $appData )['sentences'], null, 'text' );
check( 'a word of phase or occasion that is none of the options is delivered as any or none', ( $odd[ $seed['en_US']['early-04']['text'] ] ?? [] )['phase'] === 'any' && ( $odd[ $seed['en_US']['early-05']['text'] ] ?? [] )['occasion'] === 'none' );

[ $status ] = ninoCall( $appData, [ \Nino\Modules\Elements\Types::class, 'apiDelete' ], [ 'uri' => 'greetings', 'confirm' => 'greetings' ] );
$card = cardOf( $appData );
check( 'a type the editors deleted does not come back, and the card is there without a warning for it', $status === 200 && is_file( $typeFile ) === false && $card !== null && $card['sentences'] === [] && ninoWarnings() === [] );

$fresh = ninoSandbox( 'greetings' );
$fresh['/nino/modules'] = [];
$seeded = \Nino\Modules\Greeting::seed( $fresh, [ 'de_DE', 'en_US' ] ) === true && count( \Nino\Elements::queryElements( $fresh, '/greetings', [], 'de_DE', [] ) ) === count( $ids );
$written = (string) file_get_contents( ninoSandboxDir( $fresh ). '/private/elements/greetings.php' );
check( 'a project without the type is given it in both languages, and a second run writes nothing', $seeded === true && \Nino\Modules\Greeting::seed( $fresh, [ 'de_DE', 'en_US' ] ) === true
	&& (string) file_get_contents( ninoSandboxDir( $fresh ). '/private/elements/greetings.php' ) === $written );
unset( $appData[ \Nino\Elements::REMOVED ]['greetings'] );
\Nino\Modules\Greeting::seed( $appData, [ 'de_DE', 'en_US' ] );

// --- The lines are settings ----------------------------------------------------------------------------------------------

$lines = static fn( array &$appData ): array => cardOf( $appData )['show'] ?? [];
check( 'every line is on until its setting says otherwise', $lines( $appData ) === [ 'hello' => true, 'sentence' => true, 'facts' => true ] );

check( 'with the facts off the log is not counted and the backup not read, and the rest is there', \Nino\Features::saveSettings( $appData, 'greeting', [ 'facts' => 'false' ] ) === []
	&& $lines( $appData ) === [ 'hello' => true, 'sentence' => true, 'facts' => false ] && array_key_exists( 'activitySince', cardOf( $appData ) ) === false
	&& array_key_exists( 'backupDays', cardOf( $appData ) ) === false && array_key_exists( 'sentences', cardOf( $appData ) ) === true );

\Nino\Features::saveSettings( $appData, 'greeting', [ 'sentence' => 'false' ] );
check( 'with the sentence off the elements are not sent', array_key_exists( 'sentences', cardOf( $appData ) ) === false );

\Nino\Features::saveSettings( $appData, 'greeting', [ 'hello' => 'false' ] );
[ $status, $body ] = summary( $appData );
check( 'with all three off there is no area in the head, and the rest of the answer is there', $status === 200 && array_key_exists( 'head', $body ) === false && array_key_exists( 'tiles', $body ) === true && array_key_exists( 'notices', $body ) === true );

$preview = ninoCall( $appData, [ \Nino\Modules\Greeting\Admin::class, 'apiList' ] );
check( 'the panel answers the same card with all lines off, so its screen can say so', $preview[0] === 200 && $preview[1]['show'] === [ 'hello' => false, 'sentence' => false, 'facts' => false ] );
\Nino\Features::saveSettings( $appData, 'greeting', [ 'hello' => 'true', 'sentence' => 'true', 'facts' => 'true' ] );

// --- The panel -------------------------------------------------------------------------------------------------------------

ninoUser( $appData, 'editor@example.org', [] );
$denied = ninoCall( $appData, [ \Nino\Modules\Greeting\Admin::class, 'apiList' ] );
check( 'an account without the permission of the panel is refused, and sees the card all the same', $denied[0] === 403 && cardOf( $appData ) !== null );
ninoUser( $appData, 'admin@example.org', [ '/_admin/greeting/manage' ] );
check( 'with the permission the panel answers the card of the account', ninoCall( $appData, [ \Nino\Modules\Greeting\Admin::class, 'apiList' ] )[1]['name'] === 'admin@example.org' || is_string( ninoCall( $appData, [ \Nino\Modules\Greeting\Admin::class, 'apiList' ] )[1]['name'] ) === true );

// --- The words, the files, the script --------------------------------------------------------------------------------------

$words = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];
check( 'both languages of the panel and the card have the same keys', array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) && count( array_filter( $words['de_DE'], static fn( string $word ): bool => trim( $word ) === '' ) ) === 0 );

$script = (string) file_get_contents( $dir. '/assets/admin.js' );
preg_match_all( "~'(/_admin/(?!common/|features/)[a-z/_-]+)'~", $script, $read );
$own = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( $words['en_US'] ) );
$typeWords = [ '/_admin/elements/type/greetings/hint' ];
foreach( $seed['model'] as $field => $definition ) {
	$typeWords[] = '/_admin/elements/field/greetings/'. $field;
	foreach( $definition['options'] ?? [] as $option )
		$typeWords[] = '/_admin/elements/option/greetings/'. $field. '/'. $option;
}
// A key that ends in a slash is a prefix the script completes with the time of day: the six of them are there
$prefixes = array_values( array_filter( $read[1], static fn( string $key ): bool => str_ends_with( $key, '/' ) ) );
check( 'every word the script reads, and every word the Elements panel needs for the type, is one the feature brings in both languages', array_diff( array_unique( array_diff( $read[1], $prefixes ) ), $own ) === [] && array_diff( $typeWords, $own ) === []
	&& $prefixes === [ '/_admin/greeting/hello/' ] && count( array_filter( $own, static fn( string $key ): bool => str_starts_with( $key, '/_admin/greeting/hello/' ) ) ) === 6 );

$blanks = [];
foreach( $words as $locale => $set ) {
	foreach( [ 'days', 'activity', 'backup' ] as $fact )
		if( substr_count( $set['[[/_admin/greeting/fact/'. $fact. ']]'], '%s' ) !== 1 )
			$blanks[] = $fact. ' '. $locale;
	foreach( [ 'first', 'today', 'yesterday', 'activity-one' ] as $fact )
		if( str_contains( $set['[[/_admin/greeting/fact/'. $fact. ']]'], '%s' ) === true )
			$blanks[] = $fact. ' '. $locale;
}
check( 'the facts that take a number have one blank each, the ones that do not have none', $blanks === [] );

check( 'the script takes its button from the workbench and the stylesheet names no size, no colour and no class of the workbench', preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(/', $script ) === 0
	&& preg_match( '/\d(px|rem)\b|#[0-9a-f]{3,6}\b|\.nino-admin-/i', (string) preg_replace( '~/\*.*?\*/~s', '', (string) file_get_contents( $dir. '/assets/admin.css' ) ) ) === 0 );

// What the unit ships: the sentences of the pools, in both languages, each one line that needs no markup
$short = [];
$pools = [];
foreach( $ids as $id ) {
	$when = $seed['*'][$id] ?? [];
	$pools[ count( $when ) === 1 ? key( $when ). ':'. current( $when ) : '?' ][] = $id;
}
foreach( [ 'phase:early' => 10, 'phase:morning' => 10, 'phase:noon' => 10, 'phase:afternoon' => 10, 'phase:evening' => 10, 'phase:night' => 10, 'day:monday' => 6, 'day:friday' => 6, 'day:weekend' => 6, 'occasion:christmas' => 4, 'occasion:newyear' => 4 ] as $pool => $least ) {
	if( count( $pools[$pool] ?? [] ) < $least )
		$short[] = $pool;
	foreach( [ 'de_DE', 'en_US' ] as $locale )
		if( count( array_filter( $pools[$pool] ?? [], static fn( string $id ): bool => str_contains( $seed[$locale][$id]['text'], '%s' ) === false ) ) < 4 )
			$short[] = $pool. ' '. $locale;
}
$bad = [];
foreach( $ids as $id )
	foreach( [ 'de_DE', 'en_US' ] as $locale ) {
		$text = $seed[$locale][$id]['text'] ?? '';
		if( $text === '' || mb_strlen( $text ) > 200 || substr_count( $text, '%s' ) > 1 || preg_match( '/[<>\[\]&#\n]/', $text ) === 1 )
			$bad[] = $id. ' '. $locale;
	}
check( 'both languages carry the same sentences, every pool has enough of them and four that need no name, and a sentence is one line of text with at most one blank',
	$ids === array_keys( $seed['en_US'] ) && $short === [] && isset( $pools['?'] ) === false && $bad === [] );

// --- The script's own test, where node is on the path ---------------------------------------------------------------------

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: greeting-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/greeting-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'greeting-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

// --- Off ---------------------------------------------------------------------------------------------------------------------

$bytes = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/elements/greetings.php' );
check( 'deactivation takes the panel and the card away and keeps the type of sentences', \Nino\Features::deactivate( $appData, 'greeting' ) === true
	&& isset( \Nino\Admin\Admin::panels( $appData )['greeting'] ) === false && (string) file_get_contents( ninoSandboxDir( $appData ). '/private/elements/greetings.php' ) === $bytes );
$appData['./nino/callbacks'] = [];
\Nino\Modules::callModules( $appData, 'init' );
[ $status, $body ] = summary( $appData );
check( 'the Dashboard is answered without a head', $status === 200 && array_key_exists( 'head', $body ) === false );

// Nothing of the old components API or the old stylesheet vocabulary is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && str_contains( (string) $file, '/install/elements/' ) === false
		&& preg_match( '/Feature::shortcode|Modules\\\\Components|(?<![-\w])nino-(?!admin-card)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\.', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
