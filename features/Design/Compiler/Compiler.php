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
	 *										assigned to, the root size, the width, the whitespace, the
	 *										radius of its surfaces and the animation of the page, the
	 *										chosen frames, the six chosen sets, the variants the
	 *										features that bring some are given, the block that says
	 *										which step of every ladder is live, and last the css of the
	 *										theme, as it was typed.
	 *
	 *										A set declares what the steps of a knob *are* - the five
	 *										values of a scale, the three of a choice, the two of a
	 *										toggle - rather than a value a knob then modifies: +1rem
	 *										behaves differently on a default of 5rem than on 2rem, and
	 *										some scales are not linear at all. So the knob never
	 *										computes: it picks, and what it picks is one selection line
	 *										per token, gathered into a single block at the end. CSS
	 *										cannot compose a variable name, which is why that block
	 *										exists at all. A frame declares its ladders in its
	 *										style.css as a set does in its file, and is compiled and
	 *										selected the same way. A style a template brings along (see
	 *										Setup::styles()) is the css file of the option chosen, written
	 *										as it stands behind the css of the template, in the same
	 *										section, under a line that names the style and the option.
	 *										The styles of a feature (Setup::featureStyles()) are written the same
	 *										way behind the css of the variant chosen, whichever variant it is.
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
			a page ends up with the css of a floating bar over the html of a plain
			one. The two templates the base
			install unit delivers are the ones this writes over - and refuses to,
			by the same stamp, when they are not ours */
		public const string FRAME_TARGET = '/templates/frame-%s.tpl';

		/*	The line a chosen style's css stands under, behind the css of its template: the style and the option. One line, the form the
			other marks of the sheet have (a comment on a line of its own)	*/
		public const string STYLE = '/* -- style %s: %s -- */';

		/*	The line the rules the styles of a feature share stand under, before the css of the options chosen: the body of design/styles.css	*/
		public const string SHARED = '/* -- styles -- */';

		/*	The two lines base.css falls into its two halves by: the tokens follow
			the first, the roles the second. What stands above the first - the
			file's own description - is for whoever reads the library and does not
			travel into a project's stylesheet	*/
		private const array HALVES = [ '/* ---- 1. ', '/* ---- 2. ' ];

		/**
		 *	The stylesheet a setup produces, in seventeen numbered sections - and one more for each feature
		 *	that has a variant chosen, after the sets (see below):
		 *
		 *	  1 the tokens          base.css, the --nino-* defaults
		 *	  2 the palette         solved over them, in the shape the mode asks for
		 *	  3 the type            @font-face and the five font roles
		 *	  4 the roles           base.css, which token goes in which role
		 *	  5 the root size
		 *	  6 the page            the width, the whitespace, the radius of its surfaces, and the animation as a rule on main
		 *	  7 header, 8 footer    the chosen frames
		 *	  9 section, 10 tiles, 11 card, 12 buttons, 13 lists, 14 tables, 15 forms    the chosen sets
		 *	  16 the knob positions
		 *	  17 the theme          the custom css, as it was typed
		 *
		 *	A frame or a set the library does not have is left out and noted, and
		 *	the sections after it take the numbers that are free. So do the sections of the
		 *	features: one for each feature that is active and has an entry in the setup, in the
		 *	order of the catalogue (see Setup::features()), holding the stylesheet of the variant
		 *	chosen for it, titled "feature forms: loud (size more)" for the variant loud of the
		 *	feature forms - after the sets and before the knob positions, which then number from
		 *	the next. The lines of the knobs a variant declares go into the one block of the knob
		 *	positions, after those of the parts: a ladder is selected as the ladders of a set are,
		 *	a ground is written as the surface and every part the palette publishes of it, each
		 *	the var() of the token of the palette, a colour as its light half, and its dark half
		 *	in the dark blocks the palette's own are written in (see Colours::dark()) - where the
		 *	palette has them, which is the mode auto. An ink is the var() of a token of the page
		 *	on every element, since the sections of Nino.css point those tokens elsewhere on their
		 *	own ground and a property declared on the root alone would keep the value the root
		 *	gave it. An entry of a feature that is not in the catalogue is told once and writes
		 *	nothing. The theme's css
		 *	is the last section, and only where there is some: it follows the knob
		 *	positions so that it wins against everything before it, a rung of a
		 *	ladder re-declared in it included - the selection line of the knob then
		 *	reads the rung the theme gave. It is written byte for byte, not rewritten,
		 *	prefixed or minified.
		 *
		 *	The styles chosen for a template are written behind its css, in its section: each file as it
		 *	stands - its head comment too, as the head of the template's own file stays - under the line
		 *	STYLE makes of the style and the option, in the order the template's head declares the
		 *	styles. The title of the section names them after the knobs ("header: floating (nav default,
		 *	nav-hover soft-glow)"). A style is no ladder, so the block of the knob positions has no
		 *	line for it.
		 *
		 *	The styles chosen for a feature are written behind the css of its variant, in the section of the feature, whichever variant it is:
		 *	where there is one, first the body of the feature's design/styles.css - the rules the styles share - under the line SHARED, then
		 *	each option as a template's are written. A feature with no style chosen has the section it always had. The title of the section
		 *	names them after the knobs ("feature styled: loud (size more, look soft)"), the line of the feature in the header after its knobs.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data - the families a project installed are read off its disk
		 *	@param		array 		$setup				A normalised setup (see Setup::normalize())
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								The complete css, header and all
		 */
		public static function compile( array &$appData, array $setup, string $libraryDir, array &$notes = [] ): string {

			$libraryDir = rtrim( $libraryDir, '/' );
			$parts 			= [];
			$selection 	= [];
			$answered 	= [];
			$styled 		= [];
			$inks 			= [];

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
				. Fonts::css( $appData, is_array( $setup['fonts'] ?? null ) ? $setup['fonts'] : [] );

			if( $roles !== '' )
				$parts[] = sprintf( self::SECTION, 4, 'the roles' ). "\n". rtrim( $roles, "\n" ). "\n";

			// 5. the root size, as the percentage pair Nino.css reads
			$size = Setup::SIZES[ (int) ( $setup['size'] ?? 0 ) ] ?? Setup::SIZES[0];
			$parts[] = sprintf( self::SECTION, 5, 'the root size' ). "\n"
				. ":root { --nino-base-size: ". $size[0]. "; }\n"
				. "@media (min-width: 768px) { :root { --nino-base-size: ". $size[1]. "; } }";

			$parts[] = sprintf( self::SECTION, 6, 'the page' ). "\n". self::page( $setup );

			// 7. and 8. the frames, then 9. to 15. the sets - both in PARTS
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
					$where[] = $knob. ' '. Setup::said( $setup, $part, $knob );

				/*	The css of each style chosen, behind the css of the set and in its section. The choice was held to the
					files of the set when the setup was normalised; a file that is gone since is told, and left out	*/
				foreach( (array) ( $setup['styles'][$part] ?? [] ) as $style => $option ) {

					$extra = Setup::styleFile( $libraryDir, $part, $set, $style, $option );

					if( $extra === '' ) {
						$notes[] = 'no stylesheet for the style "'. $style. '" option "'. $option. '" of "'. $part. '" set "'. $set. '"';
						continue;
					}

					$css 		.= "\n". sprintf( self::STYLE, $style, $option ). "\n". self::_read( $extra );
					$where[] = $style. ' '. $option;
					$styled[$part][] = $style. ' '. $option;
				}

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
						$line = "\t--". $token. ': var('. sprintf( Setup::KNOB_TOKEN, $part, $name, Setup::step( $setup, $part, $knob ) ). ');';

						/*	An ink is the tone of the page on the ground the element stands on, so it is written on every element, in the
							block of the inks (see below): the rung the template gives it where nothing is chosen, the tone where a
							tone is - the var() of the role of the page, which the sections of Nino.css point elsewhere on their own ground	*/
						if( Setup::kind( $part, $knob ) === 'ink' ) {

							$tone = $name === $knob ? Setup::tone( $setup['knobs'][$part][$knob] ?? '' ) : '';
							$inks['--'. $token] = $tone !== '' ? "\t--". $token. ': var('. Setup::INKS[$tone]. ');' : $line;
						}
						else
							$selection[$token] = $line;
					}
			}

			/*	The variants of the features, after the nine parts so that a feature that restyles what a part
				drew has the last word - and the stylesheet the feature itself loads on the site comes after
				this whole sheet, so that what a variant sets is set on what that one declares. In the order
				of the catalogue. A feature that has an entry and is not in the catalogue is told, once -
				normalising told it already where this setup was read - and compiles nothing	*/
			$catalogue 	= Setup::features( $appData );
			$featured 	= [];
			$lines 			= [];
			$dark 			= [];

			foreach( array_keys( (array) ( $setup['features'] ?? [] ) ) as $key ) {

				$told = Setup::inactive( (string) $key );

				if( isset( $catalogue[$key] ) === false && in_array( $told, $notes, true ) === false )
					$notes[] = $told;
			}

			foreach( $catalogue as $key => $feature ) {

				$entry = $setup['features'][$key] ?? null;

				if( is_array( $entry ) === false )
					continue;

				$set 		= (string) ( $entry['set'] ?? '' );
				$file 	= Setup::featureFile( $appData, $key, $set );

				if( $file === '' ) {
					$notes[] = 'no stylesheet for the feature "'. $key. '" variant "'. $set. '"';
					continue;
				}

				$where 	= [];
				$moved 	= [];

				foreach( $feature['catalogue'][$set]['knobs'] as $knob => $meta ) {

					$value 	= $entry['knobs'][$knob] ?? $meta['default'];
					$name 	= '--'. $key. '-'. $knob;
					$step 	= in_array( $meta['kind'], Setup::LADDERS, true ) === true ? Setup::featureStep( $setup, $key, $knob, $meta['kind'] ) : '';

					// What is said of the knob where it stands off the middle: the step of a ladder, the word of a ground or an ink, the two halves of a colour
					if( $value !== $meta['default'] )
						$moved[] = $knob. ' '. ( $step !== '' ? $step : ( is_array( $value ) === true ? implode( '/', $value ) : (string) $value ) );

					switch( $meta['kind'] ) {

						// The ladders of a variant are selected as the ladders of a set are
						case 'scale':
						case 'choice':

							$where[] = $knob. ' '. $step;

							foreach( $meta['ladder'] as $ladder )
								$lines['--'. $key. '-'. $ladder] = "\t--". $key. '-'. $ladder. ': var('. sprintf( Setup::KNOB_TOKEN, $key, $ladder, $step ). ');';
							break;

						/*	A surface of the palette, and with it every part the palette publishes of it - the text the palette
							solved for that ground, and the rest - each as the var() of the palette's own token: the dark mode
							switches the tokens, and needs no line of its own here	*/
						case 'ground':

							foreach( Colours::parts() as $part )
								$lines[$name. '/'. $part] = "\t". $name. ( $part === 'bg' ? '' : '-'. $part ). ': var('. Colours::token( (string) $value, $part ). ');';
							break;

						// A tone of the page, on every element, so that it is the tone of the ground the element stands on
						case 'ink':

							if( isset( Setup::INKS[ (string) $value ] ) === true )
								$inks[$name] = "\t". $name. ': var('. Setup::INKS[ (string) $value ]. ');';
							break;

						// A colour of its own, in light and in dark. Where the palette is one mode alone, only the half of that mode is written
						case 'colour':

							$pair = is_array( $value ) === true && count( $value ) === 2 ? array_values( $value ) : $meta['default'];

							$lines[$name] = "\t". $name. ': '. $pair[ $mode === 'dark' ? 1 : 0 ]. ';';

							if( $mode === 'auto' )
								$dark[$name] = $name. ': '. $pair[1]. ';';
							break;
					}
				}

				/*	The styles chosen for the feature, behind the css of the variant and in its section, whichever variant it is: the rules the
					styles share (the body of design/styles.css) once, under their own line, then the css of each option under the line that
					names it. Nothing is chosen, nothing is written - not the shared rules either. The choice was held to the files of the
					feature when the setup was normalised; a file that is gone since is told, and left out	*/
				$css 		= self::_read( $file );
				$extras = '';

				foreach( (array) ( $entry['styles'] ?? [] ) as $style => $option ) {

					$extra = Setup::featureStyleFile( $appData, $key, (string) $style, (string) $option );

					if( $extra === '' ) {
						$notes[] = 'no stylesheet for the style "'. $style. '" option "'. $option. '" of the feature "'. $key. '"';
						continue;
					}

					$extras 	.= "\n". sprintf( self::STYLE, $style, $option ). "\n". self::_read( $extra );
					$where[] 	= $style. ' '. $option;
					$moved[] 	= $style. ' '. $option;
				}

				if( $extras !== '' ) {

					$shared = Setup::featureShared( $appData, $key );
					$css 		.= ( $shared === '' ? '' : "\n". self::SHARED. "\n". $shared. "\n" ). $extras;
				}

				$featured[$key] = [ 'set' => $set, 'moved' => $moved ];

				$parts[] = sprintf( self::SECTION, $number++, 'feature '. $key. ': '. $set
					. ( $where === [] ? '' : ' ('. implode( ', ', $where ). ')' ) ). "\n". $css;
			}

			/*	The block of the knob positions: the lines of the parts, sorted, and those of the features after them in the
				order of the catalogue and of the heads. Then the inks - those of the parts first, then those of the features -, which are declared on every element (see above), and the
				dark halves of the colours, under the selectors the palette's own dark half stands under	*/
			ksort( $selection );

			$blocks = [];
			$all 		= array_merge( array_values( $selection ), array_values( $lines ) );

			if( $all !== [] )
				$blocks[] = ":root {\n". implode( "\n", $all ). "\n}";

			if( $inks !== [] )
				$blocks[] = ":root, :root * {\n". implode( "\n", $inks ). "\n}";

			if( $dark !== [] )
				$blocks[] = Colours::dark( static fn( int $depth ): string => str_repeat( "\t", $depth ). implode( "\n". str_repeat( "\t", $depth ), $dark ). "\n" );

			if( $blocks !== [] )
				$parts[] = sprintf( self::SECTION, $number++, 'the knob positions' ). "\n". implode( "\n\n", $blocks );

			// 15. the theme's own css, the last word of the sheet. The body adds the one line break the file ends in
			$custom = is_string( $setup['css'] ?? null ) === true ? $setup['css'] : '';

			if( trim( $custom ) !== '' )
				$parts[] = sprintf( self::SECTION, $number, 'the theme' ). "\n". rtrim( $custom, "\n" );

			$body = implode( "\n\n", $parts ). "\n";

			return self::_header( $appData, $setup, $body, $answered, $featured, $styled ). $body;
		}

		/**
		 *	What the page asks of Nino.css as a whole: how wide it is, how much air
		 *	it has, how its surfaces are cut and how its sections arrive.
		 *
		 *	A width of 0 is the kernel's own and writes nothing; the four around it
		 *	write the maximum width of a row and the space between the viewport
		 *	and it, with the step it takes from 768px - the two widest no maximum
		 *	(none), and the widest of them no space (0) from there. The whitespace
		 *	is the same: 0 is the kernel's own and writes nothing, the four around
		 *	it write the number --whitespace-scale is set to (see Setup::WHITESPACE),
		 *	from which Nino.css derives what it scales whole and what it scales
		 *	damped. The radius is one rule on :root and is always written, at the
		 *	kernel's own values as well: the length of the standard strength and of
		 *	the alternative one (see Setup::RADIUS) and, for each of the five kinds of
		 *	surface, its token pointing at the strength the setup gives it with its
		 *	marker, the word that says which - so that a tool reading the marker
		 *	finds what the setup says and not what the kernel assumed. An animation
		 *	writes one rule on main with the custom properties the kernel sets for
		 *	the effect, its strength and its speed, which every viewport animation
		 *	below main inherits - and which the Builder overrides per template at
		 *	its wrap, and a section with an animation class of its own overrides
		 *	again. No effect writes nothing, whatever strength or speed stand
		 *	beside it: they belong to an effect. The hover of the pictures adds its
		 *	handles to that rule, after the animation's - the custom properties the
		 *	rules of Nino.css for nino-hover read (see Setup::HOVERS), for the kind and
		 *	the strength, the middle one where there is none - and is the rule by
		 *	itself where there is no effect. No kind writes nothing, which is the
		 *	kernel's own gentle zoom, and where neither is set there is no rule on main.
		 *
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	string								The rules
		 */
		public static function page( array $setup ): string {

			$rules 		= [];
			$width 		= Setup::WIDTHS[ (int) ( $setup['width'] ?? 0 ) ] ?? null;
			$air 			= Setup::WHITESPACE[ (int) ( $setup['whitespace'] ?? 0 ) ] ?? '1';
			$motion 	= is_array( $setup['motion'] ?? null ) === true ? $setup['motion'] : [];
			$effect 	= (string) ( $motion['effect'] ?? '' );
			$hover 		= (string) ( $motion['hover'] ?? '' );

			if( $width !== null ) {
				$rules[] = ":root { --grid-max-width: ". $width[0]. "; --grid-gutter: ". $width[1]. "; }";
				$rules[] = "@media (min-width: 768px) { :root { --grid-gutter: ". $width[2]. "; } }";
			}

			if( $air !== '1' )
				$rules[] = ":root { --whitespace-scale: ". $air. "; }";

			$lines = [
				"\t--radius-surface: ". ( Setup::RADIUS[ (int) ( $setup['radius'] ?? 0 ) ] ?? Setup::RADIUS[0] ). ";",
				"\t--radius-surface-alt: ". ( Setup::RADIUS[ (int) ( $setup['radius-alt'] ?? 1 ) ] ?? Setup::RADIUS[1] ). ";",
			];

			foreach( Setup::SURFACES as $kind ) {

				$word = Setup::radiusOf( $setup, $kind );

				$lines[] = "\t--radius-". $kind. ": var(--radius-surface". ( $word === Setup::RADIUS_WORDS[1] ? "-alt" : "" ). ");";
				$lines[] = "\t--radius-". $kind. "-default: ". $word. ";";
			}

			$rules[] = ":root {\n". implode( "\n", $lines ). "\n}";

			$declarations = [];

			if( isset( Setup::EFFECTS[$effect] ) === true ) {

				$declarations = Setup::EFFECTS[$effect][ (string) ( $motion['strength'] ?? '' ) ] ?? Setup::EFFECTS[$effect]['medium'];
				$speed = (string) ( $motion['speed'] ?? '' );

				if( isset( Setup::SPEEDS[$speed] ) === true )
					$declarations['--vpa-duration'] = Setup::SPEEDS[$speed];
			}

			if( isset( Setup::HOVERS[$hover] ) === true )
				$declarations = array_merge( $declarations, Setup::HOVERS[$hover][ (string) ( $motion['hover-strength'] ?? '' ) ] ?? Setup::HOVERS[$hover]['medium'] );

			if( $declarations !== [] ) {

				$lines = [];

				foreach( $declarations as $property => $value )
					$lines[] = "\t". $property. ': '. $value. ';';

				$rules[] = "main {\n". implode( "\n", $lines ). "\n}";
			}

			return implode( "\n", $rules );
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
				source on purpose: this file is html+, so anything here is served,
				and the comment is the compiler's own mark of a frame it wrote */
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
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$body					Everything below the header
		 *	@param		array 		$answered			The knobs each chosen set answers to, by part
		 *	@param		array 		$featured			The variants the features were given, by key: set, and the knobs that stand off the head's default
		 *	@param		array 		$styled				The styles written behind each chosen part, by part: "<style> <option>" each
		 *
		 *	@return 	string
		 */
		private static function _header( array &$appData, array $setup, string $body, array $answered, array $featured, array $styled = [] ): string {

			$lines = [];

			foreach( Setup::PARTS as $part => $kind ) {

				$set 		= (string) ( $setup['parts'][$part] ?? '' );
				$moved 	= [];

				// Only the knobs the set answers to that stand off the middle: the
				// rest is the template as it is
				foreach( $answered[$part] ?? [] as $knob )
					if( Setup::said( $setup, $part, $knob ) !== 'default' )
						$moved[] = $knob. ' '. Setup::said( $setup, $part, $knob );

				// The styles follow the knobs of their part
				$moved = array_merge( $moved, $styled[$part] ?? [] );

				$lines[] = ' *   '. str_pad( $part, 11 ). ( $set !== '' ? $set : '-' )
					. ( $moved === [] ? '' : '  ('. implode( ', ', $moved ). ')' );
			}

			// A line for each feature that was given a variant, and none where no feature was
			foreach( $featured as $key => $given )
				$lines[] = ' *   feature '. $key. '  '. $given['set']. ( $given['moved'] === [] ? '' : '  ('. implode( ', ', $given['moved'] ). ')' );

			$lines[] = ' *';

			$families = Fonts::families( $appData );

			foreach( Fonts::ROLES as $role ) {

				$chosen = (array) ( $setup['fonts'][$role] ?? Fonts::DEFAULTS[$role] );
				$family = (string) ( $chosen['family'] ?? '' );
				$size 	= (string) ( $chosen['size'] ?? '' );

				$lines[] = ' *   '. str_pad( $role, 11 )
					. ( isset( $families[$family] ) === true ? $families[$family]['name'] : 'the system stack' )
					. ', '. (string) ( $chosen['weight'] ?? 'normal' )
					. ( $size === '' ? '' : ', '. $size. 'rem' );
			}

			$width 		= (int) ( $setup['width'] ?? 0 );
			$air 			= (int) ( $setup['whitespace'] ?? 0 );
			$kinds 		= [];

			foreach( Setup::SURFACES as $kind )
				$kinds[] = $kind. ' '. Setup::radiusOf( $setup, $kind );

			$motion 	= (array) ( $setup['motion'] ?? [] );
			$effect 	= (string) ( $motion['effect'] ?? '' );
			// A slug only: the header is a comment, and a name that closed it would end the header early
			$theme 		= is_string( $setup['theme'] ?? null ) === true ? $setup['theme'] : '';

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
				. " *\twhitespace ". Setup::word( $air ). "\n"
				. " *\tradius     ". Setup::word( (int) ( $setup['radius'] ?? 0 ) ). "\n"
				. " *\tradius alt ". Setup::word( (int) ( $setup['radius-alt'] ?? 1 ) ). "\n"
				. " *\tradii      ". implode( ', ', $kinds ). "\n"
				. " *\tanimation  ". ( isset( Setup::EFFECTS[$effect] ) === true
					? $effect. ', '. ( (string) ( $motion['strength'] ?? '' ) ?: 'medium' ). ( (string) ( $motion['speed'] ?? '' ) === '' ? '' : ', '. $motion['speed'] )
					: 'as Nino.css has it' ). "\n"
				. " *\ttheme      ". ( preg_match( Themes::SLUG, $theme ) === 1 ? $theme : '-' ). "\n"
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
