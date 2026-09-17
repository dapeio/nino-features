<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Copy				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Copy							A copy button on the one thing that would otherwise be
	 *										typed out by hand: an IBAN, a voucher code, a command,
	 *										a block of configuration.
	 *
	 *										A shortcode with a body rather than an attribute on
	 *										markup somebody wrote. Two reasons, and the second is
	 *										the one that settled it: what is copied is content,
	 *										and content belongs between two tags rather than
	 *										inside an attribute where a quote would end it - and
	 *										the button's three words are text fills, which only
	 *										something rendered on the server can resolve. A static
	 *										asset cannot read a fill (docs/development.md, "Assets
	 *										Are Not Templates"), so a button this feature did not
	 *										render would be a button with no word in the project's
	 *										language.
	 *
	 *										What is shown and what is copied are two things, and
	 *										value="..." is where they part: a number grouped so it
	 *										can be read, copied without the grouping. Where it is
	 *										not given, what is copied is exactly what stands there.
	 *
	 *										Without JavaScript the button is not shown at all and
	 *										what is left is the text, selectable, which is what it
	 *										was before. A button that copies nothing is worse than
	 *										no button.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Copy {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Copy/templates';

		/**
		 *	Register the shortcode and ship the two static files
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'copy', [ self::class, 'doShortcode' ] );

			/*	A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
				resolves against the project root (\Nino\Filesystem::path()'s
				fallback) - the same way '/_nino/Nino.css' already does for the
				kernel's own bundle	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Copy/assets/copy.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Copy/assets/copy.js' );
		}

		/**
		 *	[copy]...[/copy]
		 *
		 *	Renders nothing where there is nothing between the tags: a copy
		 *	button for an empty string is a control that does something nobody
		 *	can tell apart from it doing nothing.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes, 'content' among them
		 *
		 *	@return 	string								The text and its button, or ''
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			/*	A shortcode body only arrives as 'content' when it is not empty -
				see \Nino\Html::addShortcode() - so an [copy][/copy] with nothing in
				it never gets here with anything to show	*/
			$content = trim( (string) ( $args['content'] ?? '' ) );

			if( $content === '' )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			// Rendered, then escaped: a body is editor text that may hold a
			// textfill, and what comes out of the fill engine is still text
			$shown = $safe( \Nino\Html::renderHtml( $appData, $content ) );

			/*	What is copied, where that is not what is shown. Held to the same
				rendering and escaping: it ends up in an attribute, and an attribute
				is where an unescaped quote stops being text	*/
			$value = trim( (string) ( $args['value'] ?? '' ) );
			$value = $value === '' ? '' : $safe( \Nino\Html::renderHtml( $appData, $value ) );

			/*	What the button is for, for a reader who cannot see what it stands
				beside. Not the same as the word on it: "Copy" is what it does,
				"IBAN" is what it copies, and a screen reader needs both	*/
			$label = trim( (string) ( $args['label'] ?? '' ) );
			$label = $label === '' ? '' : $safe( \Nino\Html::renderHtml( $appData, $label ) );

			$block = in_array( 'block', $args, true );

			return str_replace(
				[ '[[modifier]]', '[[tag]]', '[[value]]', '[[named]]', '[[shown]]' ],
				[
					( $block === true ? ' nino-copy--block' : '' ),
					( $block === true ? 'pre' : 'span' ),
					( $value === '' ? '' : ' data-copy-value="'. $value. '"' ),
					( $label === '' ? '' : ' data-copy-named="'. $label. '"' ),
					$shown,
				],
				self::template( $appData, 'copy' )
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
