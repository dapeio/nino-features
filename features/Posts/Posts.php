<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Posts				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Posts							A page per element, and a list with paging: what turns
	 *										an element type into a section of the site.
	 *
	 *										Nino has the content half already. An element type is
	 *										records of one shape, the Elements panel edits them,
	 *										[collection] lists them, Search indexes them and a
	 *										backup carries them. What it has no answer for is the
	 *										other half of a blog: every record wants a page of its
	 *										own at a readable url, the list wants to be more than
	 *										one page long, and a title wants to be the post's
	 *										rather than the route's.
	 *
	 *										So this owns no content. A section names an element
	 *										type and a path; the routes follow from that, and the
	 *										records stay the project's - written where every other
	 *										element is written, searchable, translatable, and still
	 *										there when the feature is removed.
	 *
	 *										The routes are this module's own, registered per
	 *										request rather than written into config.php. Removing
	 *										the feature takes the section's pages with it and leaves
	 *										the posts, which is the honest way round: a route
	 *										nothing answers is worse than no route.
	 *
	 *										Four components draw it. posts is a loop: what it holds
	 *										is drawn once for each post of the page that is on, and
	 *										a component in it reads the post as its source - the
	 *										fields of the type, and four the section computes for
	 *										it (see _shaped()). posts-entry is the same loop for the
	 *										one post the address is for, posts-pager leads to the
	 *										other pages of the list and posts-nav to the post before
	 *										and the post after.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Posts {

		/*	The internal uri of a section's two routes - the identity its text
			keys hang off, and what the response callback is registered on.
			Separate identities on purpose: a list page and a post page want
			different titles, and [[/_nino/webpage/blog/title]] can only be one thing */
		public const string INDEX_URI = '/%s';
		public const string POST_URI 	= '/%s/post';

		// Which page of the list is wanted. A get parameter rather than a path
		// segment: /blog/2 is a post called "2", and a section must not have to
		// reserve slugs that look like numbers
		public const string PAGE_KEY = 'page';

		// What a page number is: digits, no sign, no zero in front, a size no list has pages for
		public const string PAGE_PATTERN = '/^[1-9][0-9]{0,5}$/D';

		/*	One segment, and the alphabet an element uri is written in (see
			Elements::insertElement()). A slug that could climb out of the type,
			or name a second type, never becomes a path - and a post whose
			element carries one that is none is a post nobody can open, so no
			list, sitemap or search offers it either */
		public const string SLUG_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/D';

		// The directory of the small pieces of markup the renderers put together: the items of the pager and of the navigation, the box of a post in another language
		public const string TEMPLATES = '/features/Posts/templates';

		// The state of the request while a loop draws its posts: a loop inside a loop draws nothing
		private const string DRAWING = './nino/posts/drawing';

		/**
		 *	Register a section's routes and the callbacks that answer for the posts
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			self::routes( $appData );

			// Here rather than in routes(), which a test calls again after it
			// changes a section: callbacks accumulate, and a second registration
			// would put every post in the sitemap twice
			\Nino\Callbacks::registerCallback( $appData, '/seo/pages', [ self::class, 'callbackSeoPages' ] );

			// Under the name as a string, like the one above: Search is not required
			\Nino\Callbacks::registerCallback( $appData, '/search/hits', [ self::class, 'callbackSearchHits' ] );
			\Nino\Callbacks::registerCallback( $appData, '/search/hit', [ self::class, 'callbackSearchHit' ] );
		}

		/**
		 *	The routes alone - see init(), which is where the kernel calls this
		 *	from. Its own method because the routes are a pure function of the
		 *	sections, and a test that changes one wants them again.
		 *
		 *	The page under a section's path renders the section's index from
		 *	here on - but it keeps everything the project's own route said
		 *	about it, and only its body becomes the list. A route is more than
		 *	a template: it carries the menus the page is in, the locale it
		 *	answers for, and the identity its texts hang off. Registering a
		 *	fresh one over it took a blog out of its own navigation, which is
		 *	the kind of thing a test agrees with and a browser does not.
		 *
		 *	Nothing persisted is touched either way, so removing the feature
		 *	gives the page back exactly as it was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function routes( array &$appData ): void {

			foreach( self::sections( $appData ) as $key => $section ) {

				$postUri = sprintf( self::POST_URI, $key );

				if( $section['index'] !== '' ) {

					$existing = $appData['/nino/http/routes']['GET://'. $section['path']] ?? [];
					$existing = is_array( $existing ) === true ? $existing : [];
					$indexUri = (string) ( $existing['uri'] ?? sprintf( self::INDEX_URI, $key ) );

					$appData['/nino/http/routes']['GET://'. $section['path']] = array_merge( $existing, [
						'uri' 	=> $indexUri,
						'body' 	=> '[template '. $section['index']. ']',
					] );

					\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET:/'. $indexUri, [ self::class, 'callbackIndex' ] );
				}

				$appData['/nino/http/routes']['GET://'. $section['path']. '/*'] = [
					'uri' 	=> $postUri,
					'body' 	=> '[template '. $section['post']. ']',
				];

				\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET:/'. $postUri, [ self::class, 'callbackPost' ] );
			}
		}

		/**
		 *	The sections, read once per request. Every component and the route
		 *	callbacks want them, and a file read per component on a list page is
		 *	a file read per post
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ key => section ]
		 */
		public static function sections( array &$appData ): array {

			if( isset( $appData['./posts/sections'] ) === false )
				$appData['./posts/sections'] = Posts\Sections::read( $appData );

			return $appData['./posts/sections'];
		}

		/**
		 *	The options of the attribute section of a component: the page's own, and each section by its key
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		The attribute that asks
		 *
		 *	@return 	array										value => label
		 */
		public static function sectionOptions( array &$appData, string $attribute ): array {

			$options = [ '' => [ 'de_DE' => 'Rubrik der Seite', 'en_US' => 'Section of the page' ] ];

			foreach( self::sections( $appData ) as $key => $section )
				$options[$key] = $key. ' (/'. $section['path']. ')';

			return $options;
		}

		/**
		 *	One of them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					'' for the section of the page being rendered,
		 *																		and the first one where that is no section
		 *
		 *	@return 	array										A section, or [] where there is none
		 */
		public static function section( array &$appData, string $key = '' ): array {

			$sections = self::sections( $appData );

			if( $key !== '' )
				return $sections[$key] ?? [];

			// The page being rendered knows which section it belongs to, and a
			// posts on a blog page should not have to repeat it
			$current = self::currentKey( $appData );

			if( $current !== '' && isset( $sections[$current] ) === true )
				return $sections[$current];

			return $sections === [] ? [] : reset( $sections );
		}

		/**
		 *	The section of the page being rendered
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string								A section key, or ''
		 */
		public static function currentKey( array &$appData ): string {

			/*	Off the live request, the way Modules\Navigation and the Seo
				feature's shortcodes find it: a renderer never sees $request.
				The *request* path rather than the resolved route's uri, because
				the copy Http::request() stored is the one it made before the
				route was matched - its response uri is still the path that was
				asked for */
			return self::match( $appData, (string) ( \Nino\Http::getRequest( $appData )['/nino/http/request']['uri'] ?? '' ) )[0];
		}

		/**
		 *	Which section a path belongs to, and what it says after the
		 *	section's own path. The longest path wins, so a section under
		 *	"blog" and one under "blog/notes" can both exist
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$requestUri		The path that was asked for
		 *
		 *	@return 	array										[ section key, the rest ] - both '' for no section,
		 *																		the rest '' on a section's own index page
		 */
		public static function match( array &$appData, string $requestUri ): array {

			$path 	= trim( $requestUri, '/' );
			$found 	= [ '', '' ];

			foreach( self::sections( $appData ) as $key => $section ) {

				if( $path !== $section['path'] && str_starts_with( $path, $section['path']. '/' ) === false )
					continue;

				if( $found[0] !== '' && strlen( self::sections( $appData )[ $found[0] ]['path'] ) >= strlen( $section['path'] ) )
					continue;

				$found = [ $key, substr( $path, strlen( $section['path'] ) + 1 ) ];
			}

			return $found;
		}

		/**
		 *	The moment a post is judged against: the clock of the server, unless a test has held it. The one place the feature
		 *	asks the clock, so that a test sets ./posts/now (a unix time or an ISO moment, a key of the runtime that is never
		 *	stored) and every output of the request reads that moment
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function now( array &$appData ): int {

			$held = $appData['./posts/now'] ?? null;

			if( is_int( $held ) === true )
				return $held;

			$stamp = is_string( $held ) === true && $held !== '' ? strtotime( $held ) : false;

			return $stamp === false ? time() : $stamp;
		}

		/**
		 *	The post the current request is for, resolved once and remembered
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string								An element uri, or '' outside a post page
		 */
		public static function current( array &$appData ): string {

			return (string) ( $appData['./posts/current'] ?? '' );
		}

		/**
		 *	Resolve the slug the request carries, or answer the project's own
		 *	404. A post nobody has is not a blank page with a header on it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackPost( array &$appData, array &$request ): void {

			[ $key, $rest ] = self::match( $appData, (string) ( $request['/nino/http/request']['uri'] ?? '' ) );

			if( $key === '' )
				return;

			$section	= self::sections( $appData )[$key];

			if( preg_match( self::SLUG_PATTERN, $rest ) !== 1 || str_contains( $rest, '..' ) === true ) {
				self::_notFound( $appData, $request );
				return;
			}

			$uri 			= $section['type']. '/'. $rest;
			$element 	= \Nino\Elements::getElementWithFallback( $appData, $uri, '' );

			if( $element === false || self::published( $section, $element + [ '.uri' => $uri ], self::now( $appData ) ) === false ) {
				self::_notFound( $appData, $request );
				return;
			}

			$appData['./posts/current'] = $uri;

			/*	The page's own title and description, for this request only.
				getFills() merges the runtime fills over the text files, so this
				wins over [[/_nino/webpage/blog/post/title]] without the text key
				having to go away - which is what makes it a fallback for a post
				whose title field is empty rather than dead weight.

				Escaped by the rule of a field, and with the '[' swapped
				(Html::fieldValue()): a fill is put in as it is - into the <title>,
				the content of the description, og: and twitter: tags and the
				heading of the frame - so a '">' in the field closed the attribute
				and the rest was markup in the head of every visitor's page: a
				refresh to another site, and where the Consent feature is on a
				placeholder whose host joined the policy's script-src, which it
				reads off the finished body. The swapped '[' keeps the next
				rendering pass from running a shortcode or a fill an editor wrote
				there.

				The post is the one the language has, with what it lacks taken from
				another language (see \Nino\Elements::getElementWithFallback()): a title
				that no language has is a post with no title at all. Its page then says what
				the page of its section says, else the company's - never the key of a title
				nobody made, which the head printed as it stood. What the project wrote for
				the page of a post, the one text that is every post's, stays where a post
				has none of its own */
			$page 	= '/_nino/webpage'. $request['/nino/http/response']['uri'];
			$fills 	= [];

			foreach( [
				'title'				=> [ self::field( $section, $element, 'title' ), 'name', '/project/company/general/name' ],
				'description'	=> [ self::field( $section, $element, 'summary' ), 'description', '/project/company/general/description' ],
			] as $detail => [ $text, $standIn, $company ] ) {

				if( $text === '' && trim( \Nino\Html::renderTextfill( $appData, $page. '/'. $detail ) ) !== '' )
					continue;

				$fills[$page. '/'. $detail] = \Nino\Html::fieldValue( $text !== '' ? $text : self::_standIn( $appData, $key, $section, $standIn, $company ), [] );
			}

			\Nino\Html::addFills( $appData, $fills, '*' );
		}

		/**
		 *	The list page of a section: a page of the list that is none is a 404.
		 *
		 *	The number comes from the query of the address and is held to a
		 *	pattern before it is a number - digits, none of them a leading zero,
		 *	at most six - and then to the list: the first page is always there,
		 *	however empty, and a page behind the last one is none. A list that
		 *	a component draws on another page says nothing about it and draws
		 *	the nearest page there is (see _pageOf())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackIndex( array &$appData, array &$request ): void {

			[ $key, $rest ] = self::match( $appData, (string) ( $request['/nino/http/request']['uri'] ?? '' ) );

			if( $key === '' || $rest !== '' )
				return;

			self::_details( $appData, $key, '/_nino/webpage'. (string) ( $request['/nino/http/response']['uri'] ?? '' ) );

			$query = $request['/nino/http/request']['query'] ?? [];

			if( is_array( $query ) === false || array_key_exists( self::PAGE_KEY, $query ) === false )
				return;

			$asked = self::asked( $query );
			$pages = self::pages( $appData, self::sections( $appData )[$key] );

			if( $asked === 0 || $asked > max( 1, $pages ) )
				self::_notFound( $appData, $request );
		}

		/**
		 *	The title and the description of the list page of a section that has none written: the page of the default
		 *	section says them, and a section that is none of its own is named by its key. Without that the head of the page
		 *	printed the key of the text as it stands. What the project wrote for the page stays
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The section's key
		 *	@param		string		$page					'/_nino/webpage' and the uri of the page
		 *
		 *	@return 	void
		 */
		private static function _details( array &$appData, string $key, string $page ): void {

			$fills = [];

			foreach( [ 'title' => $key, 'description' => '' ] as $detail => $last ) {

				if( trim( \Nino\Html::renderTextfill( $appData, $page. '/'. $detail ) ) !== '' )
					continue;

				$text = trim( \Nino\Html::renderTextfill( $appData, '/_nino/webpage'. sprintf( self::INDEX_URI, Posts\Sections::DEFAULT_KEY ). '/'. $detail ) );
				$fills[$page. '/'. $detail] = \Nino\Html::fieldValue( $text !== '' ? $text : $last, [] );
			}

			if( $fills !== [] )
				\Nino\Html::addFills( $appData, $fills, '*' );
		}

		/**
		 *	What stands for the title or the description of a post that has none in any
		 *	language: the name or the description of the page of its section - the list
		 *	the post belongs to, under the uri its route has, which is the section's own
		 *	where the project made no route of its own for the path - in the language of
		 *	the request, with what the kernel falls back to for a language that has none,
		 *	and where there is none of that either the company's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The section's key
		 *	@param		array 		$section			A normalised section
		 *	@param		string		$of						'name' or 'description': what the page of the section says
		 *	@param		string		$company			The key of the company's own
		 *
		 *	@return 	string								Plain text, '' where the project says none of it
		 */
		private static function _standIn( array &$appData, string $key, array $section, string $of, string $company ): string {

			$route	= $appData['/nino/http/routes']['GET://'. $section['path']] ?? [];
			$uri		= is_array( $route ) === true && is_string( $route['uri'] ?? null ) === true && $route['uri'] !== '' ? $route['uri'] : sprintf( self::INDEX_URI, $key );

			foreach( [ '/_nino/webpage'. $uri. '/'. $of, $company ] as $fill ) {

				$text = trim( (string) \Nino\Html::resolveTextfill( $appData, $fill ) );

				if( $text !== '' )
					return $text;
			}

			return '';
		}

		/**
		 *	The Seo feature's page list, for the addresses it cannot find.
		 *
		 *	A section's routes are registered per request out of /data/posts.php,
		 *	so config.php - which is where sitemap.xml and llms.txt read the
		 *	site's pages from - has never heard of the index, and the posts are
		 *	one wildcard route standing for as many pages as there are records.
		 *	Neither was in either document; both are now.
		 *
		 *	Registered whether or not that feature is installed, and under the
		 *	name as a string: a callback nobody fires costs one array entry,
		 *	while naming \Nino\Modules\Seo::PAGES on a site without the Seo
		 *	feature is a fatal error.
		 *
		 *	Titles and dates travel with the entries because the page they
		 *	belong to has neither textfills of its own nor a template to take an
		 *	mtime off - the record is where both live. The index is dated by the
		 *	newest post it lists, which is when it last said anything different.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$pages				(reference) The list to append entries to
		 *
		 *	@return 	void
		 */
		public static function callbackSeoPages( array &$appData, array &$pages ): void {

			/*	The site's native locale, not the request's: a crawler asking for
				sitemap.xml brings whatever locale its headers won, while the list it
				is answered with is the site's own - and llms.txt groups a page with
				no locale of its own under exactly that one. */
			$locale	= \Nino\Locales::getNativeLocale( $appData );
			$dir		= \Nino\Filesystem::getDir( $appData );

			foreach( self::sections( $appData ) as $key => $section ) {

				$posts	= [];
				$newest	= null;

				foreach( self::posts( $appData, $section, $locale ) as $element ) {

					$elementUri = (string) ( $element['.uri'] ?? '' );

					if( $elementUri === '' )
						continue;

					$date		= self::_isoDate( self::field( $section, $element, 'date' ) );
					$newest	= $date !== null && ( $newest === null || $date > $newest ) ? $date : $newest;

					/*	Sections::url() is where a post's address is decided, and the
						project directory it puts in front is the one thing the Seo
						feature's own paths do not carry: its base url is the bare domain
						and the routes it lists are keyed without it. Cutting it off here
						keeps that one decision in one place rather than rebuilding the
						address beside it. */
					$posts[] = [
						'externalPath'	=> substr( Posts\Sections::url( $appData, $section, $elementUri ), strlen( $dir ) ),
						'lastmod'				=> $date,
						'title'					=> self::field( $section, $element, 'title' ),
						'description'		=> self::field( $section, $element, 'summary' ),
					];
				}

				// The index carries the uri its own route has, not its path: that is
				// what its title and description are filed under, and a section whose
				// path is not its key ('/news' for 'blog') has the two differ
				if( $section['index'] !== '' )
					$pages[] = [
						'externalPath'	=> '/'. $section['path'],
						'uri'						=> sprintf( self::INDEX_URI, $key ),
						'lastmod'				=> $newest,
					];

				array_push( $pages, ...$posts );
			}
		}

		/**
		 *	The Search feature's ranked list, without the posts nobody may see yet.
		 *
		 *	The index holds every element of a type, and a post dated for Monday is
		 *	one until Monday: whether it is published is a question for the clock at
		 *	the time of the search, so it is asked here, when the list is read, and
		 *	not when the index is written. A hit of a type no section is made of
		 *	passes as it is. A type two sections share is findable where at least one
		 *	of them publishes the post - each has its own date field - and a hit whose
		 *	element cannot be read is not a post anybody can open, so it goes.
		 *
		 *	Registered whether or not that feature is installed and under the name as
		 *	a string, as callbackSeoPages() is. The element is read as posts() reads
		 *	it, for the language of the request.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$hits				(reference) The ranked list: [ uri, type, score, coverage, fields ], …
		 *
		 *	@return 	mixed										The list without those hits - anything but a list as it came, for Search to refuse
		 */
		public static function callbackSearchHits( array &$appData, mixed &$hits ): mixed {

			if( is_array( $hits ) === false )
				return $hits;

			$ofType = [];

			foreach( self::sections( $appData ) as $section )
				$ofType[ $section['type'] ][] = $section;

			return array_values( array_filter( $hits, static function( mixed $hit ) use ( &$appData, $ofType ): bool {

				if( is_array( $hit ) === false || is_string( $hit['type'] ?? null ) === false || isset( $ofType[ $hit['type'] ] ) === false )
					return true;

				$element = is_string( $hit['uri'] ?? null ) === true ? \Nino\Elements::getElementWithFallback( $appData, $hit['uri'], '' ) : false;

				if( $element === false )
					return false;

				foreach( $ofType[ $hit['type'] ] as $section )
					if( self::published( $section, $element + [ '.uri' => $hit['uri'] ], self::now( $appData ) ) === true )
						return true;

				return false;
			} ) );
		}

		/**
		 *	A hit of the json endpoint of the Search feature, with the address of the post it is.
		 *
		 *	The address is made as a link on the page is: out of the section's path and the name of the
		 *	element, and never out of a field of it. A hit of a type no section is made of stays as it
		 *	is, and so does a hit of a post that is not published - the list it stands in has dropped
		 *	it already, and a callback that is asked all the same gives it no address
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$hit					[ uri, type, score, coverage, fields, element ]
		 *
		 *	@return 	mixed										The hit with 'url' beside its fields, or as it came
		 */
		public static function callbackSearchHit( array &$appData, mixed &$hit ): mixed {

			if( is_array( $hit ) === false || is_string( $hit['type'] ?? null ) === false || is_string( $hit['uri'] ?? null ) === false || is_array( $hit['element'] ?? null ) === false )
				return $hit;

			foreach( self::sections( $appData ) as $section )
				if( $section['type'] === $hit['type'] && self::published( $section, $hit['element'] + [ '.uri' => $hit['uri'] ], self::now( $appData ) ) === true ) {

					$hit['url'] = Posts\Sections::url( $appData, $section, $hit['uri'] );

					break;
				}

			return $hit;
		}

		/**
		 *	A post's own date field as a sitemap date. Whatever a project types
		 *	into it - published() reads the same field with the same strtotime(),
		 *	so a value that publishes a post is a value that can date it
		 *
		 *	@param		string		$value				The date field's content
		 *
		 *	@return 	string|null					'Y-m-d', or null where there is no date to state
		 */
		private static function _isoDate( string $value ): ?string {

			$stamp = trim( $value ) === '' ? false : strtotime( trim( $value ) );

			return $stamp === false ? null : date( 'Y-m-d', $stamp );
		}

		/**
		 *	Whether a post is one a visitor may see: it has a name a page can be at, and
		 *	it is not hidden. A section with no date field publishes everything it has;
		 *	one with a date publishes what is not in the future, so a post can be written
		 *	today and appear on Monday without anything having to run on Monday
		 *
		 *	@param		array 		$section			A normalised section
		 *	@param		array 		$element			An element, with its '.uri' where it has one
		 *	@param		int				$now					The moment to judge it at, 0 for the clock of the server
		 *
		 *	@return 	bool
		 */
		public static function published( array $section, array $element, int $now = 0 ): bool {

			// The name of the element is the slug of its page: one it cannot be opened by is no post
			if( is_string( $element['.uri'] ?? null ) === true ) {

				$slug = substr( strrchr( '/'. $element['.uri'], '/' ) ?: '', 1 );

				if( preg_match( self::SLUG_PATTERN, $slug ) !== 1 || str_contains( $slug, '..' ) === true )
					return false;
			}

			if( $section['date'] === '' )
				return true;

			// is_string(), not a cast: the field is whatever the element type
			// declares and whatever an editor, a feature or a hand-edited
			// file put there. An array cast to string raises "Array to string
			// conversion", a level \Nino\Runtime treats as fatal - one list
			// field named as the date field and every posts page is a 500.
			// A value that is not a string is no date, so the post is a draft
			$raw	= $element[ $section['date'] ] ?? '';
			$date	= is_string( $raw ) === true ? trim( $raw ) : '';

			// An empty date is a draft: the field exists, the post has not
			// been given one, and guessing "now" would publish it
			if( $date === '' )
				return false;

			$stamp = strtotime( $date );

			return $stamp !== false && $stamp <= ( $now === 0 ? time() : $now );
		}

		/**
		 *	One of a section's named fields, as a plain string
		 *
		 *	@param		array 		$section			A normalised section
		 *	@param		array 		$element			An element
		 *	@param		string		$which				'title', 'summary' or 'date'
		 *
		 *	@return 	string
		 */
		public static function field( array $section, array $element, string $which ): string {

			$name = (string) ( $section[$which] ?? '' );

			if( $name === '' || is_scalar( $element[$name] ?? null ) === false )
				return '';

			return trim( (string) $element[$name] );
		}

		/**
		 *	Every post of a section a visitor may see, in the section's order.
		 *
		 *	The list is the one the language's own version lists, and each post in it is
		 *	the post as \Nino\Elements::getElementWithFallback() answers it for the
		 *	language: what the language has no text for - none, empty, whitespace -
		 *	taken from the native language, then from the other languages of the site,
		 *	with '.lang' beside it, the tag of the language it was taken from, for a
		 *	block to say on what it shows (see _boxed()). A post that exists in German
		 *	only is a post in the English list, and says it is German. The order is the
		 *	order of what is drawn, as [collection] has it: a title taken from another
		 *	language stands where it sorts
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		string		$locale				'' for the request's own
		 *
		 *	@return 	array										Elements, each with its '.uri' and its '.lang'
		 */
		public static function posts( array &$appData, array $section, string $locale = '' ): array {

			$all = \Nino\Elements::queryElements( $appData, $section['type'], [], $locale, [] );

			if( is_array( $all ) === false )
				return [];

			$posts = [];
			$now = self::now( $appData );
			$next = null;

			foreach( $all as $element ) {

				if( is_array( $element ) === false )
					continue;

				$drawn = is_string( $element['.uri'] ?? null ) === true ? \Nino\Elements::getElementWithFallback( $appData, $element['.uri'], $locale ) : false;
				$post	 = $drawn !== false ? $drawn : $element;

				$named = $post + [ '.uri' => (string) ( $element['.uri'] ?? '' ) ];

				if( self::published( $section, $named, $now ) === true )
					$posts[] = $named;
				// A post that waits for its day: what a page that shows the list says stops being true on that day
				else if( self::published( $section, [ $section['date'] => date( 'Y-m-d', $now ) ] + $named, $now ) === true ) {

					$stamp = strtotime( self::field( $section, $named, 'date' ) );

					if( $stamp !== false && $stamp > $now && ( $next === null || $stamp < $next ) )
						$next = $stamp;
				}
			}

			/*	The page of the Cache module that holds the list is good until the next post comes due, and not to the end of
				its lifetime: a post dated for Monday is on the page on Monday. The moment is the stamp of that date on the
				clock of the cache, never time() itself, which would keep every page of the site out of it */
			if( $next !== null )
				\Nino\Modules\Cache::limit( $appData, $next );

			return \Nino\Elements::sortElements( $posts, (string) $section['sort'] );
		}

		/**
		 *	How many pages the list of a section has
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *
		 *	@return 	int											0 where there is no post
		 */
		public static function pages( array &$appData, array $section ): int {

			return (int) ceil( count( self::posts( $appData, $section ) ) / max( 1, $section['perPage'] ) );
		}

		/**
		 *	The page a query asks for
		 *
		 *	@param		array 		$query				The query of the request, as Http::request() parsed it
		 *
		 *	@return 	int											1 where it asks none, 0 where what it asks is no page number
		 */
		public static function asked( array $query ): int {

			if( array_key_exists( self::PAGE_KEY, $query ) === false )
				return 1;

			$value = $query[ self::PAGE_KEY ];

			return is_string( $value ) === true && preg_match( self::PAGE_PATTERN, $value ) === 1 ? (int) $value : 0;
		}

		/**
		 *	The page of the list the request is on, for a list that does not stand on the page of its section: a page that is
		 *	none is the first. The query is the one the kernel keeps of the request: a renderer is handed $appData and its call,
		 *	never the request itself
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int										1 or more
		 */
		public static function page( array &$appData ): int {

			$query = \Nino\Http::getRequest( $appData )['/nino/http/request']['query'] ?? [];

			return max( 1, self::asked( is_array( $query ) === true ? $query : [] ) );
		}

		/**
		 *	The posts of the page that is on, and none where the page is past the last one.
		 *
		 *	The page asked for is held to the one after the last - which is empty
		 *	already - before it becomes an offset: ( $page - 1 ) * $perPage of a page
		 *	as large as an integer goes is a float, and array_slice() answers a float
		 *	offset with a TypeError. What page() answers stays what was asked for,
		 *	because that is how the pager knows the list is past its end
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		array 		$posts				Every post of the section a visitor may see
		 *
		 *	@return 	array										The posts of that page, [] past the last one
		 */
		private static function _pageOf( array &$appData, array $section, array $posts ): array {

			$perPage	= max( 1, $section['perPage'] );
			$page			= min( self::page( $appData ), (int) ceil( count( $posts ) / $perPage ) + 1 );

			return array_slice( $posts, ( $page - 1 ) * $perPage, $perPage );
		}

		/**
		 *	The project's own 404, not a blank one of ours
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		private static function _notFound( array &$appData, array &$request ): void {

			$route = \Nino\Http::requestRoute( $appData, '/404', 'GET' ) ?? [ 'uri' => '/404', 'body' => '' ];

			$request['/nino/http/response'] = array_merge( $request['/nino/http/response'], $route, [ 'statusCode' => 404 ] );
		}

		/**
		 *	The post as a component in a loop reads it: the element of the language, and what the section computes for it.
		 *	A call names five fields of a post whatever the section calls them - .title, .summary, .date, .image and .body - and
		 *	.url, the address of its page. None of the six is read from a field that is not the section's: .url is made of the
		 *	section's path and the name of the element and of nothing a field holds, and .body is the section's body field read
		 *	by the format of blocks, so that the kernel's own sanitizer is the only way into the page. .image is the picture of
		 *	the section's image field with the alternative text of its alt field
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		array 		$model				The model of the section's type
		 *	@param		array 		$post					The post, as posts() lists it
		 *	@param		bool			$entry				The page of the post itself: .text is its body
		 *
		 *	@return 	array										[ the element, the model it is read by ]
		 */
		private static function _shaped( array &$appData, array $section, array $model, array $post, bool $entry ): array {

			$element = $post;

			foreach( [ 'title', 'summary', 'date', 'body', 'image' ] as $which ) {

				$field = (string) $section[$which];
				$value = $field !== '' && is_scalar( $post[$field] ?? null ) === true ? (string) $post[$field] : '';
				$declared = $field !== '' && is_array( $model[$field] ?? null ) === true ? $model[$field] : [ 'type' => 'string' ];

				if( $which === 'body' )
					$declared = [ 'type' => 'string', 'html' => true, 'blocks' => true ];
				else if( $which === 'image' )
					$declared = ( $declared['type'] ?? '' ) === 'image' && $value !== '' ? $declared + [ 'alt' => $section['alt'] ] : [ 'type' => 'string' ];

				$element[$which] = $value;
				$model[$which] = $declared;
			}

			$element['url'] = Posts\Sections::url( $appData, $section, (string) ( $post['.uri'] ?? '' ) );
			$model['url'] = [ 'type' => 'string' ];

			/*	The names of the kernel's example elements, which are the only fields a block may read: .text is the summary of a list and the
				body of a post, .link the address, so that a block written against /_placeholder draws the posts of the section. .price has none */
			$element['text'] = $element[ $entry === true ? 'body' : 'summary' ];
			$model['text'] = $model[ $entry === true ? 'body' : 'summary' ];
			$element['link'] = $element['url'];
			$model['link'] = $model['url'];
			$element['price'] = '';
			$model['price'] = [ 'type' => 'string' ];

			return [ $element, $model ];
		}

		/**
		 *	The nodes of a loop without the ids of what they hold: an id would stand once for each post in the page, and a
		 *	~name of it reads the section outside the loop. Which attribute is an id is the register's to say
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$nodes
		 *
		 *	@return 	array
		 */
		private static function _unid( array &$appData, array $nodes ): array {

			$ids = [];

			foreach( \Nino\Components::components( $appData ) as $name => $schema )
				foreach( $schema['attributes'] as $attribute => $declared )
					if( $declared['type'] === 'id' )
						$ids[$name][] = $attribute;

			$strip = static function( array $nodes ) use ( &$strip, $ids ): array {

				foreach( $nodes as $at => $node ) {

					if( is_array( $node ) === false || isset( $node['n'] ) === false )
						continue;

					foreach( (array) ( $ids[(string) $node['n']] ?? [] ) as $attribute )
						unset( $nodes[$at]['a'][$attribute] );

					if( is_array( $node['c'] ?? null ) === true )
						$nodes[$at]['c'] = $strip( $node['c'] );
				}

				return $nodes;
			};

			return $strip( $nodes );
		}

		/**
		 *	The children of a loop drawn once for each post, one after the other where the call stands. Nothing is drawn
		 *	round them, so that a column stands in the row it was written in; the box of a post that is in a language other
		 *	than the page's is taken out of the layout by the part
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		array 		$posts				The posts to draw
		 *	@param		array			$context			What the renderer was handed
		 *	@param		bool			$entry				The one post of its own page
		 *
		 *	@return 	string
		 */
		private static function _loop( array &$appData, array $section, array $posts, array $context, bool $entry = false ): string {

			$nodes = self::_unid( $appData, (array) $context['children'] );
			$model = \Nino\Elements::getElementModel( $appData, $section['type'] );
			$fills = is_array( $context['fills'] ?? null ) === true ? $context['fills'] : [];
			$round = $context;
			$html = '';

			$appData[self::DRAWING] = true;

			try {

				foreach( array_values( $posts ) as $index => $post ) {

					[ $element, $shape ] = self::_shaped( $appData, $section, $model, $post, $entry );

					$round['element'] = [ 'uri' => $section['type'], 'id' => $index, 'element' => $element, 'model' => $shape ];
					$round['fills'] = array_map( 'strval', \Nino\Modules\Elements::fills( $element, $shape, $index ) ) + $fills;

					$html .= self::_boxed( $appData, $post, \Nino\Components::render( $appData, $nodes, $round ) );
				}
			}
			finally {
				unset( $appData[self::DRAWING] );
			}

			return $html;
		}

		/**
		 *	What is drawn for a post, in a box that names the language the post's text was taken from, where that is not the
		 *	page's: a screen reader reads a German text with a German voice on an English page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$post					The post, with its '.lang'
		 *	@param		string		$drawn				What was drawn for it
		 *
		 *	@return 	string
		 */
		private static function _boxed( array &$appData, array $post, string $drawn ): string {

			$lang = is_string( $post['.lang'] ?? null ) === true ? $post['.lang'] : '';

			if( $lang === '' )
				return $drawn;

			return strtr( \Nino\Feature::template( $appData, self::TEMPLATES, 'post-box' ), [
				'[[lang]]'		=> \Nino\Components::escape( $lang ),
				'[[content]]'	=> $drawn,
			] );
		}

		/**
		 *	Replace posts: its children once for each post of the page that is on - the posts of one page of the list, or
		 *	where the call names a limit the newest posts without paging, a teaser. Nothing is drawn for a section that is
		 *	none, a list that has no post on the page, or a list inside a list
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentPosts( array &$appData, array $args, string $content, array $context ): string {

			$section = self::section( $appData, $args['section'] );

			if( $section === [] || isset( $appData[self::DRAWING] ) === true )
				return '';

			$posts = self::posts( $appData, $section );
			$limit = (int) $args['limit'];
			$posts = $limit > 0 ? array_slice( $posts, 0, $limit ) : self::_pageOf( $appData, $section, $posts );

			return $posts === [] ? '' : self::_loop( $appData, $section, $posts, $context );
		}

		/**
		 *	Replace posts-entry: its children once, for the post the address is for. It is the one post the route of a
		 *	section resolved - one that is not published, or has no page, was a 404 before this was reached - and it is read
		 *	and judged again here, so that a page that is none of the route's draws nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentPostsEntry( array &$appData, array $args, string $content, array $context ): string {

			$uri = self::current( $appData );
			$section = self::section( $appData );

			if( $uri === '' || $section === [] || isset( $appData[self::DRAWING] ) === true )
				return '';

			$post = \Nino\Elements::getElementWithFallback( $appData, $uri, '' );

			if( $post === false || self::published( $section, $post + [ '.uri' => $uri ], self::now( $appData ) ) === false )
				return '';

			return self::_loop( $appData, $section, [ $post + [ '.uri' => $uri ] ], $context, true );
		}

		/**
		 *	A word of the pager or of the navigation: the attribute where the call has one, else the word of the site
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$attribute		prev, next or label
		 *	@param		string		$key					The key of the site's word
		 *
		 *	@return 	string										Escaped, finished
		 */
		private static function _word( array &$appData, array $args, string $attribute, string $key ): string {

			if( $args[$attribute] !== '' )
				return $args[$attribute];

			return \Nino\Components::escape( html_entity_decode( (string) ( \Nino\Html::getFills( $appData )['[['. $key. ']]'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		}

		/**
		 *	The address of a page of a list: the section's path, and the page in the query. Page one is the section without a
		 *	query, so the first page of a blog has one address rather than two
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		int				$page
		 *
		 *	@return 	string										Escaped
		 */
		private static function _href( array &$appData, array $section, int $page ): string {

			// On the page of its own section the list is at the section's path; on any other page the list reads the page of that page
			// and the pager leads there
			$path = $section['path'];
			$asked = trim( (string) ( \Nino\Http::getRequest( $appData )['/nino/http/request']['uri'] ?? '' ), '/' );

			if( ( self::sections( $appData )[ self::currentKey( $appData ) ] ?? null ) !== $section && preg_match( '#^(?:[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*)?$#D', $asked ) === 1 && str_contains( $asked, '..' ) === false )
				$path = $asked;

			$base = \Nino\Filesystem::getDir( $appData ). '/'. $path;

			return \Nino\Components::escape( $page <= 1 ? $base : $base. '?'. self::PAGE_KEY. '='. $page );
		}

		/**
		 *	Replace posts-pager: where the list is, and the way on.
		 *
		 *	Plain links with the page in the query, so the pager works without javascript and a page of a blog is a url
		 *	somebody can send. One page is not a pager: saying "1 of 1" is telling somebody there is more where there is not.
		 *	A page number past the end is a page the list has nothing to put on, and nothing is marked as the one that is on,
		 *	because nothing is where they are; the way back is the last page that has posts on it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in
		 *
		 *	@return 	string
		 */
		public static function componentPostsPager( array &$appData, array $args, string $content, array $context ): string {

			$section = self::section( $appData, $args['section'] );

			if( $section === [] )
				return '';

			$pages = self::pages( $appData, $section );
			$page = self::page( $appData );

			if( $pages < 2 )
				return '';

			$beyond = $page > $pages;
			$item = \Nino\Feature::template( $appData, self::TEMPLATES, 'pager-item' );
			$items = '';

			if( $page > 1 )
				$items .= strtr( $item, [ '[[href]]' => self::_href( $appData, $section, $beyond === true ? $pages : $page - 1 ), '[[attributes]]' => ' rel="prev"', '[[label]]' => self::_word( $appData, $args, 'prev', '/feature/posts/pager/prev' ) ] );

			for( $number = 1; $number <= $pages; $number++ )
				$items .= strtr( \Nino\Feature::template( $appData, self::TEMPLATES, $number === $page ? 'pager-item-current' : 'pager-item' ), [ '[[href]]' => self::_href( $appData, $section, $number ), '[[attributes]]' => '', '[[label]]' => (string) $number ] );

			if( $page < $pages )
				$items .= strtr( $item, [ '[[href]]' => self::_href( $appData, $section, $page + 1 ), '[[attributes]]' => ' rel="next"', '[[label]]' => self::_word( $appData, $args, 'next', '/feature/posts/pager/next' ) ] );

			return \Nino\Components::fill( $appData, 'posts-pager', $args, $content, [
				'[[label]]'	=> self::_word( $appData, $args, 'label', '/feature/posts/pager/label' ),
				'[[items]]'	=> $items,
			] );
		}

		/**
		 *	Replace posts-nav: the post before and the post after the one the address is for, in the section's own order.
		 *	A title no language has is the name of the post, so that a link has words
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in
		 *
		 *	@return 	string
		 */
		public static function componentPostsNav( array &$appData, array $args, string $content, array $context ): string {

			$uri = self::current( $appData );
			$section = self::section( $appData, $args['section'] );

			if( $uri === '' || $section === [] )
				return '';

			$posts = self::posts( $appData, $section );
			$at = null;

			foreach( $posts as $index => $post )
				if( (string) ( $post['.uri'] ?? '' ) === $uri ) {
					$at = $index;
					break;
				}

			if( $at === null )
				return '';

			$item = \Nino\Feature::template( $appData, self::TEMPLATES, 'nav-item' );
			$items = '';

			foreach( [ 'prev' => $at - 1, 'next' => $at + 1 ] as $rel => $index ) {

				if( isset( $posts[$index] ) === false )
					continue;

				$title = self::field( $section, $posts[$index], 'title' );
				$lang = is_string( $posts[$index]['.lang'] ?? null ) === true ? $posts[$index]['.lang'] : '';

				$items .= strtr( $item, [
					'[[rel]]'		=> $rel,
					'[[href]]'	=> \Nino\Components::escape( Posts\Sections::url( $appData, $section, (string) $posts[$index]['.uri'] ) ),
					'[[lang]]'	=> $lang === '' ? '' : ' lang="'. \Nino\Components::escape( $lang ). '"',
					'[[word]]'	=> self::_word( $appData, $args, $rel, '/feature/posts/navigation/'. $rel ),
					'[[title]]'	=> \Nino\Components::escape( $title !== '' ? $title : \Nino\Modules\Elements::slug( $posts[$index] ) ),
				] );
			}

			return $items === '' ? '' : \Nino\Components::fill( $appData, 'posts-nav', $args, $content, [
				'[[label]]'	=> self::_word( $appData, $args, 'label', '/feature/posts/navigation/label' ),
				'[[items]]'	=> $items,
			] );
		}
	}
}
