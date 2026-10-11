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
	 *										One model for the six tabs of the panel: the five
	 *										templates (header and footer bring markup, content,
	 *										buttons and components are one stylesheet each), the root
	 *										size, the width of the page and its animation, a position
	 *										for every named knob of the three template tabs, the five
	 *										font roles and the palette.
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
			it with the defaults and says so	*/
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

		/*	What a knob can be set to, as the words a stylesheet declares its three
			values under. The model keeps -1, 0 and +1; word() is the one place
			that maps one to the other	*/
		public const array STEPS = [ 'less', 'default', 'more' ];

		/*	The knobs, per tab, and nothing else. A knob belongs to one tab and has
			a name: what it is called, what the terse note beside it says and what
			its three steps are called live in the panel's text files, one key each
			in every locale. The meaning of each is fixed here and not per set, so
			that the templates of a tab stay exchangeable - what a set decides is
			the three values behind a knob, and whether it answers to it at all:

				content		hero-title, hero-text	the size of the hero's title and text
							quiet, loud				the size of the --quiet and --loud variants
							section-space			the vertical space of a section
							article-space			the space inside and between the parts of a card
							corners, border, shadow	the corners, the line and the shadow of a card
				buttons		small, big				the size of .nino-btn--small and --big
							space, corners			the padding and the radius
				components	scale					the type size of fields and tables
							space, corners, border	the space, the corners and the lines
							zebra					the stripes of a table
							labels					where the label of a field stands	*/
		public const array KNOBS = [
			'content' 		=> [ 'hero-title', 'hero-text', 'quiet', 'loud', 'section-space', 'article-space', 'corners', 'border', 'shadow' ],
			'buttons' 		=> [ 'small', 'big', 'space', 'corners' ],
			'components' 	=> [ 'scale', 'space', 'corners', 'border', 'zebra', 'labels' ],
		];

		/*	How a set says which knobs it answers to: one triple per knob, named
			after the tab and the knob and nothing else.

			  --content-section-space--less / --default / --more

			Declaring the triple *is* publishing the knob - the panel offers
			exactly what a set declares and greys out what it does not, so a handle
			the stylesheet does not answer to can never be offered. The @knob line
			in a set's own opening comment is documentation for whoever reads the
			file; the panel reads the declarations.

			A value that cannot say all a knob means - the stripes of a table are a
			background and a text colour - may be declared in more triples, named
			after the knob and an aspect of it (--components-zebra-ink--less ...).
			They are the knob's too: they are selected where it stands, and a
			stylesheet that declares one without the triple of the knob itself has
			published nothing */
		public const string KNOB_TOKEN = '--%s-%s--%s';

		/*	The root size, as the percentage pair Nino.css wants: below the
			768px breakpoint, and from it. Relative, never a length - see
			--base-size in Nino.css.

			A full step apart rather than a hair: at a browser default of 16px
			this is 15/18/21 from the breakpoint and 14/16/18 below it, which
			is a choice somebody can see on the page rather than one they have
			to measure. The middle position is the delivered size and does not
			move, so nothing compiled before this reads differently.	*/
		public const array SIZES = [
			's' => [ '87.5%', '93.75%' ],
			'm' => [ '100%', '112.5%' ],
			'l' => [ '112.5%', '131.25%' ],
		];

		/*	The width of the page, as the maximum width of a row and the space
			between the viewport and it, below 768px and from it. 0 is Nino.css as
			it is and writes nothing; each step to a side is one step of the space
			scale away from what the kernel has (--space-2, from 768px --space-3)	*/
		public const array WIDTHS = [
			-1 => [ '96rem', 'var(--space-1)', 'var(--space-2)' ],
			1 	=> [ '144rem', 'var(--space-3)', 'var(--space-4)' ],
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
		 *	animation of Nino.css, the faces the base unit delivers and the
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

			foreach( self::KNOBS as $tab => $names )
				$knobs[$tab] = array_fill_keys( $names, 0 );

			return [
				'format' 	=> self::FORMAT,
				'size' 		=> 'm',
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
		 *	The word a stylesheet declares a knob position under: -1 is less, 0 is
		 *	default and +1 is more. The model keeps numbers, the library keeps
		 *	words, and this is the only place that knows both
		 *
		 *	@param		int				$position			-1, 0 or 1
		 *
		 *	@return 	string
		 */
		public static function word( int $position ): string {
			return self::STEPS[ max( 0, min( 2, $position + 1 ) ) ];
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
		 *	@param		array 		$raw					Whatever the file held, or the screen posted
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced or dropped
		 *
		 *	@return 	array										A setup safe to compile
		 */
		public static function normalize( array $raw, string $libraryDir, array &$notes = [] ): array {

			$setup = self::defaults( $libraryDir );

			foreach( array_keys( $raw ) as $key )
				if( in_array( $key, self::KEYS, true ) === false )
					$notes[] = 'the key "'. self::shown( $key ). '" is none of this version\'s - dropped';

			$size = (string) ( is_string( $raw['size'] ?? null ) === true ? $raw['size'] : '' );
			if( isset( self::SIZES[$size] ) === true )
				$setup['size'] = $size;

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

			/*	Where every knob stands. A knob is a position of its own tab, and a
				name that is none of the tab's - or a tab that is none of the three -
				is dropped with a note rather than carried: a position nothing reads
				is a position nobody can find again. One that is missing is 0	*/
			$given = is_array( $raw['knobs'] ?? null ) === true ? $raw['knobs'] : [];

			foreach( $given as $tab => $values ) {

				if( isset( self::KNOBS[$tab] ) === false ) {
					$notes[] = 'there is no tab "'. self::shown( $tab ). '" with knobs - dropped';
					continue;
				}

				foreach( array_keys( is_array( $values ) === true ? $values : [] ) as $knob )
					if( in_array( $knob, self::KNOBS[$tab], true ) === false )
						$notes[] = 'the tab "'. $tab. '" has no knob "'. self::shown( $knob ). '" - dropped';
			}

			foreach( self::KNOBS as $tab => $names )
				foreach( $names as $knob )
					$setup['knobs'][$tab][$knob] = self::position( is_array( $given[$tab] ?? null ) === true ? ( $given[$tab][$knob] ?? 0 ) : 0 );

			/*	The five font roles. A family has to be one the role's select offers -
				the code is given the monospace ones only - and a weight one of the three
				words; anything else is what the role starts from. A size is a number of
				rem held inside the bounds of the role, and what is no number is the
				default (see Fonts::size()). The code has no case switch: capitals in a
				block of code are not a choice anybody makes	*/
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
					'family' 		=> is_string( $family ) === true && in_array( $family, Fonts::options( $role ), true ) === true ? $family : Fonts::DEFAULTS[$role]['family'],
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
		 *	One knob position, as a number: -1, 0 or 1. Anything else - a word, an
		 *	array, 2 - is the middle, which is the template as it is
		 *
		 *	@param		mixed			$value				Straight off the wire
		 *
		 *	@return 	int
		 */
		public static function position( mixed $value ): int {

			if( is_string( $value ) === true )
				$value = is_numeric( $value ) === true ? (float) $value : null;

			if( is_int( $value ) === false && is_float( $value ) === false )
				return 0;

			$position = (int) round( (float) $value );

			return $position >= -1 && $position <= 1 ? $position : 0;
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
			return self::word( self::position( $setup['knobs'][$tab][$knob] ?? 0 ) );
		}

		/**
		 *	Which knobs a set answers to.
		 *
		 *	Read out of the stylesheet itself rather than out of a manifest
		 *	beside it, the same bargain @name makes: a set is one file, and the
		 *	person writing sets is going to write a lot of them. A set that
		 *	declares --content-section-space--less / --default / --more answers to
		 *	Section space; one that does not, does not, and the panel greys that
		 *	knob out for it.
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										Knob names, in KNOBS order
		 */
		public static function knobs( string $libraryDir, string $part, string $set ): array {
			return array_keys( self::triples( $libraryDir, $part, $set ) );
		}

		/**
		 *	The triples a set declares for each knob it answers to: the one named
		 *	after the knob and, where it has them, those named after an aspect of
		 *	it (see KNOB_TOKEN). What the compiler writes a selection line for.
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										knob => [ name, ... ], the knob's own first and the others as they are declared; a name is the triple without its step
		 */
		public static function triples( string $libraryDir, string $part, string $set ): array {

			$file = self::file( $libraryDir, $part, $set );

			if( $file === '' || isset( self::KNOBS[$part] ) === false )
				return [];

			$css = self::uncomment( (string) @file_get_contents( $file ) );

			preg_match_all( '/--'. preg_quote( $part, '/' ). '-([a-z0-9]+(?:-[a-z0-9]+)*)--(?:less|default|more)\s*:/', $css, $found );

			$names = array_values( array_unique( $found[1] ) );
			$out = [];

			foreach( self::KNOBS[$part] as $knob ) {

				if( in_array( $knob, $names, true ) === false )
					continue;

				$out[$knob] = [ $knob ];

				foreach( $names as $name )
					if( str_starts_with( $name, $knob. '-' ) === true && in_array( $name, $out[$knob], true ) === false )
						$out[$knob][] = $name;
			}

			return $out;
		}

		/**
		 *	A stylesheet without its comments - shared with the compiler, which
		 *	scans the same triples for the same reason
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

			return self::normalize( $raw, $libraryDir, $notes );
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
