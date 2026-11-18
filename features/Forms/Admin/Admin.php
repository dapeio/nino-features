<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Nino\Modules\Forms\Admin	The /_admin panel of the Forms feature - see docs/features.md, "The panel"
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Forms {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Forms\Admin				The panel: the forms a project has defined as a table, and
	 *										one form on a screen of its own with the tabs Fields,
	 *										Delivery and Protection. Two levels, no third - the
	 *										submissions are the kernel's own Submissions panel, which
	 *										reads the same forms this one writes and needs nothing
	 *										from here.
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
	 *										not saved. A refusal is answered with a code, which
	 *										the workbench says in the session language, and
	 *										where it went wrong with the words of what is wrong
	 *										with each field and control - the script looks those
	 *										up in the same language and lays them out.
	 *
	 *										The label of a field and the subject of a form are one
	 *										string in the definition, which is what the kernel
	 *										reads, so the words of each language live in the text
	 *										files under a key of the panel's own,
	 *										/project/form-<key>/..., and the definition holds that
	 *										key as a fill. A label that was typed by hand or is the
	 *										fill of another key (the contact form's
	 *										/template/common/form/...) is shown in every language
	 *										as what it resolves to and stays as it is until a
	 *										person changes a language.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/forms/manage';

		// A form key as the engine writes one - checked here before anything
		// is read, so a stray value never reaches an error message
		private const string KEY_PATTERN = '/^[a-z][a-z0-9-]*$/D';

		// The words of a form in the text files: the namespace of a form, and how long a text may be. The kernel cuts a
		// label and a subject at 200 bytes, so a form key longer than the stem leaves its words to the definition alone
		private const string OWN = '/project/form-';
		private const int MAX_STEM = 60;
		private const int MAX_TEXT = 200;

		// What \Nino\Form::problems() reports, as the word the script looks its
		// sentence up by: /_admin/forms/problem/<word>. A code that is not here is
		// said with the general word of its kind: that a field cannot be read, or
		// that the form cannot be saved as it is
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

		// No group is named here: a panel a feature brought always lands in
		// the rail's own "features" group (see \Nino\Admin\Panels)
		public static function nav(): array {
			return [ 'forms', '/_admin/nav/forms', 60 ];
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
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
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
		 *	Every form as the engine reads it - with the words of its labels and its subject in each
		 *	language -, what a field may be, the mail templates a form can name, what guards a form, and the
		 *	two things about the submissions a project decides. Plus the one state in which a form drawn by
		 *	[form] would post into nothing: the kernel module that owns the endpoint switched off
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

			$locales	= \Nino\Locales::getTextLocales( $appData );
			$forms		= [];

			foreach( \Nino\Form::forms( $appData ) as $form ) {

				// The words of each language, as the page puts them in: a label that is a fill is shown by what it says
				foreach( $form['fields'] as $index => $field )
					$form['fields'][ $index ]['texts'] = self::_resolve( $appData, $field['label'], $locales );

				$form['subjectTexts']	= $form['subject'] === '' ? array_fill_keys( $locales, '' ) : self::_resolve( $appData, $form['subject'], $locales );
				$forms[]							= $form + [ 'entries' => $counts[ $form['key'] ] ?? 0 ];
			}

			\Nino\Http::ok( $request, [
				'forms'			=> $forms,
				'types'			=> \Nino\Form::TYPES,
				'reserved'	=> \Nino\Form::RESERVED,
				'templates'	=> self::_templates( $appData ),
				'locales'		=> $locales,
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
				// What guards a form besides the honeypot, as the settings of the feature say it: read for the tab
				// Protection, which points to the settings and does not edit them
				'guards'		=> [
					'minSeconds'	=> (int) \Nino\Features::setting( $appData, 'forms', 'minSeconds', 3 ),
					'rateLimit'		=> (int) \Nino\Features::setting( $appData, 'forms', 'rateLimit', 10 ),
					'blocked'			=> count( array_filter( (array) \Nino\Features::setting( $appData, 'forms', 'blocklist', [] ), static fn( mixed $word ): bool => trim( (string) $word ) !== '' ) ),
				],
			] );
		}

		/**
		 *	Create or replace one form. The whole definition is posted and
		 *	written as one: a form is small, and a field-at-a-time api would
		 *	buy nothing but a half-saved form.
		 *
		 *	The words of each language of a label and of the subject come with it (`texts`): where a
		 *	person changed a language they are written into the text files under a key of the panel's own
		 *	and the definition holds the fill of that key; where nothing changed the label stays as it was
		 *	posted, so a form of Nino 1 is read and saved without being touched. They are written before
		 *	the definition, so a definition never names a key that is not there.
		 *
		 *	A definition the engine would only repair is refused instead, and
		 *	the answer says where: the code forms_fields and, as its params, three
		 *	maps - 'fields' maps the index of a field in the posted list to the
		 *	word of what is wrong with it, 'controls' to the control of that
		 *	field the word belongs to, and 'about' maps a control of the form
		 *	itself (key, to, ownertpl, usertpl) to its word. Nothing is written
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
				self::_refuse( $request, $problems );
				return;
			}

			// A key that is made or changed here fits a text key without loss, so that no two forms share the words of one; 'new' is the
			// address of the panel's own screen for a form that does not exist yet. A key a site of Nino 1 has and nobody changed stays
			$key = is_string( $posted['key'] ?? null ) === true ? strtolower( trim( $posted['key'] ) ) : '';

			// The exception is for a form that is there: the key of a form of Nino 1 that does not fit stays as it is. A post that only says it
			// was that key makes no form of it
			$exists = $was !== '' && array_filter( \Nino\Form::forms( $appData ), static fn( array $form ): bool => $form['key'] === $was ) !== [];

			if( ( $key !== $was || $exists === false ) && ( self::segment( $key ) === false || $key === 'new' ) ) {
				\Nino\Http::fail( $request, 400, 'the key of a form is lower case words joined by single hyphens', 'forms_fields', [ [], [], [ 'key' => 'key' ] ] );
				return;
			}

			// Nothing that is typed here is a control character: a label, an option, a subject and a name are text in a page, a
			// mail and a header line alike
			$posted = self::_clean( $posted );

			// The words of each language, set apart from the definition: what changed goes into the text files below
			$set		= [];
			$posted	= self::_words( $appData, $posted, is_array( $data['texts'] ?? null ) === true ? $data['texts'] : [], $set, $exists === true ? $was : '' );

			if( is_array( $posted ) === false ) {
				\Nino\Http::fail( $request, 400, 'the words of a language cannot be saved for this form', 'forms_texts' );
				return;
			}

			if( isset( $posted['refused'] ) === true ) {
				self::_refuseLabels( $request, $posted['refused'] );
				return;
			}

			$form = \Nino\Form::normalize( $posted );

			if( $form === null ) {
				\Nino\Http::fail( $request, 400, 'the form needs a key of its own and at least one field', 'forms_invalid' );
				return;
			}

			// A template that is not on disk renders as nothing, and the mail
			// that goes out is an empty one - which nobody sees until a visitor
			// asks why no answer came. The pattern is already checked, so the
			// path is one \Nino\Filesystem resolves inside the project
			$missing = [];

			foreach( self::TEMPLATES as $role => $control )
				if( is_file( \Nino\Filesystem::path( $appData, $form[ $role ]. '.tpl' ) ) === false )
					$missing[ $control ] = 'missingtpl';

			if( $missing !== [] ) {
				\Nino\Http::fail( $request, 400, 'a mail template of the form is not in the project', 'forms_fields', [ [], [], $missing ] );
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
					\Nino\Http::fail( $request, 400, 'another form has that key', 'forms_duplicate', [ [], [], [ 'key' => 'keytaken' ] ] );
					return;
				}

				$out[] = $existing;
			}

			if( $found === false )
				$out[] = $form;

			// The words first: a definition that names a key must find it
			if( $set !== [] && \Nino\Text::put( $appData, $set, [], array_fill_keys( array_keys( $set ), 'plain' ) ) === null ) {
				\Nino\Http::fail( $request, 500, 'the words of the form could not be written' );
				return;
			}

			if( self::_write( $appData, $out ) === false ) {
				\Nino\Http::fail( $request, 500, 'the forms could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'form' => $form ] );
		}

		/**
		 *	Delete one form. Its submissions stay: they are what a person
		 *	asked for, not a property of the definition, and the retention
		 *	window removes them on its own schedule. The Submissions panel
		 *	goes on showing them under the key they were recorded with. The
		 *	words of its labels stay in the text files, where the Text panel
		 *	lists them
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
				\Nino\Http::fail( $request, 400, 'no form has that key', 'forms_key' );
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
				\Nino\Http::fail( $request, 400, 'the last form cannot be deleted', 'forms_last' );
				return;
			}

			if( self::_write( $appData, $out ) === false ) {
				\Nino\Http::fail( $request, 500, 'the forms could not be written' );
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
				\Nino\Http::fail( $request, 400, 'the submissions are kept between 1 and 60 months', 'forms_retention' );
				return;
			}

			$appData[ \Nino\Form::RETENTION ]	= $retention;
			$appData[ \Nino\Form::STORE ]			= ( $data['store'] ?? true ) === true;

			if( self::_writeKeys( $appData, [ \Nino\Form::RETENTION, \Nino\Form::STORE ] ) === false ) {
				\Nino\Http::fail( $request, 500, 'the settings could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'retention' => $retention, 'store' => $appData[ \Nino\Form::STORE ] ] );
		}

		/**
		 *	A text as each language of the site would put it on a page: the fills of the site put in, nothing
		 *	else read - what the engine does with a label and a subject (\Nino\Form::send(), render())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$text					A label or a subject as the definition holds it
		 *	@param		array 		$locales			The languages of the site's texts
		 *
		 *	@return 	array											locale => text
		 */
		private static function _resolve( array &$appData, string $text, array $locales ): array {

			$current	= \Nino\Locales::getCurrentLocale( $appData );
			$texts		= [];

			// As the page puts them in (\Nino\Modules\Forms::componentForm()): the fill of a language, as characters; the text round it as typed
			$put = static function( array $match ) use ( &$appData ): string {
				$word = \Nino\Html::resolveTextfill( $appData, $match[1] );
				return $word === null ? $match[0] : \Nino\Modules\Forms::characters( $word );
			};

			foreach( $locales as $locale ) {
				\Nino\Locales::useLocale( $appData, (string) $locale );
				$texts[ (string) $locale ] = (string) preg_replace_callback( '#\[\[(/[a-zA-Z0-9/_.-]*)\]\]#', $put, $text );
			}

			\Nino\Locales::useLocale( $appData, $current );

			return $texts;
		}

		/**
		 *	The posted definition without control characters in anything a person types: the form's name and
		 *	subject, the label, the name and the options of each field. A tab or a line break is a space
		 *
		 *	@param		array 		$form					As posted
		 *
		 *	@return 	array
		 */
		private static function _clean( array $form ): array {

			$line = static fn( mixed $value ): mixed => is_string( $value ) === true ? trim( (string) preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $value ) ) : $value;

			foreach( [ 'name', 'subject' ] as $key )
				if( isset( $form[ $key ] ) === true )
					$form[ $key ] = $line( $form[ $key ] );

			foreach( (array) ( $form['fields'] ?? [] ) as $index => $field ) {

				if( is_array( $field ) === false )
					continue;

				foreach( [ 'name', 'label' ] as $key )
					if( isset( $field[ $key ] ) === true )
						$field[ $key ] = $line( $field[ $key ] );

				if( is_array( $field['options'] ?? null ) === true )
					$field['options'] = array_map( $line, $field['options'] );

				$form['fields'][ $index ] = $field;
			}

			return $form;
		}

		/**
		 *	Set the words a person changed apart from the definition. For the subject and for each label whose
		 *	texts were posted: unchanged against what the label says now, the definition keeps the label it has;
		 *	changed, the texts go to a key of the panel's own - the one the label already is, or a new one below
		 *	/project/form-<key>/ - and the label becomes the fill of that key
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$form					The posted definition
		 *	@param		array 		$texts				[ 'subject' => locale => text, 'fields' => [ index => locale => text ] ]
		 *	@param		array 		&$set					(reference) key => locale => text, what is to be written
		 *	@param		string		$was					The key the form had, '' for a new one: a form that is renamed takes its words to the stem of its new key
		 *
		 *	@return 	array|null								The definition with its fills; [ 'refused' => ... ] where a text is not allowed; null where the words cannot be kept for this form
		 */
		private static function _words( array &$appData, array $form, array $texts, array &$set, string $was ): ?array {

			$key = is_string( $form['key'] ?? null ) === true ? strtolower( trim( $form['key'] ) ) : '';

			$locales	= \Nino\Locales::getTextLocales( $appData );
			$moved		= [];

			// A form that is renamed takes its words to the stem of the new key: the old stem is free then, and a form made under the old key
			// begins with words of its own. What the old stem held is read as the text files keep it and written as they take it
			if( $was !== '' && $was !== $key && self::segment( $was ) === true && self::segment( $key ) === true ) {

				$old		= self::OWN. $was;
				$held		= \Nino\Text::values( $appData, $old );
				$carry	= static function( string $raw ) use ( $old, $key, $held, $locales, &$set ): string {

					$from = self::_own( $old, $raw );

					if( $from === '' )
						return $raw;

					$to = self::OWN. $key. substr( $from, strlen( $old ) );

					foreach( $locales as $locale )
						$set[ $to ][ (string) $locale ] = \Nino\Modules\Forms::characters( (string) ( $held[ $from ][ $locale ] ?? '' ) );

					return '[['. $to. ']]';
				};

				foreach( (array) ( $form['fields'] ?? [] ) as $index => $field )
					if( is_array( $field ) === true && is_string( $field['label'] ?? null ) === true && ( $new = $carry( trim( $field['label'] ) ) ) !== trim( $field['label'] ) ) {
						$form['fields'][ $index ]['label'] = $new;
						$moved[ $index ] = $set[ substr( $new, 2, -2 ) ];
					}

				if( is_string( $form['subject'] ?? null ) === true && ( $new = $carry( trim( $form['subject'] ) ) ) !== trim( $form['subject'] ) ) {
					$form['subject'] = $new;
					$moved['subject'] = $set[ substr( $new, 2, -2 ) ];
				}
			}

			if( $texts === [] )
				return $form;

			// The key of the form goes into the key of its words as it is, never shortened or joined: two forms never share one. A key that
			// is no segment of a text key (a double or a trailing hyphen, more than MAX_STEM characters) has no words of its own, and a person
			// who changes a language of such a form is told so
			$stem			= self::OWN. $key;
			$usable		= self::segment( $key ) === true;

			$refused	= [];
			$used			= [];

			// The keys of words that any definition already holds, so that a new one never takes one of them: a key of words belongs to one
			// definition. The form that is saved is read from what was posted, the others as they are stored
			foreach( (array) ( $form['fields'] ?? [] ) as $field )
				if( is_array( $field ) === true && is_string( $field['label'] ?? null ) === true && self::_own( $stem, $field['label'] ) !== '' )
					$used[ self::_own( $stem, $field['label'] ) ] = true;

			foreach( \Nino\Form::forms( $appData ) as $other )
				if( $other['key'] !== $was ) {
					foreach( array_merge( array_column( $other['fields'], 'label' ), [ $other['subject'] ] ) as $raw )
						if( self::_own( $stem, (string) $raw ) !== '' )
							$used[ self::_own( $stem, (string) $raw ) ] = true;
				}

			foreach( (array) ( $form['fields'] ?? [] ) as $index => $field ) {

				if( is_array( $field ) === false || is_array( $texts['fields'][ $index ] ?? null ) === false )
					continue;

				$now = self::_map( $texts['fields'][ $index ], $locales );
				$raw = is_string( $field['label'] ?? null ) === true ? trim( $field['label'] ) : '';

				// A text that holds a fill would be read as one on the page: the words of a label are words
				if( count( array_filter( $now, static fn( string $text ): bool => str_contains( $text, '[[' ) === true || str_contains( $text, ']]' ) === true ) ) > 0 ) {
					$refused[ $index ] = 'brackets';
					continue;
				}

				if( count( array_filter( $now, static fn( string $text ): bool => $text !== '' ) ) === 0 || $now === ( $moved[ $index ] ?? self::_resolve( $appData, $raw, $locales ) ) )
					continue;

				if( $usable === false ) {
					$refused[ $index ] = 'textkey';
					continue;
				}

				$own = self::_own( $stem, $raw );

				if( $own === '' ) {

					$name = is_string( $field['name'] ?? null ) === true ? $field['name'] : '';
					$slug = self::_slug( $name, 'field' );
					$own	= $stem. '/label/'. $slug;

					for( $n = 2; isset( $used[ $own ] ) === true; $n++ )
						$own = $stem. '/label/'. $slug. '-'. $n;

					if( \Nino\Text::isGrammarKey( $own ) === false )
						return null;

					$used[ $own ] = true;
				}

				$set[ $own ]						= $now;
				$form['fields'][ $index ]['label']	= '[['. $own. ']]';
			}

			if( $refused !== [] )
				return [ 'refused' => $refused ];

			if( is_array( $texts['subject'] ?? null ) === true ) {

				$now = self::_map( $texts['subject'], $locales );
				$raw = is_string( $form['subject'] ?? null ) === true ? trim( $form['subject'] ) : '';

				if( count( array_filter( $now, static fn( string $text ): bool => str_contains( $text, '[[' ) === true || str_contains( $text, ']]' ) === true ) ) > 0 )
					return [ 'refused' => [ 'subject' => 'brackets' ] ];

				if( count( array_filter( $now, static fn( string $text ): bool => $text !== '' ) ) === 0 ) {

					// Every language emptied: the engine's own subject again
					if( $raw !== '' && self::_own( $stem, $raw ) !== '' )
						$form['subject'] = '';
				}
				else if( $now !== ( $moved['subject'] ?? ( $raw === '' ? array_fill_keys( $locales, '' ) : self::_resolve( $appData, $raw, $locales ) ) ) ) {

					if( $usable === false )
						return [ 'refused' => [ 'subject' => 'textkey' ] ];

					$own = $stem. '/mail/subject';

					$set[ $own ]			= $now;
					$form['subject']	= '[['. $own. ']]';
				}
			}

			return $form;
		}

		/**
		 *	Whether a form key can stand as a segment of a text key without loss: lower case words of letters and digits joined by one
		 *	hyphen, a letter first, at most MAX_STEM characters
		 *
		 *	@param		string		$key
		 *
		 *	@return 	bool
		 */
		public static function segment( string $key ): bool {
			return strlen( $key ) <= self::MAX_STEM && preg_match( '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/D', $key ) === 1;
		}

		/**
		 *	The key of the panel's own that a label or a subject is, '' for any other text: exactly one fill, of a key below this form's
		 *	namespace
		 *
		 *	@param		string		$stem					/project/form-<key>
		 *	@param		string		$raw					As the definition holds it
		 *
		 *	@return 	string
		 */
		private static function _own( string $stem, string $raw ): string {
			return preg_match( '#^\[\[('. preg_quote( $stem, '#' ). '/[a-z0-9-]+/[a-z0-9-]+)\]\]$#D', $raw, $match ) === 1 ? $match[1] : '';
		}

		/**
		 *	The words a person posted for each language, in the form the text files take them: only the languages of the site, plain
		 *	text, without control characters, at most MAX_TEXT bytes
		 *
		 *	@param		array 		$posted				locale => text, as posted
		 *	@param		array 		$locales			The languages of the site's texts
		 *
		 *	@return 	array											locale => text for every language of the site
		 */
		private static function _map( array $posted, array $locales ): array {

			$map = [];

			foreach( $locales as $locale ) {

				$text = is_string( $posted[ $locale ] ?? null ) === true ? $posted[ $locale ] : '';

				$map[ (string) $locale ] = mb_strcut( trim( (string) preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $text ) ), 0, self::MAX_TEXT, 'UTF-8' );
			}

			return $map;
		}

		/**
		 *	A name as a segment of a text key: lower case letters and digits, words joined by one hyphen
		 *
		 *	@param		string		$text
		 *	@param		string		$fallback			What stands where nothing of $text is left
		 *
		 *	@return 	string
		 */
		private static function _slug( string $text, string $fallback ): string {

			$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $text ) ), '-' );
			$slug = rtrim( substr( $slug, 0, 40 ), '-' );

			return $slug === '' ? $fallback : $slug;
		}

		/**
		 *	The 400 for a definition \Nino\Form::problems() found something in: the
		 *	code forms_fields, and every problem as the word of the field or the
		 *	control it is about - the three maps of apiSave()
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		array 		$problems			What \Nino\Form::problems() answered
		 *
		 *	@return 	void
		 */
		private static function _refuse( array &$request, array $problems ): void {

			$fields		= [];
			$controls	= [];
			$about		= [];

			foreach( $problems as $problem ) {

				$code		= (string) $problem['code'];
				$word		= self::PROBLEMS[ $code ] ?? ( $problem['field'] !== null ? 'field' : 'form' );

				if( $problem['field'] !== null ) {
					// The first thing found in a field is the one said: it is what
					// the person has to change first
					if( isset( $fields[ $problem['field'] ] ) === false ) {
						$fields[ $problem['field'] ]		= $word;
						$controls[ $problem['field'] ]	= match( $code ) {
							'options'	=> 'options',
							'type'		=> 'type',
							default		=> 'name',
						};
					}
					continue;
				}

				$about[ self::TEMPLATES[ $code ] ?? $code ] = $word;
			}

			\Nino\Http::fail( $request, 400, 'the form could not be saved as it is - see the marked fields', 'forms_fields', [ $fields, $controls, $about ] );
		}

		/**
		 *	The 400 for words that hold a fill: the label of the field is marked, the subject is a control of the form
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		array 		$refused			index of a field or 'subject' => word
		 *
		 *	@return 	void
		 */
		private static function _refuseLabels( array &$request, array $refused ): void {

			$fields		= [];
			$controls	= [];
			$about		= [];

			foreach( $refused as $where => $word )
				if( $where === 'subject' )
					$about['subject'] = $word;
				else {
					$fields[ $where ]		= $word;
					$controls[ $where ]	= 'label';
				}

			\Nino\Http::fail( $request, 400, 'the form could not be saved as it is - see the marked fields', 'forms_fields', [ $fields, $controls, $about ] );
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

				if( in_array( $name, [ 'mail-header', 'mail-footer' ], true ) === false && preg_match( '/^[a-zA-Z0-9_-]+$/D', $name ) === 1 )
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

			return self::_writeKeys( $appData, [ \Nino\Form::FORMS ] );
		}

		/**
		 *	Write top-level keys of the running request into config.php
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$keys					The keys
		 *
		 *	@return 	bool
		 */
		private static function _writeKeys( array &$appData, array $keys ): bool {
			return \Nino\AppData::writeContentData( $appData, $keys );
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
	}
}
