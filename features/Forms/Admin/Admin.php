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
	 *	Forms\Admin				The builder: the forms a project has defined, and one
	 *										form's fields on a screen of its own. Two levels, no
	 *										third - the submissions are the kernel's own
	 *										Submissions panel, which reads the same forms this one
	 *										writes and needs nothing from here.
	 *
	 *										What it edits is '/nino/form/forms' in config.php, the
	 *										key \Nino\Form reads. Nothing is stored anywhere else
	 *										and nothing is duplicated: a definition saved here is
	 *										read by the engine on the next request, a definition
	 *										written by hand shows up here, and switching the
	 *										feature off leaves every form working.
	 *
	 *										Validation is \Nino\Form::normalize(), not a copy of
	 *										it - so what the panel accepts is exactly what the
	 *										endpoint accepts, and a rule that changes changes in
	 *										one place. \Nino\Form::problems() asks the same
	 *										routine what it would leave out, so a refused save
	 *										says which field and which control instead of one
	 *										sentence for the whole form; a mail template that is
	 *										not on disk is refused here as well, since a form
	 *										that mails an empty body is worse than one that is
	 *										not saved. Every word a person reads is resolved
	 *										here, in the session language, so the script renders
	 *										what it gets: the same split the Features panel makes.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/forms/manage';

		// A form key as the engine writes one - checked here before anything
		// is read, so a stray value never reaches an error message
		private const string KEY_PATTERN = '/^[a-z][a-z0-9-]*$/';

		// What \Nino\Form::problems() reports, as the fill that says it. A code
		// that is not here is said with the general sentence of its kind: that a
		// field cannot be read, or that the form was not saved
		private const array PROBLEMS = [
			'key'							=> 'key',
			'field'						=> 'field',
			'name'						=> 'name',
			'reserved'				=> 'reserved',
			'duplicate'				=> 'duplicate',
			'type'						=> 'type',
			'options'					=> 'options',
			'fields'					=> 'fields',
			'to'							=> 'to',
			'ownerTemplate'		=> 'ownertpl',
			'userTemplate'		=> 'usertpl',
		];

		// The two mail templates a form names, by the control the script marks
		private const array TEMPLATES = [ 'ownerTemplate' => 'ownertpl', 'userTemplate' => 'usertpl' ];

		public static function actions(): array {
			return [
				'forms/list'			=> [ self::class, 'apiList' ],
				'forms/save'			=> [ self::class, 'apiSave' ],
				'forms/delete'		=> [ self::class, 'apiDelete' ],
				'forms/settings'	=> [ self::class, 'apiSettings' ],
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
			return [ 'forms-list', 'forms-form' ];
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

		// How many forms there are, not how many submissions: the kernel's
		// Submissions panel already carries that number, and two tiles
		// answering the same question is one tile too many
		public static function summary( array &$appData ): array {
			return [ 'value' => count( \Nino\Form::forms( $appData ) ), 'label' => '/_admin/forms/label/forms' ];
		}

		public static function log( string $action, array $data ): string {

			// 'key' is the key a form had before the edit - empty for one being
			// created - so a save is logged under the key it has afterwards
			$key = is_string( $data['form']['key'] ?? null ) === true ? $data['form']['key'] : '';

			if( $key === '' )
				$key = is_string( $data['key'] ?? null ) === true ? $data['key'] : '';

			return match( $action ) {
				'forms/save'			=> 'Save form "'. $key. '"',
				'forms/delete'		=> 'Delete form "'. $key. '"',
				'forms/settings'	=> 'Edit form settings',
				default						=> '',
			};
		}

		/**
		 *	Every form as the engine reads it, what a field may be, the mail
		 *	templates a form can name, and the two things about the
		 *	submissions a project decides. Plus the one state in which a form
		 *	drawn by [form] would post into nothing: the kernel module that
		 *	owns the endpoint switched off
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$counts = [];
			foreach( \Nino\Form::entries( $appData ) as $entry )
				$counts[ (string) ( $entry['form'] ?? '' ) ] = ( $counts[ (string) ( $entry['form'] ?? '' ) ] ?? 0 ) + 1;

			$forms = [];
			foreach( \Nino\Form::forms( $appData ) as $form )
				$forms[] = $form + [ 'entries' => $counts[ $form['key'] ] ?? 0 ];

			\Nino\Http::ok( $request, [
				'forms'			=> $forms,
				'types'			=> \Nino\Form::TYPES,
				'reserved'	=> \Nino\Form::RESERVED,
				'templates'	=> self::_templates( $appData ),
				// True while the project has defined none: the list is showing
				// the contact form the kernel falls back to, not a definition of
				// its own - and saving anything is what first writes the key
				'default'		=> is_array( $appData[ \Nino\Form::FORMS ] ?? null ) === false || $appData[ \Nino\Form::FORMS ] === [],
				'retention'	=> \Nino\Form::retention( $appData ),
				'store'			=> \Nino\Form::stores( $appData ),
				// The endpoint every form posts to is \Nino\Modules\Form's. A
				// project that switched it off has no form endpoint on purpose,
				// and the panel is the only place that would ever say so
				'endpoint'	=> \Nino\Modules\Forms::endpointActive( $appData ),
			] );
		}

		/**
		 *	Create or replace one form. The whole definition is posted and
		 *	written as one: a form is small, and a field-at-a-time api would
		 *	buy nothing but a half-saved form.
		 *
		 *	A definition the engine would only repair is refused instead, and
		 *	the answer says where: 'fields' maps the index of a field in the
		 *	posted list to the sentence, 'controls' to the control of that
		 *	field the sentence belongs to, and 'about' maps a control of the
		 *	form itself (key, to, ownertpl, usertpl) to its sentence. Nothing
		 *	is written
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

			$problems = \Nino\Form::problems( $posted );

			if( $problems !== [] ) {
				self::_refuse( $appData, $request, $problems );
				return;
			}

			$form = \Nino\Form::normalize( $posted );

			if( $form === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/invalid' ) );
				return;
			}

			// A template that is not on disk renders as nothing, and the mail
			// that goes out is an empty one - which nobody sees until a visitor
			// asks why no answer came. The pattern is already checked, so the
			// path is one \Nino\Filesystem resolves inside the project
			$missing = [];

			foreach( self::TEMPLATES as $role => $control )
				if( is_file( \Nino\Filesystem::path( $appData, $form[ $role ]. '.tpl' ) ) === false )
					$missing[ $control ] = self::_say( $appData, '/_admin/forms/problem/missingtpl' );

			if( $missing !== [] ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/fields' ) );
				$request['/nino/http/response']['body']['about'] = $missing;
				return;
			}

			// The list is written whole rather than mutated in place: it is one
			// short array, and the panel always posts the form as it should be
			// afterwards
			$out		= [];
			$found	= false;

			foreach( \Nino\Form::forms( $appData ) as $existing ) {

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
					$request['/nino/http/response']['body']['about'] = [ 'key' => self::_say( $appData, '/_admin/forms/error/duplicate' ) ];
					return;
				}

				$out[] = $existing;
			}

			if( $found === false )
				$out[] = $form;

			if( self::_write( $appData, $out ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'form' => $form ] );
		}

		/**
		 *	Delete one form. Its submissions stay: they are what a person
		 *	asked for, not a property of the definition, and the retention
		 *	window removes them on its own schedule. The Submissions panel
		 *	goes on showing them under the key they were recorded with
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
			foreach( \Nino\Form::forms( $appData ) as $form )
				if( $form['key'] !== $key )
					$out[] = $form;

			// Deleting the last one would leave the key empty, which is what
			// "this project has defined no forms" means - and the engine would
			// answer with its built-in contact form again. Refused rather than
			// done quietly: a project with no form at all is a decision, and
			// removing the module is how it is made
			if( $out === [] ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/last' ) );
				return;
			}

			if( self::_write( $appData, $out ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'deleted' => $key ] );
		}

		/**
		 *	The two things about the submissions a project decides: how many
		 *	months they stay, and whether they are written at all. Both are
		 *	the kernel's own config keys rather than this feature's settings -
		 *	it is the kernel that writes the records, and a project that
		 *	switches the feature off keeps whatever it chose here
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSettings( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data				= \Nino\Admin\Admin::postData();
			$retention	= $data['retention'] ?? null;

			if( is_int( $retention ) === false || $retention < 1 || $retention > 60 ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/retention' ) );
				return;
			}

			$appData[ \Nino\Form::RETENTION ]	= $retention;
			$appData[ \Nino\Form::STORE ]			= ( $data['store'] ?? true ) === true;

			if( \Nino\AppData::writeContentData( $appData, [ \Nino\Form::RETENTION, \Nino\Form::STORE ] ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'retention' => $retention, 'store' => $appData[ \Nino\Form::STORE ] ] );
		}

		/**
		 *	The 400 for a definition \Nino\Form::problems() found something in:
		 *	one general sentence, and every problem as the sentence of the field
		 *	or the control it is about
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		array 		$problems			What \Nino\Form::problems() answered
		 *
		 *	@return 	void
		 */
		private static function _refuse( array &$appData, array &$request, array $problems ): void {

			$fields		= [];
			$controls	= [];
			$about		= [];

			foreach( $problems as $problem ) {

				$code		= (string) $problem['code'];
				$say		= isset( self::PROBLEMS[ $code ] ) === true
					? self::_say( $appData, '/_admin/forms/problem/'. self::PROBLEMS[ $code ] )
					: self::_say( $appData, $problem['field'] !== null ? '/_admin/forms/problem/field' : '/_admin/forms/error/fields' );

				if( $problem['field'] !== null ) {
					// The first thing found in a field is the one said: it is what
					// the person has to change first
					if( isset( $fields[ $problem['field'] ] ) === false ) {
						$fields[ $problem['field'] ]		= $say;
						$controls[ $problem['field'] ]	= match( $code ) {
							'options'	=> 'options',
							'type'		=> 'type',
							default		=> 'name',
						};
					}
					continue;
				}

				$about[ self::TEMPLATES[ $code ] ?? $code ] = $say;
			}

			\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/forms/error/fields' ) );

			$request['/nino/http/response']['body']['fields']		= $fields;
			$request['/nino/http/response']['body']['controls']	= $controls;
			$request['/nino/http/response']['body']['about']		= $about;
		}

		/**
		 *	The mail templates a form can name: the project's own
		 *	/templates/mail-*.tpl, without the header and the footer every one
		 *	of them is wrapped in. Only names \Nino\Form would accept as a
		 *	template path are offered, sorted
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Template paths, eg. '/templates/mail-owner'
		 */
		private static function _templates( array &$appData ): array {

			$templates = [];

			foreach( (array) glob( \Nino\Filesystem::path( $appData, '/templates' ). '/mail-*.tpl' ) as $file ) {

				$name = basename( (string) $file, '.tpl' );

				if( in_array( $name, [ 'mail-header', 'mail-footer' ], true ) === false && preg_match( '/^[a-zA-Z0-9_-]+$/', $name ) === 1 )
					$templates[] = '/templates/'. $name;
			}

			sort( $templates );

			return $templates;
		}

		/**
		 *	Write the whole list of forms into config.php, and into the
		 *	running request with it - so anything reading \Nino\Form::forms()
		 *	after this call sees what was just saved
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$forms				Normalized forms
		 *
		 *	@return 	bool
		 */
		private static function _write( array &$appData, array $forms ): bool {

			$appData[ \Nino\Form::FORMS ] = $forms;

			return \Nino\AppData::writeContentData( $appData, [ \Nino\Form::FORMS ] );
		}

		/**
		 *	The posted form key, '' when it is not one
		 *
		 *	@return 	string
		 */
		private static function _key(): string {

			$key = \Nino\Admin\Admin::postData()['key'] ?? null;

			return is_string( $key ) === true && preg_match( self::KEY_PATTERN, $key ) === 1 ? $key : '';
		}

		/**
		 *	One of this panel's own fills, in the language of whoever is
		 *	looking - the panel phrases its refusals, the script only shows
		 *	them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					A fill key
		 *
		 *	@return 	string
		 */
		private static function _say( array &$appData, string $key ): string {

			return \Nino\Html::renderHtml( $appData, '[['. $key. ']]' );
		}
	}
}
