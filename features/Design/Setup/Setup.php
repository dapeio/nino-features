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
	 *										Every value is normalised against the library that is
	 *										actually present: a part naming a set that is not there
	 *										falls back to the first one that is, and says so, rather
	 *										than compiling a stylesheet with a hole in it.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Setup {

		public const string PATH = '/data/design.php';

		// The shape's own version, so a later format can recognise an older
		// file rather than misreading it
		public const int FORMAT = 1;

		/*	The parts, in the order they are compiled - which is also the order
			they are shown in. `frame` parts bring a template and are chosen from
			library/<part>/<set>/; the rest are one stylesheet each, from
			library/sets/<part>/<set>.css */
		public const array PARTS = [
			'header' 	=> 'frame',
			'footer' 	=> 'frame',
			'atf' 		=> 'set',
			'section' => 'set',
			'article' => 'set',
			'buttons' => 'set',
			'forms' 	=> 'set',
			'lists' 	=> 'set',
			'blocks' 	=> 'set',
		];

		// What a knob can be set to, and what a part inherits when it names
		// nothing of its own
		public const array STEPS = [ 'less', 'default', 'more' ];

		/*	The knobs, and nothing else. The vocabulary is Nino's own - the four
			the kernel's Design module published as its raster group before the
			look left the core, minus the one that is the root size here (which
			has a select of its own under Global).

			Fixed rather than per set, and that is the whole point: a knob named
			"Abstände" means the same thing on a section as on a form, so moving
			it globally means something. A set that invented its own vocabulary
			would give every part a private language and the global position
			nothing to be the position of.

			What each one is called, what the terse note beside it says and what
			its three steps are called live in the panel's text files - one knob
			is one key there, in every locale.	*/
		public const array KNOBS = [ 'volume', 'spacing', 'shaping', 'measure' ];

		/*	How a set says which knobs it answers to: one triple per knob, named
			after the part and the knob and nothing else.

			  --section-spacing--less / --default / --more

			Declaring the triple *is* publishing the knob - the panel offers
			exactly what a set declares, so a handle the stylesheet does not
			answer to can never be offered. The @knob line in a set's own
			opening comment is documentation for whoever reads the file; the
			panel reads the declarations */
		public const string KNOB_TOKEN = '--%s-%s--%s';

		// The root size, as the percentage pair Nino.css wants: below the
		// 768px breakpoint, and from it. Relative, never a length - see
		// --base-size in Nino.css
		public const array SIZES = [
			's' => [ '93.75%', '106.25%' ],
			'm' => [ '100%', '112.5%' ],
			'l' => [ '106.25%', '118.75%' ],
		];

		/**
		 *	Everything a fresh install starts from: the first set of every part,
		 *	no deviations, the delivered root size
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *
		 *	@return 	array
		 */
		public static function defaults( string $libraryDir ): array {

			$parts = [];

			foreach( self::PARTS as $part => $kind ) {
				$available = self::available( $libraryDir, $part );
				$parts[$part] = [ 'set' => (string) ( $available[0] ?? '' ), 'knobs' => [], 'sha' => '' ];
			}

			return [
				'format' 	=> self::FORMAT,
				'parts' 	=> $parts,
				// Where every knob stands for the whole design, and where a part
				// stands until it is moved on its own
				'knobs' 	=> array_fill_keys( self::KNOBS, 'default' ),
				'size' 		=> 'm',
				'compiled'=> [],
			];
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
				foreach( glob( rtrim( $libraryDir, '/' ). '/sets/'. $part. '/*.css' ) ?: [] as $file )
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
			if( preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $set ) !== 1 || str_contains( $set, '..' ) === true )
				return '';

			$path = ( self::PARTS[$part] ?? '' ) === 'frame'
				? $libraryDir. '/'. $part. '/'. $set. '/'. ( $which === 'template' ? 'template.tpl' : 'style.css' )
				: $libraryDir. '/sets/'. $part. '/'. $set. '.css';

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
		 *	part and no idea why
		 *
		 *	@param		array 		$raw					Whatever the file held
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced
		 *
		 *	@return 	array										A setup safe to compile
		 */
		public static function normalize( array $raw, string $libraryDir, array &$notes = [] ): array {

			$setup = self::defaults( $libraryDir );

			/*	The global position of every knob. One value for the whole
				design was what the first cut had, and it could only ever say "a
				bit more of everything" - Abstände and Ecken are not the same
				decision. A setup written before this reaches here as `step`,
				and seeds every knob with it rather than being thrown away */
			$seed = in_array( $raw['step'] ?? null, self::STEPS, true ) === true ? (string) $raw['step'] : 'default';
			$setup['knobs'] = [];

			foreach( self::KNOBS as $knob ) {
				$given = $raw['knobs'][$knob] ?? null;
				$setup['knobs'][$knob] = in_array( $given, self::STEPS, true ) === true ? (string) $given : $seed;
			}

			$size = (string) ( $raw['size'] ?? '' );
			if( isset( self::SIZES[$size] ) === true )
				$setup['size'] = $size;

			foreach( self::PARTS as $part => $kind ) {

				$stored 		= is_array( $raw['parts'][$part] ?? null ) ? $raw['parts'][$part] : [];
				$available 	= self::available( $libraryDir, $part );
				$wanted 		= (string) ( $stored['set'] ?? '' );

				if( $available === [] ) {
					$notes[] = 'the library holds no set for "'. $part. '"';
					$setup['parts'][$part]['set'] = '';
				}
				else if( in_array( $wanted, $available, true ) === true )
					$setup['parts'][$part]['set'] = $wanted;
				else if( $wanted !== '' )
					$notes[] = '"'. $part. '" names the set "'. $wanted. '", which is not in the library - using "'. $available[0]. '"';

				/*	A knob this part was moved at on its own. Only the knobs
					that were actually decided are written: a knob not named here
					follows the global position and keeps following it when that
					moves, which is a different state from one that happens to
					name today's value */
				$knobs = [];

				foreach( self::KNOBS as $knob )
					if( in_array( $stored['knobs'][$knob] ?? null, self::STEPS, true ) === true )
						$knobs[$knob] = (string) $stored['knobs'][$knob];

				$setup['parts'][$part]['knobs'] = $knobs;

				$setup['parts'][$part]['sha'] = (string) ( $stored['sha'] ?? '' );
			}

			$setup['compiled'] = is_array( $raw['compiled'] ?? null ) ? $raw['compiled'] : [];

			return $setup;
		}

		/**
		 *	The step a knob stands at, innermost decision first: the part's own
		 *	where it names one, else the global position. A level that named
		 *	nothing is not a level set to "default" - it is one that keeps
		 *	following whatever the level above moves to
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$part					A key of PARTS, '' for the global position
		 *	@param		string		$knob					A key of KNOBS
		 *
		 *	@return 	string								One of STEPS
		 */
		public static function step( array $setup, string $part, string $knob ): string {

			if( $part !== '' ) {
				$own = $setup['parts'][$part]['knobs'][$knob] ?? null;
				if( in_array( $own, self::STEPS, true ) === true )
					return (string) $own;
			}

			$global = $setup['knobs'][$knob] ?? null;

			return in_array( $global, self::STEPS, true ) === true ? (string) $global : 'default';
		}

		/**
		 *	Which knobs a set answers to.
		 *
		 *	Read out of the stylesheet itself rather than out of a manifest
		 *	beside it, the same bargain @name makes: a set is one file, and the
		 *	person writing sets is going to write a lot of them. A set that
		 *	declares --section-spacing--less / --default / --more answers to
		 *	Abstände; one that does not, does not, and the panel offers it
		 *	nothing to turn there.
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$part					A key of PARTS
		 *	@param		string		$set					A set name
		 *
		 *	@return 	array										Knob keys, in KNOBS order
		 */
		public static function knobs( string $libraryDir, string $part, string $set ): array {

			$file = self::file( $libraryDir, $part, $set );

			if( $file === '' || ( self::PARTS[$part] ?? '' ) !== 'set' )
				return [];

			// Without comments: a skeleton documents the shape of a triple, and
			// an example in a docblock is not a declaration
			$css 	= self::uncomment( (string) @file_get_contents( $file ) );
			$found = [];

			foreach( self::KNOBS as $knob )
				foreach( self::STEPS as $step )
					if( str_contains( $css, sprintf( self::KNOB_TOKEN, $part, $knob, $step ). ':' ) === true ) {
						$found[] = $knob;
						break;
					}

			return $found;
		}

		/**
		 *	Every knob any of the chosen sets answers to - what the global
		 *	position is a position of. A knob nothing follows is a knob worth
		 *	not offering
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	array										Knob keys, in KNOBS order
		 */
		public static function knobsInUse( string $libraryDir, array $setup ): array {

			$found = [];

			foreach( self::PARTS as $part => $kind )
				foreach( self::knobs( $libraryDir, $part, (string) ( $setup['parts'][$part]['set'] ?? '' ) ) as $knob )
					$found[$knob] = true;

			return array_values( array_filter( self::KNOBS, static fn( string $knob ): bool => isset( $found[$knob] ) ) );
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
		 *	Read the stored setup, normalised
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced
		 *
		 *	@return 	array
		 */
		public static function read( array &$appData, string $libraryDir, array &$notes = [] ): array {

			$raw = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );

			return self::normalize( is_array( $raw ) === true ? $raw : [], $libraryDir, $notes );
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

	}
}
