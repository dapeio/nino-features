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
				$parts[$part] = [ 'set' => (string) ( $available[0] ?? '' ), 'step' => null, 'sha' => '' ];
			}

			return [ 'format' => self::FORMAT, 'parts' => $parts, 'step' => 'default', 'size' => 'm', 'compiled' => [] ];
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

			$step = (string) ( $raw['step'] ?? '' );
			if( in_array( $step, self::STEPS, true ) === true )
				$setup['step'] = $step;

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

				// null is its own state: the part follows the global knob. A part
				// set to today's global value would otherwise stop following it
				// the moment that value moves
				$partStep = $stored['step'] ?? null;
				$setup['parts'][$part]['step'] = in_array( $partStep, self::STEPS, true ) === true ? (string) $partStep : null;

				$setup['parts'][$part]['sha'] = (string) ( $stored['sha'] ?? '' );
			}

			$setup['compiled'] = is_array( $raw['compiled'] ?? null ) ? $raw['compiled'] : [];

			return $setup;
		}

		/**
		 *	The step a part is compiled at: its own where it names one, the
		 *	global knob where it does not
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$part					A key of PARTS
		 *
		 *	@return 	string								One of STEPS
		 */
		public static function step( array $setup, string $part ): string {

			$own = $setup['parts'][$part]['step'] ?? null;

			return is_string( $own ) === true && in_array( $own, self::STEPS, true ) === true
				? $own
				: (string) ( $setup['step'] ?? 'default' );
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
