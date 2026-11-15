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
 *												What it brings the Design feature - the three looks in
 *												design/, the style of the separator in design/styles.css
 *												and design/styles/, the block of the preview - is held
 *												to the grammar of the Design feature's manual: the heads,
 *												the ladders, the knobs each look answers to, the place of
 *												every default in countdown.css; and, where the Design
 *												feature stands beside it, read and compiled by it.
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


/**
 *	The rules of a stylesheet, its comments left out: for each the at-rule it stands in ('' for none), its selector with the
 *	whitespace folded and the text of its body. One level of at-rule is read, which is as deep as the stylesheets here go
 *
 *	@param		string		$css
 *
 *	@return 	array<int, array{at: string, selector: string, body: string}>
 */
function countdownRules( string $css ): array {

	$rules	= [];
	$read		= static function( string $text, string $at ) use ( &$read, &$rules ): void {

		$offset = 0;

		while( ( $open = strpos( $text, '{', $offset ) ) !== false ) {

			$depth	= 1;
			$close	= $open + 1;

			for( ; $close < strlen( $text ) && $depth > 0; $close++ )
				$depth += $text[$close] === '{' ? 1 : ( $text[$close] === '}' ? -1 : 0 );

			$selector	= trim( (string) preg_replace( '/\s+/', ' ', substr( $text, $offset, $open - $offset ) ) );
			$body			= substr( $text, $open + 1, $close - $open - 2 );

			if( str_starts_with( $selector, '@' ) === true )
				$read( $body, $selector );
			else
				$rules[] = [ 'at' => $at, 'selector' => $selector, 'body' => $body ];

			$offset = $close;
		}
	};

	$read( (string) preg_replace( '#/\*.*?\*/#s', '', $css ), '' );

	return $rules;
}

/**
 *	The declarations in the body of a rule, in the order they stand, the whitespace of each value folded
 *
 *	@param		string		$body
 *
 *	@return 	array<int, array{property: string, value: string}>
 */
function countdownDeclarations( string $body ): array {

	preg_match_all( '/(?:^|;)\s*(-{0,2}[a-z][a-z0-9-]*)\s*:\s*([^;]*)/i', $body, $found, PREG_SET_ORDER );

	return array_map( static fn( array $match ): array => [ 'property' => $match[1], 'value' => trim( (string) preg_replace( '/\s+/', ' ', $match[2] ) ) ], $found );
}

/**
 *	A stylesheet of the Design feature's grammar, read as it is written: the tags of its head (tag => the texts after it, one
 *	for each line), the rules after the head, and the custom property each declaration sets (property => [ selector, value ]
 *	for each). Nothing is compared with what a head says - that is the file's to say
 *
 *	@param		string		$file
 *
 *	@return 	array{tags: array<string, array<int, string>>, rules: array<int, array{at: string, selector: string, body: string}>, declared: array<string, array<int, array{selector: string, value: string}>>}
 */
function countdownSheet( string $file ): array {

	$css	= (string) file_get_contents( $file );
	$tags	= [];

	preg_match( '#^\s*/\*(.*?)\*/#s', $css, $comment );

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 )
			$tags[ $found[1] ][] = $found[2];

	$sheet = [ 'tags' => $tags, 'rules' => countdownRules( $css ), 'declared' => [] ];

	foreach( $sheet['rules'] as $rule )
		foreach( countdownDeclarations( $rule['body'] ) as $declaration )
			$sheet['declared'][ $declaration['property'] ][] = [ 'selector' => $rule['selector'], 'value' => $declaration['value'] ];

	return $sheet;
}

/**
 *	The colours a stylesheet writes as a literal in the value of a declaration: a hex colour or a colour function. What a value
 *	reads of the kernel - var(--color-ink) - is the name of a property and none of these
 *
 *	@param		string		$css
 *
 *	@return 	array<int, string>				property: literal, for each
 */
function countdownColourLiterals( string $css ): array {

	$found = [];

	foreach( countdownRules( $css ) as $rule )
		foreach( countdownDeclarations( $rule['body'] ) as $declaration )
			if( preg_match( '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(/i', (string) preg_replace( '/--[a-z0-9-]+/i', '', $declaration['value'] ), $match ) === 1 )
				$found[] = $declaration['property']. ': '. $match[0];

	return $found;
}


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


// --- The looks of the Design feature ----------------------------------------------

echo "design/ - the looks, the separator and the block that the Design feature reads\n";

/*	What a feature brings the Design feature as files (its manual, under Base, says what a variant, a style and a block are). Read
	here by their grammar - which tags, which fields, which names, which rule where - and what a head says is never compared: the
	words are the files' to say. The base stylesheet owns the layout and declares every property a look moves, the looks move them	*/
$design 		= dirname( __DIR__ ). '/design';
$base 			= countdownSheet( dirname( __DIR__ ). '/assets/countdown.css' );
$looks 			= [];

foreach( glob( $design. '/*.css' ) ?: [] as $file )
	if( basename( $file ) !== 'styles.css' )
		$looks[ basename( $file, '.css' ) ] = countdownSheet( $file );

ksort( $looks );

$kinds			= [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
$tabs				= [ 'type', 'surfaces', 'colours', 'motion' ];
$inkWords		= [ 'ink', 'subtitle', 'title', 'accent', 'primary', 'border', 'focus' ];
$surfaces		= [ 'default', 'alt', 'tint', 'dark', 'black', 'brand', 'brand-safe', 'accent-safe', 'success', 'warning', 'danger' ];
$steps			= [ 'scale' => [ 'least', 'less', 'default', 'more', 'most' ], 'choice' => [ 'less', 'default', 'more' ] ];
$stepSuffix	= '/--(?:least|less|default|more|most)$/';
$knobTable	= [ 'size' => [ 'scale', 'type' ], 'gap' => [ 'scale', 'surfaces' ], 'corners' => [ 'scale', 'surfaces' ], 'names' => [ 'choice', 'surfaces' ], 'tile' => [ 'ground', 'colours' ], 'digits' => [ 'ink', 'colours' ] ];

check( 'the feature brings three looks of the Design feature, a stylesheet each in design/ - and regular, which is where a feature starts from, is the plain one - with a block for the preview, and the styles of all of them in styles.css and design/styles/',
	array_keys( $looks ) === [ 'big', 'regular', 'tiles' ] && is_file( $design. '/preview.tpl' ) === true && is_file( $design. '/styles.css' ) === true && is_dir( $design. '/styles' ) === true );

$headless = [];

foreach( $looks as $variant => $look )
	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $look['tags'][$tag] ?? [] ) !== 1 || trim( $look['tags'][$tag][0] ) === '' )
			$headless[] = $variant. ' '. $tag;

check( 'each head names and describes its look in both languages, once each: @name, @name-de, @description, @description-de'. ( $headless === [] ? '' : ' - '. implode( ', ', $headless ) ), $headless === [] && $looks !== [] );

/** The knobs a head declares: name => kind, tab, label, default (null where the line has none), and how often it was declared */
$knobsOf = [];

foreach( $looks as $variant => $look )
	foreach( $look['tags']['@knob'] ?? [] as $text ) {
		$fields = (array) preg_split( '/\s+/', $text, 4 );
		preg_match( '/^(.*?)(?:\s+=\s+(\S+))?$/', (string) ( $fields[3] ?? '' ), $label );
		$knobsOf[$variant][ (string) $fields[0] ][] = [ 'fields' => count( $fields ), 'kind' => (string) ( $fields[1] ?? '' ), 'tab' => (string) ( $fields[2] ?? '' ), 'label' => (string) ( $label[1] ?? '' ), 'default' => $label[2] ?? null ];
	}

$knobProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( $knobsOf[$variant] ?? [] as $name => $lines ) {
		$line = $lines[0];

		if( count( $lines ) !== 1 || $line['fields'] !== 4 || in_array( $line['kind'], $kinds, true ) === false || in_array( $line['tab'], $tabs, true ) === false || $line['label'] === '' )
			$knobProblems[] = $variant. ' @knob '. $name;

		$twins = array_filter( $look['tags']['@knob-de'] ?? [], static fn( string $text ): bool => preg_match( '/^'. preg_quote( $name, '/' ). '\s+\S/', $text ) === 1 );

		if( count( $twins ) !== 1 )
			$knobProblems[] = $variant. ' @knob-de '. $name;
	}

	foreach( $look['tags']['@knob-de'] ?? [] as $text )
		if( isset( $knobsOf[$variant][ (string) strtok( $text, " \t" ) ] ) === false )
			$knobProblems[] = $variant. ' @knob-de '. strtok( $text, " \t" ). ' names no knob';
}

check( 'each @knob line has its four fields - the name, a kind, a tab, the words - once, and a @knob-de beside it'. ( $knobProblems === [] ? '' : ' - '. implode( ', ', $knobProblems ) ), $knobProblems === [] && $knobsOf !== [] );

$offTable = [];

foreach( $knobsOf as $variant => $declared )
	foreach( $declared as $name => $lines )
		if( ( $knobTable[$name] ?? null ) !== [ $lines[0]['kind'], $lines[0]['tab'] ] )
			$offTable[] = $variant. ' '. $name;

check( 'every knob is one of the six, of the same kind and on the same tab in every look: the size of the digits on Text; the space between the parts, where the name stands and the corners of a tile on Surface; the ground of a tile and the colour of the digits on Colour'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ),
	$offTable === [] && array_keys( $knobsOf['tiles'] ?? [] ) === array_keys( $knobTable ) );
check( 'a look declares only what it answers to: Tiles all six, Big all but the corners and the ground of a tile, Plain only the size, the space and the place of the names - it paints nothing, so it has no colour for the digits either; a knob a look does not declare is greyed out in the panel, with a line that says so',
	array_keys( $knobsOf['regular'] ?? [] ) === [ 'size', 'gap', 'names' ] && array_keys( $knobsOf['big'] ?? [] ) === [ 'size', 'gap', 'names', 'digits' ] );

$optionProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( [ '@option', '@option-de' ] as $tag ) {
		$words = [];

		foreach( $look['tags'][$tag] ?? [] as $text ) {
			$fields = (array) preg_split( '/\s+/', $text, 3 );

			if( count( $fields ) !== 3 || ( $knobsOf[$variant][ $fields[0] ][0]['kind'] ?? '' ) !== 'choice' || in_array( $fields[1], $steps['choice'], true ) === false )
				$optionProblems[] = $variant. ' '. $tag. ' '. $text;
			else
				$words[ $fields[0] ][ $fields[1] ][] = $fields[2];
		}

		foreach( $knobsOf[$variant] ?? [] as $name => $lines )
			if( $lines[0]['kind'] === 'choice' ) {
				foreach( $steps['choice'] as $step )
					if( count( $words[$name][$step] ?? [] ) !== 1 )
						$optionProblems[] = $variant. ' '. $tag. ' '. $name. ' '. $step;

				if( count( array_unique( array_map( static fn( array $each ): string => (string) $each[0], $words[$name] ?? [] ) ) ) !== 3 )
					$optionProblems[] = $variant. ' '. $tag. ' '. $name. ' has words that are not three';
			}
	}

	foreach( $knobsOf[$variant] ?? [] as $name => $lines ) {
		$default = $lines[0]['default'];

		if( in_array( $lines[0]['kind'], [ 'scale', 'choice' ], true ) === true ? $default !== null : ( $lines[0]['kind'] === 'ink' ? in_array( $default, $inkWords, true ) === false : ( $lines[0]['kind'] === 'ground' && in_array( $default, $surfaces, true ) === false ) ) )
			$optionProblems[] = $variant. ' default of '. $name;
	}
}

check( 'a choice has an @option and an @option-de for each of its three steps, each its own words, and an ink or a ground a default that is a tone of the kernel\'s tokens or a surface of the palette - a scale and a choice none'. ( $optionProblems === [] ? '' : ' - '. implode( ', ', $optionProblems ) ), $optionProblems === [] && $looks !== [] );

/** The ladders a look declares: the property each rung is named after, without its step, for the knobs of its head that are scales and choices - a choice with the aspects it declares beside its own */
$stemsOf = static function( array $look, array $declared ) use ( $stepSuffix ): array {

	$found = [];

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 )
			$found[] = (string) preg_replace( $stepSuffix, '', $property );

	$stems = [];

	foreach( $declared as $name => $lines )
		if( in_array( $lines[0]['kind'], [ 'scale', 'choice' ], true ) === true )
			$stems[$name] = $lines[0]['kind'] === 'choice'
				? array_values( array_filter( array_unique( $found ), static fn( string $stem ): bool => $stem === '--countdown-'. $name || str_starts_with( $stem, '--countdown-'. $name. '-' ) ) )
				: [ '--countdown-'. $name ];

	return $stems;
};

$ladderProblems = [];
$aspects 				= [];

foreach( $looks as $variant => $look ) {
	$stems = [];

	foreach( $stemsOf( $look, $knobsOf[$variant] ?? [] ) as $name => $list )
		foreach( $list as $stem ) {
			$stems[] = $stem;

			foreach( $steps[ $knobsOf[$variant][$name][0]['kind'] ] as $step )
				if( count( $look['declared'][ $stem. '--'. $step ] ?? [] ) !== 1 || $look['declared'][ $stem. '--'. $step ][0]['selector'] !== ':root' )
					$ladderProblems[] = $variant. ' lacks '. $stem. '--'. $step. ' on :root, once';

			if( $knobsOf[$variant][$name][0]['kind'] === 'choice' )
				$aspects[$variant] = array_merge( $aspects[$variant] ?? [], [ $stem ] );
		}

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 && in_array( (string) preg_replace( $stepSuffix, '', $property ), $stems, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs and a choice its three - the choice for its own property and for each aspect of it, the direction and the alignment of a part - once each, on :root, and every ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ),
	$ladderProblems === [] && count( $aspects ) === 3 && count( array_unique( array_map( 'serialize', $aspects ) ) ) === 1 && count( (array) reset( $aspects ) ) > 1 );

$baseDeclared	= $base['declared'];
$ruleProblems	= [];

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule ) {
		$properties = array_column( countdownDeclarations( $rule['body'] ), 'property' );

		// The ladders stand on :root, a rule is on the classes of the counter and on nothing else - no element, no id, no class of the kernel's
		if( $rule['selector'] === ':root' ) {
			foreach( $properties as $property )
				if( preg_match( '/^--countdown-[a-z0-9-]+--(?:least|less|default|more|most)$/', $property ) !== 1 )
					$ruleProblems[] = $variant. ' declares '. $property. ' on :root, which is no rung of a ladder';
		}
		elseif( $rule['at'] !== '' || preg_match( '/^[\s>+~,]*$/', (string) preg_replace( '/\.nino-countdown[a-z0-9-]*/', '', $rule['selector'] ) ) !== 1 || str_contains( $rule['selector'], '.nino-countdown' ) === false )
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];

		foreach( countdownDeclarations( $rule['body'] ) as $declaration ) {
			preg_match_all( '/var\(\s*(--countdown-[a-z0-9-]+)/', $declaration['value'], $reads );

			foreach( $reads[1] as $read )
				if( isset( $baseDeclared[$read] ) === false )
					$ruleProblems[] = $variant. ' reads '. $read. ', which countdown.css does not declare';
		}
	}

check( 'a look is a head, the rungs of its ladders on :root and rules on the counter\'s own classes - never an element, an id or a class of the kernel - and it reads only properties that countdown.css declares'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 3 );

$colourProblems = [];

foreach( array_merge( array_map( static fn( string $variant ): string => $design. '/'. $variant. '.css', array_keys( $looks ) ), [ $design. '/styles.css', dirname( __DIR__ ). '/assets/countdown.css' ], glob( $design. '/styles/*.css' ) ?: [] ) as $file ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );

	foreach( countdownColourLiterals( $code ) as $literal )
		$colourProblems[] = basename( $file ). ' '. $literal;

	if( stripos( $code, '!important' ) !== false )
		$colourProblems[] = basename( $file ). ' !important';
}

check( '...and no stylesheet of the feature writes a colour as a literal or declares anything !important: the colours are a ground, a tone or what the page says'. ( $colourProblems === [] ? '' : ' - '. implode( ', ', $colourProblems ) ), $colourProblems === [] );

/*	A tile is a ground of its own, and what stands in it - the digits and the name - is read on it: the roles the page writes with are passed on to them from the
	roles the palette solved for the ground, as Nino.css does for its grounds, and from nowhere else - the part that holds the sign between the tiles has no such
	rule, so the sign keeps the tone of the section. Without it a tone chosen for the digits is the section's, white on a dark one, on a light tile	*/
$passed 	= [];
$passedAt = [];

foreach( $looks['tiles']['rules'] ?? [] as $rule )
	foreach( countdownDeclarations( $rule['body'] ) as $declaration )
		if( str_starts_with( $declaration['property'], '--color-' ) === true ) {
			$passed[ $declaration['property'] ] = $declaration['value'];
			$passedAt[ $rule['selector'] ] = true;
		}

$roles = [
	'--color-ink' => 'var(--countdown-tile-on)', '--color-title' => 'var(--countdown-tile-on)', '--color-subtitle' => 'var(--countdown-tile-on-muted)',
	'--color-accent' => 'var(--countdown-tile-link)', '--color-border' => 'var(--countdown-tile-border)', '--color-focus' => 'var(--countdown-tile-focus)',
];

check( 'Tiles pass the roles of their ground on to what stands in them: the ink and the title as its text colour, the subtitle as the quieter one, the accent as its link, the border and the focus - on the digits and the names and on nothing else, not on the part, which holds the sign between the tiles',
	$passed === $roles && array_keys( $passedAt ) === [ '.nino-countdown-value, .nino-countdown-name' ]
	&& array_filter( [ 'regular', 'big' ], static fn( string $variant ): bool => array_filter( array_keys( $looks[$variant]['declared'] ?? [] ), static fn( string $property ): bool => str_starts_with( $property, '--color-' ) ) !== [] ) === [] );

/*	Where the defaults stand: lengths, numbers, keywords and grounds in :where(:root), a tone of the page in :where(*), and nowhere else - a custom
	property is resolved where it is declared, and :where() has no specificity, so the line of the knob positions a look compiles beats it	*/
$moved 	= [];

foreach( $knobsOf as $variant => $declared ) {
	foreach( $stemsOf( $looks[$variant], $declared ) as $list )
		foreach( $list as $stem )
			$moved[$stem] = ':where(:root)';

	foreach( $declared as $name => $lines ) {
		if( $lines[0]['kind'] === 'ink' )
			$moved[ '--countdown-'. $name ] = ':where(*)';

		if( $lines[0]['kind'] === 'ground' ) {
			$moved[ '--countdown-'. $name ] = ':where(:root)';

			preg_match_all( '/var\(\s*(--countdown-'. preg_quote( $name, '/' ). '-[a-z0-9-]+)/', (string) file_get_contents( $design. '/'. $variant. '.css' ), $parts );

			foreach( $parts[1] as $part )
				$moved[$part] = ':where(:root)';
		}
	}
}

$strayDefaults = [];

foreach( $moved as $property => $place )
	if( array_column( $baseDeclared[$property] ?? [], 'selector' ) !== [ $place ] )
		$strayDefaults[] = $property. ' is not declared once in '. $place;

foreach( $base['rules'] as $rule )
	foreach( countdownDeclarations( $rule['body'] ) as $declaration )
		if( str_starts_with( $declaration['property'], '--countdown-' ) === true && in_array( $rule['selector'], [ ':where(:root)', ':where(*)' ], true ) === false )
			$strayDefaults[] = $declaration['property']. ' is declared under '. $rule['selector'];

check( 'countdown.css declares every property a look moves, once, in the place of its kind and in no other: a scale, a choice with its aspects and a ground in :where(:root), the tone of the digits in :where(*) - never in a bare :root, on a class or with !important'. ( $strayDefaults === [] ? '' : ' - '. implode( ', ', $strayDefaults ) ),
	$strayDefaults === [] && count( array_filter( $moved, static fn( string $place ): bool => $place === ':where(*)' ) ) === 1 && count( array_filter( $moved, static fn( string $place ): bool => $place === ':where(:root)' ) ) >= 8 );

// Plain is the counter as it is without the Design feature: the middle of every ladder is the default countdown.css has, it paints nothing, and the digits that nothing paints are the colour of the text around the counter
$unequal = [];
$plain 	 = $looks['regular'] ?? [ 'declared' => [] ];

foreach( $plain['declared'] as $property => $places )
	if( preg_match( '/^(.+)--default$/', $property, $stem ) === 1 && ( $baseDeclared[ $stem[1] ][0]['value'] ?? null ) !== $places[0]['value'] )
		$unequal[] = $property;

foreach( $plain['rules'] ?? [] as $rule )
	if( $rule['selector'] !== ':root' )
		$unequal[] = 'a rule on '. $rule['selector'];

foreach( $knobsOf['regular'] ?? [] as $name => $lines )
	if( in_array( $lines[0]['kind'], [ 'ink', 'ground', 'colour' ], true ) === true )
		$unequal[] = 'a colour knob '. $name;

if( ( $baseDeclared['--countdown-digits'][0]['value'] ?? null ) !== 'currentColor' )
	$unequal[] = 'the digits are not the colour of the text around the counter';

check( 'Plain stands where countdown.css stands: the middle of each of its ladders is the default the base declares, it writes no rule and has no colour knob, and the tone of the digits the base declares is the text colour around the counter - so the counter under it is drawn as it is without the Design feature'. ( $unequal === [] ? '' : ' - '. implode( ', ', $unequal ) ),
	$unequal === [] && count( array_filter( array_keys( $plain['declared'] ), static fn( string $property ): bool => str_ends_with( $property, '--default' ) ) ) === count( array_merge( ...array_values( $stemsOf( $looks['regular'] ?? [ 'declared' => [] ], $knobsOf['regular'] ?? [] ) ) ) ) );

// --- The styles: once for all the looks
$styles 			= countdownSheet( $design. '/styles.css' );
$styleLines		= $styles['tags']['@style'] ?? [];
$styleName		= (string) strtok( $styleLines[0] ?? '', " \t" );
$optionFiles	= glob( $design. '/styles/*.css' ) ?: [];
$styleProblems = [];

if( count( $styleLines ) !== 1 || count( $styles['tags']['@style-de'] ?? [] ) !== 1 || strtok( $styles['tags']['@style-de'][0], " \t" ) !== $styleName || count( (array) preg_split( '/\s+/', $styleLines[0], 2 ) ) !== 2 )
	$styleProblems[] = 'styles.css declares one style with its @style and its @style-de line';

if( isset( $styles['tags']['@name'] ) === true )
	$styleProblems[] = 'styles.css has a @name - it is no look';

foreach( $optionFiles as $file ) {
	$option = countdownSheet( $file );

	if( preg_match( '/^'. preg_quote( $styleName, '/' ). '-[a-z0-9]+(?:-[a-z0-9]+)*$/', basename( $file, '.css' ) ) !== 1 )
		$styleProblems[] = basename( $file ). ' is named for no option of '. $styleName;

	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $option['tags'][$tag] ?? [] ) !== 1 || trim( $option['tags'][$tag][0] ) === '' )
			$styleProblems[] = basename( $file ). ' '. $tag;
}

/*	The shared rule is the body of styles.css, written once before the option chosen: a sign on the part, which is not the last one, with its content out of a
	property; and an option is that property and nothing else - the sign and nothing of where it stands	*/
$sharedRule	= [];
$altRule		= [];

foreach( $styles['rules'] as $rule )
	if( str_contains( $rule['selector'], '::after' ) === true ) {
		if( $rule['at'] === '' )
			$sharedRule = $rule;
		else
			$altRule = $rule;
	}

$sharedContent = (string) ( array_column( countdownDeclarations( (string) ( $sharedRule['body'] ?? '' ) ), 'value', 'property' )['content'] ?? '' );
$signProperty	 = preg_match( '/^var\(\s*(--[a-z0-9-]+)\s*\)$/', $sharedContent, $found ) === 1 ? $found[1] : '';
$signs				 = [];

foreach( $optionFiles as $file ) {
	$option = countdownSheet( $file );

	if( count( $option['rules'] ) !== 1 || $option['rules'][0]['selector'] !== ':root' || array_keys( $option['declared'] ) !== [ $signProperty ] )
		$styleProblems[] = basename( $file ). ' is more than the property '. $signProperty. ' on :root';
	else
		$signs[] = $option['declared'][$signProperty][0]['value'];
}

if( $signProperty === '' || str_contains( (string) ( $sharedRule['selector'] ?? '' ), '.nino-countdown-part:not(:last-child)::after' ) === false )
	$styleProblems[] = 'the shared rule is no sign on a part that is not the last one, with its content out of a property';

// The sign is decoration: where the browser knows the alternative text of generated content it is given an empty one, and nowhere else is the content a different one
if( $signProperty === '' || ( $altRule['selector'] ?? null ) !== ( $sharedRule['selector'] ?? '' ) || str_starts_with( $altRule['at'] ?? '', '@supports (content: "" / "")' ) === false
	|| ( array_column( countdownDeclarations( (string) ( $altRule['body'] ?? '' ) ), 'value', 'property' ) ) !== [ 'content' => 'var('. $signProperty. ') / ""' ] )
	$styleProblems[] = 'the sign has no empty alternative text under @supports (content: "" / "")';

// The property is the options' to set: a default in countdown.css would make None a sign of its own
if( $signProperty === '' || isset( $baseDeclared[$signProperty] ) === true )
	$styleProblems[] = 'countdown.css declares a default for '. $signProperty;

check( 'design/styles.css declares one style, the files of design/styles/ are its options - each named <style>-<option>.css and headed in both languages - and its body is the one rule they share: a sign after every part but the last, its content out of a property'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ),
	$styleProblems === [] && $signProperty !== '' );
check( '...and each option is that property and nothing else, a sign of its own: two options, no two the same, and no rule of the styles writes the content itself',
	count( $optionFiles ) === 2 && count( array_unique( $signs ) ) === 2 && str_contains( (string) file_get_contents( $design. '/styles.css' ), 'content: var(' ) === true
	&& array_filter( $optionFiles, static fn( string $file ): bool => preg_match( '/\bcontent\s*:/', (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) ) ) === 1 ) === [] );

// --- The block: the counter as it stands once the script has taken it over
$block 			= (string) @file_get_contents( $design. '/preview.tpl' );
$blockIds 	= [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

preg_match_all( '/<div class="nino-countdown-parts"([^>]*)>(.*?)\n\t*<\/div>/s', $block, $blockCounters, PREG_SET_ORDER );
preg_match_all( '/<time\b[^>]*>/', $block, $blockTimes );
preg_match_all( '/\[\[(\/feature\/countdown\/[a-z0-9\/-]+)\]\]/', $block, $blockFills );

$blockUnits = array_map( static fn( string $unit ): string => '/feature/countdown/unit-'. rtrim( $unit, 's' ). '/many', \Nino\Modules\Countdown::UNITS );
$blockFirst = [];
preg_match_all( '/\[\[(\/feature\/countdown\/[a-z0-9\/-]+)\]\]/', (string) ( $blockCounters[0][2] ?? '' ), $blockFirst );

check( 'the feature brings a block to the specimen, design/preview.tpl: one grid row, every id in it countdown-something and each once, no section of its own and no inline handler',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $block ), '</div>' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'countdown-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1 );
check( '...the counters are the markup of the shortcode, finished: the parts in view - not hidden, as the script leaves them - each with its number written by hand and the name from the unit\'s text key, and the date a <time> with a moment of its own; none carries data-countdown-to, so that the script never counts it',
	count( $blockCounters ) >= 2 && array_filter( $blockCounters, static fn( array $counter ): bool => preg_match( '/\bhidden\b/', $counter[1] ) === 1 || preg_match_all( '/<span class="nino-countdown-part"><span class="nino-countdown-value">\d+<\/span><span class="nino-countdown-name">\[\[\/feature\/countdown\/unit-[a-z]+\/(?:one|many)\]\]<\/span><\/span>/', $counter[2] ) < 1 ) === []
	&& count( $blockTimes[0] ) === count( $blockCounters ) && array_filter( $blockTimes[0], static fn( string $time ): bool => preg_match( '/\sdatetime="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}"/', $time ) !== 1 ) === []
	&& str_contains( $block, 'data-countdown-to' ) === false );
check( '...the first one has the four parts of a counter without units=, largest first, and the names are keys the install unit writes into the Text panel in both languages - the second has fewer, so that the sign has a last part to leave alone',
	$blockFirst[1] === $blockUnits && count( $blockCounters ) === 2 && preg_match_all( '/class="nino-countdown-part"/', $blockCounters[1][2] ) > 0 && preg_match_all( '/class="nino-countdown-part"/', $blockCounters[1][2] ) < 4
	&& array_filter( array_unique( $blockFills[1] ), static function( string $key ) use ( $dir ): bool {
		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( isset( ( include $dir. '/install/text/'. $locale. '.php' )[ '[['. $key. ']]' ] ) === false )
				return true;

		return false;
	} ) === [] );

$blockClasses = [];
preg_match_all( '/class="([^"]*)"/', $block, $blockClassLists );

foreach( $blockClassLists[1] as $list )
	foreach( explode( ' ', $list ) as $class )
		if( str_starts_with( $class, 'nino-countdown' ) === true )
			$blockClasses[$class] = true;

$templateText = (string) file_get_contents( $dir. '/templates/countdown.tpl' ). (string) file_get_contents( $dir. '/templates/countdown-part.tpl' );

check( '...and no class of the counter is one the shortcode\'s own templates do not write',
	$blockClasses !== [] && array_filter( array_keys( $blockClasses ), static fn( string $class ): bool => str_contains( $templateText, $class ) === false ) === [] );

// --- The manual
$manualProblems = [];

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = ( (array) ( $manifest['manual']['panel'] ?? [] ) )['Design: the looks of the counter'][$locale] ?? null;

	if( is_string( $text ) === false || trim( $text ) === '' || max( array_map( 'strlen', explode( "\n", $text ) ) ) >= 1000 )
		$manualProblems[] = $locale;
}

check( 'the manual names the looks, the knobs and the separator under panel, in both languages, every line under 1000 bytes'. ( $manualProblems === [] ? '' : ' - '. implode( ', ', $manualProblems ) ), $manualProblems === [] );

echo "\n";


// --- ...run through the Design feature ---------------------------------------------

echo "design/ - as the Design feature reads and compiles it\n";

$designSetup			= '\\Nino\\Modules\\Design\\Setup';
$designPreview		= '\\Nino\\Modules\\Design\\Preview';
$designCompiler		= '\\Nino\\Modules\\Design\\Compiler';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the looks are not run through it here, bin/check.sh runs them with it in the checkout\n";
else {
	$library			= \Nino\Features::dir(). '/Design/library';
	$modulesKept	= (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept		= $appData['./nino/locales/current'];

	/** Which of the two features are on: their classes are listed in /nino/modules, and what the kernel and the Design feature have read of the features is read again */
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Countdown', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Countdown', 'Design' ] );
	$appData['./nino/locales/current'] = 'en_US';

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['countdown'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its three looks, regular the one it has before anything is chosen, the anchor of its block - and has nothing to say of any file: no line dropped, no ladder missing, no style without a file',
		isset( $scan['countdown'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === array_keys( $looks ) && $feature['default'] === 'regular'
		&& $feature['anchor'] === 'feature-countdown' && $scanNotes === [] );

	$publishes = [];

	foreach( $catalogue as $variant => $look )
		$publishes[$variant] = array_map( static fn( array $knob ): array => [ $knob['kind'], $knob['tab'] ], $look['knobs'] );

	check( 'each look publishes the knobs it declares and no other, with the kind and the tab of its head: six for Tiles, four for Big, three for Plain',
		$publishes['tiles'] === $knobTable && $publishes['regular'] === array_diff_key( $knobTable, [ 'corners' => 1, 'tile' => 1, 'digits' => 1 ] ) && $publishes['big'] === array_diff_key( $knobTable, [ 'corners' => 1, 'tile' => 1 ] ) );
	check( '...a scale by its own ladder and the choice by its ladder and the aspects it declares beside it, which are the ones every look carries',
		array_filter( $catalogue, static fn( array $look ): bool => ( $look['knobs']['size']['ladder'] ?? [] ) !== [ 'size' ] || ( $look['knobs']['gap']['ladder'] ?? [] ) !== [ 'gap' ]
			|| count( $look['knobs']['names']['ladder'] ?? [] ) < 2 || ( $look['knobs']['names']['ladder'] ?? [] ) !== ( $catalogue['regular']['knobs']['names']['ladder'] ?? [] ) ) === [] );

	$styled = $feature['styles'] ?? [];

	check( 'the styles the scan finds are the one design/styles.css declares, with the options its files give, the file of each there',
		array_keys( $styled ) === [ $styleName ] && count( $styled[$styleName]['options'] ?? [] ) === count( $optionFiles ) && count( $optionFiles ) === 2
		&& array_filter( $styled[$styleName]['options'] ?? [], static fn( array $option ): bool => is_file( (string) $option['file'] ) === false ) === [] );

	// The block is the feature's, not a look's: Design makes the section round it, with the feature's anchor
	$blockTold		= [];
	$blockInside	= $designSetup::previewOf( (string) $feature['dir'], 'countdown', $blockTold );
	$blocks				= $designPreview::blocks( $appData );

	check( 'the block stands the check Design holds it to and the preview makes the section of it, with the anchor the scan names, before the footer',
		$blockInside === trim( $block ) && $blockInside !== '' && $blockTold === [] && array_keys( $blocks ) === [ 'countdown' ]
		&& preg_match( '~^<section id="feature-countdown" class="nino-section" aria-label="[^"]+">\s*(.*?)\s*</section>$~s', $blocks['countdown'] ?? '', $blockSection ) === 1 && str_contains( $blockSection[1] ?? '', 'id="countdown-full"' ) === true );

	$featuresOn( [ 'Countdown', 'Design' ] );
	$withNone	= $compile( [] );
	$featuresOn( [ 'Design' ] );
	$withOff	= $compile( [] );
	$featuresOn( [ 'Countdown', 'Design' ] );

	check( 'with no look chosen the feature compiles nothing: the sheet of a project that has the feature on is the sheet of one that has it off, byte for byte', $withNone === $withOff && str_contains( $withNone, 'feature countdown' ) === false );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'countdown' => [ 'set' => $variant ] ] ], $sheetNotes );
		$ladders		= [];

		foreach( $look['knobs'] as $name => $knob )
			if( in_array( $knob['kind'], [ 'scale', 'choice' ], true ) === true ) {
				$ladders[] = $name. ' default';

				foreach( $knob['ladder'] ?? [] as $ladder )
					if( str_contains( $sheet, "\n\t--countdown-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'countdown', $ladder, 'default' ). ");\n" ) === false )
						$sheetProblems[] = $variant. ' selects no '. $ladder;
			}
			elseif( $knob['kind'] === 'ink' && preg_match( '~\n:root, :root \* \{\n(?:\t[^\n]*\n)*?\t--countdown-'. preg_quote( $name, '~' ). ': var\('. preg_quote( $designSetup::INKS[ (string) $knob['default'] ] ?? '', '~' ). '\);\n~', $sheet ) !== 1 )
				$sheetProblems[] = $variant. ' writes no ink '. $name;
			elseif( $knob['kind'] === 'ground' && str_contains( $sheet, "\n\t--countdown-". $name. ': var(--nino-'. $knob['default']. ");\n" ) === false )
				$sheetProblems[] = $variant. ' writes no ground '. $name;

		if( preg_match( '~^/\* ==== \d+\. feature countdown: '. preg_quote( $variant, '~' ). ' \((.*)\) ==== \*/$~m', $sheet, $title ) !== 1 || $title[1] !== implode( ', ', $ladders ) )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] || str_contains( $sheet, $designCompiler::SHARED ) === true )
			$sheetProblems[] = $variant. ' compiled with a note, or with the shared rules of a style that was not chosen';
	}

	check( 'with a look chosen the sheet carries its section, its stylesheet as the file has it, a line for every ladder of every knob it declares and the tone of its ink and the ground of its tile where it has them - and none of the shared rules of the styles, which stay out where no style is chosen'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ),
		$sheetProblems === [] && $catalogue !== [] );

	$styleProblems = [];

	foreach( array_keys( $catalogue ) as $variant )
		foreach( $styled[$styleName]['options'] ?? [] as $option => $declared ) {
			$sheet	= $compile( [ 'features' => [ 'countdown' => [ 'set' => $variant, 'styles' => [ $styleName => $option ] ] ] ] );
			$shared	= $designSetup::featureShared( $appData, 'countdown' );
			$at 		= (int) strpos( $sheet, $designCompiler::SHARED );
			$line 	= sprintf( $designCompiler::STYLE, $styleName, $option );

			if( $shared === '' || substr_count( $sheet, $shared ) !== 1 || $at === 0 || strpos( $sheet, $shared ) < $at || strpos( $sheet, $line ) < strpos( $sheet, $shared ) )
				$styleProblems[] = $variant. ' '. $option. ': the shared rules stand once, under their line, before the option';

			if( str_contains( $sheet, rtrim( (string) file_get_contents( (string) $declared['file'] ), "\n" ) ) === false || substr_count( $sheet, $line ) !== 1 )
				$styleProblems[] = $variant. ' '. $option. ': the option is not in the sheet once, as the file has it';
		}

	check( 'a style chosen puts the rules the styles share once into the section of the look, whichever it is, and then the file of the option under its own line - the same for Plain, Tiles and Big'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ), $styleProblems === [] && count( $catalogue ) === 3 );

	// The bundle of the website lists the sheet of the feature once, behind the kernel's own
	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];
	$kernelSheets = (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] );

	check( 'in a booted project the bundle of the website lists countdown.css once, behind the kernel\'s own sheets',
		$kernelSheets !== [] && array_keys( $bundle, '/features/Countdown/assets/countdown.css', true ) === [ count( $kernelSheets ) ] && array_slice( $bundle, 0, count( $kernelSheets ) ) === $kernelSheets );

	$appData['/nino/modules']					= $modulesKept;
	$appData['./nino/locales/current']	= $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );

	ninoWarnings();
}

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
