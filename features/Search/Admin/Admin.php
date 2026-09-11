<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Search\Admin		The /_admin panel of the Search feature - see
 *											features/Search/README.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Search {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Search\Admin			The "Search" panel. Up to 1.0.0 this was one button and
	 *										nothing else: it rebuilt the indexes and reported a
	 *										number. Everything the button could not say had to be
	 *										worked out from config.php by hand - which types are
	 *										indexed, whether the index still answers to them, why a
	 *										configured type produces no hits, and whether the
	 *										ranking does what somebody wanted.
	 *
	 *										Three screens now:
	 *
	 *										  Index		one row per element type the project has, with
	 *														what it indexes, both counts and its state
	 *										  Type		the four priority slots, each a select over
	 *														the type's own model - a field name cannot be
	 *														mistyped into a slot any more
	 *										  Probe		a query, and the hits with their scores
	 *
	 *										The last of those is the one that earns its keep: this
	 *										is a ranking feature, and a ranking is invisible. Move
	 *										a field from priority 1 to 0 and the order changes on
	 *										screen, in the same panel, without a page to test it on.
	 *
	 *										Saving writes /nino/elements/index in config.php. That
	 *										key had no editor at all before, which is the one case
	 *										where a panel owning a config key is uncontroversial:
	 *										there is no second writer to disagree with.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/search/manage';

		// The config key the Type screen writes. Named here rather than spelled
		// out in three methods, since it is also what the panel is allowed to
		// touch and nothing else
		public const string CONFIG = '/nino/elements/index';

		// What a probe answers with at most, whatever it is asked for
		private const int PROBE_LIMIT = 25;

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'search/list' 				=> [ self::class, 'apiList' ],
				'search/save' 				=> [ self::class, 'apiSave' ],
				'search/createindex' 	=> [ self::class, 'apiCreateIndex' ],
				'search/probe' 				=> [ self::class, 'apiProbe' ],
			];
		}

		/*	The group named here is decorative: \Nino\Admin\Admin::_entry() puts
			every panel a feature brought into 'features' regardless, so that
			granting that one group is a bounded grant. Kept meaningful anyway,
			for the day a panel like this one is not a feature's */
		public static function nav(): array {
			return [ 'search', '/_admin/nav/search', 30, 'system' ];
		}

		/**
		 *	A Dashboard tile: how much is searchable, and whether it is current
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null							null while nothing is configured
		 */
		public static function summary( array &$appData ): ?array {

			$types = 0;
			$elements = 0;
			$stale = false;

			foreach( \Nino\Modules\Search::indexState( $appData ) as $row ) {

				if( $row['configured'] !== true || $row['fields'] === [] )
					continue;

				$types++;
				$elements += (int) $row['indexedElements'];
				$stale = $stale || $row['stale'] === true;
			}

			return $types === 0 ? null : [
				'value' => (string) $elements,
				'label' => $stale === true ? '/_admin/search/label/tile-stale' : '/_admin/search/label/tile',
			];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-code-icon lucide-search-code"><path d="m13 13.5 2-2.5-2-2.5"/><path d="m21 21-4.3-4.3"/><path d="M9 8.5 7 11l2 2.5"/><circle cx="11" cy="11" r="8"/></svg>';
		}

		public static function panes(): array {
			return [ 'search-list', 'search-type', 'search-probe' ];
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'search/createindex'	=> 'Rebuild Search Index',
				'search/save'					=> 'Configure Search Index'. ( is_string( $data['type'] ?? null ) === true ? ' ('. $data['type']. ')' : '' ),
				default	=> '',
			};
		}

		/**
		 *	Everything the Index screen draws
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [
				'types' 		=> \Nino\Modules\Search::indexState( $appData ),
				// The panel's four slots and the numbers beside them come from
				// the engine, so the interface cannot promise a weight the
				// ranking does not use
				'weights' 	=> \Nino\Modules\Search::WEIGHTS,
				'indexable' => \Nino\Modules\Search::INDEXABLE,
				'locales' 	=> \Nino\Locales::getAvailableLocales( $appData ),
			] );
		}

		/**
		 *	Write one type's field map into /nino/elements/index. An empty map
		 *	takes the type out of the configuration and removes the derived file
		 *	with it - an index nobody searches is a copy of the content with
		 *	nothing reading it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data 		= \Nino\Admin\Admin::postData();
			$typeUri 	= '/'. trim( (string) ( $data['type'] ?? '' ), '/' );
			$posted 	= $data['fields'] ?? [];

			if( preg_match( '/^\/[a-z][a-z0-9_-]*$/', $typeUri ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'not an element type name' );
				return;
			}

			if( \Nino\Filesystem::fileExists( $appData, '/elements/'. ltrim( $typeUri, '/' ). '.php' ) === false ) {
				\Nino\Http::fail( $request, 404, 'there is no element type "'. $typeUri. '"' );
				return;
			}

			if( is_array( $posted ) === false ) {
				\Nino\Http::fail( $request, 400, 'no fields posted' );
				return;
			}

			$model 	= \Nino\Elements::getElementModel( $appData, $typeUri );
			$fields = [];

			foreach( $posted as $priority => $field ) {

				$priority = is_int( $priority ) === true ? $priority : (int) $priority;
				$field 		= trim( (string) $field );

				if( $field === '' )
					continue;

				if( isset( \Nino\Modules\Search::WEIGHTS[$priority] ) === false ) {
					\Nino\Http::fail( $request, 400, 'priority "'. $priority. '" is not 0, 1, 2 or 3' );
					return;
				}

				if( isset( $model[$field] ) === false ) {
					\Nino\Http::fail( $request, 400, 'the model of "'. $typeUri. '" has no field "'. $field. '"' );
					return;
				}

				if( in_array( (string) ( $model[$field]['type'] ?? 'string' ), \Nino\Modules\Search::INDEXABLE, true ) === false ) {
					\Nino\Http::fail( $request, 400, 'the field "'. $field. '" carries no text to search' );
					return;
				}

				$fields[$priority] = $field;
			}

			ksort( $fields );

			$config = is_array( $appData[self::CONFIG] ?? null ) === true ? $appData[self::CONFIG] : [];

			// Both spellings are the same type at the configuration boundary,
			// so a save must not leave the other one behind to shadow it
			unset( $config[$typeUri], $config[ltrim( $typeUri, '/' )] );

			$removed = false;

			if( $fields === [] ) {
				$path = \Nino\Filesystem::path( $appData, '/data/index-'. ltrim( $typeUri, '/' ). '.php' );
				$removed = is_file( $path ) === true && @unlink( $path ) === true;
			}
			else
				$config[$typeUri] = $fields;

			ksort( $config );
			$appData[self::CONFIG] = $config;

			if( \Nino\AppData::writeContentData( $appData, [ self::CONFIG ] ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. self::CONFIG. ' to config.php' );
				return;
			}

			// Configured is not indexed: the rebuild is its own decision, and
			// the screen says which of the two just happened
			\Nino\Http::ok( $request, [ 'type' => $typeUri, 'fields' => $fields, 'removed' => $removed ] );
		}

		/**
		 *	Recreate every configured index, or the one type named
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiCreateIndex( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$only 		= (string) ( \Nino\Admin\Admin::postData()['type'] ?? '' );
			$result 	= \Nino\Modules\Search::createIndexes( $appData, $only );

			if( $result['failed'] !== [] ) {
				\Nino\Http::fail( $request, 500, 'could not create search indexes for '. implode( ', ', $result['failed'] ) );
				return;
			}

			\Nino\Http::ok( $request, $result );
		}

		/**
		 *	Run a search and answer what it found, with the scores. The screen
		 *	the whole panel exists for: a ranking nobody can see is a ranking
		 *	nobody can tune
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiProbe( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data 	= \Nino\Admin\Admin::postData();
			$query 	= (string) ( $data['query'] ?? '' );
			$types 	= is_array( $data['types'] ?? null ) === true ? array_values( $data['types'] ) : [];
			$locale = (string) ( $data['locale'] ?? '' );

			// A probe answers for the locale it is asked about, not for whichever
			// one the operator's own interface happens to be in
			if( $locale !== '' && \Nino\Locales::verifyLocale( $appData, $locale ) === true )
				$appData['./nino/locales/current'] = $locale;

			$configured = \Nino\Modules\Search::configuration( $appData );
			$rows = [];

			foreach( \Nino\Modules\Search::getHits( $appData, $types, $query, self::PROBE_LIMIT ) as $hit ) {

				$element 	= \Nino\Elements::getElement( $appData, $hit['uri'], \Nino\Locales::getCurrentLocale( $appData ) );
				$fields 	= $configured[$hit['type']]['fields'] ?? [];
				$label 		= '';

				// What a person recognises the hit by: its strongest configured
				// field, falling back to the uri when that one is empty
				foreach( $fields as $field )
					if( $label === '' && is_array( $element ) === true && is_scalar( $element[$field] ?? null ) === true )
						$label = (string) $element[$field];

				$rows[] = [
					'uri' 			=> $hit['uri'],
					'type' 			=> $hit['type'],
					'label' 		=> $label !== '' ? mb_substr( $label, 0, 120 ) : $hit['uri'],
					'score' 		=> round( (float) $hit['score'], 3 ),
					'coverage' 	=> round( (float) $hit['coverage'], 2 ),
					// Which priority slots carried a match, as the field names
					// the operator just chose rather than as numbers
					'matched' 	=> array_values( array_intersect_key( $fields, array_flip( $hit['fields'] ) ) ),
				];
			}

			\Nino\Http::ok( $request, [ 'query' => $query, 'hits' => $rows, 'limit' => self::PROBE_LIMIT ] );
		}
	}

}
