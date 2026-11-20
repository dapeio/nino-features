<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Themes\Mix	see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The mix: which theme each group takes its part from, which variants stand where a page names none and
	 *										which step each knob is on - and the free css of the developer
	 *
	 *										{ "name": "Lindner", "css": "...", "parts": {
	 *											"page":    { "from": "nino@2.0.0", "values": { "radius": 1 } },
	 *											"section": { "from": "craft@1.0.0", "standard": { "section-wrap": "soft" }, "values": { "section-wrap.space": 2 } },
	 *											"button":  { "from": "standard" } } }
	 *
	 *										A step is -2 to +2 and 0 is the middle one, which is left out; the brand is not in it (M5). Where a
	 *										group changes its part, the steps of knobs of the same name stay and every other knob begins at its
	 *										middle step: clean() keeps what the part has a knob for and drops the rest.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Mix {

		// What a part is taken from: the standard part of the group, an installed theme or a version of one the project holds a copy of
		private const string FROM = '/^(?:standard|([a-z0-9]+(?:-[a-z0-9]+)*)(?:@(\d{1,4}\.\d{1,4}\.\d{1,4}(?:-[A-Za-z0-9.]{1,20})?))?)$/D';
		private const string KNOB = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*(?:\.[a-z][a-z0-9]*(?:-[a-z0-9]+)*)?$/D';

		// How much a mix may say: the longest name of an own theme, the longest css, the most groups
		public const int NAME_LENGTH = 60;
		public const int CSS_LENGTH = 65536;
		private const int GROUPS = 40;

		// How many knobs and components of a group a mix may speak of, and how many bytes it may come to: well below what the store reads (Store::MAX_FILE)
		private const int ENTRIES = 40;
		public const int BYTES = 262144;

		/**
		 *	The mix a theme is: its part for the page and for every group of the register that it has one for - the standard part
		 *	for the rest (F5) - each at the steps the theme is delivered at, which are the middle step where its part says none: a
		 *	step moves no ladder, the middle of the scale stays the middle. The css of the theme as a whole, where it has one and
		 *	the rule holds it, is the css of the mix - after the parts, where an own theme made of the mix keeps it to change
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of an installed theme
		 *
		 *	@return 	array|null								null where the theme is not installed
		 */
		public static function of( array &$appData, string $key ): ?array {

			$theme = Reader::read( $appData )['themes'][$key] ?? null;

			if( $theme === null )
				return null;

			$parts = [];

			foreach( array_merge( [ 'page' ], array_keys( \Nino\Components::groups( $appData ) ) ) as $group ) {

				$parts[$group] = [ 'from' => isset( $theme['parts'][$group] ) === true ? $key. '@'. $theme['version'] : 'standard' ];
				$delivered = (array) ( $theme['parts'][$group]['part']['values'] ?? [] );

				if( $delivered !== [] )
					$parts[$group]['values'] = $delivered;
			}

			ksort( $parts );

			return [ 'parts' => $parts ] + ( ( $theme['css'] ?? '' ) !== '' ? [ 'css' => (string) $theme['css'] ] : [] );
		}

		/**
		 *	Check what a person says of a mix and make it whole: a name where it is an own theme's, the css, and for each group a
		 *	part from a theme that is installed or has a copy, variants that part has, steps of its knobs
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$input
		 *	@param		bool			$named				Whether a name is needed, as for an own theme
		 *
		 *	@return 	array|string							The clean mix, or what is wrong, as 'field: reason'
		 */
		public static function clean( array &$appData, mixed $input, bool $named ): array|string {

			if( is_array( $input ) === false || array_diff( array_keys( $input ), [ 'name', 'css', 'parts' ] ) !== [] )
				return 'mix: a mix is a name, a css and parts';

			$clean = [];

			if( $named === true ) {

				$name = $input['name'] ?? null;

				if( is_string( $name ) === false || trim( $name ) === '' || mb_strlen( $name ) > self::NAME_LENGTH || preg_match( '/[\x00-\x1F\x7F]/', $name ) === 1 || mb_check_encoding( $name, 'UTF-8' ) === false )
					return 'name: a name is text of 1 to '. self::NAME_LENGTH. ' characters, in one line';

				$clean['name'] = trim( $name );
			}

			$css = self::css( $input['css'] ?? '' );

			if( $css === null )
				return 'css: the css is text of at most '. self::CSS_LENGTH. ' bytes, with no null byte';

			$clean['css'] = $css;
			$parts = $input['parts'] ?? [];

			if( is_array( $parts ) === false || count( $parts ) > self::GROUPS )
				return 'parts: a map of at most '. self::GROUPS. ' groups';

			$groups = \Nino\Components::groups( $appData );
			$clean['parts'] = [];

			foreach( $parts as $group => $entry ) {

				if( is_string( $group ) === false || ( $group !== 'page' && preg_match( \Nino\Components::NAME, $group ) !== 1 ) || strlen( $group ) > \Nino\Components::NAME_LENGTH )
					return 'parts: a group is a slug';

				if( is_array( $entry ) === false || array_diff( array_keys( $entry ), [ 'from', 'standard', 'values' ] ) !== [] )
					return $group. ': a part is from, standard and values';

				// What neither the register nor the page has is not kept: a mix does not grow by what nothing reads
				if( $group !== 'page' && isset( $groups[$group] ) === false )
					continue;

				$from = $entry['from'] ?? 'standard';

				if( is_string( $from ) === false || preg_match( self::FROM, $from, $found ) !== 1 )
					return $group. '.from: standard, or a theme as key@version';

				// A bare key is the version that is installed, which is what a copy is made of
				if( $from !== 'standard' && ( $found[2] ?? '' ) === '' ) {

					$theme = Reader::read( $appData )['themes'][$found[1]] ?? null;

					if( $theme === null )
						return $group. '.from: the theme "'. $found[1]. '" is not installed';

					$from = $found[1]. '@'. $theme['version'];
				}

				$resolved = $from === 'standard' ? null : Reader::resolve( $appData, $from, $group );

				if( $resolved !== null && $resolved['origin'] === '' )
					return $group. '.from: '. $resolved['problem'];

				$mix = [ 'from' => $from ];
				$known = self::_knobs( $groups, $group, $resolved );
				$variants = self::_variants( $appData, $groups, $group, $resolved );

				$values = $entry['values'] ?? [];

				if( is_array( $values ) === false || count( $values ) > self::ENTRIES )
					return $group. '.values: a map of at most '. self::ENTRIES. ' knobs to steps';

				foreach( $values as $knob => $step ) {

					if( is_string( $knob ) === false || preg_match( self::KNOB, $knob ) !== 1 || strlen( $knob ) > 2 * \Nino\Components::NAME_LENGTH + 1 )
						return $group. '.values: a knob is <component>.<name>, or a name for the page';

					if( is_int( $step ) === false || $step < -2 || $step > 2 )
						return $group. '.values.'. $knob. ': a step is a whole number from -2 to 2';

					if( $step !== 0 && isset( $known[$knob] ) === true )
						$mix['values'][$knob] = $step;
				}

				$standard = $entry['standard'] ?? [];

				if( is_array( $standard ) === false || count( $standard ) > self::ENTRIES )
					return $group. '.standard: a map of at most '. self::ENTRIES. ' components to variants';

				foreach( $standard as $component => $variant ) {

					if( is_string( $component ) === false || preg_match( \Nino\Components::NAME, $component ) !== 1 || is_string( $variant ) === false || preg_match( \Nino\Components::NAME, $variant ) !== 1 )
						return $group. '.standard: a component and one of its variants, as slugs';

					if( in_array( $variant, $variants[$component] ?? [], true ) === true )
						$mix['standard'][$component] = $variant;
				}

				foreach( [ 'standard', 'values' ] as $key ) {

					if( isset( $mix[$key] ) === false )
						continue;

					ksort( $mix[$key] );
					$ordered = $mix[$key];
					unset( $mix[$key] );
					$mix[$key] = $ordered;
				}

				$clean['parts'][$group] = $mix;
			}

			ksort( $clean['parts'] );

			// Nothing is written that the store would not read back
			if( strlen( (string) json_encode( $clean ) ) > self::BYTES )
				return 'mix: a mix is at most '. self::BYTES. ' bytes';

			return $clean;
		}

		/**
		 *	The group a refusal of clean() is about: the first word of 'group.field: reason' and of 'group: a part is ...', where that word names a part
		 *	of the mix and not the mix, its name, its css or its list of parts
		 *
		 *	@param		string		$reason				What clean() answered
		 *
		 *	@return 	string										The key of the group, 'page' for the page; '' where the refusal is about no part
		 */
		public static function where( string $reason ): string {

			if( preg_match( '/^([a-z][a-z0-9-]*)(\.[a-z0-9.\-]+)?: (.*)$/Ds', $reason, $found ) !== 1 )
				return '';

			return $found[2] !== '' || str_starts_with( $found[3], 'a part is from' ) === true ? $found[1] : '';
		}

		/**
		 *	The css of the developer as it is stored: text, one line ending, a null byte is none, a length is the limit
		 *
		 *	@param		mixed			$css
		 *
		 *	@return 	string|null								null where it is none
		 */
		public static function css( mixed $css ): ?string {

			if( is_string( $css ) === false || strlen( $css ) > self::CSS_LENGTH || str_contains( $css, "\0" ) === true || mb_check_encoding( $css, 'UTF-8' ) === false )
				return null;

			return trim( str_replace( [ "\r\n", "\r" ], "\n", $css ) );
		}

		/**
		 *	The knobs a group has with a part: those of the part where it holds, else those of the group; the page has those of its part
		 *
		 *	@param		array			$groups				The register's groups
		 *	@param		string		$group
		 *	@param		array|null								$resolved			What Reader::resolve() answered, null for the standard part
		 *
		 *	@return 	array											Knob key => true
		 */
		private static function _knobs( array $groups, string $group, ?array $resolved ): array {

			$part = $resolved !== null && $resolved['problem'] === '' ? $resolved['part'] : null;

			if( $group === 'page' )
				return $part !== null ? array_fill_keys( array_keys( $part['knobs'] ), true ) : [];

			return array_fill_keys( array_keys( $part !== null ? $part['knobs'] : $groups[$group]['knobs'] ), true );
		}

		/**
		 *	The variants each component of a group has with a part: those of its schema and those of the part
		 *
		 *	@return 	array											component => names
		 */
		private static function _variants( array &$appData, array $groups, string $group, ?array $resolved ): array {

			if( $group === 'page' )
				return [];

			$part = $resolved !== null && $resolved['problem'] === '' ? $resolved['part'] : null;
			$schemas = \Nino\Components::components( $appData );
			$variants = [];

			foreach( $groups[$group]['components'] as $component )
				$variants[$component] = array_merge( (array) ( $schemas[$component]['variants'] ?? [ 'regular' ] ), array_keys( $part['variants'][$component] ?? [] ) );

			return $variants;
		}
	}
}
