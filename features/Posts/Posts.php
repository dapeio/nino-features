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
	 *										[elements] lists them, Search indexes them and a backup
	 *										carries them. What it has no answer for is the other
	 *										half of a blog: every record wants a page of its own at
	 *										a readable url, the list wants to be more than one page
	 *										long, and a title wants to be the post's rather than
	 *										the route's.
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
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Posts {

		public const string KEY = 'posts';

		/*	The internal uri of a section's two routes - the identity its text
			keys hang off, and what the response callback is registered on.
			Separate identities on purpose: a list page and a post page want
			different titles, and [[/webpage/blog/title]] can only be one thing */
		public const string INDEX_URI = '/%s';
		public const string POST_URI 	= '/%s/post';

		// Which page of the list is wanted. A get parameter rather than a path
		// segment: /blog/2 is a post called "2", and a section must not have to
		// reserve slugs that look like numbers
		public const string PAGE_KEY = 'page';

		/**
		 *	Register a section's routes and the callback that resolves a post.
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
		public static function init( array &$appData ): void {

			Posts\Shortcodes::init( $appData );
			self::routes( $appData );
		}

		/**
		 *	The routes alone - see init(), which is where the kernel calls this
		 *	from. Its own method because a shortcode may only be registered
		 *	once (callbacks accumulate, see \Nino\Callbacks::registerCallback())
		 *	while the routes are a pure function of the sections, and a test
		 *	that changes one wants them again
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

					$appData['/nino/http/routes']['GET://'. $section['path']] = array_merge( $existing, [
						'uri' 	=> (string) ( $existing['uri'] ?? sprintf( self::INDEX_URI, $key ) ),
						'body' 	=> '[template '. $section['index']. ']',
					] );
				}

				$appData['/nino/http/routes']['GET://'. $section['path']. '/*'] = [
					'uri' 	=> $postUri,
					'body' 	=> '[template '. $section['post']. ']',
				];

				\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET:/'. $postUri, [ self::class, 'callbackPost' ] );
			}
		}

		/**
		 *	The sections, read once per request. Every shortcode and the
		 *	route callback want them, and a file read per shortcode on a list
		 *	page is a file read per post
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
			// [posts] on a blog page should not have to repeat it
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
				feature's shortcodes find it: a shortcode never sees $request.
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
			$slug 		= self::_slug( $rest );

			if( $slug === '' ) {
				self::_notFound( $appData, $request );
				return;
			}

			$uri 			= $section['type']. '/'. $slug;
			$element 	= \Nino\Elements::getElement( $appData, $uri );

			if( is_array( $element ) === false || self::published( $section, $element ) === false ) {
				self::_notFound( $appData, $request );
				return;
			}

			$appData['./posts/current'] = $uri;

			/*	The page's own title and description, for this request only.
				getFills() merges the runtime fills over the text files, so this
				wins over [[/webpage/blog/post/title]] without the text key
				having to go away - which is what makes it a fallback for a post
				whose title field is empty rather than dead weight */
			$fills 	= [];
			$title 	= self::field( $section, $element, 'title' );
			$summary= self::field( $section, $element, 'summary' );

			if( $title !== '' )
				$fills['/webpage'. $request['/nino/http/response']['uri']. '/title'] = $title;

			if( $summary !== '' )
				$fills['/webpage'. $request['/nino/http/response']['uri']. '/description'] = $summary;

			if( $fills !== [] )
				\Nino\Html::addFills( $appData, $fills, '*' );
		}

		/**
		 *	Whether a post is one a visitor may see. A section with no date
		 *	field publishes everything it has; one with a date publishes what
		 *	is not in the future, so a post can be written today and appear on
		 *	Monday without anything having to run on Monday
		 *
		 *	@param		array 		$section			A normalised section
		 *	@param		array 		$element			An element
		 *
		 *	@return 	bool
		 */
		public static function published( array $section, array $element ): bool {

			if( $section['date'] === '' )
				return true;

			$date = trim( (string) ( $element[ $section['date'] ] ?? '' ) );

			// An empty date is a draft: the field exists, the post has not
			// been given one, and guessing "now" would publish it
			if( $date === '' )
				return false;

			$stamp = strtotime( $date );

			return $stamp !== false && $stamp <= time();
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
		 *	Every post of a section a visitor may see, in the section's order
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		string		$locale				'' for the request's own
		 *
		 *	@return 	array										Elements, each with its '.uri'
		 */
		public static function posts( array &$appData, array $section, string $locale = '' ): array {

			$all = \Nino\Elements::queryElements( $appData, $section['type'], [], $locale, [], [ 'sort' => $section['sort'] ] );

			if( is_array( $all ) === false )
				return [];

			return array_values( array_filter( $all, static fn( mixed $element ): bool =>
				is_array( $element ) === true && self::published( $section, $element ) ) );
		}

		/**
		 *	The slug a request to a section's wildcard route carries
		 *
		 *	@param		string		$rest					What the path says after the section's
		 *
		 *	@return 	string								'' where it is not one
		 */
		private static function _slug( string $rest ): string {

			/*	One segment, and the alphabet an element uri is written in
				(see Elements::insertElement()). A slug that could climb out of
				the type - or name a second type - never becomes a path */
			return preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $rest ) === 1 && str_contains( $rest, '..' ) === false
				? $rest
				: '';
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
	}
}
