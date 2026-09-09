<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Nino\Modules\Forms\Admin	The /_admin panel of the Forms feature - see docs/development.md
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Forms {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Forms\Admin				The panel of the Forms feature, three levels deep: the
	 *										forms, one form's fields, and one form's submissions.
	 *										\Nino\Modules\Forms (Forms.php beside this) owns the
	 *										shape of both files; this reads and writes them
	 *										through that class' own normalize(), so a definition
	 *										the panel saves is exactly one the endpoint accepts.
	 *
	 *										Every word a person reads is resolved here, in the
	 *										session language, so the script renders what it gets -
	 *										the same split the Features panel makes. A submission's
	 *										values travel as they are stored, html-escaped (see
	 *										Forms::_record()); the script decodes them into
	 *										textContent, never into markup.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/forms/manage';

		// A form key and a submission id as the runtime class writes them -
		// checked here before anything is read, so a stray value never
		// reaches a path or an error message
		private const string KEY_PATTERN	= '/^[a-z][a-z0-9-]*$/';
		private const string ID_PATTERN		= '/^[0-9a-f]{16}$/';

		public static function actions(): array {
			return [
				'forms/list'					=> [ self::class, 'apiList' ],
				'forms/save'					=> [ self::class, 'apiSave' ],
				'forms/delete'				=> [ self::class, 'apiDelete' ],
				'forms/entries'				=> [ self::class, 'apiEntries' ],
				'forms/entry-delete'	=> [ self::class, 'apiEntryDelete' ],
			];
		}

		// The group is named the way every catalogue feature names one, and
		// the workbench overrides it: a panel whose class lies below
		// \Nino\Features::dir() always lands in the rail's own "features"
		// group, whatever this says (see \Nino\Admin\Panels)
		public static function nav(): array {
			return [ 'forms', '/_admin/nav/forms', 60, 'content' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-form-icon lucide-form"><path d="M4 14h6"/><path d="M4 2h10"/><rect x="4" y="18" width="16" height="4" rx="1"/><rect x="4" y="6" width="16" height="4" rx="1"/></svg>';
		}

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function panes(): array {
			return [ 'forms-list', 'forms-form', 'forms-entries' ];
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function summary( array &$appData ): array {
			return [ 'value' => self::count( $appData ), 'label' => '/_admin/forms/label/submissions' ];
		}

		public static function log( string $action, array $data ): string {

			// 'key' is the key a form had before the edit - empty for one being
			// created - so a save is logged under the key it has afterwards
			$key = is_string( $data['form']['key'] ?? null ) === true ? $data['form']['key'] : '';

			if( $key === '' )
				$key = is_string( $data['key'] ?? null ) === true ? $data['key'] : '';

			return match( $action ) {
				'forms/save'					=> 'Save form "'. $key. '"',
				'forms/delete'				=> 'Delete form "'. $key. '"',
				'forms/entry-delete'	=> 'Delete a submission of form "'. $key. '"',
				default								=> '',
			};
		}

		/**
		 *	Every form with what the list needs: how many submissions it has
		 *	on file, and - the one state in which none of this does anything -
		 *	whether the kernel's own contact form is still switched on
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$forms = [];

			foreach( \Nino\Modules\Forms::forms( $appData ) as $form )
				$forms[] = $form + [ 'entries' => count( self::entries( $appData, $form['key'] ) ) ];

			\Nino\Http::ok( $request, [
				'forms'			=> $forms,
				'types'			=> \Nino\Modules\Forms::TYPES,
				// True while nothing has ever been saved: the list is showing
				// the built-in default, not a definition on disk
				'default'		=> \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Forms::DEFINITIONS, [] ) === [],
				'blocked'		=> \Nino\Modules\Forms::kernelFormActive( $appData ),
			] );
		}

		/**
		 *	Create or replace one form. The whole definition is posted and
		 *	written as one: a form is small, and a field-at-a-time api would
		 *	buy nothing but a half-saved form
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data		= \Nino\Admin\Admin::postData();
			$posted	= is_array( $data['form'] ?? null ) === true ? $data['form'] : [];
			$was		= is_string( $data['key'] ?? null ) === true ? $data['key'] : '';
			$form		= \Nino\Modules\Forms::normalize( $posted );

			if( $form === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/invalid' ) );
				return;
			}

			// The definitions file is written whole rather than mutated in
			// place: it is one short list, and the panel always posts the
			// form as it should be afterwards
			$forms 	= \Nino\Modules\Forms::forms( $appData );
			$out		= [];
			$found	= false;

			foreach( $forms as $existing ) {

				// The one being replaced, found by the key it had before this
				// edit - so a rename stays one entry rather than becoming two.
				// Only ever for an edit: a new form ('' as the previous key)
				// replaces nothing, or creating one under a key that is taken
				// would silently delete the form already using it
				if( $was !== '' && $existing['key'] === $was ) {
					$out[] 	= $form;
					$found 	= true;
					continue;
				}

				if( $existing['key'] === $form['key'] ) {
					\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/duplicate' ) );
					return;
				}

				$out[] = $existing;
			}

			if( $found === false )
				$out[] = $form;

			if( \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Forms::DEFINITIONS, $out ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'form' => $form ] );
		}

		/**
		 *	Delete one form. Its submissions stay on disk: they are what a
		 *	person asked for, not a property of the definition, and the
		 *	retention window removes them on its own schedule
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key = self::_key();

			if( $key === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/key' ) );
				return;
			}

			$out = [];
			foreach( \Nino\Modules\Forms::forms( $appData ) as $form )
				if( $form['key'] !== $key )
					$out[] = $form;

			if( \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Forms::DEFINITIONS, $out ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'deleted' => $key ] );
		}

		/**
		 *	One form's submissions, most recent first, with the field names
		 *	the panel builds its columns from - the union of what the
		 *	definition declares now and what the stored entries actually
		 *	carry, so a field that was renamed or removed still shows its
		 *	answers instead of dropping them silently
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiEntries( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key = self::_key();

			if( $key === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/key' ) );
				return;
			}

			$entries	= self::entries( $appData, $key );
			$form			= \Nino\Modules\Forms::form( $appData, $key );
			$columns	= [];

			foreach( $form['fields'] ?? [] as $field )
				$columns[$field['name']] = \Nino\Html::renderHtml( $appData, $field['label'] );

			foreach( $entries as $entry )
				foreach( array_keys( (array) ( $entry['fields'] ?? [] ) ) as $name )
					if( isset( $columns[$name] ) === false )
						$columns[$name] = $name;

			\Nino\Http::ok( $request, [
				'key'			=> $key,
				'name'		=> $form['name'] ?? $key,
				'columns'	=> $columns,
				'entries'	=> array_reverse( $entries ),
			] );
		}

		/**
		 *	Delete one submission, found by the id it was recorded with
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiEntryDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data	= \Nino\Admin\Admin::postData();
			$key	= self::_key();
			$id		= is_string( $data['id'] ?? null ) === true ? $data['id'] : '';

			if( $key === '' || preg_match( self::ID_PATTERN, $id ) !== 1 ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/key' ) );
				return;
			}

			$removed = false;

			foreach( self::_files( $appData, $key ) as $file ) {

				\Nino\Filesystem::mutate( $appData, \Nino\Modules\Forms::DIR. '/'. $file, function( array $entries ) use ( $id, &$removed ): ?array {

					foreach( $entries as $entryKey => $entry )
						if( (string) ( $entry['id'] ?? '' ) === $id ) {
							unset( $entries[$entryKey] );
							$removed = true;
							return array_values( $entries );
						}

					return null;
				} );

				if( $removed === true )
					break;
			}

			if( $removed === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/entry' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'deleted' => $id ] );
		}

		/**
		 *	How many submissions are on file across every form - shared by
		 *	summary() and the Dashboard tile behind it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function count( array &$appData ): int {

			$total = 0;

			foreach( \Nino\Modules\Forms::forms( $appData ) as $form )
				$total += count( self::entries( $appData, $form['key'] ) );

			return $total;
		}

		/**
		 *	One form's submissions within the retention window, oldest first
		 *	(as stored). Public because the list needs the count and the
		 *	entries screen the rows, and reading them twice is one glob
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					A form key
		 *
		 *	@return 	array
		 */
		public static function entries( array &$appData, string $key ): array {

			$entries = [];

			foreach( self::_files( $appData, $key ) as $file )
				foreach( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Forms::DIR. '/'. $file, [] ) as $entry )
					if( is_array( $entry ) === true )
						$entries[] = $entry;

			return $entries;
		}

		/**
		 *	The month files of one form, oldest first - "<key>.<Y-m>.php"
		 *	and nothing else, so definitions.php and rate.php beside them
		 *	are never read as submissions
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					A form key, already checked
		 *
		 *	@return 	array										Basenames
		 */
		private static function _files( array &$appData, string $key ): array {

			if( preg_match( self::KEY_PATTERN, $key ) !== 1 )
				return [];

			$dir 	 = \Nino\Filesystem::path( $appData, \Nino\Modules\Forms::DIR );
			$files = [];

			foreach( glob( $dir. '/'. $key. '.*.php' ) ?: [] as $file ) {

				$month = substr( basename( $file, '.php' ), strlen( $key ) + 1 );

				if( preg_match( '/^\d{4}-\d{2}$/', $month ) === 1 )
					$files[] = basename( $file );
			}

			sort( $files );

			return $files;
		}

		/**
		 *	The posted form key, '' for anything that is not one
		 *
		 *	@return 	string
		 */
		private static function _key(): string {

			$data	= \Nino\Admin\Admin::postData();
			$key 	= is_string( $data['key'] ?? null ) === true ? $data['key'] : '';

			return preg_match( self::KEY_PATTERN, $key ) === 1 ? $key : '';
		}

		/**
		 *	One of the panel's own messages in the session language - the
		 *	same shape the Features panel's _say() has: a fill resolved
		 *	here, so the script only ever renders what it is handed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					Fill key
		 *
		 *	@return 	string
		 */
		private static function _say( array &$appData, string $key ): string {
			return \Nino\Html::renderHtml( $appData, '[['. $key. ']]' );
		}
	}

}
