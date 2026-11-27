<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Clients;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Settings;
	use Nino\Modules\Crm\Values;

	/**
	 *	Api		The actions of the documents behind the endpoint of the CRM (POST /crm/api, action crm-documents/<name>): the documents (the list with its filters, the draft with its lines, finalising, the steps of a finalised document, the
	 *						offer that becomes an invoice and the invoice that is taken back by a credit note; the contract, the privacy document and the information document without lines), what the screens draw them with (the templates of Pdf, the
	 *						items of the catalogue, the figures) and the note of a payment reminder. It validates what it is posted and answers the failures a person can cause with codes, whose sentences are the surface's (error.<code>); the rules of
	 *						the data are the classes of the package's, the storage the core's. A database failure is answered 500 with the code crm_database.
	 *
	 *						A finalised document is the books: the only actions that write one are the step of its state (sent, paid, accepted, declined), the change of its project, its internal notes and the note of a reminder - what a
	 *						finalised document says (its number, its date, its lines, its amounts, its client, its PDF and its hash) no action changes, and no action deletes it. save answers 409 crm_documents_fixed for anything but the notes.
	 *						Every action asks the permission of the CRM itself, on top of what the endpoint asks.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		// What the model refuses with, as the status and the code a person is answered. A kind that stands for a field names it
		private const array REFUSALS = [
			'unknown'  => [ 404, 'crm_documents_unknown', 'id' ],
			'fixed'    => [ 409, 'crm_documents_fixed', '' ],
			'state'    => [ 409, 'crm_documents_state', '' ],
			'category' => [ 400, 'crm_documents_category', 'category' ],
			'client'   => [ 400, 'crm_documents_client', 'client_id' ],
			'inactive' => [ 409, 'crm_documents_inactive', 'client_id' ],
			'unready'  => [ 409, 'crm_documents_unready', 'client_id' ],
			'period'   => [ 400, 'crm_documents_period', 'period_from' ],
			'reverse'  => [ 409, 'crm_documents_reverse', 'reverse_charge' ],
			'lines'    => [ 400, 'crm_documents_lines', 'lines' ],
			'total'    => [ 400, 'crm_documents_total', 'lines' ],
			'pdf'      => [ 409, 'crm_documents_pdf', '' ],
			'template' => [ 409, 'crm_documents_template', 'template_key' ],
			'empty'    => [ 409, 'crm_documents_empty', 'lines' ],
			'gross'    => [ 409, 'crm_documents_gross', 'lines' ],
			'credit_parent' => [ 409, 'crm_documents_credit_parent', '' ],
			'credit_lines'  => [ 400, 'crm_documents_credit_lines', 'lines' ],
			'credit_over'   => [ 409, 'crm_documents_credit_over', 'lines' ],
			'daterange' => [ 400, 'crm_documents_date_range', 'date' ],
			'number'   => [ 409, 'crm_documents_number', '' ],
			'render'   => [ 500, 'crm_documents_render', '' ],
			'words'    => [ 409, 'crm_documents_words', '' ],
			'markup'   => [ 409, 'crm_documents_markup', '' ],
			'write'    => [ 500, 'crm_documents_write', '' ],
			'nothing'  => [ 409, 'crm_documents_restore_nothing', '' ],
			'differs'  => [ 409, 'crm_documents_restore_differs', '' ],
			'foreign'  => [ 409, 'crm_documents_restore_foreign', '' ],
			'offer'    => [ 400, 'crm_documents_offer', 'parent_id' ],
			'portal'   => [ 409, 'crm_documents_portal_off', '' ],
			'project'  => [ 400, 'crm_documents_project', 'project' ],
			'project_client' => [ 409, 'crm_documents_project_foreign', 'project' ],
		];

		// The fields of a draft a post may carry
		private const array FIELDS = [ 'date', 'due_date', 'valid_until', 'period_from', 'period_to', 'template_key', 'inputs', 'reverse_charge', 'lines', 'title', 'project', 'buyer_reference' ];

		/**
		 *	Enter the actions of the package
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool											Whether the endpoint took them
		 */
		public static function register( array &$appData ): bool {

			return Endpoint::add( $appData, [
				'crm-documents/list'       => [ self::class, 'apiList' ],
				'crm-documents/get'        => [ self::class, 'apiGet' ],
				'crm-documents/save'       => [ self::class, 'apiSave' ],
				'crm-documents/remove'     => [ self::class, 'apiRemove' ],
				'crm-documents/project'    => [ self::class, 'apiProject' ],
				'crm-documents/finalise'   => [ self::class, 'apiFinalise' ],
				'crm-documents/state'      => [ self::class, 'apiState' ],
				'crm-documents/from-offer' => [ self::class, 'apiFromOffer' ],
				'crm-documents/credit'     => [ self::class, 'apiCredit' ],
				'crm-documents/copy'       => [ self::class, 'apiCopy' ],
				'crm-documents/figures'    => [ self::class, 'apiFigures' ],
				'crm-documents/templates'  => [ self::class, 'apiTemplates' ],
				'crm-documents/items'      => [ self::class, 'apiItems' ],
				'crm-documents/remind'     => [ self::class, 'apiRemind' ],
				'crm-documents/restore-file' => [ self::class, 'apiRestoreFile' ],
			], [ 'package' => 'crm-documents' ] );
		}

		/**
		 *	The documents by the filters, the clients and years to filter by, the settings the screen draws with, the active items of the catalogue a line is filled from and what the export of the list can do (export: whether the ZIP
		 *	can be made, for how many documents, and how many documents of the year of the filter have a PDF file)
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$data = Values::posted();
			$filter = [];

			foreach( [ 'category', 'state' ] as $field )
				$filter[$field] = Values::string( $data, $field );

			foreach( [ 'year' => 'year', 'client' => 'client_id' ] as $posted => $key ) {

				$value = Values::string( $data, $posted );

				if( $value !== '' && preg_match( '/^[0-9]{1,10}$/D', $value ) !== 1 ) {
					\Nino\Http::fail( $request, 400, 'the filter is not valid', 'crm_documents_format', [], $posted );
					return;
				}

				if( $value !== '' )
					$filter[$key] = (int) $value;
			}

			$project = Values::string( $data, 'project' );

			if( $project !== '' && preg_match( Projects::ID, $project ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'the filter is not valid', 'crm_documents_format', [], 'project' );
				return;
			}

			if( $project !== '' )
				$filter['project'] = $project;

			Endpoint::run( $request, static function() use ( &$appData, &$request, $filter ): void {

				\Nino\Http::ok( $request, [
					'documents' => Documents::list( $appData, $filter ),
					'years'     => Documents::years( $appData ),
					'clients'   => self::_clients( $appData ),
					'settings'  => self::_settings( $appData ),
					'items'     => self::_catalogue( $appData ),
					'offers'    => self::_offers( $appData ),
					'export'    => [ 'zip' => Export::zipAvailable(), 'limit' => Export::MAX_FILES, 'files' => isset( $filter['year'] ) === true ? Figures::exportable( $appData, $filter['year'] ) : 0 ],
				] );
			} );
		}

		/**
		 *	The figures of the block of the figures on the first screen: the last twelve months, the five clients that were billed most this year and the offers of the year (see Figures::figures()), with the settings the amounts are written by
		 */
		public static function apiFigures( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {
				\Nino\Http::ok( $request, Figures::figures( $appData ) + [ 'settings' => self::_settings( $appData ) ] );
			} );
		}

		/**
		 *	A draft from a document of any state
		 */
		public static function apiCopy( array &$appData, array &$request ): void {
			self::_derived( $appData, $request, 'Copied document %s to a draft', [ Finalise::class, 'copy' ] );
		}

		/**
		 *	Note that the accounts of the client of an invoice that is open or sent were reminded to pay it - overdue or not -: the answer is the document and how many mails were taken. Without notify false the reminder is
		 *	mailed to the accounts of the client, which only the portal does: without it that is the 409 crm_documents_portal_off, and a reminder noted with notify false is all there is
		 */
		public static function apiRemind( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = Values::posted();
				$document = self::_document( $appData, $request, $data );

				if( $document === null )
					return;

				$notify = ( $data['notify'] ?? true ) !== false;
				$reminded = Documents::remind( $appData, $document['id'], $notify );

				if( is_string( $reminded ) === true ) {
					self::_refused( $request, $reminded );
					return;
				}

				Endpoint::made( $appData, 'Noted a payment reminder for document '. Render::name( $document ) );

				\Nino\Http::ok( $request, [ 'document' => self::_shown( $appData, $reminded['document'] ), 'mailed' => $reminded['mailed'], 'notify' => $notify ] );
			} );
		}

		/**
		 *	Make the file of a finalised document again where it is gone (the status says 'missing' for it): the PDF and the electronic invoice, each of its own, are rendered from the document - which does not change - and are
		 *	written only where their hash is the one that was stored. Answers the document, the files that were made and those that were not, because the hash was another (the template or the fonts were changed): the 409
		 *	crm_documents_restore_differs where none was made for that reason, crm_documents_restore_nothing where no file is missing, and crm_documents_restore_foreign where another file, a link or a folder lies at the name of a missing file: that is never written over
		 */
		public static function apiRestoreFile( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$document = self::_document( $appData, $request, Values::posted() );

				if( $document === null )
					return;

				$made = Finalise::restoreFiles( $appData, $document['id'] );

				if( is_string( $made ) === true ) {
					self::_refused( $request, $made );
					return;
				}

				if( $made['restored'] === [] && $made['differs'] !== [] ) {
					self::_refused( $request, 'differs' );
					return;
				}

				Endpoint::made( $appData, 'Made the file of document '. Render::name( $document ). ' again' );

				\Nino\Http::ok( $request, [ 'document' => self::_shown( $appData, (array) Documents::get( $appData, $document['id'] ) ), 'restored' => array_map( 'basename', $made['restored'] ), 'differs' => array_map( 'basename', $made['differs'] ) ] );
			} );
		}

		/**
		 *	One document with what its editor needs: the templates of its tag with their inputs
		 */
		public static function apiGet( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$document = self::_document( $appData, $request, Values::posted() );

				if( $document !== null )
					\Nino\Http::ok( $request, self::_view( $appData, $document ) );
			} );
		}

		/**
		 *	Add a draft (no id: category and client_id) or change one (id). A finalised document takes its notes and nothing else
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$data = Values::posted();
			$input = [];

			foreach( array_merge( self::FIELDS, [ 'notes' ] ) as $field )
				if( array_key_exists( $field, $data ) === true )
					$input[$field] = $data[$field];

			$client = Values::string( $data, 'client_id' );

			if( preg_match( '/^[1-9][0-9]{0,9}$/D', $client ) === 1 )
				$input['client_id'] = (int) $client;

			$offer = Values::string( $data, 'parent_id' );

			if( preg_match( '/^[1-9][0-9]{0,9}$/D', $offer ) === 1 )
				$input['parent_id'] = (int) $offer;

			Endpoint::run( $request, static function() use ( &$appData, &$request, $data, $input ): void {

				if( Values::string( $data, 'id' ) === '' ) {
					$saved = Documents::add( $appData, Values::string( $data, 'category' ), $input['client_id'] ?? 0, array_diff_key( $input, [ 'client_id' => 0 ] ) );
				}
				else {
					$existing = self::_document( $appData, $request, $data );

					if( $existing === null )
						return;

					// The notes are what a finalised document still takes; anything else of it is fixed
					if( $existing['state'] !== 'draft' )
						$saved = array_diff_key( $input, [ 'notes' => 0 ] ) === [] && array_key_exists( 'notes', $input ) === true && is_string( $input['notes'] ) === true
							? Documents::setNotes( $appData, $existing['id'], $input['notes'] )
							: 'fixed';
					else
						$saved = Documents::change( $appData, $existing['id'], $input );
				}

				if( is_string( $saved ) === true ) {
					self::_refused( $request, $saved );
					return;
				}

				Endpoint::made( $appData, ( Values::string( $data, 'id' ) !== '' ? 'Changed document ' : 'Added draft ' ). ( $saved['number'] !== '' ? $saved['number'] : (string) $saved['id'] ) );

				\Nino\Http::ok( $request, [ 'document' => self::_shown( $appData, $saved ) ] );
			} );
		}

		/**
		 *	Delete a draft
		 */
		public static function apiRemove( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$document = self::_document( $appData, $request, Values::posted() );

				if( $document === null )
					return;

				$removed = Documents::remove( $appData, $document['id'] );

				if( is_string( $removed ) === true ) {
					self::_refused( $request, $removed );
					return;
				}

				Endpoint::made( $appData, 'Deleted draft '. $document['id'] );

				\Nino\Http::ok( $request, [ 'removed' => true ] );
			} );
		}

		/**
		 *	Set the project of a document of any state, a finalised one included: id and project (the id of a project of the document's client, '' for none). The relation is no part of the finalised content, the PDF and its hash stay.
		 *	Answers the document as save does
		 */
		public static function apiProject( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = Values::posted();
				$document = self::_document( $appData, $request, $data );

				if( $document === null )
					return;

				$project = is_string( $data['project'] ?? null ) === true ? $data['project'] : null;

				if( $project === null ) {
					\Nino\Http::fail( $request, 400, 'the value has a wrong format', 'crm_documents_format', [], 'project' );
					return;
				}

				$moved = Documents::setProject( $appData, $document['id'], $project );

				if( is_string( $moved ) === true ) {
					self::_refused( $request, $moved );
					return;
				}

				Endpoint::made( $appData, 'Changed the project of document '. Render::name( $moved ). ' to '. ( $moved['project'] === '' ? 'none' : 'project '. $moved['project'] ) );

				\Nino\Http::ok( $request, [ 'document' => self::_shown( $appData, $moved ) ] );
			} );
		}

		/**
		 *	Finalise a draft; answers the view of the finalised document, as get does
		 */
		public static function apiFinalise( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$document = self::_document( $appData, $request, Values::posted() );

				if( $document === null )
					return;

				$final = Finalise::finalise( $appData, $document['id'] );

				if( is_string( $final ) === true ) {
					self::_refused( $request, $final );
					return;
				}

				Endpoint::made( $appData, 'Finalised document '. Render::name( $final ) );

				\Nino\Http::ok( $request, self::_view( $appData, $final ) );
			} );
		}

		/**
		 *	Move a finalised document a step: state is sent, paid, accepted or declined; date is the day of it
		 */
		public static function apiState( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = Values::posted();
				$document = self::_document( $appData, $request, $data );

				if( $document === null )
					return;

				$date = Values::string( $data, 'date' );
				$moved = Documents::setState( $appData, $document['id'], Values::string( $data, 'state' ), $date === '' ? null : $date );

				if( is_string( $moved ) === true ) {
					self::_refused( $request, $moved );
					return;
				}

				Endpoint::made( $appData, 'Moved document '. Render::name( $moved ). ' to '. $moved['state'] );

				\Nino\Http::ok( $request, [ 'document' => self::_shown( $appData, $moved ) ] );
			} );
		}

		/**
		 *	An invoice draft from an offer
		 */
		public static function apiFromOffer( array &$appData, array &$request ): void {
			self::_derived( $appData, $request, 'Made an invoice draft from offer %s', [ Finalise::class, 'fromOffer' ] );
		}

		/**
		 *	A credit note draft from an invoice
		 */
		public static function apiCredit( array &$appData, array &$request ): void {
			self::_derived( $appData, $request, 'Made a credit note draft from invoice %s', [ Finalise::class, 'creditOf' ] );
		}

		/**
		 *	The templates of Pdf for a category, each with its inputs
		 */
		public static function apiTemplates( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$category = Values::string( Values::posted(), 'category' );

			if( in_array( $category, Documents::CATEGORIES, true ) === false ) {
				\Nino\Http::fail( $request, 400, 'the category is none', 'crm_documents_category', [], 'category' );
				return;
			}

			Endpoint::run( $request, static function() use ( &$appData, &$request, $category ): void {

				$templates = Templates::of( $appData, $category );
				$standard = Documents::STANDARD[$category];

				\Nino\Http::ok( $request, [ 'templates' => $templates, 'standard' => in_array( $standard, array_column( $templates, 'key' ), true ) === true ? $standard : '', 'offers' => self::_offers( $appData ), 'items' => self::_catalogue( $appData ) ] );
			} );
		}

		/**
		 *	The items of the catalogue as the Elements panel keeps them, for the screen that looks at them: every item, hidden ones too, in the order of their sort, with its words in each language of the site (what the element holds in that
		 *	language, nothing taken from another), the price and the rate. missing says the element type is not there, which applying the content of the feature again brings
		 */
		public static function apiItems( array &$appData, array &$request ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				\Nino\Http::ok( $request, [
					'missing'   => Items::missing( $appData ),
					'unit_code' => Items::hasUnitCode( $appData ),
					'locales'   => array_values( \Nino\Locales::getAvailableLocales( $appData ) ),
					'native'    => \Nino\Locales::getNativeLocale( $appData ),
					'items'     => Items::all( $appData ),
					'settings'  => self::_settings( $appData ),
				] );
			} );
		}

		/**
		 *	The step from a document to another or to itself again: the offer's invoice, the invoice's credit note, a copy
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$line					The line of the activity log, with the place for the number of the source
		 *	@param		callable	$derive
		 *
		 *	@return 	void
		 */
		private static function _derived( array &$appData, array &$request, string $line, callable $derive ): void {

			if( Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request, $line, $derive ): void {

				$source = self::_document( $appData, $request, Values::posted() );

				if( $source === null )
					return;

				$draft = $derive( $appData, $source['id'] );

				if( is_string( $draft ) === true ) {
					self::_refused( $request, $draft );
					return;
				}

				Endpoint::made( $appData, sprintf( $line, Render::name( $source ) ) );

				\Nino\Http::ok( $request, [ 'document' => self::_shown( $appData, $draft ) ] );
			} );
		}

		/**
		 *	The document a request names by id, or the 400/404 that says why not
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	array|null
		 */
		private static function _document( array &$appData, array &$request, array $data ): ?array {

			$id = Endpoint::id( $request, $data, 'crm_documents_id' );

			if( $id === null )
				return null;

			$document = Documents::get( $appData, $id );

			if( $document === null )
				\Nino\Http::fail( $request, 404, 'there is no such document', 'crm_documents_unknown', [], 'id' );

			return $document;
		}

		/**
		 *	A document as the screen has it: the frozen client is the client, and the address of its PDF is there once it has one
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document
		 *
		 *	@return 	array
		 */
		private static function _shown( array &$appData, array $document ): array {

			unset( $document['client_frozen'] );
			$dir = \Nino\Filesystem::getDir( $appData );
			$document['pdf_url'] = $document['pdf_path'] === '' ? '' : $dir. '/.crm/pdf/'. $document['id'];
			$document['xml_url'] = $document['xml_path'] === '' ? '' : $dir. '/.crm/xml/'. $document['id'];
			$document['erechnung'] = self::_electronic( $appData, $document );

			return $document;
		}

		/**
		 *	What the screens say of the electronic invoice of a document: null for a document that gets none (an offer, a document without lines, a document of a private client, one finalised before the client had the flag); otherwise
		 *	whether the file is there (xml) and what is missing, each with its key and its sentence. A draft is read as it stands now; a finalised document says what was missing when it was finalised
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			As get() has it
		 *
		 *	@return 	array|null								xml (bool) and lacks ( [ key, text ] )
		 */
		private static function _electronic( array &$appData, array $document ): ?array {

			if( $document['state'] === 'draft' )
				return XRechnung::applies( $document ) === false ? null : [ 'xml' => false, 'lacks' => XRechnung::sentences( $appData, XRechnung::lacks( $appData, $document ) ) ];

			if( $document['xml_path'] !== '' )
				return [ 'xml' => true, 'lacks' => [] ];

			return $document['xml_lacks'] === [] ? null : [ 'xml' => false, 'lacks' => XRechnung::sentences( $appData, $document['xml_lacks'] ) ];
		}

		/**
		 *	A document with the templates of its tag, the settings the editor draws with and the offers a contract can hang on
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document
		 *
		 *	@return 	array
		 */
		private static function _view( array &$appData, array $document ): array {

			return [
				'document'  => self::_shown( $appData, $document ),
				'templates' => Templates::of( $appData, $document['category'] ),
				'clients'   => self::_clients( $appData ),
				'settings'  => self::_settings( $appData ),
				'items'     => self::_catalogue( $appData ),
				'offers'    => self::_offers( $appData ),
				'projects'  => self::_projects( $appData, $document['client_id'] ),
			];
		}

		/**
		 *	The items a line is filled from: the active elements of the type of the items in the native language, as the editor reads them (price_net in cents, vat_rate in hundredths). Where the project is a small business the rate is
		 *	not asked for and 0 is written
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id, title, description, unit, unit_code, price_net, vat_rate, active and sort of each
		 */
		private static function _catalogue( array &$appData ): array {

			$small = Settings::get( $appData, 'vat_mode' ) === 'small_business';

			return array_map( static fn( array $item ): array => [
				'id'          => $item['id'],
				'title'       => $item['title'],
				'description' => $item['description'],
				'unit'        => $item['unit'],
				'unit_code'   => $item['unit_code'],
				'price_net'   => $item['price'],
				'vat_rate'    => $small === true ? 0 : $item['vat'],
				'active'      => $item['active'],
				'sort'        => $item['sort'],
			], Items::list( $appData ) );
		}

		/**
		 *	The projects of a client, the newest first, the done ones included (the screen offers the open ones and the one the document names)
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$clientId
		 *
		 *	@return 	array											id, title and done of each
		 */
		private static function _projects( array &$appData, int $clientId ): array {

			$client = Clients::get( $appData, $clientId );

			return $client === null ? [] : array_map( static fn( array $project ): array => [ 'id' => (string) $project['id'], 'title' => $project['title'], 'done' => $project['done'] ], Projects::ofClient( $appData, (string) $client['number'] ) );
		}

		/**
		 *	The offers a contract can hang on: finalised, the newest first
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id, number, date, client_id and client_title of each
		 */
		private static function _offers( array &$appData ): array {

			$offers = array_filter( Documents::list( $appData, [ 'category' => 'offer' ] ), static fn( array $offer ): bool => $offer['state'] !== 'draft' );

			return array_values( array_map( static fn( array $offer ): array => [ 'id' => $offer['id'], 'number' => $offer['number'], 'date' => $offer['date'], 'client_id' => $offer['client_id'], 'client_title' => $offer['client_title'] ], $offers ) );
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id, number, title, ready, bare (neither company nor name), active, business and buyer_reference of every client, by number
		 */
		private static function _clients( array &$appData ): array {

			return array_map( static fn( array $client ): array => [ 'id' => $client['id'], 'number' => $client['number'], 'title' => $client['title'], 'ready' => Documents::invoiceReady( $client ), 'bare' => $client['company'] === '' && $client['name'] === '', 'active' => $client['active'], 'business' => $client['business'], 'buyer_reference' => $client['buyer_reference'] ], Clients::list( $appData ) );
		}

		/**
		 *	What the screen needs to compute and draw a document
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The settings, and the rates as a list
		 */
		private static function _settings( array &$appData ): array {

			return Settings::all( $appData ) + [ 'rates' => Rules::rates( $appData ) ];
		}

		/**
		 *	The answer to a refusal of the model
		 *
		 *	@param		array 		&$request
		 *	@param		string		$why					See Documents
		 *
		 *	@return 	void
		 */
		private static function _refused( array &$request, string $why ): void {

			[ $kind, $rest ] = array_pad( explode( ':', $why, 2 ), 2, '' );

			// A document without lines has a title, and it is the title that is missing, too long or wrong
			if( $kind === 'empty' && $rest === 'title' ) {
				\Nino\Http::fail( $request, 400, 'the title is missing', 'crm_documents_title', [], 'title' );
				return;
			}

			if( isset( self::REFUSALS[$kind] ) === true ) {
				[ $status, $code, $field ] = self::REFUSALS[$kind];
				\Nino\Http::fail( $request, $status, 'the document does not allow it: '. $kind, $code, match( $kind ) { 'lines' => [ Documents::MAX_LINES ], 'words', 'markup' => [ $rest ], default => [] }, $field );
				return;
			}

			match( $kind ) {
				'date'   => \Nino\Http::fail( $request, 400, 'the date is none', 'crm_documents_date', [], $rest ),
				'long'   => \Nino\Http::fail( $request, 400, 'the value is too long', 'crm_documents_long', [ match( $rest ) { 'title' => Documents::MAX_TITLE, 'buyer_reference' => Documents::MAX_REFERENCE, default => Documents::MAX_NOTES } ], $rest ),
				'format' => \Nino\Http::fail( $request, 400, 'the value has a wrong format', 'crm_documents_format', [], $rest ),
				'exists' => str_ends_with( $rest, '.xml' ) === true
					? \Nino\Http::fail( $request, 409, 'the file of the electronic invoice is there already', 'crm_documents_exists_xml', [ basename( $rest, '.xml' ), ltrim( $rest, '/' ) ] )
					: \Nino\Http::fail( $request, 409, 'the PDF file is there already', 'crm_documents_exists', [ basename( $rest, '.pdf' ), ltrim( $rest, '/' ) ] ),
				'inputs' => \Nino\Http::fail( $request, 409, 'a required input is missing', 'crm_documents_inputs', [], 'inputs.'. $rest ),
				'line'   => self::_refusedLine( $request, $rest ),
				default  => \Nino\Http::fail( $request, 500, 'the document package refused with "'. $kind. '"' ),
			};
		}

		/**
		 *	A line that is wrong: line:<index>:<kind>:<field>
		 *
		 *	@param		array 		&$request
		 *	@param		string		$rest					<index>:<kind>:<field>
		 *
		 *	@return 	void
		 */
		private static function _refusedLine( array &$request, string $rest ): void {

			[ $index, $kind, $field ] = array_pad( explode( ':', $rest, 3 ), 3, '' );
			$code = in_array( $kind, [ 'empty', 'long', 'format', 'amount', 'rate' ], true ) === true ? $kind : 'format';

			\Nino\Http::fail( $request, 400, 'a line is wrong', 'crm_documents_line_'. $code, [ (int) $index + 1 ], 'lines.'. (int) $index. ( $field === '' ? '' : '.'. $field ) );
		}
	}
}
