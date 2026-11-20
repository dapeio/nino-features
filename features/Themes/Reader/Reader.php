<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Themes\Reader	see _nino/Nino/Modules/Modules.php for the
 *												package-level docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The reader of the themes: the folders a theme is, what is in them, and whether each part of a theme
	 *										may stand for the group it names
	 *
	 *										A theme is a folder, and nothing in it is code:
	 *
	 *											themes/<key>/theme.json                 key, version, name, description, brand (suggestions)
	 *											themes/<key>/parts/page/part.json       the knobs of the page: name => five sets of --page tokens
	 *											themes/<key>/parts/<group>/part.json    contract, ladders, knobs, variants, standard
	 *											themes/<key>/parts/<group>/part.css     what the variants look like
	 *
	 *										Exactly these files are read - theme.json, part.json and part.css, each from a folder of the
	 *										theme itself and none through a link - and no file of the theme is ever included or run. A
	 *										theme that is not what it should be is refused as a whole, with the reason; a part that is
	 *										not rests with its reason, and its group takes the standard part, as the register does for a
	 *										theme that brings none. A part is checked with what the kernel has for it: validateGroup()
	 *										for the knobs, validStep() for each step, partProblems() for the css, the rule of mixing
	 *										and nothing more - what a part may do beyond it is the theme author's.
	 *
	 *										What is read is kept in private/data/cache/themes/read.php with a stamp - the size and the
	 *										modification time of each file, the register's groups and the versions - and read anew
	 *										where the stamp is not the one of the files.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Reader {

		// The hook that adds folders of themes to the one this feature ships: the list of absolute directories, each holding
		// <key>/theme.json
		public const string ROOTS = '/nino/themes/roots';

		// What a theme.json and a part.json may say, and the tokens of the page a knob of it may set (the fixed set of the
		// contract, S1)
		private const array THEME_KEYS = [ 'key', 'version', 'name', 'description', 'brand' ];
		private const array PART_KEYS = [ 'contract', 'ladders', 'knobs', 'variants', 'standard', 'values' ];
		private const int MAX_VALUES = 64;
		public const array PAGE_TOKENS = [
			'--page-size', '--page-width', '--page-leading', '--page-radius', '--page-line', '--page-motion', '--page-scroll-pad',
			'--page-weight-title', '--page-weight-text',
			'--page-space-1', '--page-space-2', '--page-space-3', '--page-space-4', '--page-space-5', '--page-space-6',
			'--page-text-1', '--page-text-2', '--page-text-3', '--page-text-4', '--page-text-5', '--page-text-6',
		];

		// How much a theme may be: folders, bytes of a file, knobs of the page, variants of a component and additional knobs
		// of a component (L5)
		private const int MAX_THEMES = 100;
		private const int MAX_PARTS = 40;
		private const int MAX_JSON = 32768;
		private const int MAX_CSS = 65536;
		private const int MAX_PAGE_KNOBS = 16;
		private const int MAX_VARIANTS = 8;
		private const int MAX_EXTRA = 2;

		// Why a theme or a part is not used, one sentence each, %s where a name or a number goes. The sentence is English, for the log and for the one who
		// writes a theme; the panel says it in its own words, by the code (why() makes the sentence, explain() takes it apart)
		public const array WHY = [
			'folder'						=> 'the folder is no folder of its own',
			'unreadable'				=> '%s is not a file of the theme, is longer than %s bytes or is no text',
			'no-map'						=> '%s is no JSON map',
			'theme-key'					=> '"%s" is no key of a theme - there are %s',
			'key-slug'					=> '"key" is a slug of at most %s characters, and the name of the folder',
			'version'						=> '"version" is major.minor.patch',
			'words'							=> '"%s" is a string or a locale => string map, with no empty text',
			'brand'							=> 'the brand of a theme may suggest %s and nothing else - "%s" is none of them or has no value that fits',
			'parts-many'				=> 'there are more than %s parts',
			'themes-many'				=> 'there are more than %s themes',
			'key-taken'					=> 'the key "%s" is the theme %s\'s',
			'part-key'					=> '"%s" is no key of a part - there are %s',
			'part-values'				=> '"values" is a map of knobs of the part to a step from -2 to 2 - "%s" is none of them or has no step',
			'theme-css'					=> 'theme.css breaks the rules of a theme at line %s',
			'theme-css-any'			=> 'theme.css breaks the rules of a theme',
			'part-contract'			=> '"contract" names the feature and the versions of it the part is for, as basis@^1.0',
			'page-contract'			=> 'the contract of the page is kernel@<constraint> that the kernel %s satisfies',
			'page-css'					=> 'the page has no stylesheet: its part is part.json alone',
			'page-keys'					=> 'the part of the page has a contract and knobs',
			'page-knobs'				=> '"knobs" is a map of at most %s knobs',
			'knob-name'					=> 'knob "%s": the name is a slug',
			'knob-shape'				=> 'knob "%s": a knob is a label, a ladder and, where it is for small screens, small',
			'knob-small'				=> 'knob "%s": "small" is true or false',
			'knob-ladder'				=> 'knob "%s": "ladder" is a list of %s sets of tokens',
			'knob-step'					=> 'knob "%s": a step of the ladder is a map of tokens to values',
			'knob-token'				=> 'knob "%s": "%s" is no token of the page, or its value is no step of a ladder (at most 120 characters, no quote, semicolon, brace, comment or url())',
			'group-unknown'			=> 'the group "%s" is brought by no feature that is on',
			'group-feature'			=> 'the contract names "%s", the group is the feature "%s"\'s',
			'group-version'			=> 'the part is for %s %s, which this project does not have',
			'map'								=> '"%s" is a map',
			'ladder-unknown'		=> 'ladder "%s" is no knob of the group',
			'extra-name'				=> 'knob "%s": an additional knob is <component>.x-<name>',
			'extra-shape'				=> 'knob "%s": a knob is a label and a ladder',
			'extra-many'				=> 'knob "%s": a component has %s additional knobs at most',
			'group-knobs'				=> 'the knobs of the group are refused: %s',
			'variants-component'	=> 'variants of "%s": it is no component of the group',
			'variants-many'			=> 'variants of "%s": a map of at most %s variants to their labels',
			'variants-shape'		=> 'variants of "%s": a variant is a slug with a label, a string or a locale => string map',
			'standard'					=> 'standard of "%s": the component of the group and one of its variants',
			'css'								=> 'the css of the part breaks the rules of a part: %s',
			'copy-refused'			=> 'the copy of %s is refused: %s',
			'not-installed'			=> 'the theme %s is not installed and the project holds no copy of it',
		];

		private const string VERSION = '/^\d{1,4}\.\d{1,4}\.\d{1,4}(?:-[A-Za-z0-9.]{1,20})?$/D';
		private const string CONTRACT = '/^([a-z][a-z0-9-]{0,39})@([0-9A-Za-z.^~<>=!*| ,-]{1,40})$/D';

		/**
		 *	The sentence of a reason, from its code
		 *
		 *	@param		string		$code					A key of WHY
		 *	@param		string|int	...$params	What goes into the sentence, in the order of its %s
		 *
		 *	@return 	string
		 */
		public static function why( string $code, string|int ...$params ): string {
			return vsprintf( self::WHY[$code], $params );
		}

		/**
		 *	The code and the params of a sentence why() made - what the panel says in its own words
		 *
		 *	@param		string		$reason				A reason of a rejected theme or a part that rests
		 *
		 *	@return 	array|null								[ 'code' => ..., 'params' => [ ... ] ]; null for no reason
		 */
		public static function explain( string $reason ): ?array {

			if( $reason === '' )
				return null;

			foreach( self::WHY as $code => $sentence )
				if( preg_match( '/^'. str_replace( '%s', '(.*?)', preg_quote( $sentence, '/' ) ). '$/Ds', $reason, $found ) === 1 )
					return [ 'code' => $code, 'params' => array_slice( $found, 1 ) ];

			throw new \LogicException( 'a reason that no sentence of WHY makes: '. $reason );
		}

		/**
		 *	The folders themes are read from, in the order the first theme of a key wins: the one of this feature, the folder of the
		 *	installed packages of themes (\Nino\Packages::dir()), and what the callbacks of ROOTS add. A theme whose key is the one of an
		 *	earlier folder is not read and is said once, in 'rejected', as read() answers it: a package never puts out the theme that
		 *	comes with the feature, nor does a folder of the hook put out a package
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											Absolute directories that exist, each once
		 */
		public static function roots( array &$appData ): array {

			$asked = [ \Nino\Features::dir(). '/Themes/themes', \Nino\Packages::dir( $appData, \Nino\Packages::THEME, '' ) ];
			$listed = \Nino\Callbacks::doCallbacks( $appData, self::ROOTS, $asked );
			$roots = [];

			foreach( is_array( $listed ) === true ? $listed : [] as $root ) {

				$real = is_string( $root ) === true && str_contains( $root, "\0" ) === false ? realpath( $root ) : false;

				if( $real !== false && is_dir( $real ) === true && in_array( $real, $roots, true ) === false )
					$roots[] = $real;
			}

			return $roots;
		}

		/**
		 *	The themes there are, each with its parts checked against the register as it stands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											'themes' => key => [ 'key', 'version', 'name', 'description', 'brand', 'dir', 'parts' => group => [ 'ok', 'problem', 'part' ] ],
		 *																'rejected' => directory => the reason
		 */
		public static function read( array &$appData ): array {

			if( is_array( $appData['./nino/themes/read'] ?? null ) === true )
				return $appData['./nino/themes/read'];

			$roots = self::roots( $appData );
			$stamp = self::_stamp( $appData, $roots );
			$kept = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Themes::CACHE. '/read.php', [] );

			if( is_array( $kept ) === true && ( $kept['stamp'] ?? '' ) === $stamp && is_array( $kept['read'] ?? null ) === true )
				return $appData['./nino/themes/read'] = $kept['read'];

			$read = [ 'themes' => [], 'rejected' => [] ];
			$count = 0;

			foreach( $roots as $root ) {

				$folders = glob( $root. '/*', GLOB_ONLYDIR );

				foreach( $folders === false ? [] : $folders as $folder ) {

					if( ++$count > self::MAX_THEMES ) {
						$read['rejected'][$folder] = self::why( 'themes-many', self::MAX_THEMES );
						continue;
					}

					$theme = self::theme( $folder, basename( $folder ) );

					if( is_string( $theme ) === true )
						$read['rejected'][$folder] = $theme;
					else if( isset( $read['themes'][$theme['key']] ) === true )
						$read['rejected'][$folder] = self::why( 'key-taken', $theme['key'], basename( $read['themes'][$theme['key']]['dir'] ) );
					else
						$read['themes'][$theme['key']] = $theme;
				}
			}

			foreach( $read['themes'] as $key => $theme ) {

				foreach( $theme['raw'] as $group => $raw )
					$read['themes'][$key]['parts'][$group] = self::check( $appData, $group, $raw );

				unset( $read['themes'][$key]['raw'] );
			}

			ksort( $read['themes'] );

			$appData['./nino/themes/read'] = $read;
			\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Themes::CACHE. '/read.php', [ 'stamp' => $stamp, 'read' => $read ] );

			return $read;
		}

		/**
		 *	One folder as a theme, its parts read and not yet checked. Only theme.json, part.json and part.css are opened, and
		 *	only where they are files of the folder itself
		 *
		 *	@param		string		$folder				The directory
		 *	@param		string		$name					What its key has to be; '' for a copy, whose folder is <key>@<version>
		 *
		 *	@return 	array|string							The theme, with 'raw' => group => [ 'json', 'css', 'problem' ], or why it is none
		 */
		public static function theme( string $folder, string $name ): array|string {

			$real = realpath( $folder );

			if( $real === false || is_dir( $real ) === false || is_link( $folder ) === true )
				return self::why( 'folder' );

			$json = self::_json( $real, $real. '/theme.json', 'theme.json', self::MAX_JSON );

			if( is_string( $json ) === true )
				return $json;

			foreach( array_keys( $json ) as $key )
				if( in_array( $key, self::THEME_KEYS, true ) === false )
					return self::why( 'theme-key', self::_shown( (string) $key ), implode( ', ', self::THEME_KEYS ) );

			$key = $json['key'] ?? null;

			if( is_string( $key ) === false || preg_match( \Nino\Components::NAME, $key ) !== 1 || strlen( $key ) > \Nino\Components::NAME_LENGTH || ( $name !== '' && $key !== $name ) )
				return self::why( 'key-slug', \Nino\Components::NAME_LENGTH );

			if( is_string( $json['version'] ?? null ) === false || preg_match( self::VERSION, $json['version'] ) !== 1 )
				return self::why( 'version' );

			foreach( [ 'name', 'description' ] as $words )
				if( \Nino\Features::localizedValid( $json[$words] ?? null ) === false )
					return self::why( 'words', $words );

			$brand = Brand::suggestion( $json['brand'] ?? [] );

			if( is_string( $brand ) === true )
				return self::why( 'brand', implode( ', ', Brand::KEYS ), self::_shown( $brand ) );

			// The css of the theme as a whole: read as the kernel reads it, and left out - the theme is used without it - where it breaks the rule; the reason is said
			$css = '';
			$cssProblem = '';

			if( file_exists( $real. '/theme.css' ) === true ) {

				$text = self::_file( $real, $real. '/theme.css', self::MAX_CSS );

				if( $text === null )
					$cssProblem = self::why( 'unreadable', 'theme.css', self::MAX_CSS );
				else if( ( $problems = \Nino\Components::themeProblems( $text ) ) !== [] )
					$cssProblem = preg_match( '/\(line (\d+)\)/', implode( '; ', $problems ), $line ) === 1 ? self::why( 'theme-css', (int) $line[1] ) : self::why( 'theme-css-any' );
				else
					$css = trim( str_replace( [ "\r\n", "\r" ], "\n", $text ) );
			}

			$raw = [];
			$parts = $real. '/parts';

			if( is_dir( $parts ) === true && is_link( $parts ) === false ) {

				$folders = glob( $parts. '/*', GLOB_ONLYDIR );

				foreach( $folders === false ? [] : $folders as $dir ) {

					$group = basename( $dir );

					if( preg_match( \Nino\Components::NAME, $group ) !== 1 || strlen( $group ) > \Nino\Components::NAME_LENGTH || is_link( $dir ) === true )
						continue;

					if( count( $raw ) >= self::MAX_PARTS )
						return self::why( 'parts-many', self::MAX_PARTS );

					$raw[$group] = self::part( $real, $dir );
				}
			}

			return [ 'key' => $key, 'version' => $json['version'], 'name' => $json['name'], 'description' => $json['description'], 'brand' => $brand, 'dir' => $real, 'css' => $css, 'cssProblem' => $cssProblem, 'raw' => $raw, 'parts' => [] ];
		}

		/**
		 *	The two files of a part, read
		 *
		 *	@param		string		$base					The real path of the theme: no file of a part may lie outside it
		 *	@param		string		$dir					The folder of the part
		 *
		 *	@return 	array											'json' => what part.json holds (null where it cannot be read), 'css' => part.css or '', 'problem' => '' or why not
		 */
		public static function part( string $base, string $dir ): array {

			$json = self::_json( $base, $dir. '/part.json', 'part.json', self::MAX_JSON );

			if( is_string( $json ) === true )
				return [ 'json' => null, 'css' => '', 'problem' => $json ];

			$css = '';

			if( file_exists( $dir. '/part.css' ) === true ) {

				$css = self::_file( $base, $dir. '/part.css', self::MAX_CSS );

				if( $css === null )
					return [ 'json' => null, 'css' => '', 'problem' => self::why( 'unreadable', 'part.css', self::MAX_CSS ) ];
			}

			return [ 'json' => $json, 'css' => $css, 'problem' => '' ];
		}

		/**
		 *	Whether a part may stand for its group as the register stands, and what it is if it may
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				A group of the register, or 'page'
		 *	@param		array			$raw					As part() answers it
		 *
		 *	@return 	array											'ok' => bool, 'problem' => the reason, 'part' => [ 'knobs', 'variants', 'standard', 'css' ] where it is ok
		 */
		public static function check( array &$appData, string $group, array $raw ): array {

			$problem = $raw['problem'] !== '' ? $raw['problem'] : self::_problem( $appData, $group, $raw );

			if( is_string( $problem ) === true )
				return [ 'ok' => false, 'problem' => $problem, 'part' => null ];

			return [ 'ok' => true, 'problem' => '', 'part' => $problem ];
		}

		/**
		 *	The part of a group of a theme the way a mix says it, 'craft@1.0.0' or the bare key: the copy the project holds of that
		 *	version, else the installed theme of that version (the one there is, for a bare key). The website never hangs on the
		 *	installed package - a copy there is what is used
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					'<key>@<version>' or '<key>'
		 *	@param		string		$group
		 *
		 *	@return 	array											'theme' => key, 'version' => ..., 'origin' => 'copy'|'installed'|'', 'dir' => the theme's folder, 'brand' => its suggestions,
		 *																'part' => the checked part or null, 'problem' => why it cannot be used ('' for a theme that brings no part for the group)
		 */
		public static function resolve( array &$appData, string $from, string $group ): array {

			[ $key, $version ] = explode( '@', $from, 2 ) + [ 1 => '' ];
			$none = [ 'theme' => $key, 'version' => $version, 'origin' => '', 'dir' => '', 'brand' => [], 'part' => null, 'problem' => '' ];

			$copied = null;

			if( $version !== '' ) {

				$copy = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/copies/'. $key. '@'. $version );

				if( is_dir( $copy ) === true ) {

					$copied = self::theme( $copy, '' );

					if( is_string( $copied ) === true )
						return [ 'problem' => self::why( 'copy-refused', $from, $copied ) ] + $none;

					$found = [ 'version' => $copied['version'], 'origin' => 'copy', 'dir' => $copied['dir'], 'brand' => $copied['brand'] ] + $none;

					// A copy holds the parts that were in use when it was made - the part of a group it has not is the installed theme's
					if( isset( $copied['raw'][$group] ) === true )
						return array_merge( $found, self::_answer( self::check( $appData, $group, $copied['raw'][$group] ) ) );
				}
			}

			$theme = self::read( $appData )['themes'][$key] ?? null;

			if( $theme === null || ( $version !== '' && $theme['version'] !== $version ) )
				return $copied !== null ? $found : [ 'problem' => self::why( 'not-installed', $from ) ] + $none;

			$found = [ 'version' => $theme['version'], 'origin' => 'installed', 'dir' => $theme['dir'], 'brand' => $theme['brand'] ] + $none;

			return isset( $theme['parts'][$group] ) === true ? array_merge( $found, self::_answer( $theme['parts'][$group] ) ) : $found;
		}

		/**
		 *	The part and the reason of a check()
		 */
		private static function _answer( array $checked ): array {
			return [ 'part' => $checked['part'], 'problem' => $checked['problem'] ];
		}

		/**
		 *	What is wrong with a part of a group, or the part made whole
		 *
		 *	@return 	array|string							The part, or the reason
		 */
		private static function _problem( array &$appData, string $group, array $raw ): array|string {

			$json = $raw['json'];

			foreach( array_keys( $json ) as $key )
				if( in_array( $key, self::PART_KEYS, true ) === false )
					return self::why( 'part-key', self::_shown( (string) $key ), implode( ', ', self::PART_KEYS ) );

			$contract = is_string( $json['contract'] ?? null ) === true && preg_match( self::CONTRACT, $json['contract'], $found ) === 1 ? $found : null;

			if( $contract === null )
				return self::why( 'part-contract' );

			return $group === 'page' ? self::_page( $json, $contract, $raw['css'] ) : self::_group( $appData, $group, $json, $contract, $raw['css'] );
		}

		/**
		 *	The part of the page: knobs of tokens of the page, no stylesheet
		 *
		 *	@return 	array|string
		 */
		private static function _page( array $json, array $contract, string $css ): array|string {

			if( $contract[1] !== 'kernel' || \Nino\Features::satisfies( $contract[2] ) === false )
				return self::why( 'page-contract', \Nino\VERSION );

			if( trim( $css ) !== '' )
				return self::why( 'page-css' );

			if( array_diff( array_keys( $json ), [ 'contract', 'knobs', 'values' ] ) !== [] )
				return self::why( 'page-keys' );

			$knobs = $json['knobs'] ?? [];

			if( is_array( $knobs ) === false || count( $knobs ) > self::MAX_PAGE_KNOBS )
				return self::why( 'page-knobs', self::MAX_PAGE_KNOBS );

			$clean = [];

			foreach( $knobs as $name => $knob ) {

				$where = self::_shown( (string) $name );

				if( is_string( $name ) === false || preg_match( \Nino\Components::NAME, $name ) !== 1 || strlen( $name ) > \Nino\Components::NAME_LENGTH )
					return self::why( 'knob-name', $where );

				if( is_array( $knob ) === false || array_diff( array_keys( $knob ), [ 'label', 'small', 'ladder' ] ) !== [] || \Nino\Features::localizedValid( $knob['label'] ?? null ) === false )
					return self::why( 'knob-shape', $where );

				if( isset( $knob['small'] ) === true && is_bool( $knob['small'] ) === false )
					return self::why( 'knob-small', $where );

				$ladder = $knob['ladder'] ?? null;

				if( is_array( $ladder ) === false || array_is_list( $ladder ) === false || count( $ladder ) !== \Nino\Components::STEPS )
					return self::why( 'knob-ladder', $where, \Nino\Components::STEPS );

				foreach( $ladder as $set ) {

					if( is_array( $set ) === false || ( $set !== [] && array_is_list( $set ) === true ) )
						return self::why( 'knob-step', $where );

					foreach( $set as $token => $value )
						if( in_array( $token, self::PAGE_TOKENS, true ) === false || \Nino\Components::validStep( $value ) === false )
							return self::why( 'knob-token', $where, self::_shown( (string) $token ) );
				}

				$clean[$name] = [ 'label' => $knob['label'], 'small' => ( $knob['small'] ?? false ) === true, 'ladder' => $ladder ];
			}

			$values = self::_values( $json, $clean );

			if( is_string( $values ) === true )
				return $values;

			return [ 'knobs' => $clean, 'variants' => [], 'standard' => [], 'css' => '', 'values' => $values ];
		}

		/**
		 *	The part of a group of the register
		 *
		 *	@return 	array|string
		 */
		private static function _group( array &$appData, string $group, array $json, array $contract, string $css ): array|string {

			$declared = \Nino\Components::groups( $appData )[$group] ?? null;

			if( $declared === null )
				return self::why( 'group-unknown', $group );

			$feature = \Nino\Features::get( $appData, $declared['feature'] );

			if( $contract[1] !== $declared['feature'] )
				return self::why( 'group-feature', $contract[1], $declared['feature'] );

			if( $feature === null || \Nino\Features::satisfies( $contract[2], (string) $feature['version'] ) === false )
				return self::why( 'group-version', $contract[1], $contract[2] );

			$components = $declared['components'];
			$all = $declared['knobs'];

			foreach( [ 'ladders', 'knobs', 'variants', 'standard' ] as $key )
				if( is_array( $json[$key] ?? [] ) === false )
					return self::why( 'map', $key );

			foreach( (array) ( $json['ladders'] ?? [] ) as $key => $ladder ) {

				if( isset( $declared['knobs'][$key] ) === false )
					return self::why( 'ladder-unknown', self::_shown( (string) $key ) );

				$all[$key]['ladder'] = $ladder;
			}

			$extra = [];

			foreach( (array) ( $json['knobs'] ?? [] ) as $key => $knob ) {

				if( is_string( $key ) === false || preg_match( '/^([a-z][a-z0-9-]*)\.x-([a-z0-9]+(?:-[a-z0-9]+)*)$/D', $key, $found ) !== 1 )
					return self::why( 'extra-name', self::_shown( (string) $key ) );

				if( is_array( $knob ) === false || array_diff( array_keys( $knob ), [ 'label', 'ladder' ] ) !== [] )
					return self::why( 'extra-shape', $key );

				$extra[$found[1]] = ( $extra[$found[1]] ?? 0 ) + 1;

				if( $extra[$found[1]] > self::MAX_EXTRA )
					return self::why( 'extra-many', $key, self::MAX_EXTRA );

				$all[$key] = [ 'token' => '--'. $found[1]. '-x-'. $found[2], 'label' => $knob['label'] ?? '', 'ladder' => $knob['ladder'] ?? null ];
			}

			$checked = \Nino\Components::validateGroup( $group, [ 'label' => $declared['label'], 'knobs' => $all ], $components );

			if( is_string( $checked ) === true )
				return self::why( 'group-knobs', $checked );

			$schemas = \Nino\Components::components( $appData );
			$variants = [];

			foreach( (array) ( $json['variants'] ?? [] ) as $component => $words ) {

				if( is_string( $component ) === false || in_array( $component, $components, true ) === false )
					return self::why( 'variants-component', self::_shown( (string) $component ) );

				if( is_array( $words ) === false || count( $words ) > self::MAX_VARIANTS )
					return self::why( 'variants-many', $component, self::MAX_VARIANTS );

				foreach( $words as $name => $label )
					if( is_string( $name ) === false || preg_match( \Nino\Components::NAME, $name ) !== 1 || strlen( $name ) > \Nino\Components::NAME_LENGTH || \Nino\Features::localizedValid( $label ) === false )
						return self::why( 'variants-shape', $component );

				$variants[$component] = $words;
			}

			$standard = [];

			foreach( (array) ( $json['standard'] ?? [] ) as $component => $name ) {

				$known = array_merge( (array) ( $schemas[$component]['variants'] ?? [] ), array_keys( $variants[$component] ?? [] ) );

				if( is_string( $component ) === false || is_string( $name ) === false || in_array( $name, $known, true ) === false )
					return self::why( 'standard', self::_shown( (string) $component ) );

				$standard[$component] = $name;
			}

			$problems = \Nino\Components::partProblems( $css, $components );

			if( $problems !== [] )
				return self::why( 'css', implode( '; ', $problems ) );

			$knobs = [];

			foreach( $checked['knobs'] as $key => $knob )
				$knobs[$key] = $knob + [ 'extra' => isset( $declared['knobs'][$key] ) === false ];

			$values = self::_values( $json, $knobs );

			if( is_string( $values ) === true )
				return $values;

			return [ 'knobs' => $knobs, 'variants' => $variants, 'standard' => $standard, 'css' => $css, 'values' => $values ];
		}

		/**
		 *	The steps a part is delivered at: a map of the knobs the part has to a whole number from -2 to 2, the one the kernel holds a package to as well. The middle step is
		 *	left out - a knob that is not in the map is at it - and what is kept is in the order of the knobs
		 *
		 *	@param		array			$json					The part.json
		 *	@param		array			$knobs				The knobs the part has, by their keys
		 *
		 *	@return 	array|string							Knob => step, or the reason
		 */
		private static function _values( array $json, array $knobs ): array|string {

			if( array_key_exists( 'values', $json ) === false )
				return [];

			$values = $json['values'];

			if( is_array( $values ) === false || ( $values !== [] && array_is_list( $values ) === true ) || count( $values ) > self::MAX_VALUES )
				return self::why( 'part-values', '' );

			$clean = [];

			foreach( $values as $knob => $step )
				if( is_string( $knob ) === false || isset( $knobs[$knob] ) === false || is_int( $step ) === false || $step < -2 || $step > 2 )
					return self::why( 'part-values', self::_shown( (string) $knob ) );
				else if( $step !== 0 )
					$clean[$knob] = $step;

			ksort( $clean );

			return $clean;
		}

		/**
		 *	The stamp of what a read depends on: the files of the themes, the groups of the register and the versions
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$roots				As roots() answers
		 *
		 *	@return 	string
		 */
		private static function _stamp( array &$appData, array $roots ): string {

			$files = [];

			foreach( $roots as $root ) {

				foreach( (array) glob( $root. '/*/theme.json' ) as $file )
					$files[] = $file;

				// The css of a theme is stamped by what it says and not by its size and second: a file that is changed to as many bytes in the second it was read is another css
				foreach( (array) glob( $root. '/*/theme.css' ) as $file )
					$files[] = $file. '|'. ( is_file( (string) $file ) === true && is_link( (string) $file ) === false ? (string) @sha1_file( (string) $file ) : '' );

				// One pattern for each name: a pattern with braces needs a flag of glob() that not every system has (musl has none)
				foreach( [ 'part.json', 'part.css' ] as $name )
					foreach( (array) glob( $root. '/*/parts/*/'. $name ) as $file )
						$files[] = $file;
			}

			$state = [];

			foreach( $files as $file ) {

				$stat = @stat( (string) $file );
				$state[] = $file. '|'. ( $stat === false ? '' : $stat['size']. '|'. $stat['mtime'] );
			}

			return sha1( implode( "\n", $state ). "\n". json_encode( \Nino\Components::groups( $appData ) ). "\n". \Nino\Components::version( $appData ). "\n". self::_code(). "\n". \Nino\VERSION. "\n". (string) ( \Nino\Features::get( $appData, 'themes' )['version'] ?? '' ) );
		}

		/**
		 *	The time and the size of the files whose code decides what a read comes to - this class and the register of the kernel - so that
		 *	an update of either that leaves the version as it was does not leave a read that was made by the code before it. The register's own
		 *	cache is stamped by the files of the folders and by the version of the kernel, and has the limit this removes
		 *
		 *	@return 	string
		 */
		private static function _code(): string {

			$stamp = '';

			foreach( [ __FILE__, (string) ( new \ReflectionClass( \Nino\Components::class ) )->getFileName() ] as $file )
				$stamp .= (int) @filemtime( $file ). ':'. (int) @filesize( $file ). ';';

			return $stamp;
		}

		/**
		 *	A JSON file of a theme, decoded
		 *
		 *	@param		string		$base					The real path of the theme
		 *	@param		string		$file
		 *	@param		string		$name					How the file is called in a reason
		 *	@param		int				$limit				The most bytes
		 *
		 *	@return 	array|string							The map, or what is wrong with the file
		 */
		private static function _json( string $base, string $file, string $name, int $limit ): array|string {

			$text = self::_file( $base, $file, $limit );

			if( $text === null )
				return self::why( 'unreadable', $name, $limit );

			$json = json_decode( $text, true, 16 );

			return is_array( $json ) === true && ( $json === [] || array_is_list( $json ) === false ) ? $json : self::why( 'no-map', $name );
		}

		/**
		 *	The text of a file that lies in a folder of the theme itself - a link out of it, a file too long, a null byte and a
		 *	text that is no UTF-8 are none
		 *
		 *	@return 	string|null
		 */
		private static function _file( string $base, string $file, int $limit ): ?string {

			$real = realpath( $file );

			if( $real === false || is_file( $real ) === false || Store::readable( $real ) === false || is_link( $file ) === true || str_starts_with( $real, $base. '/' ) === false )
				return null;

			$size = filesize( $real );

			if( $size === false || $size > $limit )
				return null;

			// A file that cannot be read is a file that is not there: the kernel would make a 500 of the warning
			$text = Store::quietRead( $real );

			return $text !== false && str_contains( $text, "\0" ) === false && mb_check_encoding( $text, 'UTF-8' ) === true ? $text : null;
		}

		/**
		 *	A name as it may stand in a reason: short, and with no control character
		 */
		private static function _shown( string $name ): string {
			return mb_substr( (string) preg_replace( '/[\x00-\x1F\x7F]/', '?', $name ), 0, 60 );
		}
	}
}
