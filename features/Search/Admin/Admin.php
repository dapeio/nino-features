<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Search\Admin		The /_admin panel of the Search feature
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Search {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Search\Admin			The "Search" panel. Three tabs and a view:
	 *
	 *										  Index		one row per element type the project has, with
	 *														both counts and its state, and the buttons that
	 *														rebuild an index
	 *										  Types		the same rows with the fields their index holds;
	 *														a type opens a view of its own with a checklist
	 *														of the fields of its model, the order they are
	 *														ticked in being their priority
	 *										  Try it		a query, and the hits with their scores
	 *
	 *										The last of those is the one that earns its keep: this
	 *										is a ranking feature, and a ranking is invisible. Move
	 *										a field to the front and the order changes on screen,
	 *										in the same panel, without a page to test it on.
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

		/*	No group is named here: \Nino\Admin\Panels puts every panel a feature
			brought into 'features' whatever it would name, so granting that one
			group stays a bounded grant */
		public static function nav(): array {
			return [ 'search', '/_admin/nav/search', 30 ];
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

		// The sign of the rail entry: Lucide search, geometry and no view
		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.3-4.3"/><circle cx="11" cy="11" r="8"/></svg>';
		}

		// The list with its three tabs, and the fields of one type: two levels of the pane, one shown at a time
		public static function panes(): array {
			return [ 'search-list', 'search-type' ];
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'search/createindex'	=> 'Rebuild Search Index',
				// The type as the engine reads a name - what a person posted is never written into the log as it is, and a probe, whose words are a visitor's kind of input, is not logged at all
				'search/save'					=> 'Configure Search Index ('. ( \Nino\Modules\Search::typeUri( $data['type'] ?? null ) ?? '?' ). ')',
				default	=> '',
			};
		}

		/**
		 *	Everything the screens draw
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
			// Search's own reading of a type name, never a cast: an array in
			// the posted json raises "Array to string conversion", which the
			// runtime treats as fatal - a 500 where the 400 below is the answer
			$typeUri 	= \Nino\Modules\Search::typeUri( $data['type'] ?? null );
			$posted 	= $data['fields'] ?? [];

			if( $typeUri === null ) {
				\Nino\Http::fail( $request, 400, 'not an element type name', 'search_type' );
				return;
			}

			if( \Nino\Filesystem::fileExists( $appData, '/elements/'. ltrim( $typeUri, '/' ). '.php' ) === false ) {
				\Nino\Http::fail( $request, 404, 'there is no element type "'. $typeUri. '"', 'search_notype', [ $typeUri ] );
				return;
			}

			if( is_array( $posted ) === false ) {
				\Nino\Http::fail( $request, 400, 'no fields posted', 'search_fields' );
				return;
			}

			$model 	= \Nino\Elements::getElementModel( $appData, $typeUri );
			$fields = [];

			foreach( $posted as $priority => $field ) {

				$priority = is_int( $priority ) === true ? $priority : (int) $priority;

				// A slot holds a name or nothing: a list or a number in one is
				// no field, and cast it would raise "Array to string conversion"
				if( is_string( $field ) === false ) {
					\Nino\Http::fail( $request, 400, 'a field is not a name', 'search_fields' );
					return;
				}

				$field = trim( $field );

				if( $field === '' )
					continue;

				if( isset( \Nino\Modules\Search::WEIGHTS[$priority] ) === false ) {
					\Nino\Http::fail( $request, 400, 'priority "'. $priority. '" is not 0, 1, 2 or 3', 'search_priority', [ $priority ] );
					return;
				}

				// The Element's address is the one name that is no field of the
				// model and is taken anyway - see Search::URI_FIELD
				if( $field !== \Nino\Modules\Search::URI_FIELD ) {

					if( isset( $model[$field] ) === false ) {
						\Nino\Http::fail( $request, 400, 'the model of "'. $typeUri. '" has no field "'. $field. '"', 'search_field', [ $typeUri, $field ] );
						return;
					}

					if( in_array( (string) ( $model[$field]['type'] ?? 'string' ), \Nino\Modules\Search::INDEXABLE, true ) === false ) {
						\Nino\Http::fail( $request, 400, 'the field "'. $field. '" carries no text to search', 'search_text', [ $field ] );
						return;
					}
				}

				$fields[$priority] = $field;
			}

			ksort( $fields );

			$config = is_array( $appData[self::CONFIG] ?? null ) === true ? $appData[self::CONFIG] : [];

			// Both spellings are the same type at the configuration boundary,
			// so a save must not leave the other one behind to shadow it
			unset( $config[$typeUri], $config[ltrim( $typeUri, '/' )] );

			$removed = false;

			if( $fields === [] )
				$removed = \Nino\Modules\Search::removeIndex( $appData, $typeUri );
			else
				$config[$typeUri] = $fields;

			ksort( $config );
			$appData[self::CONFIG] = $config;

			// A write that cannot be made ends the request itself (\Nino\Runtime::fail()), so there is no answer to check
			\Nino\AppData::writeContentData( $appData, [ self::CONFIG ] );

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

			$only 		= \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'type' );
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
			$query 	= is_string( $data['query'] ?? null ) === true ? $data['query'] : '';
			$types 	= is_array( $data['types'] ?? null ) === true ? array_values( $data['types'] ) : [];
			$locale = is_string( $data['locale'] ?? null ) === true ? $data['locale'] : '';

			// A probe answers for the locale it is asked about, not for whichever
			// one the operator's own interface happens to be in
			if( $locale !== '' && \Nino\Locales::verifyLocale( $appData, $locale ) === true )
				$appData['./nino/locales/current'] = $locale;

			$configured = \Nino\Modules\Search::configuration( $appData );
			$rows = [];

			foreach( \Nino\Modules\Search::getHits( $appData, $types, $query, self::PROBE_LIMIT ) as $hit ) {

				// The element as the page of the language shows it - what the index holds
				$element 	= \Nino\Elements::getElementWithFallback( $appData, $hit['uri'], \Nino\Locales::getCurrentLocale( $appData ) );
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
