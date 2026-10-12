<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design\Setup		see features/Design/Design.php for the feature's
 *											own docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Setup							What was chosen, and nothing derived from it. Read and
	 *										written as /data/design.php, declared under `data` in
	 *										feature.php so a backup carries it - it is the one thing
	 *										here that cannot be worked out again from what is on disk.
	 *
	 *										One model for the six tabs of the panel that choose
	 *										something (the seventh, Typefaces, installs families and
	 *										keeps nothing here): the five templates (header and footer
	 *										bring markup, content, buttons and components are one
	 *										stylesheet each), the root size, the width of the page and
	 *										its animation, a position for every named knob of the
	 *										three template tabs, the five font roles and the palette.
	 *
	 *										Every value is normalised against the library that is
	 *										actually present: a part naming a set that is not there
	 *										falls back to the first one that is, and says so, rather
	 *										than compiling a stylesheet with a hole in it. A key this
	 *										version does not know is dropped, and says so as well.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Setup {

		public const string PATH = '/data/design.php';

		/*	The shape's own version. A file of another one is not read and not
			mapped: Design 2.0 was the first version to be used, so read() replaces
			it with the defaults and says so. A file written while the scales had
			three positions has this format and is read by the rules of normalize()
			as it is: a size that is a letter is the middle, a knob that is gone is
			dropped and named, and a position is a position of five	*/
		public const int FORMAT = 2;

		// The keys of the file, in the order they are written
		public const array KEYS = [ 'format', 'size', 'width', 'motion', 'parts', 'knobs', 'fonts', 'colours', 'compiled' ];

		/*	The parts, in the order they are compiled - which is also the order
			they are shown in. `frame` parts bring a template and are chosen from
			library/<part>/<set>/; the rest are one stylesheet each, library/<part>/<set>.css	*/
		public const array PARTS = [
			'header' 			=> 'frame',
			'footer' 			=> 'frame',
			'content' 		=> 'set',
			'buttons' 		=> 'set',
			'components' 	=> 'set',
		];

		/*	What a knob can be set to, as the words a stylesheet declares its values
			under. The model keeps -2 to +2 and the library keeps these five words;
			word() is the one place that maps one to the other. A choice has three
			alternatives and declares them under the inner three - less, default, more -
			so that there is one vocabulary and no word of its own	*/
		public const array STEPS = [ 'least', 'less', 'default', 'more', 'most' ];

		/*	The knobs, per tab, and the kind of each - and nothing else. A knob belongs
			to one tab and has a name: what it is called and what the terse note beside
			it says live in the panel's text files, one key each in every locale, and so
			do the three alternatives of a choice. The meaning of each is fixed here and
			not per set, so that the templates of a tab stay exchangeable - what a set
			decides is the values behind a knob, and whether it answers to it at all.

			A scale is a track of five positions, -2 to +2, the middle the template as it
			is: a size, a space, a corner, a line. A choice is a few alternatives that
			are no more or less of anything - the stripes of a table, the place of a
			label - and has three, which the panel names by words:

				content		hero-title, hero-text	the size of the hero's title and text
							quiet, loud				the size of the --quiet and --loud variants
							section-title			the size of the title of a section
							section-subtitle		the size of the subtitle of a section
							section-space			the vertical space of a section
							section-line			the rule under the title of a section
							card-title				the size of the title of a card
							card-subtitle			the size of the subtitle of a card
							card-space				the space inside and between the parts of a card
							corners, border, shadow	the corners, the line and the shadow of a card
				buttons		size					the size of .nino-btn
							spread					how far .nino-btn--small and --big stand from it
							space, corners			the padding and the radius
				components	scale					the type size of fields and tables
							space, corners, border	the space, the corners and the lines
							zebra					the stripes of a table (a choice)
							labels					where the label of a field stands (a choice)	*/
		public const array KNOBS = [
			'content' 		=> [ 'hero-title' => 'scale', 'hero-text' => 'scale', 'quiet' => 'scale', 'loud' => 'scale', 'section-title' => 'scale', 'section-subtitle' => 'scale', 'section-space' => 'scale', 'section-line' => 'scale', 'card-title' => 'scale', 'card-subtitle' => 'scale', 'card-space' => 'scale', 'corners' => 'scale', 'border' => 'scale', 'shadow' => 'scale' ],
			'buttons' 		=> [ 'size' => 'scale', 'spread' => 'scale', 'space' => 'scale', 'corners' => 'scale' ],
			'components' 	=> [ 'scale' => 'scale', 'space' => 'scale', 'corners' => 'scale', 'border' => 'scale', 'zebra' => 'choice', 'labels' => 'choice' ],
		];

		/*	How the screen shows the knobs of a tab: in groups, a strip of tabs and under it
			the rows of the group that is chosen. The groups and the knobs of each are in
			the order they are shown in. Only the content has groups - the buttons and the
			components are one list of rows and have no entry here - and every knob of
			such a tab stands in exactly one of them. A group belongs to the screen and
			nothing is stored under it: a setup keeps the position of a knob by its tab
			and its name, as before. The panel's words for a group are
			/_admin/design/group/<tab>/<group>/label	*/
		public const array GROUPS = [
			'content' => [
				'hero' 		=> [ 'hero-title', 'hero-text' ],
				'section' 	=> [ 'section-title', 'section-subtitle', 'section-space', 'section-line' ],
				'card' 		=> [ 'card-title', 'card-subtitle', 'card-space', 'corners', 'border', 'shadow' ],
				'variants' 	=> [ 'quiet', 'loud' ],
			],
		];

		/*	How a set says which knobs it answers to: one ladder per knob, named
			after the tab and the knob and nothing else.

			  --content-section-space--least / --less / --default / --more / --most

			A ladder is the five values of a scale, the middle of them the template as it
			is, or the three of a choice, which are declared under less, default and more.
			Declaring the ladder *is* publishing the knob - the panel offers exactly what
			a set declares and greys out what it does not, so a handle the stylesheet
			does not answer to can never be offered. A scale with three of its five, and a
			choice with five of its three, have published nothing. The @knob line in a
			set's own opening comment is documentation for whoever reads the file; the
			panel reads the declarations.

			A value that cannot say all a knob means - the stripes of a table are a
			background and a text colour - may be declared in more ladders, named after
			the knob and an aspect of it (--components-zebra-ink--less ...). They are the
			knob's too: they are selected where it stands, and are as long as it is. A
			stylesheet that declares one without the ladder of the knob itself has
			published nothing, and one with another length than its knob's has not
			published the knob.

			The name of a rung is KNOB_TOKEN filled with the tab, the name and the
			word: ladders() reads the declarations with it, and the compiler asks
			the rung of the position with it */
		public const string KNOB_TOKEN = '--%s-%s--%s';

		/*	The root size, as the percentage pair Nino.css wants: below the 768px
			breakpoint, and from it. Relative, never a length - see
			--base-size in Nino.css.

			Five positions, -2 to +2, each a choice somebody can see on the page rather
			than one they have to measure. Below the breakpoint they are 85, 92.5, 100,
			115 and 145%: the two steps under the middle 7.5% each, the two over it 15%
			and 30%, so that the small end is close and the large end reaches far. At a
			browser default of 16px that is 13.6, 14.8, 16, 18.4 and 23.2px. From it
			each is 1.125 times its own, the ratio of the kernel's pair of 100% and
			112.5%: 15.3, 16.65, 18, 20.7 and 26.1px. The middle position is the
			delivered size and does not move, so nothing compiled before this reads
			differently. The model keeps the position, a number, and not the pair	*/
		public const array SIZES = [
			-2 	=> [ '85%', '95.625%' ],
			-1 	=> [ '92.5%', '104.0625%' ],
			0 	=> [ '100%', '112.5%' ],
			1 	=> [ '115%', '129.375%' ],
			2 	=> [ '145%', '163.125%' ],
		];

		/*	The width of the page, as the maximum width of a row and the space
			between the viewport and it, below 768px and from it. 0 is Nino.css as
			it is and writes nothing: a row of at most 120rem, with the kernel's own
			space to the sides (--space-2, from 768px --space-3).

			The four others run from a reading column to the edge, and every one of the
			five is another row in a preview of 1280px, which is the width the panel
			shows. At the root size the kernel has from 768px, 18px, the frame is 1280px
			and a section keeps 36px of padding of its own at both sides, so a row has
			1208px at the most, and its text 1208px less the space to the sides, twice:

				-2	48rem, 864px	with 36px to each side: a text of 792px. A reading column
				-1	64rem, 1152px	with 36px to each side: 1080px. A page that is contained
				 0	120rem, 2160px	more than there is: the row is 1208px, 1136px of text
				 1	none			1208px, the kernel's small space to the sides at every
								width, 18px: 1172px of text
				 2	none			1208px and from 768px no space to the sides: 1208px of
								text, to the edge of the section's own padding. Below 768px
								the space stays - a phone is as wide as it gets, and the text
								stays off the edge of the screen

			Nino.css reads --grid-max-width as a max-width, of which none is a value,
			and --grid-gutter as the padding of a row, of which 0 is one.	*/
		public const array WIDTHS = [
			-2 	=> [ '48rem', 'var(--space-2)', 'var(--space-3)' ],
			-1 	=> [ '64rem', 'var(--space-2)', 'var(--space-3)' ],
			1 	=> [ 'none', 'var(--space-2)', 'var(--space-2)' ],
			2 	=> [ 'none', 'var(--space-2)', '0' ],
		];

		/*	The animation of the page: what Nino.css sets for nino-vpa--<effect>-<strength>
			and nino-vpa--speed-<speed>, as the properties of a rule on main. Held in
			code, because the compiler writes them and does not read a stylesheet it
			has no business with - tests/design-smoke.php reads them against the
			Nino.css it runs against. An effect with no strength is the middle one	*/
		public const array STRENGTHS = [ 'soft', 'medium', 'hard' ];

		public const array SPEEDS = [ 'fast' => '.25s', 'medium' => '.5s', 'slow' => '2s' ];

		public const array EFFECTS = [
			'zoom' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'scale(.94)', '--vpa-t-visible' => 'scale(1)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'scale(.85)', '--vpa-t-visible' => 'scale(1)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'scale(.7)', '--vpa-t-visible' => 'scale(1)' ],
			],
			'zoom-out' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'scale(1.06)', '--vpa-t-visible' => 'scale(1)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'scale(1.15)', '--vpa-t-visible' => 'scale(1)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'scale(1.35)', '--vpa-t-visible' => 'scale(1)' ],
			],
			'slide-left' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'translateX(1.5rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'translateX(3rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'translateX(6rem)', '--vpa-t-visible' => 'translateX(0)' ],
			],
			'slide-right' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'translateX(-1.5rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'translateX(-3rem)', '--vpa-t-visible' => 'translateX(0)' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'translateX(-6rem)', '--vpa-t-visible' => 'translateX(0)' ],
			],
			'blur' => [
				'soft' 		=> [ '--vpa-f-hidden' => 'blur(6px)', '--vpa-f-visible' => 'blur(0)' ],
				'medium' 	=> [ '--vpa-f-hidden' => 'blur(12px)', '--vpa-f-visible' => 'blur(0)' ],
				'hard' 		=> [ '--vpa-f-hidden' => 'blur(24px)', '--vpa-f-visible' => 'blur(0)' ],
			],
			'flip' => [
				'soft' 		=> [ '--vpa-t-hidden' => 'perspective(800px) rotateX(-20deg)', '--vpa-t-visible' => 'perspective(800px) rotateX(0deg)', '--vpa-origin' => 'top center' ],
				'medium' 	=> [ '--vpa-t-hidden' => 'perspective(800px) rotateX(-40deg)', '--vpa-t-visible' => 'perspective(800px) rotateX(0deg)', '--vpa-origin' => 'top center' ],
				'hard' 		=> [ '--vpa-t-hidden' => 'perspective(800px) rotateX(-65deg)', '--vpa-t-visible' => 'perspective(800px) rotateX(0deg)', '--vpa-origin' => 'top center' ],
			],
		];

		/**
		 *	Everything a fresh install starts from: the first set of every part,
		 *	every knob in the middle, the delivered root size, the width and the
		 *	animation of Nino.css, the face the base unit delivers and the
		 *	framework's own colours
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *
		 *	@return 	array
		 */
		public static function defaults( string $libraryDir ): array {

			$parts = [];

			foreach( array_keys( self::PARTS ) as $part )
				$parts[$part] = (string) ( self::available( $libraryDir, $part )[0] ?? '' );

			$knobs = [];

			foreach( self::KNOBS as $tab => $kinds )
				$knobs[$tab] = array_fill_keys( array_keys( $kinds ), 0 );

			return [
				'format' 	=> self::FORMAT,
				'size' 		=> 0,
				'width' 	=> 0,
				// '' is what Nino.css does by itself: a fade and a rise, as fast as it is
				'motion' 	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
				'parts' 	=> $parts,
				'knobs' 	=> $knobs,
				'fonts' 	=> Fonts::DEFAULTS,
				// The palette half, in Colours' own vocabulary - two colours, five
				// knobs and the mode. Its defaults are the framework's own values, so
				// a setup that never opened the Colours tab compiles to the palette
				// library/base.css already declares
				'colours'	=> Colours::normalize( [] ),
				'compiled'=> [],
			];
		}

		/**
		 *	The word a stylesheet declares a knob position under: -2 is least, -1
		 *	less, 0 default, +1 more and +2 most. The model keeps numbers, the
		 *	library keeps words, and this is the only place that knows both
		 *
		 *	@param		int				$position			-2 to 2; a number past the ends is the end
		 *
		 *	@return 	string
		 */
		public static function word( int $position ): string {
			return self::STEPS[ max( 0, min( count( self::STEPS ) - 1, $position + 2 ) ) ];
		}

		/**
		 *	What a knob is: 'scale' or 'choice' (see KNOBS)
		 *
		 *	@param		string		$tab					A key of KNOBS
		 *	@param		string		$knob					A name in KNOBS[$tab]
		 *
		 *	@return 	string								'' for a knob there is none of
		 */
		public static function kind( string $tab, string $knob ): string {
			return self::KNOBS[$tab][$knob] ?? '';
		}

		/**
		 *	How far a kind of knob reaches from the middle: two positions to each side
		 *	for a scale, one for a choice. A kind there is none of reaches nowhere
		 *
		 *	@param		string		$kind					'scale' or 'choice'
		 *
		 *	@return 	int
		 */
		public static function range( string $kind ): int {

			return match( $kind ) {
				'scale' 	=> 2,
				'choice' 	=> 1,
				default 	=> 0,
			};
		}

		/**
		 *	The words a ladder of that kind is declared under: all five of STEPS for a
		 *	scale, the inner three for a choice
		 *
		 *	@param		string		$kind					'scale' or 'choice'
		 *
		 *	@return 	array
		 */
		public static function words( string $kind ): array {

			$range = self::range( $kind );

			return array_slice( self::STEPS, 2 - $range, 2 * $range + 1 );
		}

		/**
		 *	The sets a part can be given, sorted, as they are on disk
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *
		 *	@return 	array										Set names, eg. [ 'v1', 'v2' ]
		 */
		public static function available( string $libraryDir, string $part ): array {

			$kind = self::PARTS[$part] ?? '';
			$found = [];

			if( $kind === 'frame' )
				foreach( glob( rtrim( $libraryDir, '/' ). '/'. $part. '/*', GLOB_ONLYDIR ) ?: [] as $dir )
					if( is_file( $dir. '/template.tpl' ) === true )
						$found[] = basename( $dir );

			if( $kind === 'set' )
				foreach( glob( rtrim( $libraryDir, '/' ). '/'. $part. '/*.css' ) ?: [] as $file )
					$found[] = basename( $file, '.css' );

			sort( $found, SORT_NATURAL );

			return $found;
		}

		/**
		 *	The file a part's choice resolves to
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *	@param		string		$which				'style' or, for a frame, 'template'
		 *
		 *	@return 	string								A path, '' when the choice does not exist
		 */
		public static function file( string $libraryDir, string $part, string $set, string $which = 'style' ): string {

			$libraryDir = rtrim( $libraryDir, '/' );

			// A set name is a file name, and a file name from a stored setup is
			// input: anything that could climb out of the library is not a set
			if( preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $set ) !== 1 || str_contains( $set, '..' ) === true )
				return '';

			if( isset( self::PARTS[$part] ) === false )
				return '';

			$path = self::PARTS[$part] === 'frame'
				? $libraryDir. '/'. $part. '/'. $set. '/'. ( $which === 'template' ? 'template.tpl' : 'style.css' )
				: $libraryDir. '/'. $part. '/'. $set. '.css';

			return is_file( $path ) === true ? $path : '';
		}

		/**
		 *	What a variant is called and what it is, for the panel to list it as.
		 *
		 *	Read from the stylesheet's own opening comment - `@name` and
		 *	`@description` on their own lines - rather than from a manifest
		 *	beside it. A set is one file, so a second file per set would be a
		 *	second file to keep in sync, and the person writing sets is going to
		 *	write a lot of them. A file without a @name is offered under its own
		 *	name, which is what a set in progress looks like.
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										[ name, description ]
		 */
		public static function describe( string $libraryDir, string $part, string $set ): array {

			$file = self::file( $libraryDir, $part, $set );
			$read = [ 'name' => $set, 'description' => '' ];

			if( $file === '' )
				return $read;

			// The first comment block only: a @name further down belongs to a
			// rule somebody documented, not to the file
			$head = (string) @file_get_contents( $file, false, null, 0, 4096 );

			if( preg_match( '~/\*(.*?)\*/~s', $head, $block ) !== 1 )
				return $read;

			foreach( [ 'name', 'description' ] as $tag )
				if( preg_match( '/@'. $tag. '[ \t]+(.+)$/m', $block[1], $found ) === 1 )
					$read[$tag] = trim( (string) preg_replace( '/\s+/', ' ', $found[1] ) );

			return $read;
		}

		/**
		 *	Every variant a part can be given, described - what the panel's
		 *	picker is built out of
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *
		 *	@return 	array										[ set => [ name, description ] ]
		 */
		public static function catalogue( string $libraryDir, string $part ): array {

			$catalogue = [];

			foreach( self::available( $libraryDir, $part ) as $set )
				$catalogue[$set] = self::describe( $libraryDir, $part, $set );

			return $catalogue;
		}

		/**
		 *	A stored setup, held against the library that is really there. What
		 *	cannot be honoured is replaced and named - a compile from a setup
		 *	nobody checked is how a project ends up with a stylesheet missing a
		 *	part and no idea why - and a key that is none of this version's is
		 *	dropped and named.
		 *
		 *	The format is not looked at here: a request names none, and read() is
		 *	the one place a file is held to it.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data - the families a project installed are read off its disk
		 *	@param		array 		$raw					Whatever the file held, or the screen posted
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced or dropped
		 *
		 *	@return 	array										A setup safe to compile
		 */
		public static function normalize( array &$appData, array $raw, string $libraryDir, array &$notes = [] ): array {

			$setup = self::defaults( $libraryDir );

			foreach( array_keys( $raw ) as $key )
				if( in_array( $key, self::KEYS, true ) === false )
					$notes[] = 'the key "'. self::shown( $key ). '" is none of this version\'s - dropped';

			$setup['size'] = self::position( $raw['size'] ?? 0 );
			$setup['width'] = self::position( $raw['width'] ?? 0 );

			$motion = is_array( $raw['motion'] ?? null ) === true ? $raw['motion'] : [];

			foreach( array_keys( $motion ) as $key )
				if( isset( $setup['motion'][$key] ) === false )
					$notes[] = 'the animation has no "'. self::shown( $key ). '" - dropped';

			$effect 	= $motion['effect'] ?? null;
			$strength = $motion['strength'] ?? null;
			$speed 		= $motion['speed'] ?? null;

			$setup['motion'] = [
				'effect' 		=> is_string( $effect ) === true && isset( self::EFFECTS[$effect] ) === true ? $effect : '',
				'strength' 	=> is_string( $strength ) === true && in_array( $strength, self::STRENGTHS, true ) === true ? $strength : '',
				'speed' 		=> is_string( $speed ) === true && isset( self::SPEEDS[$speed] ) === true ? $speed : '',
			];

			$given = is_array( $raw['parts'] ?? null ) === true ? $raw['parts'] : [];

			foreach( array_keys( $given ) as $part )
				if( isset( self::PARTS[$part] ) === false )
					$notes[] = 'there is no part "'. self::shown( $part ). '" - dropped';

			foreach( array_keys( self::PARTS ) as $part ) {

				$available 	= self::available( $libraryDir, $part );
				$wanted 		= is_string( $given[$part] ?? null ) === true ? $given[$part] : '';

				if( $available === [] ) {
					$notes[] = 'the library holds no set for "'. $part. '"';
					$setup['parts'][$part] = '';
				}
				else if( in_array( $wanted, $available, true ) === true )
					$setup['parts'][$part] = $wanted;
				else if( $wanted !== '' )
					$notes[] = '"'. $part. '" names the set "'. self::shown( $wanted ). '", which is not in the library - using "'. $available[0]. '"';
			}

			/*	Where every knob stands. A knob is a position of its own tab, held to
				the range of its kind, and a name that is none of the tab's - or a tab that
				is none of the three - is dropped with a note rather than carried: a
				position nothing reads is a position nobody can find again. One that is
				missing is 0	*/
			$given = is_array( $raw['knobs'] ?? null ) === true ? $raw['knobs'] : [];

			foreach( $given as $tab => $values ) {

				if( isset( self::KNOBS[$tab] ) === false ) {
					$notes[] = 'there is no tab "'. self::shown( $tab ). '" with knobs - dropped';
					continue;
				}

				foreach( array_keys( is_array( $values ) === true ? $values : [] ) as $knob )
					if( isset( self::KNOBS[$tab][$knob] ) === false )
						$notes[] = 'the tab "'. $tab. '" has no knob "'. self::shown( $knob ). '" - dropped';
			}

			foreach( self::KNOBS as $tab => $kinds )
				foreach( $kinds as $knob => $kind )
					$setup['knobs'][$tab][$knob] = self::position( is_array( $given[$tab] ?? null ) === true ? ( $given[$tab][$knob] ?? 0 ) : 0, $kind );

			/*	The five font roles. A family has to be one the role's select offers -
				the code is given the monospace ones only, and the families the project
				installed are among them - and a weight one of the three words;
				anything else is what the role starts from. A size is a number of rem
				held inside the bounds of the role, and what is no number is the default
				(see Fonts::size()). The code has no case switch: capitals in a block of
				code are not a choice anybody makes	*/
			$given = is_array( $raw['fonts'] ?? null ) === true ? $raw['fonts'] : [];

			foreach( array_keys( $given ) as $role )
				if( in_array( $role, Fonts::ROLES, true ) === false )
					$notes[] = 'there is no font role "'. self::shown( $role ). '" - dropped';

			foreach( Fonts::ROLES as $role ) {

				$stored = is_array( $given[$role] ?? null ) === true ? $given[$role] : [];

				foreach( array_keys( $stored ) as $key )
					if( isset( Fonts::DEFAULTS[$role][$key] ) === false )
						$notes[] = 'the font role "'. $role. '" has no "'. self::shown( $key ). '" - dropped';

				$family = $stored['family'] ?? null;
				$weight = $stored['weight'] ?? null;

				$setup['fonts'][$role] = [
					'family' 		=> is_string( $family ) === true && in_array( $family, Fonts::options( $appData, $role ), true ) === true ? $family : Fonts::DEFAULTS[$role]['family'],
					'weight' 		=> is_string( $weight ) === true && in_array( $weight, Fonts::WEIGHTS, true ) === true ? $weight : Fonts::DEFAULTS[$role]['weight'],
					'uppercase' => $role !== 'code' && ( $stored['uppercase'] ?? false ) === true,
					'size' 			=> Fonts::size( $role, $stored['size'] ?? '' ),
				];
			}

			/*	The palette. Normalized by Colours rather than here, because the
				vocabulary is its own - a hex, five positions and the mode, none of
				them a step word - and a second copy of those tables in this file is a
				second place for them to drift	*/
			$setup['colours'] = Colours::normalize( is_array( $raw['colours'] ?? null ) ? $raw['colours'] : [] );

			$setup['compiled'] = is_array( $raw['compiled'] ?? null ) ? $raw['compiled'] : [];

			return $setup;
		}

		/**
		 *	One position, as a number inside the range of its kind: -2 to 2 for a scale
		 *	(the default kind - the root size and the width are scales as well), -1 to 1
		 *	for a choice. Anything else - a word, an array, and a number past the ends -
		 *	is the middle, which is the template as it is
		 *
		 *	@param		mixed			$value				Straight off the wire
		 *	@param		string		$kind					'scale' or 'choice'
		 *
		 *	@return 	int
		 */
		public static function position( mixed $value, string $kind = 'scale' ): int {

			if( is_string( $value ) === true )
				$value = is_numeric( $value ) === true ? (float) $value : null;

			if( is_int( $value ) === false && is_float( $value ) === false )
				return 0;

			$position = (int) round( (float) $value );
			$range = self::range( $kind );

			return $position >= -$range && $position <= $range ? $position : 0;
		}

		/**
		 *	The step a knob stands at, as the word its stylesheet declares it
		 *	under
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$tab					A key of KNOBS
		 *	@param		string		$knob					A name in KNOBS[$tab]
		 *
		 *	@return 	string								One of STEPS
		 */
		public static function step( array $setup, string $tab, string $knob ): string {
			return self::word( self::position( $setup['knobs'][$tab][$knob] ?? 0, self::kind( $tab, $knob ) ) );
		}

		/**
		 *	Which knobs a set answers to.
		 *
		 *	Read out of the stylesheet itself rather than out of a manifest
		 *	beside it, the same bargain @name makes: a set is one file, and the
		 *	person writing sets is going to write a lot of them. A set that
		 *	declares --content-section-space--least / --less / --default / --more /
		 *	--most answers to Section space; one that does not, does not, and the
		 *	panel greys that knob out for it.
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										Knob names, in KNOBS order
		 */
		public static function knobs( string $libraryDir, string $part, string $set ): array {
			return array_keys( self::ladders( $libraryDir, $part, $set ) );
		}

		/**
		 *	The ladders a set declares for each knob it answers to: the one named
		 *	after the knob and, where it has them, those named after an aspect of
		 *	it (see KNOB_TOKEN). What the compiler writes a selection line for.
		 *
		 *	A ladder is complete when it is declared under every word of its kind - the
		 *	five of a scale, the three of a choice (see words()) - and a knob is
		 *	published when its own ladder is and so is each of its aspects. A knob with
		 *	less or more words than its kind has, or with an aspect that has, is not in
		 *	the answer: the panel greys it out
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										knob => [ name, ... ], the knob's own first and the others as they are declared; a name is the ladder without its step
		 */
		public static function ladders( string $libraryDir, string $part, string $set ): array {

			$file = self::file( $libraryDir, $part, $set );

			if( $file === '' || isset( self::KNOBS[$part] ) === false )
				return [];

			$css = self::uncomment( (string) @file_get_contents( $file ) );

			preg_match_all( '/'. sprintf( preg_quote( self::KNOB_TOKEN, '/' ), preg_quote( $part, '/' ), '([a-z0-9]+(?:-[a-z0-9]+)*)', '('. implode( '|', self::STEPS ). ')' ). '\s*:/', $css, $found, PREG_SET_ORDER );

			// The words each name is declared under - once each, however often a media query says it again
			$declared = [];

			foreach( $found as $hit )
				$declared[ $hit[1] ][ $hit[2] ] = true;

			$out = [];

			foreach( self::KNOBS[$part] as $knob => $kind ) {

				if( self::complete( $declared[$knob] ?? [], $kind ) === false )
					continue;

				$names = [ $knob ];

				foreach( array_keys( $declared ) as $name )
					if( str_starts_with( $name, $knob. '-' ) === true )
						$names[] = $name;

				foreach( $names as $name )
					if( self::complete( $declared[$name], $kind ) === false )
						continue 2;

				$out[$knob] = $names;
			}

			return $out;
		}

		/**
		 *	Whether the words a name is declared under are those of a ladder of that
		 *	kind: every one of them and no other
		 *
		 *	@param		array 		$declared			word => true
		 *	@param		string		$kind					'scale' or 'choice'
		 *
		 *	@return 	bool
		 */
		private static function complete( array $declared, string $kind ): bool {

			$words = self::words( $kind );

			return count( $declared ) === count( $words ) && array_diff( $words, array_keys( $declared ) ) === [];
		}

		/**
		 *	A stylesheet without its comments - shared with the compiler, which
		 *	scans the same ladders for the same reason
		 *
		 *	@param		string		$css
		 *
		 *	@return 	string
		 */
		public static function uncomment( string $css ): string {

			return (string) preg_replace( '~/\*.*?\*/~s', '', $css );
		}

		/**
		 *	Read the stored setup, normalised.
		 *
		 *	A file of another format - or none - is read as the defaults, and the
		 *	first says so: the file itself stays where it is until the next save
		 *	writes the new one. Nothing maps what the older shape chose onto this
		 *	one: there are no projects to carry over, and a mapping would be a
		 *	second model to keep right
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced
		 *
		 *	@return 	array
		 */
		public static function read( array &$appData, string $libraryDir, array &$notes = [] ): array {

			$raw = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );
			$raw = is_array( $raw ) === true ? $raw : [];

			if( $raw !== [] && ( $raw['format'] ?? null ) !== self::FORMAT ) {
				$notes[] = ltrim( self::PATH, '/' ). ' was written in another format - it is not read, the defaults stand in until the next save';
				$raw = [];
			}

			return self::normalize( $appData, $raw, $libraryDir, $notes );
		}

		/**
		 *	Write the setup back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	bool
		 */
		public static function write( array &$appData, array $setup ): bool {

			$setup['format'] = self::FORMAT;

			return \Nino\Filesystem::putFileContent( $appData, self::PATH, $setup );
		}

		/**
		 *	A key out of a file or a request, short enough to stand in a note.
		 *	Whatever it is, it is shown as text and cut where it runs on
		 *
		 *	@param		mixed			$key
		 *
		 *	@return 	string
		 */
		private static function shown( mixed $key ): string {
			return substr( (string) $key, 0, 40 );
		}

	}
}
