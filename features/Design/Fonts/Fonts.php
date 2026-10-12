<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design\Fonts		see features/Design/Design.php for the feature's
 *											own docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Fonts							The five font roles of Nino.css - title, subtitle, text,
	 *										button and code - and the families a role can be given.
	 *
	 *										Nino.css reads a role as a family, a weight, a transform
	 *										and a size (--fontfamily-<role>, --font-<role>-weight,
	 *										-transform and -size) and sets every one of them to what
	 *										its rules had before there were roles. This class decides
	 *										what the compiler writes over those defaults: the
	 *										@font-face rules of every family a role uses, and the
	 *										lines of :root that say which family, which weight, which
	 *										case and which size - only where the setup chose one. A
	 *										role with no family writes no family line, and Nino.css
	 *										keeps the system stack it has for it.
	 *
	 *										The families are the two the setup wizard's base unit
	 *										delivers as woff2 files and three system stacks that
	 *										need no file at all - a table in code - and, after them,
	 *										every family the project installed: a directory below
	 *										assets/fonts/ of its public root, <slug>/, holding the
	 *										font.json, the LICENSE and the woff2 files the archive of
	 *										webfonts.getnino.dev brought (see Webfonts). families() is
	 *										the one place that lists them, so that an installed
	 *										family joins the same list and the same select without a
	 *										line of the compiler or the panel changing. It reads the
	 *										project, and so takes the app data.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Fonts {

		// The roles, in the order they are shown in and written in
		public const array ROLES = [ 'title', 'subtitle', 'text', 'button', 'code' ];

		// What a weight can be asked for. A family answers with the weight it has
		// nearest to the number beside the word (see weight())
		public const array WEIGHTS = [ 'light', 'normal', 'bold' ];

		private const array TARGETS = [ 'light' => 300, 'normal' => 500, 'bold' => 800 ];

		/*	A system stack has no weights of its own to choose from, so the word is
			written as what it means to a browser: 300, the keyword, the keyword	*/
		private const array KEYWORDS = [ 'light' => '300', 'normal' => 'normal', 'bold' => 'bold' ];

		/*	What a fresh setup gives each role - the faces the base unit delivers: the
			title in League Spartan, the rest in Source Sans 3, and the code in the
			system's monospace, which is the empty family	*/
		public const array DEFAULTS = [
			'title' 		=> [ 'family' => 'league-spartan', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'subtitle' 	=> [ 'family' => 'source-sans-3', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'text' 			=> [ 'family' => 'source-sans-3', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'button' 		=> [ 'family' => 'source-sans-3', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'code' 			=> [ 'family' => '', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
		];

		/*	The size Nino.css gives each role from 768px, in rem: --font-title-size
			is the --text-4 step of its scale (1.6 times the text size), the subtitle
			--text-2 (1.125), the text 1rem, the button --fontsize-btn (1rem) and the
			code .875rem. The panel shows it as the value of the size field of a role
			that chose none, and keeps a size typed that comes to it as the empty one,
			which is this size. The bounds below run over it: a role can be made
			smaller than it, and larger	*/
		public const array INHERITED = [ 'title' => '1.6', 'subtitle' => '1.125', 'text' => '1', 'button' => '1', 'code' => '0.875' ];

		/*	What a size can be fixed at, in rem, as [ least, most ] for each role. A
			size typed outside it is held at the nearer end (see size()), so that no
			role can be typed into a line nobody can read, or one that fills the page.
			Every bound is on a tenth, the step of the field a size is typed in. The
			spinner of a number field counts from the least, so both ends of a range
			are sizes it reaches and the browser finds valid	*/
		public const array BOUNDS = [
			'title' 		=> [ 1, 4 ],
			'subtitle' 	=> [ 0.8, 2.5 ],
			'text' 			=> [ 0.8, 1.5 ],
			'button' 		=> [ 0.6, 1.5 ],
			'code' 			=> [ 0.6, 1.3 ],
		];

		/*	The stacks Nino.css puts after nothing: the system fonts a role has until
			a family is chosen for it, and the ones a webface is followed by. Copied
			from the :root of Nino.css, which tests/design-smoke.php reads them
			against - a stack that drifts from the kernel's would be a different
			fallback than the page had	*/
		public const string STACK_SANS = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
		public const string STACK_MONO = 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';

		private const string STACK_SERIF = 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif';

		// Where the families a project installed live, one directory each, named by the slug: below
		// the public root of the project, because a browser loads the files from there. The url
		// of a file is the same path below the fill every stylesheet of a project writes for it
		public const string DIR = '/assets/fonts';
		public const string URL = '[[/nino/public]]/assets/fonts';

		/**
		 *	The directory the installed families live in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									Absolute, existing or not: assets/fonts below the public root
		 */
		public static function dir( array &$appData ): string {

			return rtrim( \Nino\Filesystem::getPublicPath( $appData ), '/' ). self::DIR;
		}

		/**
		 *	Every family a role can be given, by slug: the ones the feature brings
		 *	(see builtin()) and after them the ones the project installed (see
		 *	installed()).
		 *
		 *	A family has a name (what font-family calls it), a category (the
		 *	grouping of Google Fonts: sans, serif, display, handwriting, mono,
		 *	other), the weights it has files for and those files. A family with no
		 *	files is a system stack: it brings the stack itself and no @font-face.
		 *	The url of a file is written into the stylesheet as it stands, so a
		 *	family carries the fill or the path its files are served from.
		 *
		 *	A file that carries a weight axis - one file for all the weights from 100
		 *	to 900, say - has the two numbers as its weight, '100 900'; the family
		 *	lists nothing of it under 'weights' and says [ 100, 900 ] under 'axis'.
		 *	An installed family says under 'installed' where it came from.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										slug => [ name, category, weights, files ] (and stack, for a system family; axis and installed, for an installed one)
		 */
		public static function families( array &$appData ): array {

			return self::builtin() + self::installed( $appData );
		}

		/**
		 *	The families the feature brings: the two the setup wizard's base unit
		 *	delivers as woff2 files, and three system stacks
		 *
		 *	@return 	array										slug => [ name, category, weights, files ] (and stack, for a system family)
		 */
		public static function builtin(): array {

			return [
				'source-sans-3' => [
					'name' 			=> 'Source Sans 3',
					'category' 	=> 'sans',
					'weights' 	=> [ 400, 700 ],
					'files' 		=> [
						[ 'weight' => 400, 'style' => 'normal', 'url' => '[[/nino/public]]/fonts/source-sans-3-regular.woff2' ],
						[ 'weight' => 700, 'style' => 'normal', 'url' => '[[/nino/public]]/fonts/source-sans-3-bold.woff2' ],
					],
				],
				'league-spartan' => [
					'name' 			=> 'League Spartan',
					'category' 	=> 'sans',
					'weights' 	=> [ 700 ],
					'files' 		=> [
						[ 'weight' => 700, 'style' => 'normal', 'url' => '[[/nino/public]]/fonts/league-spartan.woff2' ],
					],
				],
				'system-sans' => [
					'name' 			=> 'System sans-serif',
					'category' 	=> 'sans',
					'weights' 	=> [],
					'files' 		=> [],
					'stack' 		=> 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
				],
				'system-serif' => [
					'name' 			=> 'System serif',
					'category' 	=> 'serif',
					'weights' 	=> [],
					'files' 		=> [],
					'stack' 		=> self::STACK_SERIF,
				],
				'system-mono' => [
					'name' 			=> 'System monospace',
					'category' 	=> 'mono',
					'weights' 	=> [],
					'files' 		=> [],
					'stack' 		=> self::STACK_MONO,
				],
			];
		}

		/**
		 *	The families the project installed: every directory below assets/fonts/
		 *	that holds a font.json of the archive's shape (see Webfonts::manifest())
		 *	whose slug is the name of the directory, sorted by slug. A directory that
		 *	does not is left out, and broken() says why
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										slug => the family, as families() has it
		 */
		public static function installed( array &$appData ): array {

			return array_filter( self::_scan( $appData ), static fn( array|string $found ): bool => is_array( $found ) );
		}

		/**
		 *	The directories below assets/fonts/ that are no installed family, each
		 *	with the reason: an interrupted copy, a font.json that is not one, a
		 *	slug that is not the directory's, a slug that is one of the families
		 *	the feature brings. Only a directory with the name of a slug counts -
		 *	whatever else lies there is not this feature's to list or to remove
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										slug => why it is not listed
		 */
		public static function broken( array &$appData ): array {

			return array_filter( self::_scan( $appData ), static fn( array|string $found ): bool => is_string( $found ) );
		}

		/**
		 *	Look at every directory below assets/fonts/
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										slug => the family, or why the directory is none
		 */
		private static function _scan( array &$appData ): array {

			$dir 		= self::dir( $appData );
			$found 	= [];

			if( is_dir( $dir ) === false )
				return $found;

			foreach( @scandir( $dir ) ?: [] as $name )
				if( preg_match( Webfonts::SLUG, $name ) === 1 && is_dir( $dir. '/'. $name ) === true && is_link( $dir. '/'. $name ) === false )
					$found[$name] = self::_read( $dir. '/'. $name, $name );

			return $found;
		}

		/**
		 *	One directory, as the family it is - or the reason it is none
		 *
		 *	@param		string		$path					The directory
		 *	@param		string		$slug					Its name
		 *
		 *	@return 	array|string
		 */
		private static function _read( string $path, string $slug ): array|string {

			// A slug the feature brings is taken: an installed family of that name could only
			// stand in the select under the one that is there already
			if( isset( self::builtin()[$slug] ) === true )
				return 'the slug is one of the families the feature brings';

			// A manifest is a few hundred bytes. More than a megabyte is none
			$raw = @file_get_contents( $path. '/font.json', false, null, 0, Webfonts::MAX_LIST_BYTES );

			if( $raw === false )
				return 'there is no font.json';

			$data = json_decode( $raw, true );

			if( is_array( $data ) === false )
				return 'font.json is not valid json';

			$manifest = Webfonts::manifest( $data );

			if( is_string( $manifest ) === true )
				return 'font.json: '. $manifest;

			if( $manifest['slug'] !== $slug )
				return 'font.json names the slug "'. $manifest['slug']. '", the directory is "'. $slug. '"';

			$weights 	= [];
			$axis 		= [];
			$files 		= [];

			foreach( $manifest['files'] as $file ) {

				if( is_int( $file['weight'] ) === true )
					$weights[ $file['weight'] ] = $file['weight'];
				else if( $axis === [] )
					$axis = array_map( 'intval', explode( ' ', $file['weight'] ) );

				$files[] = [ 'weight' => $file['weight'], 'style' => $file['style'], 'url' => self::URL. '/'. $slug. '/'. $file['file'] ];
			}

			ksort( $weights );

			return [
				'name' 			=> $manifest['family'],
				'category' 	=> $manifest['category'],
				'weights' 	=> array_values( $weights ),
				'axis' 			=> $axis,
				'files' 		=> $files,
				'installed' => [ 'version' => $manifest['version'], 'license' => $manifest['license'], 'source' => $manifest['source'] ],
			];
		}

		/**
		 *	The slugs a role's select offers, in the order it shows them.
		 *
		 *	Every family for the four roles of running text, and for the code the
		 *	monospace ones - the system's own is the empty family, which is its
		 *	default, so a stack that says the same is not offered a second time.
		 *	'' is the first of them and always allowed: it leaves the role to
		 *	Nino.css.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$role					A key of ROLES
		 *
		 *	@return 	array										Slugs, '' first
		 */
		public static function options( array &$appData, string $role ): array {

			$slugs = [ '' ];

			foreach( self::families( $appData ) as $slug => $family ) {

				if( $role === 'code' && ( $family['category'] !== 'mono' || $family['files'] === [] ) )
					continue;

				$slugs[] = $slug;
			}

			return $slugs;
		}

		/**
		 *	The value of --fontfamily-<role> for a family, or '' where the role is
		 *	to keep the stack Nino.css has for it.
		 *
		 *	A webface is its name in quotes, then the stack of its role - a name
		 *	that needs quoting is how a single family is spelled, and the stack
		 *	after it stays bare (a stack quoted whole is one family called by that
		 *	name, which no system has). A system family is its stack alone.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$role					A key of ROLES
		 *	@param		string		$slug					A family, '' for none
		 *
		 *	@return 	string
		 */
		public static function line( array &$appData, string $role, string $slug ): string {

			return self::_line( $role, self::families( $appData )[$slug] ?? null );
		}

		/**
		 *	@param		string		$role					A key of ROLES
		 *	@param		array|null	$family				One entry of families(), null for none
		 *
		 *	@return 	string
		 */
		private static function _line( string $role, ?array $family ): string {

			if( $family === null )
				return '';

			if( $family['files'] === [] )
				return (string) $family['stack'];

			return '"'. $family['name']. '", '. ( $role === 'code' ? self::STACK_MONO : self::STACK_SANS );
		}

		/**
		 *	The value of --font-<role>-weight for a weight word.
		 *
		 *	A family answers with the weight it has nearest to what the word aims
		 *	at - 300, 500 or 800 - and the lighter of two that are as near. One
		 *	that has a weight axis answers with the weight itself where it lies on
		 *	the axis, and with the end of the axis it is nearer to where it does
		 *	not. One with no weights to choose from, which is a system stack, gets
		 *	the word as a browser reads it.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					A family, '' for none
		 *	@param		string		$word					One of WEIGHTS
		 *
		 *	@return 	string
		 */
		public static function weight( array &$appData, string $slug, string $word ): string {

			return self::_weight( self::families( $appData )[$slug] ?? null, $word );
		}

		/**
		 *	@param		array|null	$family				One entry of families(), null for none
		 *	@param		string		$word					One of WEIGHTS
		 *
		 *	@return 	string
		 */
		private static function _weight( ?array $family, string $word ): string {

			$word 		= isset( self::TARGETS[$word] ) === true ? $word : 'normal';
			$axis 		= (array) ( $family['axis'] ?? [] );
			$weights 	= (array) ( $family['weights'] ?? [] );

			if( $axis !== [] )
				return (string) max( $axis[0], min( $axis[1], self::TARGETS[$word] ) );

			if( $weights === [] )
				return self::KEYWORDS[$word];

			return (string) self::nearest( $weights, self::TARGETS[$word] );
		}

		/**
		 *	The weight of a list that stands nearest to a target - the lighter of two
		 *	that are as near, which is why the list is sorted first and only a weight
		 *	that is nearer takes the place of the one held
		 *
		 *	@param		array 		$weights		The weights a family has files for
		 *	@param		int				$target			The weight a word aims at
		 *
		 *	@return 	int
		 */
		private static function nearest( array $weights, int $target ): int {

			sort( $weights );

			$best = (int) $weights[0];

			foreach( $weights as $weight )
				if( abs( (int) $weight - $target ) < abs( $best - $target ) )
					$best = (int) $weight;

			return $best;
		}

		/**
		 *	A size as the setup keeps it: a number of rem written as short as it
		 *	can be, '' for the default.
		 *
		 *	A number is held inside the bounds of the role - below them it is the
		 *	least, above them the most - and rounded to three decimals, so 2.4,
		 *	'2.40' and '2.4' are one size, and '1.1250' is '1.125'. Whatever is not
		 *	a number - '', null, text, an array, NAN - is the default rather than a
		 *	size the page would show.
		 *
		 *	@param		string		$role					A key of ROLES
		 *	@param		mixed			$value				Straight off the wire
		 *
		 *	@return 	string
		 */
		public static function size( string $role, mixed $value ): string {

			$bounds = self::BOUNDS[$role] ?? null;

			if( $bounds === null || ( is_int( $value ) === false && is_float( $value ) === false && ( is_string( $value ) === false || is_numeric( $value ) === false ) ) )
				return '';

			// A float can be NAN, which is neither below a bound nor above one: it
			// would come out as the most. It is no number at all
			if( is_float( $value ) === true && is_nan( $value ) === true )
				return '';

			return rtrim( rtrim( number_format( max( $bounds[0], min( $bounds[1], (float) $value ) ), 3, '.', '' ), '0' ), '.' );
		}

		/**
		 *	The part of a compiled stylesheet that is the type: the @font-face rules
		 *	of every family a role uses, and the :root that sets the roles.
		 *
		 *	A family used by two roles is declared once. Nothing is written for a
		 *	role that chose nothing: no family line for the empty family, no
		 *	transform line unless it is uppercase, no size line for the default.
		 *	The weight is always written - it is a choice every role has - as the
		 *	number or keyword weight() makes of it.
		 *
		 *	A fixed title size also fixes the card title. Nino.css multiplies the
		 *	title role by 5/6, and by 25/32 from 768px: a factor which only gives the
		 *	size it had when the role stands on a step of the scale. On a fixed size
		 *	the card title would get smaller in rem at the breakpoint, so a
		 *	factor between the two stands in for both (see "The hero, the card
		 *	title and the variants" in Nino's design manual).
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$fonts				The setup's fonts, a role => [ family, weight, uppercase, size ]
		 *
		 *	@return 	string
		 */
		public static function css( array &$appData, array $fonts ): string {

			$families = self::families( $appData );
			$faces 		= [];
			$lines 		= [];

			foreach( self::ROLES as $role ) {

				$chosen = (array) ( $fonts[$role] ?? self::DEFAULTS[$role] );
				$slug 	= (string) ( $chosen['family'] ?? '' );
				$family = $families[$slug] ?? null;

				if( $family !== null && $family['files'] !== [] && isset( $faces[$slug] ) === false )
					$faces[$slug] = self::faces( $family );

				$line = self::_line( $role, $family );

				if( $line !== '' )
					$lines[] = "\t--fontfamily-". $role. ': '. $line. ';';

				$lines[] = "\t--font-". $role. '-weight: '. self::_weight( $family, (string) ( $chosen['weight'] ?? 'normal' ) ). ';';

				if( ( $chosen['uppercase'] ?? false ) === true )
					$lines[] = "\t--font-". $role. '-transform: uppercase;';

				$size = (string) ( $chosen['size'] ?? '' );

				if( $size !== '' ) {
					$lines[] = "\t--font-". $role. '-size: '. $size. 'rem;';

					if( $role === 'title' )
						$lines[] = "\t--card-title-scale: .8;";
				}
			}

			return ( $faces === [] ? '' : implode( "\n", $faces ). "\n\n" ). ":root {\n". implode( "\n", $lines ). "\n}";
		}

		/**
		 *	The @font-face rules of one family, a rule per file. The weight of a file
		 *	that carries a weight axis is the two numbers it runs between ('100 900'),
		 *	as css writes a range of weights: font-weight: 100 900
		 *
		 *	@param		array 		$family				One entry of families()
		 *
		 *	@return 	string
		 */
		private static function faces( array $family ): string {

			$rules = [];

			foreach( $family['files'] as $file )
				$rules[] = "@font-face {\n"
					. "\tfont-family: '". $family['name']. "';\n"
					. "\tfont-style: ". $file['style']. ";\n"
					. "\tfont-weight: ". $file['weight']. ";\n"
					. "\tfont-display: swap;\n"
					. "\tsrc: url('". $file['url']. "') format('woff2');\n"
					. '}';

			return implode( "\n", $rules );
		}

	}
}
