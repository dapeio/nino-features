<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Search				A small weighted fuzzy index for Elements
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	class Search {

		private const array WEIGHTS = [
			0 => 1.00,
			1 => 0.70,
			2 => 0.45,
			3 => 0.25,
		];

		private const int MAX_QUERY_LENGTH = 256;
		private const int MAX_QUERY_TOKENS = 12;

		/*	How many of the query's words have to be found for a document to
			count as a hit at all. One - a word the document does not carry no
			longer throws the document away.

			It used to: every token had to reach its threshold or _score()
			returned null. That reads as reasonable until somebody types a
			sentence. An article titled "AI im Jahr 2026" was not found by "AI in
			2026", and "Ausblick der Modelle" found nothing in a summary reading
			"Ein Ausblick auf Modelle und Werkzeuge" - one filler word the text
			happens not to use, and the whole result is gone. Visitors type
			sentences.

			What the missed word does instead is lower the coverage, and the
			coverage multiplies the score: three words of three always outranks
			two of three, so a partial match lands below a full one rather than
			nowhere. */
		private const int MIN_MATCHED_TOKENS = 1;

		// The index file's own entry, beside the locales. A dot-prefixed key is
		// never a locale, so an older index without it still reads
		public const string META = '.meta';
		public const int META_FORMAT = 1;

		/*	The model field types a priority slot may be given. 'image' carries a
			path, 'element' a reference and 'boolean' a flag - none of them is text
			a visitor would type, and _normalizeValue() would reduce them to noise
			or to nothing. The panel's field picker offers exactly this set */
		public const array INDEXABLE = [ 'string', 'integer', 'double', 'array', 'date', 'datetime' ];

		/**
		 *	The /_admin screen this module brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears in the
		 *	dev area exactly while this module is active and vanishes with it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Search\Admin::class ];
		}

		/**
		 *	Register the write-time refresh. Building the initial set stays an
		 *	explicit action in /_admin > Config; init itself performs no I/O.
		 */
		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements/committed', [ self::class, 'callbackElementsCommitted' ] );
		}

		/**
		 *	Refresh the changed configured type after its Elements write committed.
		 */
		public static function callbackElementsCommitted( array &$appData, array &$change ): void {

			$typeUri = self::_typeUri( $change['type'] ?? null );
			if( $typeUri === null )
				return;

			$types = self::_configuredTypes( $appData );
			if( isset( $types[$typeUri] ) === false )
				return;

			if( self::_createIndex( $appData, $typeUri, $types[$typeUri] ) === false )
				trigger_error( 'Search index for \''. $typeUri. '\' could not be written.' );
		}

		/**
		 *	Recreate every index named by /nino/elements/index, or just the one
		 *	type named. This is the API behind the Search panel's buttons
		 *
		 *	`skipped` and `issues` are what the old version of this threw away: a
		 *	configured type that cannot be indexed at all, and a type that is
		 *	indexed but whose configuration holds a name that does not resolve.
		 *	Reporting "created: 1" for a configuration naming two types told
		 *	nobody which of them was misspelled, and a configuration that was
		 *	entirely invalid came back as "nothing is configured"
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$only					One canonical /type, or '' for all of them
		 *
		 *	@return 	array{created:int,elements:int,failed:array,skipped:array,issues:array}
		 */
		public static function createIndexes( array &$appData, string $only = '' ): array {

			$result = [
				'created'		=> 0,
				'elements'	=> 0,
				'failed'		=> [],
				'skipped'		=> [],
				'issues'		=> [],
			];

			$wanted = $only === '' ? null : self::_typeUri( $only );

			if( $only !== '' && $wanted === null )
				return $result;

			foreach( self::configuration( $appData ) as $typeUri => $entry ) {

				if( $wanted !== null && $typeUri !== $wanted )
					continue;

				if( $entry['fields'] === [] ) {
					$result['skipped'][$typeUri] = $entry['issues'];
					continue;
				}

				$count = self::_createIndex( $appData, $typeUri, $entry['fields'] );

				if( $count === false ) {
					$result['failed'][] = $typeUri;
					continue;
				}

				// Indexed, but the configuration still holds a name that does not
				// resolve - a different thing from not being indexed at all
				if( $entry['issues'] !== [] )
					$result['issues'][$typeUri] = $entry['issues'];

				$result['created']++;
				$result['elements'] += $count;
			}

			return $result;
		}

		/**
		 *	What the current locale's index says, without touching the Elements
		 *	themselves: one row per hit, best score first. The cheap half of a
		 *	search - a result page that shows ten of two hundred hits has no
		 *	business reading two hundred Element files to find that out
		 *
		 *	One type or several: the scores come off the same scale, so hits from
		 *	two types interleave by score exactly as they would within one.
		 *
		 *	A missing or invalid index is simply an empty result; reads never
		 *	create or repair derived files.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string|array	$elementType	One type, or several
		 *	@param		string		$searchString	What was typed
		 *	@param		int				$limit				0 for everything
		 *	@param		int				$offset				How many of the best to skip
		 *
		 *	@return 	array										[ uri, type, score, coverage, fields ], …
		 */
		public static function getHits( array &$appData, string|array $elementType, string $searchString, int $limit = 0, int $offset = 0 ): array {

			$query = self::_normalize( (string) mb_substr( $searchString, 0, self::MAX_QUERY_LENGTH ) );
			$tokens = array_slice( self::_tokens( $query ), 0, self::MAX_QUERY_TOKENS );

			if( $query === '' || $tokens === [] )
				return [];

			$types = self::_configuredTypes( $appData );
			$locale = \Nino\Locales::getCurrentLocale( $appData );
			$hits = [];

			foreach( is_array( $elementType ) === true ? $elementType : [ $elementType ] as $wanted ) {

				$typeUri = self::_typeUri( $wanted );

				if( $typeUri === null || isset( $types[$typeUri] ) === false )
					continue;

				$documents = self::_readIndex( $appData, $typeUri )[$locale] ?? null;

				if( is_array( $documents ) === false )
					continue;

				foreach( $documents as $uri => $fields ) {

					if( is_string( $uri ) === false || is_array( $fields ) === false )
						continue;

					$hit = self::_score( $fields, $query, $tokens );

					if( $hit !== null )
						$hits[] = [ 'uri' => $uri, 'type' => $typeUri ] + $hit;
				}
			}

			usort( $hits, function( array $a, array $b ): int {
				$score = $b['score'] <=> $a['score'];
				return $score !== 0 ? $score : strcmp( $a['uri'], $b['uri'] );
			} );

			$offset = max( 0, $offset );

			return $limit > 0 ? array_slice( $hits, $offset, $limit ) : array_slice( $hits, $offset );
		}

		/**
		 *	The same search, with the Elements read. Every returned Element
		 *	carries its '.score' and '.type' beside the '.uri' and '.locale' it
		 *	always had - a result list that cannot say how well something matched
		 *	cannot sort or explain itself
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string|array	$elementType	One type, or several
		 *	@param		string		$searchString	What was typed
		 *	@param		int				$limit				0 for everything - a page should say a number
		 *	@param		int				$offset				How many of the best to skip
		 *
		 *	@return 	array										Canonical Elements, best score first
		 */
		public static function getElements( array &$appData, string|array $elementType, string $searchString, int $limit = 0, int $offset = 0 ): array {

			$locale = \Nino\Locales::getCurrentLocale( $appData );
			$elements = [];

			foreach( self::getHits( $appData, $elementType, $searchString, $limit, $offset ) as $hit ) {

				$element = \Nino\Elements::getElement( $appData, $hit['uri'], $locale );

				if( is_array( $element ) === true )
					$elements[] = $element + [ '.score' => $hit['score'], '.type' => $hit['type'] ];
			}

			return $elements;
		}

		/**
		 *	Validated configured types as canonical /type => priority => field.
		 */
		private static function _configuredTypes( array &$appData ): array {

			$types = [];

			foreach( self::configuration( $appData ) as $typeUri => $entry )
				if( $entry['fields'] !== [] )
					$types[$typeUri] = $entry['fields'];

			return $types;
		}

		/**
		 *	Everything /nino/elements/index names, whether it is usable or not,
		 *	and for the unusable half the reason why.
		 *
		 *	The silent version of this was the feature's worst habit: a field name
		 *	with a typo in it was dropped without a word, the panel then reported
		 *	"1 index created" for a configuration naming two types, and a project
		 *	whose whole configuration was invalid was told "no search indexes are
		 *	configured". Nothing anywhere said which name was wrong
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										/type => [ fields, issues ], sorted
		 */
		public static function configuration( array &$appData ): array {

			$config = $appData['/nino/elements/index'] ?? null;

			if( is_array( $config ) === false )
				return [];

			$types = [];

			foreach( $config as $rawType => $rawFields ) {

				$typeUri = self::_typeUri( $rawType );

				if( $typeUri === null ) {
					// An array key is an int or a string, so it is always printable
					$types['?'. (string) $rawType] = [
						'fields' => [], 'issues' => [ 'not an element type name' ],
					];
					continue;
				}

				$entry = [ 'fields' => [], 'issues' => [] ];

				if( is_array( $rawFields ) === false ) {
					$entry['issues'][] = 'the entry is not a list of fields';
					$types[$typeUri] = $entry;
					continue;
				}

				if( \Nino\Filesystem::fileExists( $appData, '/elements/'. ltrim( $typeUri, '/' ). '.php' ) === false ) {
					$entry['issues'][] = 'there is no element type "'. $typeUri. '"';
					$types[$typeUri] = $entry;
					continue;
				}

				$model = \Nino\Elements::getElementModel( $appData, $typeUri );

				foreach( $rawFields as $priority => $field ) {

					if( is_int( $priority ) === false || isset( self::WEIGHTS[$priority] ) === false ) {
						$entry['issues'][] = 'priority "'. (string) $priority. '" is not 0, 1, 2 or 3';
						continue;
					}

					if( is_string( $field ) === false ) {
						$entry['issues'][] = 'priority '. $priority. ' does not name a field';
						continue;
					}

					if( isset( $model[$field] ) === false ) {
						$entry['issues'][] = 'the model of "'. $typeUri. '" has no field "'. $field. '"';
						continue;
					}

					$entry['fields'][$priority] = $field;
				}

				if( $entry['fields'] === [] && $entry['issues'] === [] )
					$entry['issues'][] = 'no field is named';

				ksort( $entry['fields'] );
				$types[$typeUri] = $entry;
			}

			ksort( $types );

			return $types;
		}

		/**
		 *	What the panel draws: one row per element type the project has, plus
		 *	any the configuration names that it does not - the row saying why a
		 *	type is not working is the whole reason the list exists
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										One row per type, sorted by type uri
		 */
		public static function indexState( array &$appData ): array {

			$configured = self::configuration( $appData );
			$rows = [];

			foreach( glob( \Nino\Filesystem::path( $appData, '/elements' ). '/*.php' ) ?: [] as $file )
				$rows['/'. basename( $file, '.php' )] = self::_typeRow( $appData, '/'. basename( $file, '.php' ) );

			foreach( array_keys( $configured ) as $typeUri )
				$rows[$typeUri] = $rows[$typeUri] ?? self::_typeRow( $appData, $typeUri );

			foreach( $rows as $typeUri => $row ) {

				$entry = $configured[$typeUri] ?? null;

				$rows[$typeUri] = $row + [
					'configured' 	=> $entry !== null,
					'fields' 			=> $entry['fields'] ?? [],
					'issues' 			=> $entry['issues'] ?? [],
				] + self::_indexFileState( $appData, $typeUri, $entry['fields'] ?? [] );
			}

			ksort( $rows );

			return array_values( $rows );
		}

		/**
		 *	One element type as the panel needs it: what it is called, what its
		 *	model offers as an indexable field, and how many elements it holds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$typeUri			A canonical /type
		 *
		 *	@return 	array
		 */
		private static function _typeRow( array &$appData, string $typeUri ): array {

			$typeData = \Nino\Filesystem::getFileContent( $appData, '/elements/'. ltrim( $typeUri, '/' ). '.php', [] );
			$typeData = is_array( $typeData ) === true ? $typeData : [];
			$model 		= is_array( $typeData['model'] ?? null ) === true ? $typeData['model'] : [];

			/*	An element exists as soon as one locale carries data for it - the
				same union queryElements() takes, and for the same reason: a
				half-translated element is still an element */
			$slugs = array_keys( is_array( $typeData['*'] ?? null ) === true ? $typeData['*'] : [] );

			foreach( \Nino\Locales::getAvailableLocales( $appData ) as $locale )
				if( is_array( $typeData[$locale] ?? null ) === true )
					$slugs = array_merge( $slugs, array_keys( $typeData[$locale] ) );

			return [
				'type' 			=> $typeUri,
				'title' 		=> is_string( $typeData['title'] ?? null ) === true ? $typeData['title'] : ltrim( $typeUri, '/' ),
				'exists' 		=> $model !== [] || $typeData !== [],
				// What a priority slot may be given: every field the model has,
				// minus the ones that carry no text to search (see _normalizeValue)
				'model' 		=> array_values( array_filter( array_keys( $model ),
										static fn( string $field ): bool => in_array( (string) ( $model[$field]['type'] ?? 'string' ), self::INDEXABLE, true ) === true ) ),
				'elements' 	=> count( array_diff( array_unique( $slugs ), [ '*' ] ) ),
			];
		}


		/**
		 *	What the derived file of one type says about itself: when it was
		 *	built, out of what, and whether the type has moved on since
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$typeUri			A canonical /type
		 *	@param		array 		$fields				What the configuration names today
		 *
		 *	@return 	array										indexed, built, indexed elements, stale
		 */
		private static function _indexFileState( array &$appData, string $typeUri, array $fields ): array {

			$path = \Nino\Filesystem::path( $appData, self::_indexPath( $typeUri ) );

			if( is_file( $path ) === false )
				return [ 'indexed' => false, 'built' => '', 'indexedElements' => 0, 'stale' => $fields !== [] ];

			$meta = self::_readIndex( $appData, $typeUri )[self::META] ?? [];
			$meta = is_array( $meta ) === true ? $meta : [];

			$typePath = \Nino\Filesystem::path( $appData, '/elements/'. ltrim( $typeUri, '/' ). '.php' );

			/*	Two ways to be out of date, and both are one stat call: the type
				file has been written since the index was (an element changed while
				the feature was switched off, a restored backup, a hand edit), or
				the configuration names other fields than the ones it was built
				from. Counting elements would be the third and is not needed - a
				changed element changes the file's mtime */
			$stale = ( is_file( $typePath ) === true && filemtime( $typePath ) > filemtime( $path ) )
				|| ( $fields !== [] && ( $meta['fields'] ?? null ) !== $fields );

			return [
				'indexed' 				=> true,
				'built' 					=> (string) ( $meta['built'] ?? '' ),
				'indexedElements' => (int) ( $meta['elements'] ?? 0 ),
				'stale' 					=> $stale,
			];
		}

		/**
		 *	One flat Elements type only. Both "services" and "/services" are
		 *	accepted at the API/config boundary and canonicalized to "/services".
		 */
		private static function _typeUri( mixed $type ): ?string {

			if( is_string( $type ) === false )
				return null;

			$slug = trim( $type, '/' );
			return preg_match( '/^[a-z][a-z0-9_-]*$/', $slug ) === 1 ? '/'. $slug : null;
		}

		/**
		 *	Create the complete locale index for one configured type.
		 *
		 *	@return int|false Number of distinct indexed Elements, or false on write failure
		 */
		private static function _createIndex( array &$appData, string $typeUri, array $fields ): int|false {

			$index = [];
			$elementUris = [];

			foreach( \Nino\Locales::getAvailableLocales( $appData ) as $locale ) {

				if( is_string( $locale ) === false || \Nino\Locales::verifyLocale( $appData, $locale ) === false )
					continue;

				$documents = [];
				foreach( \Nino\Elements::queryElements( $appData, $typeUri, [], $locale, [] ) as $element ) {

					$uri = $element['.uri'] ?? null;
					if( is_string( $uri ) === false )
						continue;

					$document = [];
					foreach( $fields as $priority => $field ) {
						$value = self::_normalizeValue( $element[$field] ?? null );
						if( $value !== '' )
							$document[$priority] = $value;
					}

					if( $document !== [] ) {
						$documents[$uri] = $document;
						$elementUris[$uri] = true;
					}
				}

				ksort( $documents );
				$index[$locale] = $documents;
			}

			ksort( $index );

			/*	What the panel reads to say "built two hours ago, out of these
				fields". A dot-prefixed key can never be a locale, so an index
				written before this existed still reads and every locale lookup
				below walks straight past it */
			$index = [ self::META => [
				'format' 		=> self::META_FORMAT,
				'built' 		=> gmdate( 'c' ),
				'elements' 	=> count( $elementUris ),
				'fields' 		=> $fields,
			] ] + $index;

			return self::_writeIndex( $appData, $typeUri, $index ) === true ? count( $elementUris ) : false;
		}

		/**
		 *	One direct, non-atomic full-file write. Search indexes are deliberately
		 *	simple derived files: no mutation, side-car lock, revision or signature.
		 */
		private static function _writeIndex( array &$appData, string $typeUri, array $index ): bool {

			\Nino\Filesystem::forceDir( $appData, '/data' );
			$path = \Nino\Filesystem::path( $appData, self::_indexPath( $typeUri ) );
			$content = '<?php return '. var_export( $index, true ). ';';
			$written = @file_put_contents( $path, $content );

			if( $written === false || $written !== strlen( $content ) )
				return false;

			clearstatcache( true, $path );

			if( function_exists( 'opcache_invalidate' ) === true )
				opcache_invalidate( $path, true );

			return true;
		}

		/**
		 *	Read only. A broken or missing index stays broken/missing until the
		 *	Config button is pressed or a configured Elements write refreshes it.
		 */
		private static function _readIndex( array &$appData, string $typeUri ): array {

			$path = \Nino\Filesystem::path( $appData, self::_indexPath( $typeUri ) );
			if( is_file( $path ) === false )
				return [];

			try {
				$index = @include $path;
			}
			catch( \Throwable ) {
				return [];
			}

			return is_array( $index ) === true ? $index : [];
		}

		private static function _indexPath( string $typeUri ): string {
			return '/data/index-'. ltrim( $typeUri, '/' ). '.php';
		}

		/**
		 *	Flatten a normal Elements scalar or array field into searchable text.
		 */
		private static function _normalizeValue( mixed $value ): string {

			if( is_array( $value ) === true ) {
				$parts = [];
				array_walk_recursive( $value, function( mixed $part ) use ( &$parts ): void {
					if( is_scalar( $part ) === true && is_bool( $part ) === false )
						$parts[] = (string) $part;
				} );
				return self::_normalize( implode( ' ', $parts ) );
			}

			return is_scalar( $value ) === true && is_bool( $value ) === false
				? self::_normalize( (string) $value )
				: '';
		}

		private static function _normalize( string $value ): string {

			$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$value = mb_strtolower( strip_tags( $value ), 'UTF-8' );
			$value = strtr( $value, [ 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss' ] );
			$value = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $value );
			return is_string( $value ) === true ? trim( preg_replace( '/\s+/u', ' ', $value ) ?? '' ) : '';
		}

		private static function _tokens( string $value ): array {
			return array_values( array_unique( array_filter( explode( ' ', $value ), fn( string $token ): bool => $token !== '' ) ) );
		}

		/**
		 *	What one document is worth for one query, or null when it is not a hit
		 *	at all. A query token has to reach its length-dependent fuzzy
		 *	threshold to count; one that does not lowers the coverage instead of
		 *	discarding the document (see MIN_MATCHED_TOKENS). Priority weights
		 *	affect ranking, never whether a word counts as found
		 *
		 *	@param		array 		$fields				priority => normalised text
		 *	@param		string		$query				The whole normalised query
		 *	@param		array 		$queryTokens	Its unique words
		 *
		 *	@return 	array|null									score, coverage, and which priorities
		 *																			carried a match - the panel's probe shows
		 *																			the last of these
		 */
		private static function _score( array $fields, string $query, array $queryTokens ): ?array {

			$fieldTokens 	= [];
			$score 				= 0.0;
			$matched 			= 0;
			$hitFields 		= [];

			foreach( $queryTokens as $queryToken ) {

				$bestSimilarity = 0.0;
				$bestWeighted = 0.0;
				$bestPriority = null;

				foreach( $fields as $priority => $text ) {

					if( isset( self::WEIGHTS[$priority] ) === false || is_string( $text ) === false )
						continue;

					$fieldTokens[$priority] = $fieldTokens[$priority] ?? self::_tokens( $text );
					foreach( $fieldTokens[$priority] as $candidate ) {

						$similarity = self::_similarity( $queryToken, $candidate );
						$bestSimilarity = max( $bestSimilarity, $similarity );

						if( $similarity * self::WEIGHTS[$priority] > $bestWeighted ) {
							$bestWeighted = $similarity * self::WEIGHTS[$priority];
							$bestPriority = $priority;
						}
					}
				}

				// Not found is not fatal any more - it simply does not count
				if( $bestSimilarity < self::_threshold( $queryToken ) )
					continue;

				$matched++;
				$score += $bestWeighted;

				if( $bestPriority !== null )
					$hitFields[$bestPriority] = true;
			}

			if( $matched < self::MIN_MATCHED_TOKENS )
				return null;

			// A complete phrase is more intentional than the same words scattered
			// through several fields; reward it without making it mandatory.
			foreach( $fields as $priority => $text )
				if( isset( self::WEIGHTS[$priority] ) === true && is_string( $text ) === true && str_contains( $text, $query ) === true )
					$score += self::WEIGHTS[$priority] * 1.5;

			/*	Coverage multiplies rather than gates: everything the visitor asked
				for outranks most of it, which outranks one word of it - and the
				document that carries only the filler word still comes last instead
				of not coming at all */
			$coverage = (float) $matched / (float) max( 1, count( $queryTokens ) );

			ksort( $hitFields );

			return [
				'score' 		=> $score * $coverage,
				'coverage' 	=> $coverage,
				'matched' 	=> $matched,
				'fields' 		=> array_keys( $hitFields ),
			];
		}

		/**
		 *	Exact/prefix/substring shortcuts plus Unicode bigram Dice similarity.
		 */
		private static function _similarity( string $needle, string $candidate ): float {

			if( $needle === $candidate )
				return 1.0;

			$needleLength = mb_strlen( $needle, 'UTF-8' );
			$candidateLength = mb_strlen( $candidate, 'UTF-8' );
			$shortest = min( $needleLength, $candidateLength );
			$longest = max( $needleLength, $candidateLength );

			// One- and two-character terms are useful ("AI", years split into
			// numbers), but fuzzy matching them produces mostly noise.
			if( $shortest <= 2 )
				return 0.0;

			if( str_starts_with( $candidate, $needle ) === true || str_starts_with( $needle, $candidate ) === true )
				return 0.94;

			if( $shortest >= 4 && ( str_contains( $candidate, $needle ) === true || str_contains( $needle, $candidate ) === true ) )
				return 0.86;

			if( abs( $needleLength - $candidateLength ) > max( 2, (int) ceil( $longest * 0.4 ) ) )
				return 0.0;

			return self::_dice( $needle, $candidate );
		}

		private static function _threshold( string $token ): float {
			$length = mb_strlen( $token, 'UTF-8' );
			return match( true ) {
				$length <= 2 => 1.0,
				$length === 3 => 0.70,
				$length === 4 => 0.60,
				default => 0.52,
			};
		}

		private static function _dice( string $a, string $b ): float {

			$aGrams = self::_bigrams( $a );
			$bGrams = self::_bigrams( $b );
			if( $aGrams === [] || $bGrams === [] )
				return 0.0;

			$aCounts = array_count_values( $aGrams );
			$bCounts = array_count_values( $bGrams );
			$common = 0;
			foreach( $aCounts as $gram => $count )
				$common += min( $count, $bCounts[$gram] ?? 0 );

			return ( 2 * $common ) / ( count( $aGrams ) + count( $bGrams ) );
		}

		private static function _bigrams( string $word ): array {

			$characters = preg_split( '//u', $word, -1, PREG_SPLIT_NO_EMPTY );
			if( is_array( $characters ) === false || $characters === [] )
				return [];

			array_unshift( $characters, '^' );
			$characters[] = '$';

			$grams = [];
			for( $i = 0; $i < count( $characters ) - 1; $i++ )
				$grams[] = $characters[$i]. $characters[$i + 1];

			return $grams;
		}
	}
}
