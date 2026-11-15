<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Builder\Library	see features/Builder/Builder.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino								A compact filesystembased php framework
	 *	Library							The blocks the feature brings: a section of a page, ready made,
	 *											with its words in two languages, that the editor offers under
	 *											"+ Building block" and puts into a page the way it puts in a
	 *											copy. A block is a directory of blocks/ named by its slug, with
	 *
	 *											block.php				what the dialog shows and sorts by: name and
	 *																			description by language, category, family,
	 *																			rank and the features it needs
	 *											block-<slug>.tpl		one section, in the grammar of the Builder
	 *											text/<locale>.php		the words of its keys, English and German
	 *
	 *											The keys of a block stand under /template/block-<slug>/<id>/,
	 *											<id> being the id of its section. This class reads the files
	 *											with the Reader that reads a page, and answers every block as
	 *											what Copy puts into the clipboard of the editor - the kind, the
	 *											file and the section the keys stand under, the section as a
	 *											model, the words of each key by language - so that the editor
	 *											pastes it with the one paste it has: a free id, keys of its
	 *											own on the page it goes to, no more than the languages of the
	 *											site. Nothing is written here and nothing in the project
	 *											points back to the block once it is in.
	 *
	 *											A block that is not described to the letter - a field that is
	 *											not one of the six, a field that is missing or of the wrong
	 *											kind, a template that is not one section
	 *											the Reader reads whole, a source outside the block's keys,
	 *											words in one language that the other does not have - is not
	 *											offered: a warning names it, and the others are. One that needs
	 *											a feature the project has not switched on is offered with the
	 *											names of what it needs, and not read: its components are not
	 *											there to read it with.
	 *
	 *	@package						Dape/Nino
	 *	@author							David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Library {

		// What the dialog groups by, in the order it lists them
		public const array CATEGORIES = [ 'intro', 'content', 'offer', 'action' ];

		// The languages a block brings its words in
		public const array LOCALES = [ 'en_US', 'de_DE' ];

		// The fields of block.php: all of them, and no other
		private const array FIELDS = [ 'name', 'description', 'category', 'family', 'rank', 'features' ];

		private const string SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
		private const string FEATURE = '/^[a-z][a-z0-9-]*$/D';

		// A name is a few words, a description a sentence
		private const int NAME_MAX = 60;
		private const int DESCRIPTION_MAX = 200;
		private const int RANK_MAX = 999;

		/**
		 *	The blocks of a directory, in the order the dialog lists them: by category, then
		 *	by family - the blocks of one family together, where the first of them stands - then by rank
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$directory		The directory that holds blocks, each a directory of its slug
		 *
		 *	@return 	array										Entries: slug, name and description in the language of the workbench, category, family, rank, needs - the names of the features that are not switched on, and then clip is null - and clip
		 */
		public static function blocks( array &$appData, string $directory ): array {

			$registry		= Document::schemas( $appData );
			$workbench	= \Nino\Admin\Admin::sessionLocale( $appData );
			$entries		= [];
			$directory	= rtrim( $directory, '/' );

			foreach( is_dir( $directory ) === true ? ( scandir( $directory ) ?: [] ) : [] as $slug ) {

				if( $slug[0] === '.' || is_dir( $directory. '/'. $slug ) === false )
					continue;

				$entry = self::_entry( $appData, $registry, $directory. '/'. $slug, $slug, $workbench );

				if( $entry !== null )
					$entries[] = $entry;
			}

			// A family stands where its first block does
			$first = [];

			foreach( $entries as $entry ) {
				$key = $entry['category']. '/'. $entry['family'];
				$first[$key] = min( $first[$key] ?? $entry['rank'], $entry['rank'] );
			}

			usort( $entries, static fn( array $a, array $b ): int => [
				array_search( $a['category'], self::CATEGORIES, true ), $first[$a['category']. '/'. $a['family']], $a['family'], $a['rank'], $a['slug']
			] <=> [
				array_search( $b['category'], self::CATEGORIES, true ), $first[$b['category']. '/'. $b['family']], $b['family'], $b['rank'], $b['slug']
			] );

			return $entries;
		}

		/**
		 *	One block, or null with a warning where it is not described to the letter
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$registry			The components and stacks, see Document::schemas()
		 *	@param		string		$directory		The block's directory
		 *	@param		string		$slug
		 *	@param		string		$workbench		The language of the workbench
		 *
		 *	@return 	array|null
		 */
		private static function _entry( array &$appData, array $registry, string $directory, string $slug, string $workbench ): ?array {

			if( preg_match( self::SLUG, $slug ) !== 1 ) {
				self::_warn( $slug, 'the slug is not lower case letters, digits and hyphens' );
				return null;
			}

			$described = self::_include( $directory. '/block.php' );

			if( is_array( $described ) === false ) {
				self::_warn( $slug, 'block.php does not return the description, or prints something' );
				return null;
			}

			$problem = self::_described( $described );

			if( $problem !== '' ) {
				self::_warn( $slug, $problem );
				return null;
			}

			$source	= is_file( $directory. '/block-'. $slug. '.tpl' ) === true ? (string) file_get_contents( $directory. '/block-'. $slug. '.tpl' ) : '';
			$texts	= [];

			foreach( self::LOCALES as $locale ) {

				$words = self::_include( $directory. '/text/'. $locale. '.php' );

				if( is_array( $words ) === false || array_filter( $words, static fn( mixed $word, mixed $key ): bool => is_string( $key ) === false || is_string( $word ) === false || preg_match( '#^/template/block-'. preg_quote( $slug, '#' ). '/[a-z0-9-]+/[a-z0-9-]+$#D', $key ) !== 1, ARRAY_FILTER_USE_BOTH ) !== [] ) {
					self::_warn( $slug, 'text/'. $locale. '.php does not return the words of keys of the block' );
					return null;
				}

				$texts[$locale] = $words;
			}

			if( trim( $source ) === '' ) {
				self::_warn( $slug, 'block-'. $slug. '.tpl is missing or empty' );
				return null;
			}

			if( array_diff_key( $texts['en_US'], $texts['de_DE'] ) !== [] || array_diff_key( $texts['de_DE'], $texts['en_US'] ) !== [] ) {
				self::_warn( $slug, 'the two languages do not have the same keys' );
				return null;
			}

			$needs = self::_needs( $appData, (array) $described['features'], $workbench );

			$entry = [
				'slug'				=> $slug,
				'name'				=> \Nino\Features::localized( $described['name'], $workbench ),
				'description'	=> \Nino\Features::localized( $described['description'], $workbench ),
				'category'		=> (string) $described['category'],
				'family'			=> (string) $described['family'],
				'rank'				=> (int) $described['rank'],
				'needs'				=> $needs,
				'clip'				=> null,
			];

			// What needs a feature that is off cannot be read: its components are not registered
			if( $needs !== [] )
				return $entry;

			[ $section, $reason ] = Reader::readSection( rtrim( $source ), $registry );

			if( $section === null ) {
				self::_warn( $slug, 'the template is not one section that can be read whole ('. (string) ( $reason['code'] ?? '' ). ' in line '. (int) ( $reason['line'] ?? 0 ). ')' );
				return null;
			}

			$prefix	= '/template/block-'. $slug. '/'. $section['id']. '/';
			$stray	= array_filter( self::_sources( $section ), static fn( string $uri ): bool => str_starts_with( $uri, $prefix ) === false );

			if( $stray !== [] ) {
				self::_warn( $slug, 'the source '. reset( $stray ). ' is not under the keys of the section ('. $prefix. ')' );
				return null;
			}

			$entry['clip'] = [
				'kind'		=> 'section',
				'file'		=> 'block-'. $slug,
				'section'	=> $section['id'],
				'node'		=> $section,
				'values'	=> self::_values( $appData, $texts ),
			];

			return $entry;
		}

		/**
		 *	The description of a block, checked: the six fields and no other, each of its kind
		 *
		 *	@param		array			$described
		 *
		 *	@return 	string									What is wrong; '' where nothing is
		 */
		private static function _described( array $described ): string {

			foreach( array_diff( array_keys( $described ), self::FIELDS ) as $unknown )
				return 'block.php has a field that is none of '. implode( ', ', self::FIELDS ). ': '. (string) $unknown;

			foreach( array_diff( self::FIELDS, array_keys( $described ) ) as $missing )
				return 'block.php lacks the field '. $missing;

			foreach( [ 'name' => self::NAME_MAX, 'description' => self::DESCRIPTION_MAX ] as $field => $limit ) {

				$words = $described[$field];

				if( is_array( $words ) === false || array_keys( $words ) !== self::LOCALES )
					return 'the '. $field. ' is not a line for each of '. implode( ' and ', self::LOCALES );

				foreach( $words as $line )
					if( is_string( $line ) === false || $line === '' || mb_strlen( $line ) > $limit || preg_match( '/[\r\n\[\]<>]/', $line ) === 1 )
						return 'the '. $field. ' is not a line of 1 to '. $limit. ' characters without brackets';
			}

			if( in_array( $described['category'], self::CATEGORIES, true ) === false )
				return 'the category is none of '. implode( ', ', self::CATEGORIES );

			if( is_string( $described['family'] ) === false || preg_match( self::SLUG, $described['family'] ) !== 1 )
				return 'the family is not a slug';

			if( is_int( $described['rank'] ) === false || $described['rank'] < 0 || $described['rank'] > self::RANK_MAX )
				return 'the rank is not a whole number from 0 to '. self::RANK_MAX;

			if( is_array( $described['features'] ) === false || array_is_list( $described['features'] ) === false || array_filter( $described['features'], static fn( mixed $key ): bool => is_string( $key ) === false || preg_match( self::FEATURE, $key ) !== 1 ) !== [] )
				return 'the features are not a list of feature keys';

			return '';
		}

		/**
		 *	The features a block needs that the project has not switched on
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$features			Feature keys
		 *	@param		string		$workbench		The language of the workbench
		 *
		 *	@return 	array										Their names, where they have one in the manifest, else their keys
		 */
		private static function _needs( array &$appData, array $features, string $workbench ): array {

			$needs = [];

			foreach( $features as $key ) {

				$feature = \Nino\Features::get( $appData, (string) $key );

				if( $feature !== null && $feature['active'] === true )
					continue;

				$name = $feature === null ? '' : trim( \Nino\Features::localized( $feature['name'] ?? '', $workbench ) );

				$needs[] = $name !== '' ? $name : (string) $key;
			}

			return $needs;
		}

		/**
		 *	The words of the keys, for each language of the site that a block has words for: its German
		 *	for a German language, its English for an English one and - where the site's own language is
		 *	neither - for that language. A language the site has not gets none: a text for it is refused
		 *	where it is saved
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$texts				locale => key => word, the same keys in both languages
		 *
		 *	@return 	array										key => locale => word; the word of a key that is not there in any is none
		 */
		private static function _values( array &$appData, array $texts ): array {

			$native	= \Nino\Locales::getNativeLocale( $appData );
			$values	= [];

			foreach( \Nino\Locales::getAvailableLocales( $appData ) as $locale ) {

				$language = substr( (string) $locale, 0, 2 );
				$from = match( true ) {
					$language === 'de'	=> 'de_DE',
					$language === 'en'	=> 'en_US',
					$locale === $native	=> 'en_US',
					default							=> '',
				};

				if( $from === '' )
					continue;

				foreach( $texts[$from] as $key => $word )
					if( $word !== '' )
						$values[(string) $key][(string) $locale] = $word;
			}

			return $values;
		}

		/**
		 *	Every source a section names: the texts of its components and the pictures behind it and its columns
		 *
		 *	@param		array			$section
		 *
		 *	@return 	array										Uris
		 */
		private static function _sources( array $section ): array {

			$found = [];
			$walk = static function( array $components ) use ( &$walk, &$found ): void {

				foreach( $components as $component ) {

					if( is_string( $component['source'] ?? null ) === true && $component['source'] !== '' )
						$found[] = $component['source'];

					$walk( (array) ( $component['components'] ?? [] ) );

					foreach( (array) ( $component['panels'] ?? [] ) as $panel )
						$walk( (array) ( $panel['components'] ?? [] ) );
				}
			};

			if( is_string( $section['background']['slot'] ?? null ) === true )
				$found[] = $section['background']['slot'];

			foreach( (array) ( $section['cols'] ?? [] ) as $col ) {

				if( is_string( $col['background']['slot'] ?? null ) === true )
					$found[] = $col['background']['slot'];

				$walk( (array) ( $col['components'] ?? [] ) );
			}

			return $found;
		}

		/**
		 *	A php file of a block, run; what it returned, null for what is no file, does not run or prints anything
		 *
		 *	@param		string		$file
		 *
		 *	@return 	mixed
		 */
		private static function _include( string $file ): mixed {

			if( is_file( $file ) === false )
				return null;

			// What the file prints is no answer of the panel's to spoil: a block that prints anything - a BOM, a blank before <?php, an echo - is none
			ob_start();

			try {
				$returned = include $file;
			}
			catch( \Throwable $e ) {
				$returned = null;
			}

			$printed = (string) ob_get_clean();

			return $printed === '' ? $returned : null;
		}

		/**
		 *	The warning for a block that is left out
		 *
		 *	@param		string		$slug
		 *	@param		string		$why
		 *
		 *	@return 	void
		 */
		private static function _warn( string $slug, string $why ): void {
			trigger_error( 'Builder: the block "'. preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $slug ). '" is left out: '. $why, E_USER_WARNING );
		}
	}

}
