<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Themes			see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Themes						A theme is the face of a site: the whole setup of the panel -
	 *										the five templates, the position of every knob, the root
	 *										size, the width, the animation, the five font roles and the
	 *										palette - kept under a name with a description and a
	 *										stylesheet of its own, the custom css. Loading one puts it
	 *										on the screen as a draft; saving puts the screen into it.
	 *
	 *										One php file for each, an array as data/design.php is:
	 *
	 *										  'format'          1
	 *										  'name'            the name, and under name-<lang> its line
	 *										                    in another language
	 *										  'description'     the same, under description-<lang>
	 *										  'setup'           the seven keys of a setup that choose
	 *										                    something (see snapshot())
	 *										  'css'             the stylesheet of the theme
	 *										  'presets'         [] - the Builder's presets come later,
	 *										                    and the key is here from the first day so
	 *										                    that a file never changes shape for them
	 *
	 *										The themes the feature ships are files of its library
	 *										(library/themes/<slug>.php) and are loaded and duplicated,
	 *										never written or deleted. The project's own are
	 *										data/themes/<slug>.php, declared in the manifest so a backup
	 *										carries them, each replaced whole by one atomic write: no
	 *										theme file is ever read, changed and written back, so none
	 *										needs a lock. A project theme shadows nothing - a slug that
	 *										is shipped is read from the library, and a theme cannot be
	 *										made under it.
	 *
	 *										A slug names a file, so every method that takes one holds it
	 *										to SLUG first: what is none is no theme and never reaches a
	 *										path. What a css may carry is judged here too (see
	 *										refusal()), once, for the three doors a request comes
	 *										through.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Themes {

		// Where the project's own themes live, one file each, named by the slug
		public const string DIR = '/data/themes';

		// A slug is the name of a file, and lower-case ascii: the pattern the typefaces use
		public const string SLUG = '/^(?=.{1,64}$)[a-z0-9]+(?:-[a-z0-9]+)*$/D';

		// The shape's own version. A file of another one is no theme
		public const int FORMAT = 1;

		// The themes the feature ships, in the order the list shows them in
		public const array SHIPPED = [ 'nino', 'magazine', 'poster', 'statement', 'product', 'office' ];

		// How long a css, a name and a description may be: the size keeps data/design.php and every preview request sane
		public const int MAX_CSS = 65536;
		public const int MAX_NAME = 80;
		public const int MAX_DESCRIPTION = 400;

		/*	What a css is refused with, as the code of the failure and the English sentence
			for a log (see refusal()). The words of the person's language are the code's
			text under /_admin/error/<code>, in both text files	*/
		public const array REFUSALS = [
			'design_size' 	=> 'the css is longer than 64 KB',
			'design_import' => 'the css carries an @import',
			'design_script' => 'the css carries javascript: or a script',
			'design_tag' 		=> 'the css carries a closing style tag',
		];

		/**
		 *	The themes the feature ships: every php file of library/themes/ whose
		 *	name is a slug. The ones named in SHIPPED come first, in that order, and
		 *	any other the library has after them, sorted - a library that grows does
		 *	not need the constant
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *
		 *	@return 	array										slug => the path of its file
		 */
		public static function shipped( string $libraryDir ): array {

			$found = [];

			foreach( glob( rtrim( $libraryDir, '/' ). '/themes/*.php' ) ?: [] as $file ) {

				$slug = basename( $file, '.php' );

				if( preg_match( self::SLUG, $slug ) === 1 && is_file( $file ) === true )
					$found[$slug] = $file;
			}

			ksort( $found );

			$ordered = [];

			foreach( self::SHIPPED as $slug )
				if( isset( $found[$slug] ) === true )
					$ordered[$slug] = $found[$slug];

			return $ordered + $found;
		}

		/**
		 *	The themes of the project: every php file of data/themes/ whose name is a
		 *	slug. A link is none - a theme file is read by including it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										slug => the path of its file, sorted by slug
		 */
		public static function own( array &$appData ): array {

			$dir = \Nino\Filesystem::path( $appData, self::DIR );
			$found = [];

			if( is_dir( $dir ) === false )
				return $found;

			foreach( @scandir( $dir ) ?: [] as $name ) {

				if( str_ends_with( $name, '.php' ) === false )
					continue;

				$slug = substr( $name, 0, -4 );

				if( preg_match( self::SLUG, $slug ) === 1 && is_file( $dir. '/'. $name ) === true && is_link( $dir. '/'. $name ) === false )
					$found[$slug] = $dir. '/'. $name;
			}

			return $found;
		}

		/**
		 *	Whether a theme is there, shipped or the project's - a file that is a theme,
		 *	which read() decides
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$slug
		 *
		 *	@return 	bool
		 */
		public static function exists( array &$appData, string $libraryDir, string $slug ): bool {
			return self::read( $appData, $libraryDir, $slug ) !== null;
		}

		/**
		 *	Whether a slug is one of the themes the feature ships
		 *
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$slug
		 *
		 *	@return 	bool
		 */
		public static function isShipped( string $libraryDir, string $slug ): bool {
			return isset( self::shipped( $libraryDir )[$slug] ) === true;
		}

		/**
		 *	One theme as its file holds it. A slug that is shipped is read from the
		 *	library, whatever the project has under that name; any other from
		 *	data/themes/. Null for a slug that is none, a file that is not there, one
		 *	that does not return an array, one of another format and one with no name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$slug
		 *
		 *	@return 	array|null								The array of the file, as it is
		 */
		public static function read( array &$appData, string $libraryDir, string $slug ): ?array {

			if( preg_match( self::SLUG, $slug ) !== 1 )
				return null;

			$shipped = self::shipped( $libraryDir );

			if( isset( $shipped[$slug] ) === true )
				$raw = include $shipped[$slug];
			else {

				$path = \Nino\Filesystem::path( $appData, self::DIR. '/'. $slug. '.php' );

				// is_file() first: reading a .php file through the Filesystem is an include
				$raw = $path !== '' && is_file( $path ) === true && is_link( $path ) === false
					? \Nino\Filesystem::getFileContent( $appData, self::DIR. '/'. $slug. '.php', null )
					: null;
			}

			return is_array( $raw ) === true && ( $raw['format'] ?? null ) === self::FORMAT && is_string( $raw['name'] ?? null ) === true ? $raw : null;
		}

		/**
		 *	The list the tab draws: the shipped themes first, in the order of SHIPPED, then
		 *	the project's, sorted by name as it is shown
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *
		 *	@return 	array										[ slug, name, description, shipped ], see entry()
		 */
		public static function all( array &$appData, string $libraryDir, string $locale ): array {

			$shipped = self::shipped( $libraryDir );
			$list = [];
			$own = [];

			foreach( array_keys( $shipped ) as $slug ) {

				$raw = self::read( $appData, $libraryDir, $slug );

				if( $raw !== null )
					$list[] = self::entry( $slug, $raw, $locale, true );
			}

			foreach( array_keys( self::own( $appData ) ) as $slug ) {

				$raw = isset( $shipped[$slug] ) === true ? null : self::read( $appData, $libraryDir, $slug );

				if( $raw !== null )
					$own[] = self::entry( $slug, $raw, $locale, false );
			}

			usort( $own, static fn( array $a, array $b ): int => strnatcasecmp( $a['name'], $b['name'] ) ?: strcmp( $a['slug'], $b['slug'] ) );

			return array_merge( $list, $own );
		}

		/**
		 *	One row of the list: a theme under the name and the description of the
		 *	language of the workbench. A file with an empty name is listed under its slug
		 *
		 *	@param		string		$slug
		 *	@param		array 		$raw					The array of its file
		 *	@param		string		$locale				The language of the workbench
		 *	@param		bool			$shipped			Whether it is one of the feature's own
		 *
		 *	@return 	array										slug, name, description, shipped
		 */
		public static function entry( string $slug, array $raw, string $locale, bool $shipped ): array {

			$described = self::describe( $raw, $locale );

			return [
				'slug' 				=> $slug,
				'name' 				=> $described['name'] !== '' ? $described['name'] : $slug,
				'description' => $described['description'],
				'shipped' 		=> $shipped,
			];
		}

		/**
		 *	The name and the description of a theme in a language. The line of that
		 *	language stands beside the plain one under name-<lang> and
		 *	description-<lang>, the two letters of the language as Setup::describe()
		 *	has them (de for de_DE); it is taken where there is one, and the plain
		 *	one where there is not - the one rule a template's lines follow, in a
		 *	second place because the source is an array and not a comment. English is
		 *	the plain line
		 *
		 *	@param		array 		$raw					The array of a theme's file
		 *	@param		string		$locale				The language of the workbench, eg. 'de_DE'
		 *
		 *	@return 	array										name, description - '' for a line there is none of
		 */
		public static function describe( array $raw, string $locale ): array {

			$language = strtolower( substr( $locale, 0, 2 ) );
			$own = preg_match( '/^[a-z]{2}$/', $language ) === 1 && $language !== 'en';
			$read = [ 'name' => '', 'description' => '' ];

			foreach( array_keys( $read ) as $tag )
				foreach( $own === true ? [ $tag. '-'. $language, $tag ] : [ $tag ] as $line )
					if( is_string( $raw[$line] ?? null ) === true && trim( $raw[$line] ) !== '' ) {
						$read[$tag] = trim( $raw[$line] );
						break;
					}

			return $read;
		}

		/**
		 *	The lines in another language a file carries, as they are kept when it is
		 *	copied in: name-<lang> and description-<lang>, the language two letters of
		 *	the alphabet, the text a string
		 *
		 *	@param		array 		$raw					The array of a theme's file
		 *
		 *	@return 	array										'name-de' => text, ...
		 */
		public static function lines( array $raw ): array {

			$lines = [];

			foreach( $raw as $key => $value )
				if( is_string( $key ) === true && is_string( $value ) === true && preg_match( '/^(name|description)-[a-z]{2}$/D', $key, $found ) === 1 )
					$lines[$key] = self::_cut( $value, $found[1] === 'name' ? self::MAX_NAME : self::MAX_DESCRIPTION );

			return $lines;
		}

		/**
		 *	The setup a theme's file holds, as it is - the seven keys that choose
		 *	something, and whatever else somebody wrote there: the caller normalises it
		 *
		 *	@param		array 		$raw					The array of a theme's file
		 *
		 *	@return 	array
		 */
		public static function setup( array $raw ): array {
			return is_array( $raw['setup'] ?? null ) === true ? $raw['setup'] : [];
		}

		/**
		 *	The css a theme's file holds, its line breaks all one kind - what the screen
		 *	holds and what a setup keeps of its own, so that the two compare
		 *
		 *	@param		array 		$raw					The array of a theme's file
		 *
		 *	@return 	string
		 */
		public static function css( array $raw ): string {
			return is_string( $raw['css'] ?? null ) === true ? str_replace( [ "\r\n", "\r" ], "\n", $raw['css'] ) : '';
		}

		/**
		 *	A setup cut down to the seven keys that choose something - the size, the
		 *	width, the animation, the parts, the knobs, the fonts and the palette - in
		 *	the order of Setup::KEYS. The format, the theme, the css and the record of
		 *	what was compiled are not a theme's setup: the css stands beside it in the
		 *	file, and the other three are the project's. What a created, saved or
		 *	duplicated theme keeps, and what the chip "changed" compares
		 *
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	array
		 */
		public static function snapshot( array $setup ): array {

			$cut = [];

			foreach( Setup::KEYS as $key )
				if( in_array( $key, [ 'format', 'theme', 'css', 'compiled' ], true ) === false && array_key_exists( $key, $setup ) === true )
					$cut[$key] = $setup[$key];

			return $cut;
		}

		/**
		 *	A theme as its file keeps it: the name and the description cut to what the
		 *	panel allows, the setup a snapshot, the css, and no presets
		 *
		 *	@param		string		$name
		 *	@param		string		$description
		 *	@param		array 		$setup				A normalised setup, snapshot() cuts it
		 *	@param		string		$css
		 *	@param		array 		$lines				The lines in other languages that are kept, see lines()
		 *
		 *	@return 	array										The shape of the file, format first
		 */
		public static function record( string $name, string $description, array $setup, string $css, array $lines = [] ): array {

			return self::_shape( [
				'name' 				=> self::name( $name ),
				'description' => self::description( $description ),
				'setup' 			=> self::snapshot( $setup ),
				'css' 				=> $css,
			] + $lines );
		}

		/**
		 *	A name as it is kept: trimmed and cut to MAX_NAME characters. The slug of a
		 *	theme is made of the name as it is kept, so that the two agree
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		public static function name( string $text ): string {
			return self::_cut( $text, self::MAX_NAME );
		}

		/**
		 *	A description as it is kept: trimmed and cut to MAX_DESCRIPTION characters
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		public static function description( string $text ): string {
			return self::_cut( $text, self::MAX_DESCRIPTION );
		}

		/**
		 *	Write a theme of the project: the array in the shape of a file, with the
		 *	format set here, replacing the file whole in one atomic write. Whether a
		 *	slug may be written - not a shipped one, not one that is taken - is for the
		 *	action to decide; what is refused here is a slug that is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug
		 *	@param		array 		$theme				See record()
		 *
		 *	@return 	bool
		 */
		public static function write( array &$appData, string $slug, array $theme ): bool {

			if( preg_match( self::SLUG, $slug ) !== 1 )
				return false;

			return \Nino\Filesystem::putFileContent( $appData, self::DIR. '/'. $slug. '.php', self::_shape( $theme ) );
		}

		/**
		 *	Remove a theme of the project. A shipped theme is a file of the library and
		 *	is none of this method's: it names data/themes/ alone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug
		 *
		 *	@return 	bool										Whether the file is gone - false for a slug that is none and for a file that was not there
		 */
		public static function remove( array &$appData, string $slug ): bool {

			if( preg_match( self::SLUG, $slug ) !== 1 )
				return false;

			$path = \Nino\Filesystem::path( $appData, self::DIR. '/'. $slug. '.php' );

			if( $path === '' || is_file( $path ) === false )
				return false;

			@unlink( $path );
			clearstatcache( true, $path );

			return is_file( $path ) === false;
		}

		/**
		 *	The slug a name comes to: lower case, the German umlauts and the sharp s as
		 *	ae, oe, ue and ss, every other letter outside a-z, every digit outside 0-9 and
		 *	every run of anything else a single hyphen, the hyphens at the ends cut. At
		 *	most 64 characters: a longer one is cut at a hyphen where one stands within
		 *	the last 16 and does not end a word already, else hard. The server decides the
		 *	slug - the screen shows the name and takes none of its own
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string										'' where no letter or digit is left
		 */
		public static function slug( string $name ): string {

			$slug = strtr( mb_strtolower( $name, 'UTF-8' ), [ "\u{e4}" => 'ae', "\u{f6}" => 'oe', "\u{fc}" => 'ue', "\u{df}" => 'ss' ] );
			$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $slug ), '-' );

			if( strlen( $slug ) <= 64 )
				return $slug;

			$cut = substr( $slug, 0, 64 );
			$at = strrpos( $cut, '-' );

			// A hyphen right after the 64 characters says that they end a word: nothing is cut off the last one
			if( $slug[64] !== '-' && $at !== false && $at >= 48 )
				$cut = substr( $cut, 0, $at );

			return rtrim( $cut, '-' );
		}

		/**
		 *	The slug itself where no theme has it, else the first of <slug>-2, <slug>-3,
		 *	... that is free - what a copy is made under. The slug is cut as far as the
		 *	number needs, so that none is longer than a slug may be
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		string		$slug					A slug, see slug()
		 *
		 *	@return 	string
		 */
		public static function free( array &$appData, string $libraryDir, string $slug ): string {

			if( self::exists( $appData, $libraryDir, $slug ) === false )
				return $slug;

			for( $number = 2; ; $number++ ) {

				$suffix = '-'. $number;
				$candidate = rtrim( substr( $slug, 0, 64 - strlen( $suffix ) ), '-' ). $suffix;

				if( self::exists( $appData, $libraryDir, $candidate ) === false )
					return $candidate;
			}
		}

		/**
		 *	Whether a css may be taken in, and if not, the code it is refused with. On the
		 *	raw text, comments included - a comment is no place to smuggle an @import past a
		 *	check that strips comments.
		 *
		 *	  design_size		longer than 64 KB, which keeps data/design.php and every
		 *									preview request sane
		 *	  design_import	an @import, which loads a foreign file into every page, and
		 *									no theme may do that unseen
		 *	  design_script	javascript: or a script, vectors of old that a stylesheet
		 *									never needs
		 *	  design_tag		a closing style tag, which would end the inline style the
		 *									preview carries
		 *
		 *	A url() is allowed: the project's Content-Security-Policy decides what a page may
		 *	load
		 *
		 *	@param		string		$css
		 *
		 *	@return 	string										A key of REFUSALS, or '' where the css is fine
		 */
		public static function refusal( string $css ): string {

			if( strlen( $css ) > self::MAX_CSS )
				return 'design_size';

			if( preg_match( '/@import\b/i', $css ) === 1 )
				return 'design_import';

			// The < stands in a bracket in both patterns below: tests/markup-smoke.php takes a php string that opens a tag - a plain /<script/ - for markup
			if( preg_match( '/javascript\s*:/i', $css ) === 1 || preg_match( '/[<]script/i', $css ) === 1 )
				return 'design_script';

			if( preg_match( '/[<]\/style/i', $css ) === 1 )
				return 'design_tag';

			return '';
		}

		/**
		 *	The text of an export: the theme as json in the key order of the shape, with
		 *	the slashes and the characters of any language as they are, and a line break at
		 *	the end
		 *
		 *	@param		array 		$theme				The array of a theme's file
		 *
		 *	@return 	string										'' where json cannot hold it
		 */
		public static function json( array $theme ): string {

			$json = json_encode( self::_shape( $theme ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

			return $json === false ? '' : $json. "\n";
		}

		/**
		 *	The array of an export, or the code of why it is none: design_file where the
		 *	text is no json object, has no format of 1 or has no string name. Every other
		 *	key is taken as it is and judged where it is used - the setup by
		 *	Setup::normalize() when the theme is written, the css by refusal()
		 *
		 *	@param		string		$text
		 *
		 *	@return 	array|string
		 */
		public static function fromJson( string $text ): array|string {

			$data = json_decode( $text, true );

			if( is_array( $data ) === false || array_is_list( $data ) === true || ( $data['format'] ?? null ) !== self::FORMAT || is_string( $data['name'] ?? null ) === false )
				return 'design_file';

			return $data;
		}

		/**
		 *	The keys of a theme in the order of the file: the format, the name with its
		 *	lines in other languages, the description with its lines, the setup, the css and
		 *	the presets, which are none. A key that is none of those is not kept
		 *
		 *	@param		array 		$theme
		 *
		 *	@return 	array
		 */
		private static function _shape( array $theme ): array {

			$lines = self::lines( $theme );
			$shape = [ 'format' => self::FORMAT ];

			foreach( [ 'name', 'description' ] as $tag ) {

				$shape[$tag] = is_string( $theme[$tag] ?? null ) === true ? $theme[$tag] : '';

				$own = array_filter( array_keys( $lines ), static fn( string $key ): bool => str_starts_with( $key, $tag. '-' ) );
				sort( $own );

				foreach( $own as $key )
					$shape[$key] = $lines[$key];
			}

			$shape['setup'] = self::setup( $theme );
			$shape['css'] = is_string( $theme['css'] ?? null ) === true ? $theme['css'] : '';
			$shape['presets'] = [];

			return $shape;
		}

		/**
		 *	A name or a description as it is kept: trimmed and cut to its length
		 *
		 *	@param		string		$text
		 *	@param		int				$length				In characters
		 *
		 *	@return 	string
		 */
		private static function _cut( string $text, int $length ): string {
			return trim( mb_substr( trim( $text ), 0, $length ) );
		}

	}
}
