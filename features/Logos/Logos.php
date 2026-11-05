<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Logos				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Logos							A row of logos - the customers a site is proud of - drawn
	 *										from the elements of one type.
	 *
	 *										A logo is an element, so the editors keep them where they
	 *										keep every repeated thing, in the Elements panel, and the
	 *										feature reads them the way the stacks of the kernel do:
	 *										the loop is \Nino\Modules\Components::renderStack(), the
	 *										same query, sort and limit as [elements], and each logo's
	 *										picture is the kernel's own [image] with an image field
	 *										of the element as its source. The feature writes no
	 *										markup of the picture and reads no file of its own.
	 *
	 *										A logo can have a second picture for a dark ground. It is
	 *										written beside the first in the kernel's logo set, the
	 *										two classes Nino.css shows by the ground the set stands
	 *										on, so nothing in this class knows a ground.
	 *
	 *										The class has no init(): the manifest declares the component
	 *										and the Components module registers it, and without that
	 *										module there is nothing to draw the pictures with.
	 *
	 *										The list is written twice: once to be read, and once more
	 *										as a copy a screen reader skips and a stylesheet shows
	 *										only where the look is a moving band. The look is the
	 *										Design feature's to choose, after the page is written,
	 *										so the markup carries the copy for every look and the
	 *										stylesheet hides it where nothing moves.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Logos {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Logos/templates';

		// The element type the install unit brings, and the one [logos] reads
		// where it is not told another
		public const string TYPE = 'logo';

		// The heights of a logo: the class of the row, relative to the height
		// the Design feature sets for the middle one
		public const array HEIGHTS = [ 's', 'm', 'l' ];

		// The sort of a call that says nothing: by the position the type brings,
		// smaller first, a logo with none or with 0 last
		public const string SORT = 'order';

		// The callback that orders the logos by their position, registered by init() and named by componentLogos()
		public const string ORDER = '/logos/order';

		// The most logos a call asks for. A row of more is a wall, and the
		// copy of a band doubles it
		public const int MOST = 200;

		/**
		 *	The one thing the feature registers besides what its manifest declares: the
		 *	callback the loop of a row orders its logos by, see callbackOrder()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, self::ORDER, [ self::class, 'callbackOrder' ] );
		}

		/**
		 *	[logos] - the elements of a type as a row of logos.
		 *
		 *	A logo without a picture is left out; a type with none to draw, a type that
		 *	is no element type with a picture field image and a plain text field name, and a site
		 *	on which the Components module does not know [image] draw nothing. The same arguments are read
		 *	whether the Components module made them ready or a page wrote them by
		 *	hand: a bound is applied here, so that a call with a limit of 9000 or a
		 *	height of xl is the nearest call that means something.
		 *
		 *	A logo with an address is a link where the address is an https, http or
		 *	mailto address or a path of the site, and where the call does not say
		 *	link="0". Anything else - a javascript: address, a protocol-relative one -
		 *	is drawn without the link, not with a changed one.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					type, limit, sort, link, height and class
		 *
		 *	@return 	string								The row, or '' where there is nothing to draw
		 */
		public static function componentLogos( array &$appData, array $args ): string {

			$type = trim( (string) ( $args['type'] ?? '' ) );
			$type = $type === '' ? self::TYPE : $type;

			if( preg_match( '#^[a-z][a-z0-9_-]*$#D', $type ) !== 1 )
				return '';

			if( isset( \Nino\Modules\Components::registered( $appData )['image'] ) === false )
				return '';

			if( \Nino\Filesystem::fileExists( $appData, '/elements/'. $type. '.php' ) === false )
				return '';

			$model = \Nino\Elements::getElementModel( $appData, '/'. $type );

			// The name is the alt text of the picture, written into an attribute from the fill of the field: a field that keeps
			// markup or the line breaks of its text (html, breaks) leaves a quote in it, and a field of another kind is none
			$name = $model['name'] ?? [];

			if( ( $model['image']['type'] ?? '' ) !== 'image' || ( $name['type'] ?? '' ) !== 'string' || ( $name['html'] ?? false ) === true || ( $name['breaks'] ?? false ) === true )
				return '';

			$height = (string) ( $args['height'] ?? '' );
			$sort = array_key_exists( 'sort', $args ) === true ? trim( (string) $args['sort'] ) : self::SORT;
			$loop = [
				'source'	=> $type,
				'content'	=> \Nino\Feature::template( $appData, self::TEMPLATES, 'logos-picture' ),
				'sort'		=> $sort,
				'limit'		=> max( 0, min( self::MOST, (int) ( $args['limit'] ?? 0 ) ) ),
			];

			/*	The position is no field the kernel's sort can read: the Elements panel saves a field it was left empty in as 0, which would put a logo
				with no position in front of one with a position of 10. The loop is told no sort and a callback orders what it found - between the
				query and the limit, which is applied to what the callback let through - with 0, none and empty last	*/
			if( $sort === self::SORT ) {
				$loop['sort'] = '';
				$loop['callback'] = self::ORDER;
			}
			$links = \Nino\Feature::flag( $args, 'link', true );
			$items = [];
			$copy = [];

			// One run of the loop makes both lists: the cell is the pictures of one logo, and each logo is written twice, as an item and as its copy
			\Nino\Modules\Components::renderStack( $appData, $loop, static function( string $pictures, array $element ) use ( &$appData, $links, &$items, &$copy ): string {

				if( trim( $pictures ) === '' )
					return '';

				$items[] = self::_item( $appData, $pictures, $element, $links, false );
				$copy[] = self::_item( $appData, $pictures, $element, $links, true );

				return '';
			} );

			if( $items === [] )
				return '';

			return str_replace(
				[ '[[classes]]', '[[height]]', '[[count]]', '[[items]]', '[[copy]]' ],
				[
					\Nino\Feature::classes( $args ),
					in_array( $height, self::HEIGHTS, true ) === true ? $height : 'm',
					(string) count( $items ),
					implode( '', $items ),
					implode( '', $copy ),
				],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'logos' )
			);
		}

		/**
		 *	The logos by their position, the smaller first: a logo whose position is 0, empty or missing stands last, in the order the
		 *	elements were found in (the sort is stable). Registered by init() for the callback the loop of a row names
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$elements		(reference) The elements the loop found
		 *
		 *	@return 	array											The same elements, ordered
		 */
		public static function callbackOrder( array &$appData, mixed &$elements ): array {

			$elements = is_array( $elements ) === true ? $elements : [];

			usort( $elements, static fn( mixed $a, mixed $b ): int => self::_position( $a ) <=> self::_position( $b ) );

			return $elements;
		}

		/**
		 *	@param		mixed			$element			One element as the loop found it
		 *
		 *	@return 	float										Its position, or INF for none: empty, missing or 0
		 */
		private static function _position( mixed $element ): float {

			$order = is_array( $element ) === true ? ( $element['order'] ?? null ) : null;

			return is_numeric( $order ) === true && (float) $order !== 0.0 ? (float) $order : INF;
		}

		/**
		 *	One logo in its item. The copy is the same logo with its link taken out of
		 *	the order of the Tab key: it is hidden from a screen reader, and a link a
		 *	keyboard can reach and a screen reader cannot see is a stop nobody can name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$pictures			The pictures of the logo, as the stack rendered them
		 *	@param		array			$element			The element
		 *	@param		bool			$links				Whether a logo with an address is a link
		 *	@param		bool			$copy					Whether this is the copy
		 *
		 *	@return 	string
		 */
		private static function _item( array &$appData, string $pictures, array $element, bool $links, bool $copy ): string {

			$inner = str_replace( '[[inner]]', $pictures, \Nino\Feature::template( $appData, self::TEMPLATES, 'logos-set' ) );
			$href = $links === true ? self::href( (string) ( $element['url'] ?? '' ) ) : '';

			if( $href !== '' )
				$inner = str_replace(
					[ '[[href]]', '[[inner]]' ],
					[ $href, $inner ],
					\Nino\Feature::template( $appData, self::TEMPLATES, $copy === true ? 'logos-link-copy' : 'logos-link' )
				);

			return str_replace( '[[inner]]', $inner, \Nino\Feature::template( $appData, self::TEMPLATES, 'logos-item' ) );
		}

		/**
		 *	An address a logo may link to, escaped for an attribute: https, http and
		 *	mailto addresses and a path of the site, with no space or control
		 *	character anywhere in it. A browser drops a control character in front of a
		 *	scheme before it reads the address, so none is allowed to stand in it
		 *
		 *	@param		string		$url
		 *
		 *	@return 	string										'' for an address no logo may link to
		 */
		public static function href( string $url ): string {

			$url = trim( $url );

			if( $url === '' || preg_match( '/[\x00-\x20\x7F]/', $url ) === 1 )
				return '';

			if( preg_match( '#^(?:https?://|mailto:)[^/\s]#i', $url ) !== 1 && preg_match( '#^/(?![/\\\\])#', $url ) !== 1 )
				return '';

			return \Nino\Modules\Components::escape( $url );
		}
	}

}
