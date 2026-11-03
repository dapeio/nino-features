<?php
declare(strict_types=1);

/**
 *	Nino
 *	posts-smoke.php		Contract test for the Posts feature (Modules\Posts): the
 *										manifest and activation through \Nino\Features, what a
 *										section is normalised to, the two routes a section
 *										registers, the slug resolution and its four ways of
 *										being a 404, the page title a post takes over, and the
 *										four shortcodes - the paged list, the post, the pager
 *										and the way to the next one. Travels with the feature
 *										and runs against the checkout three levels up, or the
 *										one NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/Posts/tests/posts-smoke.php
 *	       NINO_ROOT=../nino php features/Posts/tests/posts-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'posts' );
// What a request always carries and a sandbox does not - a section's urls are
// built on it
$appData['/nino/dir'] 								= '';

/**
 *	Put a request in place, resolve the post it is for, and answer what came
 *	out - the shortcodes read the live request rather than being handed one.
 *
 *	The request is ninoVisit()'s, the first stage of Http::request(), and its
 *	response uri is the path that was asked for, not the route's own: the copy
 *	Http::request() pushes onto './nino/http/requests' is made before
 *	Http::response() matches a route, so that is what a shortcode reading the
 *	live request actually finds. Written the other way round, this fixture
 *	agreed with an implementation that read the resolved uri - and a browser
 *	found what it could not. What this does beyond ninoVisit() is what the
 *	route does next: the response uri becomes the section's own, and
 *	callbackPost() resolves the post or answers the 404
 */
function postsResolve( array &$appData, string $path ): array {
	unset( $appData['./posts/current'] );
	// A request begins with the fills of the files alone: what the post of the one before made of the page's title and description is gone
	foreach( [ 'title', 'description' ] as $detail )
		\Nino\Html::removeFill( $appData, '/_nino/webpage/blog/post/'. $detail );
	$request = ninoVisit( $appData, $path );
	// The route matched by then, so the response uri is the section's own
	$request['/nino/http/response']['uri'] = '/blog/post';
	\Nino\Modules\Posts::callbackPost( $appData, $request );
	return $request;
}

/**
 *	Render a template fragment the way a page would
 */
function postsRender( array &$appData, string $html ): string {
	return \Nino\Html::renderHtml( $appData, $html );
}


echo "The manifest, and what it promises\n";

$feature = \Nino\Features::manifest( dirname( __DIR__ ) );

check( 'the manifest reads, with the key and the class the directory implies', is_array( $feature ) === true
	&& $feature['key'] === 'posts' && $feature['module'] === '\\Nino\\Modules\\Posts' );
check( 'it is content, and names the kernel it needs - the one whose toolset its class calls, and with it the card that is called one, which the list page is written in, and the element with its fallback', ( $feature['category'] ?? '' ) === 'content'
	&& ( $feature['nino'] ?? '' ) === '^1.9' );
check( 'the sections file is declared under data, so a backup carries it',
	in_array( \Nino\Modules\Posts\Sections::PATH, (array) ( $feature['data'] ?? [] ), true ) === true );
check( 'the posts themselves are not - they are ordinary elements, and /elements is backed up already',
	count( (array) ( $feature['data'] ?? [] ) ) === 1 );


echo "\nWhat a section is normalised to\n";

$notes 	= [];
$plain 	= \Nino\Modules\Posts\Sections::normalizeSection( 'blog', [], $notes );

check( 'an empty section is the defaults, and says nothing about it', $plain === \Nino\Modules\Posts\Sections::DEFAULTS && $notes === [] );

$notes = [];
$given = \Nino\Modules\Posts\Sections::normalizeSection( 'blog', [
	'type' => 'news/', 'path' => '/journal/', 'index' => '', 'post' => '/templates/entry',
	'sort' => '-published,title', 'date' => 'published', 'perPage' => 999,
], $notes );

check( 'a type and a path are taken as meant, however they were written',
	$given['type'] === '/news' && $given['path'] === 'journal' && $notes === [] );
check( 'an empty index is a section with no index route, not a mistake', $given['index'] === '' );
check( 'a sort may name several fields and reverse any of them', $given['sort'] === '-published,title' );
check( 'the page size is held to what a page can be', $given['perPage'] === \Nino\Modules\Posts\Sections::MAX_PER_PAGE );

$notes = [];
$refused = \Nino\Modules\Posts\Sections::normalizeSection( 'blog', [
	'type' => '../../etc', 'path' => '../secrets', 'post' => '/templates/../../config', 'date' => 'a field',
], $notes );

check( 'a type, a path or a template that could climb out never becomes one',
	$refused['type'] === '/posts' && $refused['path'] === 'blog' && $refused['post'] === '/templates/page-post'
	&& count( $notes ) === 3 );
check( '...and a section is what a fresh install has before it has said anything',
	\Nino\Modules\Posts\Sections::read( $appData ) === [ 'blog' => \Nino\Modules\Posts\Sections::DEFAULTS ] );
check( '...and a field name that is not one is nothing rather than something', $refused['date'] === '' );

$notes = [];
$sections = \Nino\Modules\Posts\Sections::normalize( [ 'sections' => [
	'blog' 		=> [ 'type' => '/posts', 'path' => 'blog' ],
	'news' 		=> [ 'type' => '/news', 'path' => 'blog' ],
	'Not A Key'	=> [ 'type' => '/nope', 'path' => 'nope' ],
] ], $notes );

check( 'two sections may not share a path - the second would win silently',
	array_keys( $sections ) === [ 'blog' ] && count( array_filter( $notes, static fn( string $n ): bool => str_contains( $n, 'already has' ) ) ) === 1 );
check( 'a key that is not a slug is not a section', count( array_filter( $notes, static fn( string $n ): bool => str_contains( $n, 'not a section key' ) ) ) === 1 );


echo "\nActivating it: the install unit, and the two routes a section registers\n";

check( 'the feature activates', \Nino\Features::activate( $appData, 'posts' ) === true );
check( '...and brought the element type a section is', \Nino\Filesystem::fileExists( $appData, '/elements/posts.php' ) === true );
check( '...with the three fields the defaults point at',
	array_key_exists( 'title', \Nino\Elements::getElementModel( $appData, '/posts' ) ) === true
	&& array_key_exists( 'summary', \Nino\Elements::getElementModel( $appData, '/posts' ) ) === true
	&& ( \Nino\Elements::getElementModel( $appData, '/posts' )['date']['type'] ?? '' ) === 'date' );
check( '...and the two templates', \Nino\Filesystem::fileExists( $appData, '/templates/page-posts.tpl' ) === true
	&& \Nino\Filesystem::fileExists( $appData, '/templates/page-post.tpl' ) === true );
preg_match( '#<article.*?</article>#s', (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-posts.tpl' ) ), $postCard );
preg_match_all( '/class="([^"]+)"/', $postCard[0] ?? '', $postClasses );
check( '...the list page draws a post as the card, in the card classes the kernel owns, the card that is a link the stretched way and the one link it stretches, and no others: the card itself, its content, its subtitle, its title, the link of the title and its description',
	$postClasses[1] === [ 'nino-card nino-card--linked', 'nino-card-content', 'nino-card-subtitle', 'nino-card-title', 'nino-stretch', 'nino-card-descr' ] );
check( 'and the words those templates say', \Nino\Html::renderTextfill( $appData, '/template/page-posts/intro/title' ) !== '' );

/*	The list page of the default section is a page of the system: its title, its name in a menu and its
	description are the /_nino/webpage<uri> keys of the Element-URI the section chose. Nothing has written
	them in a fresh project, and the head of the page prints the key as it stands until the Routes panel
	has been used - so the unit brings the three, in each language the project has	*/
$pageUri		= sprintf( \Nino\Modules\Posts::INDEX_URI, \Nino\Modules\Posts\Sections::DEFAULT_KEY );
$pageKeys		= array_map( static fn( string $name ): string => '[[/_nino/webpage'. $pageUri. '/'. $name. ']]', [ 'name', 'title', 'description' ] );
$pageLocales	= array_map( 'strval', \Nino\Locales::getAvailableLocales( $appData ) );
$pageMissing	= [];

foreach( $pageLocales as $pageLocale ) {

	$pageTexts = (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $pageLocale. '.php', [] );

	foreach( $pageKeys as $pageKey )
		if( is_string( $pageTexts[$pageKey] ?? null ) === false || trim( $pageTexts[$pageKey] ) === '' )
			$pageMissing[] = $pageLocale. ' '. $pageKey;
}

check( '...and the name, the title and the description of the list page of the default section, in every language the project has: the unit brings the three keys of the system for the Element-URI the section chose',
	count( $pageLocales ) >= 2 && $pageMissing === [] );
check( '...so the head of that page prints a title and not the key as it stands, whichever of those languages the page is in',
	( static function() use ( &$appData, $pageUri, $pageLocales ): bool {

		$current	= $appData['./nino/locales/current'] ?? null;
		$printed	= true;

		foreach( $pageLocales as $pageLocale ) {

			$appData['./nino/locales/current'] = $pageLocale;

			$head = postsRender( $appData, '<title>[[/_nino/webpage'. $pageUri. '/title]]</title>' );
			$printed = $printed && str_contains( $head, '[[' ) === false && trim( strip_tags( $head ) ) !== '';
		}

		if( $current === null )
			unset( $appData['./nino/locales/current'] );
		else
			$appData['./nino/locales/current'] = $current;

		return $printed;
	} )() );
check( '...a word the project wrote for that page stays when the unit is applied again, as an activation applies it, and the other two are there',
	( static function() use ( &$appData, $pageKeys, $pageLocales ): bool {

		$titleKey	= $pageKeys[1];
		$own			= 'The words of the project';
		$before	= (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $pageLocales[0]. '.php', [] );
		$routes	= [];
		$black		= [];
		$config	= [];

		\Nino\Features::mergeText( $appData, '/text/'. $pageLocales[0]. '.php', [ $titleKey => $own ], true );
		\Nino\Features::applyUnit( $appData, dirname( __DIR__ ). '/install', $pageLocales, $routes, $black, $config, false );

		$after = (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $pageLocales[0]. '.php', [] );

		\Nino\Features::mergeText( $appData, '/text/'. $pageLocales[0]. '.php', [ $titleKey => $before[$titleKey] ], true );

		return ( $after[$titleKey] ?? '' ) === $own && is_string( $after[$pageKeys[0]] ?? null ) === true && is_string( $after[$pageKeys[2]] ?? null ) === true;
	} )() );

/*	The section the rest of this runs against is the example of data/posts.php.
	Posts has no panel and no writer, so a file written by hand is how a
	project says what its section is - it has to be a file read() takes as
	written	*/
$example = [ 1 => "<?php return [\n\t'sections' => [\n\t\t'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ],\n\t],\n];\n" ];
$sectionsFile = \Nino\Filesystem::path( $appData, \Nino\Modules\Posts\Sections::PATH );
is_dir( dirname( $sectionsFile ) ) === true || mkdir( dirname( $sectionsFile ), 0755, true );
file_put_contents( $sectionsFile, (string) ( $example[1] ?? '' ) );
$exampleNotes = [];
check( 'an example of data/posts.php is a file the feature reads as written',
	\Nino\Modules\Posts\Sections::read( $appData, $exampleNotes ) === [ 'blog' => \Nino\Modules\Posts\Sections::normalizeSection( 'blog', [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ] ) ]
	&& $exampleNotes === [] );
unset( $appData['./posts/sections'] );

$appData['/nino/modules'] = [ '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Posts' ];
\Nino\Modules::callModules( $appData, 'init' );

check( 'a section registers its list under its path', ( $appData['/nino/http/routes']['GET://blog']['uri'] ?? '' ) === '/blog'
	&& str_contains( (string) ( $appData['/nino/http/routes']['GET://blog']['body'] ?? '' ), '/templates/page-posts' ) === true );
check( '...and a page per post under it, as the wildcard route the kernel walks up to',
	( $appData['/nino/http/routes']['GET://blog/*']['uri'] ?? '' ) === '/blog/post' );

// A project that already has a page at that path: the section renders into it
// rather than over it, or the blog leaves its own navigation
$appData['/nino/http/routes']['GET://blog'] = [ 'uri' => '/theirs', 'body' => '[template /templates/page-theirs]', 'navs' => [ 'main' => 20 ] ];
\Nino\Modules\Posts::routes( $appData );

check( 'a page the project already has under that path keeps its menus and its identity',
	( $appData['/nino/http/routes']['GET://blog']['navs'] ?? null ) === [ 'main' => 20 ]
	&& ( $appData['/nino/http/routes']['GET://blog']['uri'] ?? '' ) === '/theirs' );
check( '...and only what it renders becomes the section\'s',
	str_contains( (string) ( $appData['/nino/http/routes']['GET://blog']['body'] ?? '' ), '/templates/page-posts' ) === true );

unset( $appData['/nino/http/routes']['GET://blog'] );
\Nino\Modules\Posts::routes( $appData );
check( 'the routes are this request\'s, never written into config.php',
	is_array( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? null ) === false
	|| isset( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes']['GET://blog'] ) === false );

// Three posts, and one dated next week. title is required and per locale, so
// an insert carries it - the global fields land in '*' on the way
\Nino\Elements::insertElement( $appData, '/posts/first-light', [ 'title' => 'First light', 'summary' => 'How it began.', 'body' => '<p>A <b>body</b>.</p>', 'date' => '2026-01-05', 'author' => 'Ada' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/posts/second-wind', [ 'title' => 'Second wind', 'summary' => 'And onwards.', 'date' => '2026-02-09' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/posts/third-rail', [ 'title' => 'Third rail', 'date' => '2026-03-01' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/posts/from-monday', [ 'title' => 'Not yet', 'date' => date( 'Y-m-d', strtotime( '+7 days' ) ) ], 'de_DE' );

$section = \Nino\Modules\Posts::section( $appData, 'blog' );

check( 'a post dated in the future is not published, and the rest are',
	array_column( \Nino\Modules\Posts::posts( $appData, $section ), '.uri' ) === [ '/posts/third-rail', '/posts/second-wind', '/posts/first-light' ] );

/*	The date field is whatever the type declares and whatever was written
	into it - a list field named as the date field, say. Cast to string,
	an array raises "Array to string conversion", which the kernel treats
	as fatal (see \Nino\Runtime::NON_FATAL_LEVELS): one such element and
	every page of the section is a 500, not one post missing	*/
ninoWarnings();
$arrayDated = [ '.uri' => '/posts/array-dated', 'date' => [ '2026-01-05' ] ];
check( 'an element whose date field is an array is a draft, not a raised warning',
	\Nino\Modules\Posts::published( $section, $arrayDated ) === false && ninoWarnings() === [] );


echo "\nResolving a post, and the four ways of not finding one\n";

$request = postsResolve( $appData, '/blog/second-wind' );

check( 'a slug that is a post resolves to it', \Nino\Modules\Posts::current( $appData ) === '/posts/second-wind'
	&& $request['/nino/http/response']['statusCode'] === 200 );
check( '...and the page takes the post\'s title over the route\'s',
	\Nino\Html::renderTextfill( $appData, '/_nino/webpage/blog/post/title' ) === 'Second wind'
	&& \Nino\Html::renderTextfill( $appData, '/_nino/webpage/blog/post/description' ) === 'And onwards.' );

foreach( [
	'a slug nobody has'						=> '/blog/nothing-here',
	'a slug that is a path'				=> '/blog/../config',
	'a slug with a second segment'=> '/blog/2026/first-light',
	'a post dated in the future'	=> '/blog/from-monday',
] as $what => $path ) {
	$request = postsResolve( $appData, $path );
	check( $what. ' is the project\'s own 404, not a blank page', $request['/nino/http/response']['statusCode'] === 404
		&& \Nino\Modules\Posts::current( $appData ) === '' );
}


echo "\nThe four shortcodes\n";

/*	The page of a list is the query of the request, and the shortcodes read it
	off the request the kernel carries: each ask below is a visit to the list	*/
unset( $appData['./posts/current'] );
ninoVisit( $appData, '/blog' );

$list = postsRender( $appData, '[posts]<li><a href="[[.url]]">[[title]]</a></li>[/posts]' );
check( '[posts] lists the first page of the section the page is', $list ===
	'<li><a href="/blog/third-rail">Third rail</a></li><li><a href="/blog/second-wind">Second wind</a></li>' );

ninoVisit( $appData, '/blog?page=2' );
check( '...and the second page is the rest', postsRender( $appData, '[posts]<a href="[[.url]]">[[title]]</a>[/posts]' ) ===
	'<a href="/blog/first-light">First light</a>' );

ninoVisit( $appData, '/blog?page=99' );
check( 'a page past the end is empty rather than the last one over again', postsRender( $appData, '[posts][[title]][/posts]' ) === '' );
/*	...and the pager under it says the same thing. It used to clamp to the last
	real page and mark that one as the one that is on, so an empty list stood
	under a pager saying "you are on page 2" - the two halves of one list
	telling somebody two different places they are	*/
$beyond = postsRender( $appData, '[posts-pager]' );
check( '...and the pager under it marks no page as the one that is on',
	str_contains( $beyond, 'nino-is-active' ) === false && str_contains( $beyond, 'aria-current' ) === false );
check( '...while the way back is the last page that has posts on it, and there is no way on',
	str_contains( $beyond, '<li><a href="/blog?page=2" rel="prev">' ) === true && str_contains( $beyond, 'rel="next"' ) === false );

/*	A query string can carry a page number as large as an integer goes -
	99999999999999999999 is PHP_INT_MAX once it is cast - and the offset of
	that page, ( $page - 1 ) * perPage, is a float: array_slice() answered it
	with a TypeError, a 500 for whoever asked and a line in the log each time.
	It is a page past the end like any other, and answered like one	*/
foreach( [ '99999999999999999999', (string) PHP_INT_MAX ] as $huge ) {

	ninoVisit( $appData, '/blog?page='. $huge );
	ninoWarnings();

	$hugeList		= null;
	$hugePager	= '';
	$raised			= null;

	try {
		$hugeList		= postsRender( $appData, '[posts][[title]][/posts]' );
		$hugePager	= postsRender( $appData, '[posts-pager]' );
	}
	catch( \Throwable $error ) {
		$raised = $error->getMessage();
	}

	check( 'page '. $huge. ' is an empty list, and nothing is raised or logged for it', $raised === null && $hugeList === '' && ninoWarnings() === [] );
	check( '...the pager under it is the one of any page past the end: no page is the one that is on, the way back is the last page that has posts, and there is no way on',
		str_contains( $hugePager, 'nino-is-active' ) === false && str_contains( $hugePager, 'aria-current' ) === false
		&& str_contains( $hugePager, '<li><a href="/blog?page=2" rel="prev">' ) === true && str_contains( $hugePager, 'rel="next"' ) === false );
}

ninoVisit( $appData, '/blog' );
check( 'a limit of its own turns the paging off - a teaser is not page one',
	postsRender( $appData, '[posts limit="3"][[title]] [/posts]' ) === 'Third rail Second wind First light ' );

$pager = postsRender( $appData, '[posts-pager]' );
check( 'the pager links every page and marks the one that is on', str_contains( $pager, 'href="/blog?page=2"' ) === true
	&& str_contains( $pager, '<li class="nino-is-active"><a href="/blog" aria-current="page">1</a></li>' ) === true
	&& str_contains( $pager, 'rel="next"' ) === true );
check( '...in the markup the framework\'s own .nino-pagination is written against',
	str_contains( $pager, '<ul class="nino-pagination">' ) === true && substr_count( $pager, '<li' ) === 3 );
check( '...and page one is the section itself, not a query that means the same', str_contains( $pager, 'page=1' ) === false );

ninoVisit( $appData, '/blog?page=2' );
check( 'from page two the way back is the section\'s own url', str_contains( postsRender( $appData, '[posts-pager]' ), '<li><a href="/blog" rel="prev">' ) === true );
ninoVisit( $appData, '/blog' );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Posts\Sections::PATH, [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 50 ],
] ] );
unset( $appData['./posts/sections'] );
check( 'one page is no pager - saying "1 of 1" is telling somebody there is more', postsRender( $appData, '[posts-pager]' ) === '' );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Posts\Sections::PATH, [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ],
] ] );
unset( $appData['./posts/sections'] );

postsResolve( $appData, '/blog/second-wind' );

check( '[post] is the post the url is for', postsRender( $appData, '[post]<h1>[[title]]</h1>[/post]' ) === '<h1>Second wind</h1>' );
check( '...and renders nothing on a page that is not one',
	postsRender( $appData, '[posts][/posts]' ) !== null && ( static function() use ( &$appData ): bool {
		ninoVisit( $appData, '/blog' );
		unset( $appData['./posts/current'] );
		return postsRender( $appData, '[post]<h1>[[title]]</h1>[/post]' ) === '';
	} )() === true );

postsResolve( $appData, '/blog/second-wind' );
$nav = postsRender( $appData, '[post-nav]<a class="[[.rel]]" href="[[.url]]">[[title]]</a>[/post-nav]' );
check( 'the way to the post before and the post after, in the section\'s own order', $nav ===
	'<a class="prev" href="/blog/third-rail">Third rail</a><a class="next" href="/blog/first-light">First light</a>' );

postsResolve( $appData, '/blog/third-rail' );
check( '...and the newest post has no newer one', postsRender( $appData, '[post-nav][[.rel]] [/post-nav]' ) === 'next ' );

/*	Two keys are composed - the page template's /feature/posts/navigation/[[.rel]] and the pager's
	/feature/posts/pager/<which> - so a template or a class that broke the composition still renders:
	the words themselves are what is looked at, in both languages, in the template the unit copies	*/
postsResolve( $appData, '/blog/second-wind' );
$navigation = trim( (string) preg_replace( '/^\[template [^\]]+\]\s*$/m', '', \Nino\Filesystem::getFileContent( $appData, '/templates/page-post.tpl', '' ) ) );
foreach( [ 'en_US' => [ 'Previous post', 'Next post' ], 'de_DE' => [ 'Vorheriger Beitrag', 'Nächster Beitrag' ] ] as $locale => $words ) {
	$appData['./nino/locales/current'] = $locale;
	$rendered = postsRender( $appData, $navigation );
	check( 'the page template says "'. $words[0]. '" and "'. $words[1]. '" under the way to the post before and after ('. $locale. ')',
		str_contains( $rendered, '<p class="nino-section-subtitle">'. $words[0]. '</p>' ) === true && str_contains( $rendered, '<p class="nino-section-subtitle">'. $words[1]. '</p>' ) === true
		&& str_contains( $rendered, '[[/feature' ) === false );
}

$appData['./nino/locales/current'] = 'en_US';
$textFile = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( $textFile, [ '[[/feature/posts/pager/next]]' => 'Further on' ] ) );
check( 'the pager says what the project has set for its next link, a composed key', str_contains( postsRender( $appData, '[posts-pager]' ), 'Further on' ) === true );
check( '...and a word given as an attribute wins over it', str_contains( postsRender( $appData, '[posts-pager next="More"]' ), '>More<' ) === true && str_contains( postsRender( $appData, '[posts-pager next="More"]' ), 'Further on' ) === false );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', $textFile );
$appData['./nino/locales/current'] = 'de_DE';


// --- Components ----------------------------------------------------------------

echo "Components - [posts-pager] as the Builder offers it, the record shortcodes as they were\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// The page of one post: [post] and [post-nav] have a post to speak of, and
	// the lists name their section by its key where the page cannot say
	postsResolve( $appData, '/blog/second-wind' );

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[posts section="blog"]<li><a href="[[.url]]">[[title]]</a></li>[/posts]',
		'[posts section="blog" limit="3"]<a href="[[.url]]">[[title]]</a> [/posts]',
		'[posts section="blog" limit="2" offset="1"]<a href="[[.url]]">[[title]]</a> [/posts]',
		'[posts section="blog" limit="2" offset="-1"]<a href="[[.url]]">[[title]]</a> [/posts]',
		'[posts section="blog" limit="3x"]<a href="[[.url]]">[[title]]</a> [/posts]',
		'[posts limit="3"][[title]] [/posts]',
		'[posts section="nowhere"]<p>[[title]]</p>[/posts]',
		'[posts section="blog"][/posts]',
		'[post]<h1>[[title]]</h1>[/post]',
		'[post]<h1>[[title]]</h1>[[.body]][/post]',
		'[post][/post]',
		'[posts-pager]',
		'[posts-pager section="blog" prev="Zurück" next="Weiter" label="Seiten des Blogs"]',
		'[posts-pager next="More"]',
		'[post-nav]<a class="[[.rel]]" href="[[.url]]">[[title]]</a>[/post-nav]',
		'[post-nav section="blog"][[.rel]] [/post-nav]',
		'[post-nav section="nowhere"]x[/post-nav]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'post', 'post-nav', 'posts', 'posts-pager' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Posts\Shortcodes::init( $probe );
	ninoWarnings();

	check( 'the pager is registered with the schema the manifest declares, and it is the only component',
		( \Nino\Modules\Components::components( $probe )['posts-pager'] ?? null ) === ( $declared['posts-pager'] ?? false )
		&& array_keys( $declared ) === [ 'posts-pager' ]
		&& array_diff( [ 'posts', 'post', 'post-nav' ], array_keys( \Nino\Modules\Components::components( $probe ) ) ) === [ 'posts', 'post', 'post-nav' ] );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['posts-pager']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the pager by its wrapper, the three that carry a record as shortcodes',
		\Nino\Features::shortcodes( $probe, 'posts' ) === [ 'post', 'post-nav', 'posts', 'posts-pager' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/posts' ) ) === 1 && is_array( $registered[0] )
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/post' ) ) === 1 && is_array( $registered[0] )
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/post-nav' ) ) === 1 && is_array( $registered[0] )
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/posts-pager' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'posts-pager' ) === [ 'section' => '', 'prev' => '', 'next' => '', 'label' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[posts-pager section="" prev="" next="" label="" class=""]' ) === $before['[posts-pager]']
		&& str_contains( $before['[posts section="blog"]<li><a href="[[.url]]">[[title]]</a></li>[/posts]'], '<li><a href="/blog/third-rail">Third rail</a></li>' ) === true );
	check( 'a class of its own is set on the pager\'s navigation, no class leaves no attribute behind',
		str_contains( \Nino\Html::renderHtml( $probe, '[posts-pager class="paging"]' ), '<nav class="paging" aria-label="' ) === true
		&& str_contains( $before['[posts-pager]'], '<nav aria-label="' ) === true );

} )( $appData );

echo "\n";


echo "\nWhat a post may put in a page\n";

\Nino\Elements::updateElement( $appData, '/posts/second-wind', [
	'title' 	=> '<script>alert(1)</script> & [posts]',
	'body' 		=> 'A <strong>kept</strong> word<script>alert(2)</script>',
], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
postsResolve( $appData, '/blog/second-wind' );

$rendered = postsRender( $appData, '[post][[title]]|[[.body]][/post]' );

check( 'a title is editor text, so its markup is text too', str_contains( $rendered, '&lt;script&gt;' ) === true
	&& str_contains( $rendered, '<script>alert(1)' ) === false );
check( '...and a shortcode in it is not a shortcode', str_contains( $rendered, '&#91;posts]' ) === true );
check( 'a field the model released for html keeps the inline markup the kernel allows',
	str_contains( $rendered, '<strong>kept</strong>' ) === true && str_contains( $rendered, 'alert(2)' ) === false );

// [[.body]] takes the same field through sanitizeHtml() and its own paragraph
// template, which used to leave the '[' the ordinary values have swapped -
// so a body was the one place an editor's text was still run as a shortcode
\Nino\Elements::updateElement( $appData, '/posts/second-wind', [
	'body' => 'Ein Absatz mit [posts] und [[/nothing/defined]]',
], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
postsResolve( $appData, '/blog/second-wind' );
$bodyWithBrackets = postsRender( $appData, '[post][[.body]][/post]' );
check( 'a shortcode or a fill in the body is drawn as text there too',
	str_contains( $bodyWithBrackets, '&#91;posts]' ) === true && str_contains( $bodyWithBrackets, '&#91;&#91;/nothing/defined]]' ) === true );


echo "\nThe picture, as a whole tag or as nothing at all\n";

check( 'a post without one renders no <img>, rather than a broken picture',
	postsRender( $appData, '[post]|[[.image]]|[/post]' ) === '||' );

\Nino\Elements::updateElement( $appData, '/posts/second-wind', [ 'image' => 'post-2.jpg' ], '*' );
\Nino\Elements::updateElement( $appData, '/posts/second-wind', [ 'imageAlt' => 'A "wide" view [posts]' ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
postsResolve( $appData, '/blog/second-wind' );

$image = postsRender( $appData, '[post][[.image]][/post]' );

check( 'the alt text is editor content as well, shortcode and all',
	str_contains( $image, '&#91;posts]' ) === true );

check( 'a post with one gets the whole tag, sized out of the model',
	str_contains( $image, 'src="'. \Nino\Images::getUrl( $appData, 'post-2.jpg' ). '"' ) === true
	&& str_contains( $image, 'width="1200" height="675"' ) === true
	&& str_contains( $image, 'loading="lazy"' ) === true );
check( '...and the alt is the field the section names, quoted for an attribute',
	str_contains( $image, 'alt="A &quot;wide&quot; view &#91;posts]"' ) === true );


echo "\nThe body, in paragraphs\n";

\Nino\Elements::updateElement( $appData, '/posts/first-light', [
	'body' => "One <strong>bold</strong> line.\nStill the first.\n\n<h2>Not a heading</h2>Second.",
], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
postsResolve( $appData, '/blog/first-light' );

$body = postsRender( $appData, '[post][[.body]][/post]' );

$p = '<p class="'. \Nino\Modules\Posts\Shortcodes::BODY_CLASS. '">';

check( 'a blank line starts a paragraph and a single one is a break',
	$body === $p. 'One <strong>bold</strong> line.<br>Still the first.</p>'. $p. 'Not a heading Second.</p>'
	|| $body === $p. 'One <strong>bold</strong> line.<br>Still the first.</p>'. $p. 'Not a headingSecond.</p>' );
check( '...and nothing block-level survives that a field could not carry anyway',
	str_contains( $body, '<h2>' ) === false && substr_count( $body, $p ) === 2 );
check( 'a paragraph carries the framework\'s own body-copy class - a bare <p> has no margin in Nino',
	str_contains( $body, 'class="nino-section-text"' ) === true );
check( 'an empty body is nothing rather than an empty paragraph',
	( static function() use ( &$appData ): bool {
		\Nino\Elements::updateElement( $appData, '/posts/first-light', [ 'body' => '' ], 'de_DE' );
		unset( $appData['./nino/elements/cache'] );
		postsResolve( $appData, '/blog/first-light' );
		return postsRender( $appData, '[post][[.body]][/post]' ) === '';
	} )() === true );

echo "\nA field the model gives a format\n";

/*	A field is the kernel's to render - \Nino\Html::fieldValue() - by the entry the
	model has for it: the paragraphs and lists of a field that holds them, the line
	breaks of one that keeps them, the inline tags of one released for html, the
	escape for the rest. The type gets a field of each of the two it has none of	*/
\Nino\Filesystem::mutate( $appData, '/elements/posts.php', static function( mixed $state ): array {
	$state = is_array( $state ) === true ? $state : [];
	$state['model']['lead'] = [ 'type' => 'string', 'locale' => true, 'html' => true, 'blocks' => true ];
	$state['model']['note'] = [ 'type' => 'string', 'locale' => true, 'breaks' => true ];
	return $state;
}, [] );
unset( $appData['./nino/elements/cache'] );
\Nino\Elements::updateElement( $appData, '/posts/second-wind', [
	'title'	=> '<b>[x]</b>',
	'body'	=> '<strong>Bold</strong> [x]',
	'lead'	=> '<p>Alpha</p><ul><li>Beta</li></ul>',
	'note'	=> "Gamma\nDelta <b>",
], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
postsResolve( $appData, '/blog/second-wind' );

$formats = explode( '|', postsRender( $appData, '[post][[title]]|[[body]]|[[lead]]|[[note]][/post]' ) );

check( 'a plain field is escaped, and a bracket in it is not a shortcode', ( $formats[0] ?? '' ) === '&lt;b&gt;&#91;x]&lt;/b&gt;' );
check( 'a field released for html keeps its inline tags and neutralises a bracket', ( $formats[1] ?? '' ) === '<strong>Bold</strong> &#91;x]' );
check( 'a field that holds paragraphs and lists keeps them', ( $formats[2] ?? '' ) === '<p>Alpha</p><ul><li>Beta</li></ul>' );
check( 'a field that keeps its line breaks draws them as <br>, escaped', ( $formats[3] ?? '' ) === "Gamma<br>\nDelta &lt;b&gt;" );

echo "\nWhat the title and the summary of a post put in the head of its page\n";

/*	The page takes both as the fills of its own title and description (see
	Posts::callbackPost()), and a fill is put in as it is: the base unit's
	html-header.tpl and frame-header.tpl set them into the <title>, into the
	content of the description, og: and twitter: tags and into the <h1>. This
	is that use, with the fills of this post, and what is looked at is what a
	browser would read out of it - a tag, an attribute or a shortcode that the
	text of an editor made would show here	*/
$head = '<title>[[/_nino/webpage/blog/post/title]] | Site</title>'
	. '<meta name="description" content="[[/_nino/webpage/blog/post/description]]">'
	. '<meta property="og:title" content="[[/_nino/webpage/blog/post/title]] | Site">'
	. '<meta property="og:description" content="[[/_nino/webpage/blog/post/description]]">'
	. '<meta name="twitter:title" content="[[/_nino/webpage/blog/post/title]] | Site">'
	. '<meta name="twitter:description" content="[[/_nino/webpage/blog/post/description]]">'
	. '<h1 class="nino-sr-only" id="header-title">Site | [[/_nino/webpage/blog/post/title]]</h1>';

\Nino\Elements::updateElement( $appData, '/posts/second-wind', [
	'title' 	=> '"><b>x</b>[image',
	'summary'	=> '" onmouseover=',
], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
postsResolve( $appData, '/blog/second-wind' );

$page = postsRender( $appData, $head );
preg_match_all( '/<meta\b[^>]*>/', $page, $metas );

check( 'the title is text in the <title>, with no tag of its own', str_contains( $page, '<title>&quot;&gt;&lt;b&gt;x&lt;/b&gt;&#91;image | Site</title>' ) === true
	&& str_contains( $page, '<b>' ) === false );
check( '...and the summary is the content of the description, with no attribute of its own',
	str_contains( $page, '<meta name="description" content="&quot; onmouseover=">' ) === true );
check( '...every one of the five meta tags is its name and its content and nothing else - the og: and twitter: ones too, and none was added',
	count( $metas[0] ) === 5 && count( array_filter( $metas[0], static fn( string $tag ): bool => preg_match( '/^<meta (?:name|property)="[a-z:]+" content="[^"<>]*">$/D', $tag ) === 1 ) ) === 5 );
check( '...the heading of the frame has the same text and nothing else',
	str_contains( $page, '<h1 class="nino-sr-only" id="header-title">Site | &quot;&gt;&lt;b&gt;x&lt;/b&gt;&#91;image</h1>' ) === true );
check( '...and the bracket is a character reference, so that the page is rendered once more without running a shortcode of it',
	str_contains( $page, '[image' ) === false && str_contains( $page, '&#91;image' ) === true );

/*	The card of the list is the other way a title reaches a page, and spells it
	the way the head does now: the block of the list page the unit installed,
	rendered for page one	*/
preg_match( '#\[posts\].*?\[/posts\]#s', (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-posts.tpl' ) ), $cardBlock );
ninoVisit( $appData, '/blog' );
$cards = postsRender( $appData, $cardBlock[0] ?? '' );

check( 'the card in the list has both as it always had them: escaped, the way the head has them now',
	str_contains( $cards, '<h3 class="nino-card-title"><a href="/blog/second-wind" class="nino-stretch">&quot;&gt;&lt;b&gt;x&lt;/b&gt;&#91;image</a></h3>' ) === true
	&& str_contains( $cards, '<p class="nino-card-descr">&quot; onmouseover=</p>' ) === true );

/*	With the Consent feature there, the finished body is read for the placeholders
	it names: a <script type="text/plain" data-consent="necessary" data-src="https://..."> adds
	its host to the script-src of the policy, and consent.js then releases it as a
	script. A title that could write one was a script of any host on the page of
	its post. Driven the way the output phase drives it - over the body of the page and
	the policy of a response; what the hook reads and refuses is held by the test of
	that feature	*/
if( class_exists( '\\Nino\\Modules\\Consent' ) === false )
	echo "  note - the Consent feature is not beside this one: what its output hook makes of a page is not driven here\n";
else {

	$placeholder	= '<script type="text/plain" data-consent="necessary" data-src="https://x.invalid/a.js"></script>';
	$response			= ninoVisit( $appData, '/blog/second-wind' );
	$policyOf			= static function( string $body ) use ( &$appData, $response ): string {
		$response['/nino/http/response']['body'] = $body;
		\Nino\Modules\Consent::callbackOutput( $appData, $response );
		return (string) $response['/nino/http/response']['header']['Content-Security-Policy'];
	};

	\Nino\Elements::updateElement( $appData, '/posts/second-wind', [ 'title' => 'Placeholder '. $placeholder ], 'de_DE' );
	unset( $appData['./nino/elements/cache'] );
	postsResolve( $appData, '/blog/second-wind' );

	check( 'a placeholder in the markup of a page puts its host in the policy of the page - the title as it was typed, in the head, is that',
		str_contains( $policyOf( '<title>Placeholder '. $placeholder. ' | Site</title>' ), 'https://x.invalid' ) === true );
	check( '...and the page of a post whose title is that placeholder names no host in its policy: the title is text there',
		str_contains( $policyOf( postsRender( $appData, $head ) ), 'x.invalid' ) === false );
}

echo "\nA post in a language that has none of it\n";

/*	What a language has no text for is the text of the native one (\Nino\Elements::getElementWithFallback()),
	and every post is drawn through it: a post that exists in one language only is a post in the list of the
	other, and in the block that shows it, and says whose words they are. The words are made up - what is looked
	at is where each of them stands, and what is said about it, and not what it says	*/
$word = static fn( string $seed ): string => 'w'. substr( md5( $seed ), 0, 7 );

$sectionsKept = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Posts\Sections::PATH, [] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Posts\Sections::PATH, [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 50 ],
] ] );
unset( $appData['./posts/sections'] );

\Nino\Elements::insertElement( $appData, '/posts/lang-both', [ 'title' => 'M-'. $word( 'both-de' ), 'summary' => $word( 'both-de-s' ), 'date' => '2026-01-10' ], 'de_DE' );
\Nino\Elements::updateElement( $appData, '/posts/lang-both', [ 'title' => 'M-'. $word( 'both-en' ), 'summary' => $word( 'both-en-s' ) ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/posts/lang-german', [ 'title' => 'A-'. $word( 'only-de' ), 'summary' => $word( 'only-de-s' ), 'date' => '2026-01-11' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/posts/lang-mixed', [ 'title' => 'T-'. $word( 'mixed-de' ), 'summary' => $word( 'mixed-de-s' ), 'date' => '2026-01-12' ], 'de_DE' );
\Nino\Elements::updateElement( $appData, '/posts/lang-mixed', [ 'title' => 'T-'. $word( 'mixed-en' ) ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/posts/lang-english', [ 'title' => 'Z-'. $word( 'only-en' ), 'date' => '2026-01-13' ], 'en_US' );
// A post with no text in any language is not one the Elements panel makes - the title is required - but a file that was written by hand, or one whose texts were taken away, holds it
\Nino\Filesystem::mutate( $appData, '/elements/posts.php', static function( mixed $state ): array {
	$state = is_array( $state ) === true ? $state : [];
	$state['*']['lang-bare'] = [ 'date' => '2026-01-14' ];
	return $state;
}, [] );
unset( $appData['./nino/elements/cache'] );

$inLanguage = static function( string $locale ) use ( &$appData ): void {
	ninoVisit( $appData, '/blog' );
	$appData['./nino/locales/current'] = $locale;
};

// Each post of the list as a row: the address, then what the block says of it
$drawn = static function( string $block ) use ( &$appData ): array {

	$rows = [];

	foreach( explode( ';', postsRender( $appData, '[posts limit="50"]'. $block. ';[/posts]' ) ) as $row )
		if( $row !== '' )
			$rows[ (string) strstr( $row, '|', true ) ] = substr( (string) strstr( $row, '|' ), 1 );

	return $rows;
};
$row = '[[.uri]]|[[title]]|[[summary]]|[[.lang]]';

$inLanguage( 'en_US' );
$english = $drawn( $row );

check( 'a post the language has all of is drawn as it is, and says nothing of another language',
	( $english['/posts/lang-both'] ?? null ) === 'M-'. $word( 'both-en' ). '|'. $word( 'both-en-s' ). '|' );
check( '...a post that exists in the native language only is in the list of the other, in the native words and with the lang attribute of that language',
	( $english['/posts/lang-german'] ?? null ) === 'A-'. $word( 'only-de' ). '|'. $word( 'only-de-s' ). '| lang="de-DE"' );
check( '...a post with a title in the language and no summary has its own title and the summary of the native language, and says so',
	( $english['/posts/lang-mixed'] ?? null ) === 'T-'. $word( 'mixed-en' ). '|'. $word( 'mixed-de-s' ). '| lang="de-DE"' );

$inLanguage( 'de_DE' );
$german = $drawn( $row );

check( '...and the other way round: the native language\'s list shows a post that only another language has, in that language\'s words and with its tag',
	( $german['/posts/lang-english'] ?? null ) === 'Z-'. $word( 'only-en' ). '|| lang="en-US"'
	&& ( $german['/posts/lang-mixed'] ?? null ) === 'T-'. $word( 'mixed-de' ). '|'. $word( 'mixed-de-s' ). '|' );

// A field the type has that no language has a value for is nothing in the block: left standing it was a
// fill nobody answered, which the next rendering pass made "[]" of when its name was a shortcode's
$inLanguage( 'en_US' );
$everything = '[[.uri]]|[[title]]|[[summary]]|[[author]]|[[tags]]|[[imageAlt]]|[[body]]|[[.body]];';
$leftover = postsRender( $appData, '[posts limit="50"]'. $everything. '[/posts]' );

check( 'a post no language has a title for draws it as nothing, and no field of the type is left in the block for the next pass to make anything of',
	( $english['/posts/lang-bare'] ?? null ) === '||' && str_contains( $leftover, '[' ) === false && str_contains( $leftover, '/posts/lang-bare|||||||;' ) === true );

// The order is the order of what is drawn: a title taken from another language stands where it sorts
$byTitle	= \Nino\Modules\Posts\Sections::normalizeSection( 'blog', [ 'type' => '/posts', 'path' => 'blog', 'sort' => 'title' ] );
$listed		= static function( array $section ) use ( &$appData ): array {
	return array_values( array_filter( array_column( \Nino\Modules\Posts::posts( $appData, $section ), '.uri' ), static fn( string $uri ): bool => str_starts_with( $uri, '/posts/lang-' ) ) );
};

check( '...the list is sorted after the fallback: a post whose title is the native one stands where that title sorts, and the one with no title at all comes last',
	$listed( $byTitle ) === [ '/posts/lang-german', '/posts/lang-both', '/posts/lang-mixed', '/posts/lang-english', '/posts/lang-bare' ] );

// [post] and [post-nav] draw a post the same way
postsResolve( $appData, '/blog/lang-german' );
check( '[post] is the post with what the language lacks taken from another, and says so on what it shows',
	postsRender( $appData, '[post]<h1[[.lang]]>[[title]]</h1>[/post]' ) === '<h1 lang="de-DE">A-'. $word( 'only-de' ). '</h1>' );
postsResolve( $appData, '/blog/lang-both' );
check( '...and a post the language has all of says nothing',
	postsRender( $appData, '[post]<h1[[.lang]]>[[title]]</h1>[/post]' ) === '<h1>M-'. $word( 'both-en' ). '</h1>' );
postsResolve( $appData, '/blog/lang-mixed' );
check( '[post-nav] says it of the post before and the post after as well - the one the language has all of nothing, the other its tag',
	postsRender( $appData, '[post-nav]<a class="[[.rel]]"[[.lang]]>[[title]]</a>[/post-nav]' ) === '<a class="prev">Z-'. $word( 'only-en' ). '</a><a class="next" lang="de-DE">A-'. $word( 'only-de' ). '</a>' );

// The templates the unit copies carry the token where the text is shown
$listTemplate = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-posts.tpl' ) );
$postTemplate = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-post.tpl' ) );
$sayingIt = static function( string $template ): array {
	preg_match_all( '/<([a-z0-9]+)\b[^>]*\[\[\.lang\]\][^>]*>/', $template, $found );
	return $found[1];
};

check( 'the list page the unit copies says the language on a post\'s card and on its title, and the page of a post on the article, its heading and the link to the post beside it',
	$sayingIt( $listTemplate ) === [ 'article', 'h3' ] && $sayingIt( $postTemplate ) === [ 'article', 'h1', 'a' ] );

preg_match( '#\[posts\].*?\[/posts\]#s', $listTemplate, $listBlock );
$inLanguage( 'en_US' );
$cards		= postsRender( $appData, $listBlock[0] ?? '' );
$posted		= \Nino\Modules\Posts::posts( $appData, \Nino\Modules\Posts::section( $appData, 'blog' ) );
$borrowed	= count( array_filter( $posts = $posted, static fn( array $post ): bool => ( $post['.lang'] ?? '' ) !== '' ) );

check( '...so the list of the other language has a card of every post, and the ones with words from the native language say so on the card and on its title',
	$borrowed >= 2 && $borrowed < count( $posted ) && substr_count( $cards, '<article class="nino-card nino-card--linked"' ) === count( $posted )
	&& substr_count( $cards, '<article class="nino-card nino-card--linked" lang="de-DE">' ) === $borrowed && substr_count( $cards, '<h3 class="nino-card-title" lang="de-DE">' ) === $borrowed
	&& str_contains( $cards, '[[' ) === false );

// What the page of a post says about itself where no language has a title for it
echo "\nThe page of a post that has no title in any language\n";

$text = static function( string $key, string $english, string $german ) use ( &$appData ): void {
	foreach( [ 'en_US' => $english, 'de_DE' => $german ] as $locale => $value )
		\Nino\Features::mergeText( $appData, '/text/'. $locale. '.php', [ '[['. $key. ']]' => $value ], true );
};
$textsKept = [];
foreach( [ 'en_US', 'de_DE' ] as $locale )
	$textsKept[$locale] = (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );

$said = static function( string $detail ) use ( &$appData ): string {
	return \Nino\Html::renderTextfill( $appData, '/_nino/webpage/blog/post/'. $detail );
};

$text( '/_nino/webpage/blog/name', $word( 'name-en' ), $word( 'name-de' ) );
$text( '/_nino/webpage/blog/description', $word( 'description-en' ), $word( 'description-de' ) );
$text( '/project/company/general/name', $word( 'company-en' ), $word( 'company-de' ) );
$text( '/project/company/general/description', $word( 'about-en' ), $word( 'about-de' ) );
postsResolve( $appData, '/blog/lang-german' );
check( 'the page of a post that exists in the native language only has the title and the summary of that post, in the head of the page of another language',
	$said( 'title' ) === 'A-'. $word( 'only-de' ) && $said( 'description' ) === $word( 'only-de-s' ) );

postsResolve( $appData, '/blog/lang-bare' );

check( 'the page of a post no language has a title or a summary for says what the page of its section says: its name for the title, its description for the description - in the language of the page',
	$said( 'title' ) === $word( 'name-en' ) && $said( 'description' ) === $word( 'description-en' ) );
postsResolve( $appData, '/blog/lang-both' );
check( '...and the title and the summary of a post are its own, as they were', $said( 'title' ) === 'M-'. $word( 'both-en' ) && $said( 'description' ) === $word( 'both-en-s' ) );

$text( '/_nino/webpage/blog/name', '', '' );
$text( '/_nino/webpage/blog/description', '', '' );
postsResolve( $appData, '/blog/lang-bare' );
check( 'where the page of the section says none, the company says it: its name, and its description', $said( 'title' ) === $word( 'company-en' ) && $said( 'description' ) === $word( 'about-en' ) );

$text( '/_nino/webpage/blog/post/title', $word( 'own-en' ), $word( 'own-de' ) );
postsResolve( $appData, '/blog/lang-bare' );
check( 'what the project wrote for the page of a post stays what stands for a post that has none',
	$said( 'title' ) === $word( 'own-en' ) && $said( 'description' ) === $word( 'about-en' ) );
$text( '/_nino/webpage/blog/post/title', '', '' );

$text( '/project/company/general/name', '', '' );
$text( '/project/company/general/description', '', '' );
postsResolve( $appData, '/blog/lang-bare' );
$head = postsRender( $appData, '<title>[[/_nino/webpage/blog/post/title]] | x</title><meta name="description" content="[[/_nino/webpage/blog/post/description]]">' );
check( 'and where nothing says it, the page says nothing - never the key of a title nobody made', $said( 'title' ) === '' && $said( 'description' ) === ''
	&& $head === '<title> | x</title><meta name="description" content="">' );

// The page of the section is the one its route has, which the project may have made its own
$appData['/nino/http/routes']['GET://blog'] = [ 'uri' => '/'. $word( 'theirs' ), 'body' => '[template /templates/page-posts]' ];
\Nino\Modules\Posts::routes( $appData );
$text( '/_nino/webpage/'. $word( 'theirs' ). '/name', $word( 'theirs-en' ), $word( 'theirs-de' ) );
postsResolve( $appData, '/blog/lang-bare' );
check( 'the page of the section is found under the uri its route has - the project\'s own page at the path of the section included',
	$said( 'title' ) === $word( 'theirs-en' ) );
unset( $appData['/nino/http/routes']['GET://blog'] );
\Nino\Modules\Posts::routes( $appData );

foreach( $textsKept as $locale => $kept )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $kept );

foreach( [ 'lang-both', 'lang-german', 'lang-mixed', 'lang-english', 'lang-bare' ] as $slug )
	\Nino\Elements::deleteElement( $appData, '/posts/'. $slug, '*' );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Posts\Sections::PATH, $sectionsKept );
unset( $appData['./posts/sections'], $appData['./nino/elements/cache'], $appData['./posts/current'] );
$appData['./nino/locales/current'] = 'de_DE';

echo "\nThe pages the SEO feature cannot find on its own\n";

/*	A section's routes live in /data/posts.php and are registered per request,
	so config.php - the only place sitemap.xml and llms.txt look - carries
	neither the index nor a single post: one wildcard route stands for all of
	them. This is the answer to the question that feature asks, and it is
	called here the way \Nino\Callbacks::doCallbacks() calls it, with a list
	to append to. */
$seoPages = [];
\Nino\Modules\Posts::callbackSeoPages( $appData, $seoPages );

$seoPaths = array_column( $seoPages, 'externalPath' );
$seoByPath = array_column( $seoPages, null, 'externalPath' );

check( 'the section index is offered under the path the section is at', in_array( '/blog', $seoPaths, true ) === true );
check( '...and every published post, one address each, built the way a link on the page is',
	$seoPaths === [ '/blog', '/blog/third-rail', '/blog/second-wind', '/blog/first-light' ]
	|| $seoPaths === [ '/blog', '/blog/first-light', '/blog/second-wind', '/blog/third-rail' ] );
// The same rule the page itself follows: a post dated next week is not a page
// yet, and a sitemap that names it invites a crawler to a 404
check( 'a post that is not published yet is offered to neither document', in_array( '/blog/from-monday', $seoPaths, true ) === false );

check( 'a post carries the title llms.txt has no textfill to read',
	( $seoByPath['/blog/first-light']['title'] ?? '' ) === 'First light' );
check( '...and its summary as the description beside it',
	( $seoByPath['/blog/first-light']['description'] ?? '' ) === 'How it began.' );
check( '...and its own date, which is the only thing a page with no template of its own can be dated by',
	( $seoByPath['/blog/first-light']['lastmod'] ?? '' ) === '2026-01-05' );
check( 'a post with no summary says nothing rather than something empty',
	( $seoByPath['/blog/third-rail']['description'] ?? null ) === '' );

// Not the date of the newest post there is - the date of the newest post the
// index actually shows, which is what a crawler would see changed
check( 'the index is dated by the newest post it lists, not by the one written for next week',
	( $seoByPath['/blog']['lastmod'] ?? '' ) === '2026-03-01' );
check( 'the index keeps the uri its own route has, so the textfills of its title still resolve',
	( $seoByPath['/blog']['uri'] ?? '' ) === '/blog' );

/*	A section with no index page has no page at its path either - the wildcard
	route answers below it and nothing answers it. Offering one would put a 404
	in the sitemap. */
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Posts\Sections::PATH, [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'index' => '', 'perPage' => 2 ],
] ] );
unset( $appData['./posts/sections'] );

$withoutIndex = [];
\Nino\Modules\Posts::callbackSeoPages( $appData, $withoutIndex );

check( 'a section with no index page offers its posts and no page at its own path',
	in_array( '/blog', array_column( $withoutIndex, 'externalPath' ), true ) === false
	&& in_array( '/blog/first-light', array_column( $withoutIndex, 'externalPath' ), true ) === true );

// A subdirectory install: Sections::url() puts the project's own directory in
// front of a post's address, and the Seo feature's paths carry none - its base
// url is the bare domain and the routes it lists are keyed without it
$appData['/nino/dir'] = '/shop';
$inSubdir = [];
\Nino\Modules\Posts::callbackSeoPages( $appData, $inSubdir );
$appData['/nino/dir'] = '';

check( 'the project directory is left off, the way every other page in those two documents leaves it off',
	in_array( '/blog/first-light', array_column( $inSubdir, 'externalPath' ), true ) === true );

ninoDone( $appData );
