<?php
declare(strict_types=1);

// The test of the feature Hours: structure and behaviour, one pin each; the security boundary is named in its label. Nothing here
// asks the clock: the moment is handed in (./hours/now). php features/Hours/tests/hours-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel', is_array( $manifest ) === true && $manifest['key'] === 'hours' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && $manifest['version'] === '2.0.0' );

// Wednesday, the fourth of March 2026
const HOURS_NOW = '2026-03-04T10:00:00+00:00';
$locationsType = require $dir. '/install/locations.php';
$closuresType = require $dir. '/install/closures.php';
$week = [ 'mon' => '09:00-12:00, 14:00-18:00', 'tue' => '09:00-12:00, 14:00-18:00', 'wed' => '09:00-12:00, 14:00-18:00', 'thu' => '09:00-12:00, 14:00-18:00', 'fri' => '09:00-12:00, 14:00-18:00', 'sat' => '10:00-14:00', 'sun' => '' ];

/**
 *	A project with Basis and Hours: two locations and the closures a test gives, the words of the feature in German, a moment
 *
 *	@param		array 		$closures			name => [ 'global' => fields, 'de_DE' => fields ]
 *	@param		array			$overrides		location => [ 'global' => fields, 'de_DE' => fields ] added to the two
 *	@param		string		$zone					The setting timezone
 *	@param		string		$now					The moment
 *
 *	@return 	array
 */
$site = static function( array $closures = [], array $overrides = [], string $zone = 'UTC', string $now = HOURS_NOW ) use ( $root, $dir, $locationsType, $closuresType, $week ): array {

	$appData = ninoSandbox( 'hours' );
	$appData['./nino/filesystem/path'] = $root;
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Hours', '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Collections' ];
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );

	$global = [ 'main' => [ 'address' => "Main Street 1\n12345 Town" ] + $week, 'branch' => [ 'address' => '', 'mon' => '08:00-16:00', 'tue' => '', 'wed' => '', 'thu' => '', 'fri' => '', 'sat' => '', 'sun' => '' ] ];
	$german = [ 'main' => [ 'name' => 'Hauptgeschäft', 'note' => 'Nach Vereinbarung auch später.' ], 'branch' => [ 'name' => 'Filiale', 'note' => '' ] ];

	foreach( $overrides as $name => $fields ) {
		$global[$name] = $fields['global'] ?? [];
		$german[$name] = $fields['de_DE'] ?? [];
	}

	\Nino\Filesystem::putFileContent( $appData, '/elements/locations.php', [ 'model' => $locationsType['model'], '*' => [ '*' => [] ] + $global, 'de_DE' => $german ] );

	$closureGlobal = [];
	$closureGerman = [];

	foreach( $closures as $name => $fields ) {
		$closureGlobal[$name] = $fields['global'];
		$closureGerman[$name] = $fields['de_DE'] ?? [];
	}

	\Nino\Filesystem::putFileContent( $appData, '/elements/closures.php', [ 'model' => $closuresType['model'], '*' => [ '*' => [] ] + $closureGlobal, 'de_DE' => $closureGerman ] );

	// The words of the feature as an activation puts them into the Text panel
	$words = [];

	foreach( require $dir. '/install/text/de_DE.php' as $key => $text )
		$words[substr( $key, 2, -2 )] = $text;

	\Nino\Html::addFills( $appData, $words, '*' );

	$appData['/nino/features']['hours']['settings']['timezone'] = $zone;
	$appData['./hours/now'] = $now;

	return $appData;
};
$draw = static fn( array &$appData, string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
$rows = static function( string $html ): array {
	preg_match_all( '#<tr class="([^"]*)"([^>]*)><th scope="row">(.*?)</th><td>(.*?)</td></tr>#s', $html, $found, PREG_SET_ORDER );
	return $found;
};

$appData = $site();
check( 'the folders are read without a warning', ninoWarnings() === [] && \Nino\Features::componentProblems( $appData, 'hours' ) === [] );

$schemas = \Nino\Components::components( $appData );
$names = [ 'hours', 'hours-today' ];
check( 'both components are registered in their group, with a label in both languages, an icon of the table and a standard part',
	array_filter( $names, static fn( string $name ): bool => ( $schemas[$name]['group'] ?? '' ) !== 'hours' || isset( $schemas[$name]['label']['de_DE'], $schemas[$name]['label']['en_US'] ) === false || in_array( $schemas[$name]['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['hours']['part'] ?? false ) === true );
check( 'every attribute has a label in both languages', array_filter( $names, static fn( string $name ): bool => array_filter( $schemas[$name]['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) !== [] ) === [] );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/hours/part/part.css' ), $names ) === [] );
check( 'a tool offers the week in a column and the line for today in a column, the foot and the actions of the header',
	in_array( 'hours', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true && in_array( 'hours-today', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& in_array( 'hours-today', \Nino\Components::allowed( $appData, 'footer-col' ), true ) === true && in_array( 'hours-today', \Nino\Components::allowed( $appData, 'header-actions' ), true ) === true );
check( 'the group has the knob gap', array_keys( \Nino\Components::groups( $appData )['hours']['knobs'] ?? [] ) === [ 'hours.gap' ] );

// The notation of a day
check( 'a day is read in its notations and stored in the normal form, and what cannot be read says why',
	\Nino\Modules\Hours::parseTimes( '9-12, 14.30–18' )['normal'] === '09:00-12:00, 14:30-18:00' && \Nino\Modules\Hours::parseTimes( '18-2' )['spans'] === [ [ 1080, 1560 ] ] && \Nino\Modules\Hours::parseTimes( '' )['error'] === ''
	&& \Nino\Modules\Hours::parseTimes( '9-12, 11-14' )['error'] === 'overlap' && \Nino\Modules\Hours::parseTimes( '24-5' )['error'] === 'hours' && \Nino\Modules\Hours::parseTimes( 'open' )['error'] === 'unreadable' && \Nino\Modules\Hours::parseTimes( '9-9' )['error'] === 'equal' );

// The week
$html = $draw( $appData, '[hours location="/locations/main" address]' );
$table = $rows( $html );
check( 'the week puts days of equal hours into one row, and every time is a time element with its value',
	count( $table ) === 3 && str_starts_with( $table[0][3], 'Mo–Fr' ) === true && str_contains( $table[0][4], '<time datetime="09:00">9</time>–<time datetime="12:00">12</time>' ) === true && str_contains( $table[0][4], 'Uhr' ) === true && str_contains( $table[2][4], 'hours-closed' ) === true );
check( 'the row that holds today is marked in more than colour: an attribute, a class and the word',
	str_contains( $table[0][2], 'aria-current="date"' ) === true && str_contains( $table[0][1], 'hours-row--today' ) === true && str_contains( $table[0][3], 'Heute' ) === true && str_contains( $table[1][2], 'aria-current' ) === false );
check( 'the name and the address are drawn above the week as its caption, the note below', str_contains( $html, '<caption class="hours-name"><span class="hours-title">Hauptgeschäft</span>' ) === true && str_contains( $html, 'Main Street 1' ) === true && str_contains( $html, '<p class="hours-note">Nach Vereinbarung auch später.</p>' ) === true );
check( 'name, address and closures are switched off by their attributes', str_contains( $draw( $appData, '[hours location="/locations/main" name="0" closures="0"]' ), 'Hauptgeschäft' ) === false
	&& str_contains( $draw( $appData, '[hours location="/locations/main"]' ), 'Main Street' ) === false );
check( 'with no location there are all of them, each as a block with its location', substr_count( $draw( $appData, '[hours]' ), 'data-location="/locations/' ) === 2 && substr_count( $draw( $appData, '[hours location="/locations/branch"]' ), 'data-location="/locations/branch"' ) === 1 );
preg_match_all( '#data-location="(/locations/[a-z]+)"#', $draw( $appData, '[collection /locations sort="name"][hours][/collection]' ), $loop );
check( 'inside a loop over the locations an empty location is the one of the element', $loop[1] === [ '/locations/branch', '/locations/main' ] );

// The time: the zone is the setting, and the moment is the one handed in
$edge = '2026-03-03T23:30:00+00:00';
$utc = $site( [], [], 'UTC', $edge );
$berlin = $site( [], [], 'Europe/Berlin', $edge );
$todayRow = static fn( string $html ): string => preg_match( '#hours-row--today[^>]*><th scope="row">(\w+)#', $html, $day ) === 1 ? $day[1] : '';
$wide = '[hours location="/locations/main"]';
check( 'today is counted in the timezone of the setting: the same moment is Tuesday in UTC and Wednesday in Berlin, and the line for today follows',
	$todayRow( $draw( $utc, $wide ) ) === 'Mo' && $todayRow( $draw( $berlin, $wide ) ) === 'Mo' && str_contains( $draw( $utc, '[hours-today location="/locations/main"]' ), 'data-closed="false"' ) === true
	&& str_contains( $draw( $site( [], [], 'Europe/Berlin', '2026-03-07T23:30:00+00:00' ), '[hours-today location="/locations/main"]' ), 'data-closed="true"' ) === true
	&& str_contains( $draw( $site( [], [], 'UTC', '2026-03-07T23:30:00+00:00' ), '[hours-today location="/locations/main"]' ), 'data-closed="true"' ) === false
	&& \Nino\Modules\Hours::zone( $berlin )->getName() === 'Europe/Berlin' );

// The deviation today
$closed = $site( [
	'c2' => [ 'global' => [ 'location' => '/locations/main', 'from' => '2026-03-04', 'until' => '2026-03-05', 'times' => '' ], 'de_DE' => [ 'reason' => 'Inventur' ] ],
	'c1' => [ 'global' => [ 'location' => '', 'from' => '2026-03-04', 'until' => '2026-03-04', 'times' => '10:00-12:00' ], 'de_DE' => [ 'reason' => 'Alle Standorte kurz' ] ],
] );
$html = $draw( $closed, '[hours location="/locations/main"]' );
$table = $rows( $html );
check( 'a closure that holds today is told apart in the row of today, and the one that names the location beats the one for every location',
	count( $table ) === 5 && str_contains( $table[1][1], 'hours-row--deviation' ) === true && str_contains( $table[1][4], 'Inventur' ) === true && str_contains( $table[1][4], 'Alle Standorte' ) === false && str_contains( $table[1][4], 'hours-closed' ) === true );
$line = $draw( $closed, '[hours-today location="/locations/main" name]' );
check( 'the line for today counts the closure in: closed with its reason, and with the name in front',
	str_contains( $line, 'data-closed="true"' ) === true && str_contains( $line, 'Inventur' ) === true && str_contains( $line, '<strong class="hours-name">Hauptgeschäft</strong>' ) === true && str_contains( $draw( $closed, '[hours-today location="/locations/branch"]' ), 'Alle Standorte kurz' ) === true );
$far = $site( [ 'far' => [ 'global' => [ 'location' => '', 'from' => '2026-12-01', 'until' => '', 'times' => '' ] ] ] );
check( 'the closures to come are listed with dates as time elements, within the days ahead, and not when they are switched off',
	str_contains( $html, '<dl class="hours-closures">' ) === true && str_contains( $html, '<time datetime="2026-03-04">' ) === true && str_contains( $draw( $closed, '[hours location="/locations/main" closures="0"]' ), 'hours-closures' ) === false
	&& str_contains( $draw( $far, '[hours location="/locations/main" ahead="30"]' ), 'hours-closures' ) === false && str_contains( $draw( $far, '[hours location="/locations/main" ahead="366"]' ), 'hours-closures' ) === true );

// The data for search engines
// A page draws the data of a location once: a fresh page, then
$page = $site();
$html = $draw( $page, '[hours location="/locations/main" address]' );
preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $found );
$data = json_decode( $found[1][0] ?? '', true );
$night = $site( [], [ 'main' => [ 'global' => [ 'address' => '', 'mon' => '18-24', 'tue' => '', 'wed' => '', 'thu' => '', 'fri' => '', 'sat' => '', 'sun' => '' ], 'de_DE' => [ 'name' => 'Nacht' ] ] ] );
check( 'the JSON-LD is valid: a Place with its name, its address and an opening hours specification for each time, the end of the day written 23:59 where it is 24:00, once for a location on a page',
	count( $found[1] ) === 1 && is_array( $data ) === true && $data['@type'] === 'Place' && $data['name'] === 'Hauptgeschäft' && $data['address']['streetAddress'] === 'Main Street 1, 12345 Town' && count( $data['openingHoursSpecification'] ) === 3
	&& $data['openingHoursSpecification'][0]['dayOfWeek'] === [ 'https://schema.org/Monday', 'https://schema.org/Tuesday', 'https://schema.org/Wednesday', 'https://schema.org/Thursday', 'https://schema.org/Friday' ] && $data['openingHoursSpecification'][0]['opens'] === '09:00'
	&& str_contains( $draw( $night, $wide ), '"closes":"23:59"' ) === true && substr_count( $draw( $page, '[hours location="/locations/main"]' ), 'application/ld+json' ) === 0 );

// SECURITY: nothing an editor typed leaves the script element of the JSON-LD
$evil = "x</script><script>alert(1)</script><!-- [csrf] [[/project/company/general/name]]";
$hostile = $site( [ 'e' => [ 'global' => [ 'location' => '', 'from' => '2026-03-05', 'until' => '', 'times' => '10-11' ], 'de_DE' => [ 'reason' => $evil ] ] ], [ 'main' => [ 'global' => [ 'address' => $evil ] + $week, 'de_DE' => [ 'name' => $evil, 'note' => $evil ] ] ] );
$html = $draw( $hostile, '[hours location="/locations/main" address]' );
preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $found );
$data = json_decode( $found[1][0] ?? '', true );
check( 'the values of the elements cannot leave the script element of the JSON-LD: </script> and <!-- are escapes in it, and it reads back as what was typed',
	count( $found[1] ) === 1 && substr_count( $html, '<script' ) === 1 && substr_count( $html, '</script>' ) === 1 && str_contains( $found[1][0], '<' ) === false && str_contains( $found[1][0], '>' ) === false && is_array( $data ) === true && $data['name'] === $evil );
check( 'the same values are text in the markup: a tag, a call and a fill in a name, an address, a note and a reason stay what they are',
	str_contains( $html, '<b' ) === false && str_contains( $html, 'name="csrf"' ) === false && substr_count( $html, '[[/project/company/general/name]]' ) >= 4 && substr_count( $html, '&lt;script&gt;alert(1)' ) >= 4 && preg_match( '#<script(?!\stype="application/ld\+json")#', $html ) === 0 );
$spelled = $site( [], [ 'main' => [ 'global' => $week, 'de_DE' => [ 'name' => '[[rows]] [[caption]] [[address]]' ] ] ] );
$html = $draw( $spelled, '[hours location="/locations/main"]' );
check( 'a name that spells a place of the markup is text, and fills nothing', count( $rows( $html ) ) === 3 && str_contains( $html, '[[rows]] [[caption]] [[address]]' ) === true );

// The veto of the write
$typeData = [ 'model' => $locationsType['model'], '*' => [ '*' => [], 'x' => [ 'mon' => '9-12,14-18', 'tue' => '' ] ], 'de_DE' => [ 'x' => [ 'name' => 'X' ] ] ];
$normal = $typeData;
$ignored = ninoWarnings();
$answer = \Nino\Modules\Hours::callbackLocations( $appData, $normal );
$badData = $typeData;
$badData['*']['x']['wed'] = 'sometime';
$answerBad = \Nino\Modules\Hours::callbackLocations( $appData, $badData );
check( 'a location whose days cannot be read is refused with a sentence, and one that can is stored in the normal form', $answer === null && $normal['*']['x']['mon'] === '09:00-12:00, 14:00-18:00' && $answerBad === false && count( ninoWarnings() ) === 1 );
$closuresData = static fn( array $entry ): array => [ 'model' => $closuresType['model'], '*' => [ '*' => [], 'k' => $entry ] ];
$refused = [];

foreach( [ [ 'from' => '2026-02-30' ], [ 'from' => '2026-03-05', 'until' => '2026-03-01' ], [ 'from' => '2026-03-05', 'times' => 'noon' ], [ 'from' => '2026-03-05', 'location' => '/locations/nowhere' ] ] as $entry ) {
	$data = $closuresData( $entry );
	$refused[] = \Nino\Modules\Hours::callbackClosures( $appData, $data );
}
$good = $closuresData( [ 'from' => '2026-03-05', 'until' => '2026-03-06', 'times' => '10-12', 'location' => '/locations/main' ] );
check( 'a closure with a day that does not exist, an end before its beginning, times that cannot be read or a location that is none is refused, and a good one is stored in the normal form',
	$refused === [ false, false, false, false ] && ninoWarnings() !== [] && \Nino\Modules\Hours::callbackClosures( $appData, $good ) === null && $good['*']['k']['times'] === '10:00-12:00' );
$request = [ '/nino/http/response' => [ 'statusCode' => 400, 'body' => [ 'error' => ( $appData['./hours/refused']['error'] ?? 'none' ) ] ] ];
\Nino\Modules\Hours::callbackOutput( $appData, $request );
check( 'the answer to a refused save names the field it is about', ( $request['/nino/http/response']['body']['field'] ?? '' ) === 'location' );

// The cache keeps a page no longer than to the next midnight of the zone, from the moment handed in
$cached = $site();
$cached['/nino/cache/status'] = true;
$cached['/nino/cache/ttl'] = 86400;
\Nino\Modules\Hours::limitCache( $cached, \Nino\Modules\Hours::now( $cached ) );
check( 'a page of the cache is kept at most until the next midnight of the zone', $cached['/nino/cache/ttl'] === 14 * 3600 );

// Words, files and what is around them
$de = require $dir. '/install/text/de_DE.php';
$en = require $dir. '/install/text/en_US.php';
check( 'both languages have the same words', array_keys( $de ) === array_keys( $en ) && array_filter( $de, static fn( string $text ): bool => $text === '' ) === [] );
$groups = \Nino\Components::groups( $appData );
$demo = $draw( $appData, (string) ( $groups['hours']['demo'] ?? '' ) );
check( 'the demo of the group is read without a note and draws the week and the line for today', ( $groups['hours']['demo'] ?? '' ) !== '' && str_contains( $demo, '<table class="hours-table">' ) === true && str_contains( $demo, 'class="hours-today' ) === true );
$block = \Nino\Blocks::get( $appData, 'hours-week' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'hours', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );
check( 'the part is in the style bundle', str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.hours-table' ) === true );
$off = ninoSandbox( 'hours-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[hours]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary, and nothing that depends on the system's locale, is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Feature::classes|addShortcode|Modules\\\\Components|nino-hours|--color-|--space-[0-9]|--radius-|--fontsize-|setlocale|strftime|strptime|IntlDateFormatter|GLOB_BRACE/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1 or a function of the system locale', $old === [] );

ninoDone( $appData );
