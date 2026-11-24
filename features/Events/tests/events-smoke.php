<?php
declare(strict_types=1);

/**
 *	Nino features
 *	events-smoke.php	The test of the feature Events: the manifest and the activation with the element type and the words
 *										of its unit, the time of a date (what a moment is, what is upcoming and what is past, against a clock
 *										the test sets), the component events with its scope, order, limit and query and what a visitor sees
 *										of it, the chip event-when in both languages, the links event-ics and event-feed, the feed and the
 *										file of one date (RFC 5545: lines, folding, escaping, UTC - the bytes of the files are held to those the
 *										Nino 1 feature wrote for the same dates), the tokens of the feed links, the hook of Members that
 *										removes an account, the standard part and the demo of the group, and the deactivation.
 *
 *										The feature takes Members as an option, and that is the second half of the run: a process of its own
 *										(this file again, with NINO_EVENTS_FEATURES naming a directory of features that holds a stand-in of
 *										Members) asks the feature with the Members feature there but off, which is the case where the class
 *										exists and must not be asked, and then on.
 *
 *										The clock is the key Events::CLOCK of $appData, a number of seconds, and the timezone of the site is
 *										php's, which this test sets to Europe/Berlin (UTC+1 in the winter the clock stands in), so that the
 *										conversion to UTC is seen to happen.
 *
 *	Usage: php features/Events/tests/events-smoke.php
 *	       NINO_ROOT=../nino php features/Events/tests/events-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
// The second half of the run names the directory of features it runs in; the first half runs in the one the feature stands in
$stand = (string) getenv( 'NINO_EVENTS_FEATURES' );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $stand !== '' ? $stand : dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

date_default_timezone_set( 'Europe/Berlin' );

$dir = dirname( __DIR__ );

/**
 *	A project of the test: the sandbox with the facts of the website the links and the feed are built on, the feature and Basis
 *	switched on, the register read as \Nino\init() reads it, and the clock at noon of a day in the winter
 *
 *	@param		string		$name
 *
 *	@return 	array											$appData
 */
function eventsBoot( string $name ): array {

	global $root;

	$appData = ninoSandbox( $name );
	$appData['./nino/filesystem/path'] = $root;
	$appData['/nino/dir'] = '';
	\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club', '[[/project/company/general/name]]' => 'The Club' ], '*' );

	if( \Nino\Features::activate( $appData, 'events' ) !== true )
		throw new \RuntimeException( 'the feature cannot be switched on' );

	eventsReload( $appData );
	eventsClock( $appData, '2026-12-10 12:00' );

	return $appData;
}

/**
 *	What the kernel reads of the features at the start of a request: the modules that are on, the register of the components, the
 *	modules' init() and the standard parts
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function eventsReload( array &$appData ): void {

	$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
	unset( $appData['./nino/features/all'], $appData['./nino/callbacks'] );
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );
	ninoWarnings();
}

/**
 *	The clock of the site, set to a wall-clock time of the site's timezone
 *
 *	@param		array 		&$appData
 *	@param		string		$local				eg. '2026-12-10 12:00'
 *
 *	@return 	void
 */
function eventsClock( array &$appData, string $local ): void {

	$appData[\Nino\Modules\Events::CLOCK] = ( new DateTimeImmutable( $local, new DateTimeZone( date_default_timezone_get() ) ) )->getTimestamp();
}

/**
 *	A request of the site, answered by the routes and the callbacks that are registered. Nothing is rendered
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *
 *	@return 	array										[ status, Content-Type, Cache-Control, Content-Disposition, body ]
 */
function eventsServe( array &$appData, string $uri ): array {

	$request = ninoVisit( $appData, $uri );
	\Nino\Http::response( $appData, $request );
	$response = $request['/nino/http/response'];

	return [ $response['statusCode'], (string) ( $response['header']['Content-Type'] ?? '' ), (string) ( $response['header']['Cache-Control'] ?? '' ), (string) ( $response['header']['Content-Disposition'] ?? '' ), $response['body'] ?? '' ];
}

/**
 *	The elements of a piece of html by xpath
 *
 *	@param		string		$html
 *	@param		string		$query
 *
 *	@return 	array										The DOMElements
 */
function eventsFind( string $html, string $query ): array {

	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8"><body>'. $html. '</body>' );
	libxml_clear_errors();

	return iterator_to_array( ( new DOMXPath( $document ) )->query( $query ) ?: [] );
}

/**
 *	A piece of html+ drawn the way a page of the site draws it
 *
 *	@param		array 		&$appData
 *	@param		string		$source
 *
 *	@return 	string
 */
function eventsRender( array &$appData, string $source ): string {

	return \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-events' ] + \Nino\Html::context( $appData ) );
}

/**
 *	The names of the dates a list draws, in the order it draws them: the file each one links, [event-ics .slug]
 *
 *	@param		array 		&$appData
 *	@param		string		$attributes		eg. 'scope="past" limit="2"'
 *
 *	@return 	array										Slugs
 */
function eventsList( array &$appData, string $attributes = '' ): array {

	$links = eventsFind( eventsRender( $appData, '[events '. $attributes. '][event-ics .slug][/events]' ), '//a[contains(concat(" ", normalize-space(@class), " "), " event-ics ")]' );

	return array_map( static fn( DOMElement $link ): string => (string) preg_replace( '~^/\.events/(.*)\.ics$~', '$1', $link->getAttribute( 'href' ) ), $links );
}

/**
 *	A calendar file with its folded lines joined again
 *
 *	@param		string		$ics
 *
 *	@return 	array										The content lines
 */
function eventsLines( string $ics ): array {

	return explode( "\r\n", rtrim( str_replace( "\r\n ", '', $ics ), "\r\n" ) );
}

/**
 *	The lines of one VEVENT of a calendar file, by the slug in its UID
 *
 *	@param		string		$ics
 *	@param		string		$slug
 *
 *	@return 	array										The unfolded lines of that event; [] for none
 */
function eventsEvent( string $ics, string $slug ): array {

	$found = [];
	$inside = [];

	foreach( eventsLines( $ics ) as $line ) {

		if( $line === 'BEGIN:VEVENT' )
			$inside = [ $line ];
		else if( $inside !== [] )
			$inside[] = $line;

		if( $line === 'END:VEVENT' ) {
			if( in_array( 'UID:'. $slug. '@example.org', $inside, true ) === true )
				$found = $inside;
			$inside = [];
		}
	}

	return $found;
}

/**
 *	The slugs of the events a calendar file carries, in the order they stand
 *
 *	@param		string		$ics
 *
 *	@return 	array
 */
function eventsUids( string $ics ): array {

	return array_map( static fn( string $line ): string => substr( $line, 4, (int) strpos( $line, '@' ) - 4 ), array_values( array_filter( eventsLines( $ics ), static fn( string $line ): bool => str_starts_with( $line, 'UID:' ) ) ) );
}

/**
 *	The data file of the feature as it is on disk, read fresh
 *
 *	@param		array 		&$appData
 *
 *	@return 	array
 */
function eventsData( array &$appData ): array {

	$state = \Nino\Filesystem::getFileContent( $appData, '/data/events.php', [] );

	return is_array( $state ) === true ? $state : [];
}

/**
 *	The chip a date is drawn as in the list that holds it: [event-when .start end="end"]
 *
 *	@param		array 		&$appData
 *	@param		string		$start				The start the date is found by
 *	@param		string		$call					The attributes of the call
 *
 *	@return 	DOMElement|null
 */
function eventsChip( array &$appData, string $start, string $call = '.start end="end"' ): ?DOMElement {

	return eventsFind( eventsRender( $appData, '[events scope="all" query="start='. $start. '"][event-when '. $call. '][/events]' ), '//time' )[0] ?? null;
}

/**
 *	Whether the classes of an element hold one
 *
 *	@param		DOMElement|null	$element
 *	@param		string		$class
 *
 *	@return 	bool
 */
function eventsHas( ?DOMElement $element, string $class ): bool {

	return $element !== null && in_array( $class, preg_split( '/\s+/', $element->getAttribute( 'class' ) ) ?: [], true );
}

/**
 *	The dates of the tests, around noon of the 10th of December 2026: start, end, group, place and the title in German. 'far' lies
 *	more than a year ahead, 'broken' has no start that is one
 *
 *	@return 	array
 */
function eventsSeed(): array {

	$long = str_repeat( 'ä', 60 ). ' und lang genug, dass die Zeile gefaltet werden muss';

	return [
		'past-old'			=> [ '2026-11-01T10:00', '2026-11-01T12:00', '', 'Halle', 'Alt' ],
		'past-recent'		=> [ '2026-12-09T09:00', '', '', 'Halle', 'Gestern' ],
		'ended-today'		=> [ '2026-12-10T08:00', '2026-12-10T11:00', '', '', 'Frühstück' ],
		'just-over'			=> [ '2026-12-10T11:59', '', '', '', 'Gerade vorbei' ],
		'running'				=> [ '2026-12-10T09:00', '2026-12-10T18:00', '', 'Halle', 'Läuft' ],
		'at-now'				=> [ '2026-12-10T12:00', '', '', '', 'Jetzt' ],
		'soon'					=> [ '2026-12-11T10:00', '', '', 'Halle', 'Bald' ],
		'board-meeting'	=> [ '2026-12-15T19:00', '', 'Board', '', 'Vorstand' ],
		'staff-party'		=> [ '2026-12-16T19:00', '', 'staff', '', 'Personal' ],
		'strange'				=> [ '2026-12-17T19:00', '', '***', '', 'Seltsam' ],
		'all-day'				=> [ '2026-12-20T00:00', '', '', '', 'Ganzer Tag' ],
		'later'					=> [ '2026-12-24T18:00', '2026-12-24T20:00', '', 'Halle 1, Hof', 'Feier, groß; mit \\ Gästen' ],
		'two-day'				=> [ '2026-12-27T10:00', '2026-12-28T12:00', '', '', $long ],
		'far'						=> [ '2028-01-01T10:00', '', '', '', 'Fern' ],
		'broken'				=> [ 'not a date', '', '', '', 'Kaputt' ],
	];
}

/**
 *	The dates of eventsSeed() as elements, German, with the English words of one and the text and the link of two
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function eventsPlant( array &$appData ): void {

	foreach( eventsSeed() as $slug => [ $start, $end, $group, $place, $title ] )
		\Nino\Elements::insertElement( $appData, '/events/'. $slug, [ 'title' => $title, 'start' => $start, 'end' => $end, 'group' => $group, 'place' => $place ], 'de_DE' );

	\Nino\Elements::updateElement( $appData, '/events/later', [ 'text' => '<p>Erste Zeile</p><p>Zweite, Zeile; ok &amp; <strong>gut</strong></p><ul><li>Punkt</li></ul>', 'link' => 'https://example.org/feier?a=1&b=2' ], 'de_DE' );
	\Nino\Elements::updateElement( $appData, '/events/soon', [ 'title' => 'Soon', 'place' => 'The hall' ], 'en_US' );
	\Nino\Elements::updateElement( $appData, '/events/running', [ 'link' => '/programm' ], 'de_DE' );
	unset( $appData['./nino/elements/cache'] );
}

/**
 *	Whether the bytes of a calendar file are those the Nino 1 feature wrote for the same dates: the sha256 of each is the one the
 *	table holds. The table is the answer the source gave under its own kernel, the one thing in this test no code of this feature made
 *
 *	@param		string		$label
 *	@param		string		$body
 *	@param		array 		$table
 *
 *	@return 	bool
 */
function eventsSame( string $label, string $body, array $table ): bool {

	return isset( $table[$label] ) === true && hash( 'sha256', $body ) === $table[$label];
}

// What the Nino 1 feature wrote, by file: the feed of a visitor in each language, the files of the dates, the feed as the settings
// and the zone move it, the files of dates that carry what a hand typed, and the files with an organizer
const EVENTS_BYTES = [
	'feed.visitor.de_DE' => '8a1773b9b138d7940179f7c5ea3e4b0f8c902884e2e4500cf5252885b8819eb7',
	'feed.visitor.en_US' => '52b68084261ee099924dd0dd1c87d99d4e3217abf4d5b8489a0bd0bcc74695f4',
	'single.past-old' => '595894c57059ddc0716dcdb16eeafbfe85987cfb6214f26bd1b9052d9fa1b104',
	'single.past-recent' => '614020d9dbda620dafe184d67537168608967687b745b282c1aff6b0112c5429',
	'single.ended-today' => 'becc317eea0496e56d2a5b31ea89bde62f43cc49a981bdf1babc4c0ec5574429',
	'single.just-over' => 'e18240e9cee4142fce179e05eeef55e4903cce6b15a46eeb3f5207b67568cf3c',
	'single.running' => '43eb08c20151ba1f74926d49873366eef0fb01adb9dc485d9fcd08e6f8bcdce9',
	'single.at-now' => '97e89d0ddb88248793f2c27318145d59a06fcb20f59b251501f0811934f943f1',
	'single.soon' => '3b59b2668077e247e0cc5d91b3036dc2fa8d6db8b6016c1e95acdf170bb28ca8',
	'single.all-day' => 'da171b4b2b03badbd29f7be3340d22187cf2937caee58b9e375a679f7a7d60af',
	'single.later' => '9edd31ae1701ed2bdfada1587c2a146290db26891b28c3f912e5d9a6a6b45f68',
	'single.two-day' => '9694cabcdb0b35a74e3f2dd12a6e4423f65765e25c7d93247dc5d9c6280d88a8',
	'single.far' => '5984e865fc6deeceea8b1630e6515d63d1f79aaa85ef3bcd25515e6ea4183165',
	'single.soon.en_US' => '9180f5df0627fbe7e1717a72c5625772c5b71a7141a3c76d4a0f97c6d568d9d9',
	'feed.window' => 'bcfab9868070cb4d03c556812aa9aacf2230b6870152c3ae643d2c891e9157a1',
	'feed.utc-berlin' => '8a1773b9b138d7940179f7c5ea3e4b0f8c902884e2e4500cf5252885b8819eb7',
	'single.later.utc-berlin' => '9edd31ae1701ed2bdfada1587c2a146290db26891b28c3f912e5d9a6a6b45f68',
	'feed.utc-server' => 'd62352a0999afa17ff0a53e0117acfcf8942e2340fc91c078142fbbe81495df2',
	'single.js-link' => 'b654eaaf87812f809686c55229275448b0481b9f172e38dcc81f0f7bbca3c365',
	'single.rel-link' => '1c5c7876bcd638ed6bcafb75a525164bbff09da8836b621d112618f8762ff170',
	'single.data-link' => '4599b1ed8c92d7c99ff1a7f53c93c6a28a13d8ae089c19f6849b0bdb0c3e2ad3',
	'single.mail-link' => 'b9251ccee99290a7471344a3581afadaab54a8d6846e60d7625f3ca91dd5c9eb',
	'single.good-link' => '2dda25473cbfdabec44206fb71ae6fc221b48861501008e440feba2a3a4705fc',
	'feed.organizer' => '00516b4a3b8e2509ef3cbab71320a1ca64aca4b546dc881156c5c8b053c84895',
	'single.later.organizer' => '73630fc2cc42548126812a8cdfd79bbc9896b96fa739c37a7a986d1549e66306',
	'single.soon.organizer' => '6d500d0fb2d4e986e1abba9d9b0db815fc03cdd6a382bd726f8e33cb8eafd4ca',
];

// The second half of the run is a process of its own
if( $stand !== '' ) {
	require __DIR__. '/events-members.php';
	return;
}

// --- The feature - manifest and activation ---------------------------------------

echo "The feature - manifest and activation\n";

$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "events", for this kernel and without a warning', is_array( $manifest ) === true && $manifest['key'] === 'events' && ninoWarnings() === []
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true);
check( '...class, version and category: \Nino\Modules\Events, 2.0.0, content, no maturity, no extension needed', $manifest['module'] === '\\Nino\\Modules\\Events' && $manifest['version'] === '2.0.0'
	&& $manifest['category'] === 'content' && $manifest['maturity'] === '' && $manifest['php'] === [ 'ext' => [] ] );
check( '...it names itself in both interface languages', \Nino\Features::localized( $manifest['name'], 'de_DE' ) === 'Termine' && \Nino\Features::localized( $manifest['name'], 'en_US' ) === 'Events'
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( '...the floor is 2.0: a kernel of the 1.x line is refused and the 2.x ones are not', \Nino\Features::satisfies( $manifest['nino'], '1.99.0' ) === false && \Nino\Features::satisfies( $manifest['nino'], '2.0.0' ) === true && \Nino\Features::satisfies( $manifest['nino'], '2.7.1' ) === true );
check( '...it requires Basis, whose columns hold the fields of a date, and not Members, which is optional; it owns the file of the feed tokens and brings no stylesheet and no script',
	$manifest['requires'] === [ 'basis' ] && $manifest['data'] === [ '/data/events.php' ] && $manifest['assets'] === [] && glob( $dir. '/assets/*' ) === [] );
check( '...the settings are ahead, past, feed, name and timezone, each with its type, its default and its bounds', array_keys( $manifest['settings'] ) === [ 'ahead', 'past', 'feed', 'name', 'timezone' ]
	&& $manifest['settings']['ahead']['type'] === 'int' && $manifest['settings']['ahead']['min'] === 1 && $manifest['settings']['ahead']['max'] === 730 && $manifest['settings']['ahead']['unit'] === 'days' && $manifest['settings']['ahead']['default'] === 365
	&& $manifest['settings']['past']['type'] === 'int' && $manifest['settings']['past']['min'] === 0 && $manifest['settings']['past']['max'] === 3650 && $manifest['settings']['past']['unit'] === 'days' && $manifest['settings']['past']['default'] === 0
	&& $manifest['settings']['feed']['type'] === 'bool' && $manifest['settings']['feed']['default'] === true
	&& $manifest['settings']['name']['type'] === 'string' && $manifest['settings']['name']['maxlength'] === 80 && $manifest['settings']['name']['default'] === ''
	&& array_keys( $manifest['settings']['timezone']['options'] ) === array_merge( [ 'server' ], DateTimeZone::listIdentifiers() ) && $manifest['settings']['timezone']['required'] === true && $manifest['settings']['timezone']['default'] === 'server' && $manifest['settings']['timezone']['type'] === 'select' );
check( '...the manual answers all six sections in both languages', array_keys( $manifest['manual'] ) === \Nino\Features::MANUAL_SECTIONS
	&& array_filter( $manifest['manual'], static fn( array $section ): bool => array_filter( $section, static fn( mixed $line ): bool => is_array( $line ) === false || ( $line['en_US'] ?? '' ) === '' || ( $line['de_DE'] ?? '' ) === '' ) !== [] ) === [] );
check( '...the feature has no panel, no section of the privacy policy and no template: the unit is the element type and the words', is_file( $dir. '/Admin/Admin.php' ) === false
	&& is_file( $dir. '/install/elements/privacy.php' ) === false && ( include $dir. '/install/manifest.php' ) === [ 'elementTypes' => [ 'events.php' ] ] && glob( $dir. '/install/templates/*' ) === [] );

$appData = ninoSandbox( 'events' );
$appData['./nino/filesystem/path'] = $root;
$appData['/nino/dir'] = '';
\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club', '[[/project/company/general/name]]' => 'The Club' ], '*' );
check( 'the feature and Basis are switched on', \Nino\Features::activate( $appData, 'events' ) === true && ( \Nino\Features::get( $appData, 'basis' )['active'] ?? false ) === true );
check( '...the version is recorded and the class listed', ( \Nino\Features::get( $appData, 'events' )['installed'] ?? '' ) === '2.0.0' && in_array( '\\Nino\\Modules\\Events', (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] ), true ) === true );

$model = \Nino\Elements::getElementModel( $appData, '/events' );
check( '...the element type /events lands with the seven fields', \Nino\Filesystem::fileExists( $appData, '/elements/events.php' ) === true && array_keys( $model ) === [ 'title', 'start', 'end', 'place', 'text', 'link', 'group' ] );
check( '...a start that is a date and a time and is required, an end that is the same and is not, and a title, a place and a text per language', ( $model['start']['type'] ?? '' ) === 'datetime' && ( $model['start']['required'] ?? false ) === true
	&& ( $model['end']['type'] ?? '' ) === 'datetime' && ( $model['end']['required'] ?? false ) === false && ( $model['title']['required'] ?? false ) === true
	&& ( $model['title']['locale'] ?? false ) === true && ( $model['place']['locale'] ?? false ) === true && ( $model['text']['locale'] ?? false ) === true
	&& ( $model['start']['locale'] ?? false ) === false && ( $model['end']['locale'] ?? false ) === false && ( $model['group']['locale'] ?? false ) === false && ( $model['link']['locale'] ?? false ) === false );
check( '...the text has paragraphs and lists, the link and the group are plain strings', ( $model['text']['html'] ?? false ) === true && ( $model['text']['blocks'] ?? false ) === true && ( $model['link']['type'] ?? '' ) === 'string' && ( $model['group']['type'] ?? '' ) === 'string' );

foreach( [ 'en_US' => 'Today', 'de_DE' => 'Heute' ] as $locale => $word )
	check( '...and the words of the unit are in the Text panel in '. $locale, ( \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] )['[[/feature/events/when/today]]'] ?? '' ) === $word );

$words = array_keys( (array) include $dir. '/install/text/en_US.php' );
check( '...the same keys in both languages, each of the grammar /feature/events/<part>/<name>', $words === array_keys( (array) include $dir. '/install/text/de_DE.php' ) && count( $words ) === 11
	&& array_filter( $words, static fn( string $key ): bool => preg_match( '~^\[\[/feature/events/[a-z]+(?:-[a-z]+)*/[a-z]+(?:-[a-z]+)*\]\]$~', $key ) !== 1 ) === [] );

// The unit is applied add-only: a type the project changed stays, a word it wrote stays
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/feature/events/when/today]]' => 'Tonight' ] + (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) );
$typeFile = \Nino\Filesystem::path( $appData, '/elements/events.php' );
file_put_contents( $typeFile, (string) file_get_contents( $typeFile ). "\n// changed by the project\n" );
check( 'activating again leaves the type and the words of the project as they are', \Nino\Features::activate( $appData, 'events' ) === true && str_contains( (string) file_get_contents( $typeFile ), '// changed by the project' ) === true
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/events/when/today]]'] ?? '' ) === 'Tonight' );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/feature/events/when/today]]' => 'Today' ] + (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) );

eventsReload( $appData );
eventsClock( $appData, '2026-12-10 12:00' );

// --- The components in their folders --------------------------------------------------

echo "\nThe four components\n";

$components = \Nino\Components::components( $appData );
$group = \Nino\Components::groups( $appData )['events'] ?? [];
$mine = array_keys( array_filter( $components, static fn( array $schema ): bool => ( $schema['group'] ?? '' ) === 'events' ) );
sort( $mine );

check( 'the folders are read without a reason or a warning, and the register holds the four components, in one group of this feature', \Nino\Features::componentProblems( $appData, 'events' ) === [] && ninoWarnings() === []
	&& $mine === [ 'event-feed', 'event-ics', 'event-when', 'events' ] && ( $group['feature'] ?? '' ) === 'events' && ( $group['part'] ?? false ) === true && array_keys( $group['knobs'] ?? [] ) === [ 'event-when.space', 'event-when.radius' ] );
check( '...each is labelled in both languages and names an icon of the table in its file, which the register would otherwise replace by the neutral one', array_filter( $mine, static fn( string $name ): bool => isset( $components[$name]['label']['de_DE'], $components[$name]['label']['en_US'] ) === false || in_array( ( include $dir. '/components/'. $name. '/component.php' )['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& array_filter( $mine, static fn( string $name ): bool => $components[$name]['icon'] === \Nino\Components::NEUTRAL_ICON ) === [] );
check( '...events is a loop that draws what it holds: through, with nodes as its children, a scope of three words and an order of two', ( $components['events']['through'] ?? false ) === true && ( $components['events']['children'] ?? '' ) === 'nodes'
	&& array_keys( $components['events']['attributes'] ) === [ 'scope', 'order', 'limit', 'offset', 'query' ] && $components['events']['attributes']['scope']['options'] === [ 'upcoming', 'past', 'all' ] && $components['events']['attributes']['scope']['default'] === 'upcoming'
	&& $components['events']['attributes']['order']['options'] === [ 'asc', 'desc' ] && $components['events']['attributes']['order']['default'] === 'asc' );
check( '...event-when: a text source, the field of the end and what to show, in the places of a flow', $components['event-when']['source'] === 'text' && array_keys( $components['event-when']['attributes'] ) === [ 'end', 'show' ] && $components['event-when']['attributes']['show']['options'] === [ 'datetime', 'date', 'time' ]
	&& $components['event-when']['attributes']['show']['default'] === 'datetime' && $components['event-when']['attributes']['end']['default'] === '' && $components['event-when']['in'] === [ 'flow' ] );
check( '...event-ics has a text source and no attribute, event-feed has no source and none either', $components['event-ics']['source'] === 'text' && $components['event-ics']['attributes'] === [] && $components['event-feed']['source'] === 'none' && $components['event-feed']['attributes'] === [] );
check( '...a tool offers the chip and the two links in a column of Basis, and a list in a row', array_diff( [ 'event-when', 'event-ics', 'event-feed' ], \Nino\Components::allowed( $appData, 'section-col' ) ) === [] && in_array( 'events', \Nino\Components::allowed( $appData, 'section-row' ), true ) === true );
check( '...nothing else of the feature is a shortcode', array_keys( array_filter( \Nino\Features::shortcodes( $appData, 'events' ) ) ) === [] );
check( '...the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/events/part/part.css' ), $mine ) === [] );

$demo = (string) ( $group['demo'] ?? '' );
check( '...the demo of the group is read without a problem and draws the chip of three dates and a list that says it has nothing, while the project has no date', $demo !== '' && \Nino\Components::demoProblem( $demo, array_merge( array_keys( $components ) ) ) === null
	&& substr_count( eventsRender( $appData, $demo ), '<time class="event-when' ) === 3 && substr_count( eventsRender( $appData, $demo ), 'events-empty' ) === 1 && substr_count( eventsRender( $appData, $demo ), 'class="event-feed"' ) === 1 );

$part = (string) file_get_contents( $dir. '/groups/events/part/part.css' );
check( '...the part names no width but through a query written from a width, and no query at all that is not the pointer\'s', preg_match_all( '/@media\s*([^{]*)/', $part, $queries ) === 1 && array_filter( $queries[1], static fn( string $query ): bool => str_contains( $query, 'width' ) ) === [] );

// --- The moments - what a moment is, and when a date is upcoming ----------------

echo "\nThe moments of a date\n";

$moment = \Nino\Modules\Events::moment( $appData, '2026-12-24T18:30' );
check( 'an editor\'s moment is read in the timezone of the site: 2026-12-24T18:30 is 17:30 UTC', $moment instanceof DateTimeImmutable && $moment->format( 'Y-m-d H:i:s' ) === '2026-12-24 18:30:00' && $moment->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'H:i' ) === '17:30' );
check( '...with seconds, with a space for the T, and a day alone is its midnight', \Nino\Modules\Events::moment( $appData, '2026-12-24T18:30:15' )?->format( 'H:i:s' ) === '18:30:15' && \Nino\Modules\Events::moment( $appData, '2026-12-24 18:30' )?->format( 'H:i' ) === '18:30'
	&& \Nino\Modules\Events::moment( $appData, '2026-12-24' )?->format( 'Y-m-d H:i:s' ) === '2026-12-24 00:00:00' );
check( '...a day that does not exist, a time that does not, an empty value and what is no moment at all are none', \Nino\Modules\Events::moment( $appData, '2027-02-30T10:00' ) === null && \Nino\Modules\Events::moment( $appData, '2026-12-24T24:00' ) === null && \Nino\Modules\Events::moment( $appData, '2026-12-24T10:60' ) === null
	&& \Nino\Modules\Events::moment( $appData, '' ) === null && \Nino\Modules\Events::moment( $appData, 'tomorrow' ) === null && \Nino\Modules\Events::moment( $appData, '24.12.2026' ) === null && \Nino\Modules\Events::moment( $appData, null ) === null && \Nino\Modules\Events::moment( $appData, 20261224 ) === null && \Nino\Modules\Events::moment( $appData, '2026-12-24T18:30junk' ) === null
	&& \Nino\Modules\Events::moment( $appData, "2026-12-24T18:30\n" ) !== null && \Nino\Modules\Events::moment( $appData, "2026-12-24\x00T18:30" ) === null );

$now = \Nino\Modules\Events::now( $appData );
check( 'the clock is the key a test sets, in the timezone of the site: noon in Berlin', $now->format( 'Y-m-d H:i T' ) === '2026-12-10 12:00 CET' && $now->getTimezone()->getName() === 'Europe/Berlin' );

$at = static fn( string $start, ?string $end = null ): array => \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => $start, 'end' => $end ?? '' ] ) ?? [];
$in = static fn( string $scope, array $record, int $ahead = 365, int $past = 0 ): bool => \Nino\Modules\Events::selects( $record, $scope, $now, $ahead, $past );

check( 'a date that begins in the future is upcoming and not past', $in( 'upcoming', $at( '2026-12-11T10:00' ) ) === true && $in( 'past', $at( '2026-12-11T10:00' ) ) === false );
check( '...one that has begun and has not ended is upcoming, and not past', $in( 'upcoming', $at( '2026-12-10T09:00', '2026-12-10T18:00' ) ) === true && $in( 'past', $at( '2026-12-10T09:00', '2026-12-10T18:00' ) ) === false );
check( '...one that has ended is past, and not upcoming', $in( 'past', $at( '2026-12-10T08:00', '2026-12-10T11:00' ) ) === true && $in( 'upcoming', $at( '2026-12-10T08:00', '2026-12-10T11:00' ) ) === false );
check( '...one with no end is over when it begins: at noon sharp it is upcoming, a minute earlier it is past', $in( 'upcoming', $at( '2026-12-10T12:00' ) ) === true && $in( 'past', $at( '2026-12-10T12:00' ) ) === false
	&& $in( 'upcoming', $at( '2026-12-10T11:59' ) ) === false && $in( 'past', $at( '2026-12-10T11:59' ) ) === true );
check( '...an end that is not after the start, or is none, is no end', $at( '2026-12-10T10:00', '2026-12-10T10:00' )['end'] === null && $at( '2026-12-10T10:00', '2026-12-09T10:00' )['end'] === null && $at( '2026-12-10T10:00', 'later' )['end'] === null );
check( '...an element whose start is no moment is no date', \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => 'soon' ] ) === null && \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x' ] ) === null );
check( 'the days ahead end the upcoming dates: a date 365 days away is in, the day after is out, and 730 days take it in', $in( 'upcoming', $at( '2027-12-10T12:00' ) ) === true && $in( 'upcoming', $at( '2027-12-10T12:01' ) ) === false && $in( 'upcoming', $at( '2027-12-10T12:01' ), 730 ) === true );
check( '...the days back end the past ones, counted from the end of a date, and 0 is all of them', $in( 'past', $at( '2026-11-01T10:00', '2026-11-01T12:00' ), 365, 40 ) === true && $in( 'past', $at( '2026-11-01T10:00', '2026-11-01T12:00' ), 365, 30 ) === false
	&& $in( 'past', $at( '2020-01-01T10:00' ), 365, 0 ) === true );
check( '...all is the window of both', $in( 'all', $at( '2020-01-01T10:00' ) ) === true && $in( 'all', $at( '2020-01-01T10:00' ), 365, 30 ) === false && $in( 'all', $at( '2030-01-01T10:00' ) ) === false && $in( 'all', $at( '2026-12-24T10:00' ) ) === true );
check( '...a date is in the past window to the minute: one that ended exactly 30 days ago is in 30 days, one that ended a minute before is not', $in( 'past', $at( '2026-11-10T12:00' ), 365, 30 ) === true && $in( 'past', $at( '2026-11-10T11:59' ), 365, 30 ) === false );
check( 'the words of a date are trimmed: title, place, text and link lose the blanks round them', ( static function() use ( &$appData ): bool {
	$record = \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => '2026-12-20', 'title' => '  T ', 'place' => " P\n", 'text' => ' <p>x</p> ', 'link' => ' https://x.example/ ' ] );
	return $record['title'] === 'T' && $record['place'] === 'P' && $record['text'] === '<p>x</p>' && $record['link'] === 'https://x.example/';
} )() );
check( 'a date at midnight with no end is a day; with an end, or at another time, it is not', $at( '2026-12-20T00:00' )['allDay'] === true && $at( '2026-12-20' )['allDay'] === true && $at( '2026-12-20T00:00', '2026-12-20T23:00' )['allDay'] === false && $at( '2026-12-20T00:01' )['allDay'] === false );
check( 'a day is over when its day ends: a day that is today is upcoming and not past, yesterday\'s is over, and a date with a time and no end is over when it begins, one with an end when that comes',
	$in( 'upcoming', $at( '2026-12-10T00:00' ) ) === true && $in( 'past', $at( '2026-12-10T00:00' ) ) === false && $at( '2026-12-10T00:00' )['over']->format( 'Y-m-d H:i' ) === '2026-12-11 00:00'
	&& $in( 'past', $at( '2026-12-09T00:00' ) ) === true && $in( 'upcoming', $at( '2026-12-09T00:00' ) ) === false && $in( 'upcoming', $at( '2026-12-11T00:00' ) ) === true
	&& $at( '2026-12-10T08:00' )['over']->format( 'H:i' ) === '08:00' && $at( '2026-12-10T00:00', '2026-12-10T08:00' )['over']->format( 'H:i' ) === '08:00' );
check( 'a group is a slug as Members makes it - Board is board - and one that makes none belongs to nobody, never to everyone',
	\Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => '2026-12-20', 'group' => ' Board ' ] )['group'] === 'board' && \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => '2026-12-20', 'group' => '' ] )['group'] === ''
	&& \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => '2026-12-20', 'group' => '***' ] )['group'] === '!' && \Nino\Modules\Events::record( $appData, [ '.uri' => '/events/x', 'start' => '2026-12-20' ] )['group'] === '' );

// --- The dates - seeded around the clock ------------------------------------------

echo "\nThe dates of the tests\n";

eventsPlant( $appData );
$upcoming = [ 'running', 'at-now', 'soon', 'all-day', 'later', 'two-day' ];

\Nino\Elements::insertElement( $appData, '/events/a-tie', [ 'title' => 'Tie', 'start' => '2026-12-11T10:00' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/events/z-tie', [ 'title' => 'Tie', 'start' => '2026-12-11T10:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
check( 'dates that start at the same minute stand in the order of their names', array_values( array_intersect( eventsList( $appData ), [ 'a-tie', 'soon', 'z-tie' ] ) ) === [ 'a-tie', 'soon', 'z-tie' ] );
\Nino\Elements::deleteElement( $appData, '/events/a-tie', 'de_DE' );
\Nino\Elements::deleteElement( $appData, '/events/z-tie', 'de_DE' );
unset( $appData['./nino/elements/cache'] );

check( 'fifteen dates are seeded, with the moments global and the words in German, and the English ones beside', count( (array) \Nino\Elements::queryElements( $appData, '/events', [], 'de_DE', [] ) ) === 15
	&& ( \Nino\Elements::getElement( $appData, '/events/soon', 'en_US' )['start'] ?? '' ) === '2026-12-11T10:00' && ( \Nino\Elements::getElement( $appData, '/events/soon', 'en_US' )['title'] ?? '' ) === 'Soon' );

$drawnDemo = eventsFind( eventsRender( $appData, (string) ( \Nino\Components::groups( $appData )['events']['demo'] ?? '' ) ), '//time' );
check( 'the demo of the group draws the dates of the project with their own day in its list, three chips of two days, besides the three chips it holds itself', count( $drawnDemo ) === 6 && count( array_unique( array_map( static fn( DOMElement $chip ): string => $chip->getAttribute( 'datetime' ), array_slice( $drawnDemo, 3 ) ) ) ) === 2 );
$block = \Nino\Blocks::get( $appData, 'events-list' );
check( 'the feature brings one block, which the kernel reads without "missing", made of the components of this feature and of Basis, and no block is refused', is_array( $block ) === true && $block['missing'] === [] && $block['features'] === [ 'basis', 'events' ]
	&& array_keys( array_filter( \Nino\Blocks::all( $appData ), static fn( array $each ): bool => $each['origin'] === 'events' ) ) === [ 'events-list' ] && \Nino\Blocks::rejected( $appData ) === [] );
\Nino\Html::addFills( $appData, [ '[[/template/page-events/events/title]]' => 'Nächste Termine' ], '*' );
$appData['./nino/locales/current'] = 'de_DE';
$drawn = eventsRender( $appData, $block['source'] );
check( '...drawn, it is a headline, the next three dates each as a column with the chip of its start and its end, its title, and the link to the feed under them', trim( eventsFind( $drawn, '//h2' )[0]?->textContent ?? '' ) === 'Nächste Termine'
	&& array_map( static fn( DOMElement $title ): string => $title->textContent, eventsFind( $drawn, '//h3' ) ) === [ 'Läuft', 'Jetzt', 'Bald' ]
	&& array_map( static fn( DOMElement $chip ): string => $chip->getAttribute( 'datetime' ), eventsFind( $drawn, '//div[contains(@class, "section-col")]/time' ) ) === [ '2026-12-10T09:00', '2026-12-10T12:00', '2026-12-11T10:00' ]
	&& str_contains( eventsFind( $drawn, '//time' )[0]?->textContent ?? '', '09:00-18:00' ) === true && count( eventsFind( $drawn, '//a[@class="event-feed"]' ) ) === 1 );

// --- Without Members - the dates of a group are for nobody ---------------------------

echo "\nThe list for a visitor\n";

$appData['./nino/locales/current'] = 'de_DE';

check( 'Members is not on, so the feature does not ask it: nobody is in a group, a date of a group is for nobody and a date without one for everybody', \Nino\Modules\Events::members( $appData ) === false && \Nino\Modules\Events::viewer( $appData ) === ''
	&& \Nino\Modules\Events::visible( $appData, '' ) === true && \Nino\Modules\Events::visible( $appData, 'board' ) === false );
check( 'upcoming is the default: what is not over, soonest first - a date that has begun and not ended among them, one at this very minute too, and no date of a group, none beyond a year ahead, none that has no start', eventsList( $appData ) === $upcoming && eventsList( $appData, 'scope="upcoming"' ) === $upcoming );
check( '...past is what is over and starts with the one nearest to now', eventsList( $appData, 'scope="past"' ) === [ 'just-over', 'ended-today', 'past-recent', 'past-old' ] );
check( '...all is both, by their start', eventsList( $appData, 'scope="all"' ) === [ 'past-old', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'all-day', 'later', 'two-day' ] );
check( '...order="desc" turns it around: the last one that is coming first, and the oldest of the past ones first - all is by start, asc the earliest', eventsList( $appData, 'order="desc"' ) === array_reverse( $upcoming )
	&& eventsList( $appData, 'scope="past" order="desc"' ) === [ 'past-old', 'past-recent', 'ended-today', 'just-over' ] && eventsList( $appData, 'scope="all" order="desc"' ) === array_reverse( eventsList( $appData, 'scope="all"' ) ) && eventsList( $appData, 'order="asc"' ) === $upcoming );
check( '...limit keeps the first of them and 0 all, offset skips', eventsList( $appData, 'limit="2"' ) === [ 'running', 'at-now' ] && eventsList( $appData, 'scope="past" limit="1"' ) === [ 'just-over' ] && eventsList( $appData, 'limit="0"' ) === $upcoming
	&& eventsList( $appData, 'offset="4"' ) === [ 'later', 'two-day' ] && eventsList( $appData, 'offset="1" limit="2"' ) === [ 'at-now', 'soon' ] );
check( '...limit counts what the viewer sees: the dates of a group take no place from a visitor', eventsList( $appData, 'scope="all" limit="9"' ) === array_slice( eventsList( $appData, 'scope="all"' ), 0, 9 ) && count( eventsList( $appData, 'scope="all" limit="10"' ) ) === 10 );
check( '...a scope or an order that is none is the default, and so is a limit or an offset that is no number', eventsList( $appData, 'scope="never"' ) === $upcoming && eventsList( $appData, 'order="sideways"' ) === $upcoming
	&& eventsList( $appData, 'limit="many" offset="-3"' ) === $upcoming );
check( '...the sort and the callback of the call are not read: the order is the start', eventsList( $appData, 'sort="-title"' ) === $upcoming && eventsList( $appData, 'callback="/nino/nothing"' ) === $upcoming );
check( '...a query narrows by a field', eventsList( $appData, 'query="place=Halle"' ) === [ 'running', 'soon' ] && eventsList( $appData, 'scope="past" query="place=Halle"' ) === [ 'past-recent', 'past-old' ] );
check( '...a query narrows by every pair it holds, a pair of a field that is none narrows to nothing, a word that is no pair narrows nothing, and a date of a group stays out whatever the query asks: no warning', eventsList( $appData, 'query="place=Halle&title=Bald"' ) === [ 'soon' ] && eventsList( $appData, 'query="group=Board"' ) === []
	&& eventsList( $appData, 'query="nothing"' ) === $upcoming && eventsList( $appData, 'query="a=b=c&=x&&"' ) === [] && eventsList( $appData, 'query="[template /templates/x]"' ) === $upcoming && ninoWarnings() === [] );

check( 'where there is nothing to list the list says so in one line, in the words of the Text panel and of the scope', trim( strip_tags( eventsRender( $appData, '[events query="place=Nowhere"][event-ics .slug][/events]' ) ) ) === 'Es stehen keine Termine an.'
	&& trim( strip_tags( eventsRender( $appData, '[events scope="past" query="place=Nowhere"][event-ics .slug][/events]' ) ) ) === 'Es hat noch nichts stattgefunden.'
	&& trim( strip_tags( eventsRender( $appData, '[events scope="all" query="place=Nowhere"][event-ics .slug][/events]' ) ) ) === 'Es gibt keine Termine.'
	&& count( eventsFind( eventsRender( $appData, '[events query="place=Nowhere"][event-ics .slug][/events]' ), '//p[@class="events-empty"]' ) ) === 1 );
$appData['./nino/locales/current'] = 'en_US';
check( '...in English the English ones', trim( strip_tags( eventsRender( $appData, '[events query="place=Nowhere"][event-ics .slug][/events]' ) ) ) === 'There is nothing coming up.' );
$appData['./nino/locales/current'] = 'de_DE';
\Nino\Html::addFills( $appData, [ '[[/feature/events/state/upcoming]]' => '<i>&amp;[csrf]</i>' ], 'de_DE' );
check( 'the line a list writes for nothing to list is text: the entity of a word of the Text panel is the character it stands for and is escaped once, a tag and a call in it are none', str_contains( eventsRender( $appData, '[events query="place=Nowhere"][event-ics .slug][/events]' ), '<p class="events-empty">&lt;i&gt;&amp;[csrf]&lt;/i&gt;</p>' ) === true );
\Nino\Html::addFills( $appData, [ '[[/feature/events/state/upcoming]]' => 'Es stehen keine Termine an.' ], 'de_DE' );
$appData['./nino/locales/current'] = 'de_DE';

$columns = eventsRender( $appData, '[section-row][events limit="2"][section-col id="own" size="50" variant="card"][event-when .start end="end"][section-title .title tag="h3"][event-ics .slug][/section-col][/events][/section-row]' );
check( 'the children are drawn once for each date, a column of Basis in a row: two columns, each with the chip, the title and the link of its date, and the id of a call inside is not repeated for the dates', count( eventsFind( $columns, '//div[contains(@class, "section-row")]/div[contains(@class, "section-col")]' ) ) === 2
	&& count( eventsFind( $columns, '//div[contains(@class, "section-col")]/time[@class="event-when event-when--today"]/following-sibling::h3/following-sibling::a[@class="event-ics"]' ) ) === 2
	&& eventsFind( $columns, '//h3' )[0]->textContent === 'Läuft' && eventsFind( $columns, '//h3' )[1]->textContent === 'Jetzt' && str_contains( $columns, ' id="' ) === false );
check( '...the fields of the date are the fields of the element: .title, .place and .slug, and the end of the chip is the field end of the very same date', eventsFind( eventsRender( $appData, '[events limit="1"][event-when .start end="end"][/events]' ), '//time' )[0]?->textContent === 'Heute, 09:00-18:00'
	&& trim( strip_tags( eventsRender( $appData, '[events scope="past" limit="1"][text .place][/events]' ) ) ) === '' && trim( strip_tags( eventsRender( $appData, '[events limit="1"][text .place][/events]' ) ) ) === 'Halle' );
$appData['./nino/locales/current'] = 'en_US';
check( '...in English a date that has no English title takes the German one, and the English one where there is it', array_map( static fn( DOMElement $title ): string => $title->textContent, eventsFind( eventsRender( $appData, '[events limit="3"][section-title .title tag="h3"][/events]' ), '//h3' ) ) === [ 'Läuft', 'Jetzt', 'Soon' ] );
$appData['./nino/locales/current'] = 'de_DE';

// What a hand typed into a date is text wherever the list puts it: markup, a call and a fill do nothing
\Nino\Elements::insertElement( $appData, '/events/hostile', [ 'title' => '"><script>alert(1)</script> [csrf] [[/project/company/general/name]] [template /templates/evil] & x', 'start' => '2026-12-12T10:00', 'place' => '<b>Hall</b> [[/project/company/general/name]]' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$hostile = eventsRender( $appData, '[events query="start=2026-12-12T10:00"][section-title .title tag="h3"][text .place][event-ics .slug][/events]' );
check( 'a title or a place that spells markup, a call or a fill is the text it is: escaped once, no script, no token of the guard, no name of the company filled in', str_contains( $hostile, '<script' ) === false && str_contains( $hostile, 'name="_csrf"' ) === false
	&& str_contains( $hostile, 'The Club' ) === false && str_contains( $hostile, '&lt;script&gt;alert(1)&lt;/script&gt;' ) === true && str_contains( $hostile, '[csrf]' ) === true && str_contains( $hostile, '[template /templates/evil]' ) === true && str_contains( $hostile, '<b>' ) === false );
\Nino\Elements::deleteElement( $appData, '/events/hostile', 'de_DE' );
unset( $appData['./nino/elements/cache'] );

check( 'an attribute that spells a call, a fill or a quote is the text it is, and changes no list', eventsList( $appData, 'scope="[csrf]" order="[[/project/company/general/name]]"' ) === $upcoming
	&& count( eventsFind( eventsRender( $appData, '[events limit="1" scope="x&quot; onclick=&quot;y"][event-ics .slug][/events]' ), '//*[@onclick]' ) ) === 0 );
check( 'a list leaves no context of a round behind it', eventsFind( eventsRender( $appData, '[events limit="1"][event-ics .slug][/events][event-ics .slug]' ), '//a' ) !== [] && count( eventsFind( eventsRender( $appData, '[events limit="1"][event-ics .slug][/events][event-ics .slug]' ), '//a' ) ) === 1);

// --- The chip ---------------------------------------------------------------------------

echo "\n[event-when] - the date in the language of the page\n";

$running = eventsChip( $appData, '2026-12-10T09:00' );
check( 'a date that is on today is a <time> with the class of the chip and today\'s, the start as its datetime exactly, and the day in words', $running !== null && eventsHas( $running, 'event-when' ) === true && eventsHas( $running, 'event-when--today' ) === true && eventsHas( $running, 'event-when--past' ) === false
	&& $running->getAttribute( 'datetime' ) === '2026-12-10T09:00' && str_starts_with( $running->textContent, 'Heute' ) === true );
check( '...an end on the same day makes a range of times: 09:00-18:00', $running !== null && str_contains( $running->textContent, '09:00-18:00' ) === true );
$over = eventsChip( $appData, '2026-12-10T08:00' );
check( '...one that is over and was today is both: event-when--today and event-when--past', eventsHas( $over, 'event-when--today' ) === true && eventsHas( $over, 'event-when--past' ) === true && $over?->getAttribute( 'datetime' ) === '2026-12-10T08:00' );
$old = eventsChip( $appData, '2026-11-01T10:00' );
check( '...one that is over is event-when--past alone, with the day written out - it has the day number, the month and the year', eventsHas( $old, 'event-when--past' ) === true && eventsHas( $old, 'event-when--today' ) === false && preg_match( '/0?1\D+11\D+2026|1\. Nov/u', (string) $old?->textContent ) === 1 );
$soon = eventsChip( $appData, '2026-12-11T10:00' );
check( '...tomorrow is a word, and neither class is on a date that is yet to come', $soon !== null && str_starts_with( $soon->textContent, 'Morgen, 10:00' ) === true && eventsHas( $soon, 'event-when--today' ) === false && eventsHas( $soon, 'event-when--past' ) === false );
$later = eventsChip( $appData, '2026-12-24T18:00' );
check( '...a later day has its day number and the time; the range 18:00-20:00; the datetime is the start alone', $later !== null && str_contains( $later->textContent, '24' ) === true && str_contains( $later->textContent, '18:00-20:00' ) === true && $later->getAttribute( 'datetime' ) === '2026-12-24T18:00' );
$two = eventsChip( $appData, '2026-12-27T10:00' );
check( '...an end on another day writes both days with the word between them, bis, and the times with them', $two !== null && str_contains( $two->textContent, '27' ) === true && str_contains( $two->textContent, '28' ) === true && str_contains( $two->textContent, ' bis ' ) === true
	&& str_contains( $two->textContent, '10:00' ) === true && str_contains( $two->textContent, '12:00' ) === true && $two->getAttribute( 'datetime' ) === '2026-12-27T10:00' );
$day = eventsChip( $appData, '2026-12-20T00:00' );
check( '...a date at midnight with no end is a day: the day alone, no time, its datetime a date', $day !== null && $day->getAttribute( 'datetime' ) === '2026-12-20' && str_contains( $day->textContent, ':' ) === false && str_contains( $day->textContent, '20' ) === true );
\Nino\Elements::insertElement( $appData, '/events/today-all-day', [ 'title' => 'Heute ganztägig', 'start' => '2026-12-10T00:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$todayAllDay = eventsChip( $appData, '2026-12-10T00:00' );
check( '...a day that is today is upcoming in the list, and its chip is event-when--today and not event-when--past - the day is not over at midnight', $todayAllDay !== null && eventsHas( $todayAllDay, 'event-when--today' ) === true && eventsHas( $todayAllDay, 'event-when--past' ) === false
	&& str_starts_with( $todayAllDay->textContent, 'Heute' ) === true && $todayAllDay->getAttribute( 'datetime' ) === '2026-12-10' && in_array( 'today-all-day', eventsList( $appData ), true ) === true && in_array( 'today-all-day', eventsList( $appData, 'scope="past"' ), true ) === false );
\Nino\Elements::insertElement( $appData, '/events/long-running', [ 'title' => 'Lang', 'start' => '2026-12-08T10:00', 'end' => '2026-12-12T10:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
check( '...a date that began days ago and ends days ahead takes place today and is not over', eventsHas( eventsChip( $appData, '2026-12-08T10:00' ), 'event-when--today' ) === true && eventsHas( eventsChip( $appData, '2026-12-08T10:00' ), 'event-when--past' ) === false );
\Nino\Elements::deleteElement( $appData, '/events/long-running', 'de_DE' );
\Nino\Elements::insertElement( $appData, '/events/yesterday-all-day', [ 'title' => 'Gestern ganztägig', 'start' => '2026-12-09T00:00' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/events/tomorrow-all-day', [ 'title' => 'Morgen ganztägig', 'start' => '2026-12-11T00:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$yesterdayAllDay = eventsChip( $appData, '2026-12-09T00:00' );
$tomorrowAllDay = eventsChip( $appData, '2026-12-11T00:00' );
check( '...yesterday\'s day is over and not today, tomorrow\'s is neither', eventsHas( $yesterdayAllDay, 'event-when--past' ) === true && eventsHas( $yesterdayAllDay, 'event-when--today' ) === false && eventsHas( $tomorrowAllDay, 'event-when--past' ) === false && eventsHas( $tomorrowAllDay, 'event-when--today' ) === false );
foreach( [ 'today-all-day', 'yesterday-all-day', 'tomorrow-all-day' ] as $slug )
	\Nino\Elements::deleteElement( $appData, '/events/'. $slug, 'de_DE' );
unset( $appData['./nino/elements/cache'] );
check( '...an end that names no field, or a field without a moment in it, leaves the start alone', str_contains( (string) eventsChip( $appData, '2026-12-24T18:00', '.start end="nowhere"' )?->textContent, '-20:00' ) === false
	&& str_contains( (string) eventsChip( $appData, '2026-12-24T18:00', '.start end="place"' )?->textContent, '-' ) === false && str_contains( (string) eventsChip( $appData, '2026-12-24T18:00', '.start' )?->textContent, '20:00' ) === false );
check( '...and an end the markup writes cannot break out of it - a field name that is none is read as none', eventsChip( $appData, '2026-12-24T18:00', '.start end="a b&quot;"' ) !== null && eventsChip( $appData, '2026-12-24T18:00', '.start end="a b&quot;"' )?->getAttribute( 'datetime' ) === '2026-12-24T18:00'
	&& str_contains( (string) eventsChip( $appData, '2026-12-24T18:00', '.start end="../end"' )?->textContent, '20:00' ) === false );

\Nino\Elements::insertElement( $appData, '/events/backwards', [ 'title' => 'Zurück', 'start' => '2026-12-30T10:00', 'end' => '2026-12-29T10:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$backwards = eventsChip( $appData, '2026-12-30T10:00' );
check( '...an end before the start is no end: the start alone, no range and no second day', $backwards !== null && str_contains( $backwards->textContent, ' bis ' ) === false && str_contains( $backwards->textContent, '29' ) === false && str_contains( $backwards->textContent, '30' ) === true && str_ends_with( $backwards->textContent, '10:00' ) === true );
\Nino\Elements::deleteElement( $appData, '/events/backwards', 'de_DE' );
unset( $appData['./nino/elements/cache'] );

$date = eventsChip( $appData, '2026-12-27T10:00', '.start end="end" show="date"' );
check( 'show="date" is the day alone, the two of a range with bis: the datetime is the day, and the class says what the chip shows', $date !== null && $date->getAttribute( 'datetime' ) === '2026-12-27' && str_contains( $date->textContent, ':' ) === false && str_contains( $date->textContent, ' bis ' ) === true && str_contains( $date->textContent, '28' ) === true
	&& eventsHas( $date, 'event-when--show-date' ) === true && eventsHas( eventsChip( $appData, '2026-12-27T10:00' ), 'event-when--show-date' ) === false );
check( '...a range inside one day is that day, once', str_contains( (string) eventsChip( $appData, '2026-12-24T18:00', '.start end="end" show="date"' )?->textContent, ' bis ' ) === false && eventsChip( $appData, '2026-12-24T18:00', '.start end="end" show="date"' )?->getAttribute( 'datetime' ) === '2026-12-24' );
$time = eventsChip( $appData, '2026-12-24T18:00', '.start end="end" show="time"' );
check( 'show="time" is the time alone: 18:00-20:00 with the start as its datetime', $time !== null && $time->textContent === '18:00-20:00' && $time->getAttribute( 'datetime' ) === '18:00' && eventsChip( $appData, '2026-12-24T18:00', '.start show="time"' )?->textContent === '18:00' );
check( '...a show that is none is datetime', eventsChip( $appData, '2026-12-24T18:00', '.start show="week"' )?->getAttribute( 'datetime' ) === '2026-12-24T18:00' );

\Nino\Html::addFills( $appData, [ '[[/feature/events/when/today]]' => '<i>&amp;[csrf]</i>' ], 'de_DE' );
check( 'a word of the Text panel is text in the chip: its entity is the character it stands for and is escaped once, its tag and its call are no tag and no call', str_contains( eventsRender( $appData, '[events scope="all" query="start=2026-12-10T09:00"][event-when .start][/events]' ), '&lt;i&gt;&amp;[csrf]&lt;/i&gt;, 09:00' ) === true );
\Nino\Html::addFills( $appData, [ '[[/feature/events/when/today]]' => 'Heute' ], 'de_DE' );

$fixed = eventsRender( $appData, '[event-when text="2026-12-24T18:30" show="datetime"][event-when text="2026-12-24T18:30" show="date"][event-when text="2026-12-24T18:30" show="time"][event-when text="nonsense"][event-when text="2027-02-30T10:00"][event-when text=""][event-when text="[csrf]"]' );
$chips = eventsFind( $fixed, '//time' );
check( 'outside a list the text="" is the moment: three chips, the datetime of each exactly as its show says, and nothing for what is no moment', count( $chips ) === 3 && $chips[0]->getAttribute( 'datetime' ) === '2026-12-24T18:30' && $chips[1]->getAttribute( 'datetime' ) === '2026-12-24'
	&& $chips[2]->getAttribute( 'datetime' ) === '18:30' && $chips[2]->textContent === '18:30' && str_contains( $fixed, '[csrf]' ) === false );
\Nino\Html::addFills( $appData, [ '[[/project/website/event/start]]' => '2026-12-31T22:00' ], '*' );
check( '...a key of the Text panel can be the moment', eventsFind( eventsRender( $appData, '[event-when /project/website/event/start]' ), '//time' )[0]?->getAttribute( 'datetime' ) === '2026-12-31T22:00' );

$appData['./nino/locales/current'] = 'en_US';
$englishRunning = eventsChip( $appData, '2026-12-10T09:00' );
$englishLater = eventsChip( $appData, '2026-12-24T18:00' );
$englishTwo = eventsChip( $appData, '2026-12-27T10:00' );
check( 'in English the words are English, the datetime attribute is the same, and the numbers are the locale\'s: Today, 24 and 6:00-8:00 PM or 18:00', $englishRunning !== null && str_starts_with( $englishRunning->textContent, 'Today' ) === true && $englishRunning->getAttribute( 'datetime' ) === '2026-12-10T09:00'
	&& $englishLater !== null && str_contains( $englishLater->textContent, '24' ) === true && $englishLater->getAttribute( 'datetime' ) === '2026-12-24T18:00' && preg_match( '/(6:00|18:00).*(8:00|20:00)/u', $englishLater->textContent ) === 1
	&& eventsChip( $appData, '2026-12-11T10:00' ) !== null && str_starts_with( (string) eventsChip( $appData, '2026-12-11T10:00' )?->textContent, 'Tomorrow' ) === true );
check( '...a range over days says until', $englishTwo !== null && str_contains( $englishTwo->textContent, ' until ' ) === true && str_contains( $englishTwo->textContent, '27' ) === true && str_contains( $englishTwo->textContent, '28' ) === true );

$fixedAt = static fn( string $locale, bool $date ): string => \Nino\Modules\Events::fixed( $locale, new DateTimeImmutable( '2026-12-24 18:00' ), $date );
check( 'the fixed patterns, for the site where the intl extension is not: German 24.12.2026 and 18:00, en_US 12/24/2026 and 6:00 PM, other English 24/12/2026, any other language the ISO day',
	$fixedAt( 'de_DE', true ) === '24.12.2026' && $fixedAt( 'de_DE', false ) === '18:00' && $fixedAt( 'de_AT', true ) === '24.12.2026' && $fixedAt( 'en_US', true ) === '12/24/2026' && $fixedAt( 'en_US', false ) === '6:00 PM'
	&& $fixedAt( 'en_GB', true ) === '24/12/2026' && $fixedAt( 'en_GB', false ) === '18:00' && $fixedAt( 'fr_FR', true ) === '2026-12-24' && $fixedAt( 'fr_FR', false ) === '18:00' );
$englishDay = \Nino\Modules\Events::pattern( $appData, new DateTimeImmutable( '2026-12-24 18:00' ), true );
$englishTime = \Nino\Modules\Events::pattern( $appData, new DateTimeImmutable( '2026-12-24 18:00' ), false );
$appData['./nino/locales/current'] = 'de_DE';
check( '...and the extension is used where it is loaded: the locale\'s own medium date, short time and the names of its months - Dec 24, 2026 in en_US - and the fixed pattern where it is not',
	extension_loaded( 'intl' ) === true ? ( str_contains( $englishDay, 'Dec' ) === true && str_contains( $englishDay, '24' ) === true && preg_match( '/^6:00\s?PM$/u', $englishTime ) === 1 ) : ( $englishDay === '12/24/2026' && $englishTime === '6:00 PM' ) );
check( '...a time of the German page is the German one: 18:00', \Nino\Modules\Events::pattern( $appData, new DateTimeImmutable( '2026-12-24 18:00' ), false ) === '18:00' && \Nino\Modules\Events::pattern( $appData, new DateTimeImmutable( '2026-12-24 18:00' ), true ) === '24.12.2026' );

// --- The link to one date -----------------------------------------------------------------

echo "\n[event-ics] - the link to the file of one date\n";

$links = eventsFind( eventsRender( $appData, '[event-ics text="soon"]' ), '//a' );
check( 'a link to /.events/<slug>.ics, an <a> with its class and the word of the unit and the type of a calendar', count( $links ) === 1 && $links[0]->getAttribute( 'href' ) === '/.events/soon.ics' && $links[0]->getAttribute( 'class' ) === 'event-ics'
	&& $links[0]->textContent === 'Zum Kalender hinzufügen' && $links[0]->getAttribute( 'type' ) === 'text/calendar' );
check( '...in a list the source is .slug or .uri, and the link carries the directory of the site', eventsList( $appData, 'limit="2"' ) === [ 'running', 'at-now' ]
	&& array_map( static fn( DOMElement $link ): string => $link->getAttribute( 'href' ), eventsFind( eventsRender( $appData, '[events limit="2"][event-ics .uri][/events]' ), '//a' ) ) === [ '/.events/running.ics', '/.events/at-now.ics' ] );
$appData['/nino/dir'] = '/shop';
check( '...under a directory the href starts with it', eventsFind( eventsRender( $appData, '[event-ics text="soon"]' ), '//a' )[0]?->getAttribute( 'href' ) === '/shop/.events/soon.ics' );
$appData['/nino/dir'] = '';
$nothing = [ 'nowhere', 'Soon', 'so on', 'soon&quot; onclick=&quot;x', '../soon', '/other/soon', '/events/soon/', 'broken', '[csrf]', '[[/project/company/general/name]]', 'soon]' ];
check( '...nothing for a date that is not there, a name that is no slug and a date whose start is no moment', array_filter( $nothing, static fn( string $text ): bool => eventsRender( $appData, '[event-ics text="'. $text. '"]' ) !== '' ) === [] && eventsRender( $appData, '[event-ics text="/events/soon"]' ) !== '' );
check( '...and nothing for a date of a group that this visitor is not in', eventsRender( $appData, '[event-ics text="board-meeting"]' ) === '' && eventsRender( $appData, '[event-ics text="strange"]' ) === '' );
\Nino\Html::addFills( $appData, [ '[[/feature/events/feed/label]]' => '<i>&amp;[csrf]</i>' ], 'de_DE' );
check( 'the word of the feed link is text too: escaped once, its tag and its call are none', str_contains( eventsRender( $appData, '[event-feed]' ), 'rel="nofollow">&lt;i&gt;&amp;[csrf]&lt;/i&gt;</a>' ) === true );
\Nino\Html::addFills( $appData, [ '[[/feature/events/feed/label]]' => 'Kalender abonnieren' ], 'de_DE' );

// --- The feed ---------------------------------------------------------------------------------------------

echo "\nGET /.events.ics - the feed\n";

$publicUids = [ 'past-old', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'all-day', 'later', 'two-day' ];
$feed = eventsServe( $appData, '/.events.ics' );
$ics = $feed[4];
$lines = eventsLines( $ics );

check( 'the feed answers text/calendar in UTF-8, inline as events.ics, for a shared cache for an hour', $feed[0] === 200 && $feed[1] === 'text/calendar; charset=utf-8' && $feed[3] === 'inline; filename="events.ics"' && $feed[2] === 'public, max-age=3600' );
check( '...a VCALENDAR 2.0 whose PRODID is the name of the feature and has no version in it, with the name of the company for a name', array_slice( $lines, 0, 5 ) === [ 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Nino//Events//EN', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:The Club' ]
	&& end( $lines ) === 'END:VCALENDAR' && preg_match( '/\d/', $lines[2] ) === 0 );
check( '...every line ends in CRLF and none in a bare LF, and the file ends in one', str_ends_with( $ics, "\r\nEND:VCALENDAR\r\n" ) === true && preg_match( '/(?<!\r)\n/', $ics ) === 0 );
check( '...one VEVENT for each public date within the window - the dates of a group, the one beyond a year and the one without a start are not in it - by start', substr_count( $ics, "BEGIN:VEVENT\r\n" ) === 10 && substr_count( $ics, "END:VEVENT\r\n" ) === 10 && eventsUids( $ics ) === $publicUids );
check( '...the UID is the slug at the host of the website', array_filter( $lines, static fn( string $line ): bool => str_starts_with( $line, 'UID:' ) && str_ends_with( $line, '@example.org' ) === false ) === [] );

$runningEvent = eventsEvent( $ics, 'running' );
check( 'a date with an end is a moment and a moment in UTC - the site is on Berlin time, so 09:00 is 08:00', in_array( 'DTSTART:20261210T080000Z', $runningEvent, true ) && in_array( 'DTEND:20261210T170000Z', $runningEvent, true ) );
check( '...the stamp is the start, the title, the place and the address of the site are there', in_array( 'DTSTAMP:20261210T080000Z', $runningEvent, true ) && in_array( 'SUMMARY:Läuft', $runningEvent, true ) && in_array( 'LOCATION:Halle', $runningEvent, true ) && in_array( 'URL:https://example.org/club/programm', $runningEvent, true ) );
check( '...a date without a place or a text has no line for them', array_filter( eventsEvent( $ics, 'at-now' ), static fn( string $line ): bool => preg_match( '/^(LOCATION|DESCRIPTION|URL|DTEND)/', $line ) === 1 ) === [] );
$allDay = eventsEvent( $ics, 'all-day' );
check( 'a date at midnight with no end is a day: DATE values, the day after as the end, no moment', in_array( 'DTSTART;VALUE=DATE:20261220', $allDay, true ) && in_array( 'DTEND;VALUE=DATE:20261221', $allDay, true ) && array_filter( $allDay, static fn( string $line ): bool => str_starts_with( $line, 'DTSTART:' ) || str_starts_with( $line, 'DTEND:' ) ) === [] );
$laterEvent = eventsEvent( $ics, 'later' );
check( 'a title, a place and a text are escaped as RFC 5545 asks: the backslash, the semicolon and the comma with a backslash, a line break as \\n', in_array( 'SUMMARY:Feier\\, groß\; mit \\\\ Gästen', $laterEvent, true ) && in_array( 'LOCATION:Halle 1\\, Hof', $laterEvent, true )
	&& in_array( 'DESCRIPTION:Erste Zeile\\nZweite\\, Zeile\; ok & gut\\n- Punkt', $laterEvent, true ) );
check( '...the link is the address of the date, and no quote or entity is made of it', in_array( 'URL:https://example.org/feier?a=1&b=2', $laterEvent, true ) && in_array( 'DTSTART:20261224T170000Z', $laterEvent, true ) && in_array( 'DTEND:20261224T190000Z', $laterEvent, true ) );

$physical = explode( "\r\n", rtrim( $ics, "\r\n" ) );
$long = str_repeat( 'ä', 60 ). ' und lang genug, dass die Zeile gefaltet werden muss';
check( 'a line of more than 75 octets is folded: none is longer than 75 octets, the next begins with a space, no character is cut in the middle of its bytes', array_filter( $physical, static fn( string $line ): bool => str_starts_with( $line, ' ' ) ) !== [] && max( array_map( 'strlen', $physical ) ) <= 75
	&& array_filter( $physical, static fn( string $line ): bool => mb_check_encoding( $line, 'UTF-8' ) === false ) === [] && in_array( 'SUMMARY:'. str_replace( [ '\\', ';', ',' ], [ '\\\\', '\;', '\\,' ], $long ), eventsEvent( $ics, 'two-day' ), true ) );

// The bytes: what the Nino 1 feature wrote for these dates under its own kernel, file by file
$same = [];
$appData['./nino/locales/current'] = 'de_DE';
foreach( [ 'feed.visitor.de_DE' => '/.events.ics' ] + array_combine( array_map( static fn( string $slug ): string => 'single.'. $slug, [ 'past-old', 'past-recent', 'ended-today', 'just-over', 'running', 'at-now', 'soon', 'all-day', 'later', 'two-day', 'far' ] ), array_map( static fn( string $slug ): string => '/.events/'. $slug. '.ics', [ 'past-old', 'past-recent', 'ended-today', 'just-over', 'running', 'at-now', 'soon', 'all-day', 'later', 'two-day', 'far' ] ) ) as $label => $uri )
	if( eventsSame( $label, eventsServe( $appData, $uri )[4], EVENTS_BYTES ) === false )
		$same[] = $label;
$appData['./nino/locales/current'] = 'en_US';
foreach( [ 'feed.visitor.en_US' => '/.events.ics', 'single.soon.en_US' => '/.events/soon.ics' ] as $label => $uri )
	if( eventsSame( $label, eventsServe( $appData, $uri )[4], EVENTS_BYTES ) === false )
		$same[] = $label;
$appData['./nino/locales/current'] = 'de_DE';
check( 'the bytes of the feed of a visitor in both languages and of the file of each public date are those of the Nino 1 feature, 14 files', $same === [] );
check( '...and a date of a group, a date that is no date and a name that is none are the 404 the feature answered', array_filter( [ 'board-meeting', 'staff-party', 'strange', 'broken', 'nowhere' ], static fn( string $slug ): bool => eventsServe( $appData, '/.events/'. $slug. '.ics' ) !== [ 404, 'text/plain; charset=utf-8', 'no-store', '', 'Not Found' ] ) === [] );

\Nino\Elements::insertElement( $appData, '/events/summer', [ 'title' => 'Sommer', 'start' => '2027-07-01T14:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
check( 'the conversion follows the timezone of the day: in the summer Berlin is two hours ahead of UTC', in_array( 'DTSTART:20270701T120000Z', eventsEvent( eventsServe( $appData, '/.events.ics' )[4], 'summer' ), true ) );
\Nino\Elements::deleteElement( $appData, '/events/summer', 'de_DE' );
unset( $appData['./nino/elements/cache'] );

// The zone the dates are read in is the setting's: a process that runs in UTC, the site in Berlin
date_default_timezone_set( 'UTC' );
foreach( [ 'tz-winter' => '2026-12-30T14:00', 'tz-summer' => '2027-07-01T14:00' ] as $slug => $start )
	\Nino\Elements::insertElement( $appData, '/events/'. $slug, [ 'title' => 'Zone', 'start' => $start ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$zoned = static function( string $slug ) use ( &$appData ): array {
	return eventsEvent( eventsServe( $appData, '/.events/'. $slug. '.ics' )[4], $slug );
};
check( 'the setting timezone is "as the server is set" until it is changed, which is the zone php runs in: 14:00 in a process on UTC is 14:00Z, in winter and in summer', \Nino\Features::setting( $appData, 'events', 'timezone' ) === 'server' && \Nino\Modules\Events::zone( $appData )->getName() === 'UTC'
	&& in_array( 'DTSTART:20261230T140000Z', $zoned( 'tz-winter' ), true ) && in_array( 'DTSTART:20270701T140000Z', $zoned( 'tz-summer' ), true ) );
$utcServer = eventsServe( $appData, '/.events.ics' )[4];
check( 'with the setting Europe/Berlin on a process on UTC 14:00 is 13:00Z in the winter and 12:00Z in the summer, and the clock and the chip are the zone\'s', \Nino\Features::saveSettings( $appData, 'events', [ 'timezone' => 'Europe/Berlin' ] ) === []
	&& in_array( 'DTSTART:20261230T130000Z', $zoned( 'tz-winter' ), true ) && in_array( 'DTSTART:20270701T120000Z', $zoned( 'tz-summer' ), true ) && \Nino\Modules\Events::zone( $appData )->getName() === 'Europe/Berlin'
	&& \Nino\Modules\Events::now( $appData )->format( 'H:i T' ) === '12:00 CET' && str_contains( (string) eventsChip( $appData, '2026-12-30T14:00' )?->textContent, '14:00' ) === true );
foreach( [ 'tz-winter', 'tz-summer' ] as $slug )
	\Nino\Elements::deleteElement( $appData, '/events/'. $slug, 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$utcBerlin = [ 'feed.utc-berlin' => eventsServe( $appData, '/.events.ics' )[4], 'single.later.utc-berlin' => eventsServe( $appData, '/.events/later.ics' )[4] ];
$refused = \Nino\Features::saveSettings( $appData, 'events', [ 'timezone' => 'Mars/Base' ] );
check( 'a name that is no option of the setting is refused by the settings validation, and changes nothing', isset( $refused['timezone'] ) === true && \Nino\Features::setting( $appData, 'events', 'timezone' ) === 'Europe/Berlin' );
\Nino\Features::saveSettings( $appData, 'events', [ 'timezone' => 'server' ] );
$utcServer = eventsServe( $appData, '/.events.ics' )[4];
date_default_timezone_set( 'Europe/Berlin' );
check( '...the files of a process on UTC are those of the Nino 1 feature, with the zone of the setting and with the zone of the server: 3 files', eventsSame( 'feed.utc-berlin', $utcBerlin['feed.utc-berlin'], EVENTS_BYTES ) === true && eventsSame( 'single.later.utc-berlin', $utcBerlin['single.later.utc-berlin'], EVENTS_BYTES ) === true
	&& eventsSame( 'feed.utc-server', $utcServer, EVENTS_BYTES ) === true );

// The feed reaches back a year where the setting past says 0; the list reaches back as far as there are dates
foreach( [ 'old-feed' => '2025-11-05T10:00', 'mid-feed' => '2026-02-13T10:00' ] as $slug => $start )
	\Nino\Elements::insertElement( $appData, '/events/'. $slug, [ 'title' => 'Alt', 'start' => $start ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$reach = eventsUids( eventsServe( $appData, '/.events.ics' )[4] );
check( 'the feed reads 0 days back as a year: a date 300 days ago is in it and one 400 days ago is not - and the list, where 0 is all of them, lists both', in_array( 'mid-feed', $reach, true ) === true && in_array( 'old-feed', $reach, true ) === false
	&& array_slice( eventsList( $appData, 'scope="past"' ), -2 ) === [ 'mid-feed', 'old-feed' ] );
\Nino\Features::saveSettings( $appData, 'events', [ 'past' => '500' ] );
check( '...and a setting of its own is the feed\'s too: 500 days takes in the older one', in_array( 'old-feed', eventsUids( eventsServe( $appData, '/.events.ics' )[4] ), true ) === true );
\Nino\Features::saveSettings( $appData, 'events', [ 'past' => '0' ] );
foreach( [ 'old-feed', 'mid-feed' ] as $slug )
	\Nino\Elements::deleteElement( $appData, '/events/'. $slug, 'de_DE' );
unset( $appData['./nino/elements/cache'] );

// What an editor types can be nothing a calendar may carry: an address that runs a script, one that leaves for another site without a scheme, a title that tries to start a line of its own
$addresses = [ 'js-link' => 'javascript:alert(1)', 'rel-link' => '//evil.example/x', 'data-link' => 'data:text/html,x', 'mail-link' => 'mailto:a@example.org', 'good-link' => 'HTTPS://example.org/ok' ];
foreach( $addresses as $slug => $address )
	\Nino\Elements::insertElement( $appData, '/events/'. $slug, [ 'title' => "Line\r\nATTENDEE:evil\nEND:VEVENT", 'start' => '2026-12-30T10:00', 'link' => $address, 'place' => "A\rB" ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$linked = [];
$linkedBytes = [];
foreach( array_keys( $addresses ) as $slug ) {
	$answer = eventsServe( $appData, '/.events/'. $slug. '.ics' )[4];
	$linked[$slug] = eventsEvent( $answer, $slug );
	$linkedBytes[$slug] = eventsSame( 'single.'. $slug, $answer, EVENTS_BYTES );
}
check( 'an address is a link in the file only if it is http or https - a script, a data address, a mail address and one without a scheme are none - and a site-relative one has the address of the site',
	array_filter( array_slice( $linked, 0, 4 ), static fn( array $lines ): bool => array_filter( $lines, static fn( string $line ): bool => str_starts_with( $line, 'URL:' ) ) !== [] ) === [] && in_array( 'URL:HTTPS://example.org/ok', $linked['good-link'], true ) );
check( '...a title with a line break in it is one line of the file, as are the place and the rest: nothing an editor types starts a line of its own', in_array( 'SUMMARY:Line\\nATTENDEE:evil\\nEND:VEVENT', $linked['js-link'], true ) === true
	&& in_array( 'LOCATION:A\\nB', $linked['js-link'], true ) === true && array_filter( $linked['js-link'], static fn( string $line ): bool => str_starts_with( $line, 'ATTENDEE' ) ) === [] && count( $linked['js-link'] ) === 7
	&& count( array_keys( eventsLines( eventsServe( $appData, '/.events/js-link.ics' )[4] ), 'BEGIN:VEVENT', true ) ) === 1 && count( array_keys( eventsLines( eventsServe( $appData, '/.events/js-link.ics' )[4] ), 'END:VEVENT', true ) ) === 1 );
check( '...the five files are those of the Nino 1 feature, byte for byte', array_filter( $linkedBytes, static fn( bool $same ): bool => $same === false ) === [] && count( $linkedBytes ) === 5 );
\Nino\Elements::insertElement( $appData, '/events/trick', [ 'title' => 'Trick', 'start' => '2026-12-30T10:00', 'link' => 'javascript:alert(1)//https://x.example/' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/events/only-english', [ 'title' => 'English only', 'start' => '2026-12-30T11:00' ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/events/year-edge', [ 'title' => 'Edge', 'start' => '2025-12-10T12:00' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/events/year-before', [ 'title' => 'Before', 'start' => '2025-12-10T11:59' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
check( 'an address that only holds an http address further on is none: javascript:alert(1)//https://x.example/ makes no URL line', array_filter( eventsEvent( eventsServe( $appData, '/.events/trick.ics' )[4], 'trick' ), static fn( string $line ): bool => str_starts_with( $line, 'URL' ) ) === [] );
check( 'the file of a date that has no words in the language of the page has those of another language, as the feed has: a title is the one thing a calendar entry has to have', in_array( 'SUMMARY:English only', eventsEvent( eventsServe( $appData, '/.events/only-english.ics' )[4], 'only-english' ), true ) === true
	&& in_array( 'SUMMARY:English only', eventsEvent( eventsServe( $appData, '/.events.ics' )[4], 'only-english' ), true ) === true );
$edge = eventsUids( eventsServe( $appData, '/.events.ics' )[4] );
check( 'the feed reads 0 days back as 365 to the minute: a date that began exactly 365 days ago is in it, one a minute earlier is not', in_array( 'year-edge', $edge, true ) === true && in_array( 'year-before', $edge, true ) === false );
foreach( [ 'trick', 'only-english', 'year-edge', 'year-before' ] as $slug )
	\Nino\Elements::deleteElement( $appData, '/events/'. $slug, $slug === 'only-english' ? 'en_US' : 'de_DE' );
unset( $appData['./nino/elements/cache'] );
\Nino\Html::addFills( $appData, [ '[[/project/company/general/name]]' => 'Müller &amp; Söhne' ], '*' );
check( 'the name of the company is text with its entities read: the calendar is called Müller & Söhne', in_array( 'X-WR-CALNAME:Müller & Söhne', eventsLines( eventsServe( $appData, '/.events.ics' )[4] ), true ) === true );
\Nino\Html::addFills( $appData, [ '[[/project/company/general/name]]' => 'The Club' ], '*' );
foreach( array_keys( $addresses ) as $slug )
	\Nino\Elements::deleteElement( $appData, '/events/'. $slug, 'de_DE' );
unset( $appData['./nino/elements/cache'] );

$appData['./nino/locales/current'] = 'en_US';
$english = eventsServe( $appData, '/.events.ics' )[4];
check( 'the words are the page\'s language\'s, and what that language has none of is the native one\'s', in_array( 'SUMMARY:Soon', eventsEvent( $english, 'soon' ), true ) && in_array( 'LOCATION:The hall', eventsEvent( $english, 'soon' ), true ) && in_array( 'SUMMARY:Frühstück', eventsEvent( $english, 'ended-today' ), true ) );
$appData['./nino/locales/current'] = 'de_DE';

// The settings: the window and the name of the calendar and the switch of the feed
\Nino\Elements::insertElement( $appData, '/events/long-run', [ 'title' => 'Lang', 'start' => '2026-10-01T10:00', 'end' => '2026-12-05T18:00' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$saved = \Nino\Features::saveSettings( $appData, 'events', [ 'ahead' => '730', 'past' => '30', 'name' => 'Club, Kalender; 1' ] );
$windowed = eventsServe( $appData, '/.events.ics' )[4];
check( 'the settings are the window of the feed: 730 days ahead take in the date more than a year away, 30 days back leave out the old one but not a long one that ended within them - and the name is the calendar\'s, escaped', $saved === [] && eventsUids( $windowed ) === [ 'long-run', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'all-day', 'later', 'two-day', 'far' ]
	&& in_array( 'X-WR-CALNAME:Club\\, Kalender\; 1', eventsLines( $windowed ), true ) );
\Nino\Elements::deleteElement( $appData, '/events/long-run', 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$windowedBytes = eventsServe( $appData, '/.events.ics' )[4];
check( '...the file of that window is the one the Nino 1 feature wrote, and the list\'s upcoming dates have the same window, the past ones go back as far', eventsSame( 'feed.window', $windowedBytes, EVENTS_BYTES ) === true
	&& eventsList( $appData ) === [ 'running', 'at-now', 'soon', 'all-day', 'later', 'two-day', 'far' ] && eventsList( $appData, 'scope="past"' ) === [ 'just-over', 'ended-today', 'past-recent' ] );
check( '...a value out of its bounds is refused and changes nothing', count( \Nino\Features::saveSettings( $appData, 'events', [ 'ahead' => '0' ] ) ) === 1 && count( \Nino\Features::saveSettings( $appData, 'events', [ 'ahead' => '731' ] ) ) === 1 && count( \Nino\Features::saveSettings( $appData, 'events', [ 'past' => '3651' ] ) ) === 1
	&& \Nino\Features::setting( $appData, 'events', 'ahead' ) === 730 );
\Nino\Features::saveSettings( $appData, 'events', [ 'ahead' => '365', 'past' => '0', 'name' => '' ] );
check( 'the window is the days of the setting before the list looks: with the defaults back the feed is as it was', eventsServe( $appData, '/.events.ics' )[4] === $ics && eventsList( $appData ) === $upcoming );

check( 'with the feed switched off /.events.ics is a 404 and [event-feed] shows nothing, and the file of a single date is still there', \Nino\Features::saveSettings( $appData, 'events', [ 'feed' => '0' ] ) === []
	&& eventsServe( $appData, '/.events.ics' )[0] === 404 && eventsServe( $appData, '/.events.ics' )[4] === 'Not Found' && eventsRender( $appData, '[event-feed]' ) === ''
	&& eventsServe( $appData, '/.events/soon.ics' )[0] === 200 && eventsServe( $appData, '/.events.ics?k='. str_repeat( 'a', 64 ) )[0] === 404 );
\Nino\Features::saveSettings( $appData, 'events', [ 'feed' => '1' ] );
check( '...and on again', eventsServe( $appData, '/.events.ics' )[0] === 200 );

// --- The single date -------------------------------------------------------------------------------

echo "\nGET /.events/<slug>.ics - one date\n";

$single = eventsServe( $appData, '/.events/soon.ics' );
check( 'a public date is a calendar file with its one VEVENT, inline under its own name, for a shared cache', $single[0] === 200 && $single[1] === 'text/calendar; charset=utf-8' && $single[3] === 'inline; filename="soon.ics"' && $single[2] === 'public, max-age=3600'
	&& substr_count( $single[4], 'BEGIN:VEVENT' ) === 1 && eventsUids( $single[4] ) === [ 'soon' ] && in_array( 'SUMMARY:Bald', eventsEvent( $single[4], 'soon' ), true ) && str_starts_with( $single[4], "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n" ) === true );
$gone = eventsServe( $appData, '/.events/nowhere.ics' );
check( 'a date that is not there is a plain 404 that no cache keeps', $gone === [ 404, 'text/plain; charset=utf-8', 'no-store', '', 'Not Found' ] );
$refused = [];
foreach( [ '/.events/Soon.ics', '/.events/so_on.ics', '/.events/soon.txt', '/.events/soon', '/.events/soon.ics/more', '/.events/.ics', '/.events/soon.ICS', '/.events/broken.ics', '/.events/so%20on.ics', '/.events/..%2Fsoon.ics', '/.events/soon.ics%00', '/.events/%00.ics', '/.events/soon%0A.ics' ] as $uri )
	if( eventsServe( $appData, $uri ) !== $gone )
		$refused[] = $uri;
check( 'a name that is no slug, another extension, more path, a null byte and a date whose start is no moment are that very 404', $refused === [] );
check( 'the routes are the feature\'s own and are not written to the config of the project', isset( $appData['/nino/http/routes']['GET://.events.ics'], $appData['/nino/http/routes']['GET://.events/*'] ) === true
	&& array_filter( array_keys( (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) ), static fn( string $route ): bool => str_contains( $route, '.events' ) ) === [] );

// --- Who the dates are with -------------------------------------------------------------------------

echo "\n/events/organizer - the callback\n";

/**
 *	A listener for /events/organizer that answers what it is given, and counts its calls
 *
 *	@param		array 		&$appData
 *	@param		mixed			$answer				The array it answers (merged over the empty one), or anything else it answers as it is
 *	@param		array 		&$seen				The organizers it was handed, one for each call
 *
 *	@return 	void
 */
function eventsOrganizerListen( array &$appData, mixed $answer, array &$seen ): void {

	$seen = [];
	unset( $appData['./nino/callbacks'][\Nino\Modules\Events::ORGANIZER] );
	\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Events::ORGANIZER, static function( array &$appData, mixed &$organizer ) use ( $answer, &$seen ): mixed {

		$seen[] = $organizer;

		return is_array( $answer ) === true && is_array( $organizer ) === true ? $answer + $organizer : $answer;
	} );
}

$seen = [];
check( 'with nobody listening a calendar file says nothing of an organizer: no CONTACT, no ORGANIZER, no paragraph in the description', array_filter( eventsLines( $ics ), static fn( string $line ): bool => str_starts_with( $line, 'CONTACT' ) || str_starts_with( $line, 'ORGANIZER' ) ) === [] );

$full = [ 'name' => "Frau; Müller, Ö\r\nX:y", 'email' => 'owner@example.org', 'phone' => '+49 30 123', 'address' => "Weg 1\n10115 Berlin", 'url' => 'https://example.org/kontakt', 'label' => 'Termin bei' ];
eventsOrganizerListen( $appData, $full, $seen );
$feedWith = eventsServe( $appData, '/.events.ics' )[4];
$laterWith = eventsServe( $appData, '/.events/later.ics' )[4];
$soonWith = eventsServe( $appData, '/.events/soon.ics' )[4];
check( 'the listener is asked once for each file, handed the empty organizer and the account the file is for - nobody for a visitor', count( $seen ) === 3 && $seen[0] === [ 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'url' => '', 'account' => null ] && array_unique( array_column( $seen, 'account' ) ) === [ null ] );
check( 'a name makes the lines: CONTACT with the phone, ORGANIZER with the name as a parameter and the email as a mailto address, the paragraph at the end of the description, and the line break of a name is no line of its own',
	in_array( 'CONTACT:Frau\; Müller\, Ö\nX:y\, +49 30 123', eventsEvent( $laterWith, 'later' ), true ) && in_array( 'ORGANIZER;CN="Frau; Müller, Ö X:y":mailto:owner@example.org', eventsEvent( $laterWith, 'later' ), true )
	&& str_contains( implode( '', explode( "\r\n ", $laterWith ) ), 'DESCRIPTION:Erste Zeile\nZweite\, Zeile\; ok & gut\n- Punkt\n\nTermin bei Frau\; Müller\, Ö\nX:y · +49 30 123 · owner@example.org · Weg 1\n10115 Berlin' )
	&& array_filter( eventsLines( $laterWith ), static fn( string $line ): bool => str_starts_with( $line, 'X:' ) ) === [] );
check( '...the address of the organizer is never the place of a date, a date without a link has the url of the organizer and one with a link keeps its own', in_array( 'LOCATION:Halle 1\\, Hof', eventsEvent( $laterWith, 'later' ), true ) && in_array( 'URL:https://example.org/feier?a=1&b=2', eventsEvent( $laterWith, 'later' ), true )
	&& in_array( 'URL:https://example.org/kontakt', eventsEvent( $soonWith, 'soon' ), true ) && in_array( 'LOCATION:Halle', eventsEvent( $soonWith, 'soon' ), true ) );
check( '...the files are the ones the Nino 1 feature wrote for the same listener: the feed and two dates', eventsSame( 'feed.organizer', $feedWith, EVENTS_BYTES ) === true && eventsSame( 'single.later.organizer', $laterWith, EVENTS_BYTES ) === true && eventsSame( 'single.soon.organizer', $soonWith, EVENTS_BYTES ) === true );

eventsOrganizerListen( $appData, [ 'name' => 'Owner', 'email' => 'a@b.example?subject=x', 'phone' => "1\x00 2\x07", 'address' => '', 'url' => 'javascript:alert(1)' ], $seen );
$rough = eventsServe( $appData, '/.events/soon.ics' )[4];
check( 'an email that a mailto address would give a meaning, a url that is no http address and a control character are left out: no ORGANIZER line, no URL of the organizer, a phone of printable characters', array_filter( eventsLines( $rough ), static fn( string $line ): bool => str_starts_with( $line, 'ORGANIZER' ) ) === []
	&& in_array( 'CONTACT:Owner\, 1 2', eventsEvent( $rough, 'soon' ), true ) && array_filter( eventsEvent( $rough, 'soon' ), static fn( string $line ): bool => str_starts_with( $line, 'URL' ) ) === [] );
eventsOrganizerListen( $appData, [ 'name' => 'Owner', 'url' => 'javascript:alert(1)//https://x.example/' ], $seen );
check( '...and so is a url that only holds an http address further on', array_filter( eventsEvent( eventsServe( $appData, '/.events/soon.ics' )[4], 'soon' ), static fn( string $line ): bool => str_starts_with( $line, 'URL' ) ) === [] );
foreach( [ [], 'name', null, false, [ 'name' => '' ], [ 'name' => "\x00\x07" ], [ 'name' => [ 'a' ] ] ] as $answer ) {
	eventsOrganizerListen( $appData, $answer, $seen );
	if( array_filter( eventsLines( eventsServe( $appData, '/.events/soon.ics' )[4] ), static fn( string $line ): bool => str_starts_with( $line, 'CONTACT' ) || str_starts_with( $line, 'ORGANIZER' ) ) !== [] )
		$answer = 'broken';
	if( $answer === 'broken' )
		break;
}
check( 'an answer that is no array, or has no name, or a name of control characters alone, makes no organizer', $answer !== 'broken' );
eventsOrganizerListen( $appData, [ 'name' => 'Owner', 'label' => 'Veranstalter' ], $seen );
check( 'a label the listener gives is the word in front of the name, before the word of the unit', in_array( 'DESCRIPTION:Veranstalter Owner', eventsEvent( eventsServe( $appData, '/.events/soon.ics' )[4], 'soon' ), true ) === true );
eventsOrganizerListen( $appData, [ 'name' => 'Owner' ], $seen );
$appData['./nino/locales/current'] = 'en_US';
$wordEnglish = eventsEvent( eventsServe( $appData, '/.events/soon.ics' )[4], 'soon' );
$appData['./nino/locales/current'] = 'de_DE';
check( 'without a word of its own the organizer is named with the word of the unit in front, and that word is the language\'s', in_array( 'DESCRIPTION:Termin bei Owner', eventsEvent( eventsServe( $appData, '/.events/soon.ics' )[4], 'soon' ), true ) === true
	&& in_array( 'DESCRIPTION:Event at Owner', $wordEnglish, true ) === true );
unset( $appData['./nino/callbacks'][\Nino\Modules\Events::ORGANIZER] );

check( 'the hooks are the feature\'s own and are registered once each: the two routes and the hook of Members that removes an account', \Nino\Callbacks::registered( $appData, '/members/removed' ) === [ [ \Nino\Modules\Events::class, 'callbackRemoved' ] ]
	&& \Nino\Callbacks::registered( $appData, '/nino/http/response/GET://.events.ics' ) === [ [ \Nino\Modules\Events::class, 'callbackFeed' ] ] && \Nino\Callbacks::registered( $appData, '/nino/http/response/GET://.events/*' ) === [ [ \Nino\Modules\Events::class, 'callbackEvent' ] ] );

// --- The tokens of the feed links ---------------------------------------------------------------------

echo "\nEvents\\Tokens - the feed link of a member\n";

$tokens = '\\Nino\\Modules\\Events\\Tokens';
$ada = 'aaaaaaaaaaaa';
$bob = 'bbbbbbbbbbbb';
$eve = 'cccccccccccc';

check( 'no file is made by looking: a token is asked of an index that is not there, and nothing is made', $tokens::account( $appData, str_repeat( 'a', 64 ) ) === null && \Nino\Filesystem::fileExists( $appData, '/data/events.php' ) === false && $tokens::forget( $appData, [ $ada ] ) === true && \Nino\Filesystem::fileExists( $appData, '/data/events.php' ) === false );
$adaToken = $tokens::forAccount( $appData, $ada );
$data = eventsData( $appData );
check( 'a token is 64 hex characters, made on the first ask and the same on every ask after it', preg_match( '/^[a-f0-9]{64}$/D', $adaToken ) === 1 && $tokens::forAccount( $appData, $ada ) === $adaToken && count( eventsData( $appData )['tokens'] ?? [] ) === 1 );
check( '...the file keeps the sha256 of the token with the id of the account and the stamp of the clock in UTC, and a secret of 32 random bytes - and not the token', ( $data['format'] ?? 0 ) === 1 && preg_match( '/^[a-f0-9]{64}$/D', (string) ( $data['secret'] ?? '' ) ) === 1
	&& array_keys( $data['tokens'] ?? [] ) === [ hash( 'sha256', $adaToken ) ] && $data['tokens'][ hash( 'sha256', $adaToken ) ] === [ 'account' => $ada, 'created' => '2026-12-10T11:00:00Z' ]
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/events.php' ) ), $adaToken ) === false );
$bobToken = $tokens::forAccount( $appData, $bob );
$eveToken = $tokens::forAccount( $appData, $eve );
check( 'each account has a token of its own and the secret does not change', count( array_unique( [ $adaToken, $bobToken, $eveToken ] ) ) === 3 && count( eventsData( $appData )['tokens'] ?? [] ) === 3 && eventsData( $appData )['secret'] === $data['secret'] );
check( '...and Tokens says whom a token is for, and nobody for what is none or is not written as it was made', $tokens::account( $appData, $adaToken ) === $ada && $tokens::account( $appData, $bobToken ) === $bob
	&& array_filter( [ str_repeat( '0', 64 ), 'short', strtoupper( $adaToken ), '', $adaToken. "\n", ' '. $adaToken, $adaToken. 'x', substr( $adaToken, 1 ), "\x00". substr( $adaToken, 1 ) ], static fn( string $token ): bool => $tokens::account( $appData, $token ) !== null ) === [] );
check( '...the index is looked up by the sha256 of the token, which is why a token that is near to one that exists takes no more time to refuse than one that is far from it', str_contains( (string) file_get_contents( $dir. '/Tokens/Tokens.php' ), "\$state['tokens'][ hash( 'sha256', \$token ) ]['account']" ) === true
	&& preg_match( '/(?:strcmp|strncmp|===\s*\$token|\$token\s*===)/', (string) file_get_contents( $dir. '/Tokens/Tokens.php' ) ) === 0 );

// A file that is made anew, the secret with it: the links of before open nothing
\Nino\Filesystem::putFileContent( $appData, '/data/events.php', [] );
$newAda = $tokens::forAccount( $appData, $ada );
check( 'the file emptied, the next token is made from a new secret: another token for the same account, and the old links open nothing', $newAda !== $adaToken && $newAda !== str_repeat( '0', 64 ) && (string) ( eventsData( $appData )['secret'] ?? '' ) !== $data['secret']
	&& $tokens::account( $appData, $adaToken ) === null && $tokens::account( $appData, $newAda ) === $ada );
$adaToken = $newAda;
$bobToken = $tokens::forAccount( $appData, $bob );
$eveToken = $tokens::forAccount( $appData, $eve );

// The hook of Members: an account that is removed takes its tokens with it
$before = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/events.php' ) );
$told = [ 'id' => 'dddddddddddd', 'email' => 'dan@example.org', 'group' => 'board' ];
\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $told );
check( 'the hook /members/removed is heard: an account nobody has a token of leaves the file as it is, byte for byte', (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/events.php' ) ) === $before );
$odd = [];
foreach( [ [ 'id' => 5, 'email' => '', 'group' => '' ], [ 'id' => '', 'email' => '', 'group' => '' ], [ 'email' => 'x@example.org', 'group' => '' ], [ 'id' => $bob ], [ 'id' => $bob, 'email' => 'b@example.org' ], [ 'id' => $bob, 'email' => null, 'group' => 'staff' ], [], null, 'bbbbbbbbbbbb', [ [ 'id' => $bob ] ], false ] as $account ) {
	$copy = $account;
	\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $copy );
	if( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/events.php' ) ) !== $before )
		$odd[] = json_encode( $account );
}
check( '...and what is no account - an id that is no string, none, an empty one, a list, a string - takes no token out - the hook is fired as an array of id, email and group, and nothing else is heard', $odd === [] && ninoWarnings() === [] );
$told = [ 'id' => $bob, 'email' => 'bob@example.org', 'group' => 'staff' ];
\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $told );
check( 'an account that is removed has no token any more, the others have theirs, and the hook leaves nothing of the account behind but the id it was told', $tokens::account( $appData, $bobToken ) === null && $tokens::account( $appData, $adaToken ) === $ada && $tokens::account( $appData, $eveToken ) === $eve
	&& array_values( array_column( eventsData( $appData )['tokens'], 'account' ) ) === [ $ada, $eve ] );
check( '...Tokens::forget takes several at once and answers true, also for ones it does not know', $tokens::forget( $appData, [ $ada, $eve, 'unknown' ] ) === true && ( eventsData( $appData )['tokens'] ?? null ) === [] && $tokens::account( $appData, $adaToken ) === null );

// A file as the Nino 1 feature wrote it for two accounts (the clock stood at 1796900000): the tokens it made, the secret and the index
$wrote = <<<'FILE'
<?php return array (
  'format' => 1,
  'secret' => 'f65dc5bdc9835c1efadf6d72d0472111191aeed77e3be231de033c8c962ecbca',
  'tokens' =>[blank]
  array (
    '13bbab1bf0f36d81cf344f8c774d463cea2d86fc560e46ca140aa183d8cb609e' =>[blank]
    array (
      'account' => 'aaaaaaaaaaaa',
      'created' => '2026-12-10T10:53:20Z',
    ),
    '4db1ba52c0dcbc7f795090bc5e66c66d921fa25184b8a8846a511a5088665f4f' =>[blank]
    array (
      'account' => 'bbbbbbbbbbbb',
      'created' => '2026-12-10T10:53:20Z',
    ),
  ),
);
FILE;
// The index lines end in a blank, which a file of the repository may not: it is written here
$wrote = str_replace( '[blank]', ' ', $wrote );
$made = [ $ada => '428a32fc5e30a05e23956285f39e14949b43a84a0a5eb1e1faeb49f5ad3feb9e', $bob => '39f2506ae05cd2c7e8c6626c70d1987a7682380e8fad9b10d79ed2a5b6aee025' ];
file_put_contents( \Nino\Filesystem::path( $appData, '/data/events.php' ), $wrote );
$fileNow = static fn(): string => (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/events.php' ) );
check( 'a file of the feed tokens as the Nino 1 feature wrote it is read as it is: each token belongs to the account it was made for and the feature gives that very token again, without a write', $tokens::account( $appData, $made[$ada] ) === $ada && $tokens::account( $appData, $made[$bob] ) === $bob
	&& $tokens::forAccount( $appData, $ada ) === $made[$ada] && $tokens::forAccount( $appData, $bob ) === $made[$bob] && $fileNow() === $wrote );
check( '...asking for a token that nobody holds and taking out an account that has none leave the bytes as they are', $tokens::account( $appData, str_repeat( 'a', 64 ) ) === null && $tokens::forget( $appData, [ 'eeeeeeeeeeee' ] ) === true && $fileNow() === $wrote );
check( '...taking one account out leaves the format, the secret and the other entry as they were written', $tokens::forget( $appData, [ $ada ] ) === true && $tokens::account( $appData, $made[$ada] ) === null && $tokens::account( $appData, $made[$bob] ) === $bob
	&& ( eventsData( $appData )['format'] ?? 0 ) === 1 && ( eventsData( $appData )['secret'] ?? '' ) === 'f65dc5bdc9835c1efadf6d72d0472111191aeed77e3be231de033c8c962ecbca' && array_keys( eventsData( $appData )['tokens'] ) === [ '4db1ba52c0dcbc7f795090bc5e66c66d921fa25184b8a8846a511a5088665f4f' ] );

// --- The calendar file itself ----------------------------------------------------------------------------------------

echo "\nEvents\\Ics - the format\n";

$format = '\\Nino\\Modules\\Events\\Ics';

check( 'a TEXT value escapes the backslash first, then the semicolon, the comma and every kind of line break as \\n', $format::text( "a\\b;c,d\ne\r\nf\rg" ) === 'a\\\\b\;c\\,d\\ne\\nf\\ng' && $format::text( 'plain' ) === 'plain' );
check( '...a control character is left out, a byte that is no UTF-8 is replaced', $format::text( "a\x00b\x07c\x7Fd" ) === 'abcd' && mb_check_encoding( $format::text( "a\xFFb" ), 'UTF-8' ) === true && str_starts_with( $format::text( "a\xFFb" ), 'a' ) === true );
check( 'a line of 75 octets is not folded, a line of 76 is, the next line begins with one space which counts', $format::fold( str_repeat( 'a', 75 ) ) === str_repeat( 'a', 75 ) && $format::fold( str_repeat( 'a', 76 ) ) === str_repeat( 'a', 75 ). "\r\n a"
	&& $format::fold( str_repeat( 'a', 149 ) ) === str_repeat( 'a', 75 ). "\r\n ". str_repeat( 'a', 74 ) && $format::fold( str_repeat( 'a', 150 ) ) === str_repeat( 'a', 75 ). "\r\n ". str_repeat( 'a', 74 ). "\r\n a" );
$cut = [ str_repeat( 'ä', 60 ), str_repeat( 'a', 74 ). 'ä'. str_repeat( 'b', 10 ), str_repeat( '😀', 40 ), 'SUMMARY:'. str_repeat( '日本語', 30 ) ];
$cutProblems = [];
foreach( $cut as $line ) {
	$parts = explode( "\r\n", $format::fold( $line ) );
	if( max( array_map( 'strlen', $parts ) ) > 75 || array_filter( $parts, static fn( string $part ): bool => mb_check_encoding( $part, 'UTF-8' ) === false ) !== [] || str_replace( "\r\n ", '', $format::fold( $line ) ) !== $line || array_filter( array_slice( $parts, 1 ), static fn( string $part ): bool => $part[0] !== ' ' ) !== [] )
		$cutProblems[] = mb_substr( $line, 0, 12 );
}
check( '...a character of two, three or four bytes is never cut, and the lines joined are the line', $cutProblems === [] );

$berlin = new DateTimeZone( 'Europe/Berlin' );
$event = [ 'uid' => 'x@example.org', 'stamp' => new DateTimeImmutable( '2026-07-01 14:00', $berlin ), 'start' => new DateTimeImmutable( '2026-07-01 14:00', $berlin ), 'end' => new DateTimeImmutable( '2026-07-01 16:30', $berlin ), 'allDay' => false,
	'summary' => 'S, 1', 'location' => 'L; 2', 'description' => "D\n3", 'url' => "https://example.org/a b\r\n" ];

check( 'the UID is a TEXT value too', $format::event( [ 'uid' => 'a,b;c\\d@example.org' ] + $event )[1] === 'UID:a\\,b\;c\\\\d@example.org' );
check( 'an event is its lines: the moments in UTC with the Z, the text escaped, the address as it is without a space or a line break in it', $format::event( $event ) === [ 'BEGIN:VEVENT', 'UID:x@example.org', 'DTSTAMP:20260701T120000Z', 'DTSTART:20260701T120000Z', 'DTEND:20260701T143000Z', 'SUMMARY:S\\, 1', 'LOCATION:L\; 2', 'DESCRIPTION:D\\n3', 'URL:https://example.org/ab', 'END:VEVENT' ] );
check( '...without an end there is no DTEND, and a value that is empty has no line', $format::event( [ 'end' => null, 'summary' => '', 'location' => '', 'description' => '', 'url' => '' ] + $event ) === [ 'BEGIN:VEVENT', 'UID:x@example.org', 'DTSTAMP:20260701T120000Z', 'DTSTART:20260701T120000Z', 'END:VEVENT' ] );
check( '...a day is DATE values in the zone of the day - the end is the day after, the 31st of December is the 1st of January - and not converted', $format::event( [ 'allDay' => true, 'start' => new DateTimeImmutable( '2026-12-31 00:00', $berlin ), 'end' => null ] + $event )[3] === 'DTSTART;VALUE=DATE:20261231'
	&& $format::event( [ 'allDay' => true, 'start' => new DateTimeImmutable( '2026-12-31 00:00', $berlin ), 'end' => null ] + $event )[4] === 'DTEND;VALUE=DATE:20270101' );
check( 'a calendar without a name has no X-WR-CALNAME, and with events is each one between its BEGIN and END, every line with its CRLF', $format::calendar( '', [] ) === "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Nino//Events//EN\r\nCALSCALE:GREGORIAN\r\nEND:VCALENDAR\r\n"
	&& str_contains( $format::calendar( 'N', [ $event, $event ] ), "X-WR-CALNAME:N\r\nBEGIN:VEVENT\r\n" ) && substr_count( $format::calendar( 'N', [ $event, $event ] ), "BEGIN:VEVENT\r\n" ) === 2 && str_ends_with( $format::calendar( 'N', [ $event ] ), "END:VEVENT\r\nEND:VCALENDAR\r\n" ) );
check( '...a long line in it is folded', str_contains( $format::calendar( str_repeat( 'n', 100 ), [] ), "X-WR-CALNAME:". str_repeat( 'n', 62 ). "\r\n ". str_repeat( 'n', 38 ). "\r\n" ) );
check( 'an email that a mailto address would give a meaning - a percent code, a slash - is left out and a double quote of a name is none: the whole of the line is one URI and one parameter', array_filter( [ 'a%20b@example.org', 'a/b@example.org' ], static fn( string $email ): bool => $format::organizer( [ 'name' => 'N', 'email' => $email ] )['lines'] !== [ 'CONTACT:N' ] ) === []
	&& $format::organizer( [ 'name' => 'A "B" C', 'email' => 'a@example.org' ] )['lines'][0] === "ORGANIZER;CN=A 'B' C:mailto:a@example.org" );
check( 'an organizer is a name or nothing: without one there is no paragraph and no line, and the address of it is no place', $format::organizer( [ 'name' => '', 'email' => 'a@b.example' ] ) === null && $format::organizer( [ 'name' => " \x00 " ] ) === null
	&& $format::organizer( [ 'name' => 'N', 'address' => 'Street 1' ] ) === [ 'paragraph' => 'N · Street 1', 'lines' => [ 'CONTACT:N' ] ] );

// --- The source of the feature -------------------------------------------------------------------------------------------

echo "\nThe source of the feature\n";

$sources = [ $dir. '/Events.php', $dir. '/Ics/Ics.php', $dir. '/Tokens/Tokens.php' ];
$offences = [];

foreach( $sources as $source )
	foreach( token_get_all( (string) file_get_contents( $source ) ) as $token )
		if( is_array( $token ) === true && in_array( $token[0], [ T_ECHO, T_EXIT, T_PRINT, T_INLINE_HTML ], true ) === true )
			$offences[] = basename( $source ). ': '. token_name( $token[0] );

$code = implode( "\n", array_map( static fn( string $source ): string => (string) file_get_contents( $source ), $sources ) );
check( 'the classes write no output: no echo, print, exit or inline html - and call no header() or http_response_code()', count( $sources ) === 3 && $offences === [] && preg_match( '/\b(?:header|http_response_code|readfile|fpassthru)\s*\(/', $code ) === 0 );
check( '...no htmlspecialchars() without the flags the rules ask for, no network, no eval', preg_match_all( '/htmlspecialchars\(/', $code ) === preg_match_all( '/htmlspecialchars\([^;]*ENT_QUOTES \| ENT_SUBSTITUTE, \'UTF-8\'/', $code ) && preg_match( '/\b(?:curl_|fsockopen|file_get_contents\(\s*[\'"]https?|eval\()/', $code ) === 0 );
check( '...nothing of the feature names a path of the machine it was written on', preg_match( '~/home/|/Users/|C:\\\\~', $code. (string) file_get_contents( $dir. '/feature.php' ) ) === 0 );
check( '...the feature asks the Features whether Members is on, and the class of a feature that is off is no answer (see the second half, where it is loaded and never asked)', str_contains( $code, "\\Nino\\Features::get( \$appData, self::MEMBERS )['active']" ) === true );
check( '...every component answers finished markup through the register: the templates are in the folders, and the class puts no tag of its own into a string', array_filter( [ 'events', 'event-when', 'event-ics', 'event-feed' ], static fn( string $name ): bool => is_file( $dir. '/components/'. $name. '/template.tpl' ) === false ) === []
	&& preg_match( '/[\'"]<[a-z\/]/i', $code ) === 0 );

// --- Deactivation ----------------------------------------------------------------------------------------------------------------

echo "\nDeactivation\n";

$typeBefore = (string) file_get_contents( $typeFile );
$dataBefore = eventsData( $appData );
check( 'deactivation succeeds, nothing requires the feature', \Nino\Features::deactivate( $appData, 'events' ) === true );
check( '...the class is no longer listed, the routes were never written to config.php, and what the project has - the type, the dates, the words, the tokens - stays',
	in_array( '\\Nino\\Modules\\Events', (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] ), true ) === false
	&& array_filter( array_keys( (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) ), static fn( string $route ): bool => str_contains( $route, '.events' ) ) === []
	&& (string) file_get_contents( $typeFile ) === $typeBefore && count( (array) \Nino\Elements::queryElements( $appData, '/events', [], 'de_DE', [] ) ) === 15 && eventsData( $appData ) === $dataBefore
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/feature/events/when/today]]'] ?? '' ) === 'Heute' );
// The feature is off: its names are names nobody registered, and the log says so once for the call
$off = ninoSandbox( 'events-off' );
$off['./nino/locales/current'] = 'de_DE';
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
\Nino\Modules::callModules( $off, 'init' );
check( '...a call of its components is a name nobody registered: it draws nothing, nor what it holds, and the routes answer nothing', eventsRender( $off, '[events][event-when text="2026-12-24T18:30"][/events][event-when text="2026-12-24T18:30"][event-feed]' ) === ''
	&& isset( $off['/nino/http/routes']['GET://.events.ics'] ) === false && ( \Nino\Components::groups( $off )['events'] ?? null ) === null && ninoWarnings() !== [] );
\Nino\Filesystem::removeDir( ninoSandboxDir( $off ) );
check( '...and the feature can be activated again, at the version it was', \Nino\Features::activate( $appData, 'events' ) === true && ( \Nino\Features::get( $appData, 'events' )['installed'] ?? '' ) === '2.0.0' );

// --- The second half: Members beside it ----------------------------------------------------------------------------------------

echo "\nWith the Members feature beside it - a process of its own\n";

/*	A directory of features that holds Basis, a copy of this feature and a stand-in of Members: the two methods the feature asks of Members, with the
	signatures of the feature of the catalogue. Whom the stand-in knows and who is signed in are keys of $appData, and every ask is counted	*/
$stood = ninoSandboxDir( $appData ). '/stand';
mkdir( $stood. '/Members', 0755, true );
\Nino\Features::copyTree( $dir. '/../Basis', $stood. '/Basis' );
\Nino\Features::copyTree( $dir, $stood. '/Events' );
file_put_contents( $stood. '/Members/feature.php', <<<'PHP'
<?php
// A stand-in of the feature Members for the test of Events: the manifest of a feature that does nothing
return [
	'key'					=> 'members',
	'name'				=> [ 'en_US' => 'Members', 'de_DE' => 'Mitglieder' ],
	'description'	=> [ 'en_US' => 'A stand-in.', 'de_DE' => 'Ein Stellvertreter.' ],
	'category'		=> 'security',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
];
PHP );
file_put_contents( $stood. '/Members/Members.php', <<<'PHP'
<?php
declare(strict_types=1);

namespace Nino\Modules {

	// What Events asks of Members: who is signed in and one account by its id. The accounts are $appData['./events-test/accounts'],
	// the one signed in is $appData['./events-test/signed-in'], and every ask is counted in $appData['./events-test/asked']
	class Members {

		public static function current( array &$appData ): ?array {

			$appData['./events-test/asked'] = (int) ( $appData['./events-test/asked'] ?? 0 ) + 1;
			$id = $appData['./events-test/signed-in'] ?? null;
			$account = is_string( $id ) === true ? ( $appData['./events-test/accounts'][$id] ?? null ) : null;

			return is_array( $account ) === true && $account['active'] === true ? $account : null;
		}

		public static function account( array &$appData, string $id ): ?array {

			$appData['./events-test/asked'] = (int) ( $appData['./events-test/asked'] ?? 0 ) + 1;

			return $appData['./events-test/accounts'][$id] ?? null;
		}
	}
}
PHP );

$output = [];
exec( 'NINO_ROOT='. escapeshellarg( $root ). ' NINO_EVENTS_FEATURES='. escapeshellarg( $stood ). ' '. escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( __FILE__ ). ' 2>&1', $output, $status );
foreach( $output as $line )
	echo '  > ', $line, "\n";

preg_match( '/^(\d+) checks, (\d+) failed/m', implode( "\n", $output ), $counted );
check( 'the process ran the checks of the second half, all of them', $status === 0 && ( $counted[1] ?? '0' ) >= '35' && ( $counted[2] ?? '1' ) === '0' );

ninoDone( $appData );
