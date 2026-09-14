<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Compare			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Compare						Two pictures of the same thing, one over the other,
	 *										under a divider the visitor moves.
	 *
	 *										The divider is an <input type="range">. Not a <div>
	 *										with pointer handlers on it: a range is draggable with
	 *										a mouse, with a finger, and with the arrow keys; it
	 *										announces itself and its value to a screen reader; and
	 *										it is one line of markup instead of a gesture library
	 *										that would have to be told about all three. What the
	 *										script does is read its value into a custom property -
	 *										the clipping is the stylesheet's.
	 *
	 *										Without JavaScript the same markup is two captioned
	 *										pictures under one another. That is not a fallback
	 *										bolted on: the layout is what the markup reads like,
	 *										and compare.js adds a class that makes the two into a
	 *										stack. A visitor whose browser never runs it sees both
	 *										pictures and both captions, which is what the pair was
	 *										there to show.
	 *
	 *										compare.css/compare.js reach the browser the way the
	 *										kernel ships its own Nino.css/Nino.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): added to
	 *										the SAME site-wide bundles the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page, not a bundle of this feature's own.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Compare {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Compare/templates';

		// The shapes a box can have, as the class suffix a project writes and
		// the ratio compare.css gives it. Named rather than free, because a
		// ratio typed into a template is a ratio nobody checks
		public const array RATIOS = [ '16-9', '4-3', '1-1', '3-2' ];
		public const string RATIO_DEFAULT = '4-3';

		// Where the divider stands when the page opens. In the middle, which
		// says "there are two of these" before anything is moved
		public const int START_DEFAULT = 50;

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

			\Nino\Html::addShortcode( $appData, 'compare', [ self::class, 'doShortcode' ] );

			/*	A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
				resolves against the project root (\Nino\Filesystem::path()'s
				fallback) - the same way '/_nino/Nino.css' already does for the
				kernel's own bundle, so '/features/Compare/assets/...' reaches this
				feature's own copy as long as features/ sits where it does by
				default (NINO_FEATURES_DIR unmoved); see the README's "Asset
				bundling" note for the relocated case	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Compare/assets/compare.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Compare/assets/compare.js' );
		}

		/**
		 *	[compare before="..." after="..."] - the pair itself.
		 *
		 *	Renders nothing where either picture is missing: one half of a
		 *	comparison is a picture nobody asked for, and a divider with
		 *	nothing on one side of it is a control that cannot mean anything.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes (see feature.php's manual)
		 *
		 *	@return 	string								The pair, or '' where a picture is not named
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$before	= self::image( (string) ( $args['before'] ?? '' ) );
			$after	= self::image( (string) ( $args['after'] ?? '' ) );

			if( $before === '' || $after === '' )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

			$ratio = (string) ( $args['ratio'] ?? '' );
			$ratio = in_array( $ratio, self::RATIOS, true ) === true ? $ratio : self::RATIO_DEFAULT;

			/*	The two captions and the description are editor text that may be a
				textfill, so each is rendered first and escaped after - what comes
				out of the fill engine is still text. A side that is not named falls
				back to the fill the install unit wrote, so the pair is labelled in
				the project's own language either way	*/
			$beforeLabel	= self::_label( $appData, (string) ( $args['before-label'] ?? '' ), 'before' );
			$afterLabel		= self::_label( $appData, (string) ( $args['after-label'] ?? '' ), 'after' );

			$alt = trim( (string) ( $args['alt'] ?? '' ) );
			$alt = $alt === '' ? '' : $safe( \Nino\Html::renderHtml( $appData, $alt ) );

			return str_replace(
				[ '[[ratio]]', '[[start]]', '[[before]]', '[[after]]', '[[alt]]', '[[beforelabel]]', '[[afterlabel]]' ],
				[
					$ratio,
					(string) self::start( $args ),
					$safe( \Nino\Images::getUrl( $appData, $before ) ),
					$safe( \Nino\Images::getUrl( $appData, $after ) ),
					$alt,
					$beforeLabel,
					$afterLabel,
				],
				self::template( $appData, 'compare' )
			);
		}

		/**
		 *	Where the divider stands when the page opens, held to the range the
		 *	control itself has - a value outside it would be a thumb the
		 *	visitor cannot see, on a control they have not touched yet
		 *
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	int										0 to 100
		 */
		public static function start( array $args ): int {

			$start = $args['start'] ?? null;

			if( is_string( $start ) === false || preg_match( '/^[0-9]{1,3}$/', $start ) !== 1 )
				return self::START_DEFAULT;

			$start = (int) $start;

			return ( $start >= 0 && $start <= 100 ) ? $start : self::START_DEFAULT;
		}

		/**
		 *	One of the two filenames, or '' where it is not one this project
		 *	may serve
		 *
		 *	@param		string		$name					A filename below the project's images
		 *
		 *	@return 	string
		 */
		public static function image( string $name ): string {

			$name = trim( $name );

			/*	\Nino\Images only ever hands out names below its own directory, but
				these two come out of a template somebody wrote, and a name that
				climbs out of that directory would be a picture from wherever it
				climbed to	*/
			if( $name === '' || str_contains( $name, '..' ) === true || str_starts_with( $name, '/' ) === true )
				return '';

			return $name;
		}

		/**
		 *	What one side is called: what the shortcode said, or the fill the
		 *	install unit wrote into the project's own text files
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$given				What the shortcode said, possibly nothing
		 *	@param		string		$side					'before' or 'after'
		 *
		 *	@return 	string								Escaped text, or a fill for the kernel to resolve
		 */
		private static function _label( array &$appData, string $given, string $side ): string {

			$given = trim( $given );

			if( $given === '' )
				return '[[/compare/'. $side. ']]';

			return htmlspecialchars( \Nino\Html::renderHtml( $appData, $given ), ENT_QUOTES, 'UTF-8' );
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
