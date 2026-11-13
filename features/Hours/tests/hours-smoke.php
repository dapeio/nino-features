<?php
declare(strict_types=1);

// The Hours feature's contract test. Runs against the checkout three levels up,
// or the one NINO_ROOT names
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/*	The clock of the test: the timezone of the process is Berlin, which the feature's
	setting 'server' follows, and the moment "now" is held through ./hours/now, the one
	place the feature asks the clock. No sentence or word is pinned: the words are read
	from the fills of the language the page is in	*/
$processZone = date_default_timezone_get();
date_default_timezone_set( 'Europe/Berlin' );

$appData = ninoSandbox( 'hours' );
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Components', '\\Nino\\Modules\\Elements' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

$dir = dirname( __DIR__ );

/**
 *	A page as a tree to ask: the html a shortcode made, parsed as a browser would
 */
$tree = static function( string $html ): DOMXPath {
	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="utf-8"?><body>'. $html. '</body>' );
	libxml_clear_errors();
	return new DOMXPath( $document );
};

/**
 *	The words of the language the page is in, as the page reads them
 */
$word = static function( string $key ) use ( &$appData ): string {
	return html_entity_decode( \Nino\Html::renderTextfill( $appData, $key ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
};

$text = static fn( DOMNode $node ): string => trim( (string) preg_replace( '/\s+/u', ' ', $node->textContent ) );
$hold = static function( string $moment ) use ( &$appData ): void {
	$appData['./hours/now'] = $moment;
};
$days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

echo "1. The manifest and the unit\n";

$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates without a warning', is_array( $manifest ) === true && ninoWarnings() === [] );
check( 'it is a new feature at 0.1.0 for the kernel whose vocabulary it uses, with the stylesheet it ships', ( $manifest['version'] ?? '' ) === '0.1.0'
	&& ( $manifest['nino'] ?? '' ) === '^1.12' && ( $manifest['assets'] ?? [] ) === [ '/features/Hours/assets/hours.css' ] && is_file( $dir. '/assets/hours.css' ) === true );
check( 'it brings two components of the source none, hours and hours-today, and no stack', array_keys( $manifest['components'] ?? [] ) === [ 'hours', 'hours-today' ]
	&& ( $manifest['components']['hours']['source'] ?? '' ) === 'none' && ( $manifest['components']['hours-today']['source'] ?? '' ) === 'none' && ( $manifest['stacks'] ?? [] ) === [] );
check( 'it keeps one setting, the timezone, a select that offers the server\'s and every zone PHP knows', array_keys( $manifest['settings'] ?? [] ) === [ 'timezone' ]
	&& ( $manifest['settings']['timezone']['type'] ?? '' ) === 'select' && ( $manifest['settings']['timezone']['default'] ?? '' ) === 'server'
	&& isset( $manifest['settings']['timezone']['options']['server'], $manifest['settings']['timezone']['options']['Europe/Berlin'] ) === true );
check( 'the class carries the two renderers of its components and no panel', method_exists( \Nino\Modules\Hours::class, 'componentHours' ) === true
	&& method_exists( \Nino\Modules\Hours::class, 'componentHoursToday' ) === true && method_exists( \Nino\Modules\Hours::class, 'adminPanels' ) === false );

check( 'activation succeeds and records the version', \Nino\Features::activate( $appData, 'hours' ) === true && \Nino\Features::get( $appData, 'hours' )['installed'] === '0.1.0' );
\Nino\Html::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );

$typeFile = static fn( string $type ): array => (array) ( static fn( string $path ): mixed => is_file( $path ) === true ? require $path : [] )( ninoSandboxDir( $appData ). '/private/elements/'. $type. '.php' );
$locations = $typeFile( 'locations' );
$closures = $typeFile( 'closures' );

check( 'the unit copies both element types, each without an element', array_keys( $locations['*'] ?? [] ) === [ '*' ] && array_keys( $closures['*'] ?? [] ) === [ '*' ]
	&& array_keys( $locations ) === [ 'title', 'model', '*' ] && array_keys( $closures ) === [ 'title', 'model', '*' ] );
check( 'a location has a name and a note for each language, an address, and one text for each of the seven days',
	array_keys( $locations['model'] ) === [ 'name', 'address', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'note' ]
	&& $locations['model']['name']['locale'] === true && $locations['model']['note']['locale'] === true && isset( $locations['model']['address']['locale'] ) === false
	&& array_unique( array_map( static fn( string $day ): string => $locations['model'][$day]['type'].( $locations['model'][$day]['locale'] ?? '' ), [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ] ) ) === [ 'string' ] );
check( 'a closure refers to a location with the kernel\'s own field type for it, has two dates, times and a reason for each language',
	array_keys( $closures['model'] ) === [ 'location', 'from', 'until', 'times', 'reason' ]
	&& $closures['model']['location']['type'] === 'element' && $closures['model']['location']['elementType'] === 'locations'
	&& $closures['model']['from']['type'] === 'date' && $closures['model']['until']['type'] === 'date' && $closures['model']['from']['required'] === true
	&& isset( $closures['model']['until']['required'] ) === false && $closures['model']['reason']['locale'] === true );

$workbench = [];
foreach( [ 'locations' => $locations, 'closures' => $closures ] as $type => $file )
	foreach( array_keys( $file['model'] ) as $field )
		$workbench[] = '/_admin/elements/field/'. $type. '/'. $field;
$unit = require $dir. '/install/manifest.php';
check( 'every field has a label in the Elements panel in both languages, and the unit blacklists those words', $workbench !== [] && array_diff( $workbench, $unit['blacklist'] ) === [] && array_diff( $unit['blacklist'], $workbench ) === []
	&& array_filter( [ 'en_US', 'de_DE' ], static function( string $locale ) use ( $appData, $workbench ): bool {
		$texts = (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
		return array_filter( $workbench, static fn( string $key ): bool => ( $texts['[['. $key. ']]'] ?? '' ) === '' ) === [];
	} ) === [ 'en_US', 'de_DE' ] );

$before = md5_file( ninoSandboxDir( $appData ). '/private/elements/locations.php' ). md5_file( ninoSandboxDir( $appData ). '/private/elements/closures.php' );
check( 'a second activation changes neither type', \Nino\Features::activate( $appData, 'hours' ) === true
	&& md5_file( ninoSandboxDir( $appData ). '/private/elements/locations.php' ). md5_file( ninoSandboxDir( $appData ). '/private/elements/closures.php' ) === $before );

$own = ninoSandbox( 'hours-own' );
$own['/nino/modules'] = [ '\\Nino\\Modules\\Components', '\\Nino\\Modules\\Elements' ];
\Nino\AppData::writeContentData( $own, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
\Nino\Filesystem::forceDir( $own, '/elements' );
\Nino\Filesystem::putFileContent( $own, '/elements/locations.php', [ 'title' => 'Mine', 'model' => [ 'name' => [ 'type' => 'string' ] ], '*' => [ '*' => [], 'shop' => [ 'name' => 'Shop' ] ] ] );
$mine = md5_file( ninoSandboxDir( $own ). '/private/elements/locations.php' );
check( 'a project that has an element type called locations keeps it as it is', \Nino\Features::activate( $own, 'hours' ) === true && md5_file( ninoSandboxDir( $own ). '/private/elements/locations.php' ) === $mine );
\Nino\Html::init( $own );
\Nino\Modules::callModules( $own, 'init' );
check( '...and its shortcode draws a location without days as its name, with no table and no warning', ( static function() use ( &$own, $tree ): bool {
	$page = $tree( \Nino\Html::renderHtml( $own, '[hours /locations/shop]' ) );
	return $page->query( '//div[contains(@class,"nino-hours")]' )->length === 1 && $page->query( '//table' )->length === 0 && ninoWarnings() === [];
} )() );
$wordsOf = static fn( string $locale ): array => (array) require $dir. '/install/text/'. $locale. '.php';
check( 'both languages have the same words, each of them with a text, in the grammar of a key', array_keys( $wordsOf( 'en_US' ) ) === array_keys( $wordsOf( 'de_DE' ) )
	&& array_filter( array_merge( $wordsOf( 'en_US' ), $wordsOf( 'de_DE' ) ), static fn( mixed $value ): bool => is_string( $value ) === false || trim( $value ) === '' ) === []
	&& array_filter( array_keys( $wordsOf( 'en_US' ) ), static fn( string $key ): bool => preg_match( '#^\[\[/(?:feature/hours/[a-z0-9-]+/[a-z0-9-]+|_admin/elements/field/(?:locations|closures)/[a-z0-9-]+)\]\]$#D', $key ) !== 1 ) === [] );
check( '...among them the seven days short and long, which the output and the sentences read', ( static function() use ( $wordsOf, $days ): bool {
	foreach( [ 'en_US', 'de_DE' ] as $locale )
		foreach( $days as $day )
			foreach( [ 'day-short', 'day-long' ] as $part )
				if( isset( $wordsOf( $locale )['[[/feature/hours/'. $part. '/'. $day. ']]'] ) === false )
					return false;
	return true;
} )() );
echo "\n";

echo "2. The notation of a day: what is read, what is refused\n";

// input => normal form, or '!' and the word of the error. The same table goes through the reader and through the write of an element
$table = [
	''																				=> '',
	"   \t "																	=> '',
	'9-12'																		=> '09:00-12:00',
	'09:00-12:00'															=> '09:00-12:00',
	'9:00-12:00'															=> '09:00-12:00',
	'9.00-12.00'															=> '09:00-12:00',
	'9.30-12'																=> '09:30-12:00',
	'9:30–18'																=> '09:30-18:00',
	'9 - 12'																	=> '09:00-12:00',
	'9 – 12'																	=> '09:00-12:00',
	"\t9\t-\t12 "														=> '09:00-12:00',
	"9\u{00A0}-\u{00A0}12"									=> '09:00-12:00',
	'9-12, 14-18'														=> '09:00-12:00, 14:00-18:00',
	'9-12,14-18'															=> '09:00-12:00, 14:00-18:00',
	'14-18, 9-12'														=> '09:00-12:00, 14:00-18:00',
	'9-12, 14-18, 20-22'											=> '09:00-12:00, 14:00-18:00, 20:00-22:00',
	'9-12, 12-14'														=> '09:00-12:00, 12:00-14:00',
	'9:05-9:10'															=> '09:05-09:10',
	'06-14'																	=> '06:00-14:00',
	'6-14'																		=> '06:00-14:00',
	'0-24'																		=> '00:00-24:00',
	'0:00-24:00'															=> '00:00-24:00',
	'8-24'																		=> '08:00-24:00',
	'18-2'																		=> '18:00-02:00',
	'22-6'																		=> '22:00-06:00',
	'18:30-0:15'															=> '18:30-00:15',
	'12-0'																		=> '12:00-24:00',
	'5-0'																		=> '05:00-24:00',
	'23:59-0:01'															=> '23:59-00:01',
	'18-2, 3-4'															=> '03:00-04:00, 18:00-02:00',
	'1-2, 3-4, 5-6, 7-8, 9-10, 11-12, 13-14, 15-16'	=> '01:00-02:00, 03:00-04:00, 05:00-06:00, 07:00-08:00, 09:00-10:00, 11:00-12:00, 13:00-14:00, 15:00-16:00',
	'abc'																		=> '!unreadable',
	'9'																			=> '!unreadable',
	'9-'																			=> '!unreadable',
	'-12'																		=> '!unreadable',
	'9--12'																	=> '!unreadable',
	'9-12,'																	=> '!unreadable',
	',9-12'																	=> '!unreadable',
	'9-12,,14-18'														=> '!unreadable',
	'9—12'																	=> '!unreadable',
	'9 to 12'																=> '!unreadable',
	'9-12 Uhr'															=> '!unreadable',
	'9h-12h'																=> '!unreadable',
	'9:5-12'																	=> '!unreadable',
	'9:30:00-12'														=> '!unreadable',
	'9-12; 14-18'														=> '!unreadable',
	'09:00-12:00-14:00'											=> '!unreadable',
	'１-２'																	=> '!unreadable',
	'٩-١٢'																	=> '!unreadable',
	"9-\xFF12"															=> '!unreadable',
	'1-2, 3-4, 5-6, 7-8, 9-10, 11-12, 13-14, 15-16, 17-18'	=> '!many',
	str_repeat( '9-10, ', 40 )								=> '!unreadable',
	'9-12'. str_repeat( ' ', 300 )						=> '!unreadable',
	str_repeat( ' ', 150 ). '9-12'							=> '09:00-12:00',
	'9:75-12'																=> '!minutes',
	'09:00-12:60'														=> '!minutes',
	'9-25'																		=> '!hours',
	'25-26'																	=> '!hours',
	'9-24:30'																=> '!hours',
	'24-2'																		=> '!hours',
	'24:00-2'																=> '!hours',
	'9-12, 11-13'														=> '!overlap',
	'9-18, 12-14'														=> '!overlap',
	'9-12, 9-10'															=> '!overlap',
	'14-18, 9-15'														=> '!overlap',
	'18-2, 20-22'														=> '!overlap',
	'9-9'																		=> '!equal',
	'9:00-9'																=> '!equal',
	'0-0'																		=> '!equal',
];
check( 'the table has more than 40 inputs, valid and refused', count( $table ) >= 40 && count( array_filter( $table, static fn( string $result ): bool => str_starts_with( $result, '!' ) ) ) >= 20
	&& count( array_filter( $table, static fn( string $result ): bool => str_starts_with( $result, '!' ) === false ) ) >= 20 );

$misread = [];
foreach( $table as $input => $expected ) {
	$read = \Nino\Modules\Hours::parseTimes( (string) $input );
	if( str_starts_with( $expected, '!' ) ? ( $read['error'] !== substr( $expected, 1 ) || $read['normal'] !== '' ) : ( $read['error'] !== '' || $read['normal'] !== $expected ) )
		$misread[] = json_encode( (string) $input, JSON_UNESCAPED_UNICODE ). ' read as '. json_encode( $read, JSON_UNESCAPED_UNICODE );
}
check( 'the reader answers each input with its normal form or with the one error it is: '. count( $table ). ' inputs'. ( $misread === [] ? '' : ' - '. implode( ' | ', array_slice( $misread, 0, 4 ) ) ), $misread === [] );

$normal = [];
foreach( $table as $input => $expected )
	if( str_starts_with( $expected, '!' ) === false )
		$normal[] = $expected;
$again = array_filter( $normal, static fn( string $form ): bool => \Nino\Modules\Hours::parseTimes( $form )['normal'] !== $form );
check( 'a normal form is read as itself', $again === [] );

$unlike = [];
$index = 0;
foreach( $table as $input => $expected ) {
	$slug = 'row'. ( $index++ );
	$errors = ninoErrors( static function() use ( &$appData, $slug, $input ): void {
		\Nino\Elements::insertElement( $appData, '/locations/'. $slug, [ 'name' => $slug, 'wed' => (string) $input ], 'de_DE' );
	} );
	$stored = \Nino\Elements::getElement( $appData, '/locations/'. $slug, '*' );
	if( str_starts_with( $expected, '!' ) ? ( $stored !== false || count( $errors ) !== 1 ) : ( is_array( $stored ) === false || ( $stored['wed'] ?? '' ) !== $expected || $errors !== [] ) )
		$unlike[] = json_encode( (string) $input, JSON_UNESCAPED_UNICODE );
}
check( 'saving an element has the same result for every input: written in the normal form, or refused with one sentence and nothing written'. ( $unlike === [] ? '' : ' - '. implode( ' | ', array_slice( $unlike, 0, 4 ) ) ), $unlike === [] );
foreach( array_keys( $table ) as $position => $input )
	\Nino\Elements::deleteElement( $appData, '/locations/row'. $position );
echo "\n";

echo "3. The write of a location\n";

\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'en_US' );
\Nino\Locales::useLocale( $appData, 'de_DE' );
$sentence = static function( string $error, string $field, string $element, string $value, string $locale ) use ( &$appData ): string {
	$fills = \Nino\Html::getFills( $appData, $locale );
	$read = static fn( string $key ): string => html_entity_decode( (string) ( $fills['[['. $key. ']]'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return sprintf( $read( '/feature/hours/error/'. $error ), $read( $field ), $element, $value );
};
$errors = ninoErrors( static fn() => \Nino\Elements::insertElement( $appData, '/locations/alpha', [ 'name' => 'Alpha', 'mon' => '9-12', 'thu' => '25-26' ], 'de_DE' ) );
check( 'a refused location is not written, in no language and in no bucket', \Nino\Elements::getElement( $appData, '/locations/alpha', '*' ) === false
	&& isset( $typeFile( 'locations' )['*']['alpha'] ) === false && isset( $typeFile( 'locations' )['de_DE']['alpha'] ) === false );
check( 'the sentence names the day, the element and what was typed, in the language of the workbench session',
	count( $errors ) === 1 && $errors[0][1] === $sentence( 'hours', '/feature/hours/day-long/thu', 'alpha', '25-26', 'en_US' ) );
\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'de_DE' );
$errors = ninoErrors( static fn() => \Nino\Elements::insertElement( $appData, '/locations/alpha', [ 'name' => 'Alpha', 'thu' => '25-26' ], 'de_DE' ) );
check( '...and in the other language of the session', count( $errors ) === 1 && $errors[0][1] === $sentence( 'hours', '/feature/hours/day-long/thu', 'alpha', '25-26', 'de_DE' )
	&& $sentence( 'hours', '/feature/hours/day-long/thu', 'alpha', '25-26', 'de_DE' ) !== $sentence( 'hours', '/feature/hours/day-long/thu', 'alpha', '25-26', 'en_US' ) );
check( 'every error of the notation has a sentence in both languages that takes the three values', ( static function() use ( &$appData ): bool {
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$fills = \Nino\Html::getFills( $appData, $locale );
		foreach( [ 'unreadable', 'many', 'minutes', 'hours', 'equal', 'overlap', 'date', 'order', 'unknown' ] as $error )
			if( str_contains( (string) ( $fills['[[/feature/hours/error/'. $error. ']]'] ?? '' ), '%1$s' ) === false )
				return false;
	}
	return true;
} )() );

$errors = ninoErrors( static fn() => \Nino\Elements::insertElement( $appData, '/locations/beta', [ 'name' => 'Beta', 'sat' => '10-14', 'sun' => '12-12' ], 'de_DE' ) );
check( 'the first day that cannot be read stops the write, in the order of the week', count( $errors ) === 1 && $errors[0][1] === $sentence( 'equal', '/feature/hours/day-long/sun', 'beta', '12-12', 'de_DE' ) );

check( 'a location with every day readable is written, each day in its normal form, and an empty day stays empty',
	is_array( \Nino\Elements::insertElement( $appData, '/locations/main', [ 'name' => 'Main', 'mon' => '9-12 , 14.30–18', 'tue' => '9-12, 14.30-18', 'wed' => '', 'sat' => '10-14' ], 'de_DE' ) )
	&& \Nino\Elements::getElement( $appData, '/locations/main', '*' )['mon'] === '09:00-12:00, 14:30-18:00' && \Nino\Elements::getElement( $appData, '/locations/main', '*' )['wed'] === ''
	&& \Nino\Elements::getElement( $appData, '/locations/main', '*' )['sat'] === '10:00-14:00' );

$errors = ninoErrors( static fn() => \Nino\Elements::updateElement( $appData, '/locations/main', [ 'fri' => '9:61-12' ], 'de_DE' ) );
check( 'an update with a day that cannot be read is refused and leaves the element as it was', count( $errors ) === 1 && ( \Nino\Elements::getElement( $appData, '/locations/main', '*' )['fri'] ?? '' ) === ''
	&& \Nino\Elements::getElement( $appData, '/locations/main', '*' )['mon'] === '09:00-12:00, 14:30-18:00' );
check( 'an update of one day writes it in the normal form and keeps the others', is_array( \Nino\Elements::updateElement( $appData, '/locations/main', [ 'fri' => '8.5-12' ], 'de_DE' ) ) === false
	&& is_array( \Nino\Elements::updateElement( $appData, '/locations/main', [ 'fri' => '8.50-12' ], 'de_DE' ) ) && \Nino\Elements::getElement( $appData, '/locations/main', '*' )['fri'] === '08:50-12:00'
	&& \Nino\Elements::getElement( $appData, '/locations/main', '*' )['sat'] === '10:00-14:00' );

// An element written by hand that cannot be read is not asked again until it is changed, so it does not hold up the others
$file = ninoSandboxDir( $appData ). '/private/elements/locations.php';
$content = require $file;
$content['*']['hand'] = [ 'tue' => 'garbage' ];
$content['de_DE']['hand'] = [ 'name' => 'Hand' ];
\Nino\Filesystem::putFileContent( $appData, '/elements/locations.php', $content );
check( 'an element written by hand that cannot be read does not stop the write of another', is_array( \Nino\Elements::insertElement( $appData, '/locations/next', [ 'name' => 'Next', 'mon' => '7-8' ], 'de_DE' ) )
	&& is_array( \Nino\Elements::updateElement( $appData, '/locations/next', [ 'mon' => '7-9' ], 'de_DE' ) ) );
check( '...but a change to that element asks again', count( ninoErrors( static fn() => \Nino\Elements::updateElement( $appData, '/locations/hand', [ 'sat' => '1-2' ], 'de_DE' ) ) ) === 1
	&& is_array( \Nino\Elements::updateElement( $appData, '/locations/hand', [ 'tue' => '1-2' ], 'de_DE' ) ) );
\Nino\Elements::deleteElement( $appData, '/locations/hand' );
\Nino\Elements::deleteElement( $appData, '/locations/next' );

check( 'a type of another name is not looked at', ( static function() use ( &$appData ): bool {
	\Nino\Filesystem::putFileContent( $appData, '/elements/places.php', [ 'title' => 'Places', 'model' => [ 'wed' => [ 'type' => 'string' ] ], '*' => [ '*' => [] ] ] );
	return is_array( \Nino\Elements::insertElement( $appData, '/places/one', [ 'wed' => 'whenever' ], 'de_DE' ) ) && \Nino\Elements::getElement( $appData, '/places/one', '*' )['wed'] === 'whenever';
} )() );

// The Elements panel itself: the answer of the save action, the sentence in the status line
ninoUser( $appData, 'dev@example.com' );
\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'en_US' );
[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'type' => 'locations', 'uri' => 'panel', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'name' => 'Panel', 'mon' => '9-' ] ], 'elements/save' );
check( 'the save action of the Elements panel answers 400 with the sentence, and nothing is written', $status === 400 && ( $body['error'] ?? '' ) === $sentence( 'unreadable', '/feature/hours/day-long/mon', 'panel', '9-', 'en_US' )
	&& \Nino\Elements::getElement( $appData, '/locations/panel', '*' ) === false );
// The shell marks a field where the answer names it: the kernel's step before it sends the answer is the callbacks of /nino/http/output
$answered = static function( string $type, array $fields ) use ( &$appData ): array {
	[ $status, $body, $request ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'type' => $type, 'uri' => 'probe', 'locale' => 'de_DE', 'isNew' => true, 'fields' => $fields ], 'elements/save' );
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );
	return [ $status, $request['/nino/http/response']['body'], $body ];
};
$named = [];
foreach( [
	[ 'locations', [ 'name' => 'P', 'mon' => '9-12', 'sun' => '12-12' ], 'sun' ], [ 'locations', [ 'name' => 'P', 'thu' => '9-' ], 'thu' ],
	[ 'closures', [ 'from' => '2026-02-30' ], 'from' ], [ 'closures', [ 'from' => '2026-12-24', 'until' => '2026-12-01' ], 'until' ],
	[ 'closures', [ 'from' => '2026-12-24', 'until' => '2026-13-01' ], 'until' ], [ 'closures', [ 'from' => '2026-12-24', 'times' => '9:99-12' ], 'times' ],
	[ 'closures', [ 'from' => '2026-12-24', 'location' => '/locations/nowhere' ], 'location' ],
] as [ $type, $fields, $field ] ) {
	[ $status, $answer, $before ] = $answered( $type, $fields );
	$named[] = $status === 400 && ( $answer['field'] ?? '' ) === $field && ( $answer['error'] ?? '' ) === ( $before['error'] ?? '-' ) && count( $answer ) === 2 ? '' : $type. ' '. $field;
}
check( 'the answer to a refused save of the Elements panel names the field the sentence is about, and nothing else of it is changed: '. count( $named ). ' refusals', array_filter( $named ) === [] );
[ $status, $answer ] = $answered( 'locations', [ 'mon' => '9-12' ] );
check( 'an answer that is the kernel\'s own refusal (the name is required) is left as it is, and the key of the last refusal is gone', $status === 400 && isset( $answer['field'] ) === false && isset( $appData['./hours/refused'] ) === false );
[ $status, $answer ] = $answered( 'locations', [ 'name' => 'Probe', 'mon' => '9-12' ] );
check( '...and so is a save that went through', $status === 200 && isset( $answer['field'] ) === false );
\Nino\Elements::deleteElement( $appData, '/locations/probe' );
$handled = static function( int $status, string $error ) use ( &$appData ): array {
	$appData['./hours/refused'] = [ 'error' => 'said', 'field' => 'mon' ];
	$request = [ '/nino/http/response' => [ 'statusCode' => $status, 'body' => [ 'error' => $error ] ] ];
	\Nino\Modules\Hours::callbackOutput( $appData, $request );
	return $request['/nino/http/response']['body'];
};
check( 'only the answer that is the 400 with the sentence this feature said gets the name of the field', $handled( 400, 'said' ) === [ 'error' => 'said', 'field' => 'mon' ]
	&& $handled( 400, 'another' ) === [ 'error' => 'another' ] && $handled( 200, 'said' ) === [ 'error' => 'said' ] && $handled( 403, 'said' ) === [ 'error' => 'said' ] );
[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'type' => 'locations', 'uri' => 'panel', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'name' => 'Panel', 'mon' => '9-12' ] ], 'elements/save' );
check( '...and a save that can be read answers 200 with the element in its normal form', $status === 200 && ( $body['element']['mon'] ?? '' ) === '09:00-12:00' );
\Nino\Elements::deleteElement( $appData, '/locations/panel' );
\Nino\Runtime::setSessionValue( $appData, './admin/locale', 'de_DE' );
echo "\n";

echo "4. The write of a closure\n";

$sentenceOf = static fn( string $error, string $field, string $element, string $value ): string => $sentence( $error, $field, $element, $value, 'de_DE' );
$attempt = static function( array $fields, string $slug = 'c' ) use ( &$appData ): array {
	$errors = ninoErrors( static function() use ( &$appData, $fields, $slug ): void {
		\Nino\Elements::insertElement( $appData, '/closures/'. $slug, $fields, 'de_DE' );
	} );
	$stored = \Nino\Elements::getElement( $appData, '/closures/'. $slug, '*' );
	if( is_array( $stored ) === true )
		\Nino\Elements::deleteElement( $appData, '/closures/'. $slug );
	return [ $errors, $stored ];
};

[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'until' => '2026-12-23' ] );
check( 'a last day before the first is refused and not written', count( $errors ) === 1 && $stored === false && $errors[0][1] === $sentenceOf( 'order', '/feature/hours/field/until', 'c', '2026-12-23' ) );
foreach( [ '24.12.2026', '2026-02-30', '2026-13-01', '2026-12-32', '2026-1-5', 'tomorrow', '2027-02-29', '0000-00-00' ] as $date ) {
	[ $errors, $stored ] = $attempt( [ 'from' => $date ] );
	check( 'a first day that is no date is refused: '. $date, count( $errors ) === 1 && $stored === false && $errors[0][1] === $sentenceOf( 'date', '/feature/hours/field/from', 'c', $date ) );
}
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'until' => '2026-02-30' ] );
check( 'a last day that is no date is refused', count( $errors ) === 1 && $stored === false && $errors[0][1] === $sentenceOf( 'date', '/feature/hours/field/until', 'c', '2026-02-30' ) );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'times' => '25-26' ] );
check( 'times that cannot be read are refused', count( $errors ) === 1 && $stored === false && $errors[0][1] === $sentenceOf( 'hours', '/feature/hours/field/times', 'c', '25-26' ) );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'location' => '/locations/nowhere' ] );
check( 'a location that is not there is refused', count( $errors ) === 1 && $stored === false && $errors[0][1] === $sentenceOf( 'unknown', '/feature/hours/field/location', 'c', '/locations/nowhere' ) );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'location' => '/locations/' ] );
check( 'a reference that is no element is refused', $stored === false && $errors !== [] );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'until' => '2026-12-24' ] );
check( 'a last day that is the first day is one day and is written', $errors === [] && is_array( $stored ) === true );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'location' => '/locations/*' ] );
check( 'the entry of the defaults of the type is no location', count( $errors ) === 1 && $stored === false && $errors[0][1] === $sentenceOf( 'unknown', '/feature/hours/field/location', 'c', '/locations/*' ) );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'location' => '/places/one' ] );
check( 'a reference into another type is refused by the kernel before the feature is asked', $stored === false && $errors !== [] );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24', 'location' => '/locations/main', 'times' => '10-14', 'until' => '2026-12-26' ] );
check( 'a location that is there, two dates in order and times that can be read are written', $errors === [] && is_array( $stored ) === true );
\Nino\Elements::insertElement( $appData, '/closures/keep', [ 'from' => '2026-12-24', 'location' => '/locations/main', 'times' => '10-14', 'until' => '2026-12-26' ], 'de_DE' );
check( '...and the times are stored in the normal form', ( \Nino\Elements::getElement( $appData, '/closures/keep', '*' )['times'] ?? '' ) === '10:00-14:00' );
\Nino\Elements::deleteElement( $appData, '/closures/keep' );
[ $errors, $stored ] = $attempt( [ 'from' => '2026-12-24' ] );
check( 'a closure with the first day alone is written: no location is every location, no last day is the one day, no times is closed', $errors === [] && is_array( $stored ) === true );
check( 'a closure without a first day is refused by the kernel, as the field is required', $attempt( [ 'until' => '2026-12-26' ] )[1] === false );
[ $errors, $stored ] = $attempt( [ 'from' => '2028-02-29' ] );
check( 'a leap day is a date', $errors === [] );
[ $errors, $stored ] = $attempt( [ 'from' => '2027-02-29' ] );
check( 'the day after the last of February in a year without a leap day is not', count( $errors ) === 1 );
echo "\n";

echo "5. The week\n";

$store = static function( array $locationsToWrite, array $closuresToWrite = [] ) use ( &$appData ): void {
	foreach( \Nino\Elements::queryElements( $appData, '/locations', [], '*', [] ) as $hit )
		\Nino\Elements::deleteElement( $appData, (string) $hit['.uri'] );
	foreach( \Nino\Elements::queryElements( $appData, '/closures', [], '*', [] ) as $hit )
		\Nino\Elements::deleteElement( $appData, (string) $hit['.uri'] );
	foreach( $locationsToWrite as $slug => $fields )
		\Nino\Elements::insertElement( $appData, '/locations/'. $slug, $fields, 'de_DE' );
	foreach( $closuresToWrite as $slug => $fields )
		\Nino\Elements::insertElement( $appData, '/closures/'. $slug, $fields, 'de_DE' );
};
$weekdays = [ 'mon' => '9-12, 14-18', 'tue' => '9-12, 14-18', 'wed' => '9-12, 14-18', 'thu' => '9-12, 14-18', 'fri' => '9-12, 14-18', 'sat' => '10-14', 'sun' => '' ];
$store( [ 'main' => [ 'name' => 'Main', 'address' => "Main Street 1\n12345 Town" ] + $weekdays ] );
$hold( '2026-12-02T10:00:00+01:00' );

$page = $tree( \Nino\Html::renderHtml( $appData, '[hours]' ) );
$rows = $page->query( '//table//tbody/tr' );
check( 'one location: its name is the caption of a table with a row for each run of equal days', $page->query( '//div[contains(@class,"nino-hours")]' )->length === 1
	&& $page->query( '//table/caption' )->length === 1 && $text( $page->query( '//table/caption/span[1]' )->item( 0 ) ) === 'Main' && $rows->length === 3 );
check( 'a run of days with the same hours is one row: the first and the last day of the run are in its header',
	str_contains( $text( $page->query( 'th', $rows->item( 0 ) )->item( 0 ) ), $word( '/feature/hours/day-short/mon' ) ) && str_contains( $text( $page->query( 'th', $rows->item( 0 ) )->item( 0 ) ), $word( '/feature/hours/day-short/fri' ) )
	&& str_contains( $text( $page->query( 'th', $rows->item( 0 ) )->item( 0 ) ), $word( '/feature/hours/day-short/wed' ) ) === false
	&& str_contains( $text( $page->query( 'th', $rows->item( 1 ) )->item( 0 ) ), $word( '/feature/hours/day-short/sat' ) ) && str_contains( $text( $page->query( 'th', $rows->item( 2 ) )->item( 0 ) ), $word( '/feature/hours/day-short/sun' ) ) );
check( 'a row header is a header cell of its row, and every time is a time element with the machine-readable value',
	$page->query( '//tbody/tr/th[@scope="row"]' )->length === 3
	&& implode( ',', array_map( static fn( DOMNode $time ): string => $time->attributes->getNamedItem( 'datetime' )->nodeValue, iterator_to_array( $page->query( '(//tbody/tr)[1]//time' ) ) ) ) === '09:00,12:00,14:00,18:00' );
check( 'a closed day has the word for it where its times are', $page->query( '(//tbody/tr)[3]//*[contains(@class,"nino-hours-closed")]' )->length === 1
	&& $text( $page->query( '(//tbody/tr)[3]//*[contains(@class,"nino-hours-closed")]' )->item( 0 ) ) === $word( '/feature/hours/word/closed' ) && $word( '/feature/hours/word/closed' ) !== '' );

// Runs: days that are equal but do not follow one another are two rows, and the week can begin on any day
$week = static fn( array $spans ): array => array_combine( [ 1, 2, 3, 4, 5, 6, 7 ], $spans );
$a = [ [ 540, 720 ] ];
$b = [ [ 600, 700 ] ];
$runsOf = static fn( array $spans, int $first ): string => implode( ' ', array_map( static fn( array $run ): string => $run['from']. '-'. $run['to'], \Nino\Modules\Hours::runs( $week( $spans ), $first ) ) );
check( 'runs: the week of one kind of day is one run', $runsOf( [ $a, $a, $a, $a, $a, $a, $a ], 1 ) === '1-7' && $runsOf( [ $a, $a, $a, $a, $a, $a, $a ], 7 ) === '7-6' );
check( 'runs: equal days that do not follow one another are not put together', $runsOf( [ $a, $b, $a, $b, $a, $b, $b ], 1 ) === '1-1 2-2 3-3 4-4 5-5 6-7' );
check( 'runs: a week that begins on Sunday puts Saturday last and Sunday first, and Monday to Friday between them', $runsOf( [ $a, $a, $a, $a, $a, $b, [] ], 7 ) === '7-7 1-5 6-6' );
check( 'runs: a closed day is a day like another, and a run does not wrap around the end of the week', $runsOf( [ [], $a, $a, $a, $a, $a, [] ], 1 ) === '1-1 2-6 7-7' );

\Nino\Locales::useLocale( $appData, 'en_US' );
$page = $tree( \Nino\Html::renderHtml( $appData, '[hours]' ) );
$rows = $page->query( '//table//tbody/tr' );
check( 'the first day of the week follows the language: in en_US the week begins on Sunday', $word( '/feature/hours/week/first' ) === 'sun' && $rows->length === 3
	&& str_contains( $text( $page->query( 'th', $rows->item( 0 ) )->item( 0 ) ), $word( '/feature/hours/day-short/sun' ) ) && str_contains( $text( $page->query( 'th', $rows->item( 1 ) )->item( 0 ) ), $word( '/feature/hours/day-short/fri' ) )
	&& str_contains( $text( $page->query( 'th', $rows->item( 2 ) )->item( 0 ) ), $word( '/feature/hours/day-short/sat' ) ) );
check( 'a time reads the way its language counts: the twelve-hour clock has the two halves, the twenty-four-hour clock none', $word( '/feature/hours/time/clock' ) === '12'
	&& str_contains( $text( $rows->item( 1 ) ), $word( '/feature/hours/time/am' ) ) && str_contains( $text( $rows->item( 1 ) ), $word( '/feature/hours/time/pm' ) ) );
\Nino\Elements::insertElement( $appData, '/locations/twelve', [ 'name' => 'Twelve', 'mon' => '12-0', 'tue' => '0-12', 'wed' => '13:15-23:30' ], 'de_DE' );
$cells = [];
foreach( $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/twelve]' ) )->query( '//tbody/tr/td' ) as $cell )
	$cells[] = $text( $cell );
check( 'on the twelve-hour clock (the week begins on Sunday there) noon is 12 pm, the end of the day is the word for midnight and not a 12 am that reads as noon, and 0 is 12 am',
	$cells[1] === sprintf( $word( '/feature/hours/time/range' ), '12 '. $word( '/feature/hours/time/pm' ), $word( '/feature/hours/time/midnight' ) ) && $word( '/feature/hours/time/midnight' ) !== ''
	&& $cells[2] === sprintf( $word( '/feature/hours/time/range' ), '12 '. $word( '/feature/hours/time/am' ), '12 '. $word( '/feature/hours/time/pm' ) )
	&& $cells[3] === sprintf( $word( '/feature/hours/time/range' ), '1:15 '. $word( '/feature/hours/time/pm' ), '11:30 '. $word( '/feature/hours/time/pm' ) ) );
\Nino\Elements::deleteElement( $appData, '/locations/twelve' );
\Nino\Html::addFills( $appData, [ '/feature/hours/week/first' => 'someday' ], 'en_US' );
$rows = $tree( \Nino\Html::renderHtml( $appData, '[hours]' ) )->query( '//table//tbody/tr' );
check( 'a first day that is none of the days is Monday', str_contains( $text( $rows->item( 0 ) ), $word( '/feature/hours/day-short/mon' ) ) );
\Nino\Html::removeFill( $appData, '/feature/hours/week/first', 'en_US' );
\Nino\Locales::useLocale( $appData, 'de_DE' );

$clockOf = static function( string $times ) use ( &$appData, $tree, $text ): string {
	$page = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/clock]' ) );
	return $text( $page->query( '//tbody/tr' )->item( 0 ) );
};
\Nino\Elements::insertElement( $appData, '/locations/clock', [ 'name' => 'Clock', 'mon' => '9-18', 'tue' => '9:30-18', 'wed' => '0-24', 'thu' => '18-2', 'fri' => '12-0' ], 'de_DE' );
$page = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/clock]' ) );
$cells = [];
foreach( $page->query( '//tbody/tr/td' ) as $cell )
	$cells[] = $text( $cell );
check( 'the twenty-four-hour clock writes 9 to 18, 9:30 and midnight as 24, and a night as the hour it ends',
	$cells[0] === sprintf( $word( '/feature/hours/time/range' ), '9', '18' ) && $cells[1] === sprintf( $word( '/feature/hours/time/range' ), '9:30', '18' )
	&& $cells[2] === sprintf( $word( '/feature/hours/time/range' ), '0', '24' ) && $cells[3] === sprintf( $word( '/feature/hours/time/range' ), '18', '2' )
	&& $cells[4] === sprintf( $word( '/feature/hours/time/range' ), '12', '24' ) );
check( 'a time of the end of the day is the beginning of the next in its attribute', $page->query( '//time[@datetime="24:00"]' )->length === 0 && $page->query( '(//tbody/tr)[3]//time' )->item( 1 )->attributes->getNamedItem( 'datetime' )->nodeValue === '00:00' );
\Nino\Elements::deleteElement( $appData, '/locations/clock' );
echo "\n";

echo "6. Today\n";

$store( [ 'main' => [ 'name' => 'Main' ] + $weekdays, 'night' => [ 'name' => 'Night', 'mon' => '18-2', 'fri' => '9-12' ] ] );
$marked = static function( string $moment, string $call = '[hours /locations/main]' ) use ( &$appData, $tree, $hold ): array {
	$hold( $moment );
	$page = $tree( \Nino\Html::renderHtml( $appData, $call ) );
	$rows = $page->query( '//tbody/tr[@aria-current="date"]' );
	$all = [];
	foreach( $page->query( '//tbody/tr' ) as $row )
		$all[] = [ $row->attributes->getNamedItem( 'aria-current' ), $row->attributes->getNamedItem( 'data-today' ) ];
	return [ $rows->length, $rows->length === 1 ? $rows->item( 0 )->attributes->getNamedItem( 'data-today' )?->nodeValue : null, $all, $rows->length === 1 ? trim( (string) preg_replace( '/\s+/u', ' ', $rows->item( 0 )->getElementsByTagName( 'th' )->item( 0 )->textContent ) ) : '' ];
};
$todayRows = [];
foreach( [ '2026-12-07' => 'mon', '2026-12-08' => 'tue', '2026-12-09' => 'wed', '2026-12-10' => 'thu', '2026-12-11' => 'fri', '2026-12-12' => 'sat', '2026-12-13' => 'sun' ] as $date => $day ) {
	[ $count, $attribute, , $header ] = $marked( $date. 'T12:00:00+01:00' );
	$first = in_array( $day, [ 'sat', 'sun' ], true ) === true ? $day : 'mon';
	$todayRows[$day] = $count === 1 && $attribute === 'true' && str_contains( $header, $word( '/feature/hours/word/today' ) ) && str_starts_with( $header, $word( '/feature/hours/day-short/'. $first ) );
}
check( 'exactly one row holds today, whichever day it is: the attribute aria-current and the word Today mark it, not the colour alone', array_filter( $todayRows ) === $todayRows && count( $todayRows ) === 7 );
check( 'a row that does not hold today carries neither', ( static function() use ( $marked ): bool {
	[ , , $rows ] = $marked( '2026-12-02T10:00:00+01:00' );
	$other = 0;
	foreach( $rows as [ $current, $today ] )
		if( $current === null && $today === null )
			$other++;
	return $other === 2 && count( $rows ) === 3;
} )() );

$line = static function( string $moment, string $call = '[hours-today]' ) use ( &$appData, $tree, $hold ): DOMXPath {
	$hold( $moment );
	return $tree( \Nino\Html::renderHtml( $appData, $call ) );
};
check( 'today as one line: the times of the day, closed="false"', ( static function() use ( $line ): bool {
	$page = $line( '2026-12-02T10:00:00+01:00' );
	return $page->query( '//p[contains(@class,"nino-hours-line")]' )->length === 1 && $page->query( '//p/@data-closed' )->item( 0 )->nodeValue === 'false'
		&& $page->query( '//p//time' )->length === 4;
} )() );
check( 'a closed day is said as closed, with no times', ( static function() use ( $line ): bool {
	$page = $line( '2026-12-06T10:00:00+01:00' );
	return $page->query( '//p/@data-closed' )->item( 0 )->nodeValue === 'true' && $page->query( '//p//time' )->length === 0 && $page->query( '//p//*[contains(@class,"nino-hours-closed")]' )->length === 1;
} )() );
check( 'without a location the first of the elements, with a location that one, and with the name in front on request', ( static function() use ( $line ): bool {
	$first = $line( '2026-12-07T19:00:00+01:00' );
	$night = $line( '2026-12-07T19:00:00+01:00', '[hours-today /locations/night name="1"]' );
	$none = $line( '2026-12-07T19:00:00+01:00', '[hours-today /locations/none]' );
	return $first->query( '//p//time' )->length === 4 && $first->query( '//p//*[contains(@class,"nino-hours-name")]' )->length === 0
		&& $night->query( '//p//time' )->length === 2 && $night->query( '//p//*[contains(@class,"nino-hours-name")]' )->length === 1 && $night->query( '//p//strong' )->item( 0 )->textContent === 'Night'
		&& $none->query( '//p' )->length === 0;
} )() );

check( 'a night belongs to the day it begins on: Monday 18-2 is open on Monday and the Tuesday after it is closed', ( static function() use ( $line ): bool {
	$monday = $line( '2026-12-07T19:00:00+01:00', '[hours-today /locations/night]' );
	$tuesday = $line( '2026-12-08T01:00:00+01:00', '[hours-today /locations/night]' );
	return $monday->query( '//p/@data-closed' )->item( 0 )->nodeValue === 'false' && $tuesday->query( '//p/@data-closed' )->item( 0 )->nodeValue === 'true'
		&& $monday->query( '//time[@datetime="02:00"]' )->length === 1;
} )() );
echo "\n";

echo "7. Closures\n";

$store( [
	'main'		=> [ 'name' => 'Main' ] + $weekdays,
	'branch'	=> [ 'name' => 'Branch' ] + $weekdays,
], [
	'christmas'	=> [ 'from' => '2026-12-24', 'until' => '2026-12-26', 'reason' => 'Christmas' ],
	'short'			=> [ 'from' => '2026-12-25', 'location' => '/locations/main', 'times' => '10-12', 'reason' => 'Short' ],
	'newyear'		=> [ 'from' => '2026-12-31', 'until' => '2027-01-02', 'reason' => 'New year' ],
	'leap'			=> [ 'from' => '2028-02-28', 'until' => '2028-03-01', 'reason' => 'Leap' ],
	'old'				=> [ 'from' => '2026-10-01', 'until' => '2026-10-05', 'reason' => 'Old' ],
] );
$listed = static function( string $moment, string $call = '[hours /locations/main]' ) use ( &$appData, $tree, $hold, $text ): array {
	$hold( $moment );
	$page = $tree( \Nino\Html::renderHtml( $appData, $call ) );
	$found = [];
	foreach( $page->query( '//dl[contains(@class,"nino-hours-closures")]/div' ) as $closure ) {
		$dates = $page->query( 'dt//time', $closure );
		$found[] = $dates->item( 0 )->attributes->getNamedItem( 'datetime' )->nodeValue. ( $dates->length > 1 ? '..'. $dates->item( 1 )->attributes->getNamedItem( 'datetime' )->nodeValue : '' );
	}
	return $found;
};
check( 'the closures to come are listed as the days they decide: a closure that another one covers on a day does not show on it, and a run of days is a range', $listed( '2026-12-20T10:00:00+01:00' ) === [ '2026-12-24', '2026-12-25', '2026-12-26', '2026-12-31..2027-01-02' ] );
check( 'a closure that is over is never listed: the day after its last day it is gone, on its last day it stays', $listed( '2026-12-27T10:00:00+01:00' ) === [ '2026-12-31..2027-01-02' ]
	&& $listed( '2026-12-26T23:59:00+01:00' ) === [ '2026-12-26', '2026-12-31..2027-01-02' ] && $listed( '2026-10-06T00:00:00+02:00' ) === [] );
check( 'a closure that is on is listed, and one that begins before today is named with its real first day', $listed( '2026-12-25T08:00:00+01:00' ) === [ '2026-12-25', '2026-12-26', '2026-12-31..2027-01-02' ] && $listed( '2027-01-02T08:00:00+01:00' ) === [ '2026-12-31..2027-01-02' ] );
check( 'the window is 60 days: a closure that begins on the 60th day ahead is listed and one that begins on the 61st is not', $listed( '2026-10-24T10:00:00+02:00' ) === []
	&& $listed( '2026-10-25T10:00:00+01:00' ) === [ '2026-12-24' ] && $listed( '2026-10-26T10:00:00+01:00' ) === [ '2026-12-24', '2026-12-25' ] );
check( 'ahead sets the window: the same edge for 59 days, and ahead="0" lists what is on today and nothing else', $listed( '2026-10-25T10:00:00+01:00', '[hours /locations/main ahead="59"]' ) === []
	&& $listed( '2026-12-24T10:00:00+01:00', '[hours /locations/main ahead="0"]' ) === [ '2026-12-24' ]
	&& $listed( '2026-12-24T10:00:00+01:00', '[hours /locations/main ahead="1"]' ) === [ '2026-12-24', '2026-12-25' ]
	&& $listed( '2026-12-20T10:00:00+01:00', '[hours /locations/main ahead="3"]' ) === [] && $listed( '2026-12-20T10:00:00+01:00', '[hours /locations/main ahead="4"]' ) === [ '2026-12-24' ]
	&& $listed( '2026-12-20T10:00:00+01:00', '[hours /locations/main ahead="5"]' ) === [ '2026-12-24', '2026-12-25' ] );
check( 'a closure across the new year is listed from the old year and still the next', $listed( '2026-12-30T10:00:00+01:00' ) === [ '2026-12-31..2027-01-02' ] && $listed( '2027-01-01T10:00:00+01:00' ) === [ '2026-12-31..2027-01-02' ] );
check( 'a closure of a location does not stand with another, and one for every location stands with all', $listed( '2026-12-20T10:00:00+01:00', '[hours /locations/branch]' ) === [ '2026-12-24..2026-12-26', '2026-12-31..2027-01-02' ] );
check( 'a closure over the leap day is listed, and the leap day is in it', $listed( '2028-02-27T10:00:00+01:00', '[hours /locations/main]' ) === [ '2028-02-28..2028-03-01' ]
	&& \Nino\Modules\Hours::effective( \Nino\Modules\Hours::week( [] ), \Nino\Modules\Hours::closures( $appData ), '/locations/main', '2028-02-29' )['closure'] === true );
check( 'a closure shows its times or the word for closed, and its reason', ( static function() use ( &$appData, $tree, $hold, $text, $word ): bool {
	$hold( '2026-12-20T10:00:00+01:00' );
	$items = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/main]' ) )->query( '//dl/div' );
	return $items->length === 4 && $items->item( 0 )->getElementsByTagName( 'time' )->length === 1
		&& str_contains( $text( $items->item( 0 ) ), $word( '/feature/hours/word/closed' ) ) && str_contains( $text( $items->item( 0 ) ), 'Christmas' )
		&& str_contains( $text( $items->item( 1 ) ), 'Short' ) && $items->item( 1 )->getElementsByTagName( 'time' )->length === 3 && str_contains( $text( $items->item( 1 ) ), $word( '/feature/hours/word/closed' ) ) === false;
} )() );
check( 'closures="0" draws no list', $listed( '2026-12-20T10:00:00+01:00', '[hours /locations/main closures="0"]' ) === [] );

$dayOf = static function( string $moment, string $call ) use ( $line ): array {
	$page = $line( $moment, $call );
	return [ $page->query( '//p/@data-closed' )->item( 0 )?->nodeValue, $page->query( '//p//*[contains(@class,"nino-hours-reason")]' )->item( 0 )?->textContent, $page->query( '//p//time' )->length ];
};
check( 'today with a closure counted in: closed with the reason', $dayOf( '2026-12-24T10:00:00+01:00', '[hours-today /locations/main]' ) === [ 'true', 'Christmas', 0 ] );
check( 'a closure that names the location beats one for every location, whatever their days', $dayOf( '2026-12-25T10:00:00+01:00', '[hours-today /locations/main]' ) === [ 'false', 'Short', 2 ]
	&& $dayOf( '2026-12-25T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'true', 'Christmas', 0 ] );
check( 'the day after the closure is the day of the week again, and a Sunday is closed without a reason', $dayOf( '2026-12-27T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'true', null, 0 ]
	&& $dayOf( '2026-12-28T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'false', null, 4 ] );
check( 'a closure in the new year and one on the leap day', $dayOf( '2027-01-01T10:00:00+01:00', '[hours-today /locations/main]' ) === [ 'true', 'New year', 0 ] && $dayOf( '2028-02-29T10:00:00+01:00', '[hours-today /locations/main]' ) === [ 'true', 'Leap', 0 ] );

\Nino\Elements::insertElement( $appData, '/closures/second', [ 'from' => '2026-12-24', 'until' => '2026-12-26', 'times' => '8-9', 'reason' => 'Second' ], 'de_DE' );
check( 'of two closures alike the one that begins later wins, and of two that begin together the one that was written later', $dayOf( '2026-12-24T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'false', 'Second', 2 ]
	&& $dayOf( '2026-12-25T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'false', 'Second', 2 ] );
\Nino\Elements::insertElement( $appData, '/closures/early', [ 'from' => '2026-12-20', 'until' => '2026-12-28', 'times' => '7-8', 'reason' => 'Early' ], 'de_DE' );
check( 'the first day decides before the order of writing: the one that begins on the 24th beats the one that was written later and begins on the 20th', $dayOf( '2026-12-24T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'false', 'Second', 2 ]
	&& $dayOf( '2026-12-21T10:00:00+01:00', '[hours-today /locations/branch]' ) === [ 'false', 'Early', 2 ] );
\Nino\Elements::deleteElement( $appData, '/closures/early' );
\Nino\Elements::deleteElement( $appData, '/closures/second' );
echo "\n";

echo "7b. One truth for each day\n";

$store( [ 'a' => [ 'name' => 'A', 'address' => "Street 1\n<b>12345</b> Town" ] + $weekdays, 'b' => [ 'name' => 'B' ] + $weekdays ], [
	'x'		=> [ 'from' => '2026-12-01', 'until' => '2026-12-05', 'reason' => 'X' ],
	'y'		=> [ 'from' => '2026-12-01', 'until' => '2026-12-03', 'times' => '7-8', 'reason' => 'Y' ],
	'e1'	=> [ 'from' => '2026-12-10', 'until' => '2026-12-15', 'reason' => 'E1' ],
	'e2'	=> [ 'from' => '2026-12-14', 'until' => '2026-12-18', 'location' => '/locations/a', 'times' => '9-10', 'reason' => 'E2' ],
	'all'	=> [ 'from' => '2026-12-24', 'until' => '2026-12-26', 'reason' => 'ALL' ],
	'a1'	=> [ 'from' => '2026-12-20', 'until' => '2026-12-31', 'location' => '/locations/a', 'times' => '8-9', 'reason' => 'A1' ],
	'z1'	=> [ 'from' => '2027-02-01', 'until' => '2027-02-02', 'reason' => 'Z' ],
	'z2'	=> [ 'from' => '2027-02-04', 'until' => '2027-02-05', 'reason' => 'Z' ],
	'p1'	=> [ 'from' => '2027-03-01', 'until' => '2027-03-02', 'reason' => 'P' ],
	'p2'	=> [ 'from' => '2027-03-03', 'until' => '2027-03-04', 'reason' => 'Q' ],
] );
check( 'two closures that are alike but have a day between them are two stretches, and two that follow one another and differ in the reason are two', $listed( '2027-01-30T10:00:00+01:00', '[hours /locations/a ahead="3"]' ) === [ '2027-02-01..2027-02-02' ]
	&& $listed( '2027-01-30T10:00:00+01:00', '[hours /locations/a ahead="6"]' ) === [ '2027-02-01..2027-02-02', '2027-02-04..2027-02-05' ]
	&& $listed( '2027-02-27T10:00:00+01:00', '[hours /locations/a ahead="6"]' ) === [ '2027-03-01..2027-03-02', '2027-03-03..2027-03-04' ] );
check( 'a closure that lies inside another, one that overlaps it at its edge and two that begin together are each decided by day and the result is listed as stretches', $listed( '2026-11-25T10:00:00+01:00', '[hours /locations/a]' )
	=== [ '2026-12-01..2026-12-03', '2026-12-04..2026-12-05', '2026-12-10..2026-12-13', '2026-12-14..2026-12-18', '2026-12-20..2026-12-31' ]
	&& $listed( '2026-11-25T10:00:00+01:00', '[hours /locations/b]' ) === [ '2026-12-01..2026-12-03', '2026-12-04..2026-12-05', '2026-12-10..2026-12-15', '2026-12-24..2026-12-26' ] );
check( '...and the day says the same: the location that has a closure of its own inside the days of one for all, the later of two that begin together', $dayOf( '2026-12-24T10:00:00+01:00', '[hours-today /locations/a]' ) === [ 'false', 'A1', 2 ]
	&& $dayOf( '2026-12-24T10:00:00+01:00', '[hours-today /locations/b]' ) === [ 'true', 'ALL', 0 ] && $dayOf( '2026-12-14T10:00:00+01:00', '[hours-today /locations/a]' ) === [ 'false', 'E2', 2 ]
	&& $dayOf( '2026-12-14T10:00:00+01:00', '[hours-today /locations/b]' ) === [ 'true', 'E1', 0 ] && $dayOf( '2026-12-02T10:00:00+01:00', '[hours-today /locations/a]' ) === [ 'false', 'Y', 2 ]
	&& $dayOf( '2026-12-04T10:00:00+01:00', '[hours-today /locations/a]' ) === [ 'true', 'X', 0 ] );
check( 'a stretch that goes beyond the days ahead is named with its real last day, and one that began before today with its real first day', $listed( '2026-12-20T10:00:00+01:00', '[hours /locations/a ahead="2"]' ) === [ '2026-12-20..2026-12-31' ]
	&& $listed( '2026-12-28T10:00:00+01:00', '[hours /locations/a ahead="0"]' ) === [ '2026-12-20..2026-12-31' ] );
check( 'the data holds the same stretches and no two periods of a location overlap', ( static function() use ( &$appData, $tree, $hold ): bool {
	foreach( [ 'a', 'b' ] as $slug ) {
		$hold( '2026-11-25T10:00:00+01:00' );
		unset( $appData['./hours/data'] );
		$data = json_decode( $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/'. $slug. ']' ) )->query( '//script' )->item( 0 )->textContent, true );
		$periods = [];
		foreach( $data['openingHoursSpecification'] as $specification )
			if( isset( $specification['validFrom'] ) === true )
				$periods[ $specification['validFrom']. '..'. $specification['validThrough'] ] = true;
		$periods = array_keys( $periods );
		sort( $periods );
		$last = '';
		foreach( $periods as $period ) {
			[ $from, $through ] = explode( '..', $period );
			if( $from <= $last || $through < $from )
				return false;
			$last = $through;
		}
		if( $slug === 'a' && $periods !== [ '2026-12-01..2026-12-03', '2026-12-04..2026-12-05', '2026-12-10..2026-12-13', '2026-12-14..2026-12-18', '2026-12-20..2026-12-31' ] )
			return false;
	}
	return true;
} )() );

$rowsOf = static function( string $moment, string $call ) use ( &$appData, $tree, $hold, $text ): array {
	$hold( $moment );
	$page = $tree( \Nino\Html::renderHtml( $appData, $call ) );
	$rows = [];
	foreach( $page->query( '//tbody/tr' ) as $row )
		$rows[] = [ $text( $row->getElementsByTagName( 'th' )->item( 0 ) ), $row->attributes->getNamedItem( 'data-deviation' )?->nodeValue, $row->attributes->getNamedItem( 'aria-current' )?->nodeValue, $row->getElementsByTagName( 'time' )->length, $page->query( 'td//*[contains(@class,"nino-hours-flag")]', $row )->length, str_contains( $text( $row ), 'A1' ) ];
	return $rows;
};
$week5 = $rowsOf( '2026-12-24T10:00:00+01:00', '[hours /locations/a]' );
$plain = $rowsOf( '2026-12-08T10:00:00+01:00', '[hours /locations/a]' );
check( 'on a day a closure decides, the row of today says what holds today - the times or the word for closed and the reason - with an attribute, the word Today and the word for a deviation, and the days of equal hours part around it',
	count( $week5 ) === 5 && $week5[1][1] === 'true' && $week5[1][2] === 'date' && $week5[1][3] === 2 && $week5[1][4] === 1 && $week5[1][5] === true && str_starts_with( $week5[1][0], $word( '/feature/hours/day-short/thu' ) )
	&& str_contains( $week5[1][0], $word( '/feature/hours/word/today' ) ) && $week5[0][1] === null && $week5[2][1] === null && $week5[0][3] === 4 && $week5[2][3] === 4 );
check( 'on a day no closure decides the week is as the days are', count( $plain ) === 3 && array_filter( $plain, static fn( array $row ): bool => $row[1] !== null || $row[4] !== 0 ) === [] && $plain[0][2] === 'date' );
check( 'the row of today and the line for today say the same on a day of a closure', ( static function() use ( $dayOf ): bool {
	$line = $dayOf( '2026-12-24T10:00:00+01:00', '[hours-today /locations/b]' );
	return $line === [ 'true', 'ALL', 0 ];
} )() );

$block = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/a]' ) );
$without = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/a address="0"]' ) );
check( 'the address stands under the name in the caption as an address element, its lines broken and its text escaped, and address="0" takes it away', $block->query( '//caption/address' )->length === 1
	&& $block->query( '//caption/address/br' )->length === 1 && str_contains( $block->query( '//caption/address' )->item( 0 )->textContent, '<b>12345</b> Town' ) && $block->query( '//caption/address//b' )->length === 0
	&& $without->query( '//address' )->length === 0 && $text( $without->query( '//caption/span[1]' )->item( 0 ) ) === 'A' );
check( 'the line for today has no address', $tree( \Nino\Html::renderHtml( $appData, '[hours-today /locations/a name="1"]' ) )->query( '//address' )->length === 0 );
check( 'the name of the line for today is set off from the word Today by a space in the markup, so that the text reads Name Today', str_contains( \Nino\Html::renderHtml( $appData, '[hours-today /locations/a name="1"]' ), '</strong> <span class="nino-hours-label">' )
	&& preg_match( '/ $/m', (string) file_get_contents( $dir. '/templates/hours-line-name.tpl' ) ) === 0 );
check( 'a location that has no hours but an address draws the address with its name', ( static function() use ( &$appData, $tree ): bool {
	\Nino\Elements::insertElement( $appData, '/locations/appointment', [ 'name' => 'By appointment', 'address' => 'Somewhere 1' ], 'de_DE' );
	$page = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/appointment]' ) );
	\Nino\Elements::deleteElement( $appData, '/locations/appointment' );
	return $page->query( '//table' )->length === 0 && $page->query( '//address' )->length === 1 && $page->query( '//strong' )->length === 1;
} )() );
echo "\n";

echo "7c. Stretches of any length\n";

$periodsOf = static function( string $moment, string $call, string $uri ) use ( &$appData, $tree, $hold ): array {
	$hold( $moment );
	unset( $appData['./hours/data'] );
	$data = json_decode( $tree( \Nino\Html::renderHtml( $appData, $call ) )->query( '//script' )->item( 0 )->textContent, true );
	$periods = [];
	foreach( $data['openingHoursSpecification'] as $specification )
		if( isset( $specification['validFrom'] ) === true )
			$periods[ $specification['validFrom']. '..'. $specification['validThrough'] ] = true;
	$periods = array_keys( $periods );
	sort( $periods );
	return $periods;
};
$store( [ 'a' => [ 'name' => 'A' ] + $weekdays ], [ 'long' => [ 'from' => '2020-01-01', 'until' => '2036-12-31', 'reason' => 'Long' ] ] );
check( 'a closure that is years long is named with its real first and last day in the list and in the data, however far they lie from today',
	$listed( '2026-12-01T10:00:00+01:00', '[hours /locations/a]' ) === [ '2020-01-01..2036-12-31' ] && $periodsOf( '2026-12-01T10:00:00+01:00', '[hours /locations/a]', 'a' ) === [ '2020-01-01..2036-12-31' ]
	&& $dayOf( '2036-12-31T10:00:00+01:00', '[hours-today /locations/a]' ) === [ 'true', 'Long', 0 ] );
$store( [ 'a' => [ 'name' => 'A' ] + $weekdays ], [ 'long' => [ 'from' => '2026-11-01', 'until' => '2099-12-31', 'reason' => 'Long' ] ] );
check( '...up to the year 2099', $listed( '2026-12-01T10:00:00+01:00', '[hours /locations/a]' ) === [ '2026-11-01..2099-12-31' ] && $periodsOf( '2026-12-01T10:00:00+01:00', '[hours /locations/a]', 'a' ) === [ '2026-11-01..2099-12-31' ] );
$store( [ 'a' => [ 'name' => 'A' ] + $weekdays, 'b' => [ 'name' => 'B' ] + $weekdays ], [
	'long'	=> [ 'from' => '2020-01-01', 'until' => '2036-12-31', 'reason' => 'Long' ],
	'mid'		=> [ 'from' => '2026-12-10', 'until' => '2026-12-12', 'location' => '/locations/a', 'times' => '9-10', 'reason' => 'Mid' ],
] );
check( 'a long closure that a shorter one of higher rank interrupts is three stretches with the right limits, in the list and in the data', $listed( '2026-12-01T10:00:00+01:00', '[hours /locations/a]' ) === [ '2020-01-01..2026-12-09', '2026-12-10..2026-12-12', '2026-12-13..2036-12-31' ]
	&& $periodsOf( '2026-12-01T10:00:00+01:00', '[hours /locations/a]', 'a' ) === [ '2020-01-01..2026-12-09', '2026-12-10..2026-12-12', '2026-12-13..2036-12-31' ]
	&& $listed( '2026-12-01T10:00:00+01:00', '[hours /locations/b]' ) === [ '2020-01-01..2036-12-31' ] );
$store( [ 'a' => [ 'name' => 'A' ] + $weekdays ], [
	'one'		=> [ 'from' => '2026-12-24', 'times' => '9-12', 'reason' => 'Holidays' ],
	'two'		=> [ 'from' => '2026-12-25', 'until' => '2026-12-26', 'reason' => 'Holidays' ],
] );
check( 'stretches part by the times as well as by the reason: 9-12 on the 24th and closed on the 25th and 26th, both for the same reason, are two, in the list and in the data',
	$listed( '2026-12-20T10:00:00+01:00', '[hours /locations/a]' ) === [ '2026-12-24', '2026-12-25..2026-12-26' ] && $periodsOf( '2026-12-20T10:00:00+01:00', '[hours /locations/a]', 'a' ) === [ '2026-12-24..2026-12-24', '2026-12-25..2026-12-26' ] );
echo "\n";

echo "8. The clock\n";

$store( [ 'main' => [ 'name' => 'Main' ] + $weekdays ], [ 'springday' => [ 'from' => '2026-03-29', 'reason' => 'Springday' ], 'autumnday' => [ 'from' => '2026-10-25', 'reason' => 'Autumnday' ] ] );
$reasonAt = static fn( string $moment ): string => (string) ( $dayOf( $moment, '[hours-today]' )[1] ?? '' );
check( 'the day that has 23 hours is one day: from its first to its last minute today is that date', $reasonAt( '2026-03-29T00:00:00+01:00' ) === 'Springday' && $reasonAt( '2026-03-29T01:59:59+01:00' ) === 'Springday'
	&& $reasonAt( '2026-03-29T03:00:00+02:00' ) === 'Springday' && $reasonAt( '2026-03-29T23:59:59+02:00' ) === 'Springday'
	&& $reasonAt( '2026-03-28T23:59:59+01:00' ) === '' && $reasonAt( '2026-03-30T00:00:00+02:00' ) === '' );
check( 'the day that has 25 hours is one day, and its second 02:30 is on it', $reasonAt( '2026-10-25T00:00:00+02:00' ) === 'Autumnday' && $reasonAt( '2026-10-25T02:30:00+02:00' ) === 'Autumnday'
	&& $reasonAt( '2026-10-25T02:30:00+01:00' ) === 'Autumnday' && $reasonAt( '2026-10-25T23:59:59+01:00' ) === 'Autumnday' && $reasonAt( '2026-10-24T23:59:59+02:00' ) === '' && $reasonAt( '2026-10-26T00:00:00+01:00' ) === '' );
check( 'the clock held is the clock of the request, and without it the clock of the server', ( static function() use ( &$appData ): bool {
	unset( $appData['./hours/now'] );
	return \Nino\Modules\Hours::now( $appData )->format( 'Y-m-d' ) === date( 'Y-m-d' ) && abs( \Nino\Modules\Hours::now( $appData )->getTimestamp() - time() ) < 3;
} )() );

check( 'the timezone of the setting counts today: one moment is two dates in two zones', ( static function() use ( &$appData, $dayOf ): bool {
	$zoneAt = static function( string $zone, string $moment ) use ( &$appData, $dayOf ): ?string {
		$saved = \Nino\Features::saveSettings( $appData, 'hours', [ 'timezone' => $zone ] );
		unset( $appData['./nino/features/all'] );
		return $saved === [] ? $dayOf( $moment, '[hours-today]' )[1] : 'refused';
	};
	$early = '2026-03-28T10:00:00+00:00';
	$late = '2026-03-29T05:00:00+00:00';
	$result = [ $zoneAt( 'server', $early ), $zoneAt( 'Pacific/Kiritimati', $early ), $zoneAt( 'America/Los_Angeles', $early ), $zoneAt( 'Europe/Berlin', $late ), $zoneAt( 'America/Los_Angeles', $late ), $zoneAt( 'server', $late ) ];
	$zoneAt( 'server', $late );
	return $result === [ null, 'Springday', null, 'Springday', null, 'Springday' ];
} )() );
check( 'a timezone that is none is refused by the setting', array_keys( \Nino\Features::saveSettings( $appData, 'hours', [ 'timezone' => 'Mars/Olympus' ] ) ) === [ 'timezone' ] );

// The page cache: kept until the midnight of the zone, never longer than it is kept anyway
$appData['/nino/cache/status'] = true;
$ttlAt = static function( string $moment, int $ttl = 3600, string $call = '[hours-today]' ) use ( &$appData, $hold ): int {
	$appData['/nino/cache/ttl'] = $ttl;
	$hold( $moment );
	\Nino\Html::renderHtml( $appData, $call );
	return $appData['/nino/cache/ttl'];
};
check( 'a page in the cache is kept until the midnight of the zone: at 23:30 for 1800 seconds, at 10:00 for the hour it is kept anyway', $ttlAt( '2026-12-02T23:30:00+01:00' ) === 1800
	&& $ttlAt( '2026-12-02T10:00:00+01:00' ) === 3600 && $ttlAt( '2026-12-02T23:59:59+01:00' ) === 1 && $ttlAt( '2026-12-02T23:30:00+01:00', 600 ) === 600 );
check( '...from either shortcode, and with the days of 23 and of 25 hours counted as they are', $ttlAt( '2026-12-02T23:30:00+01:00', 3600, '[hours]' ) === 1800
	&& $ttlAt( '2026-03-29T00:30:00+01:00', 86400 ) === 81000 && $ttlAt( '2026-10-25T00:30:00+02:00', 100000 ) === 88200 );
$appData['/nino/cache/status'] = false;
check( 'the lifetime is left as it is where the cache is off', $ttlAt( '2026-12-02T23:30:00+01:00' ) === 3600 );
$appData['/nino/cache/status'] = true;
check( 'a lifetime that is no positive number is the kernel\'s hour, so it is limited as the hour is', $ttlAt( '2026-12-02T23:30:00+01:00', 0 ) === 1800 && $ttlAt( '2026-12-02T23:30:00+01:00', -5 ) === 1800 );
$appData['/nino/cache/ttl'] = 'long';
$hold( '2026-12-02T23:30:00+01:00' );
\Nino\Html::renderHtml( $appData, '[hours-today]' );
check( '...and so is one that is no number at all', $appData['/nino/cache/ttl'] === 1800 );
$appData['/nino/cache/ttl'] = 100000.0;
$hold( '2026-12-02T10:00:00+01:00' );
\Nino\Html::renderHtml( $appData, '[hours-today]' );
check( '...and a lifetime the kernel does not take for a number is not replaced by one it takes', $appData['/nino/cache/ttl'] === 100000.0 );
$appData['/nino/cache/status'] = false;

// What the kernel's cache really stores: a page rendered at 23:30 expires at midnight, one rendered at 10:00 in the hour
$storedFor = static function( string $moment ) use ( $dir ): int {
	$cache = ninoSandbox( 'hours-cache' );
	$cache['/nino/modules'] = [ '\\Nino\\Modules\\Components', '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Cache' ];
	\Nino\AppData::writeContentData( $cache, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
	\Nino\Features::activate( $cache, 'hours' );
	\Nino\Html::init( $cache );
	\Nino\Modules::callModules( $cache, 'init' );
	\Nino\Elements::insertElement( $cache, '/locations/main', [ 'name' => 'Main', 'mon' => '9-12' ], 'de_DE' );
	$cache['/nino/cache/status'] = true;
	$cache['/nino/cache/ttl'] = 3600;
	$cache['/nino/http/routes']['GET://hours'] = [ 'uri' => '/hours', 'body' => '[hours-today]' ];
	$cache['./hours/now'] = $moment;
	$request = [ '/nino/http/request' => [ 'method' => 'GET', 'rawMethod' => 'GET', 'uri' => '/hours', 'query' => [] ],
		'/nino/http/response' => [ 'statusCode' => 200, 'uri' => 'hours', 'locale' => 'de_DE', 'header' => [], 'body' => \Nino\Html::renderHtml( $cache, '[hours-today]' ) ] ];
	\Nino\Modules\Cache::callbackOutput( $cache, $request );
	$entry = \Nino\Filesystem::getFileContent( $cache, '/data/cache/'. sha1( '/hours|de_DE' ). '.php', [] );
	return is_array( $entry ) === true ? (int) $entry['expires'] - time() : -1;
};
$late = $storedFor( '2026-12-02T23:30:00+01:00' );
$early = $storedFor( '2026-12-02T10:00:00+01:00' );
check( 'the page the cache stores at 23:30 expires in 1800 seconds and the one stored at 10:00 in the hour, whatever the page holds', abs( $late - 1800 ) <= 5 && abs( $early - 3600 ) <= 5 );
unset( $appData['/nino/cache/status'], $appData['/nino/cache/ttl'] );
echo "\n";

echo "9. Data for search engines\n";

$store( [
	'main'		=> [ 'name' => 'Main', 'address' => "Main Street 1\n12345 Town" ] + $weekdays,
	'night'		=> [ 'name' => 'Night', 'mon' => '18-2', 'tue' => '0-24' ],
], [
	'christmas'	=> [ 'from' => '2026-12-24', 'until' => '2026-12-26', 'reason' => 'Christmas' ],
	'short'			=> [ 'from' => '2026-12-31', 'location' => '/locations/main', 'times' => '10-12' ],
] );
$hold( '2026-12-20T10:00:00+01:00' );
unset( $appData['./hours/data'] );
$html = \Nino\Html::renderHtml( $appData, '[hours /locations/main]' );
$page = $tree( $html );
$scripts = $page->query( '//script[@type="application/ld+json"]' );
$data = json_decode( $scripts->length === 1 ? $scripts->item( 0 )->textContent : '', true );
check( 'a location has one block of data, which is valid json', $scripts->length === 1 && is_array( $data ) === true );
check( 'it is a Place with a name and the address in a PostalAddress, on the context of schema.org', ( $data['@context'] ?? '' ) === 'https://schema.org' && ( $data['@type'] ?? '' ) === 'Place' && ( $data['name'] ?? '' ) === 'Main'
	&& ( $data['address']['@type'] ?? '' ) === 'PostalAddress' && ( $data['address']['streetAddress'] ?? '' ) === 'Main Street 1, 12345 Town' );
$specifications = (array) ( $data['openingHoursSpecification'] ?? [] );
$valid = array_filter( $specifications, static fn( mixed $one ): bool => is_array( $one ) === true && ( $one['@type'] ?? '' ) === 'OpeningHoursSpecification'
	&& preg_match( '/^\d\d:\d\d$/D', (string) ( $one['opens'] ?? '' ) ) === 1 && preg_match( '/^\d\d:\d\d$/D', (string) ( $one['closes'] ?? '' ) ) === 1 );
check( 'every specification has the type, a time to open and one to close', $specifications !== [] && count( $valid ) === count( $specifications ) );
$weekly = array_values( array_filter( $specifications, static fn( array $one ): bool => isset( $one['validFrom'] ) === false ) );
check( 'the week is a specification for each time with the days that have it: Monday to Friday by schema.org\'s names, then Saturday', count( $weekly ) === 3
	&& $weekly[0]['dayOfWeek'] === array_map( static fn( string $day ): string => 'https://schema.org/'. $day, [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ] ) && $weekly[0]['opens'] === '09:00' && $weekly[0]['closes'] === '12:00'
	&& $weekly[1]['opens'] === '14:00' && $weekly[1]['closes'] === '18:00' && $weekly[2]['dayOfWeek'] === [ 'https://schema.org/Saturday' ] && $weekly[2]['opens'] === '10:00' );
$deviating = array_values( array_filter( $specifications, static fn( array $one ): bool => isset( $one['validFrom'] ) === true ) );
check( 'a closure that is closed has opens and closes at 00:00 for its days, one with times has them', count( $deviating ) === 2 && $deviating[0]['validFrom'] === '2026-12-24' && $deviating[0]['validThrough'] === '2026-12-26'
	&& $deviating[0]['opens'] === '00:00' && $deviating[0]['closes'] === '00:00' && $deviating[1]['validFrom'] === '2026-12-31' && $deviating[1]['validThrough'] === '2026-12-31' && $deviating[1]['opens'] === '10:00' && $deviating[1]['closes'] === '12:00' );
unset( $appData['./hours/data'] );
$night = json_decode( $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/night]' ) )->query( '//script' )->item( 0 )->textContent, true );
check( 'a night is closing at its end on the next day, the end of the day is 23:59 and a location with no address has none', ( $night['name'] ?? '' ) === 'Night' && isset( $night['address'] ) === false
	&& $night['openingHoursSpecification'][0]['opens'] === '18:00' && $night['openingHoursSpecification'][0]['closes'] === '02:00'
	&& $night['openingHoursSpecification'][1]['opens'] === '00:00' && $night['openingHoursSpecification'][1]['closes'] === '23:59' );

unset( $appData['./hours/data'] );
$page = $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/main][hours /locations/main][hours-today /locations/main][hours /locations/night][hours]' ) );
$names = array_map( static fn( DOMNode $script ): string => (string) ( json_decode( $script->textContent, true )['name'] ?? '' ), iterator_to_array( $page->query( '//script[@type="application/ld+json"]' ) ) );
check( 'once for a location on a page, however often it is drawn: a block for each of the two locations and no more, and none from the line for today', $names === [ 'Main', 'Night' ] );
unset( $appData['./hours/data'] );
check( 'the line for today brings none', $tree( \Nino\Html::renderHtml( $appData, '[hours-today /locations/main]' ) )->query( '//script' )->length === 0 );
$store( [ 'main' => [ 'name' => 'Main' ] ] );
unset( $appData['./hours/data'] );
check( 'a location with no hours and no closure brings none, and no table', $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/main]' ) )->query( '//script | //table' )->length === 0 );
echo "\n";

echo "10. Escaping\n";

$evil = '<img src=x onerror=alert(1)> & "quoted" \'single\' [[/project/company/general/name]] [hours] </script><script>alert(2)</script>';
$store( [ 'evil' => [ 'name' => $evil, 'address' => "<b>Street</b>\n\"1\" [[x]]", 'note' => "<i>note</i> [hours-today]\nline two", 'mon' => '9-12' ] ], [ 'bad' => [ 'from' => '2026-12-24', 'reason' => $evil ] ] );
unset( $appData['./hours/data'] );
$hold( '2026-12-20T10:00:00+01:00' );
$html = \Nino\Html::renderHtml( $appData, '[hours /locations/evil]' );
$page = $tree( $html );
check( 'the name of a location is text in the caption: its tags, quotes and brackets are what it says', $page->query( '//caption/span[1]' )->item( 0 )->textContent === $evil && $page->query( '//img' )->length === 0 );
check( 'the reason, the note and the address are text as well', $page->query( '//*[contains(@class,"nino-hours-reason")]' )->item( 0 )->textContent === $evil
	&& str_contains( $page->query( '//*[contains(@class,"nino-hours-note")]' )->item( 0 )->textContent, '<i>note</i> [hours-today]' ) && $page->query( '//i | //b' )->length === 0 );
check( 'no script runs but the data block, whose json decodes to the name as it was written', $page->query( '//script' )->length === 1 && ( json_decode( $page->query( '//script' )->item( 0 )->textContent, true )['name'] ?? '' ) === $evil
	&& ( json_decode( $page->query( '//script' )->item( 0 )->textContent, true )['address']['streetAddress'] ?? '' ) === '<b>Street</b>, "1" [[x]]' );
preg_match( '#<script type="application/ld\+json">([^<]*)</script>#', $html, $block );
preg_match_all( '/"(?:[^"\\\\]|\\\\.)*"/', $block[1] ?? '', $strings );
check( 'the block has no angle bracket, and no string in it holds a bracket that a fill or a shortcode could be read from', isset( $block[1] ) === true && count( $strings[0] ) > 10
	&& array_filter( $strings[0], static fn( string $string ): bool => str_contains( $string, '[' ) || str_contains( $string, ']' ) ) === [] && str_contains( $block[1], '\\u005b' ) === true );
check( 'the line for today escapes the name and the reason, too', ( static function() use ( &$appData, $tree, $evil ): bool {
	$appData['./hours/now'] = '2026-12-24T10:00:00+01:00';
	$page = $tree( \Nino\Html::renderHtml( $appData, '[hours-today name="1"]' ) );
	return $page->query( '//strong' )->item( 0 )->textContent === $evil && $page->query( '//*[contains(@class,"nino-hours-reason")]' )->item( 0 )->textContent === $evil && $page->query( '//img' )->length === 0;
} )() );
echo "\n";

echo "11. Nothing, many, loops\n";

$store( [] );
unset( $appData['./hours/data'] );
$warned = ninoWarnings();
check( 'without a location both shortcodes draw nothing at all - no frame, no warning', \Nino\Html::renderHtml( $appData, '[hours]' ) === '' && \Nino\Html::renderHtml( $appData, '[hours-today]' ) === '' && ninoWarnings() === [] );
$store( [ 'main' => [ 'name' => 'Main' ] + $weekdays, 'second' => [ 'name' => 'Second', 'mon' => '8-9' ], 'third' => [ 'name' => 'Third', 'sat' => '1-2' ] ] );
check( 'with a location that is not there both draw nothing', \Nino\Html::renderHtml( $appData, '[hours /locations/none]' ) === '' && \Nino\Html::renderHtml( $appData, '[hours-today /locations/none]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[hours /locations/../x]' ) === '' && \Nino\Html::renderHtml( $appData, '[hours /closures/x]' ) === '' );
$places = static fn( DOMXPath $page ): array => array_map( static fn( DOMNode $div ): string => $div->attributes->getNamedItem( 'data-location' )->nodeValue, iterator_to_array( $page->query( '//div[@data-location]' ) ) );
check( 'with several locations and none named, all of them one below the other in the order of the elements, each with its name', $places( $tree( \Nino\Html::renderHtml( $appData, '[hours]' ) ) ) === [ '/locations/main', '/locations/second', '/locations/third' ]
	&& $tree( \Nino\Html::renderHtml( $appData, '[hours]' ) )->query( '//caption' )->length === 3 );
$store( [ 'only' => [ 'name' => 'Only' ] + $weekdays ] );
check( 'with one location and none named, that one', $places( $tree( \Nino\Html::renderHtml( $appData, '[hours]' ) ) ) === [ '/locations/only' ] );
$store( [ 'main' => [ 'name' => 'Main' ] + $weekdays, 'second' => [ 'name' => 'Second', 'mon' => '8-9' ], 'third' => [ 'name' => 'Third', 'sat' => '1-2' ] ] );
check( 'name="0" draws no caption and closures="0" no list', $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/main name="0"]' ) )->query( '//caption' )->length === 0
	&& $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/main name="0"]' ) )->query( '//table' )->length === 1 );
check( 'a class of the call is on the block', $tree( \Nino\Html::renderHtml( $appData, '[hours /locations/main class="wide"]' ) )->query( '//div[contains(concat(" ",@class," ")," nino-hours ") and contains(concat(" ",@class," ")," wide ")]' )->length === 1 );

check( 'in a loop of [elements] the block of each element through its uri', $places( $tree( \Nino\Html::renderHtml( $appData, '[elements /locations][hours [[.uri]] name="0"][/elements]' ) ) ) === [ '/locations/main', '/locations/second', '/locations/third' ] );
check( 'in the cell of a stack the first argument .uri is the element of the cell', $places( $tree( \Nino\Html::renderHtml( $appData, '[stack /locations][hours .uri][/stack]' ) ) ) === [ '/locations/main', '/locations/second', '/locations/third' ] );
check( 'in the cell of a stack with no argument at all the element of the cell as well, and .slug is its name', $places( $tree( \Nino\Html::renderHtml( $appData, '[stack /locations][hours][/stack]' ) ) ) === [ '/locations/main', '/locations/second', '/locations/third' ]
	&& $places( $tree( \Nino\Html::renderHtml( $appData, '[stack /locations][hours .slug][/stack]' ) ) ) === [ '/locations/main', '/locations/second', '/locations/third' ] );
check( '...with its fields beside it, in a card', ( static function() use ( &$appData, $tree ): bool {
	$page = $tree( \Nino\Html::renderHtml( $appData, '[stack /locations][card][card-title name][hours name="0"][/card][/stack]' ) );
	return $page->query( '//div[@data-location]' )->length === 3 && $page->query( '//div[@data-location="/locations/second"]//time' )->length === 2;
} )() );
check( 'the line for today in the cell of a stack is the line of its element', ( static function() use ( &$appData, $tree, $hold ): bool {
	$hold( '2026-12-07T08:30:00+01:00' );
	$page = $tree( \Nino\Html::renderHtml( $appData, '[stack /locations][hours-today .uri name="1"][/stack]' ) );
	return $page->query( '//p' )->length === 3 && $page->query( '(//p)[1]//time' )->length === 4 && $page->query( '(//p)[2]//time' )->length === 2 && $page->query( '(//p)[3]/@data-closed' )->item( 0 )->nodeValue === 'true';
} )() );
check( 'the data of a location stands once on a page that loops and then names it', ( static function() use ( &$appData, $tree ): bool {
	unset( $appData['./hours/data'] );
	$page = $tree( \Nino\Html::renderHtml( $appData, '[stack /locations][hours .uri][/stack][hours /locations/main]' ) );
	return $page->query( '//script' )->length === 3;
} )() );
echo "\n";

echo "12. Switched off\n";

$off = ninoSandbox( 'hours-off' );
$off['/nino/modules'] = [ '\\Nino\\Modules\\Components', '\\Nino\\Modules\\Elements' ];
\Nino\AppData::writeContentData( $off, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
\Nino\Html::init( $off );
\Nino\Modules::callModules( $off, 'init' );
check( 'a project without the feature leaves the shortcodes as they were written', \Nino\Html::renderHtml( $off, '[hours /locations/main][hours-today]' ) === '[hours /locations/main][hours-today]' );
check( 'deactivation takes the module off and keeps the types and their elements', ( static function() use ( &$appData, $typeFile ): bool {
	\Nino\Features::deactivate( $appData, 'hours' );
	return in_array( '\\Nino\\Modules\\Hours', (array) ( \Nino\AppData::stored( $appData, '/nino/modules' ) ?? [] ), true ) === false && isset( $typeFile( 'locations' )['*']['main'] ) === true && isset( $typeFile( 'closures' )['model'] ) === true;
} )() );

date_default_timezone_set( $processZone );
ninoDone( $appData );
