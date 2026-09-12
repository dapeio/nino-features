<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Posts\Shortcodes		see features/Posts/Posts.php for the feature's
 *													own docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Posts {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Shortcodes				What a project's own templates say.
	 *
	 *										Four, and each of them is a thing [elements] cannot do:
	 *										a list that knows which page of itself it is on, the one
	 *										post the current url is for, the pager under the list,
	 *										and the way from one post to the next.
	 *
	 *										The field vocabulary is Elements' own - [[title]] inside
	 *										the block, escaped the same way, so a template written
	 *										against [elements] reads here too. What is added to it
	 *										is [[.url]], because that is the one value neither the
	 *										element nor the type knows: it takes the section's path
	 *										to make one.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Shortcodes {

		/*	The class every paragraph of a body carries: the framework's own
			body copy, which is what a post's text is. Nino resets a bare <p> to
			no margin, so paragraphs without it read as one block - and a set of
			the Design feature that styles a section's text styles a post with
			it, which is the point of using the framework's name rather than one
			of our own */
		public const string BODY_CLASS = 'nino-section-text';

		public static function init( array &$appData ): void {
			\Nino\Html::addShortcode( $appData, 'posts', 			[ self::class, 'doPosts' ] );
			\Nino\Html::addShortcode( $appData, 'post', 			[ self::class, 'doPost' ] );
			\Nino\Html::addShortcode( $appData, 'posts-pager',[ self::class, 'doPager' ] );
			\Nino\Html::addShortcode( $appData, 'post-nav', 	[ self::class, 'doNav' ] );
		}

		/**
		 *	[posts]…[/posts] - one block per post of the page that is on.
		 *
		 *	A `limit` of its own turns the paging off: a template that wants
		 *	the three newest posts on the front page is asking for a teaser,
		 *	not for page one of something
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string											Rendered html
		 */
		public static function doPosts( array &$appData, array $args ): string {

			$section = \Nino\Modules\Posts::section( $appData, (string) ( $args['section'] ?? '' ) );
			$content = (string) ( $args['content'] ?? '' );

			if( $section === [] || $content === '' )
				return '';

			$posts = \Nino\Modules\Posts::posts( $appData, $section, (string) ( $args['locale'] ?? '' ) );
			$limit = (int) ( $args['limit'] ?? 0 );

			if( $limit > 0 )
				$posts = array_slice( $posts, (int) ( $args['offset'] ?? 0 ), $limit );
			else
				$posts = array_slice( $posts, ( self::page() - 1 ) * $section['perPage'], $section['perPage'] );

			$html = '';
			$id 	= 0;

			foreach( $posts as $post )
				$html .= self::_block( $appData, $section, $post, $content, $id++ );

			return $html;
		}

		/**
		 *	[post]…[/post] - the post this page is, and nothing anywhere else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string											Rendered html
		 */
		public static function doPost( array &$appData, array $args ): string {

			$content	= (string) ( $args['content'] ?? '' );
			$uri			= \Nino\Modules\Posts::current( $appData );

			if( $content === '' || $uri === '' )
				return '';

			$section	= \Nino\Modules\Posts::section( $appData );
			$element	= \Nino\Elements::getElement( $appData, $uri, (string) ( $args['locale'] ?? '' ) );

			if( $section === [] || is_array( $element ) === false )
				return '';

			return self::_block( $appData, $section, $element, $content, 0 );
		}

		/**
		 *	[posts-pager] - where the list is, and the way on.
		 *
		 *	Plain links with the page in the query, so the pager works without
		 *	javascript and a page of a blog is a url somebody can send. The
		 *	class names are the framework's own pagination ones, and the labels
		 *	come from the project's texts - a pager that says "Next" in a
		 *	German site is a feature nobody finishes installing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string											Rendered html
		 */
		public static function doPager( array &$appData, array $args ): string {

			$section = \Nino\Modules\Posts::section( $appData, (string) ( $args['section'] ?? '' ) );

			if( $section === [] )
				return '';

			$total = count( \Nino\Modules\Posts::posts( $appData, $section ) );
			$pages = (int) ceil( $total / max( 1, $section['perPage'] ) );
			$page 	= min( self::page(), max( 1, $pages ) );

			// One page is not a pager. Saying "1 of 1" is telling somebody
			// there is more where there is not
			if( $pages < 2 )
				return '';

			/*	The markup the framework's own .nino-pagination is written
				against: a <ul>, one <li> per page, the current one carrying
				.nino-is-active - which styles `li.nino-is-active a`, so the
				page that is on is a link to itself rather than a <span>. It
				gets aria-current instead, which is what says "this one" to
				somebody who cannot see the highlight */
			$base = \Nino\Filesystem::getDir( $appData ). '/'. $section['path'];
			$html = '<nav aria-label="'. self::_text( $appData, $args, 'label', 'Pages', 'Seiten' ). '"><ul class="nino-pagination">';

			if( $page > 1 )
				$html .= '<li><a href="'. self::_href( $base, $page - 1 ). '" rel="prev">'. self::_text( $appData, $args, 'prev', 'Newer', 'Neuer' ). '</a></li>';

			for( $number = 1; $number <= $pages; $number++ )
				$html .= $number === $page
					? '<li class="nino-is-active"><a href="'. self::_href( $base, $number ). '" aria-current="page">'. $number. '</a></li>'
					: '<li><a href="'. self::_href( $base, $number ). '">'. $number. '</a></li>';

			if( $page < $pages )
				$html .= '<li><a href="'. self::_href( $base, $page + 1 ). '" rel="next">'. self::_text( $appData, $args, 'next', 'Older', 'Älter' ). '</a></li>';

			return $html. '</ul></nav>';
		}

		/**
		 *	[post-nav]…[/post-nav] - the post before and the post after this
		 *	one, in the section's own order.
		 *
		 *	Its block is rendered twice at most, with [[.rel]] saying which of
		 *	the two it is - so one piece of markup serves both and a template
		 *	decides what "before" looks like
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string											Rendered html
		 */
		public static function doNav( array &$appData, array $args ): string {

			$content	= (string) ( $args['content'] ?? '' );
			$uri			= \Nino\Modules\Posts::current( $appData );
			$section	= \Nino\Modules\Posts::section( $appData, (string) ( $args['section'] ?? '' ) );

			if( $content === '' || $uri === '' || $section === [] )
				return '';

			$posts	= \Nino\Modules\Posts::posts( $appData, $section );
			$at			= null;

			foreach( $posts as $index => $post )
				if( (string) ( $post['.uri'] ?? '' ) === $uri ) {
					$at = $index;
					break;
				}

			if( $at === null )
				return '';

			$html = '';

			foreach( [ 'prev' => $at - 1, 'next' => $at + 1 ] as $rel => $index )
				if( isset( $posts[$index] ) === true )
					$html .= str_replace( '[[.rel]]', $rel, self::_block( $appData, $section, $posts[$index], $content, $index ) );

			return $html;
		}

		/**
		 *	The page of a list that is being asked for.
		 *
		 *	Read from $_GET, the same reasoning the Search feature's shortcodes
		 *	give: a shortcode callback is handed $appData and its own arguments,
		 *	never the request, and $_GET is php's parse of the same query string
		 *	Http::request() parses for itself
		 *
		 *	@return 	int										1 or more
		 */
		public static function page(): int {

			$value = $_GET[ \Nino\Modules\Posts::PAGE_KEY ] ?? 1;

			return is_scalar( $value ) === true ? max( 1, (int) $value ) : 1;
		}

		/**
		 *	One post through a block, with Elements' own field vocabulary and
		 *	the two values a section adds to it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$section			A normalised section
		 *	@param		array			$element			An element
		 *	@param		string		$content			The block
		 *	@param		int				$id						Its number in the list
		 *
		 *	@return 	string											Rendered html
		 */
		private static function _block( array &$appData, array $section, array $element, string $content, int $id ): string {

			$model 	= \Nino\Elements::getElementModel( $appData, $section['type'] );
			$search = [ '[[.id]]', '[[.url]]', '[[.image]]', '[[.body]]' ];
			$replace= [
				(string) $id,
				htmlspecialchars( \Nino\Modules\Posts\Sections::url( $appData, $section, (string) ( $element['.uri'] ?? '' ) ), ENT_QUOTES, 'UTF-8' ),
				self::_image( $appData, $section, $model, $element ),
				self::_body( (string) ( $element[ $section['body'] ] ?? '' ) ),
			];

			foreach( $element as $key => $value ) {

				if( is_scalar( $value ) === false )
					continue;

				$search[] 	= '[['. $key. ']]';
				$replace[] 	= self::_value( (string) $value, ( $model[$key]['html'] ?? false ) === true );
			}

			return str_replace( $search, $replace, $content );
		}

		/**
		 *	The post's text, in paragraphs.
		 *
		 *	Nino's own html fields are deliberately flat - strong, em, span,
		 *	code and a, and nothing that makes a block (see
		 *	\Nino\Html::sanitizeHtml() and the editor beside it). That is the
		 *	right decision for a field and the wrong shape for an article, so
		 *	this adds the one thing missing and nothing else: a blank line
		 *	starts a paragraph, a single one is a break, and every paragraph
		 *	goes through the kernel's own sanitiser exactly as it is.
		 *
		 *	No tag is allowed here that a field could not carry anyway - the
		 *	<p> and the <br> are this feature's own markup, written around
		 *	text the kernel has already cleaned. A body wanting headings,
		 *	lists and pictures is a page rather than a field, and a page is
		 *	what the post's template is for
		 *
		 *	@param		string		$value				The body field, as stored
		 *
		 *	@return 	string											Paragraphs, or ''
		 */
		private static function _body( string $value ): string {

			$value 	= str_replace( [ "\r\n", "\r" ], "\n", trim( $value ) );
			$out 		= '';

			if( $value === '' )
				return '';

			foreach( preg_split( '/\n{2,}/', $value ) ?: [] as $paragraph ) {

				$clean = \Nino\Html::sanitizeHtml( trim( $paragraph ) );

				if( $clean === '' )
					continue;

				$out .= '<p class="'. self::BODY_CLASS. '">'. str_replace( "\n", '<br>', $clean ). '</p>';
			}

			return $out;
		}

		/**
		 *	The post's picture, as a whole <img> or as nothing at all.
		 *
		 *	A tag rather than a url, because the alternative is what a template
		 *	written against [elements] has to do today: src="…/images/[[image]]"
		 *	and a broken picture on every post that has none. The width and the
		 *	height come out of the model, where the upload was cropped to them,
		 *	so a list does not jump while it loads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$section			A normalised section
		 *	@param		array			$model				The element type's model
		 *	@param		array			$element			An element
		 *
		 *	@return 	string											An <img>, or ''
		 */
		private static function _image( array &$appData, array $section, array $model, array $element ): string {

			$field 		= (string) $section['image'];
			$filename	= $field === '' ? '' : trim( (string) ( $element[$field] ?? '' ) );

			if( $filename === '' || ( $model[$field]['type'] ?? '' ) !== 'image' )
				return '';

			$alt 	= $section['alt'] === '' ? '' : (string) ( $element[ $section['alt'] ] ?? '' );
			$size = '';

			foreach( [ 'width', 'height' ] as $dimension )
				if( ( $model[$field][$dimension] ?? 0 ) > 0 )
					$size .= ' '. $dimension. '="'. (int) $model[$field][$dimension]. '"';

			return '<img src="'. htmlspecialchars( \Nino\Images::getUrl( $appData, $filename ), ENT_QUOTES, 'UTF-8' ). '"'
				. $size. ' alt="'. htmlspecialchars( $alt, ENT_QUOTES, 'UTF-8' ). '" loading="lazy">';
		}

		/**
		 *	A field value on its way into a template. The same two steps
		 *	Modules\Elements takes, and for the same reason: the value is
		 *	editor content, and the block is rendered again after this
		 *
		 *	@param		string		$value
		 *	@param		bool			$html					The model released this field for inline html
		 *
		 *	@return 	string
		 */
		private static function _value( string $value, bool $html ): string {

			$safe = $html === true
				? \Nino\Html::sanitizeHtml( $value )
				: htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

			return str_replace( '[', '&#91;', $safe );
		}

		/**
		 *	A page's url. Page one is the section without a query, so the first
		 *	page of a blog has one address rather than two
		 *
		 *	@param		string		$base					The section's path, with the project's dir
		 *	@param		int				$page
		 *
		 *	@return 	string
		 */
		private static function _href( string $base, int $page ): string {

			return htmlspecialchars( $page <= 1 ? $base : $base. '?'. \Nino\Modules\Posts::PAGE_KEY. '='. $page, ENT_QUOTES, 'UTF-8' );
		}

		/**
		 *	A word the pager says: the attribute, else the project's own fill,
		 *	else the one built in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *	@param		string		$which				'prev', 'next' or 'label'
		 *	@param		string		$english			The fallback
		 *	@param		string		$german				...and its German half
		 *
		 *	@return 	string											Escaped, ready for markup
		 */
		private static function _text( array &$appData, array $args, string $which, string $english, string $german ): string {

			$given = (string) ( $args[$which] ?? '' );

			// An attribute that still carries brackets is an unresolved fill,
			// not a label - the project has no such text key
			if( $given !== '' && str_contains( $given, '[[' ) === false )
				return htmlspecialchars( $given, ENT_QUOTES, 'UTF-8' );

			$fill = \Nino\Html::renderTextfill( $appData, '/posts/'. $which );

			if( $fill !== '' )
				return htmlspecialchars( $fill, ENT_QUOTES, 'UTF-8' );

			return str_starts_with( \Nino\Locales::getCurrentLocale( $appData ), 'de' ) === true ? $german : $english;
		}
	}

}
