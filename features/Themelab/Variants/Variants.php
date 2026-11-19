<?php
declare(strict_types=1);

namespace Nino\Modules\Themelab {

	// The variants of a block of the project as the editor has them and as block.json writes them. The editor sends them as a list - name, label in each language, set - of a
	// block it shows with the template and nothing else; what is written is made here from that and from the tree, never taken over: a set holds places of the tree and
	// attributes that the register writes as a class, with the values of a plain word, and the rule of the kernel has the last word
	class Variants {

		// The name of a variant is a word of the register's kind
		private const string NAME = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
		private const string PLACE = '/^(?:root|(?:0|[1-9][0-9]*)(?:\.(?:0|[1-9][0-9]*))*)$/D';
		private const string VALUE = '/^[A-Za-z0-9-][A-Za-z0-9 :-]*$/D';

		/**
		 *	The variants of a block for the editor, in the order of block.json: the first is the template itself - its set is empty - and a block that has none has that one
		 *
		 *	@param		array			$block				As \Nino\Blocks::get() answers it
		 *	@param		array			$locales			The languages of the block
		 *	@param		string		$work					The language the label of the standard is asked in
		 *
		 *	@return 	array											[ [ name, label (locale => text), set (place => attribute => value) ] ]
		 */
		public static function listed( array $block, array $locales, string $work ): array
		{
			$list = [];

			foreach( (array) ( $block['alternatives'] ?? [] ) as $name => $variant )
				$list[] = [ 'name' => (string) $name, 'label' => array_map( 'strval', (array) $variant['label'] ), 'set' => $variant['set'] === [] ? new \stdClass() : (object) array_map( static fn( array $attributes ): object => (object) $attributes, $variant['set'] ) ];

			if( $list === [] )
				$list[] = [ 'name' => 'standard', 'label' => array_combine( $locales, array_map( [ self::class, 'standard' ], $locales ) ) ?: [], 'set' => new \stdClass() ];

			return $list;
		}

		/**
		 *	The word for the first variant where nobody gave it one: the variant that is the template itself
		 *
		 *	@param		string		$locale
		 *
		 *	@return 	string
		 */
		public static function standard( string $locale ): string
		{
			return str_starts_with( $locale, 'de' ) === true ? 'Standard' : 'Default';
		}

		/**
		 *	The node a place names in a block: 'root', or the indices from the root down, the blanks counted
		 *
		 *	@param		array			$root					The root of the block
		 *	@param		string		$place
		 *
		 *	@return 	array|null								The node; null where there is no call there
		 */
		public static function at( array $root, string $place ): ?array
		{
			if( preg_match( self::PLACE, $place ) !== 1 )
				return null;

			$node = $root;

			if( $place !== 'root' )
				foreach( explode( '.', $place ) as $step ) {

					if( is_array( $node['c'] ?? null ) === false || isset( $node['c'][(int) $step] ) === false )
						return null;

					$node = $node['c'][(int) $step];
				}

			return isset( $node['n'] ) === true ? $node : null;
		}

		/**
		 *	A text or a map of language => text as the text of one language
		 */
		private static function _said( mixed $label, string $work, string $fallback ): string
		{
			if( is_array( $label ) === true )
				return (string) ( $label[$work] ?? $label[\Nino\Blocks::FALLBACK] ?? reset( $label ) ?: $fallback );

			return is_string( $label ) === true && $label !== '' ? $label : $fallback;
		}

		/**
		 *	Where a place is, as a person reads it: the component and, below the block itself, the one it stands in - "Button in Column 2", the number where its kind stands more than once
		 *
		 *	@param		array			$registry
		 *	@param		array			$root					The root of the block
		 *	@param		string		$place
		 *	@param		string		$work					The language the sentence is in
		 *
		 *	@return 	string
		 */
		public static function where( array $registry, array $root, string $place, string $work ): string
		{
			$chain = [ [ $root, 1, 1 ] ];
			$node = $root;

			if( $place !== 'root' && preg_match( self::PLACE, $place ) === 1 )
				foreach( explode( '.', $place ) as $step ) {

					if( is_array( $node['c'] ?? null ) === false || isset( $node['c'][(int) $step]['n'] ) === false )
						return $place;

					$same = array_values( array_filter( $node['c'], static fn( array $child ): bool => ( $child['n'] ?? null ) === $node['c'][(int) $step]['n'] ) );
					$number = array_search( $node['c'][(int) $step], $same, true );
					$node = $node['c'][(int) $step];
					$chain[] = [ $node, $number === false ? 1 : $number + 1, count( $same ) ];
				}

			// The component is called what the editor calls it: the word of the Builder for its kind, else the label of the register
			$file = dirname( __DIR__, 2 ). '/Builder/text/'. ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/D', $work ) === 1 && is_file( dirname( __DIR__, 2 ). '/Builder/text/'. $work. '.php' ) === true ? $work : \Nino\Blocks::FALLBACK ). '.php';
			$words = is_file( $file ) === true ? (array) require $file : [];
			$name = static fn( array $link ): string => (string) ( $words['[[/_admin/builder/kind/'. $link[0]['n']. ']]'] ?? self::_said( $registry[$link[0]['n']]['label'] ?? '', $work, (string) $link[0]['n'] ) ). ( $link[2] > 1 ? ' '. $link[1] : '' );
			$last = $chain[count( $chain ) - 1];

			if( count( $chain ) < 2 )
				return $name( $last );

			return sprintf( Sheet::words( [ $work ], 'variants/where' )[$work] ?: '%s / %s', $name( $last ), $name( $chain[count( $chain ) - 2] ) );
		}

		/**
		 *	Whether a value is one the attribute takes, where it says which: each word of it - a width may name a screen: m:33 - is one of its options
		 */
		private static function _takes( array $declared, string $value ): bool
		{
			$options = $declared['options'] ?? null;

			if( is_array( $options ) === false || $options === [] )
				return true;

			foreach( preg_split( '/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY ) ?: [] as $word ) {

				if( ( $declared['responsive'] ?? false ) === true && preg_match( '/^[a-z]+:(.+)$/D', $word, $found ) === 1 )
					$word = $found[1];

				if( in_array( $word, array_map( 'strval', $options ), true ) === false )
					return false;
			}

			return true;
		}

		/**
		 *	The variants of a draft as block.json writes them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$root					The root of the block as the draft has it - the template
		 *	@param		mixed			$given				What the request brings: a list of [ name, label, set ]
		 *	@param		array			$locales			The languages of the block, which every label has
		 *	@param		string		$work					The language of the person: a label that is given in it is the one that stands where a language has none
		 *
		 *	@return 	array											'variants' => the map of block.json, or null where the block has no choice (the standard alone); or 'failure' => [ status, message, code, params, field ]
		 */
		public static function built( array &$appData, array $root, mixed $given, array $locales, string $work ): array
		{
			$fail = static fn( string $code, string $label = '', string $place = '', string $detail = '' ): array => [ 'failure' => [ 400, 'the variants are not taken: '. $code, 'themelab_variant_'. $code, $code === 'place' ? [ $label ] : [ $label, $place, $detail ], 'variants' ] ];

			if( is_array( $given ) === false || array_is_list( $given ) === false || count( $given ) > \Nino\Blocks::MAX_VARIANTS || $given === [] )
				return $fail( 'shape' );

			$registry = \Nino\Components::components( $appData );
			$out = [];
			$names = [];

			foreach( $given as $at => $variant ) {

				$name = is_array( $variant ) === true && is_string( $variant['name'] ?? null ) === true ? $variant['name'] : '';
				$labels = is_array( $variant ) === true && is_array( $variant['label'] ?? null ) === true ? $variant['label'] : [];
				$main = is_string( $labels[$work] ?? null ) === true ? trim( $labels[$work] ) : '';

				if( $main === '' )
					foreach( $labels as $text )
						if( is_string( $text ) === true && trim( $text ) !== '' ) {
							$main = trim( $text );
							break;
						}

				// The first variant is the template, and a template that nobody named is the standard: it has a word in every language - the word of the standard in that language -,
				// never none
				$given = $main !== '';

				if( preg_match( self::NAME, $name ) !== 1 || strlen( $name ) > \Nino\Components::NAME_LENGTH || isset( $names[$name] ) === true )
					return $fail( 'name', $main === '' ? $name : $main );

				$names[$name] = true;
				$label = [];

				foreach( $locales as $locale ) {

					$text = is_string( $labels[$locale] ?? null ) === true && trim( $labels[$locale] ) !== '' ? trim( $labels[$locale] ) : ( $at === 0 && $given === false ? self::standard( $locale ) : $main );

					if( $text === '' || mb_strlen( $text ) > \Nino\Blocks::MAX_NAME || preg_match( '/[<>\x00-\x1F\x7F]|\[\[/u', $text ) === 1 )
						return $fail( 'label', $name );

					$label[$locale] = $text;
				}

				$set = is_array( $variant ) === true && is_array( $variant['set'] ?? null ) === true ? $variant['set'] : ( is_object( $variant['set'] ?? null ) === true ? (array) $variant['set'] : [] );

				// The first is the template itself: what it would set is the template's
				if( $at === 0 && array_filter( $set, static fn( mixed $attributes ): bool => is_array( $attributes ) === true ? $attributes !== [] : true ) !== [] )
					return $fail( 'first', $label[$work] ?? $main );

				$clean = [];

				foreach( $set as $place => $attributes ) {

					$place = (string) $place;
					$attributes = is_object( $attributes ) === true ? (array) $attributes : $attributes;

					if( is_array( $attributes ) === false )
						return $fail( 'shape', $label[$work] ?? $main, $place );

					if( $attributes === [] )
						continue;

					$node = self::at( $root, $place );

					if( $node === null )
						return $fail( 'place', $label[$work] ?? $main, $place );

					$schema = $registry[$node['n']] ?? null;

					foreach( $attributes as $attribute => $value ) {

						$declared = $schema === null ? null : ( $schema['attributes'][(string) $attribute] ?? null );

						if( $declared === null || ( $declared['class'] ?? false ) !== true )
							return $fail( 'attribute', $label[$work] ?? $main, self::where( $registry, $root, $place, $work ), self::_said( $declared['label'] ?? '', $work, (string) $attribute ) );

						if( is_string( $value ) === false || $value === '' || strlen( $value ) > \Nino\Blocks::MAX_NAME || preg_match( self::VALUE, $value ) !== 1 || self::_takes( $declared, $value ) === false )
							return $fail( 'value', $label[$work] ?? $main, self::where( $registry, $root, $place, $work ), self::_said( $declared['label'] ?? '', $work, (string) $attribute ) );

						$clean[$place][(string) $attribute] = $value;
					}
				}

				$out[$name] = [ 'label' => $label, 'set' => $clean === [] ? new \stdClass() : (object) array_map( static fn( array $attributes ): object => (object) $attributes, $clean ) ];
			}

			// The standard alone is no choice
			return [ 'variants' => count( $out ) < 2 ? null : $out ];
		}
	}
}
