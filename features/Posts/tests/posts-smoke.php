<?php
declare(strict_types=1);

// The test of the feature Posts: structure and behaviour, one pin each - the sections, the two routes and their
// limits, what is published, the four components, the two pages, the hooks of Seo and Search against the real
// features. php features/Posts/tests/posts-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old kind', is_array( $manifest ) === true && $manifest['key'] === 'posts'
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false && isset( $manifest['stacks'] ) === false && $manifest['version'] === '2.0.0' );
check( 'the sections file is declared under data and the posts are not: they are elements a backup carries already',
	$manifest['data'] === [ \Nino\Modules\Posts\Sections::PATH ] );

$appData = ninoSandbox( 'posts' );
$appData['./nino/filesystem/path'] = $root;
$sections = \Nino\Modules\Posts\Sections::class;

// What a section is normalised to: the gate every string passes that ends up in a route, a path or a query
$notes = [];
check( 'an empty section is the defaults and says nothing', $sections::normalizeSection( 'blog', [], $notes ) === $sections::DEFAULTS && $notes === [] );
$refused = $sections::normalizeSection( 'blog', [ 'type' => '../../etc', 'path' => '../secrets', 'post' => '/templates/../../config', 'date' => 'a field' ], $notes );
check( 'a type, a path or a template that could climb out never becomes one, and a field name that is none is nothing',
	$refused['type'] === '/posts' && $refused['path'] === 'blog' && $refused['post'] === '/templates/page-post' && $refused['date'] === '' && count( $notes ) === 3 );
$notes = [];
$many = $sections::normalize( [ 'sections' => [ 'blog' => [ 'path' => 'blog' ], 'news' => [ 'type' => '/news', 'path' => 'blog' ], 'Not A Key' => [ 'path' => 'nope' ] ] ], $notes );
check( 'two sections may not share a path and a key that is no slug is no section', array_keys( $many ) === [ 'blog' ] && count( $notes ) === 2 );
$held = $sections::normalizeSection( 'blog', [ 'perPage' => 999, 'sort' => '-date,title' ] );
check( 'the page size is held to what a page can be', $held['perPage'] === $sections::MAX_PER_PAGE && $held['sort'] === '-date,title' );

check( 'Basis, Seo, Search and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'seo' ) === true
	&& \Nino\Features::activate( $appData, 'search' ) === true && \Nino\Features::activate( $appData, 'posts' ) === true );
check( 'the unit brought the element type, the two pages and the words of the list page',
	\Nino\Filesystem::fileExists( $appData, '/elements/posts.php' ) === true && \Nino\Filesystem::fileExists( $appData, '/templates/page-posts.tpl' ) === true
	&& \Nino\Filesystem::fileExists( $appData, '/templates/page-post.tpl' ) === true && \Nino\Html::renderTextfill( $appData, '/template/page-posts/intro/title' ) !== '' );

// A file of the 1.x format is read as it is
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ] ] ] );

$text = [ 'type' => 'string', 'locale' => true ];
// Every date is counted from today, so that the suite holds on any day: a post is published once its day has come
$ago = static fn( int $days ): string => date( 'Y-m-d', time() - 86400 * $days );
$ahead = static fn( int $days ): string => date( 'Y-m-d', time() + 86400 * $days );
// The type has a field named url and a field named image-alt of its own: neither may be read by what the section computes
$model = \Nino\Elements::getElementModel( $appData, '/posts' );
$put = static function( string $slug, array $fields, string $locale = 'de_DE' ) use ( &$appData ): void {
	\Nino\Elements::insertElement( $appData, '/posts/'. $slug, $fields, $locale );
};
$put( 'first-light', [ 'title' => 'First light', 'summary' => 'How it began.', 'date' => $ago( 60 ), 'author' => 'Ada', 'body' => "One.\n\nTwo <script>alert(1)</script> <b>bold</b> <img src=x onerror=alert(1)>" ] );
$put( 'second-wind', [ 'title' => 'Second wind', 'summary' => 'It went on.', 'date' => $ago( 40 ) ] );
$put( 'third-rail', [ 'title' => 'Third rail', 'date' => $ago( 20 ) ] );
$put( 'from-monday', [ 'title' => 'Hidden future', 'summary' => 'Not yet.', 'date' => $ahead( 40 ) ] );
$put( 'undated', [ 'title' => 'Hidden draft', 'date' => $ago( 60 ) ] );
\Nino\Filesystem::mutate( $appData, '/elements/posts.php', static function( mixed $state ): array {
	$state = is_array( $state ) === true ? $state : [];
	$state['*']['undated']['date'] = '';
	return $state;
}, [] );
$put( 'hostile', [ 'title' => '"><script>alert(1)</script> [csrf] [[/project/company/general/name]] & x', 'summary' => '[template /templates/evil] <i>x</i>', 'author' => '<u>Eve</u>', 'date' => $ago( 10 ) ] );
unset( $appData['./nino/elements/cache'] );

$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
$ignored = ninoWarnings();

$visit = static function( string $path ) use ( &$appData ): array {
	unset( $appData['./posts/current'] );
	foreach( [ 'title', 'description' ] as $detail )
		\Nino\Html::removeFill( $appData, '/_nino/webpage/blog/post/'. $detail );
	$appData['./nino/http/requests'] = [];
	return ninoVisit( $appData, $path );
};
// The request a post's page is: the route matched, so the response uri is the section's own
$post = static function( string $slug ) use ( &$appData, $visit ): array {
	$request = $visit( '/blog/'. $slug );
	$request['/nino/http/response']['uri'] = '/blog/post';
	\Nino\Modules\Posts::callbackPost( $appData, $request );
	return $request;
};
$index = static function( string $query ) use ( &$appData, $visit ): array {
	$request = $visit( '/blog'. $query );
	$request['/nino/http/response']['uri'] = '/blog';
	\Nino\Modules\Posts::callbackIndex( $appData, $request );
	return $request;
};
$draw = static function( string $source ) use ( &$appData ): string {
	return trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-posts' ] + \Nino\Html::context( $appData ) ) );
};
$card = '[posts][section-col][section-title .title href=".url"][/section-col][/posts]';

echo "\nThe components\n";
check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'posts' ) === [] );
$schemas = \Nino\Components::components( $appData );
$mine = array_intersect_key( $schemas, array_flip( [ 'posts', 'posts-entry', 'posts-pager', 'posts-nav' ] ) );
check( 'the four components are in their group, labelled in both languages, with an icon of the table, and the group has a standard part and a demo',
	count( $mine ) === 4 && array_filter( $mine, static fn( array $s ): bool => $s['group'] !== 'posts' || isset( $s['label']['de_DE'], $s['label']['en_US'] ) === false || in_array( $s['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['posts']['part'] ?? false ) === true && \Nino\Components::groups( $appData )['posts']['demo'] !== '' );
$unlabelled = [];
foreach( $mine as $name => $schema )
	foreach( $schema['attributes'] as $attribute => $declared )
		if( isset( $declared['label']['de_DE'], $declared['label']['en_US'] ) === false )
			$unlabelled[] = $name. '.'. $attribute;
check( 'every attribute has a label in both languages', $unlabelled === [] );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/posts/part/part.css' ), [ 'posts', 'posts-entry', 'posts-pager', 'posts-nav' ] ) === [] );
check( 'a tool offers the list in a row, the pager and the navigation in a column, and the list and the post are loops that draw no element of their own',
	in_array( 'posts', \Nino\Components::allowed( $appData, 'section-row' ), true ) === true && in_array( 'posts-pager', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& in_array( 'posts-nav', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& ( $schemas['posts']['through'] ?? false ) === true && ( $schemas['posts-entry']['through'] ?? false ) === true && ( $schemas['posts']['children'] ?? '' ) === 'nodes' );
check( 'the section is a choice over the sections of the file, the page\'s own first',
	array_keys( $schemas['posts']['attributes']['section']['optionLabels'] ?? [] ) === [ '', 'blog' ] );

echo "\nThe routes and their limits\n";
check( 'a section registers its list and its wildcard route, and answers for both', ( $appData['/nino/http/routes']['GET://blog']['uri'] ?? '' ) === '/blog'
	&& ( $appData['/nino/http/routes']['GET://blog/*']['uri'] ?? '' ) === '/blog/post'
	&& \Nino\Callbacks::registered( $appData, '/nino/http/response/GET://blog/post' ) !== [] && \Nino\Callbacks::registered( $appData, '/nino/http/response/GET://blog' ) !== [] );
$found = [];
foreach( [ 'first-light', 'second-wind', 'hostile' ] as $slug )
	$found[$slug] = ( $post( $slug )['/nino/http/response']['statusCode'] ?? 200 ) !== 404;
check( 'a post that is published is a page', $found === [ 'first-light' => true, 'second-wind' => true, 'hostile' => true ] );
$slugs = [ 'from-monday', 'undated', 'nothing-here', '..', '.hidden', 'a..b', '-x', 'first-light/extra', 'first-light%00', 'FIRST-LIGHT', 'x y', 'posts/first-light', str_repeat( 'a', 5000 ) ];
$refused = [];
foreach( $slugs as $slug )
	if( ( $post( $slug )['/nino/http/response']['statusCode'] ?? 200 ) !== 404 )
		$refused[] = $slug;
check( 'a slug that is no post - one hidden, a draft, one that is none, a climb, a second segment, another type, a null byte - is a 404, and never a warning', $refused === [] && ninoWarnings() === [] );
$bad = [];
foreach( [ '', '?page=1', '?page=2' ] as $query )
	if( ( $index( $query )['/nino/http/response']['statusCode'] ?? 200 ) === 404 )
		$bad[] = $query;
check( 'the first page and a page the list has are no 404', $bad === [] );
$pages = [];
foreach( [ '?page=0', '?page=-1', '?page=3', '?page=01', '?page=1.5', '?page=abc', '?page=', '?page[]=1', '?page=99999999999999999999', '?page=9999999', '?page=%00' ] as $query )
	if( ( $index( $query )['/nino/http/response']['statusCode'] ?? 200 ) !== 404 )
		$pages[] = $query;
check( 'a page that is 0, negative, not a number, one past the last or beyond any size is a 404, and never a warning', $pages === [] && ninoWarnings() === [] );
$visit( '/blog?page=-1' );
check( 'a list that stands on another page draws the first page for a number that is none', $draw( '[posts]x[/posts]' ) === str_repeat( 'x', 2 ) );

echo "\nWhat is published\n";
$listed = static function() use ( &$appData ): array {
	$section = \Nino\Modules\Posts::section( $appData, 'blog' );
	return array_column( \Nino\Modules\Posts::posts( $appData, $section ), '.uri' );
};
check( 'a post dated in the future and one without a date are not published: not in the list the pager counts, and the others are', $listed() === [ '/posts/hostile', '/posts/third-rail', '/posts/second-wind', '/posts/first-light' ] );
// A page that shows the list is good for the cache only until the next post comes due - the moment itself, never the clock of the server
unset( $appData['./nino/cache/until'] );
$visit( '/blog' );
$draw( '[posts limit="9"]x[/posts]' );
check( 'where a list is drawn the page of the cache is limited to the day of the next post that waits', ( $appData['./nino/cache/until'] ?? 0 ) === strtotime( $ahead( 40 ) ) );
$appData['./posts/now'] = strtotime( $ahead( 41 ) );
unset( $appData['./nino/cache/until'] );
check( 'with the clock held past that day the post is published and no limit is asked for', in_array( '/posts/from-monday', $listed(), true ) === true && isset( $appData['./nino/cache/until'] ) === false );
unset( $appData['./posts/now'], $appData['./nino/cache/until'] );
$visit( '/blog' );
check( 'the list draws the posts of the first page, newest first, by the section\'s perPage, as links built from the path and the name',
	$draw( $card ) === '<div class="section-col"><h2 class="section-title"><a href="/blog/hostile">&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt; [csrf] [[/project/company/general/name]] &amp; x</a></h2></div>'
	. '<div class="section-col"><h2 class="section-title"><a href="/blog/third-rail">Third rail</a></h2></div>' );
check( 'a teaser is the newest posts without paging, and the hidden ones are in none of them', substr_count( $draw( '[posts limit="9"]<x>[/posts]' ), '<x>' ) === 4
	&& str_contains( $draw( '[posts limit="9"][section-col][section-title .title][/section-col][/posts]' ), 'Hidden' ) === false );
$visit( '/blog?page=2' );
check( 'the second page is the rest', substr_count( $draw( $card ), "section-col" ) === 2
	&& str_contains( $draw( $card ), '/blog/second-wind' ) === true );
$visit( '/blog' );

echo "\nWhat a post is, and is not\n";
$cell = static fn( string $field ): string => $draw( '[posts limit="1"][section-col][text .'. $field. '][/section-col][/posts]' );
check( 'the title, the summary and the author stay text: a tag, a call and a fill are what they are',
	$draw( '[posts limit="1"][section-col][section-title .title][text .summary][/section-col][/posts]' ) !== ''
	&& str_contains( $draw( '[posts limit="1"][section-col][section-title .title][/section-col][/posts]' ), '<script' ) === false
	&& str_contains( $draw( '[posts limit="1"][section-col][text .summary][/section-col][/posts]' ), '&lt;i&gt;x&lt;/i&gt;' ) === true );
$visit( '/blog/first-light' );
$appData['./posts/current'] = '/posts/first-light';
$entry = $draw( '[posts-entry][section-col][section-title .title][text .author][text .body][/section-col][/posts-entry]' );
check( 'the entry draws the post of the address once', str_contains( $entry, 'First light' ) === true && str_contains( $entry, 'Ada' ) === true && substr_count( $entry, 'class="section-col"' ) === 1 );
check( 'the body goes through the sanitizer of the kernel: paragraphs and emphasis stay, a script and an event handler do not',
	str_contains( $entry, '<p>One.</p>' ) === true && str_contains( $entry, '<b>' ) === false && str_contains( $entry, '<script' ) === false && str_contains( $entry, 'onerror' ) === false && str_contains( $entry, '<img' ) === false );
$appData['./posts/current'] = '/posts/hostile';
$hostile = $draw( '[posts-entry][section-col][section-title .title][text .author][text .summary][/section-col][/posts-entry]' );
check( 'a title, an author and a summary that spell tags, a call or a fill are text, once escaped', str_contains( $hostile, '<script' ) === false && str_contains( $hostile, '&lt;u&gt;Eve&lt;/u&gt;' ) === true
	&& str_contains( $hostile, '[template /templates/evil]' ) === true && str_contains( $hostile, '<i>' ) === false );
$appData['./posts/current'] = '/posts/from-monday';
check( 'the entry draws nothing for a post that is hidden, and nothing where the address names none', $draw( '[posts-entry]x[/posts-entry]' ) === '' && ( $appData['./posts/current'] = '' ) === '' && $draw( '[posts-entry]x[/posts-entry]' ) === '' );
check( 'a list inside a list draws nothing, so that the rounds are the limit and not its square', substr_count( $draw( '[posts limit="2"][posts limit="2"]z[/posts][/posts]' ), 'z' ) === 0 );

// An element type with fields of its own named url and picture: neither is read for what the section computes
\Nino\Elements::insertElementType( $appData, '/notes', [ 'headline' => $text, 'blurb' => $text, 'when' => [ 'type' => 'date' ], 'url' => [ 'type' => 'string' ], 'text' => [ 'type' => 'string', 'html' => true ] ] );
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'notes' => [ 'type' => '/notes', 'path' => 'notes', 'title' => 'headline', 'summary' => 'blurb', 'date' => 'when', 'sort' => '-when', 'image' => '', 'body' => 'text' ] ] ] );
unset( $appData['./posts/sections'] );
\Nino\Elements::insertElement( $appData, '/notes/one', [ 'headline' => 'Note one', 'blurb' => 'A blurb', 'when' => $ago( 90 ), 'url' => 'javascript:alert(1)', 'text' => '<p>Body</p>' ], 'de_DE' );
\Nino\Elements::insertElement( $appData, '/notes/bad..name', [ 'headline' => 'Climb', 'when' => $ago( 90 ) ], 'de_DE' );
unset( $appData['./nino/elements/cache'] );
$notes = $draw( '[posts section="notes"][section-col][section-title .title href=".url"][text .summary][text .date][/section-col][/posts]' );
check( 'the fields a call reads are the section\'s whatever they are called, and .url is made of the path and the name, never of a field named url; an element whose name is no slug is not listed',
	str_contains( $notes, 'href="/notes/one"' ) === true && str_contains( $notes, 'javascript' ) === false && str_contains( $notes, 'Note one' ) === true && str_contains( $notes, 'A blurb' ) === true && str_contains( $notes, 'Climb' ) === false );
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ] ] ] );
unset( $appData['./posts/sections'] );

echo "\nThe pager and the way between posts\n";
$visit( '/blog' );
$pager = $draw( '[posts-pager]' );
check( 'the pager is a navigation of links with the page in the query: page one is the section itself, the page that is on is marked and a link to the next carries rel',
	str_contains( $pager, '<nav class="posts-pager" aria-label="' ) === true && str_contains( $pager, 'aria-current="page">1</a>' ) === true && str_contains( $pager, 'href="/blog?page=2">2</a>' ) === true
	&& str_contains( $pager, 'rel="next"' ) === true && str_contains( $pager, 'rel="prev"' ) === false && str_contains( $pager, 'href="/blog"' ) === true );
$visit( '/blog?page=2' );
check( 'from page two the way back is the section\'s own url', str_contains( $draw( '[posts-pager]' ), 'href="/blog" rel="prev"' ) === true );
$visit( '/blog?page=9' );
$beyond = $draw( '[posts-pager]' );
check( 'a page past the end marks none and leads back to the last page that has posts', str_contains( $beyond, 'aria-current' ) === false && str_contains( $beyond, 'href="/blog?page=2" rel="prev"' ) === true );
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 50 ] ] ] );
unset( $appData['./posts/sections'] );
$visit( '/blog' );
check( 'one page is no pager, and the hidden posts are not counted', $draw( '[posts-pager]' ) === '' );
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ] ] ] );
unset( $appData['./posts/sections'] );
$visit( '/blog?page=2' );
$word = $draw( '[posts-pager prev="<b>[csrf]" label="x"]' );
check( 'a word of the call is text, escaped once, and is the word of the way back on page two', str_contains( $word, 'rel="prev">&lt;b&gt;[csrf]</a>' ) === true && str_contains( $word, '<b>' ) === false );
// A pager on a page that is not the section\'s leads to the page it stands on, where the list reads its page
$visit( '/elsewhere?page=2' );
$foreign = $draw( '[posts-pager]' );
check( 'a pager on another page of the site leads to that page and not to the section', str_contains( $foreign, 'href="/elsewhere" rel="prev"' ) === true && str_contains( $foreign, '/blog' ) === false );

$appData['./posts/current'] = '/posts/third-rail';
$nav = $draw( '[posts-nav]' );
check( 'the navigation leads to the post before and the post after in the section\'s order, as links of the path and the name',
	str_contains( $nav, 'rel="prev" href="/blog/hostile"' ) === true && str_contains( $nav, 'rel="next" href="/blog/second-wind"' ) === true && str_contains( $nav, 'Third rail' ) === false
	&& str_contains( $nav, '<script' ) === false && str_contains( $nav, '[[/project' ) === true && str_contains( $nav, '&amp; x' ) === true );
$appData['./posts/current'] = '/posts/hostile';
$edge = $draw( '[posts-nav]' );
check( 'a title that spells a call is text there as well, and the first post has no way back', str_contains( $edge, 'rel="prev"' ) === false && str_contains( $edge, '[[' ) === false );
$appData['./posts/current'] = '/posts/from-monday';
check( 'a post that is hidden has no neighbours, and no address no navigation', $draw( '[posts-nav]' ) === '' && ( $appData['./posts/current'] = '' ) === '' && $draw( '[posts-nav]' ) === '' );

echo "\nThe page of a post\n";
$request = $post( 'hostile' );
$head = \Nino\Html::getFills( $appData );
$title = (string) ( $head['[[/_nino/webpage/blog/post/title]]'] ?? '' );
check( 'the title of the page is the post\'s title as text: no tag, no call and no fill is left in it', $title !== '' && str_contains( $title, '<' ) === false && str_contains( $title, '[' ) === false && str_contains( $title, '&quot;&gt;&lt;script&gt;' ) === true );
$page = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/templates/page-post.tpl' );
$list = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/templates/page-posts.tpl' );
$found = [];
foreach( [ $page, $list ] as $source )
	foreach( \Nino\Components::check( $appData, \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ) ) ) as $at => $problems )
		foreach( $problems as $problem )
			if( ( $problem['code'] === 'unknown' && $problem['detail'] === 'template' ) === false && ( $problem['code'] === 'free' && $at === 0 ) === false )
				$found[] = $problem['code'];
check( 'the two pages of the unit are made of components: no class of Nino 1, no element of its own, nothing a tool keeps as free html', preg_match( '/nino-|<section|<div|<article|<h1/', $page. $list ) === 0 && $found === [] );
$full = \Nino\Html::renderSource( $appData, $list, [ 'template' => 'page-posts' ] + \Nino\Html::context( $appData ) );
check( 'the list page draws the cards with a link, and the pager', str_contains( $full, 'section-col--variant-card' ) === true && str_contains( $full, 'href="/blog/' ) === true );

echo "\nThe hooks, against the real features\n";
$map = static function( array $pages ): array { return array_column( $pages, null, 'externalPath' ); };
$sitemap = ninoVisit( $appData, '/sitemap.xml' );
\Nino\Http::response( $appData, $sitemap );
$xml = (string) ( $sitemap['/nino/http/response']['body'] ?? '' );
check( 'the sitemap of Seo names the list and the published posts, and neither a hidden one nor a draft',
	str_contains( $xml, '/blog/first-light' ) === true && str_contains( $xml, '/blog/hostile' ) === true && str_contains( $xml, '/blog</loc>' ) === true
	&& str_contains( $xml, 'from-monday' ) === false && str_contains( $xml, 'undated' ) === false );
$seo = [];
\Nino\Modules\Posts::callbackSeoPages( $appData, $seo );
check( 'a post carries its title, its summary and its date, and the list the date of the newest post it shows', ( $map( $seo )['/blog/first-light']['lastmod'] ?? '' ) === $ago( 60 )
	&& ( $map( $seo )['/blog/first-light']['title'] ?? '' ) === 'First light' && ( $map( $seo )['/blog']['lastmod'] ?? '' ) === $ago( 10 ) );
$appData['/nino/dir'] = '/shop';
$sub = [];
\Nino\Modules\Posts::callbackSeoPages( $appData, $sub );
$appData['/nino/dir'] = '';
check( 'in a project directory the pages are listed without it, as the Seo feature keys its own', in_array( '/blog/first-light', array_column( $sub, 'externalPath' ), true ) === true );

// A post whose title and summary spell a call, a fill and a tag, through the documents of the real Seo: text in a document, never run
\Nino\Html::addFills( $appData, [ '/template/trap/mark/word' => 'TRAPMARK' ], '*' );
$trap = 'trap [csrf] [[/template/trap/mark/word]] <b>x</b> end';
$put( 'llms-trap', [ 'title' => $trap, 'summary' => $trap, 'date' => $ago( 5 ) ] );
unset( $appData['./nino/elements/cache'] );
$documents = '';
foreach( [ '/llms.txt', '/sitemap.xml' ] as $address ) {
	$appData['./nino/http/requests'] = [];
	$document = ninoVisit( $appData, $address );
	\Nino\Http::response( $appData, $document );
	// What the kernel does with every body that is a string before it sends it: it is read for calls and fills
	\Nino\Html::response( $appData, $document );
	$documents .= (string) ( $document['/nino/http/response']['body'] ?? '' );
}
check( 'a title and a summary that spell a call, a fill and a tag are text in llms.txt and sitemap.xml: no call is read, no fill is filled, no tag is drawn',
	str_contains( $documents, 'llms-trap' ) === true && str_contains( $documents, '_csrf' ) === false && str_contains( $documents, 'TRAPMARK' ) === false && str_contains( $documents, '<input' ) === false );
\Nino\Elements::deleteElement( $appData, '/posts/llms-trap', '*' );
unset( $appData['./nino/elements/cache'] );

$appData['/nino/elements/index'] = [ 'posts' => [ 0 => 'title', 1 => 'summary' ] ];
$put( 'lantern-now', [ 'title' => 'Lantern now', 'date' => $ago( 60 ) ] );
$put( 'lantern-soon', [ 'title' => 'Lantern soon', 'date' => $ahead( 40 ) ] );
unset( $appData['./nino/elements/cache'] );
$built = \Nino\Modules\Search::createIndexes( $appData, 'posts' );
\Nino\Modules\Search::forget( $appData );
$hits = array_column( \Nino\Modules\Search::getHits( $appData, 'posts', 'lantern' ), 'uri' );
check( 'the index holds a post that is not published and the search of Search does not find it', $built['failed'] === [] && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/index-posts.php' ) ), '/posts/lantern-soon' ) === true
	&& $hits === [ '/posts/lantern-now' ] );
$appData['./nino/http/requests'] = [];
$api = ninoVisit( $appData, '/.search?q=lantern' );
\Nino\Modules\Search::forget( $appData );
\Nino\Modules\Search::callbackApi( $appData, $api );
$first = $api['/nino/http/response']['body']['hits'][0] ?? [];
check( 'a hit of the json endpoint that is a post carries its url, made of the path and the name', ( $first['uri'] ?? '' ) === '/posts/lantern-now' && ( $first['url'] ?? '' ) === '/blog/lantern-now' && count( $api['/nino/http/response']['body']['hits'] ?? [] ) === 1 );
$list = static fn( array $uris ): array => array_map( static fn( string $uri ): array => [ 'uri' => $uri, 'type' => '/posts', 'score' => 1.0, 'coverage' => 1.0, 'fields' => [] ], $uris );
$kept = array_column( \Nino\Callbacks::doCallbacks( $appData, '/search/hits', $list( [ '/posts/first-light', '/posts/from-monday', '/posts/undated', '/posts/missing' ] ) ), 'uri' );
check( 'a hit of a post that is hidden, a draft or none is taken from the list, in whatever order it comes', $kept === [ '/posts/first-light' ] );
$later = \Nino\Elements::getElementWithFallback( $appData, '/posts/from-monday', '' );
$unpublished = [ 'uri' => '/posts/from-monday', 'type' => '/posts', 'element' => $later ];
$published = [ 'uri' => '/posts/first-light', 'type' => '/posts', 'element' => \Nino\Elements::getElementWithFallback( $appData, '/posts/first-light', '' ) ];
$withUnpublished = \Nino\Modules\Posts::callbackSearchHit( $appData, $unpublished );
$withPublished = \Nino\Modules\Posts::callbackSearchHit( $appData, $published );
check( 'a hit of a post that is not published gets no url from the hook of the json endpoint, one that is published gets it', array_key_exists( 'url', $withUnpublished ) === false && ( $withPublished['url'] ?? '' ) === '/blog/first-light' );
$nonList = false;
check( 'an answer that is no list is left as it is, for Search to refuse', \Nino\Callbacks::doCallbacks( $appData, '/search/hits', $nonList ) === false );

echo "\nThe blocks, the words and the folder\n";
$blocks = [];
foreach( [ 'posts-list', 'posts-entry' ] as $key ) {
	$block = \Nino\Blocks::get( $appData, $key );
	$blocks[$key] = is_array( $block ) === true && in_array( 'posts', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [];
}
check( 'both blocks are valid and name the feature', $blocks === [ 'posts-list' => true, 'posts-entry' => true ] );
check( 'the demo of the group draws', str_contains( $draw( (string) \Nino\Components::groups( $appData )['posts']['demo'] ), 'section-wrap' ) === true );
$words = [ 'de_DE' => (array) include $dir. '/install/text/de_DE.php', 'en_US' => (array) include $dir. '/install/text/en_US.php' ];
$details = [];
foreach( [ 'blog', 'blog/post' ] as $page )
	foreach( [ 'name', 'title', 'description' ] as $detail )
		foreach( $words as $locale => $set )
			if( isset( $set['[[/_nino/webpage/'. $page. '/'. $detail. ']]'] ) === false )
				$details[] = $locale. ' '. $page. ' '. $detail;
check( 'both languages bring the same words, and name, title and description of both pages the section is: the list and the post', array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) && $details === [] );

// The list page of a section with no words of its own is titled by the page of the first one, never by the key of a text
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'blog' => [ 'path' => 'blog' ], 'b2' => [ 'path' => 'blog/notes' ] ] ] );
unset( $appData['./posts/sections'] );
\Nino\Html::removeFill( $appData, '/_nino/webpage/b2/title' );
$second = $visit( '/blog/notes' );
$second['/nino/http/response']['uri'] = '/b2';
\Nino\Modules\Posts::callbackIndex( $appData, $second );
$fills = \Nino\Html::getFills( $appData );
check( 'the list of a second section has a title and a description of its own where nobody wrote them: the first section\'s, not the key', ( $fills['[[/_nino/webpage/b2/title]]'] ?? '' ) === 'Blog' && ( $fills['[[/_nino/webpage/b2/description]]'] ?? '' ) !== '' );
\Nino\Filesystem::putFileContent( $appData, $sections::PATH, [ 'sections' => [ 'blog' => [ 'type' => '/posts', 'path' => 'blog', 'perPage' => 2 ] ] ] );
unset( $appData['./posts/sections'] );

check( 'switched off, the posts stay', \Nino\Features::deactivate( $appData, 'posts' ) === true && \Nino\Elements::getElement( $appData, '/posts/first-light', 'de_DE', false ) !== false );
$off = ninoSandbox( 'posts-off' );
$off['./nino/filesystem/path'] = $root;
\Nino\Features::activate( $off, 'basis' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
$drawnOff = \Nino\Html::renderSource( $off, '[posts]x[/posts][posts-entry]x[/posts-entry][posts-pager][posts-nav]', \Nino\Html::context( $off ) );
check( 'where the feature is off its four names are registered by nobody, the calls draw nothing and the log says so', $drawnOff === '' && array_intersect( [ 'posts', 'posts-entry', 'posts-pager', 'posts-nav' ], array_keys( \Nino\Components::components( $off ) ) ) === [] && ninoWarnings() !== [] );

$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Modules\\\\Components|\bnino-(?!sanitize)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
