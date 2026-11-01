<?php
declare(strict_types=1);

/**
 *	Nino
 *	countdown-smoke.php		Contract test for the Countdown feature
 *												(Modules\Countdown): the manifest, the activation
 *												through \Nino\Features and the words it merges,
 *												the [countdown] shortcode over its units and its
 *												formats, the timezone a wall-clock moment is read in,
 *												and every way of getting the moment wrong, the two files
 *												its manifest puts into the site's own asset bundles
 *												(\Nino\Features::registerAssets()), and deactivation.
 *												A moment is real and absolute, and the names of months
 *												and days are written in the language of the page, from
 *												the project's own words.
 *
 *												The arithmetic is the browser's, and
 *												countdown-js-smoke.js beside this file measures it;
 *												this test runs that one too where node is on the
 *												path. Travels with the feature and runs against the
 *												checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Countdown/tests/countdown-smoke.php
 *	       NINO_ROOT=../nino php features/Countdown/tests/countdown-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'countdown' );
$appData['/nino/dir'] = '';
$appData['./nino/locales/current'] = 'en_US';


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "countdown"', is_array( $manifest ) && $manifest['key'] === 'countdown' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

check( 'it is filed under ui', $manifest['category'] === 'ui' );
check( 'it requires no other feature', $manifest['requires'] === [] );
check( 'it keeps no data, and carries one setting: the timezone a countdown without tz= is read in',
	$manifest['data'] === [] && array_keys( $manifest['settings'] ) === [ 'timezone' ] );

$setting = $manifest['settings']['timezone'];
check( '...a required select that defaults to "server", which is how every countdown was read before',
	$setting['type'] === 'select' && $setting['required'] === true && $setting['default'] === 'server' );
check( '...offering "server" and every timezone PHP knows',
	isset( $setting['options']['server'], $setting['options']['UTC'], $setting['options']['Europe/Berlin'] )
	&& count( $setting['options'] ) === 1 + count( \DateTimeZone::listIdentifiers() ) );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'countdown' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Countdown', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'countdown' )['installed'] === $manifest['version'] );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged both forms of every unit into text/'. $locale. '.php',
		array_diff_key( array_flip( [
			'[[/feature/countdown/unit-day/one]]', '[[/feature/countdown/unit-day/many]]', '[[/feature/countdown/unit-hour/one]]', '[[/feature/countdown/unit-hour/many]]',
			'[[/feature/countdown/unit-minute/one]]', '[[/feature/countdown/unit-minute/many]]', '[[/feature/countdown/unit-second/one]]', '[[/feature/countdown/unit-second/many]]',
			'[[/feature/countdown/end/message]]',
		] ), $text ) === [] );
}

echo "\n";


// --- init() and the manifest's assets ----------------------------------------

echo "Modules\\Countdown::init() and the manifest's assets - the shortcode and the bundle\n";

// The bundles are filled the way \Nino\init() fills them, before the modules' init()
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [countdown]', ( $appData['./nino/html/shortcodes']['countdown'] ?? null ) !== null );
check( 'countdown.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Countdown/assets/countdown.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'countdown.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Countdown/assets/countdown.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route - nothing about a countdown reaches the server', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- [countdown] -------------------------------------------------------------

echo "[countdown] - the moment, and what stands there without a script\n";

$html = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' );

/*	The moment goes out once, as an ISO-8601 string with its offset in it, so a
	reader in another timezone counts down to the same instant rather than to
	the same wall clock	*/
check( 'the moment is written with an offset, so it means the same instant everywhere',
	preg_match( '/data-countdown-to="2026-12-24T18:00:00[+-]\d{2}:\d{2}"/', $html ) === 1 );
check( '...and the same string is the machine-readable half of a <time>',
	preg_match( '/<time class="nino-countdown-date" datetime="2026-12-24T18:00:00[+-]\d{2}:\d{2}">/', $html ) === 1 );

/*	Which offset, is the shortcode's to say. A wall-clock time is half a
	moment - 18:00 is a different instant in Berlin than it is in London - and
	Nino declares no timezone in AppData::DEFAULTS and calls no
	date_default_timezone_set(), so what was called "the site's own timezone"
	was the php process's, UTC on an installation whose host says nothing.
	'tz' is the other half, written where the moment is written, and the
	setting is the one a countdown without it is read in	*/
$processZone = date_default_timezone_get();
date_default_timezone_set( 'UTC' );

check( 'the timezone the shortcode names is the one the wall-clock moment is read in',
	\Nino\Modules\Countdown::moment( '2026-12-24 18:00', 'Europe/Berlin' )?->format( \DateTimeInterface::ATOM ) === '2026-12-24T18:00:00+01:00' );
check( '...and without it the process default is, which is what it always was',
	\Nino\Modules\Countdown::moment( '2026-12-24 18:00' )?->format( \DateTimeInterface::ATOM ) === '2026-12-24T18:00:00+00:00' );
check( '...so the two are a different instant, an hour apart',
	\Nino\Modules\Countdown::moment( '2026-12-24 18:00', 'Europe/Berlin' )?->getTimestamp() === \Nino\Modules\Countdown::moment( '2026-12-24 18:00' )?->getTimestamp() - 3600 );
check( '...and a moment that carries its own offset keeps it, whatever tz says',
	\Nino\Modules\Countdown::moment( '2026-12-24T18:00:00+05:00', 'Europe/Berlin' )?->format( \DateTimeInterface::ATOM ) === '2026-12-24T18:00:00+05:00' );

$zoned = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" tz="Europe/Berlin"]' );
check( 'the shortcode writes the moment at the offset that timezone had on the day',
	str_contains( $zoned, 'data-countdown-to="2026-12-24T18:00:00+01:00"' ) === true
	&& str_contains( $zoned, 'datetime="2026-12-24T18:00:00+01:00"' ) === true );

ninoWarnings();
check( 'a timezone this cannot read is no countdown at all, rather than one counting to the wrong instant',
	\Nino\Modules\Countdown::moment( '2026-12-24 18:00', 'Somewhere/Else' ) === null
	&& \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" tz="Somewhere/Else"]' ) === '' );
check( '...and it is said out loud, twice for the two of them',
	count( array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, '[countdown tz="Somewhere/Else"]' ) ) ) === 2 );

/*	The setting. Its default, 'server', is the process's zone and changes
	nothing for a page written before it existed; a zone chosen is the one
	every countdown without tz= and without an offset of its own is read in,
	by its rules on that day - a zone, not a fixed offset	*/
check( 'zone() is the shortcode\'s tz, else the setting\'s, else the process\'s',
	\Nino\Modules\Countdown::zone( $appData, [ 'tz' => ' Europe/London ' ] ) === 'Europe/London'
	&& \Nino\Modules\Countdown::zone( $appData, [] ) === ''
	&& \Nino\Modules\Countdown::zone( $appData, [ 'tz' => '' ] ) === '' );
check( 'a setting still on its default leaves the instant where it was',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+00:00"' ) === true );

check( 'a zone the setting does not offer is refused',
	array_keys( \Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => 'Mars/Olympus' ] ) ) === [ 'timezone' ]
	&& array_keys( \Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => '' ] ) ) === [ 'timezone' ]
	&& \Nino\Features::setting( $appData, 'countdown', 'timezone' ) === 'server' );

check( 'a zone from the options is saved', \Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => 'Europe/Berlin' ] ) === [] );
check( '...and a countdown without tz= is read in it - at the offset the day had, winter and summer',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+01:00"' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-07-01 12:00"]' ), 'data-countdown-to="2026-07-01T12:00:00+02:00"' ) === true );
check( '...a tz= still wins over it, and so does an offset the moment carries',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" tz="Europe/London"]' ), 'data-countdown-to="2026-12-24T18:00:00+00:00"' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24T18:00:00+05:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+05:00"' ) === true );
check( '...and zone() now answers it', \Nino\Modules\Countdown::zone( $appData, [] ) === 'Europe/Berlin'
	&& \Nino\Modules\Countdown::zone( $appData, [ 'tz' => 'Asia/Tokyo' ] ) === 'Asia/Tokyo' );

check( 'saving "server" again puts every countdown back where it was', \Nino\Features::saveSettings( $appData, 'countdown', [ 'timezone' => 'server' ] ) === []
	&& str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+00:00"' ) === true );

/*	A value the options no longer hold - a hand edit, a zone a later tzdata
	dropped - is not handed over: the default is, so the page renders as it
	did before and nothing is logged	*/
$appData['/nino/features']['countdown']['settings']['timezone'] = 'Mars/Olympus';
ninoWarnings();
check( 'a stored zone that is not an option falls back to "server" without a warning',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' ), 'data-countdown-to="2026-12-24T18:00:00+00:00"' ) === true
	&& ninoWarnings() === [] );
$appData['/nino/features']['countdown']['settings']['timezone'] = 'server';

date_default_timezone_set( $processZone );

/*	Without JavaScript what stands there is the date. That is the markup; the
	counter is what is hidden until countdown.js takes over, the way
	[mode-switch]'s switch is - a row of em dashes is worse than a date	*/
check( 'the date is written out for a reader too', str_contains( $html, '>2026-12-24 18:00</time>' ) === true );
check( '...and the counter is hidden until the script has it', preg_match( '/<div class="nino-countdown-parts" hidden/', $html ) === 1 );

check( 'a format given in the shortcode is used for the readable half',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" format="d.m.Y H:i"]' ), '>24.12.2026 18:00</time>' ) === true );

check( 'the four parts are drawn by default, largest first',
	preg_match_all( '/data-countdown-unit="([a-z]+)"/', $html, $found ) === 4
	&& $found[1] === [ 'days', 'hours', 'minutes', 'seconds' ] );

/*	Both forms of every unit reach the browser on the part they belong to: a
	static asset cannot read a text fill, so the script is handed the two words
	rather than the key, and only chooses between them	*/
check( 'every part carries both forms of its name, resolved before the page was sent',
	str_contains( $html, 'data-countdown-one="day" data-countdown-many="days"' ) === true
	&& str_contains( $html, 'data-countdown-one="second" data-countdown-many="seconds"' ) === true
	&& str_contains( $html, '[[/feature/countdown/' ) === false );

$some = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" units="hours,days"]' );
check( 'a shorter list of parts is taken, and always drawn largest first whatever order it was written in',
	preg_match_all( '/data-countdown-unit="([a-z]+)"/', $some, $found ) === 2 && $found[1] === [ 'days', 'hours' ] );
check( '...and a list naming nothing this knows falls back to all four rather than to an empty row',
	\Nino\Modules\Countdown::units( [ 'units' => 'fortnights' ] ) === \Nino\Modules\Countdown::UNITS_DEFAULT
	&& \Nino\Modules\Countdown::units( [ 'units' => '' ] ) === \Nino\Modules\Countdown::UNITS_DEFAULT
	&& \Nino\Modules\Countdown::units( [] ) === \Nino\Modules\Countdown::UNITS_DEFAULT );

check( 'the sentence for afterwards falls back to the project\'s own fill',
	str_contains( $html, 'data-countdown-done="The time has come"' ) === true );

$done = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" done="Der Verkauf läuft"]' );
check( '...and to what the shortcode says where it says one', str_contains( $done, 'data-countdown-done="Der Verkauf läuft"' ) === true );

$quoted = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" done="Ada & Co"]' );
check( 'a sentence with html in it is escaped, not written through',
	str_contains( $quoted, 'Ada &amp; Co' ) === true && str_contains( $quoted, '"Ada & Co"' ) === false );

echo "\n";


// --- A moment that is not one --------------------------------------------------

echo "A moment that is not one\n";

check( 'a date this cannot read is no countdown at all', \Nino\Modules\Countdown::moment( 'irgendwann' ) === null );
check( '...and it is said out loud rather than shown as a wrong date',
	count( array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, '[countdown to="irgendwann"]' ) ) ) === 1 );

check( 'an [countdown] with no moment renders nothing',
	\Nino\Html::renderHtml( $appData, '[countdown]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[countdown to=""]' ) === '' );
ninoWarnings();

check( 'a moment already past is still rendered - what has run out is what the script says so about',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2001-01-01 00:00"]' ), 'data-countdown-to="2001-01-01T00:00:00' ) === true );

echo "\n";


// --- A moment that is real -------------------------------------------------------

echo "A moment is real and absolute\n";

ninoWarnings();

check( 'a date that does not exist is no countdown: PHP reads 2027-02-30 as the 2nd of March, with a warning, and without an exception',
	\Nino\Modules\Countdown::moment( '2027-02-30' ) === null
	&& \Nino\Html::renderHtml( $appData, '[countdown to="2027-02-30"]' ) === '' );
check( '...and the log names the date, once for each of the two calls',
	count( array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, '[countdown to="2027-02-30"]' ) ) ) === 2 );
check( '...which is a verdict of getLastErrors() alone: the date is complete to date_parse() and constructs',
	( static function(): bool {
		$parsed = date_parse( '2027-02-30' );
		return is_int( $parsed['year'] ) && is_int( $parsed['month'] ) && is_int( $parsed['day'] ) && isset( $parsed['relative'] ) === false
			&& ( new \DateTimeImmutable( '2027-02-30' ) ) instanceof \DateTimeImmutable;
	} )() === true );
check( '...and so is every other day a month does not have',
	\Nino\Modules\Countdown::moment( '2026-02-29' ) === null && \Nino\Modules\Countdown::moment( '2026-04-31' ) === null );
ninoWarnings();

check( 'a relative date is no countdown: "tomorrow" would end at another instant at every rendering',
	\Nino\Modules\Countdown::moment( 'tomorrow' ) === null
	&& \Nino\Html::renderHtml( $appData, '[countdown to="tomorrow"]' ) === '' );
check( '...and the log names it, once for each of the two calls',
	count( array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, '[countdown to="tomorrow"]' ) ) ) === 2 );

$relative = [ 'next friday', '+3 days', 'now', '18:00', '24 December', '2026', '2026-12-24 +3 days', '2026-12-24 18:00 tomorrow', 'last day of december 2026', 'monday 2026-12-28' ];
$drawn		= array_filter( $relative, static fn( string $to ): bool => \Nino\Modules\Countdown::moment( $to ) !== null
	|| \Nino\Html::renderHtml( $appData, '[countdown to="'. $to. '"]' ) !== '' );
check( '...whatever its form: a day or a time of the clock, a part of the date left out, a date with something relative on it'. ( $drawn === [] ? '' : ' - drawn: '. implode( ', ', $drawn ) ),
	$drawn === [] );
ninoWarnings();

$counter = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' );
check( 'a real, absolute date with a time is the counter', str_contains( $counter, '<div class="nino-countdown" data-countdown-to="2026-12-24T18:00:00' ) === true );

$kept = [ '24 December 2026', '2026-12-24', '2026-12-24T18:00:00+05:00', '2026-12-24 18:00 Europe/Berlin', '12/24/2026' ];
$lost = array_filter( $kept, static fn( string $to ): bool => \Nino\Modules\Countdown::moment( $to ) === null
	|| str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="'. $to. '"]' ), 'data-countdown-to="2026-12-24T' ) === false );
check( '...and so is every other absolute form PHP reads: a written month, a date alone, an offset, a zone name'. ( $lost === [] ? '' : ' - refused: '. implode( ', ', $lost ) ),
	$lost === [] && ninoWarnings() === [] );
check( '...a month alone is its first day',
	\Nino\Modules\Countdown::moment( '2026-12' )?->format( 'Y-m-d' ) === '2026-12-01' && \Nino\Modules\Countdown::moment( 'Dec 2026' )?->format( 'Y-m-d' ) === '2026-12-01'
	&& str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12"]' ), 'data-countdown-to="2026-12-01T' ) === true && ninoWarnings() === [] );

/*	A Unix timestamp is an instant, whatever date_parse() says of it - relative
	to 1970, which is no clock of the rendering. An ISO week is read by PHP
	against the year it names and stays out, as the manual says	*/
check( 'a Unix timestamp is a moment, a negative one too, and it is the instant it names',
	\Nino\Modules\Countdown::moment( '@86400' )?->getTimestamp() === 86400
	&& \Nino\Modules\Countdown::moment( '@-86400' )?->getTimestamp() === -86400
	&& str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="@1798132800"]' ), 'data-countdown-to="'. ( new \DateTimeImmutable( '@1798132800' ) )->format( \DateTimeInterface::ATOM ). '"' ) === true
	&& ninoWarnings() === [] );
check( '...but only as a whole: nothing is added to it, and it is no word',
	\Nino\Modules\Countdown::moment( '@86400 +1 day' ) === null && \Nino\Modules\Countdown::moment( '@' ) === null && \Nino\Modules\Countdown::moment( '@abc' ) === null );
check( 'an ISO week is not read, and the log says so',
	\Nino\Modules\Countdown::moment( '2026-W52' ) === null
	&& count( array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, '[countdown to="2026-W52"]' ) ) ) === 1 );

echo "\n";


// --- The names of months and days ------------------------------------------------

echo "The names of months and days - in the language of the page, from the project's words\n";

// The names the keys carry are PHP's English ones in lower case, read from PHP itself
$months = [];
foreach( range( 1, 12 ) as $month )
	$months[$month] = strtolower( ( new \DateTimeImmutable( sprintf( '2026-%02d-15 12:00', $month ) ) )->format( 'F' ) );
$weekdays = [];
foreach( range( 21, 27 ) as $day )
	$weekdays[$day] = strtolower( ( new \DateTimeImmutable( '2026-12-'. $day. ' 12:00' ) )->format( 'l' ) );

$wanted = [];
foreach( [ 'month' => $months, 'month-short' => $months, 'day' => $weekdays, 'day-short' => $weekdays ] as $part => $names )
	foreach( $names as $name )
		$wanted[] = '[[/feature/countdown/'. $part. '/'. $name. ']]';

check( 'there are 19 words to a language and a kind: twelve months and seven days, whole and short', count( $wanted ) === 38 && count( array_unique( $wanted ) ) === 38 );

$ofUnit = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$ofUnit[$locale] = array_keys( array_filter( include dirname( __DIR__ ). '/install/text/'. $locale. '.php',
		static fn( string $key ): bool => preg_match( '#^\[\[/feature/countdown/(month|month-short|day|day-short)/#', $key ) === 1, ARRAY_FILTER_USE_KEY ) );
	check( 'the unit brings exactly those words in '. $locale, $ofUnit[$locale] === array_values( array_intersect( $wanted, $ofUnit[$locale] ) ) && count( $ofUnit[$locale] ) === 38 );
}
check( '...under the same names in both languages', $ofUnit['en_US'] === $ofUnit['de_DE'] );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged them into text/'. $locale. '.php, every one with a word',
		array_diff_key( array_flip( $wanted ), $text ) === [] && array_filter( $wanted, static fn( string $key ): bool => trim( (string) ( $text[$key] ?? '' ) ) === '' ) === [] );
}

/*	What a format writes, as the shortcode renders it: the text between the
	<time> tags	*/
$written = static function( array &$appData, string $moment, string $format ): string {
	$html = \Nino\Html::renderHtml( $appData, '[countdown to="'. $moment. '" format="'. $format. '"]' );
	return preg_match( '#<time class="nino-countdown-date" datetime="[^"]*">(.*?)</time>#s', $html, $found ) === 1 ? $found[1] : '';
};
// What the project says for a key, in the language of the page
$word = static fn( array &$appData, string $key ): string => \Nino\Html::renderHtml( $appData, '[[/feature/countdown/'. $key. ']]' );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {

	$appData['./nino/locales/current'] = $locale;
	$wrong = [];

	foreach( $months as $month => $name ) {
		$expected = [ $word( $appData, 'month/'. $name ), $word( $appData, 'month-short/'. $name ) ];
		if( $written( $appData, sprintf( '2026-%02d-15 12:00', $month ), 'F|M' ) !== $expected[0]. '|'. $expected[1] || str_contains( $expected[0]. $expected[1], '[[' ) === true )
			$wrong[] = $name;
	}
	foreach( $weekdays as $day => $name ) {
		$expected = [ $word( $appData, 'day/'. $name ), $word( $appData, 'day-short/'. $name ) ];
		if( $written( $appData, '2026-12-'. $day. ' 12:00', 'l|D' ) !== $expected[0]. '|'. $expected[1] || str_contains( $expected[0]. $expected[1], '[[' ) === true )
			$wrong[] = $name;
	}

	check( 'in '. $locale. ' F, M, l and D write what the keys render, for every month and every day'. ( $wrong === [] ? '' : ' - wrong: '. implode( ', ', $wrong ) ), $wrong === [] );
}

$appData['./nino/locales/current'] = 'de_DE';
$german = $written( $appData, '2026-12-24 18:00', 'F Y' );
check( 'a German page writes format="F Y" with the German word of the key, then the year',
	$german === $word( $appData, 'month/december' ). ' 2026' && str_contains( $german, '[[' ) === false );
check( '...which is not the English name PHP would have written',
	$german !== ( new \DateTimeImmutable( '2026-12-24 18:00' ) )->format( 'F Y' ) );
check( '...a whole date in the page\'s language: the day, the month, and the numbers around them',
	$written( $appData, '2026-12-24 18:00', 'l, j. F Y' ) === $word( $appData, 'day/thursday' ). ', 24. '. $word( $appData, 'month/december' ). ' 2026' );

$appData['./nino/locales/current'] = 'en_US';
$english = $written( $appData, '2026-12-24 18:00', 'F Y' );
check( 'the English page writes the English word of the same key', $english === $word( $appData, 'month/december' ). ' 2026' && $english !== $german );

check( 'an escaped letter is a letter, not a word: \F is an F, and \\\\ is a backslash before the word',
	$written( $appData, '2026-12-24 18:00', '\F Y' ) === 'F 2026'
	&& $written( $appData, '2026-12-24 18:00', '\l\D Y' ) === 'lD 2026'
	&& $written( $appData, '2026-12-24 18:00', '\\\\F' ) === '\\'. $word( $appData, 'month/december' ) );
check( 'every other letter is PHP\'s, as it always was: numbers, separators, escapes',
	$written( $appData, '2026-12-24 18:00', 'd.m.Y H:i \U\h\r' ) === '24.12.2026 18:00 Uhr'
	&& $written( $appData, '2026-12-24 18:00', 'Y-m-d H:i' ) === '2026-12-24 18:00' );

/*	No fallback in the code for a word a project may lack: the key stands there,
	the way any text fill of the page does where nobody wrote it	*/
$kept = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$kept[$locale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	$cut = $kept[$locale];
	unset( $cut['[[/feature/countdown/month/december]]'] );
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $cut );
}
check( 'a month without a word in any language shows its key, with its brackets written so that nothing reads them again, and the words around it stay',
	$written( $appData, '2026-12-24 18:00', 'F Y' ) === '&#91;&#91;/feature/countdown/month/december]] 2026'
	&& $written( $appData, '2026-11-24 18:00', 'F Y' ) === $word( $appData, 'month/november' ). ' 2026' );
foreach( $kept as $locale => $content )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );
check( '...and writing the word back writes it', $written( $appData, '2026-12-24 18:00', 'F' ) === $word( $appData, 'month/december' ) );

/*	A word is an editor's text: a fill of the template in it is not filled, a
	shortcode in it does not run, and what the date says is still the date	*/
$december = [];
foreach( [ 'en_US', 'de_DE' ] as $locale )
	$december[$locale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
$wordIs = static function( array &$appData, array $texts, string $text, string $format ): string {
	foreach( $texts as $locale => $content ) {
		$content['[[/feature/countdown/month/december]]'] = $text;
		\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );
	}
	return \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" format="'. $format. '"]' );
};

$withParts = $wordIs( $appData, $december, 'X[[parts]]Y', 'F Y' );
check( 'a word that holds [[parts]] says so, and the counter keeps its four parts',
	str_contains( $withParts, '>X&#91;&#91;parts]]Y 2026</time>' ) === true && substr_count( $withParts, 'data-countdown-unit=' ) === 4 );
$withDone = $wordIs( $appData, $december, 'X[[done]][[iso]]Y', 'F Y' );
check( '...and so does one that holds [[done]] or [[iso]]',
	str_contains( $withDone, '>X&#91;&#91;done]]&#91;&#91;iso]]Y 2026</time>' ) === true && substr_count( $withDone, 'data-countdown-done="' ) === 1 );

$withShortcode = $wordIs( $appData, $december, 'X[countdown to="2026-12-24 18:00"]Y', 'F Y' );
check( 'a word that holds a shortcode is text, and no counter is made of it',
	str_contains( $withShortcode, '>X&#91;countdown to=&quot;2026-12-24 18:00&quot;]Y 2026</time>' ) === true
	&& substr_count( $withShortcode, 'class="nino-countdown"' ) === 1 );

foreach( $december as $locale => $content )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );
check( '...and the words as they were are back', $written( $appData, '2026-12-24 18:00', 'F' ) === $word( $appData, 'month/december' ) && str_contains( $written( $appData, '2026-12-24 18:00', 'F' ), '[' ) === false );

check( 'a backslash at the very end of a format escapes nothing and is dropped, not written as a NUL byte',
	\Nino\Modules\Countdown::date( $appData, new \DateTimeImmutable( '2026-12-24 18:00' ), 'Y\\' ) === '2026'
	&& \Nino\Modules\Countdown::date( $appData, new \DateTimeImmutable( '2026-12-24 18:00' ), 'F\\' ) === $word( $appData, 'month/december' ) );

$escaped = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" format="j. F \<\b\>"]' );
check( 'the words are escaped with the rest of the date: no markup comes out of a format', str_contains( $escaped, '&lt;b&gt;' ) === true && str_contains( $escaped, '<b>' ) === false );
ninoWarnings();

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/countdown.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/countdown.js' );

check( 'the arithmetic is the browser\'s - a page cached for an hour would otherwise be an hour wrong',
	str_contains( $js, 'Date.now()' ) === true && str_contains( $js, 'Date.parse(' ) === true );
check( 'one timer serves every countdown on the page, and stops when the last one has run out',
	substr_count( $js, 'setInterval' ) === 1 && str_contains( $js, 'clearInterval' ) === true );
check( 'nothing about a countdown reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

/*	data-countdown-done is where the sentence for afterwards is kept. A flag
	written into it would destroy the sentence one line before it is read	*/
check( 'the "already finished" flag is the class, not the attribute the sentence is kept in',
	str_contains( $js, "classList.contains( 'nino-is-done' )" ) === true
	&& str_contains( $js, 'dataset.countdownDone' ) === false );

check( 'the numbers are tabular, so the row does not breathe once a second',
	str_contains( $css, 'tabular-nums' ) === true );
check( 'the stylesheet keeps the hidden counter hidden, which its own display rule would otherwise undo',
	str_contains( $css, '.nino-countdown-parts[hidden]' ) === true );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so countdown-js-smoke.js is not run here\n\n";
else {
	echo "countdown-js-smoke.js\n";
	// Over the same checkout: the script is started through the kernel's own Nino.js
	$out = (string) @shell_exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/countdown-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Components ----------------------------------------------------------------

echo "Components - [countdown] as the Builder offers it\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[countdown to="2026-12-24 18:00"]',
		'[countdown to="2026-12-24 18:00" tz="Europe/Berlin"]',
		'[countdown to="2026-12-24 18:00" units="days,hours,minutes"]',
		'[countdown to="2026-12-24 18:00" done="Es ist so weit"]',
		'[countdown to="2026-12-24 18:00" format="d.m.Y H:i"]',
		'[countdown to="2026-12-24 18:00" tz="Somewhere/Else"]',
		'[countdown to="irgendwann"]',
		'[countdown]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'countdown' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Countdown::init( $probe );
	ninoWarnings();

	check( 'the component is registered with the schema the manifest declares',
		( \Nino\Modules\Components::components( $probe )['countdown'] ?? null ) === ( $declared['countdown'] ?? false ) );
	check( '...and what the feature registers is answered once - the component by its wrapper',
		\Nino\Features::shortcodes( $probe, 'countdown' ) === [ 'countdown' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/countdown' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'countdown' ) === [ 'to' => '', 'tz' => '', 'units' => '', 'done' => '', 'format' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	// A call written the Builder's way: the moment and what differs from the
	// defaults, nothing else - and the same call with every default written out
	$short = \Nino\Html::renderHtml( $probe, '[countdown to="2026-12-24 18:00" class="launch"]' );
	check( 'a class of its own comes after the counter\'s own, on the element that carries the moment',
		str_contains( $short, '<div class="nino-countdown launch" data-countdown-to="2026-12-24T18:00:00' ) === true );
	check( 'no class leaves the markup as it was, with no space left behind',
		str_contains( $before['[countdown to="2026-12-24 18:00"]'], '<div class="nino-countdown" data-countdown-to=' ) === true );
	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[countdown to="2026-12-24 18:00" tz="" units="" done="" format="" class=""]' ) === $before['[countdown to="2026-12-24 18:00"]'] );
	check( 'a class is escaped, and a bracket in it opens nothing in the second pass',
		str_contains( \Nino\Html::renderHtml( $probe, '[countdown to="2026-12-24 18:00" class="a\'b [c"]' ), 'nino-countdown a&quot;b &#91;c"' ) === true );
	check( 'without a moment the component draws nothing, the wrapper changing nothing about it',
		\Nino\Html::renderHtml( $probe, '[countdown class="launch"]' ) === '' );
	ninoWarnings();
} )( $appData );

echo "\n";


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'countdown' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Countdown', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/countdown/unit-day/many]]'] ) === true );

ninoDone( $appData );
