<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Embed				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Embed							A video, a map or any other third-party frame as a
	 *										surface the visitor presses - and nothing else on the
	 *										page until they do.
	 *
	 *										The reason this is a feature rather than an iframe in a
	 *										template: hiding an iframe does not stop it. An iframe
	 *										inside a container with the hidden attribute, with
	 *										display:none, or with visibility:hidden is fetched by the
	 *										browser exactly like a visible one, so the visitor's
	 *										address has reached YouTube or Google before they were
	 *										asked anything. What this writes carries the address in a
	 *										data attribute; the iframe is created when it is released
	 *										and not before, so there is no request to suppress.
	 *
	 *										Two things release one, and both are the visitor's:
	 *										pressing the surface, and - where the Consent feature is
	 *										installed and the settings name a category - having
	 *										already allowed that category. embed.js reads the
	 *										allowed list off <html data-consent> and listens for the
	 *										"nino:consent" event Consent fires when it changes, so
	 *										the two features meet over markup rather than over code
	 *										and neither has to know the other is there.
	 *
	 *										No thumbnail is ever fetched from the provider. A
	 *										YouTube still lives on a YouTube server, so showing one
	 *										would make the request this feature exists to prevent,
	 *										one image earlier. A project that wants a picture on the
	 *										surface names a picture slot of its own with poster and
	 *										it is served from the project's own images.
	 *
	 *										embed.js reaches the browser in the site's script
	 *										bundle through the manifest's assets; the look of the
	 *										surface and of the box is the standard part of the
	 *										group embed.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Embed {

		// The markup the renderer writes besides the template of the component: the picture on the surface, one line, the
		// tokens in double brackets. It is the project's own picture and never one of the provider's
		public static array $html = [
			'poster' => '<img class="embed-poster" src="[[src]]" alt="" loading="lazy" decoding="async">',
		];

		/*	The two providers worth knowing by name, because their embed address
			is not the address anybody has in their clipboard - what a person
			copies is a watch page, and turning that into an embed is the step
			this saves them. YouTube and Vimeo are the only ones the code knows:
			another is one row here and its rule in video().

			'pattern' is the shape of the id, and the only thing of a pasted
			address that ever reaches the markup. 'hosts' is every host that
			counts as that provider's own, spelled out and compared as a whole
			- a suffix match would take youtube.com.evil.example for it.

			'youtube' is youtube-nocookie.com rather than youtube.com: it is
			Google's own no-cookie host, the same video, and there is no reason
			to offer the other one. 'vimeo' carries dnt=1, which is Vimeo's own
			do-not-track flag. Neither makes an embed consent-free - both still
			see the visitor's address once the frame is there, which is why
			nothing here loads by itself	*/
		public const array PROVIDERS = [
			'youtube'	=> [
				'pattern'	=> '/^[A-Za-z0-9_-]{6,20}$/D',
				'src'			=> 'https://www.youtube-nocookie.com/embed/%s?rel=0',
				'hosts'		=> [ 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be' ],
			],
			'vimeo'		=> [
				'pattern'	=> '/^[0-9]{5,15}$/D',
				'src'			=> 'https://player.vimeo.com/video/%s?dnt=1',
				'hosts'		=> [ 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com' ],
			],
		];

		// The hex token that makes an unlisted Vimeo video reachable. The D on
		// this pattern and the two above: without it '$' also matches before a
		// trailing newline, and an id with one in it would reach the markup
		private const string VIMEO_HASH = '/^[0-9a-f]{6,20}$/D';

		// The shapes a box can have, as the class suffix a project writes and
		// the ratio embed.css gives it. Named rather than free, because a
		// ratio typed into a template is a ratio nobody checks
		public const array RATIOS = [ '16-9', '4-3', '1-1', '21-9' ];
		public const string RATIO_DEFAULT = '16-9';

		/**
		 *	Hook the output phase, where the hosts of the page's embeds join the policy
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// The policy is widened where the finished response is in hand, and by
			// the hosts componentEmbed() recorded - see callbackOutput()
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOutput' ] );
		}

		/**
		 *	The renderer of the component embed, [embed youtube="..."], [embed vimeo="..."], [embed url="https://..."]
		 *
		 *	Renders nothing at all where no address can be made of what was
		 *	written: an [embed] with a typo is a call that cannot mean
		 *	anything, and a box with no frame behind it is worse on a page than
		 *	no box - it is a thing to press that never does anything. The
		 *	register has made the attributes ready, so each arrives escaped:
		 *	the address is decoded to be read and escaped again where it is
		 *	written, and the title, which is text, is written as it came.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call
		 *	@param		string		$content			Nothing: the component holds nothing
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string								The surface, or '' where the address is not one
		 */
		public static function componentEmbed( array &$appData, array $args, string $content, array $context ): string {

			$src = self::source( $args );

			if( $src === '' )
				return '';

			// What the policy has to name, collected here and not read back out of
			// the body: a call is written by the project, whereas the body is
			// every text, element and template the page was made of
			$appData['./embed/frames'][ \Nino\Feature::origin( $src ) ] = true;

			$host = \Nino\Components::escape( self::host( $src ) );

			/*	The title is the accessible name of the frame that will be there,
				and the words on the surface before it is. A frame with no name is
				"iframe" to a screen reader, so there is a fallback word rather than
				an empty string - but a project that writes one says what this
				particular video is, which no word can	*/
			$title = $args['title'];

			return \Nino\Components::fill( $appData, 'embed', $args, $content, [
				'[[src]]'				=> \Nino\Components::escape( $src ),
				'[[host]]'			=> $host,
				'[[consent]]'		=> \Nino\Components::escape( self::category( $appData ) ),
				'[[remember]]'	=> self::remembers( $appData ) === true ? ' data-embed-remember="1"' : '',
				'[[frameTitle]]'	=> $title === '' ? self::_word( $appData, 'frame/title' ) : $title,
				'[[label]]'			=> $title === '' ? self::_word( $appData, 'placeholder/button' ) : $title,
				'[[poster]]'		=> self::_poster( $appData, $args['poster'] ),
			] );
		}

		/**
		 *	Name the hosts of this page's embeds in the Content-Security-Policy's
		 *	frame-src. The shipped policy has no frame-src, so its default-src
		 *	'self' refuses every provider's frame - which is correct for a page
		 *	with no embed, and the reason an embed could never load without this.
		 *
		 *	It stands on /nino/http/output and not on /nino/http/response: the
		 *	hosts are known once the body is rendered, and every response hook
		 *	runs before that. The hosts are the ones componentEmbed() recorded, so a
		 *	page with no [embed] keeps its policy byte for byte; the directive is
		 *	extended where it exists, built from child-src or default-src where
		 *	it does not, and 'none' is left as the project decided it - see
		 *	\Nino\Http::allowSource(). Allowed because it adds only the origin of a
		 *	frame the project wrote into its own template, to frame-src and nothing
		 *	else.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackOutput( array &$appData, array &$request ): void {

			// The workbench sends a policy of its own and is not a page of the website:
			// what an [embed] in the page it previews said is none of its business
			foreach( [ $request['/nino/http/request']['uri'] ?? '', $request['/nino/http/response']['uri'] ?? '' ] as $uri )
				if( is_string( $uri ) === true && ( $uri === '/_admin' || str_starts_with( $uri, '/_admin/' ) === true ) )
					return;

			$frames = $appData['./embed/frames'] ?? [];

			if( is_array( $frames ) === false || $frames === [] || is_string( $request['/nino/http/response']['body'] ?? null ) === false )
				return;

			foreach( array_keys( $frames ) as $origin )
				\Nino\Http::allowSource( $request, 'frame-src', (string) $origin );
		}

		/**
		 *	The embed address one set of attributes names, or '' where
		 *	they name none. A provider id is checked against that provider's own
		 *	shape rather than pasted in: what goes into the markup here ends up
		 *	in an iframe's src once it is released
		 *
		 *	youtube="" and vimeo="" take the id or the address copied from the
		 *	browser (see video()); one that is neither is logged, since the
		 *	call then renders nothing and the page says why nowhere else.
		 *
		 *	@param		array			$args					The attributes of the call
		 *
		 *	@return 	string								An https url, or ''
		 */
		public static function source( array $args ): string {

			foreach( self::PROVIDERS as $provider => $definition ) {

				$value = trim( (string) ( $args[$provider] ?? '' ) );

				if( $value === '' )
					continue;

				$video = self::video( $provider, $value );

				if( $video === null ) {
					// Cut and cleaned: it is whatever somebody pasted, and it goes
					// into a log line
					trigger_error( 'Nino: [embed '. $provider. '="'. preg_replace( '/[\x00-\x1F\x7F]+/', ' ', mb_strcut( $value, 0, 200, 'UTF-8' ) ). '"] is not a video address this can read.', E_USER_WARNING );
					return '';
				}

				return self::_playerSrc( $provider, $video );
			}

			// The register hands an attribute over escaped: an address is read as it was typed
			$url = trim( html_entity_decode( (string) ( $args['url'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			if( $url === '' )
				return '';

			/*	https and nothing else. Not because http would be an attack - a
				template is the project's own file - but because an embed served
				over http on an https page is a frame the browser refuses anyway,
				and refusing it here says so at the one moment somebody is looking	*/
			$parts = parse_url( $url );

			if( is_array( $parts ) === false || ( $parts['scheme'] ?? '' ) !== 'https' || ( $parts['host'] ?? '' ) === '' )
				return '';

			// A host the policy cannot name safely (see \Nino\Feature::origin()) is a
			// frame the browser would refuse - so there is no surface for it either
			if( \Nino\Feature::origin( $url ) === '' )
				return '';

			/*	The page address of a video is the address of a page that refuses to
				be framed, and what somebody pastes into url= is that one. A YouTube
				or Vimeo page is therefore turned into the provider's own player, the
				same as youtube=/vimeo= would; a player address - which carries its
				own start and autoplay parameters - and every other host stay as
				they were written	*/
			foreach( self::PROVIDERS as $provider => $definition ) {

				$video = in_array( strtolower( $parts['host'] ), $definition['hosts'], true ) === true ? self::video( $provider, $url ) : null;

				if( $video !== null && $video['embed'] === false )
					return self::_playerSrc( $provider, $video );
			}

			return $url;
		}

		/**
		 *	The video an id or an address names at one provider. What somebody
		 *	pastes is a watch page, a short link, a share link with a time code
		 *	and a tracking parameter on it, or the id alone; the one thing taken
		 *	from it is the id - and, for an unlisted Vimeo video, the hash that
		 *	makes it reachable. Everything else, time codes and tracking
		 *	included, is dropped, so only an id that passes the provider's own
		 *	pattern and a hex hash can reach an iframe's src.
		 *
		 *	An address is read only where its host is one of the provider's own
		 *	(compared whole, lower-cased), over http or https, with no
		 *	credentials and no port: youtube.com.evil.example and
		 *	www.youtube.com@evil.example are not YouTube. A playlist, a channel,
		 *	a search and every other provider's address name no video.
		 *
		 *	@param		string		$provider			A key of PROVIDERS
		 *	@param		string		$value				An id, or an address copied from the browser
		 *
		 *	@return 	array|null						[ 'id' => string, 'hash' => string ('' where there is none),
		 *																	'embed' => bool (the address already was the provider's player) ],
		 *																or null where it names no video
		 */
		public static function video( string $provider, string $value ): ?array {

			$definition = self::PROVIDERS[$provider] ?? null;

			if( $definition === null )
				return null;

			// The attribute may come out of an html field, where '&' is '&amp;' -
			// and '?feature=share&amp;v=...' has no v otherwise
			$value = trim( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			if( preg_match( $definition['pattern'], $value ) === 1 )
				return [ 'id' => $value, 'hash' => '', 'embed' => false ];

			$parts = parse_url( $value );

			if( is_array( $parts ) === false || in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), [ 'http', 'https' ], true ) === false )
				return null;

			if( isset( $parts['user'] ) === true || isset( $parts['pass'] ) === true || isset( $parts['port'] ) === true )
				return null;

			$host = strtolower( (string) ( $parts['host'] ?? '' ) );

			if( in_array( $host, $definition['hosts'], true ) === false )
				return null;

			$segments = array_values( array_filter( explode( '/', (string) ( $parts['path'] ?? '' ) ), static fn( string $segment ): bool => $segment !== '' ) );
			$queryString = (string) ( $parts['query'] ?? '' );

			$id			= '';
			$hash		= '';
			$embed	= false;

			if( $provider === 'youtube' ) {

				if( $host === 'youtu.be' )
					$id = $segments[0] ?? '';
				elseif( ( $segments[0] ?? '' ) === 'watch' )
					$id = self::_queryValue( $queryString, 'v' );
				elseif( in_array( $segments[0] ?? '', [ 'embed', 'shorts', 'live', 'v' ], true ) === true )
					$id = $segments[1] ?? '';

				$embed = ( $segments[0] ?? '' ) === 'embed';

				// /embed/videoseries is a playlist, and its "id" has an id's shape
				if( $id === 'videoseries' )
					return null;
			}
			else {

				if( $host === 'player.vimeo.com' ) {
					$id			= ( $segments[0] ?? '' ) === 'video' ? ( $segments[1] ?? '' ) : '';
					$embed	= true;
				}
				elseif( preg_match( '/^[0-9]+$/D', $segments[0] ?? '' ) === 1 ) {
					$id		= $segments[0];
					$hash	= $segments[1] ?? '';
				}
				// /channels/<name>/<id>, /groups/<name>/videos/<id>, /showcase/<name>/video/<id>
				elseif( ( $segments[0] ?? '' ) === 'channels' )
					$id = $segments[2] ?? '';
				elseif( ( $segments[0] ?? '' ) === 'groups' && ( $segments[2] ?? '' ) === 'videos' )
					$id = $segments[3] ?? '';
				elseif( ( $segments[0] ?? '' ) === 'showcase' && ( $segments[2] ?? '' ) === 'video' )
					$id = $segments[3] ?? '';

				if( $hash === '' )
					$hash = self::_queryValue( $queryString, 'h' );

				if( preg_match( self::VIMEO_HASH, $hash ) !== 1 )
					$hash = '';
			}

			return preg_match( $definition['pattern'], $id ) === 1 ? [ 'id' => $id, 'hash' => $hash, 'embed' => $embed ] : null;
		}

		/**
		 *	One value of a query string, read the plain way: the first
		 *	"name=value" pair of that name, up to the next '&' or '#'. Not
		 *	parse_str(), which reads "v.x" and "v x" as "v_x", turns "v[]=" into
		 *	an array and has a limit on how many variables it takes - none of
		 *	which an address pasted from a browser needs - and which was the
		 *	reason the value was checked for being a string at all. The result
		 *	is not decoded: an id has no percent sign in it, and one that does
		 *	is not an id
		 *
		 *	@param		string		$query				The query string, without the '?'
		 *	@param		string		$name					The parameter, a plain word
		 *
		 *	@return 	string								The value, '' where there is none
		 */
		private static function _queryValue( string $query, string $name ): string {

			return preg_match( '/(?:^|&)'. preg_quote( $name, '/' ). '=([^&#]*)/', $query, $found ) === 1 ? $found[1] : '';
		}

		/**
		 *	The host an address will talk to, for the sentence on the surface:
		 *	"external media from ..." is the one fact that decides whether
		 *	somebody presses
		 *
		 *	@param		string		$src
		 *
		 *	@return 	string								A hostname without "www."
		 */
		public static function host( string $src ): string {

			$host = (string) ( parse_url( $src, PHP_URL_HOST ) ?: '' );

			return str_starts_with( $host, 'www.' ) === true ? substr( $host, 4 ) : $host;
		}

		/**
		 *	The Consent category that releases an embed without a press, as the
		 *	settings have it - the kernel already holds a stored value to the
		 *	manifest's pattern. '' only where the project emptied the setting,
		 *	and every embed then waits for a press; outside an installation that
		 *	has this feature the answer is the manifest's default, 'external'
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function category( array &$appData ): string {

			return (string) \Nino\Features::setting( $appData, 'embed', 'category', 'external' );
		}

		/**
		 *	Whether one press releases the rest of that provider's embeds on the
		 *	same page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function remembers( array &$appData ): bool {
			return \Nino\Features::setting( $appData, 'embed', 'remember', false ) === true;
		}

		/**
		 *	The address a provider's player is framed from, for a video()
		 *
		 *	@param		string		$provider			A key of PROVIDERS
		 *	@param		array			$video				What video() returned
		 *
		 *	@return 	string
		 */
		private static function _playerSrc( string $provider, array $video ): string {

			$src = sprintf( self::PROVIDERS[$provider]['src'], $video['id'] );

			// An unlisted Vimeo video is reachable with its hash only
			return $video['hash'] === '' ? $src : str_replace( '?', '?h='. $video['hash']. '&', $src );
		}

		/**
		 *	The picture on the surface, or nothing - which is a surface with the
		 *	play mark and the two sentences on the page's own ground, and is
		 *	what most embeds get. It is a picture slot of the project, read back
		 *	from the escaped reference the register wrote: a name that is no slot
		 *	is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$reference		The attribute, escaped
		 *
		 *	@return 	string
		 */
		private static function _poster( array &$appData, string $reference ): string {

			$picture = \Nino\Images::picture( $appData, html_entity_decode( $reference, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), null );

			return $picture === null ? '' : str_replace( '[[src]]', \Nino\Components::escape( $picture['url'] ), self::$html['poster'] );
		}

		/**
		 *	A word of the surface as it is written into html: the text of the key in the language of the page, escaped.
		 *	A key nobody has a text for is the key
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The part and the name below /feature/embed/
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $name ): string {

			$key = '/feature/embed/'. $name;

			return \Nino\Components::escape( \Nino\Html::resolveTextfill( $appData, $key ) ?? '[['. $key. ']]' );
		}
	}

}
