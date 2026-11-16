<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Themes\Brand	see _nino/Nino/Modules/Modules.php for the
 *												package-level docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The brand of the project: two colours, the tone of the grounds, the mode of the page and a font for
	 *										the titles, the text and the buttons - and the fonts there are
	 *
	 *										Every value is either the project's own or follows the suggestion of the theme the page comes
	 *										from (M1); an own value wins over any theme (M2) until it is taken back (M3). The brand is no part
	 *										of a theme and no part of a mix (M5): it is stored once, as
	 *										{ "color-1": { "own": true, "value": "#9a4b1d" }, "font-title": { "own": false }, ... }.
	 *										A theme brings suggestions for these seven values and never a logo (M4).
	 *
	 *										A font is a slug: one of the system stacks, or a folder public/fonts/<slug>/ with a font.json
	 *										and the .woff2 files it names. The compiler writes @font-face for the folders it uses and
	 *										loads nothing from another host.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Brand {

		// The values of the brand, what each may be, and what the page says where nothing else does (the defaults of Nino.css)
		public const array KEYS = [ 'color-1', 'color-2', 'tone', 'mode', 'font-title', 'font-text', 'font-button' ];
		public const array MODES = [ 'auto', 'light', 'dark' ];
		public const array DEFAULTS = [
			'color-1' => '#1c5db8', 'color-2' => '#17233a', 'tone' => 'neutral', 'mode' => 'auto',
			'font-title' => 'system-sans', 'font-text' => 'system-sans', 'font-button' => 'system-sans',
		];

		// The stacks of the system: what the browser has, in the order a page of that kind asks for it
		public const array SYSTEM = [
			'system-sans'		=> 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
			'system-serif'	=> 'Georgia, Cambria, "Times New Roman", Times, serif',
			'system-mono'		=> 'ui-monospace, "SF Mono", Menlo, Consolas, monospace',
		];

		// What follows the name of a font of public/fonts/ in a stack, by what font.json calls its fallback
		private const array FALLBACKS = [
			'sans-serif'	=> 'system-ui, sans-serif',
			'serif'				=> 'Georgia, serif',
			'monospace'		=> 'ui-monospace, monospace',
		];

		// A folder of public/fonts/ is a slug; the name of a family is written in quotes into a stylesheet, so it holds no quote
		private const string SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
		private const string FAMILY = '/^[A-Za-z0-9][A-Za-z0-9 _-]{0,59}$/D';
		private const string FILE = '/^[a-z0-9][a-z0-9._-]{0,80}\.woff2$/D';
		private const int FACES = 12;
		private const int MAX_FONTS = 50;

		/**
		 *	The fonts there are: the system stacks and every folder of public/fonts/ whose font.json is what it should be
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											slug => [ 'name' => ..., 'stack' => ..., 'system' => bool, 'faces' => [ [ weight, style, file ] ] ]
		 */
		public static function fonts( array &$appData ): array {

			$fonts = [];

			foreach( self::SYSTEM as $slug => $stack )
				$fonts[$slug] = [ 'name' => $slug, 'stack' => $stack, 'system' => true, 'faces' => [] ];

			$dir = \Nino\Filesystem::path( $appData, '/fonts' );
			$found = is_dir( $dir ) === true ? glob( $dir. '/*', GLOB_ONLYDIR ) : [];

			foreach( array_slice( $found === false ? [] : $found, 0, self::MAX_FONTS ) as $folder ) {

				$slug = basename( $folder );

				if( preg_match( self::SLUG, $slug ) === 1 && isset( $fonts[$slug] ) === false && is_link( $folder ) === false && is_array( $font = self::_font( $appData, $slug ) ) === true )
					$fonts[$slug] = $font;
			}

			return $fonts;
		}

		/**
		 *	One folder of public/fonts/ as a font: { "name": "Open Sans", "fallback": "sans-serif", "faces": [ { "weight": 400,
		 *	"style": "normal", "file": "open-sans-400.woff2" } ] }, every file there
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					The folder
		 *
		 *	@return 	array|null								null where the folder is no font
		 */
		private static function _font( array &$appData, string $slug ): ?array {

			$declared = \Nino\Filesystem::getFileContent( $appData, '/fonts/'. $slug. '/font.json', null );

			if( is_array( $declared ) === false || is_string( $declared['name'] ?? null ) === false || preg_match( self::FAMILY, $declared['name'] ) !== 1
				|| isset( self::FALLBACKS[$declared['fallback'] ?? ''] ) === false || is_array( $declared['faces'] ?? null ) === false
				|| array_is_list( $declared['faces'] ) === false || $declared['faces'] === [] || count( $declared['faces'] ) > self::FACES )
				return null;

			$faces = [];

			foreach( $declared['faces'] as $face ) {

				if( is_array( $face ) === false || is_int( $face['weight'] ?? null ) === false || $face['weight'] < 100 || $face['weight'] > 900
					|| in_array( $face['style'] ?? '', [ 'normal', 'italic' ], true ) === false || is_string( $face['file'] ?? null ) === false
					|| preg_match( self::FILE, $face['file'] ) !== 1 || is_file( \Nino\Filesystem::path( $appData, '/fonts/'. $slug. '/'. $face['file'] ) ) === false )
					return null;

				$faces[] = [ $face['weight'], $face['style'], $face['file'] ];
			}

			return [ 'name' => $declared['name'], 'stack' => '"'. $declared['name']. '", '. self::FALLBACKS[$declared['fallback']], 'system' => false, 'faces' => $faces ];
		}

		/**
		 *	Check what a person says of the brand and make it whole: every key there, an own value with its value, nothing
		 *	else. A value that is not what the key takes is refused, and so is a key the brand has not
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$input				{ key: { own: bool, value: ... } }
		 *	@param		array			$keep					The brand as it is stored: a font that is its own value there is taken as it stands, folder or no folder - a font that
		 *																is not there is the stored value of the project until it is changed, and a request that leaves it alone is no request to change it
		 *
		 *	@return 	array|string							The clean brand, or the key that is wrong ('' for an input that is no map)
		 */
		public static function clean( array &$appData, mixed $input, array $keep = [] ): array|string {

			if( is_array( $input ) === false )
				return '';

			foreach( array_keys( $input ) as $key )
				if( in_array( $key, self::KEYS, true ) === false )
					return (string) $key;

			$fonts = null;
			$clean = [];

			foreach( self::KEYS as $key ) {

				$entry = $input[$key] ?? [ 'own' => false ];

				if( is_array( $entry ) === false || is_bool( $entry['own'] ?? null ) === false || array_diff( array_keys( $entry ), [ 'own', 'value' ] ) !== [] )
					return $key;

				if( $entry['own'] === false ) {
					$clean[$key] = [ 'own' => false ];
					continue;
				}

				$value = $entry['value'] ?? null;

				if( str_starts_with( $key, 'font-' ) === true ) {
					$fonts ??= self::fonts( $appData );
					$ok = is_string( $value ) === true && ( isset( $fonts[$value] ) === true || ( ( $keep[$key]['own'] ?? false ) === true && ( $keep[$key]['value'] ?? null ) === $value && preg_match( self::SLUG, $value ) === 1 ) );
				}
				else if( $key === 'tone' )
					$ok = is_string( $value ) === true && isset( Palette::TONES[$value] ) === true;
				else if( $key === 'mode' )
					$ok = in_array( $value, self::MODES, true ) === true;
				else
					$ok = ( $value = Palette::color( $value ) ) !== null;

				if( $ok === false )
					return $key;

				$clean[$key] = [ 'own' => true, 'value' => $value ];
			}

			return $clean;
		}

		/**
		 *	The brand as it is stored, read for what it says and not held to what a request may say: every value is looked at for itself, and one that is
		 *	wrong - or none - is not the project's own, where the others stay. A font whose folder is gone stays the project's own value (the stylesheet
		 *	draws the stack of the system for it until the folder is back)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$input				The content of brand.json
		 *
		 *	@return 	array											As clean() answers it, always whole
		 */
		public static function read( array &$appData, mixed $input ): array {

			$input = is_array( $input ) === true ? $input : [];
			$brand = [];

			foreach( self::KEYS as $key ) {

				$entry = [ $key => $input[$key] ?? [ 'own' => false ] ];
				$one = self::clean( $appData, $entry, $entry );
				$brand[$key] = is_array( $one ) === true ? $one[$key] : [ 'own' => false ];
			}

			return $brand;
		}

		/**
		 *	What a theme suggests for the brand: a map of keys to values, each as the brand takes it. The font of a suggestion is
		 *	not looked for - a theme may name a font that is installed later
		 *
		 *	@param		mixed			$suggestion		The brand of a theme.json
		 *
		 *	@return 	array|string							The suggestion, or the key that is wrong
		 */
		public static function suggestion( mixed $suggestion ): array|string {

			if( is_array( $suggestion ) === false )
				return 'brand';

			$clean = [];

			foreach( $suggestion as $key => $value ) {

				if( in_array( $key, self::KEYS, true ) === false )
					return (string) $key;

				if( str_starts_with( $key, 'font-' ) === true )
					$ok = is_string( $value ) === true && preg_match( self::SLUG, $value ) === 1;
				else if( $key === 'tone' )
					$ok = is_string( $value ) === true && isset( Palette::TONES[$value] ) === true;
				else if( $key === 'mode' )
					$ok = in_array( $value, self::MODES, true ) === true;
				else
					$ok = ( $value = Palette::color( $value ) ) !== null;

				if( $ok === false )
					return (string) $key;

				$clean[$key] = $value;
			}

			return $clean;
		}

		/**
		 *	The keys the brand in force says something of: an own value of the project, or a suggestion of the theme the page comes from.
		 *	What it does not name is the reset's and is not written
		 *
		 *	@param		array			$brand				As clean() answers it
		 *	@param		array			$suggestion		As suggestion() answers it, [] for none
		 *
		 *	@return 	array											The named keys
		 */
		public static function named( array $brand, array $suggestion ): array {

			return array_values( array_filter( self::KEYS, static fn( string $key ): bool => ( $brand[$key]['own'] ?? false ) === true || isset( $suggestion[$key] ) === true ) );
		}

		/**
		 *	Whether there is a palette to work out: the brand names a colour, or a tone that is not the neutral one. Otherwise the
		 *	colours of the page are those of the reset, which this feature does not write
		 *
		 *	@param		array			$values				As effective() answers it
		 *	@param		array			$named				As named() answers it
		 *
		 *	@return 	bool
		 */
		public static function colours( array $values, array $named ): bool {

			return in_array( 'color-1', $named, true ) === true || in_array( 'color-2', $named, true ) === true || ( in_array( 'tone', $named, true ) === true && $values['tone'] !== 'neutral' );
		}

		/**
		 *	The colours the reset sets, read from Nino.css as a palette - what a page has where the brand names none - and the notice
		 *	that they are the reset's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											As Palette::make() answers, with 'color-1' and 'color-2'; [] where the file cannot be read
		 */
		public static function reset( array &$appData ): array {

			$css = \Nino\Filesystem::getFileContent( $appData, '/_nino/Nino.css', '' );
			$tokens = [];

			if( is_string( $css ) === true && preg_match_all( '/^\s*(--brand-[a-z0-9-]+):\s*([^;]+);/m', $css, $found, PREG_SET_ORDER ) > 0 )
				foreach( $found as $line )
					$tokens[$line[1]] = $tokens[$line[1]] ?? trim( $line[2] );

			$hex = static function( string $value ) use ( &$tokens ): string {

				$value = trim( $value );

				if( preg_match( '/^var\(\s*(--[a-z0-9-]+)\s*\)$/', $value, $var ) === 1 )
					$value = $tokens[$var[1]] ?? '';

				if( preg_match( '/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/', $value, $short ) === 1 )
					$value = '#'. $short[1]. $short[1]. $short[2]. $short[2]. $short[3]. $short[3];

				return strtolower( $value );
			};
			$pair = static function( string $token ) use ( &$tokens, $hex ): array {

				preg_match( '/^light-dark\(\s*(.+?)\s*,\s*(.+?)\s*\)$/', $tokens[$token] ?? '', $parts );

				return [ $hex( $parts[1] ?? '' ), $hex( $parts[2] ?? '' ) ];
			};
			$palette = [];
			$roles = [ 'base' => '--brand-ground-base', 'alt' => '--brand-ground-alt', 'tint' => '--brand-ground-tint', 'ink' => '--brand-ink-base', 'quiet' => '--brand-ink-quiet-base', 'line' => '--brand-line-base', 'accent' => '--brand-accent-base', 'on' => '--brand-on-accent-base' ];

			foreach( $roles as $role => $token ) {

				[ $light, $dark ] = $pair( $token );
				$palette['light'][$role] = $light;
				$palette['dark'][$role] = $dark;
			}

			$palette['on-color-1'] = $hex( $tokens['--brand-on-color-1'] ?? '' );
			$palette['on-color-2'] = $hex( $tokens['--brand-on-color-2'] ?? '' );
			$palette['color-1'] = $hex( $tokens['--brand-color-1'] ?? '' );
			$palette['color-2'] = $hex( $tokens['--brand-color-2'] ?? '' );

			foreach( array_merge( array_values( $palette['light'] ), array_values( $palette['dark'] ), [ $palette['on-color-1'], $palette['color-1'] ] ) as $color )
				if( Palette::color( $color ) === null )
					return [];

			return $palette;
		}

		/**
		 *	The value of every key: the project's own, else the suggestion of the theme the page comes from, else the default of
		 *	Nino.css
		 *
		 *	@param		array			$brand				As clean() answers it
		 *	@param		array			$suggestion		As suggestion() answers it, [] for none
		 *
		 *	@return 	array											key => value
		 */
		public static function effective( array $brand, array $suggestion ): array {

			$values = [];

			foreach( Brand::KEYS as $key )
				$values[$key] = ( $brand[$key]['own'] ?? false ) === true ? $brand[$key]['value'] : ( $suggestion[$key] ?? self::DEFAULTS[$key] );

			return $values;
		}
	}
}
