<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Social			Links to the profiles a site keeps elsewhere
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino-features
 */
namespace Nino\Modules {

	/**
	 *	Social links. The links are elements of the type /social, which the
	 *	install unit copies once with four of them - the Elements panel is
	 *	where an editor keeps them, and there is no screen of this feature's
	 *	own. This class only draws them: [social], [social-link], [social-icon].
	 *
	 *	Every value an element holds is an editor's, and treated as untrusted:
	 *	the address is held to a list of schemes, the icon to a slug of this
	 *	feature's own icons, the name escaped with the bracket neutralised -
	 *	what a shortcode returns is rendered again.
	 */
	class Social {

		public const string TYPE				= '/social';
		public const string TEMPLATES	= '/features/Social/templates';
		public const string ICONS			= '/features/Social/icons';

		// What a link is drawn with when its icon is no icon of this feature's:
		// a slug the type editor added to the select, or a hand-edited file
		public const string FALLBACK		= 'link';

		// An element id as the Elements panel accepts one, and an icon's slug
		private const string ID		= '/^[A-Za-z0-9][A-Za-z0-9_-]*$/';
		private const string SLUG	= '/^[a-z0-9][a-z0-9-]*$/';

		// The one attribute this class writes itself, with nothing an editor
		// typed in it: an address on the web is one of the site's profiles,
		// and rel="me" is how a profile that links back - Mastodon's - verifies
		// that it is
		private static string $relMe = ' rel="me"';

		/**
		 *	Module initiating
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
			\Nino\Html::addShortcode( $appData, 'social', [ self::class, 'doShortcode' ] );
			\Nino\Html::addShortcode( $appData, 'social-link', [ self::class, 'doLinkShortcode' ] );
			\Nino\Html::addShortcode( $appData, 'social-icon', [ self::class, 'doIconShortcode' ] );
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Social/assets/social.css' );
		}

		/**
		 *	[social only="a,b" exclude="c" show="icon|both|label" size="small|large"]
		 *	- the links as a list, in the order of their position. only= and
		 *	exclude= pick by element id and leave that order as it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string									The list, or '' when no link is left to draw
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$only			= self::_ids( (string) ( $args['only'] ?? '' ) );
			$exclude	= self::_ids( (string) ( $args['exclude'] ?? '' ) );
			$show			= self::_show( $args, 'icon' );

			$items = '';
			foreach( self::links( $appData ) as $id => $link ) {

				// (string): an id of digits alone is an integer as an array key
				$id = (string) $id;

				if( ( $only !== [] && in_array( $id, $only, true ) === false ) || in_array( $id, $exclude, true ) === true )
					continue;

				$items .= str_replace( '[[link]]', self::_link( $appData, $link, $show ), self::template( $appData, 'social-item' ) );
			}

			// An empty list is no list: a frame that includes this where no link
			// is left gets nothing, not an <ul> with nothing in it
			if( $items === '' )
				return '';

			// The items last: str_replace() also searches what an earlier pair
			// put in, and the items are where the editors' words are
			return str_replace(
				[ '[[modifiers]]', '[[items]]' ],
				[ in_array( $args['size'] ?? '', [ 'small', 'large' ], true ) === true ? ' nino-social--'. $args['size'] : '', $items ],
				self::template( $appData, 'social' )
			);
		}

		/**
		 *	[social-link <id>] or [social-link id="…"] - one link, icon and name,
		 *	for running text; show= as for [social], both by default
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string									The link, or '' for an id that is no link to draw
		 */
		public static function doLinkShortcode( array &$appData, array $args ): string {

			$id 		= (string) ( $args['id'] ?? $args[0] ?? '' );
			$links	= self::links( $appData );

			if( preg_match( self::ID, $id ) !== 1 || isset( $links[$id] ) === false )
				return '';

			return self::_link( $appData, $links[$id], self::_show( $args, 'both' ) );
		}

		/**
		 *	[social-icon <slug>] or [social-icon name="…"] - the icon alone, for
		 *	markup of a template's own: [social-icon name="[[icon]]"] inside an
		 *	[elements /social] block draws each element's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string
		 */
		public static function doIconShortcode( array &$appData, array $args ): string {
			return self::icon( $appData, (string) ( $args['name'] ?? $args[0] ?? '' ) );
		}

		/**
		 *	The links there are to draw, keyed by element id, by position.
		 *
		 *	A hidden one is left out wherever it is asked for, and so is one
		 *	without an address that may go into a link (see safeUrl()); one
		 *	without a name is named by its address, so no link reaches a screen
		 *	reader without one. The position sorts as a number, and none - 0,
		 *	which is what the Elements panel saves for a field left empty - goes
		 *	last: a link added without one is added at the end. usort() is
		 *	stable, so links of one position keep the order of the type file.
		 *	A type file of another shape - one the project had before, which the
		 *	unit leaves alone - gives what of it fits, without a warning: one
		 *	would be written on every page view
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ id => [ 'title', 'icon', 'url' ], ... ]
		 */
		public static function links( array &$appData ): array {

			$elements = \Nino\Elements::queryElements( $appData, self::TYPE, [], '', [] );

			if( is_array( $elements ) === false )
				return [];

			usort( $elements, static fn( mixed $a, mixed $b ): int => self::_position( $a ) <=> self::_position( $b ) );

			$links = [];
			foreach( $elements as $element ) {

				if( is_array( $element ) === false || filter_var( $element['hidden'] ?? false, FILTER_VALIDATE_BOOLEAN ) === true )
					continue;

				$id		= substr( (string) ( $element['.uri'] ?? '' ), strlen( self::TYPE ) + 1 );
				$url	= self::safeUrl( is_string( $element['link'] ?? null ) === true ? $element['link'] : '' );

				if( preg_match( self::ID, $id ) !== 1 || $url === null )
					continue;

				$title = trim( is_string( $element['title'] ?? null ) === true ? $element['title'] : '' );

				$links[$id] = [
					'title'	=> $title !== '' ? $title : self::_nameFromUrl( $url ),
					'icon'	=> is_string( $element['icon'] ?? null ) === true ? $element['icon'] : '',
					'url'		=> $url,
				];
			}

			return $links;
		}

		/**
		 *	An address that may go into href, or null: http(s) with a host and
		 *	nothing before it, mailto:, tel:, or a path on this site. Whitespace
		 *	and control characters anywhere are a refusal - a browser drops a tab
		 *	or a newline from an address before it reads it, so "/<tab>/elsewhere"
		 *	would be a link to another host that looked like one to this site
		 *
		 *	@param		string		$url					What the element holds
		 *
		 *	@return 	string|null
		 */
		public static function safeUrl( string $url ): ?string {

			$url = trim( $url );

			if( $url === '' || preg_match( '/[\x00-\x20\x7F]/', $url ) === 1 )
				return null;

			// A path, but not "//host" or "/\host", which a browser reads as one
			if( preg_match( '#^/(?![/\\\\])#', $url ) === 1 )
				return $url;

			// A host and nothing before it: user@host is a link to host
			if( preg_match( '#^https?://[^/?\#\\\\@]+(?:[/?\#]|$)#i', $url ) === 1 )
				return $url;

			if( preg_match( '#^(?:mailto|tel):[^/\\\\]#i', $url ) === 1 )
				return $url;

			return null;
		}

		/**
		 *	One icon, wrapped aria-hidden: the link's name is text beside it, so
		 *	the icon is decoration to a screen reader. An svg is a file of this
		 *	feature's, never an editor's input - what an element holds only picks
		 *	one by its slug, and a slug that picks none draws the link icon
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					instagram, website, …
		 *
		 *	@return 	string
		 */
		public static function icon( array &$appData, string $slug ): string {

			$svg = preg_match( self::SLUG, $slug ) === 1 ? self::_svg( $appData, $slug ) : '';

			if( $svg === '' )
				$svg = self::_svg( $appData, self::FALLBACK );

			return str_replace( '[[svg]]', $svg, self::template( $appData, 'social-icon' ) );
		}

		/**
		 *	One of this feature's templates, read through the virtual filesystem
		 *	the way every feature reads its own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The file name below templates/, without .tpl
		 *
		 *	@return 	string
		 */
		public static function template( array &$appData, string $name ): string {

			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );
			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}

		/**
		 *	One anchor
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$link					[ 'title', 'icon', 'url' ] as links() has it
		 *	@param		string		$show					icon, both or label
		 *
		 *	@return 	string
		 */
		private static function _link( array &$appData, array $link, string $show ): string {

			// The name last, for the reason doShortcode() gives
			return str_replace(
				[ '[[url]]', '[[rel]]', '[[icon]]', '[[labelclass]]', '[[title]]' ],
				[
					self::_escape( $link['url'] ),
					preg_match( '#^https?://#i', $link['url'] ) === 1 ? self::$relMe : '',
					$show === 'label' ? '' : self::icon( $appData, $link['icon'] ),
					$show === 'icon' ? 'nino-social-label nino-sr-only' : 'nino-social-label',
					self::_escape( $link['title'] ),
				],
				self::template( $appData, 'social-link' )
			);
		}

		/**
		 *	An icon file's markup, or '' where the slug names none. The bracket is
		 *	neutralised like everywhere else: the svg goes through the renderer
		 *	again with the rest of the shortcode's output
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					A slug, checked by the caller
		 *
		 *	@return 	string
		 */
		private static function _svg( array &$appData, string $slug ): string {

			$svg = \Nino\Filesystem::getFileContent( $appData, self::ICONS. '/'. $slug. '.svg', '' );
			$svg = is_string( $svg ) === true ? trim( $svg ) : '';

			return str_starts_with( $svg, '<svg' ) === true ? str_replace( '[', '&#91;', $svg ) : '';
		}

		/**
		 *	@param		array			$args					Shortcode arguments
		 *	@param		string		$default			What a missing or unknown show= means
		 *
		 *	@return 	string									icon, both or label
		 */
		private static function _show( array $args, string $default ): string {
			return in_array( $args['show'] ?? '', [ 'icon', 'both', 'label' ], true ) === true ? (string) $args['show'] : $default;
		}

		/**
		 *	@param		mixed			$element			One element as queryElements() returns it
		 *
		 *	@return 	float										Its position, or INF for none
		 */
		private static function _position( mixed $element ): float {

			$order = is_array( $element ) === true ? ( $element['order'] ?? null ) : null;

			return is_numeric( $order ) === true && (float) $order !== 0.0 ? (float) $order : INF;
		}

		/**
		 *	@param		string		$list					"instagram, youtube"
		 *
		 *	@return 	array										The element ids in it, each once
		 */
		private static function _ids( string $list ): array {
			return array_values( array_unique( array_filter( array_map( 'trim', explode( ',', $list ) ), static fn( string $id ): bool => preg_match( self::ID, $id ) === 1 ) ) );
		}

		/**
		 *	A name for a link nobody named: the host without www., or the address
		 *	after mailto: or tel:
		 *
		 *	@param		string		$url					An address safeUrl() let through
		 *
		 *	@return 	string
		 */
		private static function _nameFromUrl( string $url ): string {

			$host = parse_url( $url, PHP_URL_HOST );

			if( is_string( $host ) === true && $host !== '' )
				return preg_replace( '/^www\./i', '', $host ) ?? $host;

			return preg_replace( '/^(?:mailto|tel):/i', '', $url ) ?? $url;
		}

		/**
		 *	@param		string		$value
		 *
		 *	@return 	string									For text and for a quoted attribute, '[' neutralised
		 */
		private static function _escape( string $value ): string {
			return str_replace( '[', '&#91;', htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) );
		}
	}

}
