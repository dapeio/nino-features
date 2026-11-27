<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Clients;
	use Nino\Modules\Crm;
	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Logbook;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Records;
	use Nino\Modules\Crm\Settings;

	/**
	 *	Documents		The documents of the business: offers, invoices and credit notes, each with a client, dates, lines and totals in cents, and the contracts, privacy documents and information documents that have neither lines nor
	 *								a number. A document is a draft - editable, deletable, without a number - until Finalise::finalise() takes the number of its sequence, freezes the client's address into it, computes the totals once more and
	 *								renders and stores its PDF, all in one transaction. From then on nothing of it changes but its state, its internal notes and the project it belongs to, and a finalised document is never deleted. All storage
	 *								is the database of the core; every statement is prepared.
	 *
	 *								A write answers the document as get() has it, or a string:
	 *								  unknown                 no such document or client
	 *								  category, client, inactive   the category is none; the client is none; the client is not active
	 *								  offer                   a contract without an offer of the client in any state but draft
	 *								  fixed                   the document is no draft any more
	 *								  state                   the state does not allow it
	 *								  date:<field>, period    a date that is none; a period of one day or from after to
	 *								  long:<field>, format:<field>   notes, inputs, template_key, buyer_reference; for the documents without lines also title, and lines
	 *								  empty:title             a document without lines has no title
	 *								  reverse                 a reverse charge where the document has VAT mode small_business
	 *								  lines, total            more than MAX_LINES lines; a net total past Money::MAX_TOTAL
	 *								  line:<i>:<kind>:<field> a line (counted from 0) whose field is wrong, see Lines::clean() and qty; no field for a line that is no array
	 *								  unready                 at finalise of an offer, an invoice or a credit note: the client has no company or name, or no street, postal code, city or country yet
	 *								  pdf, template, inputs:<name>, empty    at finalise: Pdf is off; no template of the tag; a required input is missing; the document has no line
	 *								  number                  the number format is none or the number is taken
	 *								  render, exists:<path>, write   the PDF could not be made, is there already (at that path), could not be written; exists:<path> and write are also what the electronic invoice (XRechnung) is refused with, its path ending in .xml
	 *								  portal                  at remind() with a mail: the mail goes to the accounts of the client, which the portal brings
	 *								A database failure is a \PDOException the caller answers. Beside the steps there are the readings of Figures (summary, figures, ledger, needs, recent), the finalising of Finalise and the hooks of Hooks.
	 *
	 *	@package						Dape/Nino
	 */
	class Documents {

		public const array CATEGORIES = [ 'offer', 'invoice', 'credit', 'contract', 'privacy', 'information' ];

		// The categories without lines, totals and numbers: a contract hangs on an offer of the client, the others on the client
		public const array PLAIN = [ 'contract', 'privacy', 'information' ];

		// The template the status line seeds for each kind of document: a new draft starts with it where nothing else says which to take
		public const array STANDARD = [ 'offer' => 'offer', 'invoice' => 'invoice', 'credit' => 'credit-note', 'contract' => 'contract', 'privacy' => 'privacy', 'information' => 'information' ];

		public const int MAX_TITLE = 120;

		public const int MAX_LINES = 200;
		public const int MAX_NOTES = 5000;
		public const int MAX_INPUT = 20000;

		// The states a document moves through by setState(), by category: from => to. The three documents without lines end in signed or declined too, which only a package that asks for signatures gives
		public const array TRANSITIONS = [
			'offer'   => [ 'open' => [ 'sent', 'accepted', 'declined' ], 'sent' => [ 'accepted', 'declined' ] ],
			'invoice' => [ 'open' => [ 'sent' ], 'sent' => [ 'paid' ] ],
			'credit'  => [ 'open' => [ 'sent' ] ],
			'contract'    => [ 'open' => [ 'sent' ] ],
			'privacy'     => [ 'open' => [ 'sent' ] ],
			'information' => [ 'open' => [ 'sent' ] ],
		];

		public const string TIME = 'Y-m-d\TH:i:s\Z';

		public const int MAX_REFERENCE = 100;

		// The categories that have a buyer reference of their own: those that can be an electronic invoice
		public const array REFERENCING = [ 'invoice', 'credit' ];

		/**
		 *	The documents, the newest first
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$filter				category, state (or expired), year, client_id, project (the id of a project)
				 *
		 *	@return 	array											Each with its client's title as the document shows it, and whether it is expired (an offer) or overdue (an invoice), and the key of the PDF template it was made with (template_key, '' for none), and paid_notice, the day the customer reported that it paid the invoice ('' for none)
		 */
		public static function list( array &$appData, array $filter = [] ): array {

			$where = [];
			$params = [];
			$today = date( 'Y-m-d' );

			if( in_array( $filter['category'] ?? null, self::CATEGORIES, true ) === true ) {
				$where[] = 'd.category = :category';
				$params['category'] = $filter['category'];
			}

			if( is_string( $filter['state'] ?? null ) === true && $filter['state'] !== '' ) {

				if( $filter['state'] === 'expired' ) {
					$where[] = 'd.category = \'offer\' AND d.state IN ( \'open\', \'sent\' ) AND d.valid_until < :today';
					$params['today'] = $today;
				}
				else {
					$where[] = 'd.state = :state';
					$params['state'] = $filter['state'];
				}
			}

			if( is_int( $filter['year'] ?? null ) === true ) {
				$where[] = 'SUBSTR( d.date, 1, 4 ) = :year';
				$params['year'] = sprintf( '%04d', $filter['year'] );
			}

			if( is_int( $filter['client_id'] ?? null ) === true ) {
				$where[] = 'd.client_id = :client';
				$params['client'] = $filter['client_id'];
			}

			if( is_string( $filter['project'] ?? null ) === true && $filter['project'] !== '' ) {
				$where[] = 'd.project = :project';
				$params['project'] = $filter['project'];
			}

			$titles = Projects::titles( $appData );

			$rows = Db::rows( $appData, 'SELECT d.id, d.category, d.number, d.title, d.state, d.date, d.due_date, d.valid_until, d.gross, d.client_id, d.parent_id, d.project, d.template_key, d.client_json, d.released, d.reminded_at, d.reminded_mail, d.sign_id, d.sign_state, c.company AS live_company, c.name AS live_name, c.email AS live_email,
				( SELECT SUBSTR( MAX( r.at ), 1, 10 ) FROM records r WHERE r.thing = \'document\' AND r.thing_id = CAST( d.id AS TEXT ) AND r.kind = \'payment\' ) AS paid_notice
				FROM documents d JOIN clients c ON c.id = d.client_id'. ( $where === [] ? '' : ' WHERE '. implode( ' AND ', $where ) ). ' ORDER BY d.date DESC, d.id DESC', $params );

			return array_map( static function( array $row ) use ( $today, $titles ): array {

				$frozen = json_decode( (string) $row['client_json'], true );
				$frozen = is_array( $frozen ) === true ? $frozen : [];
				$shown = $row['state'] === 'draft' ? [ 'company' => $row['live_company'], 'name' => $row['live_name'], 'email' => $row['live_email'] ] : $frozen;

				return [
					'id'           => (int) $row['id'],
					'category'     => $row['category'],
					'number'       => (string) ( $row['number'] ?? '' ),
					'title'        => (string) $row['title'],
					'state'        => $row['state'],
					'released'     => (int) $row['released'] === 1,
					'reminded_at'  => (string) ( $row['reminded_at'] ?? '' ),
					'reminded_mail' => (int) ( $row['reminded_mail'] ?? 0 ) === 1,
					'sign_state'   => (string) $row['sign_state'],
					'expired'      => self::expired( $row, $today ),
					'overdue'      => self::overdue( $row, $today ),
					'date'         => $row['date'],
					'due_date'     => $row['due_date'],
					'valid_until'  => $row['valid_until'],
					'gross'        => (int) $row['gross'],
					'client_id'    => (int) $row['client_id'],
					'client_title' => Clients::title( $shown ),
					'parent_id'    => $row['parent_id'] === null ? null : (int) $row['parent_id'],
					'project'      => (string) $row['project'],
					'project_title' => $titles[(string) $row['project']] ?? '',
					'template_key' => (string) $row['template_key'],
					'paid_notice'  => (string) ( $row['paid_notice'] ?? '' ),
				];
			}, $rows );
		}

		/**
		 *	The years the documents are dated in, the latest first
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											Years as integers
		 */
		public static function years( array &$appData ): array {

			return array_map( 'intval', array_column( Db::rows( $appData, 'SELECT DISTINCT SUBSTR( date, 1, 4 ) AS year FROM documents ORDER BY year DESC' ), 'year' ) );
		}

		/**
		 *	One document with its lines, its client, the document it comes from and the documents that come from it
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|null
		 */
		public static function get( array &$appData, int $id ): ?array {

			$row = Db::row( $appData, 'SELECT * FROM documents WHERE id = :id', [ 'id' => $id ] );

			if( $row === null )
				return null;

			$document = self::shape( $row );
			$document['project_title'] = $document['project'] === '' ? '' : (string) ( Projects::get( $appData, $document['project'] )['title'] ?? '' );
			$document['lines'] = array_map( [ self::class, 'shapeLine' ], Db::rows( $appData, 'SELECT * FROM document_lines WHERE document_id = :id ORDER BY sort, id', [ 'id' => $id ] ) );
			$document['client'] = $document['state'] === 'draft' ? Clients::get( $appData, $document['client_id'] ) : $document['client_frozen'];
			$parent = $document['parent_id'] === null ? null : Db::row( $appData, 'SELECT id, number, title, category, state, date FROM documents WHERE id = :id', [ 'id' => $document['parent_id'] ] );
			$document['parent'] = $parent === null ? null : [ 'id' => (int) $parent['id'], 'number' => (string) ( $parent['number'] ?? '' ), 'date' => (string) $parent['date'], 'title' => (string) $parent['title'], 'category' => $parent['category'], 'state' => $parent['state'] ];
			$document['parent_number'] = $document['parent']['number'] ?? '';

			// A credit note that is finalised carries the tax of each rate it was finalised with, which is not the tax of its lines taken alone
			$vatOf = $document['category'] === 'credit' && $document['state'] !== 'draft' ? Finalise::creditVat( $appData, $document, $document['lines'], array_column( $document['lines'], 'amount_net' ) ) : null;
			$document['rates'] = [];

			foreach( $vatOf === null ? [] : Money::totals( $document['lines'], $document['vat_mode'], $document['reverse_charge'], $vatOf )['rates'] as $rate => $part )
				$document['rates'][] = [ 'rate' => $rate, 'net' => $part['net'], 'vat' => $part['vat'] ];

			$document['children'] = array_map( static fn( array $child ): array => [ 'id' => (int) $child['id'], 'number' => (string) ( $child['number'] ?? '' ), 'title' => (string) $child['title'], 'category' => $child['category'], 'state' => $child['state'], 'gross' => (int) $child['gross'] ],
				Db::rows( $appData, 'SELECT id, number, title, category, state, gross FROM documents WHERE parent_id = :id ORDER BY id', [ 'id' => $id ] ) );

			return $document;
		}

		/**
		 *	Add a draft. A credit note is none of them: it is made from its invoice, by creditOf()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category
		 *	@param		int				$clientId			An active client. A contract takes the client of its offer: 0, or the same client
		 *	@param		array 		$input				See change(); parent_id (the offer) for a contract; the date is today and the due or validity date follows from the settings where they are not given
		 *
		 *	@return 	array|string							The draft as get() has it; or category (none of the six, or a credit note), client, inactive, date:<field> and what clean() refuses with
		 */
		public static function add( array &$appData, string $category, int $clientId, array $input = [] ): array|string {

			if( in_array( $category, self::CATEGORIES, true ) === false || $category === 'credit' )
				return 'category';

			$parentId = null;

			if( $category === 'contract' ) {

				$offer = self::offerFor( $appData, $input['parent_id'] ?? null );

				if( is_string( $offer ) === true )
					return $offer;

				if( $clientId !== 0 && $clientId !== $offer['client_id'] )
					return 'client';

				$clientId = $offer['client_id'];
				$parentId = $offer['id'];
			}

			$client = Clients::get( $appData, $clientId );

			if( $client === null )
				return 'client';

			if( $client['active'] === false )
				return 'inactive';

			unset( $input['client_id'], $input['parent_id'] );
			$date = $input['date'] ?? date( 'Y-m-d' );
			$base = [
				'category' => $category, 'client_id' => $clientId, 'parent_id' => $parentId, 'vat_mode' => Settings::get( $appData, 'vat_mode' ), 'reverse_charge' => false, 'date' => $date, 'notes' => '', 'title' => '',
				'template_key' => '', 'inputs' => [], 'period_from' => null, 'period_to' => null, 'due_date' => null, 'valid_until' => null, 'lines' => [], 'project' => '',
				'buyer_reference' => in_array( $category, self::REFERENCING, true ) === true ? $client['buyer_reference'] : '',
			];

			if( is_string( $date ) === true && self::validDate( $date ) === true && in_array( $category, self::PLAIN, true ) === false ) {
				$key = $category === 'offer' ? 'valid_until' : 'due_date';
				$base[$key] = self::dayAfter( $appData, $date, $category === 'offer' ? 'offer_days' : 'payment_days' );

				// A day so late that the due or validity day after it is beyond the years there are: it is the date that is wrong, not a day nobody typed
				if( $base[$key] !== null && array_key_exists( $key, $input ) === false && self::validDate( $base[$key] ) === false )
					return 'date:date';
			}

			$values = self::clean( $appData, $input, $base );

			if( is_string( $values ) === true )
				return $values;

			return (array) self::get( $appData, self::insert( $appData, $values, 'draft' ) );
		}

		/**
		 *	The PDF template a new draft starts with, so that a caller that makes a draft for a client (a feature) makes it as the surface does: the only template of the kind; else the one the newest
		 *	document of the same category and the same client was made with, as long as it is one of the templates; else the template Einrichten made for the kind; else none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category			One of CATEGORIES
		 *	@param		int				$clientId
		 *
		 *	@return 	string										The key; '' for none, and where Pdf is off
		 */
		public static function template( array &$appData, string $category, int $clientId ): string {

			if( in_array( $category, self::CATEGORIES, true ) === false || Crm::featureOn( $appData, 'pdf' ) === false )
				return '';

			$keys = array_keys( \Nino\Modules\Pdf::templates( $appData, Render::tag( $category ) ) );

			if( count( $keys ) === 1 )
				return $keys[0];

			foreach( Db::rows( $appData, 'SELECT template_key FROM documents WHERE category = :category AND client_id = :client AND template_key <> \'\' ORDER BY id DESC', [ 'category' => $category, 'client' => $clientId ] ) as $row )
				if( in_array( $row['template_key'], $keys, true ) === true )
					return (string) $row['template_key'];

			return in_array( self::STANDARD[$category], $keys, true ) === true ? self::STANDARD[$category] : '';
		}

		/**
		 *	Change a draft: the fields given; the others stay. Lines given replace the lines
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array 		$input				client_id, date, due_date, valid_until, period_from, period_to, template_key, inputs, reverse_charge, notes, lines; title for the documents without lines; parent_id (the offer) for a contract, whose client follows it
		 *
		 *	@return 	array|string
		 */
		public static function change( array &$appData, int $id, array $input ): array|string {

			$current = self::get( $appData, $id );

			if( $current === null )
				return 'unknown';

			if( $current['state'] !== 'draft' )
				return 'fixed';

			// The client of a contract is its offer's: the offer is looked at again, and the client follows it
			if( $current['category'] === 'contract' ) {

				$offer = self::offerFor( $appData, $input['parent_id'] ?? $current['parent_id'] );

				if( is_string( $offer ) === true )
					return $offer;

				if( isset( $input['client_id'] ) === true && $input['client_id'] !== $offer['client_id'] )
					return 'client';

				$input['client_id'] = $offer['client_id'];
				$input['parent_id'] = $offer['id'];
			}

			// The client of a credit note is its invoice's, as a contract's is its offer's: the credit note takes back what that client was charged
			if( $current['category'] === 'credit' ) {

				$bound = $current['parent_id'] === null ? null : Db::row( $appData, 'SELECT client_id FROM documents WHERE id = :id', [ 'id' => $current['parent_id'] ] );
				$bound = $bound === null ? $current['client_id'] : (int) $bound['client_id'];

				if( isset( $input['client_id'] ) === true && $input['client_id'] !== $bound )
					return 'client';

				$input['client_id'] = $bound;
			}

			if( isset( $input['client_id'] ) === true && $input['client_id'] !== $current['client_id'] ) {

				$client = is_int( $input['client_id'] ) === true ? Clients::get( $appData, $input['client_id'] ) : null;

				if( $client === null )
					return 'client';

				if( $client['active'] === false )
					return 'inactive';
			}

			// The reference of the new client takes the place of the old client's - not of one the person typed
			if( isset( $input['client_id'] ) === true && $input['client_id'] !== $current['client_id'] && array_key_exists( 'buyer_reference', $input ) === false && is_array( $current['client'] ) === true
				&& $current['buyer_reference'] === $current['client']['buyer_reference'] && ( $next = Clients::get( $appData, $input['client_id'] ) ) !== null )
				$input['buyer_reference'] = $next['buyer_reference'];

			$values = self::clean( $appData, $input, $current );

			if( is_string( $values ) === true )
				return $values;

			$pdo = Db::pdo( $appData );
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$state = $pdo->prepare( 'SELECT state FROM documents WHERE id = :id' );
				$state->execute( [ 'id' => $id ] );

				if( $state->fetchColumn() !== 'draft' ) {
					$pdo->exec( 'ROLLBACK' );
					return 'fixed';
				}

				$pdo->prepare( 'UPDATE documents SET client_id = :client_id, parent_id = :parent_id, title = :title, date = :date, due_date = :due_date, valid_until = :valid_until, period_from = :period_from, period_to = :period_to,
					reverse_charge = :reverse_charge, net = :net, vat = :vat, gross = :gross, template_key = :template_key, inputs_json = :inputs_json, notes = :notes, project = :project, buyer_reference = :buyer_reference, updated = :now WHERE id = :id' )
					->execute( [ 'id' => $id, 'now' => gmdate( self::TIME ) ] + self::columns( $values ) );

				self::writeLines( $pdo, $id, $values['lines'], $values['amounts'] );
				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			return (array) self::get( $appData, $id );
		}

		/**
		 *	The project of a document of any state, a finalised one included: the relation is no part of the finalised content - the PDF and its hash do not
		 *	read it - and so it may be moved
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		string		$project			The id of a project of the document's client; '' for none
		 *
		 *	@return 	array|string							The document as get() has it; or unknown, format:project, project (there is no such project), project_client (it is another client's)
		 */
		public static function setProject( array &$appData, int $id, string $project ): array|string {

			$document = Db::row( $appData, 'SELECT client_id FROM documents WHERE id = :id', [ 'id' => $id ] );

			if( $document === null )
				return 'unknown';

			if( $project !== '' && preg_match( Projects::ID, $project ) !== 1 )
				return 'format:project';

			$refused = self::projectError( $appData, $project, (int) $document['client_id'] );

			if( $refused !== null )
				return $refused;

			Db::exec( $appData, 'UPDATE documents SET project = :project, updated = :now WHERE id = :id', [ 'project' => $project, 'now' => gmdate( self::TIME ), 'id' => $id ] );

			return (array) self::get( $appData, $id );
		}

		/**
		 *	Why a project is not one a document of the client may name
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *	@param		int				$clientId
		 *
		 *	@return 	string|null								null where it is none or the project exists and is the client's; project (it does not exist); project_client (it is another client's)
		 */
		private static function projectError( array &$appData, string $project, int $clientId ): ?string {

			if( $project === '' )
				return null;

			$found = Projects::get( $appData, $project );

			if( $found === null )
				return 'project';

			$client = Clients::get( $appData, $clientId );

			return $client !== null && $client['number'] === $found['client'] ? null : 'project_client';
		}

		/**
		 *	The project a document made from another keeps: its source's, where that is still one of the client's - a project that is gone or
		 *	moved on takes nothing with it, and the new document has none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *	@param		int				$clientId
		 *
		 *	@return 	string
		 */
		public static function projectKept( array &$appData, string $project, int $clientId ): string {

			return self::projectError( $appData, $project, $clientId ) === null ? $project : '';
		}

		/**
		 *	The internal notes of a document, which stay changeable after it is finalised
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		string		$notes
		 *
		 *	@return 	array|string
		 */
		public static function setNotes( array &$appData, int $id, string $notes ): array|string {

			if( mb_strlen( $notes, 'UTF-8' ) > self::MAX_NOTES )
				return 'long:notes';

			if( Db::exec( $appData, 'UPDATE documents SET notes = :notes, updated = :now WHERE id = :id', [ 'notes' => $notes, 'now' => gmdate( self::TIME ), 'id' => $id ] ) === 0 )
				return 'unknown';

			return (array) self::get( $appData, $id );
		}

		/**
		 *	Delete a draft with its lines
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	true|string
		 */
		public static function remove( array &$appData, int $id ): true|string {

			// A record that names the document keeps it: records are evidence, and what they name is not deleted - a draft is named by none, but the books are asked all the same
			if( Records::names( $appData, 'document', (string) $id ) === true )
				return 'fixed';

			// The state is part of the statement, so a document finalised a moment ago is not deleted; what it did not delete it tells apart afterwards
			if( Db::exec( $appData, 'DELETE FROM documents WHERE id = :id AND state = \'draft\' AND number IS NULL', [ 'id' => $id ] ) === 1 )
				return true;

			return Db::row( $appData, 'SELECT id FROM documents WHERE id = :id', [ 'id' => $id ] ) === null ? 'unknown' : 'fixed';
		}

		/**
		 *	Move a finalised document one step forward
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		string		$state				sent, paid, accepted or declined - see TRANSITIONS
		 *	@param		string|null	$date					Y-m-d for sent_at and paid_at; now where none is given
		 *
		 *	@return 	array|string
		 */
		public static function setState( array &$appData, int $id, string $state, ?string $date = null ): array|string {

			Projects::forget( $appData );

			$pdo = Db::pdo( $appData );
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				// Read and written in one transaction: a step another request took first is not taken twice
				$refused = self::moveIn( $pdo, $id, $state, $date );

				if( $refused !== '' ) {
					$pdo->exec( 'ROLLBACK' );
					return $refused;
				}

				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			$document = (array) self::get( $appData, $id );

			// The day the owner names is the day of sending or of payment (today where he names none); the entry is of the moment it was noted
			if( in_array( $state, [ 'sent', 'paid' ], true ) === true )
				self::logged( $appData, 'document-'. $state, $document, [ 'day' => $date ?? gmdate( 'Y-m-d' ) ] );

			return $document;
		}

		/**
		 *	One step of setState() on a connection that is in a transaction already
		 *
		 *	@param		\PDO			$pdo
		 *	@param		int				$id
		 *	@param		string		$state
		 *	@param		string|null	$date
		 *
		 *	@return 	string										'' once the document is moved; or why not: unknown, state, date:date, daterange (the day of sending or of payment is before the document, or the payment before the sending, or the day is to come)
		 */
		private static function moveIn( \PDO $pdo, int $id, string $state, ?string $date ): string {

			$statement = $pdo->prepare( 'SELECT category, state, date, sent_at FROM documents WHERE id = :id' );
			$statement->execute( [ 'id' => $id ] );
			$row = $statement->fetch();

			// The day of sending and of payment is none before the day of the document, none before the day of sending (a payment) and none to come
			$earliest = $row === false ? '' : max( (string) $row['date'], $state === 'paid' ? substr( (string) ( $row['sent_at'] ?? '' ), 0, 10 ) : '' );

			$refused = match( true ) {
				$row === false                                                                           => 'unknown',
				in_array( $state, self::TRANSITIONS[$row['category']][$row['state']] ?? [], true ) === false => 'state',
				$date !== null && self::validDate( $date ) === false                                     => 'date:date',
				in_array( $state, [ 'sent', 'paid' ], true ) === true && ( ( $date ?? date( 'Y-m-d' ) ) < $earliest || ( $date ?? date( 'Y-m-d' ) ) > self::latest() ) => 'daterange',
				default                                                                                  => '',
			};

			if( $refused !== '' )
				return $refused;

			$column = $state === 'sent' ? ', sent_at = :time' : ( $state === 'paid' ? ', paid_at = :time' : '' );
			$params = [ 'id' => $id, 'new' => $state, 'now' => gmdate( self::TIME ) ] + ( $column === '' ? [] : [ 'time' => $date === null ? gmdate( self::TIME ) : $date. 'T00:00:00Z' ] );

			$pdo->prepare( 'UPDATE documents SET state = :new, updated = :now'. $column. ' WHERE id = :id' )->execute( $params );

			return '';
		}

		/**
		 *	Whether an invoice may be reminded: it is an invoice that is open or sent - with a due date or without one, late or not yet: the owner decides when
		 *
		 *	@param		array 		$row					category and state
		 *
		 *	@return 	bool
		 */
		public static function remindable( array $row ): bool {

			return ( $row['category'] ?? '' ) === 'invoice' && in_array( $row['state'] ?? '', [ 'open', 'sent' ], true );
		}

		/**
		 *	A reminder to pay, noted on an invoice that is open or sent - overdue or not, the owner decides when: reminded_at (the time) and whether it went by mail. Without the mail nothing is sent and the note is all there is, so that the
		 *	owner who phoned notes it as well; the mail to the accounts of the client is the portal's, and a reminder with a mail is refused where the portal is not there. The invoice is noted in a transaction of its own, only while it is
		 *	still open or sent. Nothing else changes. The activity log names the number
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		bool			$mail					Whether the accounts of the client are mailed
		 *
		 *	@return 	array|string							document (as get() has it) and mailed (how many mails were taken: 0); or unknown; state (it is no invoice that is open or sent); portal (a mail was asked for, and the mail is the portal's)
		 */
		public static function remind( array &$appData, int $id, bool $mail = true ): array|string {

			$document = self::get( $appData, $id );

			if( $document === null )
				return 'unknown';

			if( self::remindable( $document ) === false )
				return 'state';

			if( $mail === true )
				return 'portal';

			$now = gmdate( self::TIME );
			$changed = Db::exec( $appData, 'UPDATE documents SET reminded_at = :now, reminded_mail = 0 WHERE id = :id AND category = \'invoice\' AND state IN ( \'open\', \'sent\' )', [ 'now' => $now, 'id' => $id ] );

			if( $changed === 0 )
				return 'state';

			self::logged( $appData, 'document-reminded', $document, [ 'mail' => false ], $now );

			return [ 'document' => (array) self::get( $appData, $id ), 'mailed' => 0 ];
		}

		/**
		 *	Write what happened to a document into the logbook: the client and the project of the document, the document as the reference, its number (else its title) for the sentence
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$kind					One of the kinds the package entered (Hooks::KINDS)
		 *	@param		array 		$document			As get() has it
		 *	@param		array 		$data					More small values for the sentence
		 *	@param		string|null	$at						The time of the entry; now for none
		 *	@param		string		$by						The account that did it; the account that is signed in (else system) for none
		 *
		 *	@return 	void
		 */
		public static function logged( array &$appData, string $kind, array $document, array $data = [], ?string $at = null, string $by = '' ): void {

			if( is_int( $document['id'] ?? null ) === false )
				return;

			$label = (string) ( $document['number'] ?? '' ) !== '' ? (string) $document['number'] : (string) ( $document['title'] ?? '' );
			$entry = [ 'source' => 'crm-documents', 'kind' => $kind, 'client_id' => $document['client_id'], 'ref' => (string) $document['id'], 'by' => $by !== '' ? $by : Logbook::who( $appData ),
				'data' => [ 'document' => $document['id'], 'label' => Logbook::scrub( $label ) ] + ( (string) ( $document['number'] ?? '' ) === '' ? [ 'titled' => true ] : [] ) + $data ];

			if( (string) ( $document['project'] ?? '' ) !== '' )
				$entry['project'] = (string) $document['project'];

			if( $at !== null )
				$entry['at'] = $at;

			// A refusal is a warning in the log and no failure of the step that asked: the document is what the books hold, the entry is a line about it
			$written = Logbook::add( $appData, $entry );

			if( is_string( $written ) === true )
				trigger_error( 'Crm: the logbook entry '. $kind. ' of a document was not written ('. $written. ')', E_USER_WARNING );
		}

		/**
		 *	Whether the master data of a client are complete for an offer, an invoice or a credit note: a company or a name, and street, postcode, town and country. A client that is not cannot be written a document of those
		 *
		 *	@param		array 		$client				As Clients::get() has it
		 *
		 *	@return 	bool
		 */
		public static function invoiceReady( array $client ): bool {

			return Clients::complete( $client );
		}

		/**
		 *	Whether an offer is open or sent and past its validity date: a way to look at it and no state
		 *
		 *	@param		array 		$row					category, state, valid_until
		 *	@param		string		$today				Y-m-d
		 *
		 *	@return 	bool
		 */
		public static function expired( array $row, string $today ): bool {

			return $row['category'] === 'offer' && in_array( $row['state'], [ 'open', 'sent' ], true ) === true && is_string( $row['valid_until'] ?? null ) === true && $row['valid_until'] < $today;
		}

		/**
		 *	Whether an invoice is open or sent and past its due date: a way to look at it and no state
		 *
		 *	@param		array 		$row					category, state, due_date (none or empty for an invoice with no day: it is due at once and past no day)
		 *	@param		string|null	$today				Y-m-d; today where none is given
		 *
		 *	@return 	bool
		 */
		public static function overdue( array $row, ?string $today = null ): bool {

			return $row['category'] === 'invoice' && in_array( $row['state'], [ 'open', 'sent' ], true ) === true && is_string( $row['due_date'] ?? null ) === true && $row['due_date'] !== '' && $row['due_date'] < ( $today ?? date( 'Y-m-d' ) );
		}

		/**
		 *	Whether a text is a day Y-m-d of the years 2000 to 2999
		 *
		 *	@param		string		$date
		 *
		 *	@return 	bool
		 */
		public static function validDate( string $date ): bool {

			// Four digits, two, two and nothing after them: a null byte is an error to the parser, and a longer text is read up to the day
			if( preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $date ) !== 1 )
				return false;

			$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );

			return $parsed !== false && $parsed->format( 'Y-m-d' ) === $date && $parsed->format( 'Y' ) >= '2000' && $parsed->format( 'Y' ) <= '2999';
		}

		/**
		 *	The last day a document may be dated, or a step be dated: today (the page takes the day of the server as the day of its dialogs)
		 *
		 *	@return 	string										Y-m-d
		 */
		public static function latest(): string {

			return date( 'Y-m-d' );
		}

		/**
		 *	The day a setting of days after a date comes to: the prefill of a due date and of a validity day. A setting of 0 days is none, and the document has no such day
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$date					Y-m-d
		 *	@param		string		$setting			payment_days or offer_days
		 *
		 *	@return 	string|null								Y-m-d; null where the setting is 0
		 */
		public static function dayAfter( array &$appData, string $date, string $setting ): ?string {

			$days = (int) Settings::get( $appData, $setting );

			return $days === 0 ? null : self::addDays( $date, $days );
		}

		/**
		 *	@param		string		$date					Y-m-d
		 *	@param		int				$days
		 *
		 *	@return 	string
		 */
		public static function addDays( string $date, int $days ): string {

			return ( new \DateTimeImmutable( $date ) )->modify( '+'. $days. ' days' )->format( 'Y-m-d' );
		}

		/**
		 *	The offer a contract hangs on: of any state but draft
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			$id
		 *
		 *	@return 	array|string							id and client_id of the offer; 'offer' for an id that is none, an offer nobody has and a draft
		 */
		private static function offerFor( array &$appData, mixed $id ): array|string {

			$row = is_int( $id ) === true ? Db::row( $appData, 'SELECT id, client_id FROM documents WHERE id = :id AND category = \'offer\' AND state != \'draft\'', [ 'id' => $id ] ) : null;

			return $row === null ? 'offer' : [ 'id' => (int) $row['id'], 'client_id' => (int) $row['client_id'] ];
		}

		/**
		 *	The offer a contract quotes, with its lines
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document
		 *
		 *	@return 	array|null								As get() has it; null for a document that is no contract and for an offer that is a draft or gone
		 */
		public static function offerOf( array &$appData, array $document ): ?array {

			if( $document['category'] !== 'contract' || $document['parent_id'] === null )
				return null;

			$offer = self::get( $appData, $document['parent_id'] );

			return $offer === null || $offer['category'] !== 'offer' || $offer['state'] === 'draft' ? null : $offer;
		}

		/**
		 *	The checked fields of a draft, from what was given over what the draft has
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$input
		 *	@param		array 		$current			The draft, or the defaults of a new one: category, client_id, vat_mode and the fields below
		 *
		 *	@return 	array|string
		 */
		private static function clean( array &$appData, array $input, array $current ): array|string {

			$plain = in_array( $current['category'], self::PLAIN, true );

			// Only a contract changes what it hangs on, and only add() and change() say to what
			$values = [ 'category' => $current['category'], 'client_id' => $input['client_id'] ?? $current['client_id'], 'parent_id' => $current['category'] === 'contract' ? ( $input['parent_id'] ?? $current['parent_id'] ?? null ) : ( $current['parent_id'] ?? null ), 'vat_mode' => $current['vat_mode'] ];

			if( $plain === true ) {

				$title = $input['title'] ?? $current['title'] ?? '';

				if( is_string( $title ) === false || mb_check_encoding( $title, 'UTF-8' ) === false )
					return 'format:title';

				$title = trim( $title );

				if( $title === '' )
					return 'empty:title';

				if( preg_match( '/[\p{C}]/u', $title ) === 1 || Lines::marked( $title ) === true )
					return 'format:title';

				if( mb_strlen( $title, 'UTF-8' ) > self::MAX_TITLE )
					return 'long:title';

				$values['title'] = $title;
			}
			else
				$values['title'] = '';

			foreach( [ 'date', 'due_date', 'valid_until', 'period_from', 'period_to' ] as $field ) {

				$date = array_key_exists( $field, $input ) === true ? $input[$field] : $current[$field];
				$date = $date === '' ? null : $date;

				if( $date !== null && ( is_string( $date ) === false || self::validDate( $date ) === false ) )
					return 'date:'. $field;

				$values[$field] = $date;
			}

			if( $values['date'] === null )
				return 'date:date';

			// The date a category does not use is not kept
			// An offer is valid for a time, an invoice is due after one; a credit note returns money and has neither; a document without lines has the date alone
			if( $values['category'] !== 'offer' )
				$values['valid_until'] = null;

			if( $values['category'] !== 'invoice' )
				$values['due_date'] = null;

			if( $plain === true )
				$values['period_from'] = $values['period_to'] = null;

			if( ( $values['period_from'] === null ) !== ( $values['period_to'] === null ) || ( $values['period_from'] !== null && $values['period_to'] !== null && $values['period_from'] > $values['period_to'] ) )
				return 'period';

			$reverse = $input['reverse_charge'] ?? $current['reverse_charge'];

			if( is_bool( $reverse ) === false )
				return 'format:reverse_charge';

			if( $plain === true && $reverse === true )
				return 'format:reverse_charge';

			if( $reverse === true && $values['vat_mode'] !== 'regular' )
				return 'reverse';

			$values['reverse_charge'] = $reverse;

			$notes = $input['notes'] ?? $current['notes'];

			if( is_string( $notes ) === false )
				return 'format:notes';

			if( mb_strlen( $notes, 'UTF-8' ) > self::MAX_NOTES )
				return 'long:notes';

			$values['notes'] = $notes;

			// A buyer reference belongs to the documents that can be an electronic invoice; the others keep none
			$reference = $input['buyer_reference'] ?? $current['buyer_reference'] ?? '';

			if( is_string( $reference ) === false || mb_check_encoding( $reference, 'UTF-8' ) === false || preg_match( '/[\p{C}]/u', $reference ) === 1 || Lines::marked( $reference ) === true )
				return 'format:buyer_reference';

			$reference = trim( $reference );

			if( mb_strlen( $reference, 'UTF-8' ) > self::MAX_REFERENCE )
				return 'long:buyer_reference';

			$values['buyer_reference'] = in_array( $values['category'], self::REFERENCING, true ) === true ? $reference : '';

			$project = $input['project'] ?? $current['project'] ?? '';

			if( is_string( $project ) === false || ( $project !== '' && preg_match( Projects::ID, $project ) !== 1 ) )
				return 'format:project';

			$refused = self::projectError( $appData, $project, (int) $values['client_id'] );

			if( $refused !== null )
				return $refused;

			$values['project'] = $project;

			$key = $input['template_key'] ?? $current['template_key'];

			if( is_string( $key ) === false )
				return 'format:template_key';

			if( $key !== '' && ( Crm::featureOn( $appData, 'pdf' ) === false || isset( \Nino\Modules\Pdf::templates( $appData, Render::tag( $values['category'] ) )[$key] ) === false ) )
				return 'template';

			$values['template_key'] = $key;

			$inputs = $input['inputs'] ?? $current['inputs'];

			if( is_array( $inputs ) === false || count( $inputs ) > 60 )
				return 'format:inputs';

			foreach( $inputs as $name => $text )
				if( is_string( $name ) === false || preg_match( '/^[a-z][a-z0-9-]{1,39}$/D', $name ) !== 1 || is_string( $text ) === false || mb_strlen( $text, 'UTF-8' ) > self::MAX_INPUT )
					return 'format:inputs';

			$values['inputs'] = $inputs;

			$lines = $input['lines'] ?? $current['lines'];

			if( is_array( $lines ) === false || array_is_list( $lines ) === false || ( $plain === true && $lines !== [] ) )
				return 'format:lines';

			if( count( $lines ) > self::MAX_LINES )
				return 'lines';

			$clean = [];

			foreach( $lines as $index => $line ) {

				$one = is_array( $line ) === true ? self::cleanLine( $appData, $line, $values['vat_mode'], $reverse ) : 'format:';

				if( is_string( $one ) === true )
					return 'line:'. $index. ':'. $one;

				$clean[] = $one;
			}

			$totals = Money::totals( $clean, $values['vat_mode'], $reverse );

			if( abs( $totals['net'] ) > Money::MAX_TOTAL )
				return 'total';

			if( $values['category'] === 'credit' ) {

				$account = Finalise::creditCheck( $appData, $values['parent_id'], $values['vat_mode'], $reverse, $clean, $totals['amounts'], false );

				if( is_string( $account ) === true )
					return $account;

				$origin = Db::row( $appData, 'SELECT date FROM documents WHERE id = :id', [ 'id' => $values['parent_id'] ] );

				if( $origin !== null && $values['date'] < $origin['date'] )
					return 'date:date';
			}

			return $values + [ 'lines' => $clean, 'amounts' => $totals['amounts'] ] + $totals;
		}

		/**
		 *	One line: the words and numbers of a line and a quantity. A reverse charge sets the rate to 0
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$line
		 *	@param		string		$vatMode
		 *	@param		bool			$reverse
		 *
		 *	@return 	array|string
		 */
		private static function cleanLine( array &$appData, array $line, string $vatMode, bool $reverse ): array|string {

			$line['price'] = $line['price'] ?? $line['price_net'] ?? 0;

			if( $reverse === true )
				$line['vat_rate'] = 0;

			$values = Lines::clean( $appData, $line, $reverse === true ? 'small_business' : $vatMode );

			if( is_string( $values ) === true )
				return $values;

			$qty = Lines::amount( $line['qty'] ?? null, 'qty' );

			if( $qty === null || $qty === 0 )
				return 'amount:qty';

			if( abs( Money::lineAmount( $qty, $values['price_net'] ) ) > Money::MAX_LINE )
				return 'amount:price';

			return $values + [ 'qty' => $qty ];
		}

		/**
		 *	Insert a document and its lines, in a transaction of their own unless the caller has one open
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$values				See clean()
		 *	@param		string		$state
		 *	@param		bool			$joined				The caller has a transaction open, which this one joins
		 *
		 *	@return 	int												The id
		 */
		public static function insert( array &$appData, array $values, string $state, bool $joined = false ): int {

			$pdo = Db::pdo( $appData );
			$now = gmdate( self::TIME );
			$own = $joined === false;

			if( $own === true )
				$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$pdo->prepare( 'INSERT INTO documents ( category, client_id, parent_id, title, state, date, due_date, valid_until, period_from, period_to, vat_mode, reverse_charge, net, vat, gross, template_key, inputs_json, notes, project, buyer_reference, created, updated )
					VALUES ( :category, :client_id, :parent_id, :title, :state, :date, :due_date, :valid_until, :period_from, :period_to, :vat_mode, :reverse_charge, :net, :vat, :gross, :template_key, :inputs_json, :notes, :project, :buyer_reference, :now, :now )' )
					->execute( [ 'category' => $values['category'], 'vat_mode' => $values['vat_mode'], 'state' => $state, 'now' => $now ] + self::columns( $values ) );

				$id = (int) $pdo->lastInsertId();
				self::writeLines( $pdo, $id, $values['lines'], $values['amounts'] );

				if( $own === true )
					$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				if( $own === true )
					$pdo->exec( 'ROLLBACK' );

				throw $e;
			}

			return $id;
		}

		/**
		 *	The columns a draft's fields are written to
		 *
		 *	@param		array 		$values
		 *
		 *	@return 	array
		 */
		private static function columns( array $values ): array {

			return [
				'client_id' => $values['client_id'], 'parent_id' => $values['parent_id'], 'title' => $values['title'], 'date' => $values['date'], 'due_date' => $values['due_date'], 'valid_until' => $values['valid_until'],
				'period_from' => $values['period_from'], 'period_to' => $values['period_to'], 'reverse_charge' => $values['reverse_charge'] === true ? 1 : 0,
				'net' => $values['net'], 'vat' => $values['vat'], 'gross' => $values['gross'], 'template_key' => $values['template_key'],
				'inputs_json' => json_encode( $values['inputs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR ), 'notes' => $values['notes'], 'project' => $values['project'],
				'buyer_reference' => $values['buyer_reference'],
			];
		}

		/**
		 *	Replace the lines of a draft
		 *
		 *	@param		\PDO			$pdo
		 *	@param		int				$id
		 *	@param		array 		$lines
		 *	@param		array 		$amounts
		 *
		 *	@return 	void
		 */
		private static function writeLines( \PDO $pdo, int $id, array $lines, array $amounts ): void {

			$pdo->prepare( 'DELETE FROM document_lines WHERE document_id = :id' )->execute( [ 'id' => $id ] );
			$statement = $pdo->prepare( 'INSERT INTO document_lines ( document_id, sort, title, description, qty, unit, unit_code, price_net, vat_rate, amount_net ) VALUES ( :id, :sort, :title, :description, :qty, :unit, :unit_code, :price_net, :vat_rate, :amount )' );

			foreach( $lines as $position => $line )
				$statement->execute( [ 'id' => $id, 'sort' => $position + 1, 'title' => $line['title'], 'description' => $line['description'], 'qty' => $line['qty'], 'unit' => $line['unit'], 'unit_code' => $line['unit_code'],
					'price_net' => $line['price_net'], 'vat_rate' => $line['vat_rate'], 'amount' => $amounts[$position] ] );
		}

		/**
		 *	@param		array 		$row
		 *
		 *	@return 	array
		 */
		private static function shape( array $row ): array {

			$inputs = json_decode( (string) $row['inputs_json'], true );
			$frozen = json_decode( (string) $row['client_json'], true );

			foreach( [ 'id', 'client_id', 'net', 'vat', 'gross' ] as $field )
				$row[$field] = (int) $row[$field];

			$row['parent_id'] = $row['parent_id'] === null ? null : (int) $row['parent_id'];
			$row['reverse_charge'] = (int) $row['reverse_charge'] === 1;
			$row['released'] = (int) $row['released'] === 1;
			$row['reminded_at'] = (string) ( $row['reminded_at'] ?? '' );
			$row['reminded_mail'] = (int) ( $row['reminded_mail'] ?? 0 ) === 1;
			$row['release_note'] = (string) ( $row['release_note'] ?? '' );
			$row['release_note_at'] = (string) ( $row['release_note_at'] ?? '' );
			$row['number'] = (string) ( $row['number'] ?? '' );
			$lacks = json_decode( (string) $row['xml_lacks'], true );
			$row['xml_lacks'] = is_array( $lacks ) === true ? array_values( array_filter( $lacks, 'is_string' ) ) : [];
			$row['inputs'] = is_array( $inputs ) === true ? $inputs : [];
			$row['client_frozen'] = is_array( $frozen ) === true ? $frozen : [];
			$row['expired'] = self::expired( $row, date( 'Y-m-d' ) );
			$row['overdue'] = self::overdue( $row, date( 'Y-m-d' ) );
			unset( $row['inputs_json'], $row['client_json'] );

			return $row;
		}

		/**
		 *	@param		array 		$row
		 *
		 *	@return 	array
		 */
		private static function shapeLine( array $row ): array {

			foreach( [ 'id', 'document_id', 'sort', 'qty', 'price_net', 'vat_rate', 'amount_net' ] as $field )
				$row[$field] = (int) $row[$field];

			return $row;
		}
	}
}
