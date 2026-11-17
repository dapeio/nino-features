<?php
declare(strict_types=1);

/**
 *	Nino features
 *	sysadmin-smoke.php		Contract test for the Sysadmin feature (Modules\Sysadmin): the manifest and the
 *												activation through \Nino\Features; what a request pays when there is nothing to do;
 *												the problem clock (a new finding, a worse one, one that goes and comes back, the log as
 *												an event, the distance of two mails) and the beat clock (the slot, the monthly step,
 *												both due at once); a send that fails and is not tried again at every request; two runs
 *												at once that make one mail; the mail itself - it says what the Dashboard says, it holds
 *												no address of a person, no path of the server and no secret; the panel with its permission
 *												and its throttle; the work after the response in a child process. Mail goes through the
 *												transport callback of the kernel, so nothing is sent. Travels with the feature and runs
 *												against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Sysadmin/tests/sysadmin-smoke.php
 *	       NINO_ROOT=../nino php features/Sysadmin/tests/sysadmin-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$feature = dirname( __DIR__ );

// A Nino older than the one the feature asks for claims nothing about it, and cannot run it: the kernel reads the
// Dashboard's notices as data from the release that has \Nino\Modules\Dashboard\Admin::notices()
$asks = (string) ( (array) include $feature. '/feature.php' )['nino'];

if( \Nino\Features::satisfies( $asks ) === false ) {
	echo "  --  - this Nino (". \Nino\VERSION. ") is older than the one the feature asks for ($asks): the checks were NOT run\n";
	exit( 0 );
}

$appData = ninoSandbox( 'sysadmin' );
$sandbox = ninoSandboxDir( $appData );
$appData['/nino/install/completed']	= true;
$appData['/nino/dir']								= '';
$appData['/nino/auth/user']					= [];
$appData['/nino/auth/maxtries']			= 3;
$appData['/nino/auth/cooldown']			= 3600;
$appData['/nino/modules']						= [ '\\Nino\\Modules\\Elements' ];
$_SERVER['REMOTE_ADDR']							= '127.0.0.1';
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

/** The mail the transport took, as { to, subject, body }: what the kernel hands a transport */
$mails = [];
$transport = true;
$onSend = null;
$failFor = null;

\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$transport, &$onSend, &$failFor ): void {

	if( $onSend !== null )
		$onSend();

	if( $transport === true )
		$mails[] = [ 'to' => $mail['to'], 'subject' => $mail['subject'], 'body' => $mail['body'] ];

	$mail['sent'] = $transport === true ? ( $failFor === null || $mail['to'] !== $failFor ) : false;
} );

// No cap on the mails of one client: the clock of the test makes more of them in an hour than a site would
$appData['/nino/mail/cap'] = 0;

// A run is the work after the response, run in this one: the test is the one who looks at the result
$appData['./sysadmin/inline']	= true;

$base = (int) strtotime( date( 'Y-m-d' ). ' 06:00:00' );
$hour = 3600;
$day	= 86400;

/** Set the clock of the feature */
$at = static function( int $time ) use ( &$appData ): void { $appData['./sysadmin/now'] = $time; \Nino\Feature::forget( $appData, 'sysmail-system', 'site' ); };

/** What the mails said, and forget them */
$taken = static function() use ( &$mails ): array { $took = $mails; $mails = []; return $took; };

/** The state file as it is on disk, read afresh */
$stored = static function() use ( &$appData ): array {
	clearstatcache();
	return (array) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Sysadmin::STATE, [] );
};

/** Free the mail cap, which counts every send of this client */
$uncap = static function() use ( &$appData ): void { \Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' ); \Nino\Feature::forget( $appData, 'sysmail-system', 'site' ); };

/** Put a settings change through the kernel's own validation */
$set = static function( array $posted ) use ( &$appData ): array { return \Nino\Features::saveSettings( $appData, 'sysadmin', $posted ); };

// A disk with room: a test says when it has none
$appData['./sysadmin/disk'] = [ 50 * 1073741824, 100 * 1073741824 ];


// --- The manifest, the unit and the words ---------------------------------------------

echo "Sysadmin - the manifest, the unit and the words\n";

$manifest = \Nino\Features::manifest( $feature );
check( 'the manifest validates without a warning', is_array( $manifest ) === true && ninoWarnings() === [] );
check( 'the feature has a manual in every section, the settings of the brief, and the data file its state is in', is_array( $manifest ) === true
	&& array_keys( $manifest['settings'] ) === [ 'to', 'problemGap', 'beat' ] && $manifest['data'] === [ \Nino\Modules\Sysadmin::STATE ]
	&& array_keys( $manifest['manual'] ) === \Nino\Features::MANUAL_SECTIONS && $manifest['category'] === 'system' );
check( 'its constraint names the kernel of Nino 2 and it brings no components', is_array( $manifest ) === true
	&& $manifest['version'] === '2.0.0' && $manifest['nino'] === '^2.0' && \Nino\Features::satisfies( $manifest['nino'] ) === true && isset( $manifest['components'] ) === false );
check( 'the defaults are the brief\'s: no address, a problem mail every 24 hours at most, a sign of life every week', is_array( $manifest ) === true
	&& $manifest['settings']['to']['default'] === '' && $manifest['settings']['problemGap']['default'] === '24' && $manifest['settings']['beat']['default'] === 'weekly'
	&& array_keys( $manifest['settings']['beat']['options'] ) === [ 'off', 'daily', 'weekly', 'monthly' ] );

check( 'activation succeeds, lists the class and records the version', \Nino\Features::activate( $appData, 'sysadmin' ) === true
	&& \Nino\Features::get( $appData, 'sysadmin' )['installed'] === '2.0.0' && in_array( '\\Nino\\Modules\\Sysadmin', $appData['/nino/modules'], true ) === true );
check( 'the unit brings no template, no route and no element type of its own - the mail is nothing a visitor opens', array_diff( array_keys( include $feature. '/install/manifest.php' ), [ 'elements' ] ) === [] );

$words = [ 'en_US' => (array) include $feature. '/install/text/en_US.php', 'de_DE' => (array) include $feature. '/install/text/de_DE.php' ];
check( 'the words of the mail are in both languages under the same keys, none of them empty and with the same places to fill', array_keys( $words['en_US'] ) === array_keys( $words['de_DE'] )
	&& count( array_filter( array_merge( $words['en_US'], $words['de_DE'] ), static fn( string $text ): bool => trim( $text ) === '' ) ) === 0
	&& count( array_filter( array_keys( $words['en_US'] ), static fn( string $key ): bool => substr_count( $words['en_US'][$key], '%s' ) !== substr_count( $words['de_DE'][$key], '%s' ) ) ) === 0 );
check( 'activation has put them into the project\'s texts of both languages, where the project may change them', ( static function() use ( &$appData, $words ): bool {
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$project = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
		foreach( $words[$locale] as $key => $text )
			if( ( $project[$key] ?? null ) !== $text )
				return false;
	}
	return true;
} )() );

// A page request has no workbench: what the feature runs in one reaches no class that only a request of the workbench has
// (\Nino\Admin\*)
$runtime = implode( '', array_map( static fn( mixed $token ): string => is_array( $token ) === true ? ( in_array( $token[0], [ T_COMMENT, T_DOC_COMMENT ], true ) === true ? '' : $token[1] ) : $token,
	array_merge( token_get_all( (string) file_get_contents( $feature. '/Sysadmin.php' ) ), token_get_all( (string) file_get_contents( $feature. '/Report/Report.php' ) ) ) ) );
check( 'the class and the report name no class of the workbench shell, which a page request does not have', preg_match( '/Nino\\\\Admin\\\\/', $runtime ) === 0 );

// Every name the code asks the words for is one of them: the literal ones by reading the source, the families by their members
$source = (string) file_get_contents( $feature. '/Sysadmin.php' ). (string) file_get_contents( $feature. '/Report/Report.php' );
preg_match_all( "/(?:say|\\\$say)\(\s*'([a-z0-9\/-]+)'/", $source, $literal );
$family = [];
foreach( [ 'kind', 'subject' ] as $name )
	foreach( [ 'problem', 'beat', 'test' ] as $kind )
		$family[] = 'mail/'. $name. '-'. $kind;
foreach( \Nino\Modules\Sysadmin\Report::RANK_WORDS as $rank )
	$family[] = 'rank/'. $rank;
foreach( [ 'cause/time' => 1, 'cause/memory' => 1, 'cause/error' => 1, 'cause/none' => 1 ] as $cause => $unused )
	$family[] = $cause;
foreach( [ 'run-aborted', 'backup-stopped', 'backup-failed', 'manifest-active', 'manifest-off', 'form-cap-hour', 'form-cap-month', 'disk', 'log-errors' ] as $finding )
	$family[] = 'finding/'. $finding;
foreach( [ 'hour', 'month' ] as $cap )
	$family[] = 'operation/cap-'. $cap;
$looked = array_filter( $literal[1], static fn( string $name ): bool => str_contains( $name, '/' ) === true && str_ends_with( $name, '/' ) === false && str_ends_with( $name, '-' ) === false );
$missing = array_filter( array_unique( array_merge( $looked, $family ) ), static fn( string $name ): bool => isset( $words['en_US']['[[/feature/sysadmin/'. $name. ']]'] ) === false );
check( 'every name the code looks up is a word of the unit'. ( $missing === [] ? '' : ' - missing: '. implode( ', ', $missing ) ), $missing === [] && count( $looked ) > 20 );
// ...and every word of the unit is looked up by something: one that nothing asks for is a word nobody can change the mail with
$asked = array_map( static fn( string $name ): string => '[[/feature/sysadmin/'. $name. ']]', array_merge( $looked, $family ) );
$unused = array_diff( array_keys( $words['en_US'] ), $asked );
check( 'every word of the unit is one the code looks up'. ( $unused === [] ? '' : ' - unused: '. implode( ', ', $unused ) ), $unused === [] );

echo "\n";


// --- A request with nothing to do pays nothing -----------------------------------------

echo "Sysadmin - what a request pays\n";

$data = \Nino\Filesystem::path( $appData, '/data' );
$listing = static function() use ( $data ): array {
	clearstatcache();
	$files = [];
	foreach( array_merge( glob( $data. '/*' ) ?: [], glob( $data. '/.*' ) ?: [] ) as $file )
		if( is_file( $file ) === true )
			$files[basename( $file )] = filemtime( $file ). ':'. filesize( $file );
	return $files;
};

$at( $base );
$before = $listing();
unset( $appData['./nino/filesystem/cache'][ \Nino\Modules\Sysadmin::STATE ] );
\Nino\Modules\Sysadmin::init( $appData );
check( 'without an address the feature does nothing: no file written, no lock made, no mail - and its file not even read', $listing() === $before && $mails === [] && \Nino\Modules\Sysadmin::recipients( $appData ) === []
	&& isset( $appData['./nino/filesystem/cache'][ \Nino\Modules\Sysadmin::STATE ] ) === false );
check( '...and run() says so, whoever asks', \Nino\Modules\Sysadmin::run( $appData ) === 'none' && $listing() === $before );

check( 'the address is a setting the kernel checks: a list of up to five, each of them an address', $set( [ 'to' => 'a@example.org, b@example.org' ] ) === []
	&& \Nino\Modules\Sysadmin::recipients( $appData ) === [ 'a@example.org', 'b@example.org' ] );
check( '...text that is none, six addresses, a group of addresses with a name and a semicolon are refused, and what was saved stays', array_keys( $set( [ 'to' => 'not an address' ] ) ) === [ 'to' ]
	&& array_keys( $set( [ 'to' => 'a@x.org, b@x.org, c@x.org, d@x.org, e@x.org, f@x.org' ] ) ) === [ 'to' ]
	&& array_keys( $set( [ 'to' => 'Ann <a@x.org>' ] ) ) === [ 'to' ] && array_keys( $set( [ 'to' => 'a@x.org; b@x.org' ] ) ) === [ 'to' ]
	&& \Nino\Modules\Sysadmin::recipients( $appData ) === [ 'a@example.org', 'b@example.org' ] );
check( '...the distance and the rhythm take the options of the manifest and nothing else', $set( [ 'problemGap' => '6', 'beat' => 'daily' ] ) === [] && \Nino\Modules\Sysadmin::gap( $appData ) === 6 * $hour && \Nino\Modules\Sysadmin::beat( $appData ) === 'daily'
	&& array_keys( $set( [ 'problemGap' => '5' ] ) ) === [ 'problemGap' ] && array_keys( $set( [ 'beat' => 'hourly' ] ) ) === [ 'beat' ] && \Nino\Modules\Sysadmin::gap( $appData ) === 6 * $hour );
// What the setting takes is what the send delivers: an address one takes and the other leaves out would be a mail that never comes
$candidates = [ 'a@b.de', 'first.last@sub.example.org', 'x+tag@example.com', "o'neil@example.org", 'a..b@example.org', '.a@example.org', 'a.@example.org', 'a@example', 'a@-b.de', 'a@b-.de', 'a@b..de', 'Bcc:x@y.de', 'ünder@example.org', 'a@bücher.de', 'a@xn--bcher-kva.de', 'root@localhost', 'a%40b@example.org', '"a b"@example.org', 'a b@example.org', 'a@b.de>', 'a@[127.0.0.1]', str_repeat( 'x', 70 ). '@example.org' ];
$disagree = [];
foreach( $candidates as $candidate ) {
	$accepted = $set( [ 'to' => $candidate ] ) === [];
	$appData['/nino/features']['sysadmin']['settings']['to'] = $candidate;
	$delivered = \Nino\Modules\Sysadmin::recipients( $appData ) === [ $candidate ];
	if( $accepted !== $delivered )
		$disagree[] = $candidate;
}
check( 'every address the setting takes is delivered and every one it refuses is not - none of them is in one and not in the other'. ( $disagree === [] ? '' : ' - '. implode( ' | ', $disagree ) ), $disagree === [] );
check( '...a name before the address, a second address that is no address and addresses of other letters are refused at the setting, and a list of five is taken', array_keys( $set( [ 'to' => 'a@b.de, Bcc:x@y.de' ] ) ) === [ 'to' ] && array_keys( $set( [ 'to' => 'ünder@example.org' ] ) ) === [ 'to' ]
	&& $set( [ 'to' => 'a@x.org, b@x.org, c@x.org, d@x.org, e@x.org' ] ) === [] && count( \Nino\Modules\Sysadmin::recipients( $appData ) ) === 5 && \Nino\Modules\Sysadmin::RECIPIENTS === 5 );
$appData['/nino/features']['sysadmin']['settings']['to'] = 'a@example.org, b@example.org';
$set( [ 'to' => 'a@example.org, b@example.org', 'problemGap' => '24', 'beat' => 'weekly' ] );

echo "\n";


// --- The first run, and the clock of the problem mail -----------------------------------

echo "Sysadmin - the problem mail\n";

// A finding the Dashboard has: a text a language lacks
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [ '[[/sysadmin-test/gap]]' => 'Eine Lücke' ] + (array) \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] ) );
$gap = static fn(): array => array_values( array_filter( \Nino\Modules\Dashboard\Admin::notices( $appData, true ), static fn( array $notice ): bool => $notice['key'] === 'untranslated:en_US' ) );
check( 'the Dashboard has a finding of its own to start from', count( $gap() ) === 1 );

$at( $base );
$uncap();
\Nino\Modules\Sysadmin::init( $appData );
$first = $taken();
$state = $stored();
check( 'the first request after the address is set makes the state and the first report: one mail to each address, nothing told before, so what is found is new', count( $first ) === 2
	&& array_column( $first, 'to' ) === [ 'a@example.org', 'b@example.org' ] && $first[0]['subject'] === $first[1]['subject'] && $first[0]['body'] === $first[1]['body']
	&& $state['since'] === $base && $state['lastProblem'] === $base && $state['lastMail'] === $base && $state['lastEval'] === $base && $state['try'] === 0 );
check( '...the beat clock starts at the first run and the first sign of life is one rhythm away, not at once', $state['lastBeat'] === $base && \Nino\Modules\Sysadmin::beatDue( $appData, \Nino\Modules\Sysadmin::state( $appData ), $base ) === false
	&& \Nino\Modules\Sysadmin::beatDue( $appData, \Nino\Modules\Sysadmin::state( $appData ), $base + 7 * $day ) === true );
check( '...the state holds times, keys and ranks and nothing a visitor typed', array_diff( array_keys( $state ), [ 'format', 'since', 'lastEval', 'lastProblem', 'lastBeat', 'lastMail', 'reported', 'try', 'failed', 'fails', 'aborts', 'delivered', 'tested' ] ) === []
	&& isset( $state['reported']['dashboard:untranslated:en_US'] ) === true );

// The mail says what the Dashboard says
$sentence = \Nino\Modules\Dashboard\Admin::sentence( $appData, $gap()[0], 'de_DE' );
$text = html_entity_decode( strip_tags( $first[0]['body'] ), ENT_QUOTES | ENT_HTML5 );
check( 'the mail says the sentence of the Dashboard for the finding, in the native language of the site, and marks it as new', str_contains( $text, $sentence ) === true && $sentence !== '' );
check( '...it is html that holds the text in one block and no markup of a finding: whatever is in the text is escaped', str_starts_with( $first[0]['body'], '<!doctype html>' ) === true && substr_count( $first[0]['body'], '<pre' ) === 1
	&& preg_match( '/<(?!\/?(?:html|body|pre)\b)[a-z]/i', $first[0]['body'] ) === 0 );

// A second request in the same minute: nothing
$at( $base + 60 );
$before = $listing();
unset( $appData['./nino/features/all'] );
\Nino\Modules\Sysadmin::init( $appData );
check( 'the next request is not due, makes no mail and writes nothing: the report was not even looked at', $taken() === [] && $listing() === $before && $stored()['lastEval'] === $base );
check( '...and it read no manifest of the catalogue, which the settings of the kernel do on the first call of a request', isset( $appData['./nino/features/all'] ) === false );
\Nino\Features::all( $appData );
$at( $base + $hour );
$before = $listing();
\Nino\Modules\Sysadmin::init( $appData );
check( '...and an hour on it is not due either: the distance of two problem mails is 24 hours', $taken() === [] && $listing() === $before && $stored()['lastEval'] === $base );

// The distance has passed, nothing new: looked at, nothing said
$at( $base + $day );
\Nino\Modules\Sysadmin::init( $appData );
$state = $stored();
check( 'when the distance has passed and nothing is new, the report is looked at and no mail goes out: the same finding is not told again', $taken() === [] && $state['lastEval'] === $base + $day && $state['lastProblem'] === $base );

// A finding that was not there: the mail of the Dashboard failing
$uncap();
$transport = false;
\Nino\Mail::send( $appData, 'somebody@example.org', 'x', 'y', '' );
$transport = true;
check( 'a mail that fails is a notice of the Dashboard', array_filter( \Nino\Modules\Dashboard\Admin::notices( $appData, true ), static fn( array $notice ): bool => $notice['key'] === 'mail' ) !== [] );

$at( $base + $day + 30 * 60 );
\Nino\Modules\Sysadmin::init( $appData );
check( 'a new finding waits for the clock: half an hour after the last look is not due', $taken() === [] );
$at( $base + $day + $hour );
$uncap();
$mailNotice = array_values( array_filter( \Nino\Modules\Dashboard\Admin::notices( $appData, true ), static fn( array $notice ): bool => $notice['key'] === 'mail' ) )[0];
\Nino\Modules\Sysadmin::init( $appData );
$second = $taken();
$state = $stored();
check( 'an hour after it, a new finding is a problem mail: it carries all findings, marks the new one and lists the old', count( $second ) === 2
	&& $state['lastProblem'] === $base + $day + $hour && isset( $state['reported']['dashboard:mail'], $state['reported']['dashboard:untranslated:en_US'] ) === true );
$secondText = html_entity_decode( strip_tags( $second[0]['body'] ), ENT_QUOTES | ENT_HTML5 );
check( '...the new one is marked, the one that was told before is not', str_contains( $secondText, \Nino\Modules\Dashboard\Admin::sentence( $appData, $mailNotice, 'de_DE' ) ) === true
	&& str_contains( $secondText, $sentence ) === true && substr_count( $secondText, '('. $words['de_DE']['[[/feature/sysadmin/mail/new]]']. ')' ) === 1 );

// The same findings again after the distance: nothing
$at( $base + 3 * $day );
\Nino\Modules\Sysadmin::init( $appData );
check( 'both findings hold, and the distance has passed twice: no mail', $taken() === [] );

// A finding that gets worse: the disk, from a warning to an error
$appData['./sysadmin/disk'] = [ 8 * 1073741824, 100 * 1073741824 ];
$at( $base + 4 * $day );
\Nino\Modules\Sysadmin::init( $appData );
$disk = $taken();
$state = $stored();
check( 'a finding that is new and a warning is a problem mail', count( $disk ) === 2 && ( $state['reported']['disk'] ?? 0 ) === \Nino\Modules\Sysadmin\Report::WARNING );
$appData['./sysadmin/disk'] = [ 2 * 1073741824, 100 * 1073741824 ];
$at( $base + 5 * $day );
\Nino\Modules\Sysadmin::init( $appData );
$worse = $taken();
$state = $stored();
check( 'the same finding with a higher rank is a problem mail again, and the rank is what is remembered', count( $worse ) === 2 && $state['reported']['disk'] === \Nino\Modules\Sysadmin\Report::ERROR );
$appData['./sysadmin/disk'] = [ 40 * 1073741824, 100 * 1073741824 ];
$at( $base + 6 * $day );
\Nino\Modules\Sysadmin::init( $appData );
$state = $stored();
check( 'a finding that is gone leaves the memory when the report is looked at, and sends nothing', $taken() === [] && isset( $state['reported']['disk'] ) === false );
$appData['./sysadmin/disk'] = [ 8 * 1073741824, 100 * 1073741824 ];
$at( $base + 7 * $day );
\Nino\Modules\Sysadmin::init( $appData );
check( '...so that it is new when it comes back', count( $taken() ) === 2 );

// The shorter distance of the settings
$set( [ 'problemGap' => '1' ] );
$appData['./sysadmin/disk'] = [ 40 * 1073741824, 100 * 1073741824 ];
$at( $base + 7 * $day + $hour );
\Nino\Modules\Sysadmin::init( $appData );
$taken();
$appData['./sysadmin/disk'] = [ 2 * 1073741824, 100 * 1073741824 ];
$at( $base + 7 * $day + 2 * $hour );
\Nino\Modules\Sysadmin::init( $appData );
check( 'a distance of one hour makes the problem mail due after an hour - and no sooner than the look at the report, which is an hour too', count( $taken() ) === 2 );
$set( [ 'problemGap' => '24' ] );
$appData['./sysadmin/disk'] = [ 50 * 1073741824, 100 * 1073741824 ];

echo "\n";


// --- The log: an event, and what a mail may say of it ------------------------------------

echo "Sysadmin - the log\n";

$now = $base + 9 * $day;
$logPath = '/data/logs.'. date( 'Y-m', $now ). '.php';
$entry = static fn( int $time, mixed $type, string $message ): array => [ 'type' => $type, 'message' => $message, 'file' => '/srv/www/secret-project/app/Thing.php', 'line' => 7, 'date' => date( 'Y-m-d H:i:s', $time ) ];

$long = 'Failed for ana@example.org from 203.0.113.9 and 2001:db8::7 reading '. $sandbox. '/private/data/x.php and /var/www/secret-site/private/other.php?token=abc: '. str_repeat( 'very long text ', 20 );
\Nino\Filesystem::putFileContent( $appData, $logPath, [
	$entry( $now - 2 * $day, E_WARNING, 'before the last mail 1' ),
	$entry( $now - $hour, E_USER_WARNING, 'Backup failed: disk 1 full' ),
	$entry( $now - $hour, E_USER_WARNING, 'Backup failed: disk 22 full' ),
	$entry( $now - $hour, 'Exception', $long ),
	$entry( $now - $hour, E_WARNING, 'An engine warning is a 500' ),
	$entry( $now - $hour, E_USER_WARNING, 'Markup <script>alert(1)</script> & more' ),
	$entry( $now - $hour, E_USER_NOTICE, 'A notice is neither' ),
	$entry( $now - $hour, E_USER_DEPRECATED, 'Nor a deprecation' ),
	$entry( $now + 3000 * $day, E_WARNING, 'in the future' ),
	'junk',
] );

$state	= $stored();
$log		= \Nino\Modules\Sysadmin\Report::log( $appData, $state['lastMail'], $now );
check( 'the log since the last mail counts what ended a request as an error and a user warning as a warning, and nothing before, after or of another kind', $log['errors'] === 2 && $log['warnings'] === 3 );
check( '...the messages that come up most often come first, the numbers in them make no difference, and there are five at most', $log['top'][0]['count'] === 2 && str_starts_with( $log['top'][0]['text'], 'Backup failed' ) === true && count( $log['top'] ) <= \Nino\Modules\Sysadmin\Report::TOP );
$cleaned = implode( "\n", array_column( $log['top'], 'text' ) );
check( 'a message holds no address of a person, no address of the network, no path of the server, no query and is cut to 120 characters', preg_match( '/@|203\.0\.113|2001:db8|secret|token=|'. preg_quote( $sandbox, '/' ). '/', $cleaned ) === 0
	&& max( array_map( 'mb_strlen', array_column( $log['top'], 'text' ) ) ) <= \Nino\Modules\Sysadmin\Report::MESSAGE_LENGTH && str_contains( $cleaned, '[email]' ) === true && str_contains( $cleaned, '[ip]' ) === true );

$at( $now );
\Nino\Modules\Sysadmin::init( $appData );
$event = $taken();
$state = $stored();
check( 'errors since the last mail are a finding of their own and a problem mail, though nothing else is new', count( $event ) === 2 && $state['lastProblem'] === $now && isset( $state['reported']['log-errors'] ) === false );
$eventText = html_entity_decode( strip_tags( $event[0]['body'] ), ENT_QUOTES | ENT_HTML5 );
check( '...what a message holds of markup is text in the mail and never a tag', str_contains( $event[0]['body'], '&lt;script&gt;' ) === true && str_contains( $event[0]['body'], '<script' ) === false );
check( '...the mail says how many there were and which came up most, and holds nothing of what was cleaned away', str_contains( $eventText, 'Backup failed' ) === true && preg_match( '/@|203\.0\.113|2001:db8|secret|token=/', $eventText ) === 0 && str_contains( $eventText, $sandbox ) === false );

$at( $now + $day );
\Nino\Modules\Sysadmin::init( $appData );
check( 'the errors were told: a day later, with none since, nothing goes out', $taken() === [] );

\Nino\Filesystem::mutate( $appData, $logPath, static fn( array $entries ): array => array_merge( $entries, [ $entry( $now + $day + 60, 'Exception', 'Something broke again' ) ] ) );
$at( $now + 2 * $day );
\Nino\Modules\Sysadmin::init( $appData );
check( 'an error since then is news again, whatever else holds', count( $taken() ) === 2 );

echo "\n";


// --- The sign of life --------------------------------------------------------------------

echo "Sysadmin - the sign of life\n";

$set( [ 'beat' => 'weekly' ] );
$state = $stored();
$slot = $state['lastBeat'];
check( 'the sign of life is due a week after its slot, not before', \Nino\Modules\Sysadmin::beatDue( $appData, \Nino\Modules\Sysadmin::state( $appData ), $slot + 7 * $day - 1 ) === false
	&& \Nino\Modules\Sysadmin::beatDue( $appData, \Nino\Modules\Sysadmin::state( $appData ), $slot + 7 * $day ) === true );

// A week after the first run - by then the other mails have gone, and a problem mail is not what is due
$late = $slot + 7 * $day + 5 * $hour;
$at( $late );
$uncap();
\Nino\Modules\Sysadmin::init( $appData );
$beat = $taken();
$state = $stored();
check( 'a sign of life that is late goes out with the first request after it is due: one mail to each address, with everything the report finds', count( $beat ) === 2 && $state['lastMail'] === $late );
check( '...its slot is the slot of the rhythm and not the minute it went out in, so that it does not drift', $state['lastBeat'] === $slot + 7 * $day );
$beatText = html_entity_decode( strip_tags( $beat[0]['body'] ), ENT_QUOTES | ENT_HTML5 );
check( '...and it names the findings that stand, none of them as new', str_contains( $beatText, $sentence ) === true && substr_count( $beatText, '('. $words['de_DE']['[[/feature/sysadmin/mail/new]]']. ')' ) === 0 );
check( '...a problem mail is not a sign of life: its subject is another', $beat[0]['subject'] !== $first[0]['subject'] );

$at( $late + $hour );
\Nino\Modules\Sysadmin::init( $appData );
check( 'the next one is a week on from the slot', $taken() === [] && \Nino\Modules\Sysadmin::after( 'weekly', $state['lastBeat'] ) === $slot + 14 * $day );

// A site nobody visited for a year: one mail and no backlog
$at( $slot + 400 * $day );
\Nino\Modules\Sysadmin::init( $appData );
$year = $taken();
$state = $stored();
check( 'a site without a visit for a year gets one sign of life and the slot is the latest one of the rhythm, not a mail for every week', count( $year ) === 2 && $state['lastBeat'] > $slot + 399 * $day - 7 * $day && $state['lastBeat'] <= $slot + 400 * $day );

// The rhythms
$monthly = (int) strtotime( '2026-01-31 06:00:00' );
check( 'monthly is the next month of the calendar and daily the next day', \Nino\Modules\Sysadmin::after( 'monthly', (int) strtotime( '2026-03-10 06:00:00' ) ) === (int) strtotime( '2026-04-10 06:00:00' )
	&& \Nino\Modules\Sysadmin::after( 'daily', $monthly ) === $monthly + $day && \Nino\Modules\Sysadmin::after( 'weekly', $monthly ) === $monthly + 7 * $day );
$set( [ 'beat' => 'off' ] );
check( 'with the rhythm off the sign of life is never due', \Nino\Modules\Sysadmin::beatDue( $appData, \Nino\Modules\Sysadmin::state( $appData ), $slot + 4000 * $day ) === false );

// Both due at once: one mail
$set( [ 'beat' => 'daily' ] );
$state = $stored();
$both = $state['lastBeat'] + $day + 1;
\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'lastProblem' => $both - 3 * $day, 'lastEval' => $both - 3 * $day, 'reported' => [] ] ) );
$at( $both );
\Nino\Modules\Sysadmin::init( $appData );
$once = $taken();
$state = $stored();
check( 'when the problem clock and the sign of life are both due there is one mail, and it is the problem mail, which says all there is to say', count( $once ) === 2 && $once[0]['subject'] !== $beat[0]['subject']
	&& $state['lastProblem'] === $both && $state['lastBeat'] > $both - 2 * $day && $state['lastBeat'] <= $both );

echo "\n";


// --- A send that fails ----------------------------------------------------------------------

echo "Sysadmin - a send that fails\n";

\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'lastEval' => 0, 'lastProblem' => 0, 'lastBeat' => $both, 'reported' => [] ] ) );
$set( [ 'beat' => 'off' ] );
$failAt = $both + 10 * $day;
$at( $failAt );
$transport = false;
$uncap();
$result = \Nino\Modules\Sysadmin::run( $appData );
$state = $stored();
check( 'a mail the transport refuses is a failed run: it is noted with its time and a count, and what was told is not remembered as told', $result === 'failed'
	&& $state['failed'] === $failAt && $state['fails'] === 1 && $state['try'] === $failAt && $state['lastProblem'] === 0 && $state['reported'] === [] );
check( '...the kernel records the failure too, so the Dashboard shows it', \Nino\Mail::failure( $appData ) !== null );

$uncap();
$at( $failAt + 10 * 60 );
\Nino\Modules\Sysadmin::init( $appData );
check( 'the next request, ten minutes on, does not try again', $taken() === [] && $stored()['fails'] === 1 );
$at( $failAt + $hour );
\Nino\Modules\Sysadmin::init( $appData );
$state = $stored();
check( 'an hour after the failure it tries once more, and a second failure is counted and waits another hour', $state['fails'] === 2 && $state['failed'] === $failAt + $hour && $state['try'] === $failAt + $hour && $stored()['lastProblem'] === 0 );
$transport = true;
$at( $failAt + $hour + 30 * 60 );
\Nino\Modules\Sysadmin::init( $appData );
check( '...so that half an hour later nothing is tried', $taken() === [] && $stored()['fails'] === 2 );
$at( $failAt + 2 * $hour );
\Nino\Modules\Sysadmin::init( $appData );
$retry = $taken();
$state = $stored();
check( 'where the next one goes through, the failure is forgotten and what was told is remembered', count( $retry ) === 2 && $state['failed'] === 0 && $state['fails'] === 0 && $state['try'] === 0 && $state['lastProblem'] === $failAt + 2 * $hour );

// A run that dies in the middle
\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'try' => $failAt + $hour + $day, 'lastEval' => 0, 'lastProblem' => 0, 'reported' => [] ] ) );
$at( $failAt + $hour + $day + 5 * 60 );
\Nino\Modules\Sysadmin::init( $appData );
check( 'a run that was begun and never ended holds the next one back for the same hour', $taken() === [] );
$at( $failAt + $hour + $day + $hour );
\Nino\Modules\Sysadmin::init( $appData );
check( '...and the one after that is made', count( $taken() ) === 2 );

echo "\n";


// --- Two runs at once ----------------------------------------------------------------------

echo "Sysadmin - two requests at once\n";

\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'try' => 0, 'lastEval' => 0, 'lastProblem' => 0, 'reported' => [] ] ) );
$at( $failAt + 3 * $day );

$child = $sandbox. '/child.php';
file_put_contents( $child, '<?php
declare(strict_types=1);
// One request, as a child process: its own boot of the sandbox of the parent, a transport that is slow and writes down every mail
$root = $argv[1]; $features = $argv[2]; $sandbox = $argv[3]; $log = $argv[4]; $mode = $argv[5]; $now = (int) $argv[6];
define( "NINO_FEATURES_DIR", $features );
require $root . "/tests/harness.php";
$appData = [ "./nino/uid" => $sandbox ];
\Nino\AppData::prepare( $appData );
foreach( [ "path" => $sandbox, "configpath" => $sandbox . "/private", "contentpath" => $sandbox . "/private", "publicpath" => $sandbox . "/public" ] as $name => $value )
	$appData["./nino/filesystem/" . $name] = $value;
$appData["./nino/locales/current"] = "de_DE"; $appData["/nino/locales/native"] = "de_DE"; $appData["/nino/locales/available"] = [ "de_DE", "en_US" ];
$appData["/nino/dir"] = ""; $appData["/nino/install/texts"] = \Nino\VERSION; $appData["./nino/test/sandbox"] = $sandbox;
$stored = \Nino\Filesystem::getFileContent( $appData, "/config.php", [] );
foreach( [ "/nino/modules", "/nino/features" ] as $key )
	$appData[$key] = $stored[$key] ?? [];
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$appData["./sysadmin/disk"] = [ 50 * 1073741824, 100 * 1073741824 ];
$appData["./sysadmin/now"] = $now;
if( $mode === "hold" ) {
	\Nino\Filesystem::lockFile( $appData, \Nino\Modules\Sysadmin::LOCK, false );
	echo "HELD\n";
	flush();
	usleep( 1500000 );
	exit( 0 );
}
if( $mode === "inline" )
	$appData["./sysadmin/inline"] = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( $log ): void {
	usleep( 400000 );
	file_put_contents( $log, microtime( true ) . " " . $mail["to"] . "\n", FILE_APPEND | LOCK_EX );
	$mail["sent"] = true;
} );
\Nino\Modules\Sysadmin::init( $appData );
if( $mode === "inline" ) { echo "RESPONSE " . microtime( true ) . "\n"; exit( 0 ); }
$request = [ "/nino/http/request" => [ "method" => "GET", "rawMethod" => "GET" ], "/nino/http/response" => [ "statusCode" => 200, "header" => [], "body" => "RESPONSE " . microtime( true ) . "\n" ] ];
\Nino\Http::output( $appData, $request );
' );

$children = static function( string $mode, int $count, array $ini = [] ) use ( $root, $feature, $sandbox, $child, $base, $failAt, $day ): array {

	$log = $sandbox. '/mails-'. uniqid(). '.log';
	$run = [];

	for( $i = 0; $i < $count; $i++ ) {
		$pipes = [];
		$process = proc_open( array_merge( [ PHP_BINARY ], $ini, [ $child, $root, dirname( $feature ), $sandbox, $log, $mode, (string) ( $failAt + 3 * $day ) ] ), [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
		fclose( $pipes[0] );
		$run[] = [ $process, $pipes ];
	}

	$outputs = [];

	foreach( $run as [ $process, $pipes ] ) {
		$outputs[] = stream_get_contents( $pipes[1] ). stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );
	}

	return [ 'log' => is_file( $log ) === true ? array_filter( explode( "\n", (string) file_get_contents( $log ) ) ) : [], 'outputs' => $outputs ];
};

// A run that finds the lock held by another process makes nothing and does not wait for it
$holder = proc_open( [ PHP_BINARY, $child, $root, dirname( $feature ), $sandbox, $sandbox. '/none.log', 'hold', (string) ( $failAt + 3 * $day ) ], [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $holding );
fclose( $holding[0] );
$held = trim( (string) fgets( $holding[1] ) );
$start = microtime( true );
$busy = \Nino\Modules\Sysadmin::run( $appData );
$waited = microtime( true ) - $start;
check( 'a run that finds the lock held by another process makes nothing, writes nothing and does not wait for it', $held === 'HELD' && $busy === 'none' && $waited < 1.0 && $taken() === [] && $stored()['lastProblem'] === 0 );
fclose( $holding[1] );
fclose( $holding[2] );
proc_close( $holder );

$both = $children( 'inline', 2 );
check( 'two requests that begin together make one mail to each address: the one that is second finds the lock taken, or finds nothing due', count( $both['log'] ) === 2 );
$state = $stored();
check( '...and the state is the one of a single run', $state['lastProblem'] === $failAt + 3 * $day && $state['try'] === 0 );

echo "\n";


// --- After the response ---------------------------------------------------------------------

echo "Sysadmin - the work after the response\n";

\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'lastEval' => 0, 'lastProblem' => 0, 'reported' => [] ] ) );
$later = $children( 'deferred', 1 );
$response = (float) preg_replace( '/^RESPONSE ([0-9.]+).*/s', '$1', $later['outputs'][0] );
$sent = array_map( static fn( string $line ): float => (float) strtok( $line, ' ' ), $later['log'] );
check( 'a request that sets it off finishes its own work first: the mails are made after the response was written and flushed', count( $sent ) === 2 && $response > 0 && min( $sent ) >= $response );

echo "\n";

// --- The mail holds nothing it should not -------------------------------------------------

echo "Sysadmin - what the mail holds\n";

// A subject is one line and a name of the site is no markup, whatever the project wrote into it
\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [ '[[/project/website/general/url]]' => "evil.example\r\nBcc: x <b>x</b>" ] + (array) \Nino\Filesystem::getFileContent( $appData, '/text/global.php', [] ) );
$mail = \Nino\Modules\Sysadmin::inNative( $appData, static function( string $native ) use ( &$appData, $base ): array {
	return \Nino\Modules\Sysadmin::compose( $appData, $native, 'problem', \Nino\Modules\Sysadmin\Report::collect( $appData, $native, $base, $base + 1 ), [], \Nino\Modules\Sysadmin::state( $appData ), $base + 1 );
} );
check( 'the name of the site in the subject is a single line with no markup, whatever the project wrote for its address', preg_match( '/[\r\n]/', $mail['subject'] ) === 0 && str_contains( $mail['subject'], '<' ) === false );
check( '...and the Host header of the request that sets it off is never the name', ( static function() use ( &$appData ): bool {
	$_SERVER['HTTP_HOST'] = 'attacker.example';
	$site = \Nino\Modules\Sysadmin::site( $appData );
	unset( $_SERVER['HTTP_HOST'] );
	return str_contains( $site, 'attacker' ) === false;
} )() );

// The whole mail: no path of the server, no secret of the config, no address of a person
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [ '[[/sysadmin-test/mail]]' => 'ana@example.org', '[[/sysadmin-test/gap]]' => 'Eine Lücke' ] + (array) \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] ) );
$whole = \Nino\Modules\Sysadmin::inNative( $appData, static function( string $native ) use ( &$appData, $base, $day ): array {
	return \Nino\Modules\Sysadmin::compose( $appData, $native, 'beat', \Nino\Modules\Sysadmin\Report::collect( $appData, $native, $base, $base + $day ), [], \Nino\Modules\Sysadmin::state( $appData ), $base + $day );
} );
$config = json_encode( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) );
$secrets = array_filter( [ $appData['/nino/auth/user']['admin@example.com']['hash'] ?? '', $appData['/nino/session/token'] ?? '' ], static fn( mixed $value ): bool => is_string( $value ) === true && strlen( $value ) > 8 );
check( 'a link to the workbench is one line with an address in it that holds no markup, or there is none', array_filter( explode( "\n", $whole['text'] ), static fn( string $line ): bool => str_contains( $line, 'http' ) === true && preg_match( '#^\S+ https?://[^\s<>"]+$#', $line ) !== 1 ) === [] );
check( 'the mail holds no path of the server, no address of a person that a message or the log carried, and none of the secrets of the project', str_contains( $whole['text'], $sandbox ) === false
	&& preg_match( '/ana@example\.org|spy@example\.net/', $whole['text'] ) === 0 && count( array_filter( $secrets, static fn( string $secret ): bool => str_contains( $whole['text'], $secret ) ) ) === 0
	&& preg_match( '#/(?:tmp|var|home|srv|usr)/#', $whole['text'] ) === 0 );

// A mail the cap of the kernel refuses is a failure, noted - and not an error of every request after it
$at( $failAt + 40 * $day );
for( $i = 0; $i <= \Nino\Mail::SYSTEM_CAP; $i++ )
	\Nino\Feature::count( $appData, 'sysmail-system', 'site', \Nino\Mail::SYSTEM_CAP, 3600 );
\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'try' => 0, 'lastEval' => 0, 'lastProblem' => 0, 'failed' => 0, 'fails' => 0, 'reported' => [] ] ) );
$taken();
$result = \Nino\Modules\Sysadmin::run( $appData );
check( 'a send that the cap of the system\'s mails an hour refuses is a failed run like any other', $result === 'failed' && \Nino\Mail::rateLimited( $appData ) === true && $stored()['fails'] === 1 && $taken() === [] );
$uncap();
\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'try' => 0, 'failed' => 0, 'fails' => 0 ] ) );

echo "\n";


// --- What the Dashboard has no place for ----------------------------------------------------

echo "Sysadmin - the findings of its own\n";

$findings = static function() use ( &$appData ): array {
	$report = \Nino\Modules\Sysadmin::inNative( $appData, static function( string $native ) use ( &$appData ): array { return \Nino\Modules\Sysadmin\Report::collect( $appData, $native, 0, 1 ); } );
	return array_column( $report['findings'], 'rank', 'key' );
};
check( 'a site with room, no cap reached and no refused manifest has none of them', array_diff_key( $findings(), array_flip( array_filter( array_keys( $findings() ), static fn( string $key ): bool => str_starts_with( $key, 'dashboard:' ) ) ) ) === [] );

// The backup that failed: the kernel's own refusal - a memory limit it cannot work under - and the note it leaves,
// which missed() answers and which a page request reads as well as the workbench does
$appData['./nino/backups/memory'] = 1048576;
$appData['./nino/backups/today'] = date( 'Y-m-d', $base );
\Nino\Modules\Backups::maybeRun( $appData );
unset( $appData['./nino/backups/memory'] );
$kept = array_values( array_filter( ninoWarnings(), static fn( string $warning ): bool => str_starts_with( $warning, 'Backup failed: ' ) ) );
$noted = \Nino\Modules\Backups::missed( $appData );
$failed = \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 120 );
check( 'a backup that failed today is an error with the reason of the kernel\'s note in it, cleaned like a message of the log', count( $kept ) === 1 && $noted !== null && $failed['on'] === true && is_string( $failed['failed'] ) === true
	&& $failed['failed'] === \Nino\Modules\Sysadmin\Report::clean( $appData, $noted['reason'] ) && $failed['failed'] !== '' && $failed['date'] === null );
check( '...it is a finding of the rank error', ( $findings()['backup-failed'] ?? 0 ) === \Nino\Modules\Sysadmin\Report::ERROR );
$dir = \Nino\Modules\Backups::dir( $appData );
$attempt = $dir. '/'. \Nino\Modules\Backups::ATTEMPT_FILE;
touch( $attempt, $base + 100 );
// A backup by hand, made after the attempt, lifts it - and is the last backup, its date and its size
file_put_contents( $dir. '/'. date( 'Y-m-d', $base ). '-070000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php', '<?php exit;' );
touch( $dir. '/'. date( 'Y-m-d', $base ). '-070000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php', $base + 600 );
$made = \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 3600 );
check( '...a backup by hand that is newer than the note lifts it, and is the last backup with its date, its age and its size', $made['date'] === date( 'Y-m-d', $base ) && $made['days'] === 0 && $made['bytes'] === 11 && $made['failed'] === null
	&& isset( $findings()['backup-failed'] ) === false );
touch( $dir. '/'. date( 'Y-m-d', $base ). '-070000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php', $base - 600 );
check( '...and one that is older than the note does not', \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 3600 )['failed'] !== null );
@unlink( $dir. '/'. date( 'Y-m-d', $base ). '-070000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php' );
@unlink( $attempt );
check( '...a note that is gone is no failure', \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 3600 )['failed'] === null && \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 3600 )['date'] === null );

// A run that php or the server ended: the kernel's note of it, lifted by a newer archive
$stub = static fn( array $note ): string => \Nino\Modules\Backups::STUB_PREFIX. base64_encode( (string) json_encode( $note ) ). \Nino\Modules\Backups::STUB_SUFFIX;
file_put_contents( $dir. '/run.php', $stub( [ 'kind' => 'manual', 'started' => $base + 200, 'files' => 3, 'bytes' => 9, 'cause' => 'memory', 'limit' => 1000 ] ) );
$ended = \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 3600 );
check( 'a run that php ended is named with when it began and what ended it, and is a finding of the rank error', ( $ended['stopped']['cause'] ?? '' ) === 'memory' && ( $findings()['backup-stopped'] ?? 0 ) === \Nino\Modules\Sysadmin\Report::ERROR );
file_put_contents( $dir. '/'. date( 'Y-m-d', $base ). '-080000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php', '<?php exit;' );
touch( $dir. '/'. date( 'Y-m-d', $base ). '-080000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php', $base + 900 );
check( '...and is lifted by an archive that is newer than its start', \Nino\Modules\Sysadmin\Report::backup( $appData, $base + 3600 )['stopped'] === null );
@unlink( $dir. '/run.php' );
@unlink( $dir. '/'. date( 'Y-m-d', $base ). '-080000'. \Nino\Modules\Backups::MANUAL_SUFFIX. '.php' );
$appData['/nino/admin/backups'] = false;
check( '...a site that switched the backup off has no finding about it and says so in the mail', \Nino\Modules\Sysadmin\Report::backup( $appData, $base )['on'] === false );
unset( $appData['/nino/admin/backups'] );

// A cap of the kernel that is reached
$appData['/nino/form/hour-cap'] = 1;
\Nino\Feature::forget( $appData, 'form-hour', 'site' );
\Nino\Feature::count( $appData, 'form-hour', 'site', 1, 3600 );
check( 'a form cap that is reached is a warning, named by which of the two it is', \Nino\Form::capped( $appData ) === 'hour' && ( $findings()['form-cap:hour'] ?? 0 ) === \Nino\Modules\Sysadmin\Report::WARNING );
\Nino\Feature::forget( $appData, 'form-hour', 'site' );
unset( $appData['/nino/form/hour-cap'] );
check( '...and is gone when the window is', isset( $findings()['form-cap:hour'] ) === false );

// A manifest the kernel refused
\Nino\Features::all( $appData );
$rejected = $appData['./nino/features/rejected'];
$modules = $appData['/nino/modules'];
$appData['./nino/features/rejected'] = [ 'Broken' => 'name for ana@example.org is no map', 'Off' => 'claims the key "x"' ];
$appData['/nino/modules'][] = '\\Nino\\Modules\\Broken';
$refused = \Nino\Modules\Sysadmin\Report::refused( $appData );
check( 'a refused manifest is named by its directory with its reason cleaned, and whether the feature is on all the same', array_keys( $refused ) === [ 'Broken', 'Off' ] && $refused['Broken']['active'] === true && $refused['Off']['active'] === false
	&& str_contains( $refused['Broken']['reason'], 'ana@' ) === false );
check( '...one that is on is an error, one that is off a warning', ( $findings()['manifest:Broken'] ?? 0 ) === \Nino\Modules\Sysadmin\Report::ERROR && ( $findings()['manifest:Off'] ?? 0 ) === \Nino\Modules\Sysadmin\Report::WARNING );
$appData['./nino/features/rejected'] = $rejected;
$appData['/nino/modules'] = $modules;

// The disk
$appData['./sysadmin/disk'] = [ 90 * 1073741824, 100 * 1073741824 ];
check( 'a disk with room is no finding; one with a tenth left is a warning, one with 5 percent or less than 100 megabytes an error', ( $findings()['disk'] ?? 0 ) === 0 );
$appData['./sysadmin/disk'] = [ 9 * 1073741824, 100 * 1073741824 ];
$warning = $findings()['disk'] ?? 0;
$appData['./sysadmin/disk'] = [ 4 * 1073741824, 100 * 1073741824 ];
$error = $findings()['disk'] ?? 0;
$appData['./sysadmin/disk'] = [ 50 * 1048576, 1000 * 1073741824 ];
$small = $findings()['disk'] ?? 0;
$appData['./sysadmin/disk'] = [ 50 * 1073741824, 100 * 1073741824 ];
check( '...the numbers are the free space against the total, which a server may not say - then there is no finding', $warning === \Nino\Modules\Sysadmin\Report::WARNING && $error === \Nino\Modules\Sysadmin\Report::ERROR && $small === \Nino\Modules\Sysadmin\Report::ERROR );

echo "\n";


// --- The panel -----------------------------------------------------------------------------

echo "Sysadmin - the panel\n";

check( 'the panel is there while the feature is on, with its two actions behind its own permission', isset( \Nino\Admin\Admin::panels( $appData )['sysadmin'] ) === true
	&& array_keys( \Nino\Modules\Sysadmin\Admin::actions() ) === [ 'sysadmin/status', 'sysadmin/test' ] && \Nino\Modules\Sysadmin\Admin::perm() === '/_admin/sysadmin/manage' );
check( '...and it is in the Features group of the rail, where the permission is offered to the roles', \Nino\Admin\Admin::panels( $appData )['sysadmin']['perm'] === '/_admin/sysadmin/manage' );

$taken();
ninoUser( $appData, 'plain@example.com', [], 'plain password' );
[ $status ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
[ $testStatus ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
check( 'an account without the permission is refused on both actions and nothing is sent', $status === 403 && $testStatus === 403 && $taken() === [] );
\Nino\Auth::logoutUser( $appData );
check( '...and so is a visitor who is not signed in', ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] )[0] === 401 && ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] )[0] === 401 && $taken() === [] );
ninoUser( $appData, 'admin@example.com' );

$at( $failAt + 41 * $day );
\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'lastProblem' => $failAt + 40 * $day ] ) );
[ $status, $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
check( 'the status says who is told, the distance, the rhythm and the clocks as times, and every finding with its rank and its sentence', $status === 200 && $body['recipients'] === [ 'a@example.org', 'b@example.org' ]
	&& $body['gap'] === 24 && $body['beat'] === 'off' && $body['nextBeat'] === null && $body['lastProblem'] === \Nino\Format::datetime( $appData, \Nino\Modules\Sysadmin\Report::stamp( $failAt + 40 * $day ) )
	&& count( array_filter( $body['findings'], static fn( array $finding ): bool => in_array( $finding['rank'], [ 1, 2, 3 ], true ) === true && is_string( $finding['text'] ) === true && $finding['text'] !== '' && is_bool( $finding['event'] ) === true ) ) === count( $body['findings'] ) && $body['findings'] !== [] );
$set( [ 'beat' => 'weekly' ] );
[ , $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
check( '...the next sign of life is the slot after the last', $body['nextBeat'] === \Nino\Format::datetime( $appData, \Nino\Modules\Sysadmin\Report::stamp( \Nino\Modules\Sysadmin::after( 'weekly', $stored()['lastBeat'] ) ) ) );
check( '...and the failure of a send, with its time and its count, once there is one', ( static function() use ( &$appData, $stored, $failAt, $day ): bool {
	\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $state ): array => array_merge( $state, [ 'failed' => $failAt + 41 * $day, 'fails' => 3 ] ) );
	[ , $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
	\Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $state ): array => array_merge( $state, [ 'failed' => 0, 'fails' => 0 ] ) );
	return ( $body['failed']['count'] ?? 0 ) === 3 && is_string( $body['failed']['at'] ?? null );
} )() );

// An account that holds only this panel's permission sees there what its Dashboard would tell it, no more: the texts a language lacks are the Text panel's
[ , $adminBody ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
ninoUser( $appData, 'sysonly@example.com', [ '/_admin/sysadmin/manage' ], 'sysonly password' );
[ $sysStatus, $sysBody ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
$untranslated = static fn( array $body ): int => count( array_filter( $body['findings'], static fn( array $finding ): bool => str_contains( $finding['text'], 'en_US' ) ) );
check( 'an account with only the permission of this panel is told by the panel what its Dashboard tells it: not the texts that are the Text panel\'s', $sysStatus === 200 && $untranslated( $adminBody ) === 1 && $untranslated( $sysBody ) === 0 );
ninoUser( $appData, 'admin@example.com' );

// The test mail
$before = $stored();
[ $status, $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
$test = $taken();
$after = $stored();
check( 'the test mail goes to the saved recipients, the same mail as the others, marked as a test', $status === 200 && $body['sent'] === 2 && count( $test ) === 2 && array_column( $test, 'to' ) === [ 'a@example.org', 'b@example.org' ] && $test[0]['subject'] !== $first[0]['subject'] );
check( '...and leaves what the two clocks remember as it was, but for the time of the test', array_diff_key( $after, [ 'tested' => 1 ] ) === array_diff_key( $before, [ 'tested' => 1 ] ) && $after['tested'] === $failAt + 41 * $day );
[ $status, $body, $request ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
check( 'a second test within a minute is refused with its own code, how long to wait and the status 429, and sends nothing', $status === 429 && ( $body['code'] ?? '' ) === 'sysadmin_wait' && $body['params'] === [ (string) \Nino\Modules\Sysadmin::TEST_EVERY ] && $taken() === [] );
$at( $failAt + 41 * $day + \Nino\Modules\Sysadmin::TEST_EVERY );
check( '...and one a minute later is made', ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] )[0] === 200 && count( $taken() ) === 2 );

$at( $failAt + 41 * $day + 3 * \Nino\Modules\Sysadmin::TEST_EVERY );
$transport = false;
[ $status, $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
check( 'a mail the transport does not take is a 502 with its own code, and no failure of the schedule', $status === 502 && ( $body['code'] ?? '' ) === 'sysadmin_failed' && $stored()['fails'] === 0 );
$transport = true;

$at( $failAt + 41 * $day + 5 * \Nino\Modules\Sysadmin::TEST_EVERY );
for( $i = 0; $i <= \Nino\Mail::SYSTEM_CAP; $i++ )
	\Nino\Feature::count( $appData, 'sysmail-test', 'site', \Nino\Mail::SYSTEM_CAP, 3600 );
[ $status, $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
check( '...the cap of the test mails an hour has its own', $status === 429 && ( $body['code'] ?? '' ) === 'sysadmin_ratelimit' );
$appData['/nino/mail/cap'] = 0;

$saved = $appData['/nino/features']['sysadmin']['settings']['to'];
$appData['/nino/features']['sysadmin']['settings']['to'] = '';
$taken();
[ $status, $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
check( 'without an address the test is refused with a code and the status 400, and nothing is sent', $status === 400 && ( $body['code'] ?? '' ) === 'sysadmin_none' && $taken() === [] );
$appData['/nino/features']['sysadmin']['settings']['to'] = $saved;

// The words of the panel
$panelWords = [ 'en_US' => (array) include $feature. '/text/en_US.php', 'de_DE' => (array) include $feature. '/text/de_DE.php' ];
$script = (string) file_get_contents( $feature. '/assets/admin.js' );
preg_match_all( "/word\(\s*'([a-z0-9\/-]+)'\s*\)/", $script, $wordsAsked );
$codes = [];
preg_match_all( "/'(sysadmin_[a-z]+)'/", (string) file_get_contents( $feature. '/Admin/Admin.php' ), $codes );
$needed = array_merge( array_map( static fn( string $name ): string => '[[/_admin/sysadmin/'. $name. ']]', $wordsAsked[1] ), array_map( static fn( string $code ): string => '[[/_admin/error/'. $code. ']]', $codes[1] ),
	[ '[[/_admin/sysadmin/nav]]', '[[/_admin/sysadmin/error/load]]' ], array_map( static fn( string $rank ): string => '[[/_admin/sysadmin/rank/'. $rank. ']]', \Nino\Modules\Sysadmin\Report::RANK_WORDS ), array_map( static fn( string $beat ): string => '[[/_admin/sysadmin/beat/'. $beat. ']]', [ 'off', 'daily', 'weekly', 'monthly' ] ) );
check( 'the panel\'s words are in both languages under the same keys, none empty, and every key the script and the actions ask for is one of them', array_keys( $panelWords['en_US'] ) === array_keys( $panelWords['de_DE'] )
	&& count( array_filter( array_merge( $panelWords['en_US'], $panelWords['de_DE'] ), static fn( string $text ): bool => trim( $text ) === '' ) ) === 0
	&& array_diff( $needed, array_keys( $panelWords['en_US'] ) ) === [] && count( $codes[1] ) === 4 && count( $wordsAsked[1] ) > 15 );
check( '...and the script writes no sentence of its own and never sets what the server sent as markup', preg_match( '/innerHTML\s*=\s*[^\'";\s]/', $script ) === 0 && substr_count( $script, 'innerHTML' ) === substr_count( $script, "innerHTML = '';" ) && preg_match( '/\.(?:insertAdjacentHTML|outerHTML)|document\.write/', $script ) === 0 );
check( '...it uses the dialog of the workbench and no confirm() or alert() of the browser', preg_match( '/\b(?:confirm|alert|prompt)\s*\(/', preg_replace( '/Nino\.adminUi\.confirm\(/', '', $script ) ) === 0 && str_contains( $script, 'Nino.adminUi.confirm(' ) === true );

echo "\n";


// --- The note before the attempt, the clock, the dead run, the slow server ---------------------

echo "Sysadmin - what a run owes the next one\n";

$set( [ 'beat' => 'weekly' ] );

$reset = static function( array $change ) use ( &$appData ): bool { return \Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static fn( array $stored ): array => array_merge( $stored, [ 'try' => 0, 'failed' => 0, 'fails' => 0, 'aborts' => 0, 'lastEval' => 0, 'lastProblem' => 0, 'reported' => [] ], $change ) ); };

// The note of the attempt is on the disk before anything is collected or sent
$noteAtSend = [];
$reset( [] );
$when = $failAt + 60 * $day;
$at( $when );
$onSend = static function() use ( $stored, &$noteAtSend ): void { $noteAtSend[] = $stored()['try']; };
\Nino\Modules\Sysadmin::run( $appData );
$taken();
$onSend = null;
check( 'the note of the attempt - its time - is in the file when the first mail is handed to the transport: a run that cannot write it sends nothing', $noteAtSend !== [] && $noteAtSend[0] === $when );

// A clock that jumped forward leaves times in the future: they are now
$reset( [ 'lastBeat' => $when + 400 * $day, 'lastEval' => $when + 400 * $day, 'lastProblem' => $when + 400 * $day, 'lastMail' => $when + 400 * $day, 'tested' => $when + 400 * $day, 'try' => $when + 400 * $day, 'since' => $when + 400 * $day ] );
$state = \Nino\Modules\Sysadmin::state( $appData );
check( 'a time the file holds that is after now is now', $state['lastBeat'] === $when && $state['lastEval'] === $when && $state['lastProblem'] === $when && $state['lastMail'] === $when && $state['tested'] === $when && $state['try'] === $when && $state['since'] === $when );
$at( $when + 8 * $day );
check( '...and the file is not read again and again for a time that is not coming: it is due, and the run writes the times as now', \Nino\Modules\Sysadmin::due( $appData, \Nino\Modules\Sysadmin::state( $appData ), $when + 8 * $day ) === true );
$taken();
\Nino\Modules\Sysadmin::run( $appData );
$after = $stored();
check( '...so that the sign of life is a rhythm after the jump and the clocks are those of now', $after['lastBeat'] === $when + 8 * $day && $after['lastEval'] === $when + 8 * $day && $after['since'] === $when + 8 * $day
	&& \Nino\Modules\Sysadmin::state( $appData )['skewed'] === false && \Nino\Modules\Sysadmin::beatDue( $appData, \Nino\Modules\Sysadmin::state( $appData ), $when + 8 * $day ) === false );

// A run that died: its note stands, and the next one counts it and says so
$reset( [ 'try' => $when + 8 * $day - 2 * $hour, 'since' => $when, 'lastBeat' => $when + 8 * $day ] );
$state = \Nino\Modules\Sysadmin::state( $appData );
check( 'an attempt that was written and never ended, and is no send that failed, is a run that ended without a word', \Nino\Modules\Sysadmin::aborted( $state, $when + 8 * $day ) === 1 );
$reset( [ 'try' => $when + 8 * $day - 2 * $hour, 'failed' => $when + 8 * $day - 2 * $hour, 'fails' => 1 ] );
check( '...a send that failed is not', \Nino\Modules\Sysadmin::aborted( \Nino\Modules\Sysadmin::state( $appData ), $when + 8 * $day ) === 0 );
$reset( [ 'try' => $when + 8 * $day - 10 * 60 ] );
check( '...and an attempt that is not an hour old may be one that is going on', \Nino\Modules\Sysadmin::aborted( \Nino\Modules\Sysadmin::state( $appData ), $when + 8 * $day ) === 0 );
$reset( [ 'try' => $when + 8 * $day - 2 * $hour ] );
$mails = [];
\Nino\Modules\Sysadmin::run( $appData );
$aborted = $taken();
$state = $stored();
check( 'the run after it says so in the mail and in the panel\'s findings, and the count goes when the mail is out', count( $aborted ) === 2 && str_contains( html_entity_decode( strip_tags( $aborted[0]['body'] ) ), '1' ) === true && $state['aborts'] === 0 );
$reset( [ 'try' => $when + 8 * $day - 2 * $hour ] );
$withAborted = \Nino\Modules\Sysadmin::inNative( $appData, static fn( string $native ): array => \Nino\Modules\Sysadmin\Report::collect( $appData, $native, 0, $when + 8 * $day, true, 1 ) );
check( '...as a finding of the rank warning', in_array( 'run-aborted', array_column( $withAborted['findings'], 'key' ), true ) === true );
ninoUser( $appData, 'admin@example.com' );
[ , $body ] = ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiStatus' ] );
check( '...and the panel counts it', $body['aborted'] === 1 );

// A mail server that does not answer ends the run at the first address, not the second after the same wait
$reset( [] );
$at( $when + 20 * $day );
$transport = false;
$calls = 0;
$onSend = static function() use ( &$calls ): void { $calls++; };
\Nino\Modules\Sysadmin::run( $appData );
check( 'a mail server that refuses ends the sending at the first address: the second is not asked', $stored()['fails'] === 1 && $calls === 1 );
$onSend = null;
$transport = true;
check( 'the whole run has a limit of its own, so that a server that never answers ends it', \Nino\Modules\Sysadmin::RUN_LIMIT === 60 );
$set( [ 'beat' => 'off' ] );

echo "\n";


// A disk that is full: where the note of the attempt cannot be written, nothing is collected and nothing is sent. Needs a small disk
// of its own to fill, which only an account that may mount one has
$dataDir = \Nino\Filesystem::path( $appData, '/data' );
$mounted = @exec( 'mount -t tmpfs -o size=64k tmpfs '. escapeshellarg( $dataDir ). ' 2>&1', $mountOut, $mountCode ) !== false && $mountCode === 0;

if( $mounted === false )
	echo "  --  - no small disk can be mounted here: the run on a full disk was NOT run\n";
else {
	$appData['./nino/filesystem/cache'] = [];
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Sysadmin::STATE, [ 'format' => 1, 'since' => $when, 'lastBeat' => $when ] );
	while( @file_put_contents( $dataDir. '/fill.bin', str_repeat( 'x', 512 ), FILE_APPEND ) !== false );
	while( @file_put_contents( $dataDir. '/fill.bin', 'x', FILE_APPEND ) !== false );
	$at( $when + 40 * $day );
	$taken();
	$full = [];
	for( $i = 0; $i < 3; $i++ )
		$full[] = \Nino\Modules\Sysadmin::run( $appData );
	$onDisk = count( $taken() );
	@exec( 'umount '. escapeshellarg( $dataDir ). ' 2>&1' );
	$appData['./nino/filesystem/cache'] = [];
	check( 'on a full disk, where the note of the attempt cannot be written, a run collects nothing and sends nothing, however often it is asked', $full === [ 'none', 'none', 'none' ] && $onDisk === 0 );
}

// One unit of the system's cap for a run, whoever it goes to; and a run that failed halfway is not sent twice to those who have the mail
$sysCount = static function() use ( &$appData ): int { $file = (array) \Nino\Filesystem::getFileContent( $appData, '/data/sysmail-system.php', [] ); return (int) ( array_values( $file )[0]['tries'] ?? 0 ); };
$reset( [ 'delivered' => [ 'kind' => '', 'to' => [] ] ] );
\Nino\Filesystem::putFileContent( $appData, '/data/sysmail-system.php', [] );
$at( $when + 30 * $day );
$taken();
\Nino\Modules\Sysadmin::run( $appData );
check( 'a run with two addresses is one unit of the cap of the system\'s mails', count( $taken() ) === 2 && $sysCount() === 1 );
$at( $when + 30 * $day + 100 );
\Nino\Feature::count( $appData, 'sysmail-system', 'site', \Nino\Mail::SYSTEM_CAP, 3600 );
$before = $sysCount();
ninoCall( $appData, [ \Nino\Modules\Sysadmin\Admin::class, 'apiTest' ] );
$taken();
check( 'a test mail is none of them: it is held back by its own throttle and counts in its own counter', $sysCount() === $before );

$reset( [ 'delivered' => [ 'kind' => '', 'to' => [] ] ] );
$at( $when + 31 * $day );
$failFor = 'b@example.org';
$taken();
\Nino\Modules\Sysadmin::run( $appData );
$partial = $stored();
check( 'a run that failed at the second address says who has the mail: the first, and of which kind', count( $taken() ) === 2 && $partial['fails'] === 1 && $partial['delivered']['to'] === [ 'a@example.org' ] && $partial['delivered']['kind'] !== '' );
$failFor = null;
$at( $when + 31 * $day + 2 * $hour );
\Nino\Modules\Sysadmin::run( $appData );
$retried = $taken();
check( '...and the run that tries again sends it to the second alone, and forgets who had it when it is out', count( $retried ) === 1 && $retried[0]['to'] === 'b@example.org' && $stored()['delivered'] === [ 'kind' => '', 'to' => [] ] );
$failFor = null;

echo "\n";

// --- What the mail may say --------------------------------------------------------------------

echo "Sysadmin - the masking\n";

$clean = static fn( string $text ): string => \Nino\Modules\Sysadmin\Report::clean( $appData, $text, 0 );
$leaks = [
	'mail of umlaut' => [ 'Mail an jürgen.müller@bücher.example failed', 'jürgen' ],
	'mail with %40' => [ 'Mail to ana%40example.org failed', 'ana' ],
	'mail to a host without a dot' => [ 'Mail to root@localhost failed', 'root@' ],
	'ip after a name and a colon' => [ 'from client:203.0.113.9 refused', '203.0.113' ],
	'ip6 after a name and a colon' => [ 'from client:2001:db8::9 refused', '2001' ],
	'query of a path of the site' => [ 'Not found: /reset?token=abc123&mail=x', 'abc123' ],
	'query of an address' => [ 'GET https://example.org/reset?token=abc123 failed', 'abc123' ],
	'fragment of an address' => [ 'GET https://example.org/page#secret failed', 'secret' ],
	'path of windows' => [ 'Cannot open C:\Users\ana\private\data\forms.php now', 'ana' ],
	'ip6 mapped' => [ 'peer ::ffff:198.51.100.4 refused', '198.51' ],
	'ip6 behind a variable' => [ 'REMOTE_ADDR=::ffff:10.0.0.5 refused', '10.0.0' ],
	'ip6 that ends with ::' => [ 'peer 2001:db8:: refused', '2001' ],
	'token of a login' => [ 'jwt eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9 used', 'eyJhbGci' ],
	'short token of a login' => [ 'jwt eyJhbGci.eyJzdWIi.abc12345 used', 'eyJhbGci' ],
	'token of three long parts' => [ 'jwt abcdefghijklmnop.qrstuvwxyz012345.6789abcdefghijkl used', 'abcdefghij' ],
	'key as hex' => [ 'token 0123456789abcdef0123456789abcdef used', '0123456789abcdef0123' ],
	'key as base64' => [ 'Authorization key QWxhZGRpbjpvcGVuIHNlc2FtZVF3ZXJ0eTEyMzQ1Ng== used', 'QWxhZGRpbjpv' ],
];
$leaked = [];
foreach( $leaks as $what => [ $text, $secret ] )
	if( str_contains( $clean( $text ), $secret ) === true )
		$leaked[] = $what. ' => '. $clean( $text );
check( 'a message holds none of what a person typed or a key is made of'. ( $leaked === [] ? '' : ' - '. implode( ' | ', $leaked ) ), $leaked === [] );
check( '...an address of version 6 is found whole, at the front, at the end and as the only one, and nothing that only looks like one is touched', $clean( 'from ::1 refused' ) === 'from [ip] refused' && $clean( 'to 2001:db8:: end' ) === 'to [ip] end' && $clean( 'a ::ffff:198.51.100.4.' ) === 'a [ip].'
	&& $clean( 'client:2001:db8::9 x' ) === 'client:[ip] x' && $clean( 'at 12:30:45 in 3 tries, www.example.com' ) === 'at 12:30:45 in 3 tries, www.example.com' );
check( '...and keeps what says what happened: the words, the file name, the numbers that are no address', $clean( 'Could not read /var/www/x/forms.php at line 12 in 3 tries' ) === 'Could not read …/forms.php at line 12 in 3 tries' && $clean( 'Class Nino\Modules\Sysadmin failed? yes' ) === 'Class Nino\Modules\Sysadmin failed? yes' );
check( '...a sentence of the Dashboard is a line and nothing else, so a placeholder of the legal texts stays readable', \Nino\Modules\Sysadmin\Report::line( "The detail #/project/company/general/name#\nis empty" ) === 'The detail #/project/company/general/name# is empty' );

check( '...and the sentences of the Dashboard are never masked like a message of the log, which would break what they quote', preg_match( '/self::line\\(\\s*\\\\Nino\\\\Modules\\\\Dashboard\\\\Admin::sentence/', (string) file_get_contents( $feature. '/Report/Report.php' ) ) === 1 );

// Grouped first, cut after: two messages that are the same up to the cut are two when they differ after it
$long = str_repeat( 'a long message ', 9 );
\Nino\Filesystem::putFileContent( $appData, '/data/logs.'. date( 'Y-m', $when ). '.php', [
	[ 'type' => 'Exception', 'message' => $long. 'ends one way', 'file' => 'x', 'line' => 1, 'date' => date( 'Y-m-d H:i:s', $when + 100 ) ],
	[ 'type' => 'Exception', 'message' => $long. 'ends another way', 'file' => 'x', 'line' => 1, 'date' => date( 'Y-m-d H:i:s', $when + 101 ) ],
] );
$grouped = \Nino\Modules\Sysadmin\Report::log( $appData, $when, $when + 1000 );
check( 'messages are grouped by what they say and cut for the line after: two that differ after the cut are two lines of one, each at most 120 characters', count( $grouped['top'] ) === 2 && $grouped['errors'] === 2 && max( array_map( 'mb_strlen', array_column( $grouped['top'], 'text' ) ) ) <= 120 );

echo "\n";


// --- The edges that the numbers sit on ------------------------------------------------------------

echo "Sysadmin - the edges\n";

$rank = static function( int $free, int $total ) use ( &$appData ): int { $appData['./sysadmin/disk'] = [ $free, $total ]; return \Nino\Modules\Sysadmin\Report::disk( $appData )['rank']; };
$GB = 1073741824;
check( 'the disk: a tenth free is no warning, just under it is; 5 percent is no error, just under it is; 100 megabytes is no error, just under it is', $rank( 10 * $GB, 100 * $GB ) === 0 && $rank( 10 * $GB - 1, 100 * $GB ) === 2 && $rank( 5 * $GB, 100 * $GB ) === 2 && $rank( 5 * $GB - 1, 100 * $GB ) === 3
	&& $rank( 104857600, 1500 * 1048576 ) === 2 && $rank( 104857599, 1500 * 1048576 ) === 3 );
$appData['./sysadmin/disk'] = [ 50 * $GB, 100 * $GB ];
check( 'the rhythm of a clock that is checked is an hour and a failure is retried in an hour, as the settings say', \Nino\Modules\Sysadmin::CHECK_EVERY === 3600 && \Nino\Modules\Sysadmin::RETRY === 3600 && \Nino\Modules\Sysadmin::TEST_EVERY === 60 );

echo "\n";


// --- The script of the panel ---------------------------------------------------------------

echo "Sysadmin - what the script of the panel builds\n";

check( 'the script takes its lists, its bar, its button and its dialog from the workbench and builds none of them itself',
	preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(|nino-admin-btn-|nino-admin-actionbar|<dialog|<form/', $script ) === 0
	&& str_contains( $script, 'Nino.adminUi.actionBar( {' ) === true && substr_count( $script, "kind : 'primary'" ) === 1 && str_contains( $script, 'Nino.adminUi.sectionTitle(' ) === true
	&& str_contains( $script, "Nino.adminUi.confirm( 'routine'" ) === true );
check( 'the explanation of the screen is behind the (?) of its section and no paragraph stands over it; a locked button has the (?) with its reason, and no word of the panel uses the unicode ellipsis',
	str_contains( $script, 'Nino.adminUi.sectionHelp(' ) === true && str_contains( $script, "'lead'" ) === false && str_contains( $script, 'Nino.adminUi.fieldHelp(' ) === true
	&& str_contains( (string) file_get_contents( $feature. '/text/de_DE.php' ). (string) file_get_contents( $feature. '/text/en_US.php' ), '…' ) === false );
check( 'the answer of the test mail is a toast and a failure stands in the line of the bar, not in the content',
	str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'bar.line.error(' ) === true && preg_match( '/hint\([^)]*msg\/sent/', $script ) === 0 );
check( 'the stylesheet names space and colour through the tokens of the workbench, writes no size, no radius and no number of its own, and every rule stands under the root of the panel', ( static function() use ( $feature ): bool {
	$css = (string) preg_replace( '~/\*.*?\*/~s', '', (string) file_get_contents( $feature. '/assets/admin.css' ) );
	$css = (string) preg_replace( '~^@layer[^;{]*;~m', '', $css );
	preg_match_all( '~([^{}]+)\{([^{}]*)\}~', $css, $rules, PREG_SET_ORDER );
	foreach( $rules as $rule ) {
		foreach( explode( ',', $rule[1] ) as $selector )
			if( str_starts_with( trim( $selector ), '@layer' ) === false && str_starts_with( trim( $selector ), '#sysadmin-list' ) === false )
				return false;
		$body = (string) preg_replace( '/var\([^)]*\)/', '', $rule[2] );
		if( preg_match( '/\d(px|rem|em)\b|#[0-9a-f]{3,6}\b|rgba?\(|border-radius/i', $body ) === 1 )
			return false;
	}
	return $rules !== [];
} )() );

echo "\n";

// Nothing of the old components API or the old stylesheet vocabulary is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $feature, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Modules\\\\Components|(?<![-\w])nino-(?!admin-)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
