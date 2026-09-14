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
	 *										surface names one of its own with poster="..." and it is
	 *										served from the project's own images.
	 *
	 *										embed.css/embed.js reach the browser the way the kernel
	 *										ships its own Nino.css/Nino.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): added to
	 *										the SAME site-wide bundles the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page, not a bundle of this feature's own.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Embed {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Embed/templates';

		/*	The two providers worth knowing by name, because their embed address
			is not the address anybody has in their clipboard - what a person
			copies is a watch page, and turning that into an embed is the step
			this saves them.

			'youtube' is youtube-nocookie.com rather than youtube.com: it is
			Google's own no-cookie host, the same video, and there is no reason
			to offer the other one. 'vimeo' carries dnt=1, which is Vimeo's own
			do-not-track flag. Neither makes an embed consent-free - both still
			see the visitor's address once the frame is there, which is why
			nothing here loads by itself	*/
		public const array PROVIDERS = [
			'youtube'	=> [ 'pattern' => '/^[A-Za-z0-9_-]{6,20}$/',	'src' => 'https://www.youtube-nocookie.com/embed/%s?rel=0' ],
			'vimeo'		=> [ 'pattern' => '/^[0-9]{5,15}$/',					'src' => 'https://player.vimeo.com/video/%s?dnt=1' ],
		];

		// The shapes a box can have, as the class suffix a project writes and
		// the ratio embed.css gives it. Named rather than free, because a
		// ratio typed into a template is a ratio nobody checks
		public const array RATIOS = [ '16-9', '4-3', '1-1', '21-9' ];
		public const string RATIO_DEFAULT = '16-9';

		/**
		 *	Register the shortcode and ship the two static files - see this
		 *	class' own docblock for why the site-wide bundles and not one of
		 *	this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'embed', [ self::class, 'doShortcode' ] );

			/*	A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
				resolves against the project root (\Nino\Filesystem::path()'s
				fallback) - the same way '/_nino/Nino.css' already does for the
				kernel's own bundle, so '/features/Embed/assets/...' reaches this
				feature's own copy as long as features/ sits where it does by
				default (NINO_FEATURES_DIR unmoved); see the README's "Asset
				bundling" note for the relocated case	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Embed/assets/embed.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Embed/assets/embed.js' );
		}

		/**
		 *	[embed youtube="..."], [embed vimeo="..."], [embed url="https://..."]
		 *
		 *	Renders nothing at all where no address can be made of what was
		 *	written: an [embed] with a typo is a shortcode that cannot mean
		 *	anything, and a box with no frame behind it is worse on a page than
		 *	no box - it is a thing to press that never does anything.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes (see feature.php's manual)
		 *
		 *	@return 	string								The surface, or '' where the address is not one
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$src = self::source( $args );

			if( $src === '' )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

			$ratio = (string) ( $args['ratio'] ?? '' );
			$ratio = in_array( $ratio, self::RATIOS, true ) === true ? $ratio : self::RATIO_DEFAULT;

			/*	The title is the accessible name of the frame that will be there,
				and the words on the surface before it is. A frame with no name is
				"iframe" to a screen reader, so there is a fallback fill rather than
				an empty string - but a project that writes one says what this
				particular video is, which no fill can	*/
			$title = trim( (string) ( $args['title'] ?? '' ) );

			// Rendered, then escaped: a title is editor text that may be a
			// textfill, and what comes out of the fill engine is still text
			$title = $title === '' ? '' : $safe( \Nino\Html::renderHtml( $appData, $title ) );

			$category = self::category( $appData );

			// The poster last of the three built pieces, and the whole block last
			// of all: str_replace() works through its arrays in order, so a token
			// after built markup would be looked for inside it as well
			return str_replace(
				[ '[[ratio]]', '[[src]]', '[[host]]', '[[consent]]', '[[remember]]', '[[title]]', '[[label]]', '[[poster]]' ],
				[
					$ratio,
					$safe( $src ),
					$safe( self::host( $src ) ),
					$safe( $category ),
					( self::remembers( $appData ) === true ? ' data-embed-remember="1"' : '' ),
					( $title === '' ? '[[/embed/frame]]' : $title ),
					( $title === '' ? '[[/embed/load]]' : $title ),
					self::_poster( $appData, (string) ( $args['poster'] ?? '' ) ),
				],
				self::template( $appData, 'embed' )
			);
		}

		/**
		 *	The embed address one set of shortcode attributes names, or '' where
		 *	they name none. A provider id is checked against that provider's own
		 *	shape rather than pasted in: what goes into the markup here ends up
		 *	in an iframe's src once it is released
		 *
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	string								An https url, or ''
		 */
		public static function source( array $args ): string {

			foreach( self::PROVIDERS as $provider => $definition ) {

				$id = trim( (string) ( $args[$provider] ?? '' ) );

				if( $id === '' )
					continue;

				return preg_match( $definition['pattern'], $id ) === 1
					? sprintf( $definition['src'], $id )
					: '';
			}

			$url = trim( (string) ( $args['url'] ?? '' ) );

			if( $url === '' )
				return '';

			/*	https and nothing else. Not because http would be an attack - a
				template is the project's own file - but because an embed served
				over http on an https page is a frame the browser refuses anyway,
				and refusing it here says so at the one moment somebody is looking	*/
			$parts = parse_url( $url );

			if( is_array( $parts ) === false || ( $parts['scheme'] ?? '' ) !== 'https' || ( $parts['host'] ?? '' ) === '' )
				return '';

			return $url;
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
		 *	settings have it. '' where the project switched that off, and also
		 *	where this feature is read outside an installation that has it -
		 *	the safe half is the one that needs no configuration
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function category( array &$appData ): string {

			$category = (string) \Nino\Features::setting( $appData, 'embed', 'category', 'external' );

			return preg_match( '/^[a-z0-9_-]*$/', $category ) === 1 ? $category : '';
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
		 *	The picture on the surface, or nothing - which is a surface with the
		 *	play mark and the two sentences on the page's own ground, and is
		 *	what most embeds get
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$poster				A filename below the project's images
		 *
		 *	@return 	string
		 */
		private static function _poster( array &$appData, string $poster ): string {

			$poster = trim( $poster );

			/*	\Nino\Images only ever hands out names below its own directory, but
				this one comes out of a template somebody wrote, and a name that
				climbs out of that directory would be a picture from wherever it
				climbed to	*/
			if( $poster === '' || str_contains( $poster, '..' ) === true || str_starts_with( $poster, '/' ) === true )
				return '';

			return str_replace(
				'[[src]]',
				htmlspecialchars( \Nino\Images::getUrl( $appData, $poster ), ENT_QUOTES, 'UTF-8' ),
				self::template( $appData, 'embed-poster' )
			);
		}

		/**
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway:
			// the one thing this could otherwise be turned into is a read of
			// something outside the feature
			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}

}
