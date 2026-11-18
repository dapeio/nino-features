<?php
declare(strict_types=1);

// The test of the feature Countdown: structure and behaviour, one pin each. php features/Countdown/tests/countdown-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

// A wall-clock moment is read in the zone of the process where nothing says another: the test fixes it
date_default_timezone_set( 'UTC' );

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form and no data', is_array( $manifest ) === true && $manifest['key'] === 'countdown' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['data'] === [] && $manifest['assets'] === [ '/features/Countdown/assets/countdown.js' ] );
$zone = $manifest['settings']['timezone'] ?? [];
check( 'the one setting is the zone a call without tz is read in: a required select, "server" by default, with every zone PHP knows', array_keys( $manifest['settings'] ) === [ 'timezone' ] && ( $zone['required'] ?? false ) === true && ( $zone['default'] ?? '' ) === 'server'
	&& array_keys( $zone['options'] ?? [] ) === array_merge( [ 'server' ], \DateTimeZone::listIdentifiers() ) );

$appData = ninoSandbox( 'countdown' );
$appData['./nino/filesystem/path'] = $root;
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'countdown' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
// By reference: the page is drawn from the app data as it is when it is drawn, a setting saved between two drawings included
$draw = static function( string $source ) use ( &$appData ): string {
	return trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
};
$ignored = ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'countdown' ) === [] );
$schema = \Nino\Components::components( $appData )['countdown'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table and a standard part', ( $schema['group'] ?? '' ) === 'countdown' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && ( \Nino\Components::groups( $appData )['countdown']['part'] ?? false ) === true );
check( 'every attribute has a label in both languages', array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === [] );
check( 'the date is a string with a hint and no more: the schema knows no date type, and the kernel gives a string no date input', ( $schema['attributes']['to']['type'] ?? '' ) === 'string' && ( $schema['attributes']['to']['hint']['en_US'] ?? '' ) !== '' && isset( $schema['attributes']['to']['input'] ) === false );
$part = (string) file_get_contents( $dir. '/groups/countdown/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'countdown' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.countdown-part' ) === true );
check( 'a tool offers it in a column, with the looks tiles and big beside the plain one', in_array( 'countdown', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& array_keys( \Nino\Components::variants( $appData, 'countdown' )['variants'] ?? [] ) === [ 'regular', 'tiles', 'big' ] );
check( 'nothing in the part moves: no animation, no transition, so there is nothing for "less motion" to take away', preg_match( '/\banimation\b|\btransition\b|@keyframes/', $part ) === 0 );

// The moment, once, with its offset: the same instant for every reader
$html = $draw( '[countdown to="2026-12-24 18:00"]' );
check( 'the moment is written once with its offset, as the data of the counter and as the machine-readable half of the <time> that stands there without a script',
	str_contains( $html, 'data-countdown-to="2026-12-24T18:00:00+00:00"' ) === true && str_contains( $html, '<time class="countdown-date" datetime="2026-12-24T18:00:00+00:00">2026-12-24 18:00</time>' ) === true );
check( 'the counter is hidden until the script has it, and says nothing aloud every second', str_contains( $html, '<div class="countdown-parts" hidden aria-live="off">' ) === true );

check( 'the zone of the call is where the wall clock is read, the setting\'s where the call says none, and an offset the moment carries wins over both',
	str_contains( $draw( '[countdown to="2026-12-24 18:00" tz="Europe/Berlin"]' ), 'data-countdown-to="2026-12-24T18:00:00+01:00"' ) === true
	&& \Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => 'Europe/Berlin' ] ) === []
	&& str_contains( $draw( '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+01:00"' ) === true
	&& str_contains( $draw( '[countdown to="2026-07-01 12:00"]' ), 'data-countdown-to="2026-07-01T12:00:00+02:00"' ) === true
	&& str_contains( $draw( '[countdown to="2026-12-24 18:00" tz="Europe/London"]' ), 'data-countdown-to="2026-12-24T18:00:00+00:00"' ) === true
	&& str_contains( $draw( '[countdown to="2026-12-24T18:00:00+05:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+05:00"' ) === true );
check( 'a zone the setting does not offer is refused and a zone the call does not offer is the setting\'s', array_keys( \Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => 'Mars/Olympus' ] ) ) === [ 'timezone' ]
	&& str_contains( $draw( '[countdown to="2026-12-24 18:00" tz="Mars/Olympus"]' ), 'data-countdown-to="2026-12-24T18:00:00+01:00"' ) === true );
\Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => 'server' ] );

// A moment is absolute and valid or it is none
$ignored = ninoWarnings();
$rejected = array_filter( [ '', 'irgendwann', 'tomorrow', '+3 days', 'next friday', '2026-12-24 +3 days', '18:00', 'December 24', '2026-W52', '2027-02-30', '2026-13-01', 'Somewhere/Else' ], static fn( string $to ): bool => $draw( '[countdown to="'. $to. '"]' ) !== '' );
check( 'a moment that is not absolute or not valid draws nothing: relative, a day of the clock alone, a date without a year or with a day the month has not'. ( $rejected === [] ? '' : ' - drawn: '. implode( ', ', $rejected ) ), $rejected === [] );
$lost = array_filter( [ '2026-12-24', '2026-12-24 18:00:00', 'Dec 24 2026 6pm', '2026-12-24T18:00:00Z', '2026-12-24T18:00:00+05:00', '2026-12-24 18:00 Europe/Berlin', '2026-12', '@1798132800', '@-86400' ], static fn( string $to ): bool => str_contains( $draw( '[countdown to="'. $to. '"]' ), 'data-countdown-to="' ) === false );
check( 'every absolute form PHP reads is a counter: a date, a written month, an offset, a zone name, a month alone and a Unix timestamp'. ( $lost === [] ? '' : ' - refused: '. implode( ', ', $lost ) ), $lost === [] );
$said = ninoWarnings();
check( 'a moment that is not one is said out loud, cut and cleaned of what would break a line of the log', count( $said ) >= 10 && array_filter( $said, static fn( string $w ): bool => preg_match( '/[\x00-\x1F]/', $w ) === 1 ) === [] );
$ignored = ninoWarnings();
\Nino\Modules\Countdown::moment( "x\n[FORGED] ". str_repeat( 'y', 400 ), '' );
\Nino\Modules\Countdown::moment( '2026-12-24 18:00', "Mars\r\n[FORGED]" );
$long = ninoWarnings();
check( 'a long moment and a zone with line ends in them reach the log on one line each, cut', count( $long ) === 2 && array_filter( $long, static fn( string $w ): bool => preg_match( '/[\x00-\x1F]/', $w ) === 1 || strlen( $w ) > 320 ) === [] );

// The parts
$names = static fn( string $html ): array => preg_match_all( '/data-countdown-unit="([a-z]+)"/', $html, $found ) === false ? [] : $found[1];
check( 'the four parts are drawn by default, largest first', $names( $html ) === [ 'days', 'hours', 'minutes', 'seconds' ] );
check( 'a part that is switched off is left out, the rest stays in the order of the sizes, and none switched on is all four',
	$names( $draw( '[countdown to="2026-12-24 18:00" seconds="0" days="1" minutes="1" hours="0"]' ) ) === [ 'days', 'minutes' ]
	&& $names( $draw( '[countdown to="2026-12-24 18:00" days="0" hours="0" minutes="0" seconds="0"]' ) ) === [ 'days', 'hours', 'minutes', 'seconds' ] );
check( 'every part carries both forms of its name, resolved before the page was sent, and the name of the plural shows without a script', str_contains( $draw( '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-unit="days" data-countdown-one="Tag" data-countdown-many="Tage"><span class="countdown-value">&mdash;</span><span class="countdown-name">Tage</span>' ) === true );

// The sentence for afterwards
check( 'the sentence for afterwards is the word of the project where the call says none, and the call\'s where it says one',
	str_contains( $draw( '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-done="Es ist so weit"' ) === true && str_contains( $draw( '[countdown to="2026-12-24 18:00" done="Der Verkauf läuft"]' ), 'data-countdown-done="Der Verkauf läuft"' ) === true );
$hostile = $draw( '[countdown to="2026-12-24 18:00" done="&quot;><script>alert(1)</script> [csrf] [[/project/company/general/name]]" dateFormat="&quot;><img src=x onerror=alert(1)> [csrf]"]' );
check( 'a sentence and a date format that spell a tag, a call or a fill are text in the page and open nothing', str_contains( $hostile, '<script' ) === false && str_contains( $hostile, '<img' ) === false
	&& str_contains( $hostile, 'name="csrf"' ) === false && str_contains( $hostile, 'data-countdown-done="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt; [csrf] "' ) === true );
check( 'a sentence is escaped once', str_contains( $draw( '[countdown to="2026-12-24 18:00" done="a & b"]' ), 'data-countdown-done="a &amp; b"' ) === true );

// The date under the counter, in the words of the page
$moment = \Nino\Modules\Countdown::moment( '2026-12-24 18:00' );
$date = static function( string $format ) use ( &$appData, $moment ): string {
	return \Nino\Modules\Countdown::date( $appData, $moment, $format );
};
$word = static function( string $key ) use ( &$appData ): string {
	return (string) \Nino\Html::resolveTextfill( $appData, '/feature/countdown/'. $key );
};
check( 'F, M, l and D are the words of the project in the language of the page, every other letter is PHP\'s',
	$date( 'l, j. F Y' ) === $word( 'day/thursday' ). ', 24. '. $word( 'month/december' ). ' 2026' && $date( 'D M' ) === $word( 'day-short/thursday' ). ' '. $word( 'month-short/december' ) && $date( 'd.m.Y H:i \U\h\r' ) === '24.12.2026 18:00 Uhr'
	&& $word( 'month/december' ) !== '' && str_contains( $date( 'F' ), '[[' ) === false );
check( 'an escaped letter is a letter, and a backslash at the very end escapes nothing and is dropped, not written as a NUL byte',
	$date( '\F Y' ) === 'F 2026' && $date( '\l\D' ) === 'lD' && $date( 'Y\\' ) === '2026' && str_contains( $date( 'F\\' ), "\0" ) === false );

// The words are texts of the project: what they say is text in the date, and a month nobody has a word for shows its key
$texts = [];
foreach( [ 'en_US', 'de_DE' ] as $locale )
	$texts[$locale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
// A word is set for every language, and the words of the unit are put back
$with = static function( array $words ) use ( &$appData, $texts ): void {
	foreach( $texts as $locale => $content ) {
		foreach( $words as $key => $text ) {
			unset( $content['[['. $key. ']]'] );
			if( $text !== null )
				$content['[['. $key. ']]'] = $text;
		}
		\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );
	}
};
$with( [ '/feature/countdown/month/december' => '<i>[csrf]</i>" [countdown to="2026-12-24 18:00"] X[[parts]]Y' ] );
$written = $draw( '[countdown to="2026-12-24 18:00" dateFormat="F Y"]' );
check( 'a word that spells a tag, a call or a fill is text in the date: it opens nothing and the counter keeps its four parts', str_contains( $written, '<i>' ) === false && str_contains( $written, '&lt;i&gt;[csrf]&lt;/i&gt;&quot; [countdown to=&quot;2026-12-24 18:00&quot;] X[[parts]]Y 2026</time>' ) === true
	&& substr_count( $written, 'class="countdown-part"' ) === 4 && substr_count( $written, 'class="countdown"' ) === 1 );
$with( [ '/feature/countdown/month/december' => null ] );
check( 'a month nobody has a word for shows its key, and the words around it stay', str_contains( $date( 'F Y' ), '[[/feature/countdown/month/december]] 2026' ) === true && $date( 'M' ) === $word( 'month-short/december' ) );
$with( [ '/feature/countdown/unit-day/one' => '<i>"', '/feature/countdown/unit-day/many' => '"><b>x</b>', '/feature/countdown/end/message' => '"><u>done</u>' ] );
$named = $draw( '[countdown to="2026-12-24 18:00"]' );
check( 'the names of the parts and the sentence of the project for afterwards are text in the markup, wherever they stand: they break out of no attribute',
	str_contains( $named, '<i>' ) === false && str_contains( $named, '<b>' ) === false && str_contains( $named, '<u>' ) === false && str_contains( $named, 'data-countdown-one="&lt;i&gt;&quot;"' ) === true
	&& str_contains( $named, 'data-countdown-done="&quot;&gt;&lt;u&gt;done&lt;/u&gt;"' ) === true );
foreach( $texts as $locale => $content )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );

// The words the unit brings: both languages, the same keys, every one a text the code reads
$ofUnit = [];
foreach( [ 'de_DE', 'en_US' ] as $locale )
	$ofUnit[$locale] = array_keys( (array) include $dir. '/install/text/'. $locale. '.php' );
$asked = [ '/feature/countdown/end/message' ];
foreach( [ 'day', 'hour', 'minute', 'second' ] as $unit )
	foreach( [ 'one', 'many' ] as $form )
		$asked[] = '/feature/countdown/unit-'. $unit. '/'. $form;
foreach( [ 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december' ] as $month )
	array_push( $asked, '/feature/countdown/month/'. $month, '/feature/countdown/month-short/'. $month );
foreach( [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ] as $day )
	array_push( $asked, '/feature/countdown/day/'. $day, '/feature/countdown/day-short/'. $day );
check( 'both languages bring the same words, and they are exactly the ones the code asks for: both forms of four units, twelve months and seven days whole and short, and the sentence for afterwards',
	$ofUnit['de_DE'] === $ofUnit['en_US'] && count( $asked ) === 8 + 24 + 14 + 1 && array_diff( array_map( static fn( string $key ): string => '[['. $key. ']]', $asked ), $ofUnit['en_US'] ) === [] && count( $ofUnit['en_US'] ) === count( $asked ) );

// Variants and the sign between the parts are classes of the register
check( 'a look and a separator are classes, and the plain look with no separator writes none', str_contains( $draw( '[countdown to="2026-12-24 18:00" variant="big" separator="dot"]' ), 'class="countdown countdown--separator-dot countdown--variant-big"' ) === true
	&& str_contains( $html, 'class="countdown"' ) === true && str_contains( $draw( '[countdown to="2026-12-24 18:00" variant="neon" separator="star"]' ), 'class="countdown"' ) === true );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the component three times', ( $groups['countdown']['demo'] ?? '' ) !== '' && substr_count( $draw( $groups['countdown']['demo'] ), 'class="countdown-parts"' ) === 3 );
$block = \Nino\Blocks::get( $appData, 'countdown-launch' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'countdown', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'countdown-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[countdown to="2026-12-24 18:00"]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-countdown|nino-is-done|--color-|--space-[0-9]|--radius-|--fontsize-|--text-[0-9]/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/design' ) === false && is_dir( $dir. '/templates' ) === false );

// The script that counts runs over the kernel's stand-in of a page (countdown-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: countdown-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/countdown-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'countdown-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
