<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Posts\Sections		see features/Posts/Posts.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Posts {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Sections					What this feature knows: which element type is a section
	 *										of the site, under which path, through which templates.
	 *
	 *										A section is a handful of strings, and every one of them
	 *										ends up in a route, a file path or an element query - so
	 *										none of them is taken as written. normalize() is the one
	 *										gate: it runs on what is read from disk and on what a
	 *										request posts, and what it cannot honour it says in
	 *										`notes` rather than passing on.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Sections {

		// Declared under `data` in feature.php, so a backup carries it and
		// removing the feature leaves it where it is
		public const string PATH = '/data/posts.php';

		public const int FORMAT = 1;

		// A section key, an element type, a path segment: three different
		// alphabets, and each one of them is joined to something later
		public const string KEY_PATTERN 	= '/^[a-z][a-z0-9-]*$/';
		public const string TYPE_PATTERN	= '#^/[a-z][a-z0-9-]*(?:/[a-z][a-z0-9-]*)*$#';
		public const string PATH_PATTERN	= '#^[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*$#';
		// A template is a project-relative path without its extension, which
		// is what a [template] shortcode takes
		public const string TEMPLATE_PATTERN = '#^/[A-Za-z0-9][A-Za-z0-9._/-]*$#';

		public const int MIN_PER_PAGE = 1;
		public const int MAX_PER_PAGE = 100;

		// The section a project has before it has said anything - see read()
		public const string DEFAULT_KEY = 'blog';

		/*	What the install unit's section is, and what every field falls back
			to. 'index' is empty on purpose in no default - a section without an
			index route is a legitimate shape (the project's own page carries
			[posts]), so the value has to be able to be nothing */
		public const array DEFAULTS = [
			'type' 		=> '/posts',
			'path' 		=> 'blog',
			'index' 	=> '/templates/page-posts',
			'post' 		=> '/templates/page-post',
			'sort' 		=> '-date',
			'date' 		=> 'date',
			'title' 	=> 'title',
			'summary'	=> 'summary',
			'image' 	=> 'image',
			'alt' 		=> 'imageAlt',
			'body' 		=> 'body',
			'perPage'	=> 10,
		];

		/**
		 *	One section, with everything that cannot be honoured said out loud
		 *
		 *	@param		string		$key					The section's own key
		 *	@param		array 		$raw					What was read or posted
		 *	@param		array 		&$notes				(reference) What was not taken as written
		 *
		 *	@return 	array										A section, every field present
		 */
		public static function normalizeSection( string $key, array $raw, array &$notes = [] ): array {

			$section = self::DEFAULTS;

			// The element type. Everything else about a section is decoration
			// beside this: without a type there is nothing to publish
			$given = (string) ( $raw['type'] ?? '' );
			$type 	= '/'. trim( $given, '/' );

			if( preg_match( self::TYPE_PATTERN, $type ) === 1 )
				$section['type'] = $type;
			// A field that was not given falls back quietly - a field that was
			// given and cannot be honoured is worth a sentence
			else if( $given !== '' )
				$notes[] = 'section "'. $key. '": "'. $given. '" is not an element type - using '. $section['type'];

			$given = (string) ( $raw['path'] ?? '' );
			$path 	= trim( $given, '/' );

			if( preg_match( self::PATH_PATTERN, $path ) === 1 && str_contains( $path, '..' ) === false )
				$section['path'] = $path;
			else if( $given !== '' )
				$notes[] = 'section "'. $key. '": "'. $given. '" is not a path - using '. $section['path'];

			/*	The two templates. An index of '' is a section with no index
				route of its own, which is the shape a project takes when its
				blog page is an ordinary page carrying [posts] - the post pages
				are what it wanted this feature for */
			foreach( [ 'index', 'post' ] as $which ) {

				$template = (string) ( $raw[$which] ?? self::DEFAULTS[$which] );

				if( $template === '' && $which === 'index' ) {
					$section['index'] = '';
					continue;
				}

				if( preg_match( self::TEMPLATE_PATTERN, $template ) === 1 && str_contains( $template, '..' ) === false )
					$section[$which] = $template;
				else
					$notes[] = 'section "'. $key. '": "'. $template. '" is not a template - using '. $section[$which];
			}

			// The field names, each of them a key of the type's model. Empty is
			// a real answer everywhere: a section whose posts are not dated,
			// or carry no picture
			foreach( [ 'sort', 'date', 'title', 'summary', 'image', 'alt', 'body' ] as $which )
				$section[$which] = self::_fieldList( (string) ( $raw[$which] ?? self::DEFAULTS[$which] ), $which === 'sort' );

			$perPage = (int) ( $raw['perPage'] ?? self::DEFAULTS['perPage'] );
			$section['perPage'] = max( self::MIN_PER_PAGE, min( self::MAX_PER_PAGE, $perPage ) );

			return $section;
		}

		/**
		 *	Every section, normalised, keyed by a key that is really a key
		 *
		 *	@param		array 		$raw					What was read or posted
		 *	@param		array 		&$notes				(reference) What was not taken as written
		 *
		 *	@return 	array										[ key => section ]
		 */
		public static function normalize( array $raw, array &$notes = [] ): array {

			$sections = [];
			$paths 		= [];

			foreach( (array) ( $raw['sections'] ?? [] ) as $key => $section ) {

				$key = (string) $key;

				if( preg_match( self::KEY_PATTERN, $key ) !== 1 ) {
					$notes[] = '"'. $key. '" is not a section key - a slug such as blog';
					continue;
				}

				if( is_array( $section ) === false ) {
					$notes[] = 'section "'. $key. '" is not a section';
					continue;
				}

				$section = self::normalizeSection( $key, $section, $notes );

				/*	Two sections under one path would register the same two
					routes, and the second would win silently - which reads as
					"the first section stopped working" and points nowhere near
					the cause */
				if( isset( $paths[ $section['path'] ] ) === true ) {
					$notes[] = 'section "'. $key. '" wants the path "'. $section['path']. '", which section "'. $paths[ $section['path'] ]. '" already has - skipped';
					continue;
				}

				$paths[ $section['path'] ] = $key;
				$sections[$key] = $section;
			}

			return $sections;
		}

		/**
		 *	The stored sections
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$notes				(reference) What was not taken as written
		 *
		 *	@return 	array										[ key => section ]
		 */
		public static function read( array &$appData, array &$notes = [] ): array {

			/*	No file is not no sections. A feature has no install hook -
				activating one applies its unit and nothing else - so a section
				that had to be written somewhere first would mean switching this
				on and then being told to configure it before anything happens.
				The unit delivers the element type and the two templates the
				defaults point at; this is what joins them up.

				An empty list in a file that exists is a real answer, and stays
				one: that is what removing the last section looks like */
			if( \Nino\Filesystem::fileExists( $appData, self::PATH ) === false )
				return [ self::DEFAULT_KEY => self::normalizeSection( self::DEFAULT_KEY, [] ) ];

			$stored = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );

			return self::normalize( is_array( $stored ) === true ? $stored : [], $notes );
		}

		/**
		 *	Store them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$sections			Normalised sections
		 *
		 *	@return 	bool
		 */
		public static function write( array &$appData, array $sections ): bool {

			return \Nino\Filesystem::putFileContent( $appData, self::PATH, [
				'format' 		=> self::FORMAT,
				'sections'	=> $sections,
			] );
		}

		/**
		 *	The public url of one post - the one thing a list of elements
		 *	cannot work out for itself, because the element knows its uri and
		 *	the section knows the path
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$section			A normalised section
		 *	@param		string		$elementUri		'/posts/my-first-post'
		 *
		 *	@return 	string								'/blog/my-first-post', with the project's own dir
		 */
		public static function url( array &$appData, array $section, string $elementUri ): string {

			$slug = \Nino\Elements::getElementUriFromUri( $elementUri );

			return \Nino\Filesystem::getDir( $appData ). '/'. $section['path']. '/'. ltrim( $slug, '/' );
		}

		/**
		 *	A comma-separated list of field names, or one of them - the shape
		 *	`sort` takes (where a leading '-' reverses) and the shape a single
		 *	field name takes (where it does not)
		 *
		 *	@param		string		$value
		 *	@param		bool			$sortable			Allow the leading '-' and several fields
		 *
		 *	@return 	string								'' where nothing survived
		 */
		private static function _fieldList( string $value, bool $sortable ): string {

			$out = [];

			foreach( explode( ',', $value ) as $field ) {

				$field = trim( $field );
				$desc 	= $sortable === true && str_starts_with( $field, '-' );

				if( $desc === true )
					$field = substr( $field, 1 );

				if( preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $field ) !== 1 )
					continue;

				$out[] = ( $desc === true ? '-' : '' ). $field;

				if( $sortable === false )
					break;
			}

			return implode( ',', $out );
		}
	}

}
