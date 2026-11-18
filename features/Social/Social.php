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
	 *	Social links. The links are elements of the type /social, which the install unit adds, add-only, with four of
	 *	them - the Elements panel is where an editor keeps them, and there is no screen of this feature's own. This class
	 *	draws them: the components social (a list), social-link (one link) and social-icon (an icon alone), whose markup
	 *	is in components/<name>/template.tpl.
	 *
	 *	Every value an element holds is an editor's, and treated as untrusted: the address is held to a list of schemes
	 *	(safeUrl()), the icon to the fixed list of the icons of this feature (ICONS), the name escaped. An svg is a file
	 *	of this feature's, never put together from what a project holds
	 */
	class Social {

		public const string TYPE				= '/social';
		public const string ICONS_DIR		= '/features/Social/icons';

		// What a link is drawn with when its icon is none of ICONS: a hand-edited file of the type
		public const string FALLBACK		= 'link';

		// The icons there are, one file each in ICONS_DIR and one option each of the element type: the one list an icon is
		// held to wherever it comes from
		public const array ICONS				= [
			'instagram', 'facebook', 'linkedin', 'youtube', 'github', 'twitch',
			'telegram', 'whatsapp', 'mastodon', 'threads', 'bluesky', 'tiktok', 'xing', 'pinterest',
			'website', 'mail', 'phone', 'rss', 'podcast', 'blog', 'shop', 'map', 'link',
		];

		// An element id as the Elements panel accepts one
		private const string ID		= '/^[A-Za-z0-9][A-Za-z0-9_-]*$/D';

		// The attributes this class writes itself, with nothing an editor typed in them, on an address on the web only: it
		// is one of the site's profiles, rel="me" is how a profile that links back - Mastodon's - verifies that it is, and
		// it opens in a new tab, which noopener keeps from reaching back into this one
		private const string REL_PROFILE		= ' rel="me noopener"';
		private const string TARGET_BLANK	= ' target="_blank"';

		// One item of the list: the link is finished markup of this class
		private const string ITEM					= '<li class="social-item">[[link]]</li>';

		/**
		 *	The renderer of the component social, [social only="a,b" exclude="c" show="both" size="large"]: the links as a
		 *	list, in the order of their position. only and exclude pick by element id and leave that order as it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call, made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string									The list, or '' where no link is left to draw
		 */
		public static function componentSocial( array &$appData, array $args, string $content, array $context ): string {

			$only			= self::_ids( $args['only'] );
			$exclude	= self::_ids( $args['exclude'] );
			$items		= '';

			// The class of a link that stands in the list: the way the list shows it is the class a lone link writes for it
			$class		= 'social-link'. ( $args['show'] === 'both' ? '' : ' social-link--show-'. $args['show'] );

			foreach( self::links( $appData ) as $id => $link ) {

				// (string): an id of digits alone is an integer as an array key
				$id = (string) $id;

				if( ( $only !== [] && in_array( $id, $only, true ) === false ) || in_array( $id, $exclude, true ) === true )
					continue;

				$items .= strtr( self::ITEM, [ '[[link]]' => self::_anchor( $appData, $link, $args['show'], $class ) ] );
			}

			// An empty list is no list: a frame that holds this where no link is left gets nothing, not an <ul> with nothing in it
			if( $items === '' )
				return '';

			return \Nino\Components::fill( $appData, 'social', $args, $content, [ '[[items]]' => $items ] );
		}

		/**
		 *	The renderer of the component social-link, [social-link profile="instagram" show="icon"]: one link, for running
		 *	text. The register has made the profile one of linkOptions(), so a name that is none of the links is ''
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call, made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string									The link, or '' for a profile that is no link to draw
		 */
		public static function componentSocialLink( array &$appData, array $args, string $content, array $context ): string {

			$links = self::links( $appData );

			if( preg_match( self::ID, $args['profile'] ) !== 1 || isset( $links[$args['profile']] ) === false )
				return '';

			return self::_anchor( $appData, $links[$args['profile']], $args['show'], $args['class'] );
		}

		/**
		 *	The renderer of the component social-icon, [social-icon name="telegram" label="Telegram"]: the icon alone. With
		 *	a label it stands alone and carries it as its name; without one it stands beside a word and is hidden from
		 *	a screen reader
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call, made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string
		 */
		public static function componentSocialIcon( array &$appData, array $args, string $content, array $context ): string {

			// The label arrives escaped, once
			$label = trim( $args['label'] );

			return self::_icon( $appData, $args['name'], $args['class'], $label === '' ? ' aria-hidden="true"' : ' role="img" aria-label="'. $label. '"' );
		}

		/**
		 *	The options of the attribute profile of social-link: the links there are to draw
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		profile
		 *
		 *	@return 	array										element id => name
		 */
		public static function linkOptions( array &$appData, string $attribute ): array {

			$options = [];

			foreach( self::links( $appData ) as $id => $link )
				$options[(string) $id] = $link['title'];

			return $options;
		}

		/**
		 *	The options of the attribute name of social-icon: the icons of this feature, and no others
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		name
		 *
		 *	@return 	array										icon => label
		 */
		public static function iconOptions( array &$appData, string $attribute ): array {

			$words = [
				'website'	=> [ 'de_DE' => 'Website', 'en_US' => 'Website' ],
				'mail'		=> [ 'de_DE' => 'E-Mail', 'en_US' => 'Mail' ],
				'phone'		=> [ 'de_DE' => 'Telefon', 'en_US' => 'Phone' ],
				'rss'			=> [ 'de_DE' => 'RSS', 'en_US' => 'RSS' ],
				'podcast'	=> [ 'de_DE' => 'Podcast', 'en_US' => 'Podcast' ],
				'blog'		=> [ 'de_DE' => 'Blog', 'en_US' => 'Blog' ],
				'shop'		=> [ 'de_DE' => 'Shop', 'en_US' => 'Shop' ],
				'map'			=> [ 'de_DE' => 'Karte', 'en_US' => 'Map' ],
				'link'		=> [ 'de_DE' => 'Link', 'en_US' => 'Link' ],
			];
			$options = [];

			foreach( self::ICONS as $icon )
				$options[$icon] = $words[$icon] ?? ucfirst( $icon );

			return $options;
		}

		/**
		 *	The links there are to draw, keyed by element id, by position.
		 *
		 *	A hidden one is left out wherever it is asked for, and so is one without an address that may go into a link
		 *	(see safeUrl()); one without a name is named by its address, so no link reaches a screen reader without one.
		 *	The position sorts as a number, and none - 0, which is what the Elements panel saves for a field left empty -
		 *	goes last: a link added without one is added at the end. usort() is stable, so links of one position keep the
		 *	order of the type file. A type file of another shape - one the project had before, which the unit leaves alone -
		 *	gives what of it fits, without a warning: one would be written on every page view
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
		 *	An address that may go into href, or null: http(s) with a host and nothing before it, mailto:, tel:, or a path
		 *	on this site. Whitespace and control characters anywhere are a refusal - a browser drops a tab or a newline from
		 *	an address before it reads it, so "/<tab>/elsewhere" would be a link to another host that looked like one to
		 *	this site
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
		 *	One anchor. An address on the web opens in a new tab and carries rel="me noopener"; a path of this website,
		 *	mailto: and tel: carry neither. A path of this website gets the directory the website is served from in front
		 *	of it, as the kernel writes the link to a page - an editor writes /contact, the website may stand at /shop
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$link					[ 'title', 'icon', 'url' ] as links() has it
		 *	@param		string		$show					icon, text or both
		 *	@param		string		$class				The class of the anchor as the register or the list made it
		 *
		 *	@return 	string
		 */
		private static function _anchor( array &$appData, array $link, string $show, string $class ): string {

			$web = preg_match( '#^https?://#i', $link['url'] ) === 1;

			// The name of the link is its text: with the icon alone it is still there, for a screen reader, and the stylesheet hides it from the eye
			return \Nino\Components::fill( $appData, 'social-link', [ 'class' => $class ], '', [
				'[[url]]'		=> \Nino\Components::escape( str_starts_with( $link['url'], '/' ) === true ? rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). $link['url'] : $link['url'] ),
				'[[target]]'	=> $web === true ? self::TARGET_BLANK : '',
				'[[rel]]'		=> $web === true ? self::REL_PROFILE : '',
				'[[icon]]'	=> $show === 'text' ? '' : self::_icon( $appData, $link['icon'], 'social-icon', ' aria-hidden="true"' ),
				'[[title]]'	=> \Nino\Components::escape( $link['title'] ),
			] );
		}

		/**
		 *	One icon. An svg is a file of this feature's, never an editor's input: what an element holds only picks one of
		 *	ICONS, and a name that picks none draws the link icon
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					instagram, website, ... - or whatever an element holds
		 *	@param		string		$class
		 *	@param		string		$aria					The attributes that give the icon its name, or hide it
		 *
		 *	@return 	string
		 */
		private static function _icon( array &$appData, string $name, string $class, string $aria ): string {

			$svg = self::_svg( $appData, in_array( $name, self::ICONS, true ) === true ? $name : self::FALLBACK );

			return $svg === '' ? '' : \Nino\Components::fill( $appData, 'social-icon', [ 'class' => $class ], '', [ '[[svg]]' => $svg, '[[aria]]' => $aria ] );
		}

		/**
		 *	An icon file's markup, or '' where the file is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					One of ICONS
		 *
		 *	@return 	string
		 */
		private static function _svg( array &$appData, string $name ): string {

			$svg = \Nino\Filesystem::getFileContent( $appData, self::ICONS_DIR. '/'. $name. '.svg', '' );
			$svg = is_string( $svg ) === true ? trim( $svg ) : '';

			return str_starts_with( $svg, '<svg' ) === true ? $svg : '';
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
		 *	A name for a link nobody named: the host without www., or the address after mailto: or tel:
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
	}

}
