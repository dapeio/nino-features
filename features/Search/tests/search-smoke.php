<?php
declare(strict_types=1);

// The test of the feature Search: structure and behaviour, one pin each - the components, the index and its
// ranking, what is searched for and what is shown, the json endpoint, the panel. php features/Search/tests/search-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$panel = \Nino\Modules\Search\Admin::class;
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old kind', is_array( $manifest ) === true && $manifest['key'] === 'search'
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false && isset( $manifest['stacks'] ) === false );

$appData = ninoSandbox( 'search' );
$appData['./nino/filesystem/path'] = $root;
$sandbox = ninoSandboxDir( $appData );
$articleIndex = \Nino\Filesystem::path( $appData, '/data/index-articles.php' );

// The elements come first and the module after them, so that the index is built once by a call and not by every write
$appData['/nino/elements/index'] = [
	// The spelling of a project of Nino 1: no slash in front, a fifth priority that does not exist, a name that is no type, a type nobody made
	'articles'		=> [ 0 => 'title', 1 => 'summary', 2 => 'keywords', 3 => 'author', 4 => 'title' ],
	'/notes'			=> [ 0 => 'title' ],
	'/ignored'		=> [ 0 => 'missing-field' ],
	'/missing-type'	=> [ 0 => 'title' ],
	'not/a/type'	=> [ 0 => 'title' ],
];
$text = [ 'type' => 'string', 'locale' => true ];
\Nino\Elements::insertElementType( $appData, '/articles', [ 'title' => $text, 'summary' => $text, 'keywords' => [ 'type' => 'array' ], 'author' => $text, 'internalCode' => [ 'type' => 'string' ], 'score' => [ 'type' => 'string' ] ] );
\Nino\Elements::insertElementType( $appData, '/notes', [ 'title' => $text, 'body' => $text ] );
\Nino\Elements::insertElementType( $appData, '/ignored', [ 'title' => $text ] );
\Nino\Elements::insertElementType( $appData, '/plain', [ 'title' => $text, 'active' => [ 'type' => 'boolean' ] ] );
\Nino\Elements::insertElement( $appData, '/articles/alpha', [ 'title' => 'Müller Katalog', 'summary' => '<strong>Leuchttürme</strong> &amp; klare Wege in Berlin', 'keywords' => [ 'CMS', 'Leicht' ], 'author' => 'Dape Team', 'internalCode' => 'XQZ-4417', 'score' => 'mine' ], 'de_DE' );
\Nino\Elements::updateElement( $appData, '/articles/alpha', [ 'title' => 'Miller Catalog', 'summary' => 'Lighthouses and clear routes in Berlin', 'author' => 'Dape Team' ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/articles/orbit-title', [ 'title' => 'Orbit', 'summary' => 'A remote observatory', 'keywords' => [], 'internalCode' => 'XQZ-4418' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/articles/orbit-summary', [ 'title' => 'Remote station', 'summary' => 'Orbit', 'keywords' => [], 'internalCode' => 'XQZ-4419' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/articles/hostile', [ 'title' => '[csrf] <b>x</b> [template /templates/evil] [[/project/company/general/name]]', 'summary' => 'Orbit trap', 'keywords' => [], 'internalCode' => 'XQZ-4420' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/notes/one', [ 'title' => 'Merkzettel', 'body' => 'Orbit nur hier' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/ignored/one', [ 'title' => 'Not indexed' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/plain/one', [ 'title' => 'Orbit plain', 'active' => true ], 'de_DE' );

check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'search' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
check( 'switching it on and starting it writes no index: building one is a decision of the panel or of a save', is_file( $articleIndex ) === false );
$created = \Nino\Modules\Search::createIndexes( $appData );
check( 'a rebuild names what it made and what it had to skip, with the reason', $created['created'] === 2 && $created['failed'] === [] && count( $created['skipped'] ) === 3 && isset( $created['skipped']['/ignored'] ) === true );

$ignored = ninoWarnings();

/**	The request of a page that carries a query: the first request is the one the components read	*/
$visit = static function( array $query, string $uri = '/search' ) use ( &$appData ): void {
	$appData['./nino/http/requests'] = [];
	ninoVisit( $appData, $uri. ( $query === [] ? '' : '?'. http_build_query( $query ) ) );
};
$draw = static function( string $source ) use ( &$appData ): string {
	// A request ranks once; this suite changes the hook and the index between its calls
	\Nino\Modules\Search::forget( $appData );
	return trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-search' ] + \Nino\Html::context( $appData ) ) );
};
$uris = static function( string $query, string|array $type = 'articles' ) use ( &$appData ): array {
	\Nino\Modules\Search::forget( $appData );
	return array_column( \Nino\Modules\Search::getElements( $appData, $type, $query ), '.uri' );
};

echo "\nThe components\n";

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'search' ) === [] );
$schemas = \Nino\Components::components( $appData );
$mine = array_intersect_key( $schemas, array_flip( [ 'search-form', 'search-results', 'search-count' ] ) );
check( 'the three components are in their group, labelled in both languages, with an icon of the table and a standard part',
	count( $mine ) === 3 && array_filter( $mine, static fn( array $s ): bool => $s['group'] !== 'search' || isset( $s['label']['de_DE'], $s['label']['en_US'] ) === false || in_array( $s['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['search']['part'] ?? false ) === true );
$unlabelled = [];
foreach( $mine as $name => $schema )
	foreach( $schema['attributes'] as $attribute => $declared )
		if( isset( $declared['label']['de_DE'], $declared['label']['en_US'] ) === false )
			$unlabelled[] = $name. '.'. $attribute;
check( 'every attribute has a label in both languages', $unlabelled === [] );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/search/part/part.css' ), [ 'search-form', 'search-results', 'search-count' ] ) === [] );
check( 'a tool offers the field in a column and in the actions of the header, the list in a column and in a row, and the list is a loop that draws no element of its own',
	in_array( 'search-form', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true && in_array( 'search-form', \Nino\Components::allowed( $appData, 'header-actions' ), true ) === true
	&& in_array( 'search-results', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true && in_array( 'search-results', \Nino\Components::allowed( $appData, 'section-row' ), true ) === true
	&& ( $schemas['search-results']['through'] ?? false ) === true && ( $schemas['search-results']['children'] ?? '' ) === 'nodes' );
$reflection = new \ReflectionClass( \Nino\Modules\Search::class );
check( 'the limits of the schema are the limits of the code: the length of a query, the hits of a list',
	$schemas['search-results']['attributes']['limit']['max'] === $reflection->getConstant( 'RESULTS_MAX' ) && $reflection->getConstant( 'RESULTS_MAX' ) === 200
	&& $reflection->getConstant( 'MAX_QUERY_LENGTH' ) === 256 && count( $reflection->getConstant( 'WEIGHTS' ) ) === 4 );

// The field
$visit( [] );
$form = $draw( '[search-form]' );
check( 'the field is a get form that needs no script: a search input with the name of the key, a button that submits, a name for a screen reader',
	preg_match( '~^<form class="search-form" method="get" role="search"><label class="search-form-field"><span class="search-form-label">[^<]+</span><input class="search-form-input" type="search" name="q" value="" maxlength="256"~', $form ) === 1
	&& str_contains( $form, '<button class="search-form-button" type="submit">' ) === true && str_contains( $form, 'action=' ) === false && str_contains( $form, '[[' ) === false );
check( 'the results page is the action, and the key is the name of the input', str_contains( $draw( '[search-form action="/ergebnisse" key="s"]' ), ' action="/ergebnisse"' ) === true && str_contains( $draw( '[search-form key="s"]' ), 'name="s"' ) === true );
check( 'a key that is no name of a field is the default, and no address that is a script is an action',
	str_contains( $draw( '[search-form key="a&quot;><script>"]' ), 'name="q"' ) === true && str_contains( $draw( '[search-form action="javascript:alert(1)"]' ), 'action=' ) === false );

$hostile = '"><script>alert(1)</script> [csrf] [template /templates/evil] [[/project/company/general/name]] & x';
$visit( [ 'q' => $hostile ] );
$typed = $draw( '[search-form]' );
check( 'what was searched for stands in the field as text, escaped once: a quote, a tag, a call and a fill are what they are',
	str_contains( $typed, 'value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt; [csrf] [template /templates/evil] [[/project/company/general/name]] &amp; x"' ) === true && str_contains( $typed, '<script' ) === false );

$visit( [ 'q' => str_repeat( 'ä', 5000 ) ] );
preg_match( '~ value="([^"]*)"~', $draw( '[search-form]' ), $field );
check( 'a query of five thousand letters is cut at the length the field takes, in the field and in the search', mb_strlen( $field[1] ) === 256
	&& mb_strlen( \Nino\Modules\Search::query( $appData, 'q' ) ) === 256 );

// What is searched for, and where
$visit( [ 'q' => "  orbit \n" ] );
check( 'the query is the one of the request being answered, trimmed', \Nino\Modules\Search::query( $appData, 'q' ) === 'orbit' && \Nino\Modules\Search::query( $appData, 'p' ) === '' );
$visit( [ 'q' => [ 'orbit' ] ] );
check( 'a variable sent as a list is nothing searched for, and nothing is drawn for nothing',
	$draw( '[search-count]' ) === '' && $draw( '[search-results][text .title][/search-results]' ) === '' && \Nino\Modules\Search::query( $appData, 'q' ) === '' );
$_GET = [ 'q' => 'orbit' ];
$visit( [] );
check( 'it is the request\'s and not php\'s: the same words in $_GET, none in the request, are nothing searched for', $draw( '[search-count]' ) === '' );
$_GET = [];
$visit( [ 'q' => 'orbit' ] );
$kept = $appData['./nino/http/requests'];
$appData['./nino/http/requests'] = [];
check( 'with no request at all - a page drawn by a script - nothing was searched for, and nothing is raised', $draw( '[search-count]' ) === '' && $draw( '[search-results][text .title][/search-results]' ) === '' && ninoWarnings() === [] );
$appData['./nino/http/requests'] = $kept;

// The hits
$list = $draw( '[search-results][section-title .title tag="h3"][/search-results]' );
check( 'the list draws what it holds once for each hit, the best first, the title of a hit as the source', preg_match_all( '~<h3 class="section-title">([^<]*)</h3>~', $list, $titles ) === 3
	&& $titles[1][0] === 'Orbit' && $titles[1][2] === 'Remote station' );
check( 'the title of a hit that spells a tag, a call and a fill is text, and the hit that it is stays in the list',
	str_contains( $list, '[csrf] &lt;b&gt;x&lt;/b&gt; [template /templates/evil] [[/project/company/general/name]]' ) === true && str_contains( $list, '<b>' ) === false );
check( 'a hit is drawn by the sources .uri and .slug, and the number of the loop is a fill of its own',
	preg_match( '~^<div class="text">/articles/orbit-title</div><div class="text">orbit-title</div><div class="text"><p>0 orbit-title</p></div>$~', $draw( '[search-results limit="1"][text .uri][text .slug][text text="[[.id]] [[.slug]]"][/search-results]' ) ) === 1 );
$visit( [ 'q' => 'katalg' ] );
check( 'a type with a field of its own called score keeps it: the number of the match is only there where the type has none',
	str_contains( $draw( '[search-results /articles][text .score][/search-results]' ), '>mine<' ) === true );
$visit( [ 'q' => 'merkzettel' ] );
check( '...and a type that has none reads the number of the match as the source .score', preg_match( '~^<div class="text">[0-9.]+</div>$~', $draw( '[search-results /notes][text .score][/search-results]' ) ) === 1 );
$visit( [ 'q' => 'orbit' ] );
check( 'limit cuts the list and the number is not the page: three hits, one drawn, three counted',
	substr_count( $draw( '[search-results limit="1"][text .uri][/search-results]' ), 'class="text"' ) === 1 && str_contains( $draw( '[search-count]' ), '>3<' ) === true );
$visit( [ 'q' => 'orbit merkzettel' ] );
check( 'hits of several types stand in one list, the ones a source names and the ones the attribute names', substr_count( $draw( '[search-results type="/articles,/notes"][text .uri][/search-results]' ), 'class="text"' ) === 4
	&& substr_count( $draw( '[search-results /notes type="/articles"][text .uri][/search-results]' ), 'class="text"' ) === 4 && substr_count( $draw( '[search-results /notes][text .uri][/search-results]' ), 'class="text"' ) === 1 );
$visit( [ 'q' => 'orbit' ] );
$outOfBounds = $draw( '[search-results limit="999999"][text .uri][/search-results]' );
check( 'a limit beyond the most there is is the most, and the register holds a limit to its bounds', substr_count( $outOfBounds, 'class="text"' ) === 3 && ( \Nino\Components::components( $appData )['search-results']['attributes']['limit']['min'] ?? 0 ) === 1 );
check( 'a list inside a list draws nothing, so that the rounds of a page are the limit and not its square',
	substr_count( $draw( '[search-results limit="2"][text .uri][search-results][text .uri][/search-results][/search-results]' ), 'class="text"' ) === 2 );
check( 'columns of a row are what a list draws inside a row, and a loop adds no element of its own',
	$draw( '[section-wrap id="s"][section-row][search-results limit="2"][section-col][text .uri][/section-col][/search-results][/section-row][/section-wrap]' ) === '<section id="s" class="section-wrap"><div class="section-row"><div class="section-col"><div class="text">/articles/orbit-title</div></div><div class="section-col"><div class="text">/articles/hostile</div></div></div></section>' );

// What is not there
$visit( [ 'q' => 'zzzzzzzz' ] );
check( 'a search that found nothing says so with the word of the feature, or with the text of the call, escaped once and read as no call',
	preg_match( '~^<p class="search-results-empty">[^<]+</p>$~', $draw( '[search-results][text .title][/search-results]' ) ) === 1
	&& $draw( '[search-results empty="No [csrf] <b> &amp;"][text .title][/search-results]' ) === '<p class="search-results-empty">No [csrf] &lt;b&gt; &amp;</p>' );
check( 'it counts 0 where something was searched for and nothing fits', str_contains( $draw( '[search-count]' ), '>0<' ) === true );
$visit( [ 'q' => 'orbit' ] );
check( 'a type nobody indexed has no hits: nothing is found in it, though it has a title that fits, and it counts 0',
	$uris( 'orbit', '/plain' ) === [] && str_contains( $draw( '[search-results /plain][text .title][/search-results]' ), 'Orbit plain' ) === false && str_contains( $draw( '[search-count type="/plain"]' ), '>0<' ) === true );
check( 'a field nobody indexed is not searched: its words find nothing', $uris( 'XQZ-4417' ) === [] && $uris( 'internalCode' ) === [] );
check( 'a name that names no type searches nothing and does not widen to every type, a placeholder type names nothing and no type at all is every indexed one',
	$draw( '[search-count type="not a type, ../etc"]' ) === '' && $draw( '[search-results type="../etc"][text .uri][/search-results]' ) === '' && $draw( '[search-results /_placeholder][text .uri][/search-results]' ) !== '' && str_contains( $draw( '[search-count]' ), '>3<' ) === true );
check( 'the number is a plain number inside a sentence of the page where it is bare, and says its word, in the singular for one, where it is not',
	preg_match( '~^<span class="search-count search-count--bare"><span class="search-count-number">\d+</span></span>$~', $draw( '[search-count bare]' ) ) === 1
	&& preg_match( '~^<p class="search-count"><span class="search-count-number">3</span> [^<]+</p>$~', $draw( '[search-count]' ) ) === 1 );
check( 'the key names the query variable of a list and of a count alike, and a list reading another one stays empty',
	( static function() use ( $visit, $draw ): bool { $visit( [ 'zoek' => 'orbit' ] ); return str_contains( $draw( '[search-count key="zoek"]' ), '>3<' ) === true && $draw( '[search-count]' ) === '' && $draw( '[search-results][text .title][/search-results]' ) === ''; } )() );
$visit( [ 'q' => 'orbit' ] );

// The hook that holds hits back
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HITS, static function( array &$appData, array $hits ): array {
	return array_values( array_filter( $hits, static fn( array $hit ): bool => $hit['uri'] !== '/articles/orbit-title' ) );
} );
check( 'a hit a feature holds back is out of the list, of the count and of the elements: they all read the list that is left',
	str_contains( $draw( '[search-results][text .uri][/search-results]' ), '/articles/orbit-title' ) === false && str_contains( $draw( '[search-count]' ), '>2<' ) === true && in_array( '/articles/orbit-title', $uris( 'orbit' ), true ) === false );
$appData['./nino/callbacks'][\Nino\Modules\Search::HITS] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HITS, static function( array &$appData, array $hits ): array {
	$hits[] = [ 'uri' => '/articles/alpha', 'type' => '/articles', 'score' => 99.0 ];
	$hits[0]['uri'] = '/articles/alpha';
	return $hits;
} );
\Nino\Modules\Search::forget( $appData );
check( 'a hook can only take hits from the list: a hit it adds is not read, and one it alters is gone with what it was', array_column( \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit' ), 'uri' ) === [ '/articles/hostile', '/articles/orbit-summary' ] );
$appData['./nino/callbacks'][\Nino\Modules\Search::HITS] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HITS, static fn( array &$appData, array $hits ): string => 'nothing' );
\Nino\Modules\Search::forget( $appData );
check( 'a hook that answers anything but a list leaves an empty result, and holds back what it was meant to hold back', \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit' ) === [] );
$appData['./nino/callbacks'][\Nino\Modules\Search::HITS] = [];

// One request ranks once: the number and the list of a page are one ranking, so the hook that holds hits back runs once for both
$ran = new \ArrayObject();
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HITS, static function( array &$appData, array $hits ) use ( $ran ): array { $ran[] = count( $hits ); return $hits; } );
$page = $draw( '[search-count bare][search-results][text .uri][/search-results]' );
check( 'the hook of a page with the number and the list runs once, and the number is the number of hits the list draws',
	count( $ran ) === 1 && preg_match( '~search-count-number">(\d+)</span>~', $page, $counted ) === 1 && (int) $counted[1] === substr_count( $page, 'class="text"' ) && $counted[1] !== '0' );
$appData['./nino/callbacks'][\Nino\Modules\Search::HITS] = [];

// An entry of the index whose element is gone is none of the hits: the number does not count what the list cannot draw
$index = \Nino\Filesystem::getFileContent( $appData, '/data/index-articles.php', [] );
$index['de_DE']['/articles/ghost'] = [ 0 => 'orbit ghost' ];
\Nino\Filesystem::putFileContent( $appData, '/data/index-articles.php', $index );
$page = $draw( '[search-count bare][search-results][text .uri][/search-results]' );
check( 'the number is the number of hits the list would draw: an element that is gone from an index that was not rebuilt is in neither', str_contains( $page, 'ghost' ) === false
	&& preg_match( '~search-count-number">(\d+)</span>~', $page, $counted ) === 1 && (int) $counted[1] === substr_count( $page, 'class="text"' ) );
\Nino\Modules\Search::createIndexes( $appData, 'articles' );

// The lang attribute of a hit: the tag is escaped where it is written
$lang = new \ReflectionMethod( \Nino\Modules\Search::class, '_lang' );
$langAttribute = (string) $lang->invoke( null, [ 'title' => 'x', '.langs' => [ 'title' => '"><x onfocus=1>' ] ], [ 'title' => [ 'type' => 'string', 'locale' => true ] ] );
check( 'the tag of the language a hit is in is written escaped, whatever it holds', $langAttribute !== '' && preg_match( '/[<>]/', $langAttribute ) === 0 && substr_count( $langAttribute, '"' ) === 2 );

// The address of a hit: the list asks the hook of the json endpoint once for each hit it draws and takes the address from the answer, and nothing else
$visit( [ 'q' => 'orbit' ] );
$asked = new \ArrayObject();
$answers = [ '/articles/orbit-title' => '/read/orbit-title', '/articles/orbit-summary' => 'https://evil.example/x', '/articles/hostile' => '/read/"><script>alert(1)</script> [csrf]' ];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ) use ( $asked, $answers ): array {
	$asked[] = $hit['uri'];
	$hit['url'] = $answers[ $hit['uri'] ] ?? '';
	return $hit;
} );
$linked = '[search-results][section-title .title tag="h3" href=".link"][text .url][/search-results]';
$page = $draw( $linked );
check( 'a hit that has an address is a link on its title and says it as .url, the one that has none is a title and no link, and no link is empty',
	str_contains( $page, '<a href="/read/orbit-title"' ) === true && preg_match( '~<h3 class="section-title">Remote station</h3>~', $page ) === 1 && str_contains( $page, 'href=""' ) === false && str_contains( $page, '<a href="https' ) === false );
check( 'the hook is asked once for each hit that is drawn and once for no other: a list of one asks for one', count( $asked ) === 3 && count( array_unique( (array) $asked ) ) === 3
	&& ( static function() use ( $draw, $asked ): bool { $asked->exchangeArray( [] ); $draw( '[search-results limit="1"][text .url][/search-results]' ); return count( $asked ) === 1; } )() );
check( 'an address that is not a path of this site is dropped, and is no field of the hit: a host, a protocol-relative address, a script, a space, a control character, a list',
	( static function() use ( &$appData, $draw ): bool {
		foreach( [ 'https://evil.example/x', '//evil.example/x', '/\\evil.example', 'javascript:alert(1)', '/a b', "/a\nb", '/js:x', 'relative/path', '' ] as $bad ) {
			$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ) use ( $bad ): array { $hit['url'] = $bad; return $hit; } );
			$one = $draw( '[search-results limit="1"][section-title .title href=".link"][text .url][text .link][/search-results]' );
			if( str_contains( $one, '<a ' ) === true || str_contains( $one, 'class="text"' ) === true && preg_match( '~<div class="text">[^<]+</div>~', $one ) === 1 )
				return false;
		}
		$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
		\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ): array { $hit['url'] = [ '/x' ]; return $hit; } );
		return str_contains( $draw( '[search-results limit="1"][section-title .title href=".link"][/search-results]' ), '<a ' ) === false;
	} )() );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ): array { $hit['url'] = '/read/"><script>alert(1)</script>[csrf][[/project/company/general/name]]'; return $hit; } );
$written = $draw( '[search-results limit="1"][section-title .title href=".link"][text .url][/search-results]' );
check( 'an address that is a path stays text where it is written: a quote, a tag, a call and a fill are what they are, as the link and as the text',
	str_contains( $written, '<script' ) === false && str_contains( $written, '[csrf]' ) === true && str_contains( $written, '&quot;' ) === true && preg_match( '~<a [^>]*href="/read/&quot;~', $written ) === 1 && str_contains( $written, '[[/project' ) === true );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ): array {
	$hit['url'] = '/read/one';
	$hit['fields'] = [ 'title' => 'Taken over' ];
	$hit['element']['title'] = 'Taken over';
	$hit['element']['summary'] = 'Taken over';
	$hit['uri'] = '/articles/alpha';
	$hit['title'] = 'Taken over';
	$hit['score'] = 99.0;
	return $hit;
} );
$kept = $draw( '[search-results][text .uri][text .title][text .score][/search-results]' );
$taken = $draw( '[search-results][text .uri][text .title][text .score][text .url][/search-results]' );
check( 'the hook can give a hit its address and nothing else: its title, its name and its score are the ones of the index, whatever the answer holds',
	str_contains( $taken, 'Taken over' ) === false && str_contains( $taken, '/articles/alpha' ) === false && str_contains( $taken, '99' ) === false && substr_count( $taken, '/read/one' ) === 3 && str_contains( $kept, '/articles/orbit-title' ) === true );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static fn( array &$appData, array $hit ): bool => false );
check( 'a hook that answers false does not drop the hit from the list: it has no address, and that is all', substr_count( $draw( '[search-results][section-title .title href=".link"][/search-results]' ), 'class="section-title"' ) === 3
	&& str_contains( $draw( '[search-results][section-title .title href=".link"][/search-results]' ), '<a ' ) === false );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
$asked->exchangeArray( [] );
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ) use ( $asked ): array { $asked[] = $hit['uri']; $hit['url'] = '/read'. $hit['uri']; return $hit; } );
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HITS, static fn( array &$appData, array $hits ): array => array_values( array_filter( $hits, static fn( array $hit ): bool => $hit['uri'] !== '/articles/orbit-title' ) ) );
$left = $draw( '[search-results][text .url][/search-results]' );
check( 'a hit that the list hook took out is never asked and never drawn', in_array( '/articles/orbit-title', (array) $asked, true ) === false && count( $asked ) === 2 && str_contains( $left, 'orbit-title' ) === false );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
$appData['./nino/callbacks'][\Nino\Modules\Search::HITS] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ): array { $hit['url'] = '/read/by-type'; return $hit; } );
\Nino\Elements::insertElementType( $appData, '/links', [ 'title' => $text, 'url' => [ 'type' => 'string' ] ] );
\Nino\Elements::insertElement( $appData, '/links/one', [ 'title' => 'Orbit link', 'url' => 'own-value' ], 'de_DE' );
$appData['/nino/elements/index']['/links'] = [ 0 => 'title' ];
\Nino\Modules\Search::createIndexes( $appData, '/links' );
$given = $draw( '[search-results /links][text .url][/search-results]' );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
check( 'a hit with no address leaves a field of the type called url as the type has it, and a hit with one has the address in its place', $draw( '[search-results /links][text .url][/search-results]' ) === '<div class="text">own-value</div>' && $given === '<div class="text">/read/by-type</div>' );
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ): array { $hit['url'] = '/read/by-type'; return $hit; } );
check( 'the address is the field .url and, for a block that reads the example element, .link: both are the same path', preg_match( '~^<div class="text">/read/by-type</div><div class="text">/read/by-type</div>$~', $draw( '[search-results /articles limit="1"][text .url][text .link][/search-results]' ) ) === 1 );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT. '/articles', static function( array &$appData, array $hit ): array { $hit['url'] = '/read/of-the-type'; return $hit; } );
check( 'the callback of one type reaches the list as it reaches the endpoint', str_contains( $draw( '[search-results /articles][text .url][/search-results]' ), '/read/of-the-type' ) === true );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT. '/articles'] = [];
\Nino\Modules\Search::forget( $appData );

// The block and the demo
$block = \Nino\Blocks::get( $appData, 'search-page' );
check( 'the block is valid, names the feature and misses nothing; the demo of the group draws the field',
	is_array( $block ) === true && in_array( 'search', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === []
	&& str_contains( $draw( (string) ( \Nino\Components::groups( $appData )['search']['demo'] ?? '' ) ), 'class="search-form"' ) === true );
$page = (string) file_get_contents( $dir. '/blocks/search-page/block.tpl' );
$found = \Nino\Components::check( $appData, \Nino\Markup::parse( $page, \Nino\Components::containers( $appData ) ) );
check( 'the block is made of components and nothing a tool keeps as free html: the field, the number and the loop stand where they are offered', $found === [] && preg_match( '/<[a-z]/', $page ) === 0 );
$bundle = \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' );
check( 'the part is in the style bundle', str_contains( $bundle, '.search-form-input' ) === true );
$words = [ 'de_DE' => include $dir. '/install/text/de_DE.php', 'en_US' => include $dir. '/install/text/en_US.php' ];
check( 'the words of the page are the same keys in both languages, each of the grammar of a text key, and filled', array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] )
	&& array_filter( array_keys( $words['de_DE'] ), static fn( string $key ): bool => preg_match( '~^\[\[/feature/search/[a-z]+/[a-z]+\]\]$~', $key ) !== 1 ) === [] && array_filter( array_merge( $words['de_DE'], $words['en_US'] ), static fn( string $word ): bool => trim( $word ) === '' ) === [] );

// The feature is off
$off = ninoSandbox( 'search-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the calls draw nothing', \Nino\Html::renderSource( $off, '[search-form][search-count][search-results][text .title][/search-results]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

echo "\nThe index and its ranking\n";

$index = \Nino\Filesystem::getFileContent( $appData, '/data/index-articles.php', [] );
check( 'the index is one plain array grouped by language, behind its own block that says when it was made and out of which fields; the spelling of Nino 1 in the configuration is read',
	is_array( $index ) === true && array_keys( $index ) === [ '.meta', 'de_DE', 'en_US' ] && $index['.meta']['format'] === \Nino\Modules\Search::META_FORMAT && $index['.meta']['fields'] === [ 0 => 'title', 1 => 'summary', 2 => 'keywords', 3 => 'author' ]
	&& array_keys( $index['de_DE']['/articles/alpha'] ) === [ 0, 1, 2, 3 ] );
check( 'only the fields of the configuration are in the index', str_contains( json_encode( $index ), 'xqz' ) === false && is_file( \Nino\Filesystem::path( $appData, '/data/index-ignored.php' ) ) === false && is_file( \Nino\Filesystem::path( $appData, '/data/index-plain.php' ) ) === false );
check( 'text is indexed as words: lower case, the umlauts as ae, oe and ue, markup and entities gone', $index['de_DE']['/articles/alpha'][0] === 'mueller katalog' && $index['de_DE']['/articles/alpha'][1] === 'leuchttuerme klare wege in berlin' );
check( 'a hit in priority 0 ranks ahead of the same word in priority 1', $uris( 'orbit' )[0] === '/articles/orbit-title' && array_search( '/articles/orbit-summary', $uris( 'orbit' ), true ) > 0 );
check( 'a small spelling error is matched, an umlaut is matched as it is written without one, an array field counts', $uris( 'katalg' ) === [ '/articles/alpha' ] && $uris( 'mueller' ) === [ '/articles/alpha' ] && $uris( 'cms' ) === [ '/articles/alpha' ] && $uris( 'dape' ) === [ '/articles/alpha' ] );
check( 'a word the text does not carry lowers the coverage and does not throw the hit away, and the full phrase outranks the scattered words',
	$uris( 'orbit nowhere' ) !== [] && ( \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit nowhere' )[0]['coverage'] ?? 0 ) === 0.5 && $uris( 'orbit' ) !== [] );
check( 'a query of no words, and a query that finds nothing, are an empty result', $uris( '' ) === [] && $uris( '   ' ) === [] && $uris( '!?' ) === [] && $uris( 'nowhere' ) === [] );
$junk = implode( ' ', array_map( static fn( int $n ): string => 'qzx'. $n. 'k', range( 1, 12 ) ) );
check( 'a query is read as twelve words at the most: twelve that fit nothing and a thirteenth that would fit find nothing, that word alone finds it, and the cut is the constant',
	$uris( $junk. ' orbit' ) === [] && $uris( 'orbit' ) !== [] && $reflection->getConstant( 'MAX_QUERY_TOKENS' ) === 12 );
check( 'a query of a thousand words is read as twelve and answered', count( \Nino\Modules\Search::getHits( $appData, 'articles', str_repeat( 'orbit ', 1000 ) ) ) > 0 );
check( 'limit and offset cut the ranked list and not the search', count( \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit', 1 ) ) === 1 && array_column( \Nino\Modules\Search::getHits( $appData, 'articles', 'orbit', 1, 1 ), 'uri' ) === [ $uris( 'orbit' )[1] ] );
check( 'an element comes back with its score and its type, the page of the language it is searched in', ( \Nino\Modules\Search::getElements( $appData, 'articles', 'orbit' )[0]['.type'] ?? '' ) === '/articles' && is_float( \Nino\Modules\Search::getElements( $appData, 'articles', 'orbit' )[0]['.score'] ?? null ) === true );
$appData['./nino/locales/current'] = 'en_US';
check( 'it searches the language it runs in: a word that is in one language only is not found in the other', $uris( 'lighthouses' ) === [ '/articles/alpha' ] && $uris( 'leuchttuerme' ) === [] );
$appData['./nino/locales/current'] = 'de_DE';
check( 'two types are one ranked list, and a type nobody indexed contributes nothing', array_column( \Nino\Modules\Search::getHits( $appData, [ '/articles', '/notes', '/plain', '/ignored' ], 'orbit' ), 'type' ) !== [] && in_array( '/plain', array_column( \Nino\Modules\Search::getHits( $appData, [ '/articles', '/notes', '/plain' ], 'orbit' ), 'type' ), true ) === false );

\Nino\Elements::updateElement( $appData, '/articles/alpha', [ 'title' => 'Neuer Katalog', 'summary' => 'x' ], 'de_DE' );
check( 'a committed update refreshes the index of its type, and the old words are gone', $uris( 'neuer' ) === [ '/articles/alpha' ] && $uris( 'mueller' ) === [] );
\Nino\Elements::deleteElement( $appData, '/articles/orbit-title', '*' );
check( 'a committed delete takes the element out of the search', in_array( '/articles/orbit-title', $uris( 'orbit' ), true ) === false );
\Nino\Elements::insertElement( $appData, '/plain/two', [ 'title' => 'Orbit two', 'active' => false ], 'de_DE' );
check( 'a write to a type nobody indexed writes no index', is_file( \Nino\Filesystem::path( $appData, '/data/index-plain.php' ) ) === false );

// What a project of Nino 1 left on the disk
$legacy = [ '.meta' => [ 'format' => 3, 'built' => '2026-09-12T10:00:00+00:00', 'elements' => 1, 'fields' => [ 0 => 'title' ] ], 'de_DE' => [ '/notes/one' => [ 0 => 'merkzettel' ] ], 'en_US' => [] ];
\Nino\Filesystem::putFileContent( $appData, '/data/index-notes.php', $legacy );
check( 'an index file of the format of Nino 1 is read as it stands, and says it is current', $uris( 'merkzettel', '/notes' ) === [ '/notes/one' ]
	&& ( array_column( \Nino\Modules\Search::indexState( $appData ), null, 'type' )['/notes']['indexed'] ?? false ) === true && ( array_column( \Nino\Modules\Search::indexState( $appData ), null, 'type' )['/notes']['stale'] ?? true ) === false );
$configured = $appData['/nino/elements/index'];
unset( $appData['/nino/elements/index']['/notes'] );
check( 'an index file of a type that is no longer in the configuration is not searched: what is not chosen for the index is not shown', $uris( 'merkzettel', '/notes' ) === [] && \Nino\Modules\Search::getHits( $appData, [ '/notes', '/articles' ], 'merkzettel' ) === [] );
$appData['/nino/elements/index'] = $configured;
$old = $legacy;
$old['.meta']['format'] = 2;
\Nino\Filesystem::putFileContent( $appData, '/data/index-notes.php', $old );
check( 'an index of an earlier format is said to be stale and is not rebuilt by a read', ( array_column( \Nino\Modules\Search::indexState( $appData ), null, 'type' )['/notes']['stale'] ?? false ) === true && ( \Nino\Filesystem::getFileContent( $appData, '/data/index-notes.php', [] )['.meta']['format'] ?? 0 ) === 2 );
$broken = $articleIndex;
file_put_contents( $broken, '<?php return "no array";' );
check( 'an index that is no array, or does not parse, is an empty result and is left as it is', $uris( 'neuer' ) === [] && file_get_contents( $broken ) === '<?php return "no array";' );
file_put_contents( $broken, '<?php this does not parse' );
check( '...and a rebuild replaces it by an index', $uris( 'neuer' ) === [] && \Nino\Modules\Search::createIndexes( $appData, 'articles' )['created'] === 1 && $uris( 'neuer' ) === [ '/articles/alpha' ] && glob( dirname( $articleIndex ). '/index-articles.php.*' ) === [] );
$ignored = ninoWarnings();

echo "\nThe ranking of a request\n";

// A field that changes its place: the index was made with the title first, the choice now has the other field alone
\Nino\Elements::insertElementType( $appData, '/swap', [ 'title' => $text, 'internal' => $text ] );
\Nino\Elements::insertElement( $appData, '/swap/a', [ 'title' => 'publicword', 'internal' => 'hiddenword' ], 'de_DE' );
$appData['/nino/elements/index']['/swap'] = [ 0 => 'title', 1 => 'internal' ];
\Nino\Modules\Search::createIndexes( $appData, '/swap' );
$appData['/nino/elements/index']['/swap'] = [ 0 => 'internal' ];
check( 'a field that gives its place to another is not searched with the place: the word only the dropped title holds finds nothing before the rebuild, and the index shows less and never more',
	\Nino\Modules\Search::getHits( $appData, '/swap', 'publicword' ) === [] && \Nino\Modules\Search::getHits( $appData, '/swap', 'hiddenword' ) === [] );
unset( $appData['/nino/elements/index']['/swap'] );

/*	One request, no forget() between the readings: what is asked of the ranking is told apart by the types, the words, the language and the
	configuration, and each list shows what is its own	*/
\Nino\Modules\Search::createIndexes( $appData, '/notes' );
\Nino\Modules\Search::forget( $appData );
$byType = [ array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'merkzettel' ), 'uri' ), array_column( \Nino\Modules\Search::getHits( $appData, '/notes', 'merkzettel' ), 'uri' ) ];
check( 'the ranking kept for a request is told apart by the types: two lists of two types for one word show each its own', $byType === [ [], [ '/notes/one' ] ] );
$byWord = [ array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'orbit' ), 'uri' ), array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'katalg' ), 'uri' ) ];
check( '...by the words: two words of one type', $byWord[1] === [ '/articles/alpha' ] && $byWord[0] !== $byWord[1] );
$appData['./nino/locales/current'] = 'de_DE';
$germanFirst = \Nino\Modules\Search::getHits( $appData, '/articles', 'lighthouses' );
$appData['./nino/locales/current'] = 'en_US';
$english = array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'lighthouses' ), 'uri' );
$appData['./nino/locales/current'] = 'de_DE';
check( '...by the language: the word of one language is found in that language and not in the other', $germanFirst === [] && $english === [ '/articles/alpha' ] );
$full = array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'orbit' ), 'uri' );
$kept = $appData['/nino/elements/index']['articles'];
$appData['/nino/elements/index']['articles'] = [ 0 => 'title' ];
$narrow = array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'orbit' ), 'uri' );
$appData['/nino/elements/index']['articles'] = $kept;
check( '...by the configuration, which changes inside the request: the narrower choice shows fewer hits', in_array( '/articles/orbit-summary', $full, true ) === true && in_array( '/articles/orbit-summary', $narrow, true ) === false );

// A write drops it: what an element becomes in this request is found by the next search of it
$zebra = static function() use ( &$appData ): array { return array_column( \Nino\Modules\Search::getHits( $appData, '/articles', 'zebraword' ), 'uri' ); };
$none = $zebra();
\Nino\Elements::insertElement( $appData, '/articles/zz', [ 'title' => 'zebraword', 'summary' => 'x' ], 'de_DE' );
$added = $zebra();
\Nino\Elements::deleteElement( $appData, '/articles/zz', '*' );
check( 'a write of the index drops the ranking: an element saved in the request is found by the next search, and one deleted is not', $none === [] && $added === [ '/articles/zz' ] && $zebra() === [] );
$before = array_column( \Nino\Modules\Search::getHits( $appData, '/notes', 'merkzettel' ), 'uri' );
\Nino\Modules\Search::removeIndex( $appData, '/notes' );
check( 'the removal of an index drops it too', $before === [ '/notes/one' ] && \Nino\Modules\Search::getHits( $appData, '/notes', 'merkzettel' ) === [] );
\Nino\Modules\Search::createIndexes( $appData, '/notes' );
$ignored = ninoWarnings();

echo "\nThe json endpoint\n";

$answer = static function( string $uri ) use ( &$appData ): array {
	\Nino\Modules\Search::forget( $appData );
	$request = ninoVisit( $appData, $uri );
	\Nino\Http::response( $appData, $request );
	return $request['/nino/http/response'];
};
unset( $appData['/nino/http/routes']['GET://.search'] );
\Nino\Modules\Search::init( $appData );
check( 'without the setting there is no route, and the address answers like any address that is none', isset( $appData['/nino/http/routes']['GET://.search'] ) === false && $answer( '/.search?q=orbit' )['statusCode'] === 404 );
$appData['/nino/features']['search']['settings']['api'] = true;
\Nino\Modules\Search::init( $appData );
$reply = $answer( '/.search?q=orbit&type=/articles' );
$body = (array) ( $reply['body'] ?? [] );
check( 'with it the address answers 200 with an array the kernel encodes, never cached, naming what was asked and what was found',
	$reply['statusCode'] === 200 && array_keys( $body ) === [ 'query', 'locale', 'types', 'total', 'offset', 'limit', 'hits' ] && ( $reply['header']['Cache-Control'] ?? '' ) === 'no-store' && $body['total'] === count( $body['hits'] ) && $body['total'] >= 2 );
$first = (array) ( $body['hits'][0] ?? [] );
check( 'a hit is its address, type, score, coverage and the indexed fields of its element and nothing else of it: a field nobody indexed stays off the wire',
	array_keys( $first ) === [ 'uri', 'type', 'score', 'coverage', 'fields' ] && array_keys( $first['fields'] ) !== [] && array_diff( array_keys( $first['fields'] ), [ 'title', 'summary', 'keywords', 'author' ] ) === [] && str_contains( json_encode( $body ), 'XQZ' ) === false );
$hostileQuery = '</script><img src=x onerror=alert(1)> [csrf] ' . str_repeat( 'a', 600 );
$reflection = new \ReflectionMethod( \Nino\Http::class, '_finalizeResponse' );
$hostileRequest = ninoVisit( $appData, '/.search?q='. rawurlencode( $hostileQuery ) );
\Nino\Http::response( $appData, $hostileRequest );
$reflection->invokeArgs( null, [ &$hostileRequest ] );
$sent = (array) json_decode( (string) $hostileRequest['/nino/http/response']['body'], true );
check( 'the query comes back as text of a json answer that cannot be taken for a page, cut at the length of a query',
	str_starts_with( (string) $hostileRequest['/nino/http/response']['header']['Content-Type'], 'application/json' ) === true && ( $hostileRequest['/nino/http/response']['header']['X-Content-Type-Options'] ?? '' ) === 'nosniff'
	&& is_string( $sent['query'] ?? null ) === true && mb_strlen( $sent['query'] ) <= 256 && str_starts_with( $sent['query'], '</script><img src=x onerror=alert(1)> [csrf]' ) === true );
check( 'no query is a 400 with the one sentence of the error that a script reads, and a query that is a list is none', $answer( '/.search' )['statusCode'] === 400 && $answer( '/.search?q[]=orbit' )['statusCode'] === 400 && is_string( $answer( '/.search' )['body']['error'] ?? null ) === true && array_keys( (array) $answer( '/.search' )['body'] ) === [ 'error' ] );
$many = (array) $answer( '/.search?q=orbit&limit=100000&offset=-5' )['body'];
check( 'a limit is held to its most and an offset to its least; a type nobody indexed answers no hits and no error',
	$many['limit'] === \Nino\Modules\Search::API_LIMIT_MAX && $many['offset'] === 0 && ( $answer( '/.search?q=orbit&type=/plain' )['body']['hits'] ?? null ) === [] && $answer( '/.search?q=orbit&type=/plain' )['statusCode'] === 200 );
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HITS, static fn( array &$appData, array $hits ): array => array_values( array_filter( $hits, static fn( array $hit ): bool => $hit['uri'] !== '/articles/orbit-summary' ) ) );
$held = (array) $answer( '/.search?q=orbit&type=/articles' )['body'];
check( 'the endpoint counts and pages the list that is left when a feature holds hits back', in_array( '/articles/orbit-summary', array_column( $held['hits'], 'uri' ), true ) === false && $held['total'] === count( $held['hits'] ) );
$appData['./nino/callbacks'][\Nino\Modules\Search::HITS] = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Search::HIT, static function( array &$appData, array $hit ): array|false { $hit['fields'] = [ 'only' => 'this' ]; unset( $hit['element'] ); return $hit['uri'] === '/articles/hostile' ? false : $hit; } );
$shaped = (array) $answer( '/.search?q=orbit&type=/articles' )['body'];
check( 'a callback shapes the fields of a hit or drops it, and the element it was handed never travels', $shaped['hits'][0]['fields'] === [ 'only' => 'this' ] && in_array( '/articles/hostile', array_column( $shaped['hits'], 'uri' ), true ) === false && array_key_exists( 'element', $shaped['hits'][0] ) === false );
$appData['./nino/callbacks'][\Nino\Modules\Search::HIT] = [];

echo "\nThe panel\n";

check( 'the panel is answered by the module, with a right of its own and a sign that is geometry and nothing else',
	\Nino\Modules\Search::adminPanels( $appData ) === [ $panel ] && $panel::perm() === '/_admin/search/manage' && preg_match( '/^<svg [^>]*>(<(?:path|circle) [^>]*\/>)+<\/svg>$/', $panel::icon() ) === 1 );
$config = static fn(): mixed => \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/elements/index'] ?? null;
$before = $config();
$beforeFile = (string) file_get_contents( $articleIndex );

ninoUser( $appData, 'editor@example.org', [] );
$denied = [];
foreach( [ 'apiList' => [], 'apiSave' => [ 'type' => 'notes', 'fields' => [ 0 => 'body' ] ], 'apiCreateIndex' => [], 'apiProbe' => [ 'query' => 'orbit', 'types' => [ '/articles' ] ] ] as $action => $data )
	$denied[$action] = ninoCall( $appData, [ $panel, $action ], $data )[0];
check( 'every action of the panel is behind the right: an account without it is refused in each', $denied === [ 'apiList' => 403, 'apiSave' => 403, 'apiCreateIndex' => 403, 'apiProbe' => 403 ]
	&& $config() === $before && (string) file_get_contents( $articleIndex ) === $beforeFile );
\Nino\Auth::logoutUser( $appData );
check( 'and nobody who is not signed in is let in', ninoCall( $appData, [ $panel, 'apiCreateIndex' ] )[0] === 401 && ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'body' ] ] )[0] === 401 && $config() === $before );

ninoUser( $appData, 'dev@example.org', [ '/_admin/search/manage' ] );
[ $status, $listed ] = ninoCall( $appData, [ $panel, 'apiList' ] );
$rows = array_column( (array) ( $listed['types'] ?? [] ), null, 'type' );
check( 'the list answers one row for each type the project has and each the configuration names, with the state the script draws, and the weights of the engine',
	$status === 200 && ( $listed['weights'] ?? null ) === \Nino\Modules\Search::WEIGHTS && isset( $rows['/articles'], $rows['/notes'], $rows['/plain'], $rows['/missing-type'] ) === true
	&& $rows['/articles']['indexed'] === true && $rows['/plain']['configured'] === false && $rows['/missing-type']['issues'] !== [] && in_array( '.uri', $rows['/articles']['model'], true ) === true && in_array( 'internalCode', $rows['/articles']['model'], true ) === true );
check( 'a problem of the configuration is a code with what belongs in it, which the panel words: no sentence of the engine reaches the interface',
	preg_match( '/^[a-z]+$/', (string) ( $rows['/missing-type']['issues'][0][0] ?? '' ) ) === 1 && ( $rows['/missing-type']['issues'][0][1] ?? '' ) === '/missing-type' );
check( 'the field picker is offered the model minus what carries no text', in_array( 'active', $rows['/plain']['model'], true ) === false && in_array( 'title', $rows['/plain']['model'], true ) === true );
[ $status, $saved ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'plain', 'fields' => [ 0 => 'title' ] ] );
check( 'a save writes one type into /nino/elements/index in config.php, and builds nothing', $status === 200 && ( $saved['fields'] ?? null ) === [ 0 => 'title' ] && ( $config()['/plain'] ?? null ) === [ 0 => 'title' ] && is_file( \Nino\Filesystem::path( $appData, '/data/index-plain.php' ) ) === false );
check( 'the rebuild of that type makes it searchable', ninoCall( $appData, [ $panel, 'apiCreateIndex' ], [ 'type' => '/plain' ] )[0] === 200 && in_array( '/plain/one', $uris( 'orbit', '/plain' ), true ) === true );
$refused = [];
foreach( [ [ 'type' => 'a/b', 'fields' => [ 0 => 'title' ] ], [ 'type' => [ 'x' ], 'fields' => [] ], [ 'type' => 'nothere', 'fields' => [ 0 => 'title' ] ], [ 'type' => 'plain', 'fields' => [ 0 => 'nofield' ] ],
	[ 'type' => 'plain', 'fields' => [ 0 => 'active' ] ], [ 'type' => 'plain', 'fields' => [ 9 => 'title' ] ], [ 'type' => 'plain', 'fields' => [ 0 => [ 'title' ] ] ], [ 'type' => 'plain', 'fields' => 'title' ] ] as $at => $data )
	$refused[$at] = ninoCall( $appData, [ $panel, 'apiSave' ], $data )[0];
check( 'a save that names no type, a type that is none, a field the model has not, a field with no text, a place there is none or a list in a place is refused, and config.php is as it was',
	$refused === [ 400, 400, 404, 400, 400, 400, 400, 400 ] && ( $config()['/plain'] ?? null ) === [ 0 => 'title' ] );
[ $status, $removed ] = ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'plain', 'fields' => [] ] );
check( 'a type that loses its last field leaves the configuration, and its index goes with it', $status === 200 && $removed['removed'] === true && isset( $config()['/plain'] ) === false && is_file( \Nino\Filesystem::path( $appData, '/data/index-plain.php' ) ) === false );
[ $status, $probed ] = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'query' => str_repeat( 'orbit ', 10000 ), 'types' => [ '/articles', 5, [ 'x' ], '/plain' ], 'locale' => 'en_US' ] );
check( 'the probe asks the index in the language it is told and answers no more than it is meant to, whatever it is asked with', $status === 200 && count( $probed['hits'] ) <= $probed['limit'] && $probed['limit'] === 25
	&& array_diff( array_keys( $probed['hits'][0] ?? [] ), [ 'uri', 'type', 'label', 'score', 'coverage', 'matched' ] ) === [] );

// A field that is taken out of the choice is not searched, though the index was made with it and nobody has rebuilt it
ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'title', 1 => 'body' ] ] );
ninoCall( $appData, [ $panel, 'apiCreateIndex' ], [ 'type' => '/notes' ] );
$deselect = static function() use ( &$appData, $panel, $visit, $draw ): array {
	\Nino\Modules\Search::forget( $appData );
	$visit( [ 'q' => 'nur hier' ] );
	$reply = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'query' => 'nur hier', 'types' => [ '/notes' ] ] );
	$request = ninoVisit( $appData, '/.search?q='. rawurlencode( 'nur hier' ). '&type=/notes' );
	\Nino\Http::response( $appData, $request );
	return [ substr_count( $draw( '[search-results /notes][text .uri][/search-results]' ), 'class="text"' ), $reply[1]['hits'], $request['/nino/http/response']['body']['hits'], $draw( '[search-count /notes]' ) ];
};
[ $drawn, $probedHits, $apiHits ] = $deselect();
check( 'a field that is chosen is found by the list, the probe and the endpoint', $drawn === 1 && count( $probedHits ) === 1 && count( $apiHits ) === 1 );
ninoCall( $appData, [ $panel, 'apiSave' ], [ 'type' => 'notes', 'fields' => [ 0 => 'title' ] ] );
[ $drawn, $probedHits, $apiHits ] = $deselect();
check( 'a field that is taken out of the choice and saved, with the index not rebuilt, is found by none of them: not in the list, not in the number, not in the probe, not in the endpoint',
	$drawn === 0 && $probedHits === [] && $apiHits === [] && ( array_column( \Nino\Modules\Search::indexState( $appData ), null, 'type' )['/notes']['stale'] ?? false ) === true );
ninoCall( $appData, [ $panel, 'apiCreateIndex' ], [ 'type' => '/notes' ] );

// The probe answers its limit and no more
\Nino\Elements::insertElementType( $appData, '/bulk', [ 'title' => $text ] );
foreach( range( 1, 30 ) as $n )
	\Nino\Elements::insertElement( $appData, '/bulk/n'. $n, [ 'title' => 'Orbit '. $n ], 'de_DE' );
$appData['/nino/elements/index']['/bulk'] = [ 0 => 'title' ];
\Nino\Modules\Search::createIndexes( $appData, '/bulk' );
[ $status, $probed ] = ninoCall( $appData, [ $panel, 'apiProbe' ], [ 'query' => 'orbit', 'types' => [ '/bulk' ] ] );
check( 'a probe of more hits than its limit answers the limit and says it', $status === 200 && count( $probed['hits'] ) === 25 && $probed['limit'] === 25 && count( \Nino\Modules\Search::getHits( $appData, '/bulk', 'orbit' ) ) === 30 );
$ignored = ninoWarnings();

// The script builds nothing itself (the guide, "What a panel does not build itself"), and every word it reads is there in both languages
$script = (string) file_get_contents( $dir. '/assets/admin.js' );
check( 'the log line of the panel names an action and a type that is one: a posted name that is none, and the words of a probe, never reach it',
	preg_match( '/[\n<>\[]/', $panel::log( 'search/save', [ 'type' => "articles\n<script>[csrf]" ] ) ) === 0 && str_contains( $panel::log( 'search/save', [ 'type' => 'articles' ] ), '/articles' ) === true
	&& $panel::log( 'search/probe', [ 'query' => 'orbit [csrf]' ] ) === '' && $panel::log( 'search/createindex', [] ) !== '' );
check( 'the panel script takes its bars, buttons, tables, tabs and checklist from the workbench and builds none of them itself',
	preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(|nino-admin-btn-|type\s*[:=]\s*.file|<select|\.innerHTML\s*=\s*[^\'\s]/', $script ) === 0
	&& str_contains( $script, 'Nino.adminUi.actionBar( {' ) === true && str_contains( $script, 'Nino.adminUi.tabs(' ) === true && str_contains( $script, 'Nino.adminUi.table( {' ) === true && str_contains( $script, 'Nino.adminUi.checklist( {' ) === true );
check( 'the fields of a type are a form of the guide: novalidate, a fieldset with its fieldgrid, one main button in the bar of its own, the answer as the badge and a toast, unsaved input reported, a way back',
	str_contains( $script, 'noValidate = true' ) === true && str_contains( $script, "'fieldset'" ) === true && str_contains( $script, 'nino-admin-fieldgrid' ) === true && substr_count( $script, 'actionBar( {' ) === substr_count( $script, 'primary :' )
	&& str_contains( $script, 'bar.line.saved()' ) === true && str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'dirty' ) === true && str_contains( $script, 'contextBar( {' ) === true );
check( 'the panel has no language switch of its own: the probe asks in the working language of the workbench and follows it', str_contains( $script, 'Nino.admin.workLocale.current' ) === true && str_contains( $script, 'Nino.admin.workLocale.follow(' ) === true
	&& preg_match( '/localeSelect|getAvailableLocales|search\/label\/locale/', $script ) === 0 );
$panelWords = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];
check( 'both languages of the panel have the same keys', array_keys( $panelWords['de_DE'] ) === array_keys( $panelWords['en_US'] ) );
preg_match_all( "~'(/_admin/(?!common/)[a-z/_-]+)'~", $script, $read );
$own = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( $panelWords['en_US'] ) );
check( 'every word the script reads is one the panel brings in both languages', array_diff( array_unique( $read[1] ), $own ) === [] );
check( 'every sentence for a code of the panel is there in both languages', array_filter( [ 'search_type', 'search_notype', 'search_fields', 'search_priority', 'search_field', 'search_text' ], static fn( string $code ): bool => in_array( '/_admin/error/'. $code, $own, true ) === false ) === [] );

// Against the real Posts: a post that is found is a link on its title in the block of the page, a post that is hidden is not found
$real = ninoSandbox( 'search-posts' );
$real['./nino/filesystem/path'] = $root;
\Nino\Features::activate( $real, 'basis' );
\Nino\Features::activate( $real, 'search' );
\Nino\Features::activate( $real, 'posts' );
\Nino\Filesystem::putFileContent( $real, \Nino\Modules\Posts\Sections::PATH, [ 'sections' => [ 'blog' => [ 'type' => '/posts', 'path' => 'blog' ] ] ] );
$real['/nino/elements/index'] = [ 'posts' => [ 0 => 'title' ], '/pages' => [ 0 => 'title' ] ];
\Nino\Elements::insertElementType( $real, '/pages', [ 'title' => [ 'type' => 'string', 'locale' => true ] ] );
\Nino\Elements::insertElement( $real, '/posts/lantern-now', [ 'title' => 'Lantern now', 'date' => date( 'Y-m-d', time() - 86400 * 30 ) ], 'de_DE' );
\Nino\Elements::insertElement( $real, '/posts/lantern-soon', [ 'title' => 'Lantern soon', 'date' => date( 'Y-m-d', time() + 86400 * 30 ) ], 'de_DE' );
\Nino\Elements::insertElement( $real, '/pages/lantern-page', [ 'title' => 'Lantern page' ], 'de_DE' );
unset( $real['./nino/elements/cache'] );
$real['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $real, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $real );
\Nino\Features::registerComponents( $real );
\Nino\Modules::callModules( $real, 'init' );
\Nino\Features::registerParts( $real );
\Nino\Modules\Search::createIndexes( $real );
\Nino\Modules\Search::forget( $real );
$real['./nino/http/requests'] = [];
ninoVisit( $real, '/search?q=lantern' );
$blockPage = trim( \Nino\Html::renderSource( $real, (string) file_get_contents( $dir. '/blocks/search-page/block.tpl' ), [ 'template' => 'page-search' ] + \Nino\Html::context( $real ) ) );
check( 'with the real Posts the block draws a post that is found as a link to its page, a post that is hidden is not drawn, and a type that has no address is a title and no link',
	preg_match( '~<a href="/blog/lantern-now">Lantern now</a>~', $blockPage ) === 1 && str_contains( $blockPage, 'Lantern soon' ) === false && str_contains( $blockPage, 'lantern-soon' ) === false
	&& preg_match( '~<h2 class="section-title[^"]*">Lantern page</h2>~', $blockPage ) === 1 && str_contains( $blockPage, 'href=""' ) === false );
$ignored = ninoWarnings();

// Nothing of the old components API, the old shortcodes or the old stylesheet vocabulary is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|\bnino-(?!admin-)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

// The script of the panel over the workbench's stand-in of a page (search-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: search-js-smoke.js was NOT run\n";
else {
	$output = [];
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/search-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'search-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
