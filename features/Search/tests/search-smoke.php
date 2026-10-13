<?php
declare(strict_types=1);

/**
 *	Nino
 *	search-smoke.php	Contract test for the Search feature (Modules\Search):
 *									configuration, activation, derived files and how they
 *									are written and read, fuzzy ranking, locale selection,
 *									committed Elements writes, the shortcodes under the
 *									query of the request they are rendered for, the JSON
 *									endpoint, and the guarded /_admin actions with the code
 *									and the sentence of every refusal. Travels with the
 *									feature and runs against the checkout three levels up,
 *									or the one NINO_ROOT names (see tests/harness.php
 *									there). Where node is on the path it runs
 *									search-js-smoke.js beside it too, the panel's script
 *									over the workbench's own helpers.
 *
 *	Usage: php features/Search/tests/search-smoke.php
 *	       NINO_ROOT=../nino php features/Search/tests/search-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

function searchUris( array &$appData, string $type, string $query ): array {
	return array_column( \Nino\Modules\Search::getElements( $appData, $type, $query ), '.uri' );
}

/**
 *	The request the page is being answered for, with the query it carries - what
 *	the shortcodes read the search from. ninoVisit() builds the request and
 *	keeps it where \Nino\Http::getRequest() finds it; nothing is resolved, which
 *	is all a page rendered in the test needs
 *
 *	@param		array 		&$appData
 *	@param		array 		$query				Name => value, as the address carries them
 *
 *	@return 	void
 */
function searchVisit( array &$appData, array $query ): void {
	ninoVisit( $appData, '/search'. ( $query === [] ? '' : '?'. http_build_query( $query ) ) );
}

/**
 *	The sentence the panel says for a code in one language, as its text
 *	directory has it - '' for a code without one
 *
 *	@param		array 		&$appData
 *	@param		string		$code
 *	@param		string		$locale
 *
 *	@return 	string
 */
function searchSentence( array &$appData, string $code, string $locale ): string {
	return (string) ( \Nino\Admin\Admin::textFills( $appData, '/features/Search/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' );
}

$appData = ninoSandbox( 'search' );
$sandbox = ninoSandboxDir( $appData );
$panel = \Nino\Modules\Search\Admin::class;
$appData['/nino/elements/index'] = [
	// Both leading-slash forms are accepted. Invalid priorities, fields, and
	// types are ignored rather than leaking into a generated document.
	'articles' => [
		0 => 'title',
		1 => 'summary',
		2 => 'keywords',
		3 => 'author',
		4 => 'title',
	],
	'/ignored-fields' => [ 0 => 'missing-field' ],
	'/missing-type' => [ 0 => 'title' ],
	'not/a/type' => [ 0 => 'title' ],
];

echo "Sandbox: $sandbox\n\n";
echo "Search configuration, activation and derived index files\n";

$articleModel = [
	'title' => [ 'type' => 'string', 'locale' => true ],
	'summary' => [ 'type' => 'string', 'locale' => true ],
	'keywords' => [ 'type' => 'array' ],
	'author' => [ 'type' => 'string', 'locale' => true ],
	'internalCode' => [ 'type' => 'string' ],
];

check( 'the Search class autoloads on direct API use', class_exists( '\Nino\Modules\Search' ) === true );
check( 'the indexed element type can be created', \Nino\Elements::insertElementType( $appData, '/articles', $articleModel ) === true );
check( 'a valid type with no valid configured field can be created', \Nino\Elements::insertElementType( $appData, '/ignored-fields', [
	'title' => [ 'type' => 'string', 'locale' => true ],
] ) === true );
\Nino\Elements::insertElement( $appData, '/ignored-fields/one', [ 'title' => 'Not indexed' ], 'de_DE' );

$alpha = \Nino\Elements::insertElement( $appData, '/articles/alpha', [
	'title' => 'Müller Katalog',
	'summary' => '<strong>Leuchttürme</strong> &amp; klare Wege in Berlin',
	'keywords' => [ 'CMS', 'Leicht' ],
	'author' => 'Dape Team',
	'internalCode' => 'A-100',
], 'de_DE' );
check( 'an element can be written before Search is active', is_array( $alpha ) === true );

$alphaEnglish = \Nino\Elements::updateElement( $appData, '/articles/alpha', [
	'title' => 'Miller Catalog',
	'summary' => 'Lighthouses and clear routes in Berlin',
	'author' => 'Dape Team',
], 'en_US' );
check( 'the same element has an independent translated document', ( $alphaEnglish['title'] ?? null ) === 'Miller Catalog' );

$articleIndexPath = \Nino\Filesystem::path( $appData, '/data/index-articles.php' );
check( 'configuration alone does not create an index', is_file( $articleIndexPath ) === false );
check( 'a read with no index is empty', \Nino\Modules\Search::getElements( $appData, 'articles', 'catalog' ) === [] );
check( 'a read never self-heals the missing file', is_file( $articleIndexPath ) === false );

$appData['/nino/modules'] = [ '\Nino\Modules\Search' ];
\Nino\Modules::callModules( $appData, 'init' );
check( 'activating Search only registers its callback', is_file( $articleIndexPath ) === false );

\Nino\Elements::insertElement( $appData, '/articles/orbit-title', [
	'title' => 'Orbit',
	'summary' => 'A remote observatory',
	'keywords' => [],
	'internalCode' => 'O-100',
], 'de_DE' );
check( 'the first committed write after activation creates the configured type index', is_file( $articleIndexPath ) === true );

\Nino\Elements::insertElement( $appData, '/articles/orbit-summary', [
	'title' => 'Remote station',
	'summary' => 'Orbit',
	'keywords' => [],
	'internalCode' => 'O-200',
], 'de_DE' );

$index = @include $articleIndexPath;
check( 'the index is one plain PHP array grouped by locale, behind its own meta block', is_array( $index ) === true && array_keys( $index ) === [ '.meta', 'de_DE', 'en_US' ] );
check( 'the meta block says when it was built and out of which fields',
	( $index['.meta']['format'] ?? null ) === 1
	&& ( $index['.meta']['elements'] ?? null ) === 3
	&& ( $index['.meta']['fields'] ?? null ) === [ 0 => 'title', 1 => 'summary', 2 => 'keywords', 3 => 'author' ]
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T/', (string) ( $index['.meta']['built'] ?? '' ) ) === 1 );
check( 'indexed text is normalized before it reaches disk', ( $index['de_DE']['/articles/alpha'][0] ?? null ) === 'mueller katalog' );
check( 'HTML, entities and nested scalar arrays are flattened safely',
	( $index['de_DE']['/articles/alpha'][1] ?? null ) === 'leuchttuerme klare wege in berlin'
	&& ( $index['de_DE']['/articles/alpha'][2] ?? null ) === 'cms leicht' );
check( 'all four priorities are stored while invalid priorities are omitted', array_keys( $index['de_DE']['/articles/alpha'] ?? [] ) === [ 0, 1, 2, 3 ] );

$created = \Nino\Modules\Search::createIndexes( $appData );
check( 'an explicit rebuild reports only valid configured types', ( $created['created'] ?? null ) === 1
	&& ( $created['elements'] ?? null ) === 3 && ( $created['failed'] ?? null ) === [] );
check( 'and names every configured type it had to skip, with the reason',
	array_keys( $created['skipped'] ?? [] ) === [ '/ignored-fields', '/missing-type', '?not/a/type' ]
	&& ( $created['skipped']['/ignored-fields'][0] ?? '' ) === 'the model of "/ignored-fields" has no field "missing-field"'
	&& ( $created['skipped']['/missing-type'][0] ?? '' ) === 'there is no element type "/missing-type"'
	&& ( $created['skipped']['?not/a/type'][0] ?? '' ) === 'not an element type name' );
check( 'a type that does index, but carries a name that does not resolve, is reported apart from those',
	array_keys( $created['issues'] ?? [] ) === [ '/articles' ]
	&& ( $created['issues']['/articles'][0] ?? '' ) === 'priority "4" is not 0, 1, 2 or 3' );
check( 'a rebuild can be asked for one type alone', ( \Nino\Modules\Search::createIndexes( $appData, 'articles' )['created'] ?? null ) === 1 );
$ignoredIndexPath = \Nino\Filesystem::path( $appData, '/data/index-ignored-fields.php' );
check( 'a type with no valid configured model field gets no index file', is_file( $ignoredIndexPath ) === false );

check( 'an unconfigured type can be created', \Nino\Elements::insertElementType( $appData, '/notes', [
	'title' => [ 'type' => 'string', 'locale' => true ],
] ) === true );
\Nino\Elements::insertElement( $appData, '/notes/one', [ 'title' => 'Merkzettel' ], 'de_DE' );
$notesIndexPath = \Nino\Filesystem::path( $appData, '/data/index-notes.php' );
check( 'writes to an unconfigured type do not create an index', is_file( $notesIndexPath ) === false );

echo "\nWeighted fuzzy search and locale selection\n";

check( 'exact terms return canonical Elements', searchUris( $appData, '/articles', 'Orbit' ) === [ '/articles/orbit-title', '/articles/orbit-summary' ] );
check( 'a hit in priority 0 ranks ahead of the same hit in priority 1', ( searchUris( $appData, 'articles', 'orbit' )[0] ?? null ) === '/articles/orbit-title' );
check( 'a small spelling error is matched fuzzily', searchUris( $appData, 'articles', 'katalg' ) === [ '/articles/alpha' ] );
check( 'German umlauts use their readable ae/oe/ue form', searchUris( $appData, 'articles', 'mueller' ) === [ '/articles/alpha' ] );
check( 'HTML text and decoded entities remain searchable as words', searchUris( $appData, 'articles', 'klare wege' ) === [ '/articles/alpha' ] );
check( 'array fields participate at their configured weight', searchUris( $appData, 'articles', 'cms' ) === [ '/articles/alpha' ] );
check( 'priority 3 remains searchable at its lower weight', searchUris( $appData, 'articles', 'dape' ) === [ '/articles/alpha' ] );
/*	A word the document does not carry no longer throws the document away: it
	lowers the coverage, and the coverage multiplies the score. Every one of
	these used to be an empty result */
check( 'a word that finds nothing no longer discards the document', searchUris( $appData, 'articles', 'orbit nowhere' ) === [ '/articles/orbit-title', '/articles/orbit-summary' ] );
check( 'a query that finds nothing at all is still empty', searchUris( $appData, 'articles', 'nowhere' ) === [] );
check( 'the full phrase outranks the partial one',
	searchUris( $appData, 'articles', 'orbit remote' ) === [ '/articles/orbit-summary', '/articles/orbit-title' ] );
$covered = \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit nowhere' );
check( 'a hit says how much of the query it covered, and where it matched',
	( $covered[0]['coverage'] ?? null ) === 0.5 && ( $covered[0]['matched'] ?? null ) === 1
	&& ( $covered[0]['fields'] ?? null ) === [ 0 ] && ( $covered[0]['type'] ?? null ) === '/articles' );
check( 'limit and offset cut the ranked list, not the search',
	array_column( \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit', 1 ), 'uri' ) === [ '/articles/orbit-title' ]
	&& array_column( \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit', 1, 1 ), 'uri' ) === [ '/articles/orbit-summary' ] );
check( 'an element comes back carrying its score and its type',
	( \Nino\Modules\Search::getElements( $appData, 'articles', 'orbit', 1 )[0]['.type'] ?? null ) === '/articles'
	&& is_float( \Nino\Modules\Search::getElements( $appData, 'articles', 'orbit', 1 )[0]['.score'] ?? null ) === true );
check( 'empty and invalid searches return no result',
	\Nino\Modules\Search::getElements( $appData, 'articles', " \n " ) === []
	&& \Nino\Modules\Search::getElements( $appData, '../articles', 'orbit' ) === [] );

$appData['./nino/locales/current'] = 'en_US';
check( 'search reads only the current locale', searchUris( $appData, 'articles', 'lighthouses' ) === [ '/articles/alpha' ] );
check( 'a term present only in German is absent in English', searchUris( $appData, 'articles', 'leuchttuerme' ) === [] );
$appData['./nino/locales/current'] = 'de_DE';

\Nino\Elements::updateElement( $appData, '/articles/alpha', [ 'title' => 'Neuer Katalog' ], 'de_DE' );
check( 'a committed update refreshes the configured type', searchUris( $appData, 'articles', 'neuer' ) === [ '/articles/alpha' ] );
check( 'the previous indexed value disappears after that refresh', searchUris( $appData, 'articles', 'mueller' ) === [] );

\Nino\Elements::deleteElement( $appData, '/articles/orbit-title', '*' );
check( 'a committed delete removes the Element from search', searchUris( $appData, 'articles', 'orbit' ) === [ '/articles/orbit-summary' ] );

echo "\nSentences, several types at once, and what the panel reads\n";

/*	The behaviour this whole change is about, on the shape a visitor actually
	types. Every one of these was an empty result while _score() discarded a
	document over one word it did not carry */
\Nino\Elements::insertElement( $appData, '/articles/ai', [
	'title' => 'AI im Jahr 2026', 'summary' => 'Ein Ausblick auf Modelle und Werkzeuge', 'keywords' => [], 'author' => '',
], 'de_DE' );
check( 'a sentence finds the article whose title is worded differently', ( searchUris( $appData, 'articles', 'AI in 2026' )[0] ?? '' ) === '/articles/ai' );
check( 'a filler word the text does not use no longer empties the result', ( searchUris( $appData, 'articles', 'Ausblick der Modelle' )[0] ?? '' ) === '/articles/ai' );
check( 'a query of mostly unknown words still finds its one known one', searchUris( $appData, 'articles', 'Artikel ueber Modelle' ) === [ '/articles/ai' ] );
/*	The other half of that bargain: the filler word now matches whatever
	happens to carry it, so it has to rank below the real answer rather than
	beside it */
check( 'a document carrying only the filler word ranks below the real answer',
	in_array( '/articles/alpha', searchUris( $appData, 'articles', 'AI in 2026' ), true ) === true
	&& array_search( '/articles/alpha', searchUris( $appData, 'articles', 'AI in 2026' ), true ) > 0 );

$appData['/nino/elements/index']['notes'] = [ 0 => 'title' ];
\Nino\Modules\Search::createIndexes( $appData );
\Nino\Elements::insertElement( $appData, '/notes/orbit', [ 'title' => 'Orbit Merkzettel' ], 'de_DE' );
$across = \Nino\Modules\Search::getHits( $appData, [ 'articles', 'notes' ], 'orbit' );
/*	One ranked list, not one list per type: the note carries "orbit" in its
	priority 0 and the article in its priority 1, so the note comes first -
	which is the whole point of the scores being on one scale */
check( 'two types search as one ranked list', array_column( $across, 'type' ) === [ '/notes', '/articles' ]
	&& array_column( $across, 'uri' ) === [ '/notes/orbit', '/articles/orbit-summary' ]
	&& $across[0]['score'] > $across[1]['score'] );
check( 'coverage is a float whether or not it divides evenly', $across[0]['coverage'] === 1.0 );
check( 'a type that is not configured contributes nothing to that list',
	\Nino\Modules\Search::getHits( $appData, [ 'articles', 'ignored-fields' ], 'orbit' ) !== []
	&& array_column( \Nino\Modules\Search::getHits( $appData, [ 'ignored-fields' ], 'orbit' ), 'type' ) === [] );

$state = [];
foreach( \Nino\Modules\Search::indexState( $appData ) as $row )
	$state[$row['type']] = $row;

check( 'the state lists every type the project has, not only the configured ones',
	array_keys( $state ) === [ '/articles', '/ignored-fields', '/missing-type', '/notes', '?not/a/type' ] );
check( 'a working type says what it indexes, how many it holds and when it was built',
	( $state['/articles']['configured'] ?? null ) === true
	&& ( $state['/articles']['fields'] ?? null ) === [ 0 => 'title', 1 => 'summary', 2 => 'keywords', 3 => 'author' ]
	&& ( $state['/articles']['indexed'] ?? null ) === true
	&& ( $state['/articles']['elements'] ?? null ) === 3
	&& ( $state['/articles']['indexedElements'] ?? null ) === 3
	&& ( $state['/articles']['stale'] ?? null ) === false );
check( 'a broken configuration says why, rather than looking unconfigured',
	( $state['/ignored-fields']['configured'] ?? null ) === true
	&& ( $state['/ignored-fields']['fields'] ?? null ) === []
	&& ( $state['/ignored-fields']['issues'][0] ?? '' ) === 'the model of "/ignored-fields" has no field "missing-field"'
	&& ( $state['/ignored-fields']['indexed'] ?? null ) === false );
check( 'the field picker is offered the model minus what carries no searchable text',
	in_array( 'title', $state['/articles']['model'] ?? [], true ) === true
	&& in_array( 'keywords', $state['/articles']['model'] ?? [], true ) === true );
check( 'a type nobody configured is listed as what it is', ( $state['/notes']['configured'] ?? null ) === true
	&& ( $state['/missing-type']['issues'] ?? null ) === [ 'there is no element type "/missing-type"' ] );

// Written after the index was: the one stat call that catches an element
// edited while the feature was off, and a restored backup
touch( \Nino\Filesystem::path( $appData, '/elements/articles.php' ), time() + 5 );
clearstatcache();
$stale = [];
foreach( \Nino\Modules\Search::indexState( $appData ) as $row )
	$stale[$row['type']] = $row;
check( 'a type file written since the index was is stale', ( $stale['/articles']['stale'] ?? null ) === true );

$appData['/nino/elements/index']['articles'] = [ 0 => 'summary' ];
$changed = [];
foreach( \Nino\Modules\Search::indexState( $appData ) as $row )
	$changed[$row['type']] = $row;
check( 'so is an index built out of other fields than the ones configured now', ( $changed['/articles']['stale'] ?? null ) === true );
$appData['/nino/elements/index']['articles'] = [ 0 => 'title', 1 => 'summary', 2 => 'keywords', 3 => 'author', 4 => 'title' ];

// This section's own fixtures go again, so the counts the rest of the file
// asserts on stay the counts the rest of the file set up
\Nino\Elements::deleteElement( $appData, '/articles/ai', '*' );
\Nino\Elements::deleteElement( $appData, '/notes/orbit', '*' );
unset( $appData['/nino/elements/index']['notes'] );
\Nino\Modules\Search::createIndexes( $appData );

echo "\nThe shortcode that draws the hits under the project's own form\n";

/*	Rendered the way a template renders: through \Nino\Html::renderHtml(), so
	the fills in the attributes resolve before the shortcode sees them and the
	[[field]] placeholders in the body survive to it, exactly as they do in a
	project (see Html::renderHtml()'s order) */
$appData['./nino/html/fills'] = [];

// The form is the project's own: an input named like `key` and a button.
// A shortcode used to draw one, and a page that still carries it gets the
// text, not a form
check( '[search] is no shortcode any more - the form is the project\'s own',
	str_contains( \Nino\Html::renderHtml( $appData, '[search]' ), '<form' ) === false );

// What a shortcode returns is rendered again (that is what lets [template]
// hold other shortcodes) - so an element value, which is editor content,
// must come back as the text it is: anybody could otherwise put any of the
// project's templates, and any of its texts, into the page by writing one.
// The kernel's own [element] escapes its '[' for exactly this reason
\Nino\Filesystem::putFileContent( $appData, '/templates/page-secret.tpl', 'THE-SECRET-TEMPLATE' );
\Nino\Html::addFills( $appData, [ '[[/secret/fill]]' => 'THE-SECRET-TEXT' ], '*' );
\Nino\Modules\Template::init( $appData );

\Nino\Elements::insertElement( $appData, '/articles/bracket', [
	'title' => 'Orbit [template /templates/page-secret]', 'summary' => 'Und [[/secret/fill]]', 'keywords' => [], 'author' => '',
], 'de_DE' );
searchVisit( $appData, [ 'q' => 'orbit' ] );
$bracketRow = \Nino\Html::renderHtml( $appData, '[search-results type="articles"]<p>[[title]] [[summary]]</p>[/search-results]' );
check( 'an element value carrying a shortcode or a fill is drawn as text, not run',
	str_contains( $bracketRow, 'THE-SECRET-TEMPLATE' ) === false && str_contains( $bracketRow, 'THE-SECRET-TEXT' ) === false );
\Nino\Elements::deleteElement( $appData, '/articles/bracket', '*' );

searchVisit( $appData, [ 'q' => 'orbit' ] );

// The shape the manual shows, with the type given the way a person writes it
$block = '[search-results key="q" type="/articles"]<h5>[[title]]</h5><p>[[summary]]</p>[/search-results]';
$results = \Nino\Html::renderHtml( $appData, $block );
check( 'the body is the row markup, once per hit',
	substr_count( $results, '<h5>' ) === 1 && str_contains( $results, '<h5>Remote station</h5>' ) === true );
check( 'and the rows come wrapped, so a project has something to style',
	str_starts_with( $results, '<div class="nino-search-results">' ) === true );

searchVisit( $appData, [ 'q' => 'nowhere at all' ] );
check( 'a query that finds nothing renders nothing without an empty text',
	\Nino\Html::renderHtml( $appData, $block ) === '' );
/*	...and with empty= it draws the project's template of that name - a
	template, not a sentence in an attribute, so it holds whatever the project
	puts there: a fill, a picture, a form. Rendered inside the wrapper, with
	the fill resolved, which is what says it went through the template pass */
\Nino\Filesystem::putFileContent( $appData, '/templates/search-empty.tpl', '<p class="nothing">NOTHING-FOUND [[/secret/fill]]</p>' );
check( '...and with empty= it draws the project\'s template of that name inside the wrapper, fills and all', str_starts_with(
	\Nino\Html::renderHtml( $appData, '[search-results type="/articles" empty="search-empty"]<p>[[title]]</p>[/search-results]' ),
	'<div class="nino-search-results"><p class="nothing">NOTHING-FOUND THE-SECRET-TEXT</p>' ) === true );
ninoWarnings();
check( 'an empty= that is not a template name draws nothing and says so in the log',
	\Nino\Html::renderHtml( $appData, '[search-results type="/articles" empty="Nichts gefunden."]<p>[[title]]</p>[/search-results]' ) === ''
	&& str_contains( implode( "\n", ninoWarnings() ), 'does not name a template below /templates' ) === true );

searchVisit( $appData, [] );
check( 'nothing searched for is not the same as nothing found', \Nino\Html::renderHtml( $appData, $block ) === '' );

searchVisit( $appData, [ 'my_own_get_var_key' => 'orbit' ] );
check( 'the block reads whichever query variable it is told to - the name of the input in the project\'s form',
	str_contains( \Nino\Html::renderHtml( $appData, '[search-results key="my_own_get_var_key" type="articles"]<p>[[title]]</p>[/search-results]' ), 'Remote station' ) === true );
check( 'a block reading another key than the one the form sent stays empty',
	\Nino\Html::renderHtml( $appData, $block ) === '' );

searchVisit( $appData, [ 'q' => 'orbit' ] );
$dotKeys = \Nino\Html::renderHtml( $appData, '[search-results type="articles"]<a href="/artikel/[[.slug]]" data-uri="[[.uri]]" data-n="[[.n]]" data-type="[[.type]]">[[title]]</a>[/search-results]' );
check( 'an element carries its uri, its last segment and its place in the list',
	str_contains( $dotKeys, 'href="/artikel/orbit-summary"' ) === true
	&& str_contains( $dotKeys, 'data-uri="/articles/orbit-summary"' ) === true
	&& str_contains( $dotKeys, 'data-n="1"' ) === true
	&& str_contains( $dotKeys, 'data-type="/articles"' ) === true );

\Nino\Elements::insertElement( $appData, '/articles/markup', [
	'title' => 'Orbit & <b>bold</b>', 'summary' => 'Ein Text mit "Zitat"', 'keywords' => [ 'a', 'b' ], 'author' => '',
], 'de_DE' );
$escaped = \Nino\Html::renderHtml( $appData, '[search-results type="articles" limit="1"]<p>[[title]] | [[keywords]]</p>[/search-results]' );
check( 'a value is escaped on the way into the page, and an array field reads as a list',
	str_contains( $escaped, 'Orbit &amp; &lt;b&gt;bold&lt;/b&gt;' ) === true && str_contains( $escaped, 'a, b' ) === true );

/*	A string field the model gives a format is the kernel's to render by
	\Nino\Html::fieldValue() - the paragraphs and lists of one that holds them,
	the line breaks of one that keeps them	*/
$renderValue 	= new ReflectionMethod( \Nino\Modules\Search\Shortcodes::class, '_value' );
$fieldValue 	= static fn( mixed $value, array $definition ): string => (string) $renderValue->invoke( null, $value, $definition );

check( 'a plain field is escaped, and a bracket in it is not a shortcode',
	$fieldValue( '<b>[x]</b>', [ 'type' => 'string' ] ) === '&lt;b&gt;&#91;x]&lt;/b&gt;' );
check( 'a field released for html keeps its inline tags and neutralises a bracket',
	$fieldValue( '<strong>Orbit</strong> [x]', [ 'type' => 'string', 'html' => true ] ) === '<strong>Orbit</strong> &#91;x]' );
check( 'a field that holds paragraphs and lists keeps them',
	$fieldValue( '<p>Alpha</p><ul><li>Beta</li></ul>', [ 'type' => 'string', 'html' => true, 'blocks' => true ] ) === '<p>Alpha</p><ul><li>Beta</li></ul>' );
check( 'a field that keeps its line breaks draws them as <br>, escaped',
	$fieldValue( "Gamma\nDelta <b>", [ 'type' => 'string', 'breaks' => true ] ) === "Gamma<br>\nDelta &lt;b&gt;" );
check( 'a value that is no string field is text, escaped the way a component escapes its own, a bracket included',
	$fieldValue( '<i>[x]</i> & "y"', [ 'type' => 'date' ] ) === \Nino\Modules\Components::escape( '<i>[x]</i> & "y"' )
	&& $fieldValue( '<i>[x]</i> & "y"', [ 'type' => 'date' ] ) === '&lt;i&gt;&#91;x]&lt;/i&gt; &amp; &quot;y&quot;' );
check( 'a list field reads as its values however the model is written, and a value that is not text is nothing',
	$fieldValue( [ 'a', [ 'b' ] ], [ 'type' => 'array' ] ) === 'a, b' && $fieldValue( true, [ 'type' => 'string' ] ) === '' );

$normalized = new ReflectionMethod( \Nino\Modules\Search::class, '_normalizeValue' );
check( 'what the index keeps of paragraphs and list items is words, with a word end where the markup had one',
	(string) $normalized->invoke( null, '<p>Alpha</p><ul><li>Beta</li></ul>Gamma<br>Delta' ) === 'alpha beta gamma delta' );
check( '...and inline tags in the middle of a word do not split it', (string) $normalized->invoke( null, 'Or<strong>bit</strong>' ) === 'orbit' );

check( 'limit cuts the list', substr_count(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" limit="1"]<p>[[title]]</p>[/search-results]' ), '<p>' ) === 1 );
check( 'a placeholder the model does not have is left standing rather than emptied',
	str_contains( \Nino\Html::renderHtml( $appData, '[search-results type="articles" limit="1"]<p>[[titel]]</p>[/search-results]' ), '[[titel]]' ) === true );
check( 'a type nobody configured draws nothing at all',
	\Nino\Html::renderHtml( $appData, '[search-results type="notes"]<p>[[title]]</p>[/search-results]' ) === '' );
check( 'tag="none" leaves the rows unwrapped', str_starts_with(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" tag="none" limit="1"]<p>[[title]]</p>[/search-results]' ), '<p>' ) === true );
check( '...and a tag that is not a tag name falls back to the wrapper rather than dropping it', str_starts_with(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" tag="<script>" limit="1"]<p>[[title]]</p>[/search-results]' ), '<div ' ) === true );
check( 'the wrapper takes the class it is given after its own, and the tag it is given', str_contains(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" tag="ul" class="produkte" limit="1"]<li>[[title]]</li>[/search-results]' ), '<ul class="nino-search-results produkte">' ) === true );
check( '...several classes, in the order they are written, and escaped like any value of a component', str_contains(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" class="a b&c<d" limit="1"]<li>[[title]]</li>[/search-results]' ), '<div class="nino-search-results a b&amp;c&lt;d">' ) === true );
check( '...and a class that is only blanks adds nothing', str_contains(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" class=" " limit="1"]<li>[[title]]</li>[/search-results]' ), '<div class="nino-search-results">' ) === true );

echo "\nThe shortcode that says how many hits there are\n";

// Two Elements of this section's own, found by one word nothing else in the
// index carries, so the number is this section's to know
\Nino\Elements::insertElement( $appData, '/articles/count-one', [ 'title' => 'Zaehlwerk eins', 'summary' => 'Der erste', 'keywords' => [], 'author' => '' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/articles/count-two', [ 'title' => 'Der zweite', 'summary' => 'Zaehlwerk zwei', 'keywords' => [], 'author' => '' ], 'de_DE' );

searchVisit( $appData, [ 'q' => 'zaehlwerk' ] );
$countHits = count( \Nino\Modules\Search::getHits( $appData, [ 'articles' ], 'zaehlwerk' ) );
check( 'the count is the number of hits the search has', $countHits >= 2 && \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === (string) $countHits );
check( '...as digits and nothing around them', preg_match( '/^\d+$/', \Nino\Html::renderHtml( $appData, '[search-count type="/articles"]' ) ) === 1 );
check( "...so the sentence around it is the page's", \Nino\Html::renderHtml( $appData, '<p>[search-count type="articles"] Treffer</p>' ) === '<p>'. $countHits. ' Treffer</p>' );
check( 'the count is of everything the query matched, not of the page a limit draws',
	substr_count( \Nino\Html::renderHtml( $appData, '[search-results type="articles" limit="1"]<p>[[title]]</p>[/search-results]' ), '<p>' ) === 1
	&& (int) \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === $countHits );
searchVisit( $appData, [ 'q' => 'nowhere at all' ] );
check( 'a search that found nothing is answered with a 0', \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === '0' );
searchVisit( $appData, [] );
check( 'nothing searched for renders nothing, like the result block', \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === '' );
searchVisit( $appData, [ 'my_own_get_var_key' => 'zaehlwerk' ] );
check( 'key names the query variable, the same way', \Nino\Html::renderHtml( $appData, '[search-count key="my_own_get_var_key" type="articles"]' ) === (string) $countHits
	&& \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === '' );
searchVisit( $appData, [ 'q' => 'zaehlwerk' ] );
check( 'a type that is not indexed counts nothing', \Nino\Html::renderHtml( $appData, '[search-count type="notes"]' ) === '0' );
check( '...and no type at all is nothing asked', \Nino\Html::renderHtml( $appData, '[search-count]' ) === '' );
\Nino\Elements::deleteElement( $appData, '/articles/count-one', '*' );
\Nino\Elements::deleteElement( $appData, '/articles/count-two', '*' );
searchVisit( $appData, [] );

\Nino\Elements::deleteElement( $appData, '/articles/markup', '*' );
searchVisit( $appData, [] );

echo "\nWhere the query comes from\n";

/*	A shortcode is handed $appData and its own arguments, never the request:
	the kernel gives it to code that has none in hand (\Nino\Http::getRequest()),
	and that request - the one the page is answered for - is where the visitor's
	words are. php's $_GET is not asked	*/
searchVisit( $appData, [ 'q' => "  orbit \n" ] );
check( 'the query is the one of the request being answered, trimmed',
	\Nino\Modules\Search\Shortcodes::query( $appData, 'q' ) === 'orbit' && \Nino\Modules\Search\Shortcodes::query( $appData, 'p' ) === '' );

searchVisit( $appData, [ 'q' => [ 'orbit' ] ] );
check( '...never an array: a variable sent as a list is nothing searched for',
	\Nino\Modules\Search\Shortcodes::query( $appData, 'q' ) === '' && \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === '' );

$_GET = [ 'q' => 'orbit' ];
searchVisit( $appData, [] );
check( '...and it is the request\'s and not php\'s: the same words in $_GET, none in the request, are nothing searched for',
	\Nino\Modules\Search\Shortcodes::query( $appData, 'q' ) === '' && \Nino\Html::renderHtml( $appData, '[search-count type="articles"]' ) === '' );
$_GET = [];

searchVisit( $appData, [ 'q' => 'orbit' ] );
$requests = $appData['./nino/http/requests'];
$appData['./nino/http/requests'] = [];
check( 'with no request at all - a page rendered by a script - nothing was searched for, and nothing is raised',
	\Nino\Modules\Search\Shortcodes::query( $appData, 'q' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[search-results type="articles"]<p>[[title]]</p>[/search-results]' ) === ''
	&& ninoWarnings() === [] );
$appData['./nino/http/requests'] = $requests;

// --- Components ----------------------------------------------------------------

echo "\nComponents - [search-count] as the Builder offers it, [search-results] as it was\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// The search the page is answering, and the type the hits are of
	searchVisit( $appData, [ 'q' => 'orbit' ] );

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[search-results key="q" type="/articles"]<h5>[[title]]</h5><p>[[summary]]</p>[/search-results]',
		'[search-results type="articles" limit="1"]<p>[[title]]</p>[/search-results]',
		'[search-results type="articles" limit="500"]<p>[[title]]</p>[/search-results]',
		'[search-results type="articles" tag="none" limit="1"]<p>[[title]]</p>[/search-results]',
		'[search-results type="articles" tag="<script>" limit="1"]<p>[[title]]</p>[/search-results]',
		'[search-results type="articles" tag="ul" class="produkte" limit="1"]<li>[[title]]</li>[/search-results]',
		'[search-results type="/articles" empty="search-empty"]<p>[[title]]</p>[/search-results]',
		'[search-results key="my_own_get_var_key" type="articles"]<p>[[title]]</p>[/search-results]',
		'[search-results type="notes"]<p>[[title]]</p>[/search-results]',
		'[search-results]<p>[[title]]</p>[/search-results]',
		'[search-results type="articles"][/search-results]',
		'<p>[search-count type="articles"] Treffer</p>',
		'[search-count type="/articles"]',
		'[search-count key="my_own_get_var_key" type="articles"]',
		'[search-count type="notes"]',
		'[search-count]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'search-count', 'search-results' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Search\Shortcodes::init( $probe );
	ninoWarnings();

	check( 'the count is registered with the schema the manifest declares, and it is the only component',
		( \Nino\Modules\Components::components( $probe )['search-count'] ?? null ) === ( $declared['search-count'] ?? false )
		&& array_keys( $declared ) === [ 'search-count' ]
		&& isset( \Nino\Modules\Components::components( $probe )['search-results'] ) === false );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['search-count']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the count by its wrapper, the results as the shortcode they are',
		\Nino\Features::shortcodes( $probe, 'search' ) === [ 'search-count', 'search-results' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/search-results' ) ) === 1 && is_array( $registered[0] )
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/search-count' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'search-count' ) === [ 'type' => '', 'key' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[search-count type="articles" key="" class=""]' ) === \Nino\Html::renderHtml( $probe, '[search-count type="articles"]' ) );
	check( 'the results add the class that names their wrapper to the default one, with the module on as with it off',
		str_starts_with( $before['[search-results type="articles" tag="ul" class="produkte" limit="1"]<li>[[title]]</li>[/search-results]'], '<ul class="nino-search-results produkte">' ) === true
		&& str_starts_with( \Nino\Html::renderHtml( $probe, '[search-results type="articles" tag="ul" class="produkte" limit="1"]<li>[[title]]</li>[/search-results]' ), '<ul class="nino-search-results produkte">' ) === true
		&& str_starts_with( \Nino\Html::renderHtml( $probe, '[search-results type="articles"]<p>[[title]]</p>[/search-results]' ), '<div class="nino-search-results">' ) === true );

} )( $appData );

echo "\n";


echo "\nThe JSON endpoint, off until the setting says so\n";

/**
 *	Build a request the way \Nino\Http::request() leaves it and resolve it -
 *	the whole pipeline, so what is under test is the route and its callback
 *	as they really run. ninoVisit() stops after the request is built, and the
 *	route is resolved by the response: this runs \Nino\Http::response() as well
 */
function searchRequest( array &$appData, string $uri ): array {
	$request = ninoVisit( $appData, $uri );
	\Nino\Http::response( $appData, $request );
	return $request['/nino/http/response'];
}

/**
 *	The same, answering the body as the array it is meant to be - or [] where
 *	it is not, so a check reads a failure rather than ending the suite
 */
function searchJson( array &$appData, string $uri ): array {
	$body = searchRequest( $appData, $uri )['body'] ?? null;
	return is_array( $body ) === true ? $body : [];
}

// Two Elements of this section's own, so what the endpoint answers does not
// depend on what the sections above left in the index
\Nino\Elements::insertElement( $appData, '/articles/api-one', [ 'title' => 'Endpoint eins', 'summary' => 'Der erste Treffer', 'keywords' => [ 'api' ], 'author' => 'Dape Team', 'internalCode' => 'E-1' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/articles/api-two', [ 'title' => 'Zweiter Endpoint', 'summary' => 'Der zweite Treffer', 'keywords' => [], 'author' => 'Dape Team', 'internalCode' => 'E-2' ], 'de_DE' );
\Nino\Modules\Search::createIndexes( $appData, 'articles' );

// Off: a public address that hands out content is a decision, so init()
// registers nothing and the address is a 404 like any other
unset( $appData['/nino/http/routes']['GET://.search'] );
\Nino\Modules\Search::init( $appData );
check( 'without the api setting there is no /.search route, and the address is a 404', isset( $appData['/nino/http/routes']['GET://.search'] ) === false
	&& searchRequest( $appData, '/.search?q=endpoint' )['statusCode'] === 404 );

$appData['/nino/features']['search']['settings']['api'] = true;
\Nino\Modules\Search::init( $appData );
check( 'with it, init() registers the route', isset( $appData['/nino/http/routes']['GET://.search'] ) === true );

$answer	= searchRequest( $appData, '/.search?q=endpoint&type=/articles' );
$body		= is_array( $answer['body'] ?? null ) === true ? $answer['body'] : [];
check( 'a query answers 200 with an array the kernel encodes as json, never cached', $answer['statusCode'] === 200
	&& array_keys( $body ) === [ 'query', 'locale', 'types', 'total', 'offset', 'limit', 'hits' ] && ( $answer['header']['Cache-Control'] ?? '' ) === 'no-store' );
check( '...naming what was asked and what was found', ( $body['query'] ?? null ) === 'endpoint' && ( $body['locale'] ?? null ) === 'de_DE' && ( $body['types'] ?? null ) === [ '/articles' ]
	&& ( $body['total'] ?? -1 ) === count( $body['hits'] ?? [] ) && ( $body['total'] ?? 0 ) === 2 );

$first = $body['hits'][0] ?? [];
check( 'a hit is uri, type, score, coverage and fields, best first', array_keys( $first ) === [ 'uri', 'type', 'score', 'coverage', 'fields' ]
	&& ( $first['type'] ?? null ) === '/articles' && ( $first['score'] ?? -1 ) >= ( $body['hits'][1]['score'] ?? 0 ) );

// The fields a hit carries are the ones the project indexed - what it
// decided to search is what it hands out - and nothing else the Element has
$indexedFields = array_values( array_unique( array_values( $appData['/nino/elements/index']['articles'] ) ) );
check( 'the fields are the indexed ones and nothing else - a field the type has but nobody indexed stays off the wire', isset( $first['fields']['title'] ) === true
	&& array_key_exists( 'internalCode', $first['fields'] ?? [] ) === false && array_diff( array_keys( $first['fields'] ?? [] ), $indexedFields ) === [] );

check( 'no query is a 400 saying so, not an empty answer', searchRequest( $appData, '/.search' )['statusCode'] === 400
	&& ( searchJson( $appData, '/.search?q=' )['error'] ?? '' ) === 'q is required' );
check( '...in the English sentence a script reads and nothing else: no code, the endpoint is not the panel\'s',
	searchJson( $appData, '/.search?q=' ) === [ 'error' => 'q is required' ] && searchJson( $appData, '/.search?q=%20' ) === [ 'error' => 'q is required' ] );

$nowhere = searchRequest( $appData, '/.search?q=endpoint&type=/nowhere' );
check( 'a type nobody indexed answers no hits, not an error', $nowhere['statusCode'] === 200 && is_array( $nowhere['body'] ?? null ) === true
	&& ( $nowhere['body']['total'] ?? null ) === 0 && ( $nowhere['body']['hits'] ?? null ) === [] && ( $nowhere['body']['types'] ?? null ) === [] );

$configuredTypes = array_keys( array_filter( \Nino\Modules\Search::configuration( $appData ), static fn( array $entry ): bool => $entry['fields'] !== [] ) );
$everything	= searchJson( $appData, '/.search?q=endpoint&limit=500' );
$second			= searchJson( $appData, '/.search?q=endpoint&limit=1&offset=1' );
check( 'without a type every configured type is searched, the limit is capped, and offset pages through the same order', ( $everything['types'] ?? null ) === $configuredTypes
	&& ( $everything['limit'] ?? null ) === \Nino\Modules\Search::API_LIMIT_MAX && ( $second['limit'] ?? null ) === 1 && ( $second['offset'] ?? null ) === 1 && count( $second['hits'] ?? [] ) === 1
	&& ( $second['hits'][0]['uri'] ?? '' ) === ( $everything['hits'][1]['uri'] ?? '-' ) );

/*	A project shapes a hit in a callback: one for every hit, then one for
	the type, each handed the hit with its whole Element under 'element' -
	so a price can be formatted, a field renamed, a field the index does not
	hold added - and the Element itself never travels	*/
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array &$hit ): void {
	$hit['fields']['kind'] = 'any';
} );
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT. '/articles', static function( array &$appData, array &$hit ): array {
	$hit['fields'] = [ 'kind' => $hit['fields']['kind'], 'title' => $hit['element']['title'], 'code' => (string) ( $hit['element']['internalCode'] ?? '' ) ];
	return $hit;
} );
$hooked			= searchJson( $appData, '/.search?q=endpoint&type=/articles' )['hits'][0] ?? [];
$hookedElement	= \Nino\Elements::getElement( $appData, (string) ( $hooked['uri'] ?? '' ), 'de_DE' );
check( 'a callback shapes the fields - the one for every hit first, the one for the type after it, with the Element in hand', is_array( $hookedElement ) === true
	&& ( $hooked['fields'] ?? null ) === [ 'kind' => 'any', 'title' => $hookedElement['title'], 'code' => (string) ( $hookedElement['internalCode'] ?? '' ) ] );
check( '...and the Element itself never travels', array_key_exists( 'element', $hooked ) === false );

// A callback that answers false drops the hit from the page; the total still
// counts what the index found
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT. '/articles', static fn( array &$appData, array &$hit ): bool => false );
$dropped = searchJson( $appData, '/.search?q=endpoint&type=/articles' );
check( 'a callback answering false drops the hit, and the total says what the index found', ( $dropped['hits'] ?? null ) === [] && ( $dropped['total'] ?? 0 ) > 0 );

unset( $appData['./nino/callbacks'][ \Nino\Modules\Search::HIT ], $appData['./nino/callbacks'][ \Nino\Modules\Search::HIT. '/articles' ],
	$appData['/nino/features']['search']['settings']['api'], $appData['/nino/http/routes']['GET://.search'], $appData['./nino/callbacks']['/nino/http/response/GET://.search'] );
\Nino\Elements::deleteElement( $appData, '/articles/api-one', '*' );
\Nino\Elements::deleteElement( $appData, '/articles/api-two', '*' );
\Nino\Modules\Search::createIndexes( $appData, 'articles' );

echo "\nRead-only failures and the guarded Admin rebuild action\n";

@unlink( $articleIndexPath );
clearstatcache( true, $articleIndexPath );
check( 'a deleted index produces an empty result', \Nino\Modules\Search::getElements( $appData, 'articles', 'neuer' ) === [] );
check( 'the search call leaves a deleted index deleted', is_file( $articleIndexPath ) === false );

$brokenIndex = '<?php return "not an index";';
file_put_contents( $articleIndexPath, $brokenIndex );
if( function_exists( 'opcache_invalidate' ) === true )
	opcache_invalidate( $articleIndexPath, true );
check( 'a non-array index is treated as empty', \Nino\Modules\Search::getElements( $appData, 'articles', 'neuer' ) === [] );
check( 'a read does not replace the malformed file', file_get_contents( $articleIndexPath ) === $brokenIndex );

// A file that does not even parse is no index either: the read says nothing,
// and the file stays for whoever rebuilds
$unparsable = '<?php return [ ;';
file_put_contents( $articleIndexPath, $unparsable );
if( function_exists( 'opcache_invalidate' ) === true )
	opcache_invalidate( $articleIndexPath, true );
ninoWarnings();
check( 'an index that does not parse is an empty result, not a fatal one', \Nino\Modules\Search::getElements( $appData, 'articles', 'neuer' ) === [] && ninoWarnings() === [] );
check( '...and the read leaves it as it is', file_get_contents( $articleIndexPath ) === $unparsable );

// The kernel writes a file whole beside the old one and renames it over: the
// rebuild that replaces this broken file leaves neither half of it nor its
// temporary file behind
check( 'a rebuild replaces the broken file by an index, through the kernel\'s whole-file write', \Nino\Modules\Search::createIndexes( $appData, 'articles' )['created'] === 1
	&& is_array( @include $articleIndexPath ) === true && ( @include $articleIndexPath )['.meta']['format'] === \Nino\Modules\Search::META_FORMAT );
check( '...with no temporary file next to it', glob( dirname( $articleIndexPath ). '/index-articles.php.*' ) === [] );
check( '...and a search finds what it holds at once, in the request that wrote it', searchUris( $appData, 'articles', 'neuer' ) === [ '/articles/alpha' ] );
file_put_contents( $articleIndexPath, $brokenIndex );
if( function_exists( 'opcache_invalidate' ) === true )
	opcache_invalidate( $articleIndexPath, true );

\Nino\Auth::logoutUser( $appData );
[ $status ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ] );
check( 'the Admin rebuild action rejects an unauthenticated request', $status === 401 );
check( 'a rejected request does not touch an index', file_get_contents( $articleIndexPath ) === $brokenIndex );

// Add the second type to the configuration only now. The button contract is to
// rebuild every currently configured index, independently of which one is stale.
$appData['/nino/elements/index']['notes'] = [ 0 => 'title' ];
ninoUser( $appData, 'dev@example.com' );
[ $status, $body ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ] );
check( 'the authenticated Admin rebuild succeeds', $status === 200 );
check( 'one button press rebuilds every configured index', ( $body['created'] ?? null ) === 2
	&& ( $body['elements'] ?? null ) === 3 && ( $body['failed'] ?? null ) === [] );
$rebuiltArticles = @include $articleIndexPath;
$rebuiltNotes = @include $notesIndexPath;
check( 'both derived files were recreated', is_array( $rebuiltArticles ) === true && is_array( $rebuiltNotes ) === true );
check( 'the second type can be searched immediately', searchUris( $appData, 'notes', 'merkzettel' ) === [ '/notes/one' ] );

/*	The write is under the kernel's lock: a lock that cannot be taken - the
	directory of the locks is a file - fails the rebuild whole, with the 500 and
	the English sentence of the server (nobody caused it, so there is no code),
	and the index is as it was. A write in place would not have asked for the lock	*/
$indexBeforeLock = (string) file_get_contents( $articleIndexPath );
$locks = \Nino\Filesystem::path( $appData, '/data/.locks' );
$locksAside = $locks. '-aside';
$hadLocks = file_exists( $locks );
if( $hadLocks === true )
	rename( $locks, $locksAside );
file_put_contents( $locks, 'not a directory' );
ninoWarnings();
[ $lockedStatus, $lockedBody ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ] );
$lockWarnings = ninoWarnings();
unlink( $locks );
if( $hadLocks === true )
	rename( $locksAside, $locks );
check( '...and the one warning in the log is the daily backup\'s, which could not take its lock either', count( $lockWarnings ) === 1 && str_contains( $lockWarnings[0], 'daily backup could not be locked' ) === true );
check( 'a rebuild that cannot take the lock is a 500 with the English sentence and no code', $lockedStatus === 500
	&& $lockedBody === [ 'error' => 'could not create search indexes for /articles, /notes' ] );
check( '...and the index is as it was: whole, and no temporary file beside it', (string) file_get_contents( $articleIndexPath ) === $indexBeforeLock
	&& glob( dirname( $articleIndexPath ). '/index-articles.php.*' ) === [] );
check( '...and once the lock can be taken the same rebuild goes through', \Nino\Modules\Search::createIndexes( $appData )['created'] === 2 );

echo "\nThe panel: the list it draws, the editor that writes config.php, the probe\n";

[ $status, $listed ] = ninoCall( $appData, [ $panel, 'apiList' ] );
check( 'the list action answers the rows, the weights and what a slot may be given',
	$status === 200
	&& is_array( $listed['types'] ?? null ) === true
	&& ( $listed['weights'] ?? null ) === \Nino\Modules\Search::WEIGHTS
	&& ( $listed['indexable'] ?? null ) === \Nino\Modules\Search::INDEXABLE
	&& in_array( 'de_DE', (array) ( $listed['locales'] ?? [] ), true ) === true );

// The editor writes the one config key nobody had an editor for
[ $status, $saved ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'title' ] ] );
check( 'the editor writes one type into /nino/elements/index', $status === 200
	&& ( $saved['type'] ?? null ) === '/notes' && ( $saved['fields'] ?? null ) === [ 0 => 'title' ] );
check( '...into config.php, not only into the running request',
	( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/elements/index']['/notes'] ?? null ) === [ 0 => 'title' ] );
check( '...and the type is searchable straight after a build',
	ninoCall( $appData, [ $panel, 'apiCreateIndex' ], [ 'type' => '/notes' ] )[0] === 200
	&& searchUris( $appData, 'notes', 'merkzettel' ) === [ '/notes/one' ] );

/*	A refusal a person can cause answers the English sentence of its cause, for
	a log, its code and what belongs in the sentence. The script says it in the
	interface language with the sentence the code has in both. The whole body is
	pinned, so a key it gains is seen: it is what the script's errorText() reads.
	A model with a field that carries no text is needed for one of them	*/
check( 'a type with a yes/no field can be created', \Nino\Elements::insertElementType( $appData, '/gadgets', [
	'title' => [ 'type' => 'string' ],
	'active' => [ 'type' => 'boolean' ],
] ) === true );

$refusals = [
	'a field the model does not have'		=> [ [ 'type' => 'notes', 'fields' => [ 0 => 'titel' ] ], 400,
		[ 'error' => 'the model of "/notes" has no field "titel"', 'code' => 'search_field', 'params' => [ '/notes', 'titel' ] ] ],
	'a priority outside the four slots'	=> [ [ 'type' => 'notes', 'fields' => [ 7 => 'title' ] ], 400,
		[ 'error' => 'priority "7" is not 0, 1, 2 or 3', 'code' => 'search_priority', 'params' => [ 7 ] ] ],
	'a field that carries no text'			=> [ [ 'type' => 'gadgets', 'fields' => [ 2 => 'active' ] ], 400,
		[ 'error' => 'the field "active" carries no text to search', 'code' => 'search_text', 'params' => [ 'active' ] ] ],
	'a type that is not one'						=> [ [ 'type' => '../etc/passwd', 'fields' => [] ], 400,
		[ 'error' => 'not an element type name', 'code' => 'search_type' ] ],
	'a type that does not exist'				=> [ [ 'type' => 'nothing-here', 'fields' => [] ], 404,
		[ 'error' => 'there is no element type "/nothing-here"', 'code' => 'search_notype', 'params' => [ '/nothing-here' ] ] ],
	'fields that are no list'						=> [ [ 'type' => 'notes', 'fields' => 'title' ], 400,
		[ 'error' => 'no fields posted', 'code' => 'search_fields' ] ],
	'a slot that holds a list'					=> [ [ 'type' => 'notes', 'fields' => [ 0 => 'title', 1 => [ 'x' ] ] ], 400,
		[ 'error' => 'a field is not a name', 'code' => 'search_fields' ] ],
];

foreach( $refusals as $label => [ $post, $expectedStatus, $expectedBody ] ) {

	[ $status, $body ] = ninoCall( $appData, [ $panel, 'apiSave' ], $post );

	check( "$label is refused with $expectedStatus, the sentence for a log and the code {$expectedBody['code']}", $status === $expectedStatus && $body === $expectedBody );

	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$sentence = searchSentence( $appData, $expectedBody['code'], $locale );
		check( "...which has its sentence in $locale, with a place for each of its ". count( $expectedBody['params'] ?? [] ). " values",
			$sentence !== '' && substr_count( $sentence, '%s' ) === count( $expectedBody['params'] ?? [] ) );
	}
}

$refusalCodes = array_unique( array_column( array_column( $refusals, 2 ), 'code' ) );
check( 'the word of each code is its own: no two codes say the same in one language',
	count( array_unique( array_map( static fn( string $code ): string => searchSentence( $appData, $code, 'en_US' ), $refusalCodes ) ) ) === count( $refusalCodes )
	&& count( array_unique( array_map( static fn( string $code ): string => searchSentence( $appData, $code, 'de_DE' ), $refusalCodes ) ) ) === count( $refusalCodes ) );

// ...and so is one that is not a string at all. Cast, an array raises
// "Array to string conversion", which the kernel treats as fatal - a 500
// where the 400 is the answer
ninoWarnings();
check( 'a type posted as an array is refused like any other, not raised at',
	ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => [ 'notes' ], 'fields' => [] ] )[0] === 400
	&& ninoWarnings() === [] );

/*	A slot holds a name or nothing: a list, a yes/no, a number or a null is no
	name, and is refused as such - cast, a list would raise	*/
check( 'a slot posted as a list, a yes/no, a number or a null is refused with search_fields, not raised at',
	array_unique( array_map( static fn( mixed $slot ): string => (string) ( ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'title', 1 => $slot ] ] )[1]['code'] ?? '' ), [ [ 'x' ], true, 5, null ] ) ) === [ 'search_fields' ]
	&& ninoWarnings() === [] );
check( '...while a slot left empty is left out, and the others are kept', ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'title', 1 => '', 2 => '  ' ] ] )[1]['fields'] === [ 0 => 'title' ] );

[ $status, $all ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ], [ 'type' => [ 'notes' ] ] );
check( 'a rebuild asked for a type posted as a list rebuilds every index, and raises nothing',
	$status === 200 && ( $all['created'] ?? null ) === 2 && ninoWarnings() === [] );

$notesIndex = \Nino\Filesystem::path( $appData, '/data/index-notes.php' );
check( 'the refusals left the configuration as it was',
	( $appData['/nino/elements/index']['/notes'] ?? null ) === [ 0 => 'title' ] && is_file( $notesIndex ) === true );

/*	Taking every field out is how a type stops being searchable, and the index
	goes with it: a derived file nobody searches is a copy of the content with
	nothing reading it */
[ $status, $removed ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => '/notes', 'fields' => [] ] );
check( 'an empty field map takes the type out of the configuration', $status === 200 && ( $removed['removed'] ?? null ) === true
	&& isset( $appData['/nino/elements/index']['/notes'] ) === false );
check( '...and removes the derived file with it', is_file( $notesIndex ) === false );
check( '...and it is gone from config.php too',
	isset( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/elements/index']['/notes'] ) === false );

/*	The one name a slot may be given that is no field of the model: the
	Element's own address, for a visitor who types a product code or a slug.
	Offered first by the list, taken by the editor, indexed like a field -
	'one' is nowhere in the note's title, only in its address */
check( 'the editor offers the Element\'s address first, beside the model',
	( array_column( \Nino\Modules\Search::indexState( $appData ), 'model', 'type' )['/notes'][0] ?? null ) === \Nino\Modules\Search::URI_FIELD );
[ $status, $savedAddress ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'title', 1 => '.uri' ] ] );
check( '...and takes it', $status === 200 && ( $savedAddress['fields'] ?? null ) === [ 0 => 'title', 1 => '.uri' ] );
check( '...so a visitor who types the slug finds the Element by its address',
	ninoCall( $appData, [ $panel, 'apiCreateIndex' ], [ 'type' => '/notes' ] )[0] === 200
	&& searchUris( $appData, 'notes', 'one' ) === [ '/notes/one' ] );
/*	Where an index lives is Search's to say: the file a rebuild of /notes wrote
	is the one removeIndex() takes, and a second call finds nothing to take	*/
check( 'removing a type\'s index takes the file its rebuild wrote, and says whether there was one',
	is_file( $notesIndex ) === true
	&& \Nino\Modules\Search::removeIndex( $appData, '/notes' ) === true
	&& is_file( $notesIndex ) === false
	&& \Nino\Modules\Search::removeIndex( $appData, '/notes' ) === false );
ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => '/notes', 'fields' => [] ] );

// The screen the panel exists for
[ $status, $probe ] = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'types' => [ 'articles' ], 'query' => 'orbit' ] );
check( 'the probe answers the hits a page would get, in the same order', $status === 200
	&& array_column( (array) ( $probe['hits'] ?? [] ), 'uri' ) === [ '/articles/orbit-summary' ] );
check( 'a hit is labelled by its strongest configured field rather than by its uri',
	( $probe['hits'][0]['label'] ?? null ) === 'Remote station' );
check( 'and says what it scored, how much of the query it covered and where it matched',
	( $probe['hits'][0]['score'] ?? null ) === 1.75
	&& ( $probe['hits'][0]['coverage'] ?? null ) === 1.0
	&& ( $probe['hits'][0]['matched'] ?? null ) === [ 'summary' ] );
check( 'the probe answers for the locale it is asked about',
	array_column( (array) ( ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'types' => [ 'articles' ], 'query' => 'lighthouses', 'locale' => 'en_US' ] )[1]['hits'] ?? [] ), 'uri' ) === [ '/articles/alpha' ] );
$appData['./nino/locales/current'] = 'de_DE';

check( 'a dashboard tile counts what is really indexed', is_array( $tile = \Nino\Modules\Search\Admin::summary( $appData ) ) === true
	&& (int) $tile['value'] > 0 && str_starts_with( (string) $tile['label'], '/_admin/search/label/tile' ) );

/*	Every action sits behind the one permission, and the two ways of not having
	it are different answers: no session at all is a 401, a session without the
	permission a 403. The editor writes config.php, so this is the guard that
	matters most in the panel */
\Nino\Auth::logoutUser( $appData );
check( 'every action of the panel refuses a request with no session',
	ninoCall( $appData, [ $panel, 'apiList' ] )[0] === 401
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'articles', 'fields' => [] ] )[0] === 401
	&& ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'types' => [ 'articles' ], 'query' => 'orbit' ] )[0] === 401 );

ninoUser( $appData, 'editor@example.com', [ '/_admin/elements/manage' ] );
check( '...and an account without /_admin/search/manage with a 403',
	ninoCall( $appData, [ $panel, 'apiList' ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'articles', 'fields' => [] ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiCreateIndex' ] )[0] === 403
	&& ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'types' => [ 'articles' ], 'query' => 'orbit' ] )[0] === 403 );
check( 'a refused save changed nothing', ( $appData['/nino/elements/index']['/articles'] ?? null ) !== [] );
\Nino\Auth::logoutUser( $appData );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

check( 'the panel names every action it answers', array_keys( \Nino\Modules\Search\Admin::actions() )
	=== [ 'search/list', 'search/save', 'search/createindex', 'search/probe' ] );
check( 'it ships the three panes its screens live in', \Nino\Modules\Search\Admin::panes() === [ 'search-list', 'search-type', 'search-probe' ] );
check( 'and the two assets they are drawn with', count( \Nino\Modules\Search\Admin::assets() ) === 2 );

check( 'a save is written to the activity log by name',
	\Nino\Modules\Search\Admin::log( 'search/save', [ 'type' => '/articles' ] ) === 'Configure Search Index (/articles)' );

// Put the second type back the way this section set it up: what follows turns
// both index files into directories and expects both to be configured
$appData['/nino/elements/index']['notes'] = [ 0 => 'title' ];
\Nino\Modules\Search::createIndexes( $appData );


$sentinel = '<?php return ["sentinel" => true];';
file_put_contents( $articleIndexPath, $sentinel );
file_put_contents( $notesIndexPath, $sentinel );
[ $status, $body ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ] );
check( 'every later button press rebuilds all indexes again', $status === 200 && ( $body['created'] ?? 0 ) === 2 );
check( 'a full rebuild replaces existing index content',
	file_get_contents( $articleIndexPath ) !== $sentinel
	&& file_get_contents( $notesIndexPath ) !== $sentinel );

$configuredIndexes = $appData['/nino/elements/index'];
$appData['/nino/elements/index'] = [];
[ $status, $body ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ] );
check( 'an empty configuration is a successful no-op', $status === 200
	&& $body === [ 'created' => 0, 'elements' => 0, 'failed' => [], 'skipped' => [], 'issues' => [] ] );
$appData['/nino/elements/index'] = $configuredIndexes;

// Turn both target filenames into directories. /data and its lock directory
// stay usable for an Element commit, while file_put_contents() cannot open
// either index target. This does not depend on permission bits (the test
// process may run as root).
@unlink( $articleIndexPath );
@unlink( $notesIndexPath );
mkdir( $articleIndexPath );
mkdir( $notesIndexPath );

$committedDespiteIndexFailure = \Nino\Elements::updateElement(
	$appData,
	'/articles/alpha',
	[ 'title' => 'Persisted despite index failure' ],
	'de_DE'
);
check( 'an index failure cannot roll back the Element commit it follows',
	is_array( $committedDespiteIndexFailure ) === true
	&& ( $committedDespiteIndexFailure['title'] ?? null ) === 'Persisted despite index failure' );

$failed = \Nino\Modules\Search::createIndexes( $appData );
check( 'write failures are reported per configured type', ( $failed['created'] ?? null ) === 0
	&& ( $failed['elements'] ?? null ) === 0 && ( $failed['failed'] ?? null ) === [ '/articles', '/notes' ] );
[ $status, $body ] = ninoCall( $appData, [ $panel, 'apiCreateIndex' ] );
check( 'the Admin action turns any index write failure into a 500',
	$status === 500
	&& str_contains( (string) ( $body['error'] ?? '' ), '/articles, /notes' ) === true );

// --- The panel's script, where node is on the path ----------------------------
//
// search-js-smoke.js beside this file runs the panel's script over a dom
// stand-in - its request, the type editor's status line and what it tells the
// shell about unsaved input; this suite runs it too where node is on the path,
// the way forms-smoke.php does, so bin/check.sh and CI cover both halves in one go
$jsTest	= __DIR__. '/search-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: search-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'search-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
