<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Clients;
	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Projects;

	/**
	 *	Figures		What the first screen and the export read of the documents: the books at a glance (summary()), the block of the figures (figures()), what goes to the accountant (ledger(), exportable()), what needs
	 *								the owner (needs()) and the documents of the latest dates (recent()). Every method reads and writes nothing.
	 *
	 *	@package						Dape/Nino
	 */
	class Figures {

		// The months the block of the figures lists, and the clients it names
		public const int MONTHS = 12;
		public const int CLIENTS = 5;

		/**
		 *	The books at a glance: the open invoices - open or sent, not paid yet - and what they come to, what of them is overdue and since
		 *	when, the offers still waiting, what was paid this year and the drafts
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											count and gross (the open invoices); overdue_count, overdue_gross and overdue_oldest (the oldest due date, '' for none); offers_count and offers_gross
		 *																		(open or sent offers); paid_year, paid_count and paid_gross (invoices paid in the year now); drafts_count and drafts_last (the day of the newest draft, '' for none)
		 */
		public static function summary( array &$appData ): array {

			$today = date( 'Y-m-d' );
			$open = Db::row( $appData, 'SELECT COUNT(*) AS n, COALESCE( SUM( gross ), 0 ) AS gross FROM documents WHERE category = \'invoice\' AND state IN ( \'open\', \'sent\' )' );
			$late = Db::row( $appData, 'SELECT COUNT(*) AS n, COALESCE( SUM( gross ), 0 ) AS gross, MIN( due_date ) AS oldest FROM documents WHERE category = \'invoice\' AND state IN ( \'open\', \'sent\' ) AND due_date IS NOT NULL AND due_date < :today', [ 'today' => $today ] );
			$offers = Db::row( $appData, 'SELECT COUNT(*) AS n, COALESCE( SUM( gross ), 0 ) AS gross FROM documents WHERE category = \'offer\' AND state IN ( \'open\', \'sent\' )' );
			$year = date( 'Y' );
			$paid = Db::row( $appData, 'SELECT COUNT(*) AS n, COALESCE( SUM( gross ), 0 ) AS gross FROM documents WHERE category = \'invoice\' AND state = \'paid\' AND SUBSTR( paid_at, 1, 4 ) = :year', [ 'year' => $year ] );
			$drafts = Db::row( $appData, 'SELECT COUNT(*) AS n, MAX( date ) AS last FROM documents WHERE state = \'draft\'' );

			return [
				'count'          => (int) ( $open['n'] ?? 0 ),
				'gross'          => (int) ( $open['gross'] ?? 0 ),
				'overdue_count'  => (int) ( $late['n'] ?? 0 ),
				'overdue_gross'  => (int) ( $late['gross'] ?? 0 ),
				'overdue_oldest' => (string) ( $late['oldest'] ?? '' ),
				'offers_count'   => (int) ( $offers['n'] ?? 0 ),
				'offers_gross'   => (int) ( $offers['gross'] ?? 0 ),
				'paid_year'      => (int) $year,
				'paid_count'     => (int) ( $paid['n'] ?? 0 ),
				'paid_gross'     => (int) ( $paid['gross'] ?? 0 ),
				'drafts_count'   => (int) ( $drafts['n'] ?? 0 ),
				'drafts_last'    => (string) ( $drafts['last'] ?? '' ),
			];
		}

		/**
		 *	The figures of the first screen's block Zahlen, one query for each table. Months: the invoices and credit notes that are no drafts, by the month of their date,
		 *	for the month now and the eleven before it, the newest first and a month with nothing in it as a row of zeros - billed (the gross of the invoices less the gross of the
		 *	credit notes, which carry theirs as a negative sum, by the day of the document), paid (the gross of the invoices that were paid, by the day of the payment - the money came, though
		 *	a credit note may have taken the invoice back since) and open (the gross of the invoices that are open or sent, by the day of the document, as summary() and the list count them). Clients: the five that were billed most in the year now, by the same sum, the live name of the client. Offers: those of the year now that are no drafts, by what
		 *	became of them - accepted, declined, expired (open or sent past the day they are valid until) and open - with their number and their gross
		 *
		 *	@param		array 		&$appData
		 *	@param		string|null	$today				Y-m-d; today where none is given
		 *
		 *	@return 	array											year, months (month Y-m, billed, paid, open), clients (client_id, client_title, billed) and offers (accepted, declined, expired, open, each count and gross)
		 */
		public static function figures( array &$appData, ?string $today = null ): array {

			$today ??= date( 'Y-m-d' );
			$first = new \DateTimeImmutable( substr( $today, 0, 7 ). '-01' );
			$months = [];

			for( $back = 0; $back < self::MONTHS; $back++ ) {
				$month = $first->modify( '-'. $back. ' months' )->format( 'Y-m' );
				$months[$month] = [ 'month' => $month, 'billed' => 0, 'paid' => 0, 'open' => 0 ];
			}

			$from = $first->modify( '-'. ( self::MONTHS - 1 ). ' months' )->format( 'Y-m-d' );
			$until = $first->modify( '+1 month' )->format( 'Y-m-d' );

			// One read: what was billed by the month of the document and what is open of it, and - as a second set of rows of the same read - what was paid by the month of the payment
			foreach( Db::rows( $appData, 'SELECT month, SUM( billed ) AS billed, SUM( paid ) AS paid, SUM( open ) AS open FROM (
					SELECT SUBSTR( date, 1, 7 ) AS month, gross AS billed, 0 AS paid, CASE WHEN category = \'invoice\' AND state IN ( \'open\', \'sent\' ) THEN gross ELSE 0 END AS open
						FROM documents WHERE category IN ( \'invoice\', \'credit\' ) AND state != \'draft\' AND date >= :from AND date < :until
					UNION ALL
					SELECT SUBSTR( paid_at, 1, 7 ) AS month, 0 AS billed, gross AS paid, 0 AS open
						FROM documents WHERE category = \'invoice\' AND paid_at IS NOT NULL AND paid_at >= :from AND paid_at < :until
				) GROUP BY month', [ 'from' => $from, 'until' => $until ] ) as $row ) {

				if( isset( $months[$row['month']] ) === false )
					continue;

				$months[$row['month']] = [ 'month' => $row['month'], 'billed' => (int) $row['billed'], 'paid' => (int) $row['paid'], 'open' => (int) $row['open'] ];
			}

			$year = substr( $today, 0, 4 );
			$clients = array_map( static fn( array $row ): array => [ 'client_id' => (int) $row['client_id'], 'client_title' => Clients::title( [ 'company' => $row['company'], 'name' => $row['name'], 'email' => $row['email'] ] ), 'billed' => (int) $row['billed'] ],
				Db::rows( $appData, 'SELECT d.client_id, c.company, c.name, c.email, SUM( d.gross ) AS billed FROM documents d JOIN clients c ON c.id = d.client_id
					WHERE d.category IN ( \'invoice\', \'credit\' ) AND d.state != \'draft\' AND d.date >= :from AND d.date < :until GROUP BY d.client_id ORDER BY billed DESC, d.client_id LIMIT '. self::CLIENTS,
					[ 'from' => $year. '-01-01', 'until' => sprintf( '%04d', (int) $year + 1 ). '-01-01' ] ) );

			$offers = [ 'accepted' => [ 'count' => 0, 'gross' => 0 ], 'declined' => [ 'count' => 0, 'gross' => 0 ], 'expired' => [ 'count' => 0, 'gross' => 0 ], 'open' => [ 'count' => 0, 'gross' => 0 ] ];

			foreach( Db::rows( $appData, 'SELECT CASE WHEN state = \'accepted\' THEN \'accepted\' WHEN state = \'declined\' THEN \'declined\' WHEN valid_until IS NOT NULL AND valid_until < :today THEN \'expired\' ELSE \'open\' END AS result, COUNT(*) AS n, SUM( gross ) AS gross
				FROM documents WHERE category = \'offer\' AND state IN ( \'open\', \'sent\', \'accepted\', \'declined\' ) AND date >= :from AND date < :until GROUP BY result',
				[ 'today' => $today, 'from' => $year. '-01-01', 'until' => sprintf( '%04d', (int) $year + 1 ). '-01-01' ] ) as $row )
				$offers[$row['result']] = [ 'count' => (int) $row['n'], 'gross' => (int) $row['gross'] ];

			return [ 'year' => (int) $year, 'months' => array_values( $months ), 'clients' => $clients, 'offers' => $offers ];
		}

		/**
		 *	How many documents of a year have a PDF file in the books: the offers, invoices and credit notes that are no drafts and name one - what the ZIP of the year can hold at most
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$year
		 *
		 *	@return 	int
		 */
		public static function exportable( array &$appData, int $year ): int {

			return (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM documents WHERE category IN ( \'offer\', \'invoice\', \'credit\' ) AND state != \'draft\' AND pdf_path != \'\' AND date >= :from AND date < :until',
				[ 'from' => sprintf( '%04d', $year ). '-01-01', 'until' => sprintf( '%04d', $year + 1 ). '-01-01' ] )['n'] ?? 0 );
		}

		/**
		 *	The documents of a year that go to the accountant: the offers, invoices and credit notes that are no drafts (a contract, a privacy or an information document has no
		 *	number and no amount), the oldest first, by the day of their date; or - with ids - those of the ids that are such documents
		 *
		 *	@param		array 		&$appData
		 *	@param		int|null	$year					The year of the date; null with ids
		 *	@param		array 		$ids					The ids to take; none for the whole year
		 *
		 *	@return 	array											Each with id, category, number, date, client_title (as it was frozen into the document), net, vat, gross, state, paid_at (Y-m-d, '' for none), project (the id, '' for none), project_title, pdf_path, pdf_sha256, xml_path and xml_sha256
		 */
		public static function ledger( array &$appData, ?int $year, array $ids = [] ): array {

			$where = [ 'category IN ( \'offer\', \'invoice\', \'credit\' )', 'state != \'draft\'' ];
			$params = [];

			if( $year !== null ) {
				$where[] = 'date >= :from AND date < :until';
				$params = [ 'from' => sprintf( '%04d', $year ). '-01-01', 'until' => sprintf( '%04d', $year + 1 ). '-01-01' ];
			}

			if( $ids !== [] ) {

				$names = [];

				foreach( array_values( $ids ) as $at => $id ) {
					$names[] = ':id'. $at;
					$params['id'. $at] = (int) $id;
				}

				$where[] = 'id IN ( '. implode( ', ', $names ). ' )';
			}

			$titles = Projects::titles( $appData );

			return array_map( static function( array $row ) use ( $titles ): array {

				$frozen = json_decode( (string) $row['client_json'], true );

				return [
					'id'           => (int) $row['id'],
					'category'     => (string) $row['category'],
					'number'       => (string) ( $row['number'] ?? '' ),
					'date'         => (string) $row['date'],
					'client_title' => Clients::title( is_array( $frozen ) === true ? $frozen : [] ),
					'net'          => (int) $row['net'],
					'vat'          => (int) $row['vat'],
					'gross'        => (int) $row['gross'],
					'state'        => (string) $row['state'],
					'paid_at'      => substr( (string) ( $row['paid_at'] ?? '' ), 0, 10 ),
					'project'      => (string) $row['project'],
					'project_title' => (string) ( $titles[(string) $row['project']] ?? '' ),
					'pdf_path'     => (string) $row['pdf_path'],
					'pdf_sha256'   => (string) $row['pdf_sha256'],
					'xml_path'     => (string) ( $row['xml_path'] ?? '' ),
					'xml_sha256'   => (string) ( $row['xml_sha256'] ?? '' ),
				];
			}, Db::rows( $appData, 'SELECT id, category, number, date, client_json, net, vat, gross, state, paid_at, project, pdf_path, pdf_sha256, xml_path, xml_sha256 FROM documents WHERE '. implode( ' AND ', $where ). ' ORDER BY date, number, id', $params ) );
		}

		/**
		 *	What needs the owner: the invoices that are overdue (the oldest due first) and the offers that run out within the next seven days. The portal adds what it knows of (the orders and the payments a customer reported, the offers a
		 *	customer declined, the clients that have no account), a package of files what it knows of, through the hook of the overview
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list in that order; each has kind (overdue, expiring), id (the document), client_id, client_title, number, title, date (the due date, the day the offer runs out), gross and reminded_at (the time of the last payment reminder, '' for none)
		 */
		public static function needs( array &$appData ): array {

			$today = date( 'Y-m-d' );
			$until = Documents::addDays( $today, 7 );
			$rows = Db::rows( $appData, 'SELECT d.id, d.category, d.number, d.title, d.state, d.date, d.due_date, d.valid_until, d.gross, d.client_id, d.client_json, d.reminded_at
				FROM documents d WHERE d.state IN ( \'open\', \'sent\' ) ORDER BY d.date, d.id' );
			$groups = [ 'overdue' => [], 'expiring' => [] ];

			foreach( $rows as $row ) {

				$frozen = json_decode( (string) $row['client_json'], true );
				$frozen = is_array( $frozen ) === true ? $frozen : [];
				$entry = [
					'kind' => '', 'id' => (int) $row['id'], 'client_id' => (int) $row['client_id'], 'client_title' => Clients::title( $frozen ), 'number' => (string) ( $row['number'] ?? '' ),
					'title' => (string) $row['title'], 'date' => '', 'gross' => (int) $row['gross'], 'reminded_at' => (string) ( $row['reminded_at'] ?? '' ),
				];

				if( Documents::overdue( $row, $today ) === true )
					$groups['overdue'][] = [ 'kind' => 'overdue', 'date' => (string) $row['due_date'] ] + $entry;
				elseif( $row['category'] === 'offer' && is_string( $row['valid_until'] ) === true && $row['valid_until'] >= $today && $row['valid_until'] <= $until )
					$groups['expiring'][] = [ 'kind' => 'expiring', 'date' => $row['valid_until'] ] + $entry;
			}

			// The oldest due date first: it has waited longest
			usort( $groups['overdue'], static fn( array $a, array $b ): int => [ $a['date'], $a['id'] ] <=> [ $b['date'], $b['id'] ] );
			usort( $groups['expiring'], static fn( array $a, array $b ): int => [ $a['date'], $a['id'] ] <=> [ $b['date'], $b['id'] ] );

			return array_merge( $groups['overdue'], $groups['expiring'] );
		}

		/**
		 *	The documents of the latest dates, drafts among them
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$limit
		 *
		 *	@return 	array											As list() has them, the newest first
		 */
		public static function recent( array &$appData, int $limit = 8 ): array {

			return array_slice( Documents::list( $appData ), 0, max( 0, $limit ) );
		}
	}
}
