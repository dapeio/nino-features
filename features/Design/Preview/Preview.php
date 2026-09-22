<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Preview		see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Preview						What a setup looks like, before it is the site's.
	 *
	 *										One page that uses every class a part set can reach, in
	 *										the markup a real page produces, with the chosen header
	 *										and footer around it and the compiled stylesheet over it.
	 *										Nothing here writes: a preview is a question, and the
	 *										answer to it must not already be the answer on disk.
	 *
	 *										This class holds no markup. The page and the specimen are
	 *										templates/preview-document.tpl and
	 *										templates/preview-specimen.tpl, read through
	 *										\Nino\Filesystem and filled with str_replace() - see
	 *										AGENTS.md, "Markup belongs in a template". What is left
	 *										here is which template, what goes in it, and where the
	 *										frames around it come from.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Preview {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Design/templates';

		/*	The one picture the specimen shows, as a data uri rather than a file.
			An article set is judged on the space around an image, not on the
			image - and shipping one would mean choosing a photograph, which is a
			design decision this page must not make for whoever is looking at it.
			A uri also needs no route: the panel renders inside /_admin, where
			/images/… is not the site's */
		public const string PLACEHOLDER = 'data:image/svg+xml;charset=utf-8,'
			. '%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 640 420%22 role=%22img%22 aria-label=%22Placeholder%22%3E'
			. '%3Crect width=%22640%22 height=%22420%22 fill=%22%23d8dee6%22/%3E'
			. '%3Cpath d=%22M0 300l170-130 130 100 110-80 230 170z%22 fill=%22%23b9c3cf%22/%3E'
			. '%3Ccircle cx=%22480%22 cy=%22110%22 r=%2246%22 fill=%22%23c9d2dc%22/%3E%3C/svg%3E';

		/*	The demonstration copy. Text rather than markup, and here rather than
			written into the specimen because each line stands in ten places in it
			and is meant to be changed in one */
		private const string LOREM = 'What a set decides only shows on real text: where the title sits, how far the subtitle stands off it, and whether the line stays calm once it gets long.';
		private const string SHORT = 'Short enough to show the alignment.';

		/**
		 *	The specimen with the chosen frames around it, as html+ for the
		 *	kernel to render.
		 *
		 *	The frames are inlined rather than included as [template
		 *	/templates/theme.header]: a preview shows a header nobody has applied
		 *	yet, and the file that include names is the one currently on disk.
		 *	Reading the library directly is the difference between "what this
		 *	would look like" and "what it looks like"
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		$setup				A normalised setup (see Setup::normalize())
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								Html+
		 */
		public static function markup( array &$appData, string $libraryDir, array $setup, array &$notes = [] ): string {

			return self::frame( $libraryDir, $setup, 'header', $notes )
				. "\n". self::specimen( $appData )
				. "\n". self::frame( $libraryDir, $setup, 'footer', $notes );
		}

		/**
		 *	The markup half of a frame, straight out of the library
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$part					'header' or 'footer'
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								Html+, or nothing at all
		 */
		public static function frame( string $libraryDir, array $setup, string $part, array &$notes = [] ): string {

			$set 			= (string) ( $setup['parts'][$part]['set'] ?? '' );
			$template = Setup::file( $libraryDir, $part, $set, 'template' );

			if( $template === '' ) {
				$notes[] = 'no template for "'. $part. '" set "'. $set. '" - the preview shows the page without it';
				return '';
			}

			return (string) file_get_contents( $template );
		}

		/**
		 *	The stylesheet the specimen is shown under - the feature's own
		 *	compiler, not a second assembly beside it. What is previewed is byte
		 *	for byte what applying would write, and the compiler is exercised
		 *	every time somebody looks at a design rather than only when its test
		 *	runs
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$public				What [[/nino/public]] resolves to here
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								The compiled css
		 */
		public static function css( array $setup, string $libraryDir, string $public, array &$notes = [] ): string {

			/*	base.css's @font-face urls carry the fill every stylesheet in a
				project carries, and a preview is not written through the asset
				bundler that would resolve it (see Modules\Assets). Unresolved, the
				three webfaces silently do not load - and a design shown in the
				wrong typeface is worse than no preview at all */
			return str_replace( '[[/nino/public]]', $public, Compiler::compile( $setup, $libraryDir, $notes ) );
		}

		/**
		 *	The page around the render: the shell, with the head the caller
		 *	hands in - the panel carries its stylesheets inline, because an
		 *	iframe's srcdoc is the whole document it has
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The render's locale - its first two letters
		 *																		become the lang attribute
		 *	@param		string		$head					Stylesheets, as tags
		 *	@param		string		$body					Everything inside <body>
		 *	@param		string		$tail					Scripts, as tags
		 *
		 *	@return 	string								A complete html document
		 */
		public static function document( array &$appData, string $locale, string $head, string $body, string $tail ): string {

			$template = self::template( $appData, 'preview-document' );

			/*	Without the shell there is still a preview: an iframe's srcdoc and
				a browser both build the document around a fragment, so what is
				lost is the lang attribute and the line that says no to crawlers -
				not the page. Answering a hand-built shell instead would put the
				markup back in here for the one case it is least needed	*/
			if( $template === '' )
				return $head. "\n". $body. "\n". $tail;

			/*	The body last, and that is not cosmetic: str_replace() works
				through its arrays in order, so every token after it would be
				looked for in the rendered page as well - and the page is the one
				string here this class did not write	*/
			return str_replace(
				[ '[[lang]]', '[[head]]', '[[tail]]', '[[body]]' ],
				[ htmlspecialchars( substr( $locale, 0, 2 ), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), $head, $tail, $body ],
				$template
			);
		}

		/**
		 *	The specimen, without its frames - templates/preview-specimen.tpl
		 *	with its three tokens filled in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string								Html+, rendered through the kernel by the caller
		 */
		public static function specimen( array &$appData ): string {

			return str_replace(
				[ '[[lorem]]', '[[short]]', '[[placeholder]]' ],
				[ self::LOREM, self::SHORT, self::PLACEHOLDER ],
				self::template( $appData, 'preview-specimen' )
			);
		}

		/**
		 *	One of this feature's own templates, read the way a project's are
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else - kept to a slug anyway,
			// so the one thing this method could ever be turned into (a read of
			// something outside the feature) is not possible from here either
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
