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
		 *	Register the shortcode where the Components module does not own it;
		 *	where it does - the manifest declares [copy] as a component - the
		 *	names its schema cannot carry are renamed ahead of it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			/*	The wrapper of the Components module renders the component, and it
				hands on only the attributes the schema names - and value is a name
				it keeps for itself, a bare flag is no attribute at all, and the text
				between the tags is no source of a component whose source is a text:
				all three are renamed by a callback that runs ahead of it	*/
			if( \Nino\Feature::shortcode( $appData, 'copy', [ self::class, 'doShortcode' ] ) === false )
				\Nino\Callbacks::registerCallback( $appData, '/nino/html/shortcode/copy', [ self::class, 'callbackShortcode' ], 1 );
		}

		/**
		 *	The names the schema cannot carry, renamed: value= as copied, the
		 *	flag block - bare, wherever it stands in the call, or named by one
		 *	of the words of a flag - as block="1", and the text between the tags
		 *	handed on as text=, which is the source a call without a key has.
		 *	Runs ahead of the Components wrapper on '/nino/html/shortcode/copy'
		 *	(priority 1, the wrapper has 5) and changes the arguments in place
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$args				(reference) The arguments of the shortcode
		 *
		 *	@return 	void
		 */
		public static function callbackShortcode( array &$appData, mixed &$args ): void {

			if( is_array( $args ) === false )
				return;

			if( isset( $args['value'] ) === true ) {
				$args['copied'] = $args['value'];
				unset( $args['value'] );
			}

			/*	The documented body is the text a call has no key for. Handed on
				as it is: componentCopy() answers a body with doShortcode(), which
				renders and escapes it itself, so what the wrapper makes of this
				copy is not used	*/
			if( isset( $args['text'] ) === false && trim( (string) ( $args['content'] ?? '' ) ) !== '' )
				$args['text'] = (string) $args['content'];

			// The same reading doShortcode() has of the flag
			if( \Nino\Feature::flag( $args, 'block' ) === true )
				$args['block'] = '1';
		}

		/**
		 *	[copy] as the Components module hands it over, the Builder's way:
		 *	every attribute is there, an empty one where nothing was written,
		 *	and doShortcode() reads them under the names it has - the line
		 *	made a block by the flag it has always been. The text is the
		 *	source: a key, or text="..." the Builder writes, which arrives
		 *	resolved and escaped and is drawn as it is - rendered again it
		 *	would be read for fills a second time. A body between the tags,
		 *	the form of the manual, goes to doShortcode() whole
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments (see \Nino\Modules\Components::dispatch())
		 *
		 *	@return 	string								What the shortcode renders for them
		 */
		public static function componentCopy( array &$appData, array $args ): string {

			$call = [ 'content' => $args['content'], 'value' => $args['copied'], 'label' => $args['label'], 'class' => $args['class'] ];

			if( $args['block'] === '1' )
				$call[] = 'block';

			if( trim( $args['content'] ) !== '' )
				return self::doShortcode( $appData, $call );

			// A line with nothing to show is none, as with an empty body
			return trim( $args['value'] ) === '' ? '' : self::_draw( $appData, $call, $args['value'] );
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
			return self::_draw( $appData, $args, $safe( \Nino\Html::renderHtml( $appData, $content ) ) );
		}

		/**
		 *	The line: what is shown, already escaped, and the button that copies it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes
		 *	@param		string		$shown				The text of the line, escaped - a key or a text= has its brackets turned into entities, a body is only escaped
		 *
		 *	@return 	string								The text and its button
		 */
		private static function _draw( array &$appData, array $args, string $shown ): string {

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

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

			/*	Feature::flag() reads a bare flag where the shortcode syntax puts it:
				a positional argument, under an integer key (\Nino\Html::_doShortcode()
				writes a name with no value that way and a name="value" under its
				own name). Asking the whole array read the named ones' values too, so
				a body, a value= or a label= that happened to say "block" turned
				the line into a block	*/
			$block = \Nino\Feature::flag( $args, 'block' );

			return str_replace(
				[ '[[modifier]]', '[[classes]]', '[[value]]', '[[named]]', '[[shown]]' ],
				[
					( $block === true ? ' nino-copy--block' : '' ),
					\Nino\Feature::classes( $args ),
					( $value === '' ? '' : ' data-copy-value="'. $value. '"' ),
					( $label === '' ? '' : ' data-copy-named="'. $label. '"' ),
					$shown,
				],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'copy' )
			);
		}
	}

}
