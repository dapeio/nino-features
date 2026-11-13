<?php
declare(strict_types=1);

/**
 *	Nino
 *	vcard-smoke.php		vCard: the manifest and the unit it brings, the start page and
 *										what happens where / is taken, what the page shows and what it
 *										leaves out, the details of the project it falls back to, the
 *										contact file against the format, the checks of the panel and
 *										what it refuses, and the card without the Legal module.
 *
 *	Usage: php features/Vcard/tests/vcard-smoke.php
 *	       NINO_ROOT=../nino php features/Vcard/tests/vcard-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/**
 *	A throwaway project: both languages, the Legal module on, the feature switched on
 *
 *	@param		callable|null	$before				Called with &$appData before the activation
 *	@param		bool			$legal				Whether the Legal module is listed
 *
 *	@return 	array
 */
function vcardSandbox( ?callable $before = null, bool $legal = true ): array {

	$appData = ninoSandbox( 'vcard' );
	$appData['/nino/dir'] = '';
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Components' ];
	if( $legal === true && class_exists( '\\Nino\\Modules\\Legal' ) === true )
		$appData['/nino/modules'][] = '\\Nino\\Modules\\Legal';
	$appData['./nino/locales/current'] = 'de_DE';
	\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

	if( $before !== null )
		$before( $appData );

	\Nino\Features::activate( $appData, 'vcard' );
	$appData['/nino/modules'][] = '\\Nino\\Modules\\Template';
	\Nino\Html::init( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

/** The facts of a project, as the wizard's step writes them, beside the words the feature brought */
function vcardFacts( array &$appData ): void {

	$write = static function( string $file, array $facts ) use ( &$appData ): void {
		$have = \Nino\Filesystem::getFileContent( $appData, $file, [] );
		\Nino\Filesystem::putFileContent( $appData, $file, array_merge( is_array( $have ) === true ? $have : [], $facts ) );
	};

	$write( '/text/global.php', [
		'[[/project/company/general/name]]'		=> 'Acme GmbH',
		'[[/project/company/contact/address]]'	=> "Street 1\n12345 City",
		'[[/project/company/contact/phone]]'		=> '+49 170 1234567',
		'[[/project/company/contact/email]]'		=> 'hello@acme.test',
		'[[/project/website/general/url]]'		=> 'www.acme.test',
		'[[/project/website/general/author]]'	=> 'SECRETAUTHOR',
	] );
	$write( '/text/de_DE.php', [ '[[/project/company/general/description]]' => 'Beschreibung der Firma.', '[[/project/company/contact/country]]' => 'Deutschland' ] );
	$write( '/text/en_US.php', [ '[[/project/company/general/description]]' => 'About the company.', '[[/project/company/contact/country]]' => 'Germany' ] );
}

/** One request of the panel, signed in with every right */
function vcardCall( array &$appData, string $action, array $data = [] ): array {
	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

/** A full valid set of details, to change one at a time */
function vcardPosted( array $over = [] ): array {
	return array_replace( [
		'picture' => 'auto', 'kind' => 'person', 'name' => 'Ada Lovelace', 'phone' => '+44 20 7946 0000', 'email' => 'ada@example.com', 'website' => 'ada.example.com',
		'address' => "1 Analytical Way\nLondon", 'map' => true, 'save' => true,
		'texts' => [ 'de_DE' => [ 'line' => 'Mathematikerin', 'text' => 'Rechnet gern.', 'hours' => 'Mo-Fr 9-17' ], 'en_US' => [ 'line' => 'Mathematician', 'text' => 'Likes to compute.', 'hours' => 'Mon-Fri 9-17' ] ],
		'links' => [ [ 'id' => 'a1', 'url' => 'https://example.com/ada', 'labels' => [ 'de_DE' => 'Profil', 'en_US' => 'Profile' ] ] ],
	], $over );
}

/** The page as a visitor gets it: the template of the unit, which holds [vcard] */
function vcardPage( array &$appData ): string {
	return \Nino\Html::renderHtml( $appData, '[template /templates/page-vcard]' );
}

/** What a refused command must leave as it is: the routes and the image slots of config.php */
function vcardSnapshot( array &$appData ): string {
	return json_encode( [ \Nino\AppData::stored( $appData, '/nino/http/routes', [] ), \Nino\AppData::stored( $appData, '/nino/html/images', [] ) ] );
}

/** The lines of a file, unfolded */
function vcardUnfold( string $file ): array {
	return explode( "\r\n", rtrim( (string) preg_replace( '/\r\n[ \t]/', '', $file ), "\r\n" ) );
}

echo "1. The manifest and the unit\n";

$manifest = \Nino\Features::manifest( dirname( __DIR__ ) );
check( 'the manifest validates without a warning', is_array( $manifest ) === true && ninoWarnings() === [] );
check( 'it names the class, the stylesheet and the one data file', is_file( dirname( __DIR__ ). '/Vcard.php' ) === true && $manifest['assets'] === [ '/features/Vcard/assets/vcard.css' ] && $manifest['data'] === [ '/data/vcard.php' ] && $manifest['version'] === '0.1.0' );

$appData = vcardSandbox();
$stored = \Nino\AppData::stored( $appData, '/nino/http/routes', [] );
check( 'activation records the version and lists the class', \Nino\Features::get( $appData, 'vcard' )['installed'] === '0.1.0' && in_array( '\\Nino\\Modules\\Vcard', $appData['/nino/modules'], true ) === true );
check( 'the unit copies the page template and puts the start page on the card', is_file( \Nino\Filesystem::path( $appData, '/templates/page-vcard.tpl' ) ) === true && ( $stored['GET://']['uri'] ?? '' ) === '/vcard' );
check( 'the words of the card are in both languages, and the details of the page', str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/text/en_US.php' ) ), '/feature/vcard/action/save' ) && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/text/de_DE.php' ) ), '/_nino/webpage/vcard/title' ) );
check( 'the card owns no second address while it is the start page, and the file has one', isset( $appData['/nino/http/routes']['GET://vcard'] ) === false && isset( $appData['/nino/http/routes']['GET://vcard.vcf'] ) === true );

echo "\n2. A start page that is taken\n";

$taken = vcardSandbox( static function( array &$appData ): void {
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
$stored = \Nino\AppData::stored( $taken, '/nino/http/routes', [] );
check( 'the route for / stays the project\'s', ( $stored['GET://']['uri'] ?? '' ) === '/home' );
check( 'the card is at /vcard instead, once, and the project\'s route there would be kept', ( $taken['/nino/http/routes']['GET://vcard']['uri'] ?? '' ) === '/vcard' );
[ , $screen ] = ( static function() use ( &$taken ): array { ninoUser( $taken, 'dev@example.com' ); return vcardCall( $taken, 'vcard/list' ); } )();
check( 'the panel says where the page is and what holds /', $screen['page']['state'] === 'page' && $screen['page']['path'] === '/vcard' && $screen['page']['takenUri'] === '/home' && $screen['page']['start']['moves'] === true && $screen['page']['start']['to'] === '/home' );

$own = vcardSandbox( static function( array &$appData ): void {
	$appData['/nino/http/routes']['GET://vcard'] = [ 'uri' => '/mine', 'body' => 'x' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => 'x' ];
} );
check( 'a project route at /vcard is never replaced', ( $own['/nino/http/routes']['GET://vcard']['uri'] ?? '' ) === '/mine' );

$moved = vcardSandbox( static function( array &$appData ): void {
	$appData['/nino/http/routes']['GET://card'] = [ 'uri' => '/vcard', 'body' => 'x' ];
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => 'x' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
check( 'a page the Routes panel already put elsewhere gets no second address', isset( $moved['/nino/http/routes']['GET://vcard'] ) === false );

echo "\n3. The panel: rights and checks\n";

$appData = vcardSandbox();
vcardFacts( $appData );
[ $status ] = vcardCall( $appData, 'vcard/list' );
check( 'without a session the panel answers nobody', $status === 401 || $status === 403 );
[ $status ] = vcardCall( $appData, 'vcard/save', vcardPosted() );
check( 'and saves nothing', ( $status === 401 || $status === 403 ) && \Nino\Filesystem::fileExists( $appData, '/data/vcard.php' ) === false );

ninoUser( $appData, 'nobody@example.com', [ '/_admin/elements/manage' ] );
check( 'an account without the permission is refused', vcardCall( $appData, 'vcard/save', vcardPosted() )[0] === 403 );

ninoUser( $appData, 'dev@example.com' );
[ $status, $body ] = vcardCall( $appData, 'vcard/list' );
check( 'the list answers the details, the project\'s own as the fallback, the languages and the limits', $status === 200 && $body['inherited']['name'] === 'Acme GmbH' && $body['inherited']['email'] === 'hello@acme.test'
	&& $body['inherited']['text']['en_US'] === 'About the company.' && $body['locales'] === [ 'de_DE', 'en_US' ] && $body['limits']['links'] === 20 );
check( 'the page is the start page, which no button is offered for, with the legal pages named', $body['page']['state'] === 'start' && $body['page']['path'] === '/' && $body['page']['start'] === null && $body['legal']['imprint'] === '/impressum' && $body['names']['de_DE'] !== '' );

$refused = [
	'a picture that is no choice'			=> [ vcardPosted( [ 'picture' => 'x' ] ), 'vcard_picture', 'picture' ],
	'a kind that is no kind'					=> [ vcardPosted( [ 'kind' => 'x' ] ), 'vcard_kind', 'kind' ],
	'a name too long'									=> [ vcardPosted( [ 'name' => str_repeat( 'n', 121 ) ] ), 'vcard_long', 'name' ],
	'a phone number that is none'			=> [ vcardPosted( [ 'phone' => 'call me maybe' ] ), 'vcard_phone', 'phone' ],
	'a phone number of four digits'		=> [ vcardPosted( [ 'phone' => '1234' ] ), 'vcard_phone', 'phone' ],
	'an e-mail with a name in it'			=> [ vcardPosted( [ 'email' => 'Ada <ada@example.com>' ] ), 'vcard_email', 'email' ],
	'an e-mail with a line break'			=> [ vcardPosted( [ 'email' => "ada@example.com\r\nBCC:x@y.z" ] ), 'vcard_email', 'email' ],
	'a website that is a script'			=> [ vcardPosted( [ 'website' => 'javascript:alert(1)' ] ), 'vcard_website', 'website' ],
	'a website without a dot'					=> [ vcardPosted( [ 'website' => 'intranet' ] ), 'vcard_website', 'website' ],
	'an address too long'							=> [ vcardPosted( [ 'address' => str_repeat( 'a', 241 ) ] ), 'vcard_long', 'address' ],
	'an address of six lines'					=> [ vcardPosted( [ 'address' => "1\n2\n3\n4\n5\n6" ] ), 'vcard_lines', 'address' ],
	'a line too long'									=> [ vcardPosted( [ 'texts' => [ 'de_DE' => [ 'line' => str_repeat( 'l', 121 ) ] ] ] ), 'vcard_long', 'texts.de_DE.line' ],
	'a text too long'									=> [ vcardPosted( [ 'texts' => [ 'en_US' => [ 'text' => str_repeat( 't', 1001 ) ] ] ] ), 'vcard_long', 'texts.en_US.text' ],
	'a language the site does not have'	=> [ vcardPosted( [ 'texts' => [ 'fr_FR' => [ 'line' => 'x' ] ] ] ), 'vcard_locale', 'texts' ],
	'a link with javascript:'					=> [ vcardPosted( [ 'links' => [ [ 'url' => 'javascript:alert(1)', 'labels' => [] ] ] ] ), 'vcard_link', 'links.0.url' ],
	'a link with data:'								=> [ vcardPosted( [ 'links' => [ [ 'url' => 'https://ok.test', 'labels' => [] ], [ 'url' => 'data:text/html,x', 'labels' => [] ] ] ] ), 'vcard_link', 'links.1.url' ],
	'a link with a space'							=> [ vcardPosted( [ 'links' => [ [ 'url' => 'https://a.test/x y', 'labels' => [] ] ] ] ), 'vcard_link', 'links.0.url' ],
	'a link of another scheme'				=> [ vcardPosted( [ 'links' => [ [ 'url' => 'ftp://a.test/x', 'labels' => [] ] ] ] ), 'vcard_link', 'links.0.url' ],
	'a link with no host'							=> [ vcardPosted( [ 'links' => [ [ 'url' => 'https:///x', 'labels' => [] ] ] ] ), 'vcard_link', 'links.0.url' ],
	'a link with a password'					=> [ vcardPosted( [ 'links' => [ [ 'url' => 'https://u:p@a.test/', 'labels' => [] ] ] ] ), 'vcard_link', 'links.0.url' ],
	'a link name too long'						=> [ vcardPosted( [ 'links' => [ [ 'url' => 'https://a.test', 'labels' => [ 'de_DE' => str_repeat( 'x', 61 ) ] ] ] ] ), 'vcard_long', 'links.0.labels.de_DE' ],
	'twenty-one links'								=> [ vcardPosted( [ 'links' => array_fill( 0, 21, [ 'url' => 'https://a.test', 'labels' => [] ] ) ] ), 'vcard_links', 'links' ],
];
foreach( $refused as $label => [ $posted, $code, $field ] ) {
	[ $status, $body ] = vcardCall( $appData, 'vcard/save', $posted );
	check( $label. ' is refused with '. $code. ' at '. $field. ', and nothing is written', $status === 400 && ( $body['code'] ?? '' ) === $code && ( $body['field'] ?? '' ) === $field && \Nino\Filesystem::fileExists( $appData, '/data/vcard.php' ) === false );
}

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$words = \Nino\Admin\Admin::textFills( $appData, '/features/Vcard/text', $locale );
	$missing = [];
	foreach( array_unique( array_merge( array_column( $refused, 1 ), [ 'vcard_start_taken' ] ) ) as $code )
		if( ( $words['[[/_admin/error/'. $code. ']]'] ?? '' ) === '' )
			$missing[] = $code;
	check( 'every code has its sentence in '. $locale. ( $missing === [] ? '' : ' - '. implode( ', ', $missing ) ), $missing === [] );
}

[ $status ] = vcardCall( $appData, 'vcard/save', vcardPosted( [ 'links' => array_fill( 0, 20, [ 'url' => 'mailto:ada@example.com', 'labels' => [] ] ) ] ) );
check( 'twenty links and mailto: and tel: are taken', $status === 200 && vcardCall( $appData, 'vcard/save', vcardPosted( [ 'links' => [ [ 'url' => 'tel:+4930123456', 'labels' => [] ] ] ] ) )[0] === 200 );

echo "\n4. What is stored\n";

[ $status, $body ] = vcardCall( $appData, 'vcard/save', vcardPosted( [ 'name' => "Ada\r\nLovelace  ", 'phone' => "+44 20\n7946 0000", 'website' => 'ada.example.com', 'texts' => [ 'de_DE' => [ 'line' => "Line\nbreak", 'text' => "A\r\n\r\n\r\n\r\nB", 'hours' => '' ] ] ] ) );
$file = \Nino\Filesystem::getFileContent( $appData, '/data/vcard.php', [] );
check( 'a line break in a one-line field becomes a space', $status === 200 && $file['name'] === 'Ada Lovelace' && $file['phone'] === '+44 20 7946 0000' && $file['texts']['de_DE']['line'] === 'Line break' );
check( 'a text keeps its paragraphs, no more than one empty line', $file['texts']['de_DE']['text'] === "A\n\nB" );
check( 'a website without a scheme is kept with https://, and the file has its format', $file['website'] === 'https://ada.example.com' && $file['format'] === 1 && $file['texts']['en_US'] === [ 'line' => '', 'text' => '', 'hours' => '' ] );

echo "\n5. The page\n";

vcardCall( $appData, 'vcard/save', vcardPosted( [ 'picture' => 'none' ] ) );
\Nino\Locales::useLocale( $appData, 'en_US' );
$html = vcardPage( $appData );
check( 'it shows the name, the line, the text and the hours of the language of the page', str_contains( $html, 'Ada Lovelace' ) && str_contains( $html, 'Mathematician' ) && str_contains( $html, 'Likes to compute.' ) && str_contains( $html, 'Mon-Fri 9-17' ) && str_contains( $html, 'Mathematikerin' ) === false );
check( 'the phone number and the e-mail are links that open the phone and the mail program', str_contains( $html, 'href="tel:+442079460000"' ) && str_contains( $html, 'href="mailto:ada@example.com"' ) );
check( 'the link carries its name, and a rel that tells the destination nothing', str_contains( $html, 'href="https://example.com/ada" rel="noopener noreferrer">Profile</a>' ) );
check( 'the address has a map link and no map', str_contains( $html, 'openstreetmap.org/search?query=1%20Analytical%20Way%2C%20London' ) && str_contains( $html, '<iframe' ) === false );
check( 'the button downloads the file, and the legal pages are linked', str_contains( $html, 'href="/vcard.vcf" download="ada-lovelace.vcf"' ) && str_contains( $html, 'href="/privacy"' ) && str_contains( $html, 'href="/imprint"' ) );
check( 'no picture, no node for it, and nothing is left unfilled', str_contains( $html, 'nino-vcard-picture' ) === false && preg_match( '/\[\[|\[[a-z-]+[ \]]/', $html ) !== 1 );
check( 'the structured data is a Person, with the line as the job and the company as the employer', preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $ld ) === 1 && ( $graph = json_decode( $ld[1], true ) ) !== null
	&& $graph['@type'] === 'Person' && $graph['jobTitle'] === 'Mathematician' && $graph['worksFor']['name'] === 'Acme GmbH' && $graph['sameAs'] === [ 'https://example.com/ada' ] );
check( 'no empty list, paragraph or heading is drawn', preg_match( '#<(ul|p|h2|li|a|span|address|nav)[^>]*></\1>#', $html ) !== 1 );

\Nino\Locales::useLocale( $appData, 'de_DE' );
check( 'the other language of the page has its own words', str_contains( vcardPage( $appData ), 'Mathematikerin' ) );

// A language without words falls back to the native one, then to the others
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'texts' => [ 'de_DE' => [ 'line' => 'Nur deutsch' ] ] ] ) );
\Nino\Locales::useLocale( $appData, 'en_US' );
check( 'a detail with no words in the language shows the native ones', str_contains( vcardPage( $appData ), 'Nur deutsch' ) );

vcardCall( $appData, 'vcard/save', vcardPosted( [ 'picture' => 'none', 'map' => false, 'save' => false ] ) );
$html = vcardPage( $appData );
check( 'the map link and the button can be switched off, and then nothing of them is drawn', str_contains( $html, 'nino-vcard-map' ) === false && str_contains( $html, 'nino-vcard-actions' ) === false && str_contains( $html, 'vcard.vcf' ) === false && str_contains( $html, '1 Analytical Way' ) );
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'picture' => 'none' ] ) );

echo "\n6. Markup, shortcodes and placeholders in the details are text\n";

$evil = '<b>x</b> [vcard] [[/project/website/general/author]] [button text="y"] #/project/company/contact/email#';
check( 'the details with markup in them are taken', vcardCall( $appData, 'vcard/save', vcardPosted( [ 'name' => $evil, 'texts' => [ 'en_US' => [ 'line' => $evil, 'text' => $evil, 'hours' => $evil ] ], 'links' => [ [ 'url' => 'https://a.test/?q=[[x]]&a=1', 'labels' => [ 'en_US' => substr( $evil, 0, 55 ) ] ] ] ] ) )[0] === 200 );
$html = vcardPage( $appData );
check( 'nothing of it is read as markup, a fill or a shortcode', str_contains( $html, '<b>x' ) === false && substr_count( $html, '&lt;b&gt;x&lt;/b&gt;' ) >= 5 && str_contains( $html, 'SECRETAUTHOR' ) === false
	&& str_contains( $html, '[button' ) === false && str_contains( $html, '&#91;vcard]' ) === true );
preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $ld );
check( 'and the structured data stays valid json with the brackets written out', json_decode( $ld[1], true ) !== null && str_contains( $ld[1], '[vcard]' ) === false && str_contains( $ld[1], '[' ) === true && str_contains( $ld[1], '</script' ) === false );

echo "\n7. Without details: what the project knows\n";

$fresh = vcardSandbox();
vcardFacts( $fresh );
\Nino\Locales::useLocale( $fresh, 'en_US' );
$html = vcardPage( $fresh );
check( 'a card nobody has filled in shows the name, the phone, the e-mail, the website and the address of the project', str_contains( $html, 'Acme GmbH' ) && str_contains( $html, '+49 170 1234567' ) && str_contains( $html, 'hello@acme.test' ) && str_contains( $html, 'acme.test' ) && str_contains( $html, 'Street 1' ) && str_contains( $html, 'About the company.' ) );
check( 'it is an Organization, there is no picture, no links, no hours', str_contains( $html, '"@type":"Organization"' ) && str_contains( $html, 'nino-vcard-picture' ) === false && str_contains( $html, 'nino-vcard-links' ) === false && str_contains( $html, 'nino-vcard-hours' ) === false );
check( 'it tells nothing of the system: no path, no key, no class name', preg_match( '#/home/|/tmp/|/private|\\\\Nino|/feature/vcard|/data/vcard#', $html ) !== 1 );

$value = \Nino\Filesystem::getFileContent( $fresh, '/text/global.php', [] );
$value['[[/project/company/general/name]]'] = 'Changed GmbH';
\Nino\Filesystem::putFileContent( $fresh, '/text/global.php', $value );
check( 'a change of the project shows at once - there is no copy', str_contains( vcardPage( $fresh ), 'Changed GmbH' ) );
ninoUser( $fresh, 'dev@example.com' );
vcardCall( $fresh, 'vcard/save', vcardPosted( [ 'name' => '', 'phone' => '', 'email' => '', 'website' => '', 'address' => '', 'texts' => [], 'links' => [] ] ) );
check( 'a detail left empty in the panel falls back again', str_contains( vcardPage( $fresh ), 'Changed GmbH' ) );
vcardCall( $fresh, 'vcard/save', vcardPosted( [ 'name' => 'Own Name', 'phone' => '', 'email' => '', 'website' => '', 'address' => '', 'texts' => [], 'links' => [] ] ) );
$html = vcardPage( $fresh );
check( 'a detail of the card wins over the project, which stays as it was', str_contains( $html, 'Own Name' ) && str_contains( \Nino\Filesystem::getFileContent( $fresh, '/text/global.php', [] )['[[/project/company/general/name]]'], 'Changed GmbH' ) );

$empty = vcardSandbox();
$html = vcardPage( $empty );
check( 'a project that knows nothing and a card with nothing say so in one sentence: no heading, no list, no data, no empty node', str_contains( $html, 'nino-vcard-empty' ) && str_contains( $html, '<h2' ) === false && str_contains( $html, '<ul' ) === false && str_contains( $html, 'ld+json' ) === false && str_contains( $html, 'nino-vcard-actions' ) === false && preg_match( '#<(ul|p|h2|li|a|span|address|nav)[^>]*></\1>#', $html ) !== 1 );
check( '...and it keeps the legal links, which a page of the website owes a visitor', str_contains( $html, 'nino-vcard-legal' ) === class_exists( '\\Nino\\Modules\\Legal' ) );

echo "\n8. The contact file\n";

$appData = vcardSandbox();
vcardFacts( $appData );
ninoUser( $appData, 'dev@example.com' );
$nasty = "Ada; \"Countess\", of \\ Lovelace\nEND:VCARD\nBEGIN:VCARD";
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'name' => 'Ada Lovelace', 'texts' => [ 'de_DE' => [ 'line' => $nasty, 'text' => "Zeile eins, mit Komma; und Semikolon\\\nZeile zwei\r\nBEGIN:VCARD", 'hours' => 'Mo-Fr' ] ], 'links' => [ [ 'url' => 'https://example.com/a,b;c', 'labels' => [ 'de_DE' => "Pro;fil,\nEND:VCARD" ] ] ] ] ) );
$file = \Nino\Modules\Vcard::file( $appData );
$lines = vcardUnfold( $file );
check( 'every line ends in CRLF and the file begins and ends as a card', str_ends_with( $file, "\r\n" ) && preg_match( '/(?<!\r)\n/', $file ) !== 1 && $lines[0] === 'BEGIN:VCARD' && $lines[1] === 'VERSION:3.0' && end( $lines ) === 'END:VCARD' );
check( 'it has the one card, whatever the details held', count( array_keys( $lines, 'BEGIN:VCARD', true ) ) === 1 && count( array_keys( $lines, 'END:VCARD', true ) ) === 1 );
check( 'the name is split at its last word, FN is the whole name', in_array( 'N:Lovelace;Ada;;;', $lines, true ) && in_array( 'FN:Ada Lovelace', $lines, true ) );
check( 'the company is the organization of a person, the title is the line, masked', in_array( 'ORG:Acme GmbH', $lines, true ) && in_array( 'TITLE:Ada\; "Countess"\, of \\\\ Lovelace END:VCARD BEGIN:VCARD', $lines, true ) );
check( 'phone and e-mail are typed, the website a URL, the address masked with its country', in_array( 'TEL;TYPE=WORK,VOICE:+44 20 7946 0000', $lines, true ) && in_array( 'EMAIL;TYPE=INTERNET,WORK:ada@example.com', $lines, true ) && in_array( 'URL:https://ada.example.com', $lines, true ) && in_array( 'ADR;TYPE=WORK:;;1 Analytical Way\nLondon;;;;', $lines, true ) );
check( 'a link is a labelled item, its address unmasked and its name masked', in_array( 'item1.URL:https://example.com/a,b;c', $lines, true ) && in_array( 'item1.X-ABLabel:Pro\;fil\, END:VCARD', $lines, true ) );
check( 'the note holds text and hours, with a break as \n and a backslash masked', preg_grep( '/^NOTE:Zeile eins\\\\, mit Komma\\\\; und Semikolon\\\\\\\\\\\\nZeile zwei\\\\nBEGIN:VCARD\\\\nMo-Fr$/', $lines ) !== [] );
check( 'no detail started a property of its own: every line is a known property', array_filter( $lines, static fn( string $line ): bool => preg_match( '/^(BEGIN:VCARD|VERSION:3\.0|N:|FN:|ORG:|TITLE:|TEL;TYPE=WORK,VOICE:|EMAIL;TYPE=INTERNET,WORK:|URL:|ADR;TYPE=WORK:|item\d\.URL:|item\d\.X-ABLabel:|NOTE:|X-ABShowAs:COMPANY|END:VCARD)/', $line ) !== 1 ) === [] );
check( 'there is no photo in it', str_contains( $file, 'PHOTO' ) === false );

$long = [ 'name' => 'Ada Lovelace', 'kind' => 'person', 'company' => '', 'line' => '', 'phone' => '', 'email' => '', 'website' => '', 'address' => '', 'country' => '', 'text' => str_repeat( 'Überlänge ', 40 ). '€', 'hours' => '', 'links' => [] ];
$folded = \Nino\Modules\Vcard::compose( $long );
$parts = explode( "\r\n", rtrim( $folded, "\r\n" ) );
$tooLong = array_filter( $parts, static fn( string $part ): bool => strlen( $part ) > 75 );
check( 'a long line is folded at 75 octets, the continuation starting with one space', $tooLong === [] && count( array_filter( $parts, static fn( string $part ): bool => str_starts_with( $part, ' ' ) ) ) >= 5 );
check( 'no character of UTF-8 is cut by a fold, and unfolding gives the text back', preg_match( '//u', implode( '', $parts ) ) === 1 && implode( '', array_map( static fn( string $part ): string => preg_match( '//u', $part ) === 1 ? '' : 'x', $parts ) ) === '' && str_contains( implode( '', vcardUnfold( $folded ) ), str_repeat( 'Überlänge ', 40 ). '€' ) );

check( 'the file of a card without a name is nothing', \Nino\Modules\Vcard::compose( [ 'name' => '' ] + $long ) === '' );
check( 'an organization is one name, not split, and marked as a company', in_array( 'N:Acme GmbH;;;;', vcardUnfold( \Nino\Modules\Vcard::compose( [ 'name' => 'Acme GmbH', 'kind' => 'organization' ] + $long ) ), true ) && str_contains( \Nino\Modules\Vcard::compose( [ 'name' => 'Acme GmbH', 'kind' => 'organization' ] + $long ), "X-ABShowAs:COMPANY\r\n" ) );

// Even a file somebody edited by hand cannot bring an address that is not allowed, or a line break
\Nino\Filesystem::putFileContent( $appData, '/data/vcard.php', [ 'format' => 1, 'name' => "Eve\r\nTEL:666", 'email' => "e@x.test\r\nBCC:y@z.test", 'phone' => "+49 1234\r\nX:1", 'links' => [ [ 'url' => 'javascript:alert(1)', 'labels' => [] ], [ 'url' => "https://a.test/\r\nX-EVIL:1", 'labels' => [] ] ] ] );
$file = \Nino\Modules\Vcard::file( $appData );
check( 'a data file edited by hand brings no property, no link that is not allowed', preg_grep( '/^TEL/', vcardUnfold( $file ) ) === [] && str_contains( $file, 'BCC' ) === false && str_contains( $file, 'X-EVIL' ) === false && str_contains( $file, 'javascript' ) === false && str_contains( $file, 'FN:Eve TEL:666' ) === true );

$request = [];
ninoVisit( $appData, '/vcard.vcf' );
$request = \Nino\Http::getRequest( $appData ) ?: [];
check( 'the file route answers as a download with the right type, and is no page of the sitemap', ( $appData['/nino/http/routes']['GET://vcard.vcf']['header']['Content-Type'] ?? '' ) === 'text/vcard; charset=utf-8' && \Nino\Http::answersHtml( $appData['/nino/http/routes']['GET://vcard.vcf'] ) === false );
$request = [ '/nino/http/response' => [ 'header' => [], 'body' => '', 'statusCode' => 200 ] ];
\Nino\Modules\Vcard::callbackFile( $appData, $request );
check( 'the callback writes the file itself, as an attachment named after the card, not to be cached', str_starts_with( $request['/nino/http/response']['body'], "BEGIN:VCARD\r\n" ) && str_starts_with( $request['/nino/http/response']['header']['Content-Disposition'], 'attachment; filename="' ) && str_ends_with( $request['/nino/http/response']['header']['Content-Disposition'], '.vcf"' ) && $request['/nino/http/response']['header']['Cache-Control'] === 'no-cache' );

echo "\n9. The photo\n";

$appData = vcardSandbox();
ninoUser( $appData, 'dev@example.com' );
$image = imagecreatetruecolor( 900, 600 );
ob_start();
imagepng( $image );
$png = (string) ob_get_clean();
$upload = static function( string $bytes ) use ( &$appData ): array {
	$tmp = tempnam( sys_get_temp_dir(), 'nino-vcard' );
	file_put_contents( $tmp, $bytes );
	$_FILES = [ 'file' => [ 'name' => 'p.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ] ];
	$result = vcardCall( $appData, 'vcard/upload' );
	$_FILES = [];
	@unlink( $tmp );
	return $result;
};
[ $status, $body ] = $upload( 'this is no image' );
check( 'a file that is no image is refused, and no slot is made for it', $status === 400 && \Nino\Images::getSlot( $appData, '/vcard/photo' ) === false );
[ $status, $body ] = $upload( $png );
$slot = \Nino\Images::getSlot( $appData, '/vcard/photo' );
check( 'a photo makes the slot, 480 pixels square, and is stored cut to it', $status === 200 && is_array( $slot ) === true && $slot['width'] === 480 && $slot['height'] === 480 && is_string( $slot['filename'] ) === true && $body['photo']['url'] !== null && \Nino\Images::dimensions( $appData, $slot['filename'] ) === [ 'width' => 480, 'height' => 480 ] );
$first = $slot['filename'];
check( 'the slot is named in the language of the project, which is German here', $slot['label'] === 'vCard-Foto' );
$html = vcardPage( $appData );
check( 'the card shows the photo through the slot, with the description of the slot as its alt', str_contains( $html, 'nino-vcard-picture' ) && str_contains( $html, 'alt=""' ) );
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'alt' => [ 'de_DE' => 'Ada am Schreibtisch', 'en_US' => '' ] ] ) );
check( 'its alt text is kept in the slot, per language, and an empty one is none', \Nino\Images::getSlot( $appData, '/vcard/photo' )['alt'] === [ 'de_DE' => 'Ada am Schreibtisch' ] && str_contains( vcardPage( $appData ), 'alt="Ada am Schreibtisch"' ) );
check( 'the contact file never carries it', str_contains( \Nino\Modules\Vcard::file( $appData ), 'PHOTO' ) === false );
[ $status ] = vcardCall( $appData, 'vcard/save', vcardPosted( [ 'picture' => 'logo' ] ) );
check( 'the choice "logo" shows no photo, and with no logo nothing', str_contains( vcardPage( $appData ), 'nino-vcard-picture' ) === false );
[ $status, $body ] = vcardCall( $appData, 'vcard/remove' );
$slot = \Nino\Images::getSlot( $appData, '/vcard/photo' );
check( 'removing the photo keeps the slot and takes the file, only the one the slot made', $status === 200 && $slot['filename'] === null && is_file( \Nino\Filesystem::path( $appData, '/public/images/'. $first ) ) === false );
check( 'removing it again is no failure', vcardCall( $appData, 'vcard/remove' )[0] === 200 );

echo "\n10. Without the Legal module\n";

$nolegal = vcardSandbox( null, false );
vcardFacts( $nolegal );
$html = vcardPage( $nolegal );
check( 'the card shows no link to an imprint or a privacy policy, and no empty nav', str_contains( $html, 'nino-vcard-legal' ) === false && str_contains( $html, 'Acme GmbH' ) );
ninoUser( $nolegal, 'dev@example.com' );
[ , $body ] = vcardCall( $nolegal, 'vcard/list' );
check( 'the panel says so', $body['legal'] === [ 'active' => false, 'imprint' => '', 'privacy' => '' ] );

echo "\n11. The panel and the feature's end\n";

$appData = vcardSandbox();
ninoUser( $appData, 'dev@example.com' );
check( 'the panel is there while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['vcard'] ) === true );
$before = \Nino\Filesystem::getFileContent( $appData, '/data/vcard.php', [] );
vcardCall( $appData, 'vcard/save', vcardPosted() );
$kept = \Nino\Filesystem::getFileContent( $appData, '/data/vcard.php', [] );
check( 'deactivation takes the panel away and keeps the details, the page template and the route', \Nino\Features::deactivate( $appData, 'vcard' ) === true && isset( \Nino\Admin\Admin::panels( $appData )['vcard'] ) === false
	&& \Nino\Filesystem::getFileContent( $appData, '/data/vcard.php', [] ) === $kept && is_file( \Nino\Filesystem::path( $appData, '/templates/page-vcard.tpl' ) ) === true && ( \Nino\AppData::stored( $appData, '/nino/http/routes', [] )['GET://']['uri'] ?? '' ) === '/vcard' );
check( 'activating again changes no route and no word the project has', \Nino\Features::activate( $appData, 'vcard' ) === true && ( \Nino\AppData::stored( $appData, '/nino/http/routes', [] )['GET://']['uri'] ?? '' ) === '/vcard' );

echo "\n12. Every action is closed to a visitor and to an account without the right\n";

/** An upload, the way php hands one over */
function vcardUpload( array &$appData, string $bytes ): array {
	$tmp = tempnam( sys_get_temp_dir(), 'nino-vcard' );
	file_put_contents( $tmp, $bytes );
	$_FILES = [ 'file' => [ 'name' => 'p.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ] ];
	$result = vcardCall( $appData, 'vcard/upload' );
	$_FILES = [];
	@unlink( $tmp );
	return $result;
}

$image = imagecreatetruecolor( 100, 100 );
ob_start();
imagepng( $image );
$png = (string) ob_get_clean();

$closed = vcardSandbox( static function( array &$appData ): void {
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
$actions = [ 'vcard/list', 'vcard/save', 'vcard/upload', 'vcard/remove', 'vcard/start' ];
foreach( [ 'without a session' => null, 'without the right' => [ '/_admin/elements/manage' ] ] as $who => $perms ) {

	if( $perms !== null )
		ninoUser( $closed, 'nobody@example.com', $perms );

	$config = vcardSnapshot( $closed );

	foreach( $actions as $action ) {
		[ $status ] = $action === 'vcard/upload' ? vcardUpload( $closed, $png ) : vcardCall( $closed, $action, vcardPosted() );
		check( $action. ' '. $who. ' is refused', $perms === null ? ( $status === 401 || $status === 403 ) : $status === 403 );
	}

	check( 'nothing was written '. $who. ': no details, no slot, no photo, config.php as it was', \Nino\Filesystem::fileExists( $closed, '/data/vcard.php' ) === false && \Nino\Images::getSlot( $closed, '/vcard/photo' ) === false
		&& vcardSnapshot( $closed ) === $config );
}

echo "\n13. Using the card as the start page\n";

$project = vcardSandbox( static function( array &$appData ): void {
	\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [ '[[/_nino/webpage/home/uri]]' => '/', '[[/_nino/webpage/home/name]]' => 'Startseite', '[[/_nino/webpage/contact/uri]]' => '/kontakt' ] );
	\Nino\Filesystem::putFileContent( $appData, '/templates/page-home.tpl', '<p>home</p>' );
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[template /templates/page-home]', 'navs' => [ 'main' => 5, 'footer' => 5 ], 'locale' => 'de_DE' ];
	$appData['/nino/http/routes']['GET://kontakt'] = [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]', 'navs' => [ 'main' => 10 ] ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
ninoUser( $project, 'dev@example.com' );
$old = $project['/nino/http/routes']['GET://'];
[ , $screen ] = vcardCall( $project, 'vcard/list' );
check( 'the panel offers the button where the card is not on /, and says where the page that holds / would go', $screen['page']['start'] === [ 'blocked' => false, 'reason' => '', 'from' => '/home', 'name' => 'Startseite', 'to' => '/home', 'moves' => true, 'gone' => [ '/vcard' ] ] && $screen['page']['state'] === 'page' );

[ $status, $body ] = vcardCall( $project, 'vcard/start' );
$stored = \Nino\AppData::stored( $project, '/nino/http/routes', [] );
check( 'the command answers, and says what moved where', $status === 200 && $body['moved'] === [ 'from' => '/home', 'to' => '/home', 'name' => 'Startseite' ] && $body['page']['state'] === 'start' && $body['page']['start'] === null );
check( '/ is the card\'s route, the page that held it is at /home whole - its Element-URI, its body, its menus and its language', ( $stored['GET://']['uri'] ?? '' ) === '/vcard' && ( $stored['GET://home'] ?? [] ) === $old );
check( 'the card has no second address, in the file or in the request, and the other routes of the project are as they were', isset( $stored['GET://vcard'] ) === false && isset( $project['/nino/http/routes']['GET://vcard'] ) === false && $stored['GET://kontakt']['navs'] === [ 'main' => 10 ] );
check( '/ answers the card, /home answers the old page, in the file and in this request', \Nino\Http::requestRoute( $project, '/', 'GET' )['uri'] === '/vcard' && \Nino\Http::requestRoute( $project, '/home', 'GET' )['uri'] === '/home' && ( $project['/nino/http/routes']['GET://']['uri'] ?? '' ) === '/vcard' );
check( 'the fill that names the address of the moved page follows it, and no other fill is touched', \Nino\Filesystem::getFileContent( $project, '/text/global.php', [] )['[[/_nino/webpage/home/uri]]'] === '/home' && \Nino\Filesystem::getFileContent( $project, '/text/global.php', [] )['[[/_nino/webpage/contact/uri]]'] === '/kontakt' );
check( 'a menu finds the page by its Element-URI: the address of /home is the one of its route', \Nino\Http::findRouteUri( $project, '/home', 'de_DE' ) === 'GET://home' && \Nino\Http::findRouteUri( $project, '/vcard', 'de_DE' ) === 'GET://' );
$after = (string) file_get_contents( \Nino\Filesystem::path( $project, '/config.php' ) );
[ $status, $body ] = vcardCall( $project, 'vcard/start' );
check( 'asking again changes nothing', $status === 200 && $body['moved'] === [ 'from' => '', 'to' => '', 'name' => '' ] && (string) file_get_contents( \Nino\Filesystem::path( $project, '/config.php' ) ) === $after );

$moved = \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/home', 'body' => 'h' ], 'GET://card' => [ 'uri' => '/vcard', 'body' => 'c' ] ], [] );
check( 'the table is one value: the new / and the moved page are in it together, so one write puts both - and the command writes config.php once', $moved['routes'] === [ 'GET://' => [ 'uri' => '/vcard', 'body' => 'c' ], 'GET://home' => [ 'uri' => '/home', 'body' => 'h' ] ]
	&& preg_match( '/function apiStart(.*?)public static function/s', (string) file_get_contents( dirname( __DIR__ ). '/Admin/Admin.php' ), $source ) === 1 && substr_count( $source[1], "mutate( \$appData, '/config.php'" ) === 1 );
check( 'a card that was put on another address of its own leaves it: one address only', isset( $moved['routes']['GET://card'] ) === false );
check( '...and the plan names the address that stops answering, not a fixed one', $moved['gone'] === [ '/card' ] && \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/home', 'body' => 'h' ], 'GET://karte' => [ 'uri' => '/vcard', 'body' => 'c', 'locale' => 'de_DE' ], 'GET://card' => [ 'uri' => '/vcard', 'body' => 'c', 'locale' => 'en_US' ] ], [] )['gone'] === [ '/karte', '/card' ] && \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/home', 'body' => 'h' ] ], [] )['gone'] === [] );

$free = \Nino\Modules\Vcard::takeStart( [], [ 'GET://impressum' => [] ] );
check( 'where / is free the card takes it, and nothing moves', $free['routes']['GET://']['uri'] === '/vcard' && $free['to'] === '' && count( $free['routes'] ) === 1 );
check( 'a route a module answers / with is not the project\'s to move', \Nino\Modules\Vcard::takeStart( [], [ 'GET://' => [ 'uri' => '/m' ] ] ) === [ 'error' => 'taken' ] );
check( 'a table with the page that holds / and the routes of the modules: the address of a module is not free - in the request, not in the file', \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/x', 'body' => 'x' ] ], [ 'GET://home' => [] ] )['to'] === '/start'
	&& \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/x', 'body' => 'x' ], 'GET://home' => [], 'GET://start' => [] ], [] )['to'] === '/x' );
check( 'an address a page cannot have is no candidate even where it is the Element-URI of the page that moves', \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/features', 'body' => 'x' ], 'GET://home' => [], 'GET://start' => [] ], [] )['to'] === '/startseite' );

$busy = [ 'GET://' => [ 'uri' => '/old', 'body' => 'o' ], 'GET://home' => [], 'GET://start/*' => [], 'GET://old' => [], 'GET://startseite' => [], 'GET://index' => [], 'GET://start-page' => [], 'GET://start-2' => [], 'GET://start-3' => [] ];
check( 'where no address is free the command is refused, whole', \Nino\Modules\Vcard::takeStart( $busy, [] ) === [ 'error' => 'taken' ] );
check( 'an address that cannot be a page, or is a subtree of another, is no candidate', \Nino\Modules\Vcard::freePath( [], '/_admin' ) === '/home' && \Nino\Modules\Vcard::freePath( [ 'GET://home' => [], 'GET://start' => [] ], '/_admin' ) === '/startseite' && \Nino\Modules\Vcard::freePath( [ 'GET://home/*' => [] ], '/x' ) === '/start' );

$full = vcardSandbox( static function( array &$appData ) use ( $busy ): void {
	$appData['/nino/http/routes'] = array_merge( (array) ( $appData['/nino/http/routes'] ?? [] ), $busy );
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
ninoUser( $full, 'dev@example.com' );
$config = vcardSnapshot( $full );
[ , $screen ] = vcardCall( $full, 'vcard/list' );
[ $status, $body ] = vcardCall( $full, 'vcard/start' );
check( 'the command answers 409 with its code and its sentence in both languages, and config.php is as it was', $status === 409 && $body['code'] === 'vcard_start_taken' && vcardSnapshot( $full ) === $config
	&& $screen['page']['start']['blocked'] === true && ( \Nino\AppData::stored( $full, '/nino/http/routes', [] )['GET://']['uri'] ?? '' ) === '/old' );

// What the panel says follows the routes, whatever somebody did in the Routes panel
$states = vcardSandbox( static function( array &$appData ): void {
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/vcard', 'body' => '[template /templates/page-other]' ];
	\Nino\Filesystem::putFileContent( $appData, '/templates/page-other.tpl', '<p>other</p>' );
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
ninoUser( $states, 'dev@example.com' );
[ , $screen ] = vcardCall( $states, 'vcard/list' );
check( 'a route for / that carries the card\'s name and another template is not said to show it, and no button pretends to mend it', $screen['page']['state'] === 'detached' && $screen['page']['path'] === '/' && $screen['page']['start'] === null );
$states['/nino/http/routes']['GET://'] = [ 'uri' => '/vcard', 'body' => '[template /templates/page-vcard]' ];
check( '...and with the template back it is the start page', \Nino\Modules\Vcard::placement( $states )['state'] === 'start' );
$states['/nino/http/routes'] = [ 'GET://card' => [ 'uri' => '/vcard', 'body' => '<p>x</p>' ] ];
check( 'a page of its own that draws something else is detached, with its address', \Nino\Modules\Vcard::placement( $states ) === [ 'state' => 'detached', 'path' => '/card', 'taken' => '' ] );
$states['/nino/http/routes'] = [];
check( 'no route at all is none, and the card can take /', \Nino\Modules\Vcard::placement( $states )['state'] === 'none' && isset( \Nino\Modules\Vcard::takeStart( [], [] )['routes'] ) );
$states['/nino/http/routes'] = [ 'GET://' => [ 'uri' => '/vcard', 'body' => '[vcard]' ] ];
check( 'a body that holds the shortcode itself shows the card', \Nino\Modules\Vcard::placement( $states )['state'] === 'start' );

echo "\n14. What the file and the page make of a name, a number, an address\n";

$person = static fn( string $name ): array => [ 'name' => $name, 'kind' => 'person', 'company' => '', 'line' => '', 'phone' => '', 'email' => '', 'website' => '', 'address' => '', 'country' => '', 'text' => '', 'hours' => '', 'links' => [] ];
$nLine = static fn( string $name ): string => (string) ( preg_grep( '/^N:/', vcardUnfold( \Nino\Modules\Vcard::compose( $person( $name ) ) ) )[2] ?? '' );
check( 'a title at the start of a name is the prefix of N, from a short fixed list', $nLine( 'Dr. Ada Lovelace' ) === 'N:Lovelace;Ada;;Dr.;' && $nLine( 'Prof. Dr. Ada Lovelace' ) === 'N:Lovelace;Ada;;Prof. Dr.;' && $nLine( 'dr Ada Lovelace' ) === 'N:Lovelace;Ada;;dr;' );
check( 'nothing else is guessed: a title with no name after it, a word that only looks like one and a middle name', $nLine( 'Dr.' ) === 'N:Dr.;;;;' && $nLine( 'Doctor Who' ) === 'N:Who;Doctor;;;' && $nLine( 'Dr. Lovelace' ) === 'N:Lovelace;;;Dr.;' && $nLine( 'Ada King Lovelace' ) === 'N:Lovelace;Ada King;;;' );

$tel = static fn( string $number ): string => \Nino\Modules\Vcard::telHref( $number );
check( 'a phone number is called without the (0) of its trunk prefix where it starts with +, in every way of writing it', $tel( '+49 (0) 170 1234567' ) === 'tel:+491701234567' && $tel( '+49(0)170/1234567' ) === 'tel:+491701234567' && $tel( '+49 ( 0 ) 170 1234567' ) === 'tel:+491701234567' );
check( '...and keeps what is no trunk prefix: a number without +, a (555) of a number of the US, the 0 of a number', $tel( '(0170) 123456' ) === 'tel:0170123456' && $tel( '0170 1234567' ) === 'tel:01701234567' && $tel( '+1 (555) 010-9999' ) === 'tel:+15550109999' && $tel( '+44 20 7946 0000' ) === 'tel:+442079460000' );

$appData = vcardSandbox();
vcardFacts( $appData );
$appData2 = $appData;
\Nino\Filesystem::putFileContent( $appData, '/text/global.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/global.php', [] ), [ '[[/project/company/contact/phone]]' => '+49 (0) 170 1234567' ] ) );
check( 'the link on the page calls the number without the (0), and the number is shown as it is written', str_contains( vcardPage( $appData ), 'href="tel:+491701234567">+49 (0) 170 1234567</a>' ) );

ninoUser( $appData, 'dev@example.com' );
check( 'a person must have a name: the panel refuses one without, with its code, and an organization may have none', vcardCall( $appData, 'vcard/save', vcardPosted( [ 'name' => '  ', 'kind' => 'person' ] ) )[1]['code'] === 'vcard_name'
	&& vcardCall( $appData, 'vcard/save', vcardPosted( [ 'name' => '', 'kind' => 'organization' ] ) )[0] === 200 );
check( 'a card nobody has saved is an organization', \Nino\Modules\Vcard::normalize( $appData, [] )['kind'] === 'organization' );

vcardCall( $appData, 'vcard/save', vcardPosted( [ 'texts' => [ 'de_DE' => [ 'text' => '' ], 'en_US' => [ 'text' => '' ] ] ] ) );
$html = vcardPage( $appData );
$file = \Nino\Modules\Vcard::file( $appData );
check( 'a person with a name of their own and no text of their own does not show the description of the company - on the page, in the file and in the data', str_contains( $html, 'Beschreibung der Firma.' ) === false && str_contains( $html, 'nino-vcard-text' ) === false && str_contains( $file, 'Beschreibung der Firma' ) === false && str_contains( $html, '"description"' ) === false );
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'name' => '', 'kind' => 'organization', 'texts' => [] ] ) );
check( 'an organization without a text of its own shows it, and page, file and data agree on the kind', str_contains( vcardPage( $appData ), 'Beschreibung der Firma.' ) && str_contains( vcardPage( $appData ), '"@type":"Organization"' ) && str_contains( \Nino\Modules\Vcard::file( $appData ), 'X-ABShowAs:COMPANY' ) );
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'texts' => [ 'de_DE' => [ 'text' => str_repeat( 'Lang. ', 40 ) ], 'en_US' => [ 'text' => 'Kurz.' ] ] ] ) );
check( 'a long text is set from the left, a short one is not', str_contains( vcardPage( $appData ), 'nino-vcard-text nino-vcard-text--long' ) );
\Nino\Locales::useLocale( $appData, 'en_US' );
check( '...and the short text of the other language is not', str_contains( vcardPage( $appData ), 'nino-vcard-text nino-vcard-text--long' ) === false && str_contains( vcardPage( $appData ), 'class="nino-vcard-text"' ) );

echo "\n15. Addresses that hide where they lead, markup in every field, a file that was edited by hand\n";

$marks = [ 'a right-to-left override' => "https://example.com/\u{202E}gpj.exe", 'an isolate' => "https://example.com/\u{2066}x", 'a zero-width space' => "https://exa\u{200B}mple.com/", 'a bell' => "https://example.com/\x07", 'a line separator' => "https://example.com/\u{2028}x", 'a no-break space' => "https://example.com/\u{00A0}x" ];
foreach( $marks as $label => $url )
	check( 'a link with '. $label. ' is refused', \Nino\Modules\Vcard::address( $url ) === null && vcardCall( $appData, 'vcard/save', vcardPosted( [ 'links' => [ [ 'url' => $url, 'labels' => [] ] ] ] ) )[1]['code'] === 'vcard_link' );
check( 'a name loses an override that would turn the line around, and a letter of another script stays', \Nino\Modules\Vcard::line( "Ada \u{202E}evoL\u{202C}", 99 ) === 'Ada evoL' && \Nino\Modules\Vcard::line( 'Αδα Ловелейс', 99 ) === 'Αδα Ловелейс' );

$evil = '<img src=x onerror=1> [button text="Z"] [[/project/website/general/author]] #/project/company/contact/email# </script>';
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'address' => str_replace( [ '[', ']' ], '', $evil ) ] ) );
vcardCall( $appData, 'vcard/save', vcardPosted( [ 'address' => "Street 1\n". substr( $evil, 0, 100 ) ] ) );
$html = vcardPage( $appData );
check( 'markup, a shortcode and a placeholder in the address are text, in the page, in the map link and in the data', str_contains( $html, '<img' ) === false && str_contains( $html, '&lt;img src=x onerror=1&gt;' ) && str_contains( $html, 'query=Street%201%2C%20%3Cimg' ) && str_contains( $html, '&#91;button' ) && str_contains( $html, 'SECRETAUTHOR' ) === false );
preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $ld );
check( '...and the data is json that closes no script', json_decode( $ld[1], true )['address']['streetAddress'] !== '' && substr_count( $ld[1], '</script' ) === 0 );

\Nino\Filesystem::putFileContent( $appData, '/data/vcard.php', [ 'format' => 1, 'name' => 'Eve', 'kind' => 'person', 'links' => [
	[ 'url' => 'javascript:alert(1)', 'labels' => [ 'de_DE' => 'JS' ] ], [ 'url' => " java\tscript:alert(2)", 'labels' => [] ], [ 'url' => 'https://ok.test/', 'labels' => [ 'de_DE' => 'OK' ] ],
	[ 'url' => "https://x.test/\u{202E}", 'labels' => [ 'de_DE' => 'Bidi' ] ], [ 'url' => 'data:text/html,x', 'labels' => [] ] ] ] );
\Nino\Locales::useLocale( $appData, 'de_DE' );
$html = vcardPage( $appData );
check( 'a file edited by hand: the links are checked again when the page is drawn - javascript:, data: and a hidden mark never reach an href', preg_match( '/javascript|java\s*script|data:text|JS<|Bidi/i', $html ) !== 1 && str_contains( $html, 'href="https://ok.test/"' ) && substr_count( $html, 'nino-vcard-more' ) === 1 );

// Folding: characters of two, three and four octets, started at an offset, so that the 75th octet falls inside one
$wide = [ 'Ä' => 'N:', '€' => 'TITLE:', '😀' => 'NOTE:' ];
foreach( $wide as $character => $property ) {

	$card = $person( 'Ada '. str_repeat( $character, 120 ) );
	$card['line'] = str_repeat( $character, 120 );
	$card['text'] = 'xy'. str_repeat( $character, 120 );
	$file = \Nino\Modules\Vcard::compose( $card );
	$physical = explode( "\r\n", rtrim( $file, "\r\n" ) );

	check( 'folded lines of '. strlen( $character ). '-octet characters: none over 75 octets, every one valid UTF-8 on its own, and the unfolded text is the text', array_filter( $physical, static fn( string $line ): bool => strlen( $line ) > 75 || preg_match( '//u', $line ) !== 1 ) === []
		&& str_contains( implode( "\r\n", vcardUnfold( $file ) ), 'TITLE:'. str_repeat( $character, 120 ) ) && str_contains( implode( "\r\n", vcardUnfold( $file ) ), 'NOTE:xy'. str_repeat( $character, 120 ) ) );
}
check( 'an octet-wise fold would have cut one: the same line folded by bytes is not valid UTF-8', preg_match( '//u', substr( 'N:'. str_repeat( 'Ä', 120 ), 0, 75 ) ) !== 1 );


echo "\n16. A card that does not draw itself, a card with an address in each language, the fills of the address\n";

// The route of the card carries its name and has been given another template
$other = static function( array &$appData ): void {
	\Nino\Filesystem::putFileContent( $appData, '/templates/page-contact.tpl', '<p>contact</p>' );
	\Nino\Filesystem::putFileContent( $appData, '/templates/page-home.tpl', '<p>home</p>' );
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ];
	$appData['/nino/http/routes']['GET://card'] = [ 'uri' => '/vcard', 'body' => '[template /templates/page-contact]' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
};
$detached = vcardSandbox( $other );
ninoUser( $detached, 'dev@example.com' );
[ , $screen ] = vcardCall( $detached, 'vcard/list' );
check( 'a route that carries the card\'s name and draws another template is detached, and the button is offered: the route of the unit takes /', $screen['page']['state'] === 'detached' && $screen['page']['path'] === '/card' && $screen['page']['start']['blocked'] === false );
[ $status, $body ] = vcardCall( $detached, 'vcard/start' );
$stored = \Nino\AppData::stored( $detached, '/nino/http/routes', [] );
check( 'what lies on / draws the card, not the other page: the route of the unit', $status === 200 && $stored['GET://'] === [ 'uri' => '/vcard', 'body' => '[template /templates/page-vcard]' ] && $body['page']['state'] === 'start' );
check( '...the other route is left as it is, and the page that held / is at /home', $stored['GET://card'] === [ 'uri' => '/vcard', 'body' => '[template /templates/page-contact]' ] && $stored['GET://home']['body'] === '[template /templates/page-home]' );
check( '...and that route draws the card, as the route of the other page does not', \Nino\Modules\Vcard::shows( $detached, $detached['/nino/http/routes']['GET://'] ) === true && \Nino\Modules\Vcard::shows( $detached, $stored['GET://card'] ) === false );
check( 'the table says the same: only a route that draws the card is the card\'s, whatever its name', \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/h' ], 'GET://card' => [ 'uri' => '/vcard', 'body' => 'other' ] ], [], static fn( array $route ): bool => in_array( $route['body'] ?? '', [ 'card', '[template /templates/page-vcard]' ], true ) )['routes']['GET://'] === [ 'uri' => '/vcard', 'body' => '[template /templates/page-vcard]' ] );
check( 'a unit whose template does not draw the card is refused whole', \Nino\Modules\Vcard::takeStart( [ 'GET://' => [ 'uri' => '/h', 'body' => 'h' ] ], [], static fn( array $route ): bool => false ) === [ 'error' => 'template' ] );

$template = vcardSandbox( $other );
ninoUser( $template, 'dev@example.com' );
\Nino\Filesystem::putFileContent( $template, '/templates/page-vcard.tpl', '<p>edited</p>' );
$before = vcardSnapshot( $template );
[ , $screen ] = vcardCall( $template, 'vcard/list' );
[ $status, $body ] = vcardCall( $template, 'vcard/start' );
check( 'with the template of the unit edited so that it does not draw the card, the command is refused with 409, its code, and nothing written', $status === 409 && $body['code'] === 'vcard_start_template' && vcardSnapshot( $template ) === $before
	&& $screen['page']['start']['blocked'] === true && $screen['page']['start']['reason'] === 'template' );
foreach( [ 'en_US', 'de_DE' ] as $locale )
	check( '...and the sentence of the code is in '. $locale, ( \Nino\Admin\Admin::textFills( $template, '/features/Vcard/text', $locale )['[[/_admin/error/vcard_start_template]]'] ?? '' ) !== '' );

// The card has an address in each language
$languages = vcardSandbox( static function( array &$appData ): void {
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => 'h' ];
	$appData['/nino/http/routes']['GET://karte'] = [ 'uri' => '/vcard', 'locale' => 'de_DE', 'body' => '[template /templates/page-vcard]' ];
	$appData['/nino/http/routes']['GET://card'] = [ 'uri' => '/vcard', 'locale' => 'en_US', 'body' => '[template /templates/page-vcard]' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
ninoUser( $languages, 'dev@example.com' );
vcardCall( $languages, 'vcard/save', vcardPosted( [ 'texts' => [ 'de_DE' => [ 'line' => 'Deutsche Zeile' ], 'en_US' => [ 'line' => 'English line' ] ] ] ) );
vcardCall( $languages, 'vcard/start' );
$stored = \Nino\AppData::stored( $languages, '/nino/http/routes', [] );
check( 'the route for / has no language of its own, so it serves every language, and the addresses of the card in each language are gone', isset( $stored['GET://']['locale'] ) === false && $stored['GET://']['uri'] === '/vcard' && isset( $stored['GET://karte'] ) === false && isset( $stored['GET://card'] ) === false );
foreach( [ 'de_DE' => 'Deutsche Zeile', 'en_US' => 'English line' ] as $locale => $line ) {
	\Nino\Locales::useLocale( $languages, $locale );
	check( '/ answers the visitor in '. $locale. ' in that language', ( $languages['/nino/http/routes']['GET://']['locale'] ?? $locale ) === $locale && str_contains( vcardPage( $languages ), $line ) && str_contains( vcardPage( $languages ), 'Deutsche Zeile' ) === ( $locale === 'de_DE' ) );
}

// The page that held / has an address in each language
$twin = vcardSandbox( static function( array &$appData ): void {
	\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [ '[[/_nino/webpage/home/uri]]' => '/' ] );
	\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] ), [ '[[/_nino/webpage/home/uri]]' => '/', '[[/_nino/webpage/home/name]]' => 'Startseite' ] ) );
	\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [ '[[/_nino/webpage/home/uri]]' => '/en', '[[/_nino/webpage/home/name]]' => 'Home' ] ) );
	$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'locale' => 'de_DE', 'body' => 'h' ];
	$appData['/nino/http/routes']['GET://en'] = [ 'uri' => '/home', 'locale' => 'en_US', 'body' => 'h' ];
	\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
} );
ninoUser( $twin, 'dev@example.com' );
[ , $body ] = vcardCall( $twin, 'vcard/start' );
$word = static fn( string $file ): mixed => \Nino\Filesystem::getFileContent( $twin, $file, [] )['[[/_nino/webpage/home/uri]]'] ?? null;
check( 'the fill of the address follows the page in the file of the language of the route, and in the one for every language where that is the native one', $word( '/text/de_DE.php' ) === '/home' && $word( '/text/global.php' ) === '/home' );
check( '...and stays where it names another address: the other language keeps /en, and so does the route', $word( '/text/en_US.php' ) === '/en' && ( \Nino\AppData::stored( $twin, '/nino/http/routes', [] )['GET://en']['uri'] ?? '' ) === '/home' );
check( 'the question and the message name the page by its name and the address it moves to', $body['moved'] === [ 'from' => '/home', 'to' => '/home', 'name' => 'Startseite' ] );
check( 'the files that hold the address: every language for a route of every language, one for a route of one, the global one with the native language', \Nino\Modules\Vcard::addressFiles( '', 'de_DE', [ 'de_DE', 'en_US' ] ) === [ '/text/global.php', '/text/de_DE.php', '/text/en_US.php' ]
	&& \Nino\Modules\Vcard::addressFiles( 'en_US', 'de_DE', [ 'de_DE', 'en_US' ] ) === [ '/text/en_US.php' ] && \Nino\Modules\Vcard::addressFiles( 'de_DE', 'de_DE', [ 'de_DE', 'en_US' ] ) === [ '/text/global.php', '/text/de_DE.php' ] );


// The panel's script, where node is on the path: vcard-js-smoke.js beside this file runs it over the kernel's
// tests/harness.js, so bin/check.sh and CI cover both halves in one go
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: vcard-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/vcard-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'vcard-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
