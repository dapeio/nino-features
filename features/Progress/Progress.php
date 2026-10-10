<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Progress		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Progress					How far through a long text the reader is, as a bar
	 *										across the top of it.
	 *
	 *										What is measured is a choice worth making: the whole
	 *										page counts the footer as part of the article, so
	 *										"finished" arrives after the last paragraph rather
	 *										than at it. data-progress-of="#article" measures that
	 *										one element instead, and the bar is full when the text
	 *										is.
	 *
	 *										The bar is one empty element and the two static files
	 *										below do the rest, so what PHP writes is that element,
	 *										with the two data attributes progress.js reads: the
	 *										component the Builder places on Nino 1.6 and the
	 *										shortcode where Nino is older, which are one call and
	 *										one markup. A project's own template may still carry
	 *										the div by hand. No route, no settings, no state:
	 *										where the bar sits and what it measures belong to the
	 *										one place it is written, and a static asset could not
	 *										read a site-wide setting anyway (see feature.php).
	 *
	 *										Every value is escaped, and a bracket in it is written
	 *										as a character reference: the markup is rendered once
	 *										more, and a selector, a label or a class somebody typed
	 *										must not open a fill or a shortcode there.
	 *
	 *										progress.css/progress.js reach the browser the way the
	 *										kernel ships its own Nino.css/Nino.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): added
	 *										to the SAME site-wide bundle targets the base
	 *										install's html-header.tpl/html-footer.tpl already load
	 *										on every page.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Progress {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Progress/templates';

		// What a bar measures where the call names nothing: the element that holds
		// the article, and the default of the component's "of" in feature.php
		public const string OF_DEFAULT = '#article';

		/**
		 *	Register the shortcode where no component is, and ship
		 *	progress.css/progress.js into the site's own asset bundles - see this
		 *	class' own docblock for why the site-wide targets and not a bundle of
		 *	this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			/*	Nino 1.6 has registered [progress] as a component from the manifest
				before this runs (\Nino\Features::registerComponents()), and a second
				registration here would take it back from the wrapper that fills in
				the defaults. Nino 1.5 ignores the manifest key and needs it	*/
			$components = class_exists( '\\Nino\\Modules\\Components' ) === true ? \Nino\Modules\Components::components( $appData ) : [];

			if( isset( $components['progress'] ) === false )
				\Nino\Html::addShortcode( $appData, 'progress', [ self::class, 'doShortcode' ] );

			/*	The virtual '/features/...' prefix resolves against
				\Nino\Features::dir() (\Nino\Filesystem::FEATURES_DIR), the same way
				TEMPLATES above is read - so '/features/Progress/assets/...' reaches
				this feature's own copy wherever NINO_FEATURES_DIR put the features
				directory, and a project that moved it has nothing to say in
				'/nino/html/assets'	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Progress/assets/progress.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Progress/assets/progress.js' );
		}

		/**
		 *	[progress] as the Components module hands it over, the Builder's
		 *	way: every attribute is there, an empty one where nothing was
		 *	written, and doShortcode() reads them as it reads a hand-written call
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments (see \Nino\Modules\Components::dispatch())
		 *
		 *	@return 	string								What doShortcode() renders for them
		 */
		public static function componentProgress( array &$appData, array $args ): string {

			return self::doShortcode( $appData, $args );
		}

		/**
		 *	[progress of="#article" label="Lesefortschritt"] - the element the
		 *	browser turns into the bar.
		 *
		 *	One empty div with the two attributes progress.js reads. A call without
		 *	"of" measures OF_DEFAULT, which is what the wrapper of Nino 1.6 fills in
		 *	for it; an "of" that is there and empty measures the whole page. A
		 *	"label" that is empty hides the bar from a screen reader rather than
		 *	announcing it unnamed.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes (see feature.php's manual)
		 *
		 *	@return 	string								The element
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$of = trim( (string) ( $args['of'] ?? self::OF_DEFAULT ) );
			$label = trim( (string) ( $args['label'] ?? '' ) );

			return str_replace(
				[ '[[class]]', '[[of]]', '[[label]]' ],
				[ self::_class( $args ), self::_escape( $of ), self::_escape( $label ) ],
				self::template( $appData, 'progress' )
			);
		}

		/**
		 *	The class of one's own a call adds to the bar, with the space in front
		 *	of it that the template leaves out - escaped, and with its brackets as
		 *	character references, because the markup is rendered once more
		 *
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	string								'' or ' my-class'
		 */
		private static function _class( array $args ): string {

			$class = trim( (string) ( $args['class'] ?? '' ) );

			return $class === '' ? '' : ' '. self::_escape( $class );
		}

		/**
		 *	A value made safe to write into an attribute of the template: escaped,
		 *	and with its brackets as character references, because the markup is
		 *	rendered once more and a selector or a label somebody typed must not
		 *	open a fill or a shortcode there
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function _escape( string $value ): string {

			return str_replace( '[', '&#91;', htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) );
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

			// A name from this class and nowhere else, and held to a slug anyway
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
