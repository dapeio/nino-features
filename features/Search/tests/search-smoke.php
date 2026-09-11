<?php
declare(strict_types=1);

/**
 *	Nino
 *	search-smoke.php	Contract test for the Search feature (Modules\Search):
 *									configuration, activation, derived files, fuzzy ranking,
 *									locale selection, committed Elements writes, and the
 *									guarded /_admin rebuild action. Travels with the feature
 *									and runs against the checkout three levels up, or the one
 *									NINO_ROOT names (see tests/harness.php there).
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

function callSearchIndexAction( array &$appData ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Search\Admin::apiCreateIndex( $appData, $request );
	return [
		$request['/nino/http/response']['statusCode'],
		$request['/nino/http/response']['body'],
	];
}

$appData = ninoSandbox( 'search' );
$sandbox = ninoSandboxDir( $appData );
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
	&& ( $state['/missing-type']['exists'] ?? null ) === false );

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

echo "\nThe two shortcodes a page puts a search on\n";

/*	Rendered the way a template renders: through \Nino\Html::renderHtml(), so
	the fills in the attributes resolve before the shortcode sees them and the
	[[field]] placeholders in the body survive to it, exactly as they do in a
	project (see Html::renderHtml()'s order) */
$appData['./nino/html/fills'] = [];
\Nino\Html::addFills( $appData, [
	'/page-products/search/submit' 			=> 'Los',
	'/page-products/search/placeholder' => 'Was suchen Sie?',
], '*' );

$searchForm = \Nino\Html::renderHtml( $appData, '[search submit="[[/page-products/search/submit]]" placeholder="[[/page-products/search/placeholder]]"]' );
check( 'the form is a plain GET form with a search field and a button',
	str_contains( $searchForm, 'method="get"' ) === true
	&& str_contains( $searchForm, 'role="search"' ) === true
	&& str_contains( $searchForm, 'type="search" name="q"' ) === true );
check( 'its labels come out of the fills the attributes named',
	str_contains( $searchForm, '>Los</button>' ) === true
	&& str_contains( $searchForm, 'placeholder="Was suchen Sie?"' ) === true
	&& str_contains( $searchForm, 'aria-label="Was suchen Sie?"' ) === true );

$plainForm = \Nino\Html::renderHtml( $appData, '[search]' );
check( 'a bare [search] still says something, in the interface language',
	str_contains( $plainForm, '>Suchen</button>' ) === true && str_contains( $plainForm, 'placeholder="Suchbegriff"' ) === true );
check( 'a fill the project never defined does not reach the visitor as brackets',
	str_contains( \Nino\Html::renderHtml( $appData, '[search submit="[[/nothing/defined/here]]"]' ), '[[' ) === false );

$_GET = [ 'q' => 'orbit' ];
check( 'the field carries back what was searched for',
	str_contains( \Nino\Html::renderHtml( $appData, '[search]' ), 'value="orbit"' ) === true );

// The shape from the feature's own README, with the type given the way a
// person writes it
$block = '[search-results key="q" type="/articles"]<h5>[[title]]</h5><p>[[summary]]</p>[/search-results]';
$results = \Nino\Html::renderHtml( $appData, $block );
check( 'the body is the row markup, once per hit',
	substr_count( $results, '<h5>' ) === 1 && str_contains( $results, '<h5>Remote station</h5>' ) === true );
check( 'and the rows come wrapped, so a project has something to style',
	str_starts_with( $results, '<div class="nino-search-results">' ) === true );

$_GET = [ 'q' => 'nowhere at all' ];
check( 'a query that finds nothing renders nothing without an empty text',
	\Nino\Html::renderHtml( $appData, $block ) === '' );
check( '...and says so with one', str_contains(
	\Nino\Html::renderHtml( $appData, '[search-results type="/articles" empty="Nichts gefunden."]<p>[[title]]</p>[/search-results]' ), 'Nichts gefunden.' ) === true );

$_GET = [];
check( 'nothing searched for is not the same as nothing found', \Nino\Html::renderHtml( $appData, $block ) === '' );

$_GET = [ 'my_own_get_var_key' => 'orbit' ];
check( 'both shortcodes read whichever query variable they are told to',
	str_contains( \Nino\Html::renderHtml( $appData, '[search key="my_own_get_var_key"]' ), 'name="my_own_get_var_key"' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[search-results key="my_own_get_var_key" type="articles"]<p>[[title]]</p>[/search-results]' ), 'Remote station' ) === true );
check( 'a result block reading another key than the form stays empty',
	\Nino\Html::renderHtml( $appData, $block ) === '' );

$_GET = [ 'q' => 'orbit' ];
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
check( 'the wrapper takes the class it is given', str_contains(
	\Nino\Html::renderHtml( $appData, '[search-results type="articles" tag="ul" class="produkte" limit="1"]<li>[[title]]</li>[/search-results]' ), '<ul class="produkte">' ) === true );

\Nino\Elements::deleteElement( $appData, '/articles/markup', '*' );
$_GET = [];

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

\Nino\Auth::logoutUser( $appData );
[ $status ] = callSearchIndexAction( $appData );
check( 'the Admin rebuild action rejects an unauthenticated request', $status === 401 );
check( 'a rejected request does not touch an index', file_get_contents( $articleIndexPath ) === $brokenIndex );

// Add the second type to the configuration only now. The button contract is to
// rebuild every currently configured index, independently of which one is stale.
$appData['/nino/elements/index']['notes'] = [ 0 => 'title' ];
\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );
[ $status, $body ] = callSearchIndexAction( $appData );
check( 'the authenticated Admin rebuild succeeds', $status === 200 );
check( 'one button press rebuilds every configured index', ( $body['created'] ?? null ) === 2
	&& ( $body['elements'] ?? null ) === 3 && ( $body['failed'] ?? null ) === [] );
$rebuiltArticles = @include $articleIndexPath;
$rebuiltNotes = @include $notesIndexPath;
check( 'both derived files were recreated', is_array( $rebuiltArticles ) === true && is_array( $rebuiltNotes ) === true );
check( 'the second type can be searched immediately', searchUris( $appData, 'notes', 'merkzettel' ) === [ '/notes/one' ] );

$sentinel = '<?php return ["sentinel" => true];';
file_put_contents( $articleIndexPath, $sentinel );
file_put_contents( $notesIndexPath, $sentinel );
[ $status, $body ] = callSearchIndexAction( $appData );
check( 'every later button press rebuilds all indexes again', $status === 200 && ( $body['created'] ?? 0 ) === 2 );
check( 'a full rebuild replaces existing index content',
	file_get_contents( $articleIndexPath ) !== $sentinel
	&& file_get_contents( $notesIndexPath ) !== $sentinel );

$configuredIndexes = $appData['/nino/elements/index'];
$appData['/nino/elements/index'] = [];
[ $status, $body ] = callSearchIndexAction( $appData );
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
[ $status, $body ] = callSearchIndexAction( $appData );
check( 'the Admin action turns any index write failure into a 500',
	$status === 500
	&& str_contains( (string) ( $body['error'] ?? '' ), '/articles, /notes' ) === true );

ninoDone( $appData );
