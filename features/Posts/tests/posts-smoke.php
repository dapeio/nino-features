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
// built on the first, and the install unit's own words are read through the
// second (see the other feature suites)
$appData['/nino/dir'] 								= '';
$appData['/nino/locales/textfiles']		= \Nino\AppData::DEFAULTS['/nino/locales/textfiles'];

/**
 *	A request the way \Nino\Http::request() builds one, minus everything this
 *	feature never looks at.
 *
 *	The response uri is the path that was asked for, not the route's own: the
 *	copy Http::request() pushes onto './nino/http/requests' is made before
 *	Http::response() matches a route, so that is what a shortcode reading the
 *	live request actually finds. Written the other way round, this fixture
 *	agreed with an implementation that read the resolved uri - and a browser
 *	found what it could not
 */
function postsRequest( string $path ): array {
	return [
		'/nino/http/request' 	=> [ 'uri' => $path, 'method' => 'GET' ],
		'/nino/http/response'	=> [ 'uri' => $path, 'body' => '', 'header' => [], 'statusCode' => 200 ],
	];
}

/**
 *	Put one in place, resolve it, and answer what came out - the shortcodes
 *	read the live request rather than being handed one
 */
function postsResolve( array &$appData, string $path ): array {
	$request = postsRequest( $path );
	unset( $appData['./posts/current'] );
	$appData['./nino/http/requests'] = [ $request ];
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
check( 'it is content, and names the kernel it needs', ( $feature['category'] ?? '' ) === 'content'
	&& ( $feature['nino'] ?? '' ) === '^1.1' );
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
check( 'and the words those templates say', \Nino\Html::renderTextfill( $appData, '/posts/index/title' ) !== '' );

// The section the rest of this runs against
\Nino\Modules\Posts\Sections::write( $appData, \Nino\Modules\Posts\Sections::normalize( [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ],
] ] ) );
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
	\Nino\Html::renderTextfill( $appData, '/webpage/blog/post/title' ) === 'Second wind'
	&& \Nino\Html::renderTextfill( $appData, '/webpage/blog/post/description' ) === 'And onwards.' );

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

$appData['./nino/http/requests'] = [ postsRequest( '/blog' ) ];
unset( $appData['./posts/current'] );
$_GET = [];

$list = postsRender( $appData, '[posts]<li><a href="[[.url]]">[[title]]</a></li>[/posts]' );
check( '[posts] lists the first page of the section the page is', $list ===
	'<li><a href="/blog/third-rail">Third rail</a></li><li><a href="/blog/second-wind">Second wind</a></li>' );

$_GET = [ 'page' => '2' ];
check( '...and the second page is the rest', postsRender( $appData, '[posts]<a href="[[.url]]">[[title]]</a>[/posts]' ) ===
	'<a href="/blog/first-light">First light</a>' );

$_GET = [ 'page' => '99' ];
check( 'a page past the end is empty rather than the last one over again', postsRender( $appData, '[posts][[title]][/posts]' ) === '' );

$_GET = [];
check( 'a limit of its own turns the paging off - a teaser is not page one',
	postsRender( $appData, '[posts limit="3"][[title]] [/posts]' ) === 'Third rail Second wind First light ' );

$pager = postsRender( $appData, '[posts-pager]' );
check( 'the pager links every page and marks the one that is on', str_contains( $pager, 'href="/blog?page=2"' ) === true
	&& str_contains( $pager, '<li class="nino-is-active"><a href="/blog" aria-current="page">1</a></li>' ) === true
	&& str_contains( $pager, 'rel="next"' ) === true );
check( '...in the markup the framework\'s own .nino-pagination is written against',
	str_contains( $pager, '<ul class="nino-pagination">' ) === true && substr_count( $pager, '<li' ) === 3 );
check( '...and page one is the section itself, not a query that means the same', str_contains( $pager, 'page=1' ) === false );

$_GET = [ 'page' => '2' ];
check( 'from page two the way back is the section\'s own url', str_contains( postsRender( $appData, '[posts-pager]' ), '<li><a href="/blog" rel="prev">' ) === true );
$_GET = [];

\Nino\Modules\Posts\Sections::write( $appData, \Nino\Modules\Posts\Sections::normalize( [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 50 ],
] ] ) );
unset( $appData['./posts/sections'] );
check( 'one page is no pager - saying "1 of 1" is telling somebody there is more', postsRender( $appData, '[posts-pager]' ) === '' );

\Nino\Modules\Posts\Sections::write( $appData, \Nino\Modules\Posts\Sections::normalize( [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ],
] ] ) );
unset( $appData['./posts/sections'] );

postsResolve( $appData, '/blog/second-wind' );

check( '[post] is the post the url is for', postsRender( $appData, '[post]<h1>[[title]]</h1>[/post]' ) === '<h1>Second wind</h1>' );
check( '...and renders nothing on a page that is not one',
	postsRender( $appData, '[posts][/posts]' ) !== null && ( static function() use ( &$appData ): bool {
		$appData['./nino/http/requests'] = [ postsRequest( '/blog' ) ];
		unset( $appData['./posts/current'] );
		return postsRender( $appData, '[post]<h1>[[title]]</h1>[/post]' ) === '';
	} )() === true );

postsResolve( $appData, '/blog/second-wind' );
$nav = postsRender( $appData, '[post-nav]<a class="[[.rel]]" href="[[.url]]">[[title]]</a>[/post-nav]' );
check( 'the way to the post before and the post after, in the section\'s own order', $nav ===
	'<a class="prev" href="/blog/third-rail">Third rail</a><a class="next" href="/blog/first-light">First light</a>' );

postsResolve( $appData, '/blog/third-rail' );
check( '...and the newest post has no newer one', postsRender( $appData, '[post-nav][[.rel]] [/post-nav]' ) === 'next ' );


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
\Nino\Modules\Posts\Sections::write( $appData, \Nino\Modules\Posts\Sections::normalize( [ 'sections' => [
	'blog' => [ 'type' => '/posts', 'path' => 'blog', 'index' => '', 'perPage' => 2 ],
] ] ) );
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

$_GET = [];
ninoDone( $appData );
