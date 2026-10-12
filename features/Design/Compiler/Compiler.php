<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Compiler		see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Compiler					Turns a setup into the one stylesheet a project's look is,
	 *										in the order the cascade needs it: the tokens, the solved
	 *										palette over them, the type, the roles those tokens are
	 *										assigned to, the root size, the width and the animation of
	 *										the page, the chosen frames, the three chosen sets, and
	 *										last the block that says which step of every ladder is
	 *										live.
	 *
	 *										A set declares what the steps of a knob *are* - the five
	 *										values of a scale, the three of a choice - rather than a
	 *										value a knob then modifies: +1rem behaves differently on
	 *										a default of 5rem than on 2rem, and some scales are not
	 *										linear at all. So the knob never computes: it picks, and
	 *										what it picks is one selection line per token, gathered
	 *										into a single block at the end. CSS cannot compose a
	 *										variable name, which is why that block exists at all.
	 *
	 *										write() refuses a file it did not write. The delivered
	 *										theme.css is explicitly editable by hand, so a project may
	 *										well have something in it the first time this runs, and
	 *										losing that silently is the mistake nobody forgives.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Compiler {

		/*	The compiled sheet marks its own sections. "====" rather than the
			"----" the hand-written stylesheets use, because everything below is
			concatenated library css that may contain anything - base.css carries
			the two dashed markers it falls into its halves by (see HALVES), and a
			marker that cannot be told from its content is not a marker */
		public const string SECTION = '/* ==== %d. %s ==== */';

		// The file a project's look is. Named by the css bundle the wizard
		// seeds (see \Nino\Install\Setup::BASE_STYLESHEETS), so compiling over
		// it needs no bundle change: the entry is already there
		public const string TARGET = '/assets/theme.css';

		// The marker the header carries. Its value is the sha256 of everything
		// below the header - so a file somebody edited no longer matches its
		// own claim, and write() can tell the two apart
		public const string STAMP = 'nino-design-sha256';

		/*	A frame is the other half of a part: header and footer bring markup,
			and compiling a stylesheet for markup the project does not have is how
			a page ends up with v3's css over v1's html. The two templates the base
			install unit delivers are the ones this writes over - and refuses to,
			by the same stamp, when they are not ours */
		public const string FRAME_TARGET = '/templates/frame-%s.tpl';

		/*	The two lines base.css falls into its two halves by: the tokens follow
			the first, the roles the second. What stands above the first - the
			file's own description - is for whoever reads the library and does not
			travel into a project's stylesheet	*/
		private const array HALVES = [ '/* ---- 1. ', '/* ---- 2. ' ];

		/**
		 *	The stylesheet a setup produces, in twelve numbered sections:
		 *
		 *	  1 the tokens          base.css, the --nino-* defaults
		 *	  2 the palette         solved over them, in the shape the mode asks for
		 *	  3 the type            @font-face and the five font roles
		 *	  4 the roles           base.css, which token goes in which role
		 *	  5 the root size
		 *	  6 the page            the width, and the animation as a rule on main
		 *	  7 header, 8 footer    the chosen frames
		 *	  9 content, 10 buttons, 11 components    the chosen sets
		 *	  12 the knob positions
		 *
		 *	A frame or a set the library does not have is left out and noted, and
		 *	the sections after it take the numbers that are free.
		 *
		 *	@param		array 		$setup				A normalised setup (see Setup::normalize())
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								The complete css, header and all
		 */
		public static function compile( array $setup, string $libraryDir, array &$notes = [] ): string {

			$libraryDir = rtrim( $libraryDir, '/' );
			$parts 			= [];
			$selection 	= [];
			$answered 	= [];

			// 1. and 4. the tokens and the roles they are assigned to, which
			// base.css holds as its two halves. The feature's own copy:
			// _admin/install/ is gone from a project by the time this runs
			$base = $libraryDir. '/base.css';
			$tokens = '';
			$roles = '';

			if( is_file( $base ) === false )
				$notes[] = 'the library has no base.css - the compiled sheet carries no tokens at all';
			else {
				$whole = self::_read( $base );
				$first = strpos( $whole, self::HALVES[0] );
				$second = strpos( $whole, self::HALVES[1] );

				if( $first === false || $second === false || $second < $first )
					$notes[] = 'base.css has not the two halves its sections are numbered by - the compiled sheet carries no tokens at all';
				else {
					$tokens = self::_after( $whole, $first, $second );
					$roles = self::_after( $whole, $second, strlen( $whole ) );
				}
			}

			if( $tokens !== '' )
				$parts[] = sprintf( self::SECTION, 1, 'the design tokens' ). "\n". rtrim( $tokens, "\n" ). "\n";

			/*	2. the palette, which is base.css's own surfaces with solved
				values rather than a second vocabulary beside them. It has to
				follow base.css and precede everything else: a part set may reach
				for --nino-alt, and reading a value the block below is about to
				replace is how a set ends up half in one palette and half in the
				other. One palette only is the browser's colour scheme as well, or
				its form controls and scrollbars would stay in the other one	*/
			$colours = is_array( $setup['colours'] ?? null ) ? $setup['colours'] : [];
			$palette = Colours::css( $colours );
			$mode = Colours::normalize( $colours )['mode'];

			if( $mode !== 'auto' )
				$palette .= "\n\n:root {\n\tcolor-scheme: ". $mode. ";\n}";

			$parts[] = sprintf( self::SECTION, 2, 'the palette' ). "\n". $palette;

			// 3. the type: the faces the roles use, then the roles
			$parts[] = sprintf( self::SECTION, 3, 'the type' ). "\n"
				. Fonts::css( is_array( $setup['fonts'] ?? null ) ? $setup['fonts'] : [] );

			if( $roles !== '' )
				$parts[] = sprintf( self::SECTION, 4, 'the roles' ). "\n". rtrim( $roles, "\n" ). "\n";

			// 5. the root size, as the percentage pair Nino.css reads
			$size = Setup::SIZES[ (int) ( $setup['size'] ?? 0 ) ] ?? Setup::SIZES[0];
			$parts[] = sprintf( self::SECTION, 5, 'the root size' ). "\n"
				. ":root { --nino-base-size: ". $size[0]. "; }\n"
				. "@media (min-width: 768px) { :root { --nino-base-size: ". $size[1]. "; } }";

			$parts[] = sprintf( self::SECTION, 6, 'the page' ). "\n". self::page( $setup );

			// 7. and 8. the frames, then 9. to 11. the sets - both in PARTS
			// order, which is the order the cascade wants them in
			$number = 7;

			foreach( Setup::PARTS as $part => $kind ) {

				$set = (string) ( $setup['parts'][$part] ?? '' );

				if( $set === '' )
					continue;

				$file = Setup::file( $libraryDir, $part, $set );

				if( $file === '' ) {
					// A frame without a stylesheet is ordinary - its markup may need
					// none. A set without one is the choice not existing
					if( $kind === 'set' )
						$notes[] = 'no stylesheet for "'. $part. '" set "'. $set. '"';
					continue;
				}

				$css 		= self::_read( $file );
				$ladders = Setup::ladders( $libraryDir, $part, $set );
				$knobs 	= array_keys( $ladders );
				$where 	= [];

				$answered[$part] = $knobs;

				foreach( $knobs as $knob )
					$where[] = $knob. ' '. Setup::step( $setup, $part, $knob );

				$parts[] = sprintf( self::SECTION, $number++, $part. ': '. $set
					. ( $where === [] ? '' : ' ('. implode( ', ', $where ). ')' ) ). "\n". $css;

				/*	One selection line per ladder of every knob the set answers to,
					asking that knob where it stands: the position of the tab the
					set belongs to (see Setup::step()).

					Collected across the tabs rather than written beside each set,
					so the whole knob state is one readable block - and a project
					that removed the feature can still move a knob by editing one
					line */
				foreach( $ladders as $knob => $names )
					foreach( $names as $name ) {
						$token = $part. '-'. $name;
						$selection[$token] = "\t--". $token. ': var(--'. $token. '--'. Setup::step( $setup, $part, $knob ). ');';
					}
			}

			if( $selection !== [] ) {
				ksort( $selection );
				$parts[] = sprintf( self::SECTION, $number, 'the knob positions' ). "\n:root {\n". implode( "\n", $selection ). "\n}";
			}

			$body = implode( "\n\n", $parts ). "\n";

			return self::_header( $setup, $body, $answered ). $body;
		}

		/**
		 *	What the page asks of Nino.css as a whole: how wide it is, and how its
		 *	sections arrive.
		 *
		 *	A width of 0 is the kernel's own and writes nothing; the four around it
		 *	write the maximum width of a row and the space between the viewport
		 *	and it, with the step it takes from 768px - the two widest no maximum
		 *	(none), and the widest of them no space (0) from there. An animation
		 *	writes one rule on main with the custom properties the kernel sets for
		 *	the effect, its strength and its speed, which every viewport animation
		 *	below main inherits - and which the Builder overrides per template at
		 *	its wrap, and a section with an animation class of its own overrides
		 *	again. No effect writes nothing, whatever strength or speed stand
		 *	beside it: they belong to an effect.
		 *
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	string								The rules, or a comment saying there are none
		 */
		public static function page( array $setup ): string {

			$rules 		= [];
			$width 		= Setup::WIDTHS[ (int) ( $setup['width'] ?? 0 ) ] ?? null;
			$motion 	= is_array( $setup['motion'] ?? null ) === true ? $setup['motion'] : [];
			$effect 	= (string) ( $motion['effect'] ?? '' );

			if( $width !== null ) {
				$rules[] = ":root { --grid-max-width: ". $width[0]. "; --grid-gutter: ". $width[1]. "; }";
				$rules[] = "@media (min-width: 768px) { :root { --grid-gutter: ". $width[2]. "; } }";
			}

			if( isset( Setup::EFFECTS[$effect] ) === true ) {

				$declarations = Setup::EFFECTS[$effect][ (string) ( $motion['strength'] ?? '' ) ] ?? Setup::EFFECTS[$effect]['medium'];
				$speed = (string) ( $motion['speed'] ?? '' );

				if( isset( Setup::SPEEDS[$speed] ) === true )
					$declarations['--vpa-duration'] = Setup::SPEEDS[$speed];

				$lines = [];

				foreach( $declarations as $property => $value )
					$lines[] = "\t". $property. ': '. $value. ';';

				$rules[] = "main {\n". implode( "\n", $lines ). "\n}";
			}

			return $rules === [] ? '/* a row of the width Nino.css gives it, and the animation Nino.css has */' : implode( "\n", $rules );
		}

		/**
		 *	Write the compiled sheet, unless the file on disk is not one of ours
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$css					What compile() returned
		 *	@param		bool			$force				Overwrite a file this never wrote
		 *
		 *	@return 	true|string							true, or why not
		 */
		public static function write( array &$appData, string $css, bool $force = false ): true|string {

			$refusal = self::refusal( $appData, self::TARGET, $force );

			if( $refusal !== '' )
				return $refusal;

			return \Nino\Filesystem::putFileContent( $appData, self::TARGET, $css ) === true
				? true
				: 'could not write '. self::TARGET;
		}

		/**
		 *	Why one of the files a compile writes may not be written, or '' when
		 *	it may. Apart from write() and writeFrame(), which ask for their own
		 *	file, this is what lets \Nino\Modules\Design::apply() ask about all
		 *	of them before it writes the first: the refusal says nothing was
		 *	overwritten, and a stylesheet already on disk while the header
		 *	refused made that untrue
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$target				A virtual path - TARGET or a FRAME_TARGET
		 *	@param		bool			$force				Overwrite a file this never wrote
		 *
		 *	@return 	string									The refusal, or ''
		 */
		public static function refusal( array &$appData, string $target, bool $force = false ): string {

			if( $force === true )
				return '';

			$path = \Nino\Filesystem::path( $appData, $target );

			if( is_file( $path ) === false )
				return '';

			$content = (string) @file_get_contents( $path );
			$ours 		= $target === self::TARGET ? self::stamped( $content ) : self::stampedFrame( $content );

			if( $ours === true )
				return '';

			return ( $target === self::TARGET ? 'assets/theme.css' : $target )
				. ' was not written by Design - it is the delivered '
				. ( $target === self::TARGET ? 'file' : 'template' )
				. ', or somebody edited it. Nothing was overwritten.';
		}

		/**
		 *	Write one frame's markup into the template the page frame includes.
		 *
		 *	Same bargain as write(): a template this did not write is left alone
		 *	and the refusal says so. A project's header template is a file people
		 *	edit - it carries the logo, the claim, whatever a site needs up there
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$part					'header' or 'footer'
		 *	@param		string		$markup				The frame's template.tpl
		 *	@param		string		$set					Which variant it is, for the header line
		 *	@param		bool			$force				Overwrite a template this never wrote
		 *
		 *	@return 	true|string							true, or why not
		 */
		public static function writeFrame( array &$appData, string $part, string $markup, string $set, bool $force = false ): true|string {

			$target 	= sprintf( self::FRAME_TARGET, $part );
			$refusal	= self::refusal( $appData, $target, $force );

			if( $refusal !== '' )
				return $refusal;

			return \Nino\Filesystem::putFileContent( $appData, $target, self::frame( $part, $markup, $set ) ) === true
				? true
				: 'could not write '. $target;
		}

		/**
		 *	The bytes of one frame template as writeFrame() puts them on disk -
		 *	the stamped header and the markup under it. Its own method so that
		 *	\Nino\Modules\Design::apply() can compare what it is about to write
		 *	with what is there, and plan() can say whether applying changes it
		 *
		 *	@param		string		$part					'header' or 'footer'
		 *	@param		string		$markup				The frame's template.tpl
		 *	@param		string		$set					Which variant it is, for the header line
		 *
		 *	@return 	string
		 */
		public static function frame( string $part, string $markup, string $set ): string {

			$body = rtrim( $markup, "\n" ). "\n";

			/*	An html comment rather than a css one, and visible in the page
				source on purpose: this file is html+, so anything here is served.
				Nino's own templates already carry <!-- nino:template-slot --> */
			$header = '<!-- Generated by the Design feature - '. $part. ' '. $set. '. Do not edit: the next'
				. ' compile rewrites this file. To take it over by hand, delete this line.'. "\n"
				. '     '. self::STAMP. ': '. hash( 'sha256', $body ). ' -->'. "\n";

			return $header. $body;
		}

		/**
		 *	Whose a file is: the three answers stamped() and stampedFrame() give
		 *	as two, told apart. A stamp that no longer matches is a file Design
		 *	wrote and somebody changed - what the panel calls edited - and no
		 *	stamp at all is a file Design never wrote, such as the one the
		 *	setup wizard delivers
		 *
		 *	@param		string		$content			A file as it is on disk
		 *	@param		bool			$frame				A frame template rather than the stylesheet
		 *
		 *	@return 	string								'ours', 'edited' or 'foreign'
		 */
		public static function ownership( string $content, bool $frame ): string {

			if( preg_match( '/'. preg_quote( self::STAMP, '/' ). ':\s*([a-f0-9]{64})/', $content ) !== 1 )
				return 'foreign';

			return ( $frame === true ? self::stampedFrame( $content ) : self::stamped( $content ) ) === true ? 'ours' : 'edited';
		}

		/**
		 *	Whether a frame template is one this wrote and nobody has touched
		 *
		 *	@param		string		$markup				A template as it is on disk
		 *
		 *	@return 	bool
		 */
		public static function stampedFrame( string $markup ): bool {

			if( preg_match( '/'. preg_quote( self::STAMP, '/' ). ':\s*([a-f0-9]{64})/', $markup, $found ) !== 1 )
				return false;

			$at = strpos( $markup, "-->\n" );

			return $at !== false && hash( 'sha256', substr( $markup, $at + 4 ) ) === $found[1];
		}

		/**
		 *	Whether a stylesheet is one this wrote and nobody has touched since:
		 *	its header claims a digest, and the body still has it
		 *
		 *	@param		string		$css					A stylesheet as it is on disk
		 *
		 *	@return 	bool
		 */
		public static function stamped( string $css ): bool {

			if( preg_match( '/'. preg_quote( self::STAMP, '/' ). ':\s*([a-f0-9]{64})/', $css, $found ) !== 1 )
				return false;

			$at = strpos( $css, "*/\n" );

			return $at !== false && hash( 'sha256', substr( $css, $at + 3 ) ) === $found[1];
		}

		/**
		 *	The header the written file carries: what made it, out of what, and
		 *	the digest that tells a later run whether it is still untouched
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$body					Everything below the header
		 *	@param		array 		$answered			The knobs each chosen set answers to, by part
		 *
		 *	@return 	string
		 */
		private static function _header( array $setup, string $body, array $answered ): string {

			$lines = [];

			foreach( Setup::PARTS as $part => $kind ) {

				$set 		= (string) ( $setup['parts'][$part] ?? '' );
				$moved 	= [];

				// Only the knobs the set answers to that stand off the middle: the
				// rest is the template as it is
				foreach( $answered[$part] ?? [] as $knob )
					if( Setup::step( $setup, $part, $knob ) !== 'default' )
						$moved[] = $knob. ' '. Setup::step( $setup, $part, $knob );

				$lines[] = ' *   '. str_pad( $part, 11 ). ( $set !== '' ? $set : '-' )
					. ( $moved === [] ? '' : '  ('. implode( ', ', $moved ). ')' );
			}

			$lines[] = ' *';

			$families = Fonts::families();

			foreach( Fonts::ROLES as $role ) {

				$chosen = (array) ( $setup['fonts'][$role] ?? Fonts::DEFAULTS[$role] );
				$family = (string) ( $chosen['family'] ?? '' );
				$size 	= (string) ( $chosen['size'] ?? '' );

				$lines[] = ' *   '. str_pad( $role, 11 )
					. ( isset( $families[$family] ) === true ? $families[$family]['name'] : 'the system stack' )
					. ', '. (string) ( $chosen['weight'] ?? 'normal' )
					. ( ( $chosen['uppercase'] ?? false ) === true ? ', uppercase' : '' )
					. ( $size === '' ? '' : ', '. $size. 'rem' );
			}

			$width 		= (int) ( $setup['width'] ?? 0 );
			$motion 	= (array) ( $setup['motion'] ?? [] );
			$effect 	= (string) ( $motion['effect'] ?? '' );

			return "/*\tGenerated by the Design feature. Do not edit - the next compile\n"
				. " *\trewrites this file, and anything put here is gone.\n"
				. " *\n"
				. " *\tTo change it: the Design panel, or data/design.php and compile again.\n"
				. " *\tTo take it over by hand: delete the line below, and Design will\n"
				. " *\trefuse to overwrite the file from then on.\n"
				. " *\n"
				. " *\t". self::STAMP. ": ". hash( 'sha256', $body ). "\n"
				. " *\n"
				. " *\troot size  ". Setup::word( (int) ( $setup['size'] ?? 0 ) ). "\n"
				. " *\twidth      ". Setup::word( $width ). "\n"
				. " *\tanimation  ". ( isset( Setup::EFFECTS[$effect] ) === true
					? $effect. ', '. ( (string) ( $motion['strength'] ?? '' ) ?: 'medium' ). ( (string) ( $motion['speed'] ?? '' ) === '' ? '' : ', '. $motion['speed'] )
					: 'as Nino.css has it' ). "\n"
				. " *\n"
				. implode( "\n", $lines ). "\n"
				. " */\n";
		}

		/**
		 *	The text of a stretch of a file below the line it starts on
		 *
		 *	@param		string		$text
		 *	@param		int				$from					Where the marker line starts
		 *	@param		int				$to						Where the stretch ends
		 *
		 *	@return 	string
		 */
		private static function _after( string $text, int $from, int $to ): string {

			$line = strpos( $text, "\n", $from );

			return $line === false || $line >= $to ? '' : substr( $text, $line + 1, $to - $line - 1 );
		}

		/**
		 *	A library file, without a trailing newline surprise
		 *
		 *	@param		string		$path
		 *
		 *	@return 	string
		 */
		private static function _read( string $path ): string {
			return rtrim( (string) @file_get_contents( $path ), "\n" ). "\n";
		}

	}
}
