<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Templates\Content	Native-locale quick fill and schema-safe Element Type creation for a section
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Templates {

	/**
	 *	Native-locale quick fill and schema-safe Element-Type creation. Full
	 *	element CRUD remains the workbench's own Elements panel and is linked
	 *	directly from the selected section.
	 */
	class Content {

		// What may be read and bound: any text key, the ones the system writes -
		// /_nino/webpage<uri>/... - included, the workbench's own never. What may
		// be written is narrower and is the document's, see _owned()
		private const string KEY_PATTERN = '#^/(?:_nino/)?[A-Za-z0-9][A-Za-z0-9_./-]*$#D';
		private const string WORD = '[a-z0-9]+(?:-[a-z0-9]+)*';

		public static function actions(): array {
			return [
				'content/keys'		=> [ self::class, 'apiKeys' ],
				'content/fields'		=> [ self::class, 'apiFields' ],
				'content/save'			=> [ self::class, 'apiSave' ],
				'content/types'			=> [ self::class, 'apiTypes' ],
				'content/type-create'	=> [ self::class, 'apiCreateType' ],
				'content/images'		=> [ self::class, 'apiImages' ],
				'content/image-create'	=> [ self::class, 'apiCreateImage' ],
			];
		}

		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'content/save'				=> 'Saved native content of a template section',
				'content/type-create'	=> 'Created element type "'. (string) ( $data['uri'] ?? '' ). '" for a template section',
				'content/image-create'	=> 'Created image slot "'. (string) ( $data['uri'] ?? '' ). '" for a template section',
				default	=> '',
			};
		}

		public static function apiKeys( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$native = \Nino\Locales::getNativeLocale( $appData );
			$entries = [];
			foreach( \Nino\Text::entries( $appData, true ) as $entry ) {
				$key = (string) ( $entry['key'] ?? '' );
				if( self::_validKey( $key ) === false )
					continue;
				$entries[] = [
					'key' => $key,
					'global' => ( $entry['global'] ?? false ) === true,
					'blacklisted' => ( $entry['blacklisted'] ?? false ) === true,
					'value' => (string) ( ( $entry['global'] ?? false ) === true
						? ( $entry['values']['*'] ?? '' )
						: ( $entry['values'][$native] ?? '' ) ),
				];
			}

			\Nino\Http::ok( $request, [ 'nativeLocale' => $native, 'entries' => $entries ] );
		}

		public static function apiFields( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$data = Admin::postData();
			$category = self::_category( $appData, $request, $data );
			if( $category === null )
				return;

			$keys = array_values( array_unique( array_map( 'strval', (array) ( $data['keys'] ?? [] ) ) ) );
			if( count( $keys ) > 100 ) {
				\Nino\Http::fail( $request, 400, 'too many text keys' );
				return;
			}

			$native = \Nino\Locales::getNativeLocale( $appData );
			$fields = [];

			/*	The catalogue once, indexed by key. \Nino\Text::entry() builds
				the whole of it - every key of /text/global.php and of every
				locale file, each one measured and looked at - and then walks it
				to find the one asked for, so asking per field did that per
				field. A page of forty fields against a catalogue of a thousand
				keys: 79.56 ms against 2.07	*/
			$catalogue = [];
			foreach( \Nino\Text::entries( $appData ) as $textEntry )
				$catalogue[ $textEntry['key'] ] = $textEntry;

			foreach( $keys as $key ) {
				if( self::_validKey( $key ) === false ) {
					\Nino\Http::fail( $request, 400, 'invalid textfill key' );
					return;
				}

				$entry = $catalogue[$key] ?? null;
				$fields[] = [
					'key' => $key,
					'exists' => $entry !== null,
					// A key of another template, of the project or of the system is
					// read here and edited in the Text panel
					'writable' => self::_owned( $key, $category ),
					'global' => ( $entry['global'] ?? false ) === true,
					'value' => $entry === null ? '' : (string) ( $entry['global'] === true ? ( $entry['values']['*'] ?? '' ) : ( $entry['values'][$native] ?? '' ) ),
				];
			}

			\Nino\Http::ok( $request, [ 'nativeLocale' => $native, 'fields' => $fields ] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$data = Admin::postData();
			$category = self::_category( $appData, $request, $data );
			if( $category === null )
				return;

			$items = is_array( $data['items'] ?? null ) ? $data['items'] : [];
			if( count( $items ) > 100 ) {
				\Nino\Http::fail( $request, 400, 'too many text values' );
				return;
			}

			$native = \Nino\Locales::getNativeLocale( $appData );
			$missing = [];
			$clean = [];

			// Same as apiFields() above: one catalogue, indexed, rather than
			// one rebuild of it per posted value
			$catalogue = [];
			foreach( \Nino\Text::entries( $appData ) as $textEntry )
				$catalogue[ $textEntry['key'] ] = $textEntry;

			foreach( $items as $item ) {
				if( is_array( $item ) === false ) {
					\Nino\Http::fail( $request, 400, 'invalid text value' );
					return;
				}
				$key = (string) ( $item['key'] ?? '' );
				$value = (string) ( $item['value'] ?? '' );

				if( self::_validKey( $key ) === false ) {
					\Nino\Http::fail( $request, 400, 'invalid textfill key' );
					return;
				}

				// The document decides what is written, not the request: its own keys
				// and nothing else. A binding to a word of the project, of the
				// system or of another template stays what it is
				if( self::_owned( $key, $category ) === false ) {
					\Nino\Http::fail( $request, 400, 'only the text keys of this page template can be saved here - edit '. $key. ' in the Text panel' );
					return;
				}

				$entry = $catalogue[$key] ?? null;
				if( $entry === null && ( $item['create'] ?? false ) !== true ) {
					\Nino\Http::fail( $request, 400, 'an existing textfill binding no longer exists' );
					return;
				}
				if( $entry === null )
					$missing['[['. $key. ']]'] = '';

				$clean[] = [
					'key' => $key,
					'locale' => ( $entry['global'] ?? false ) === true ? '*' : $native,
					'value' => $value,
				];
			}

			if( $missing !== [] ) {
				$written = \Nino\Filesystem::mutate( $appData, '/text/'. $native. '.php', function( mixed $content ) use ( $missing ): array {
					return array_merge( is_array( $content ) ? $content : [], $missing );
				} );
				if( $written === false ) {
					\Nino\Http::fail( $request, 500, 'could not create native text keys' );
					return;
				}
			}

			// Without the blacklist: a value a unit keeps up to date is no value to save from here
			$results = \Nino\Text::saveBatch( $appData, $clean, false );
			if( array_filter( $results, fn( array $result ): bool => ( $result['ok'] ?? false ) !== true ) !== [] ) {
				\Nino\Http::fail( $request, 500, 'could not save every native text value' );
				return;
			}
			\Nino\Http::ok( $request, [ 'nativeLocale' => $native, 'results' => $results ] );
		}

		public static function apiTypes( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;
			\Nino\Modules\Elements\Admin::apiTypes( $appData, $request );
		}

		public static function apiImages( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;
			\Nino\Modules\Images\Slots::apiList( $appData, $request );
		}

		public static function apiCreateType( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$data = Admin::postData();
			$preset = Library::preset( (string) ( $data['preset'] ?? '' ) );
			// A collection is an Elements area of a preset, and its model is the
			// one that area's manifest declares - there is no other place a
			// model could come from
			$area = is_array( $preset )
				? AreaComposer::collectionDefinition( $preset, (string) ( $data['area'] ?? '' ) )
				: null;
			$uri = (string) ( $data['uri'] ?? '' );

			if( $area === null || preg_match( '/^[a-z][a-z0-9_-]*$/', $uri ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'invalid preset area or element type' );
				return;
			}

			$_POST['data'] = json_encode( [
				'uri' => $uri,
				'title' => trim( (string) ( $data['title'] ?? '' ) ) ?: $area['typeTitle'],
				'model' => $area['model'],
			] );

			\Nino\Modules\Elements\Types::apiCreate( $appData, $request );
		}

		public static function apiCreateImage( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$data = Admin::postData();
			$category = self::_category( $appData, $request, $data );
			if( $category === null )
				return;

			$preset = Library::preset( (string) ( $data['preset'] ?? '' ) );
			$uri = (string) ( $data['uri'] ?? '' );
			$slot = (string) ( $data['slot'] ?? '' );
			$component = (string) ( $data['component'] ?? '' );
			// Every preset in the library is an Area preset (Library::presets()
			// skips anything else), so the slot is either the section background
			// or a component's own image property
			$definition = $preset === null
				? null
				: ( $slot === 'background'
					? [ 'label' => 'Background image', 'width' => 1920, 'height' => 1080 ]
					: AreaComposer::imageDefinition(
						$preset,
						(string) ( $data['area'] ?? '' ),
						$component,
						(string) ( $data['property'] ?? '' )
					) );
			$expectedSuffix = $slot === 'background' ? 'background' : $component;

			if( $definition === null || self::_owned( $uri, $category ) === false || str_ends_with( $uri, '/'. $expectedSuffix ) === false ) {
				\Nino\Http::fail( $request, 400, 'invalid page image slot' );
				return;
			}

			$_POST['data'] = json_encode( [
				'uri' => $uri,
				'label' => trim( (string) ( $data['label'] ?? '' ) ) ?: $definition['label'],
				'width' => $definition['width'],
				'height' => $definition['height'],
			] );

			\Nino\Modules\Images\Slots::apiCreate( $appData, $request );
		}

		/**
		 *	The category of the page template a request is about, which is the
		 *	template's name without .tpl (see \Nino\Modules\Template::category())
		 *	and the one thing the builder may write under: /template/<category>/...
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Request; answered here when there is no category
		 *	@param		array 		$data					What was posted: 'name' is the document's
		 *
		 *	@return 	string|null							null where the request has been answered
		 */
		private static function _category( array &$appData, array &$request, array $data ): ?string {

			$category = Documents::category( $appData, (string) ( $data['name'] ?? '' ) );

			if( $category === null )
				\Nino\Http::fail( $request, 400, 'the page template is unknown, or its name gives no category' );

			return $category;
		}

		/**
		 *	@param		string		$key
		 *	@param		string		$category			The document's
		 *
		 *	@return 	bool									Whether the key is /template/<category>/<part>/<name>
		 */
		private static function _owned( string $key, string $category ): bool {
			return preg_match( '#^/template/'. preg_quote( $category, '#' ). '/'. self::WORD. '/'. self::WORD. '$#D', $key ) === 1;
		}

		private static function _validKey( string $key ): bool {
			return strlen( $key ) <= 240
				&& preg_match( self::KEY_PATTERN, $key ) === 1
				&& str_contains( $key, '..' ) === false
				&& str_contains( $key, '//' ) === false;
		}
	}
}
