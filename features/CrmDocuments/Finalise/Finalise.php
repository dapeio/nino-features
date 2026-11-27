<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Clients;
	use Nino\Modules\Crm;
	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Settings;

	/**
	 *	Finalise		The step that makes a document the books hold: finalise() takes the number of its sequence, freezes the client's address into it, computes the totals once more, renders its PDF and, for the invoice of
	 *								a business client, writes its electronic invoice - all in one transaction that holds the write lock of the database, or nothing of it. The number is spent with the document and with nothing else: where the
	 *								step is refused after the number was taken, or the transaction fails, the counter goes back with it and the files that were written are taken away, so that the numbers of a category and a year run without a gap and
	 *								no number is given twice (the column is unique, and a number that is taken refuses the step). A document that is finalised is never changed by this class again.
	 *
	 *								Besides it the class makes the drafts that come from another document - the invoice of an offer (fromOffer()), the credit note of an invoice (creditOf()) and the copy of a document (copy()) - and the PDF of a
	 *								draft as its template would make it now (preview()), which stores nothing and takes no number. The credit note is held to the invoice it takes back: its lines, the tax of each rate (creditVat()) and the sum of
	 *								the credit notes together (creditCheck()). What finalise() and preview() refuse with is in the class Documents.
	 *
	 *	@package						Dape/Nino
	 */
	class Finalise {

		// The fields of the client that are frozen into a finalised document: business is frozen as 1 or 0, the others as text. The electronic invoice reads the frozen client, so what it says is what the client was when the document was finalised
		private const array CLIENT_FIELDS = [ 'company', 'name', 'street', 'zip', 'city', 'country', 'number', 'vat_id', 'email', 'business', 'buyer_reference' ];

		/**
		 *	Finalise a draft: number, frozen client, totals, PDF - in one transaction, or nothing of it
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|string
		 */
		public static function finalise( array &$appData, int $id ): array|string {

			// A file that a process left under a pending name after its commit is given its name before the next number is drawn
			Render::settleAll( $appData );

			$pdo = Db::pdo( $appData );
			$written = [];
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				// What an attempt cut off before its commit left under pending names carries a client's address and mail: it goes under the lock, where nothing is staged but by this call
				Render::purge( $appData );
				$outcome = self::finaliseIn( $appData, $pdo, $id, $written );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				self::discard( $appData, $written );
				throw $e;
			}

			// Most of what finaliseIn() refuses with came before the PDF was written; the files of the one that comes after it (the electronic invoice could not be written) are taken back
			if( is_string( $outcome ) === true ) {
				$pdo->exec( 'ROLLBACK' );
				self::discard( $appData, $written );
				return $outcome;
			}

			try {
				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				self::discard( $appData, $written );
				throw $e;
			}

			// The files get their names after the commit: until then they lie under pending names that no document can have, and a process that dies here has left rows whose files are found by their hashes (Render::settle())
			$document = (array) Documents::get( $appData, $id );
			Documents::logged( $appData, 'document-finalised', $document, [], $document['finalised'] ?? null );

			foreach( $written as $path => $file )
				if( Render::publish( $appData, $path, $file['pending'], $file['sha256'] ) === false )
					trigger_error( 'Crm: the file '. basename( $path ). ' of a finalised document has not got its name: it lies under a pending name and is given it the next time it is asked for', E_USER_WARNING );

			return $document;
		}

		/**
		 *	Make the files of a finalised document again where they are gone: the document is the books and does not change, so rendering it again gives the file it had. A file is taken only if the hash of the new one is the
		 *	hash that is stored - the template may have been changed since, or the fonts - and then it is written the way every file of the books is, under a pending name first and linked to its name, in a transaction that holds
		 *	the write lock, so that nothing else gives the name. Where the hash is not the stored one nothing is written. The PDF and the electronic invoice are each of their own
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|string							restored and differs: the virtual paths of the files that were made, and of those that would not have been the stored ones, written nowhere; or unknown, state (a draft has no file), nothing (no file is missing); foreign (another file, a link or a folder lies at the name of a file that is missing: nothing was written)
		 */
		public static function restoreFiles( array &$appData, int $id ): array|string {

			$document = Documents::get( $appData, $id );

			if( $document === null )
				return 'unknown';

			if( $document['state'] === 'draft' )
				return 'state';

			$pdo = Db::pdo( $appData );
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$outcome = self::restoreIn( $appData, $document );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			$pdo->exec( 'COMMIT' );

			return $outcome;
		}

		/**
		 *	The body of restoreFiles(), under the lock
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			As Documents::get() has it, finalised
		 *
		 *	@return 	array|string
		 */
		private static function restoreIn( array &$appData, array $document ): array|string {

			$wanted = [];

			foreach( [ 'pdf' => [ 'pdf_path', 'pdf_sha256' ], 'xml' => [ 'xml_path', 'xml_sha256' ] ] as $kind => [ $pathKey, $hashKey ] )
				if( (string) $document[$pathKey] !== '' && Render::settle( $appData, (string) $document[$pathKey], (string) $document[$hashKey] ) === false )
					$wanted[$kind] = [ (string) $document[$pathKey], (string) $document[$hashKey] ];

			if( $wanted === [] )
				return 'nothing';

			// Something else at the name of a file is never written over, moved or deleted: nothing is made before the owner has taken it away
			foreach( $wanted as [ $path, $hash ] )
				if( Render::lies( $appData, $path, $hash ) === 'other' )
					return 'foreign';

			// The document as it was finalised: the frozen client, and for a contract its offer - the same shape finaliseIn() makes the files from
			$sheet = self::sheet( $appData, $document, $document['lines'], (array) $document['client'] );
			$restored = [];
			$differs = [];

			foreach( $wanted as $kind => [ $path, $hash ] ) {

				if( $kind === 'pdf' ) {
					$made = Render::bytes( $appData, $sheet, $sheet['lines'], $sheet['client'] );

					if( is_string( $made ) === true )
						return $made;

					$bytes = $made['bytes'];
				}
				else
					$bytes = XRechnung::build( $appData, $sheet );

				if( hash_equals( $hash, hash( 'sha256', $bytes ) ) === false ) {
					$differs[] = $path;
					continue;
				}

				$staged = Render::free( $appData, $path ) === true ? Render::stage( $appData, $path, $bytes ) : '';

				if( $staged === '' || Render::publish( $appData, $path, $staged, $hash ) === false ) {
					$staged === '' || @unlink( $staged );

					return 'write';
				}

				$restored[] = $path;
			}

			return [ 'restored' => $restored, 'differs' => $differs ];
		}

		/**
		 *	The document in the shape its files are made from: finalised ('open' for the sheet), with the frozen client, a contract with its offer, and no lines where it has none. finaliseIn() makes the files from it
		 *	and restoreIn() makes them again from it, so that the same document gives the same bytes
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			As Documents::get() has it, with number and totals where it has them
		 *	@param		array 		$lines				The lines with their amounts; not looked at for a document without lines
		 *	@param		array 		$frozen				The client as freeze() has it
		 *
		 *	@return 	array
		 */
		private static function sheet( array &$appData, array $document, array $lines, array $frozen ): array {

			$plain = in_array( $document['category'], Documents::PLAIN, true );

			if( $plain === true )
				$document['offer'] = Documents::offerOf( $appData, $document );

			return [ 'state' => 'open', 'client' => $frozen, 'lines' => $plain === true ? [] : $lines ] + $document;
		}

		/**
		 *	The body of the transaction of finalise(): everything up to the commit
		 *
		 *	@param		array 		&$appData
		 *	@param		\PDO			$pdo
		 *	@param		int				$id
		 *	@param		array 		&$written			The files this call wrote (the PDF, the electronic invoice) as the virtual path they are to have => pending (the absolute path they lie under now) and sha256, for the caller to give their names or to take back
		 *
		 *	@return 	true|string
		 */
		private static function finaliseIn( array &$appData, \PDO $pdo, int $id, array &$written ): true|string {

			$document = Documents::get( $appData, $id );

			if( $document === null )
				return 'unknown';

			if( $document['state'] !== 'draft' )
				return 'fixed';

			$plain = in_array( $document['category'], Documents::PLAIN, true );

			if( $plain === false && $document['lines'] === [] )
				return 'empty';

			$client = Clients::get( $appData, $document['client_id'] );

			if( $client === null || $client['active'] === false )
				return 'inactive';

			// An offer, an invoice and a credit note name the client with its address: a client that has not given it yet cannot be written one
			if( $plain === false && Documents::invoiceReady( $client ) === false )
				return 'unready';

			if( Crm::featureOn( $appData, 'pdf' ) === false )
				return 'pdf';

			$tag = Render::tag( $document['category'] );

			if( $document['template_key'] === '' || isset( \Nino\Modules\Pdf::templates( $appData, $tag )[$document['template_key']] ) === false )
				return 'template';

			foreach( \Nino\Modules\Pdf::inputs( $appData, $document['template_key'] ) as $input )
				if( $input['required'] === true && trim( (string) ( $document['inputs'][$input['name']] ?? '' ) ) === '' )
					return 'inputs:'. $input['name'];

			$frozen = self::freeze( $client );
			$now = gmdate( Documents::TIME );

			// A document without lines has no number and no totals: the PDF is named by its category and id, and a contract quotes its offer
			if( $plain === true ) {

				$sheet = self::sheet( $appData, $document, [], $frozen );

				if( $document['category'] === 'contract' && $sheet['offer'] === null )
					return 'offer';

				$made = Render::make( $appData, $sheet, [], $frozen );

				if( is_string( $made ) === true )
					return $made;

				$written[$made['path']] = [ 'pending' => $made['pending'], 'sha256' => $made['sha256'] ];

				$pdo->prepare( 'UPDATE documents SET state = \'open\', client_json = :client, finalised = :now, updated = :now, pdf_path = :path, pdf_sha256 = :sha WHERE id = :id AND state = \'draft\'' )
					->execute( [ 'client' => json_encode( $frozen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ), 'now' => $now, 'path' => $made['path'], 'sha' => $made['sha256'], 'id' => $id ] );

				return true;
			}

			// A document is dated today at the latest
			if( $document['date'] > Documents::latest() )
				return 'date:date';

			$totals = Money::totals( $document['lines'], $document['vat_mode'], $document['reverse_charge'] );

			// An invoice claims something: a negative one is a credit note, which takes an invoice back, and one of nothing would use a number for no claim; an offer is none below 0
			if( ( $document['category'] === 'invoice' && $totals['gross'] <= 0 ) || ( $document['category'] === 'offer' && $totals['gross'] < 0 ) )
				return 'gross';

			$parent = null;

			// A credit note takes back an invoice of its client that is open, sent or paid, dated no later than the credit note - and no more of it than it charged in any rate, together with the credit notes that were finalised before.
			// All of it is read in the transaction that numbers it
			if( $document['category'] === 'credit' ) {

				$parent = $document['parent_id'] === null ? null : Db::row( $appData, 'SELECT gross, state, client_id, date FROM documents WHERE id = :id AND category = \'invoice\'', [ 'id' => $document['parent_id'] ] );

				if( $parent === null || (int) $parent['client_id'] !== $document['client_id'] )
					return 'credit_parent';

				if( in_array( $parent['state'], [ 'open', 'sent', 'paid' ], true ) === false )
					return 'state';

				if( $document['date'] < $parent['date'] )
					return 'date:date';

				if( $totals['gross'] >= 0 )
					return 'credit_lines';

				$account = self::creditCheck( $appData, $document['parent_id'], $document['vat_mode'], $document['reverse_charge'], $document['lines'], $totals['amounts'], false );
				$account = is_string( $account ) === true ? $account : self::creditCheck( $appData, $document['parent_id'], $document['vat_mode'], $document['reverse_charge'], $document['lines'], $totals['amounts'], true );

				if( is_string( $account ) === true )
					return $account;

				// The tax of each rate is rounded on what the invoice has given back in that rate so far: the credit notes of an invoice come to its tax in every rate
				$totals = Money::totals( $document['lines'], $document['vat_mode'], $document['reverse_charge'], self::creditVat( $appData, $document, $document['lines'], $totals['amounts'] ) );
			}

			$number = Numbers::next( $appData, $pdo, $document['category'], (int) substr( $document['date'], 0, 4 ), Settings::get( $appData, Rules::FORMATS[$document['category']] ), Settings::start( $appData, $document['category'] ) );

			if( $number === null )
				return 'number';

			$lines = array_map( static fn( array $line, int $amount ): array => [ 'amount_net' => $amount ] + $line, $document['lines'], $totals['amounts'] );
			$statement = $pdo->prepare( 'UPDATE document_lines SET amount_net = :amount WHERE id = :id' );

			foreach( $lines as $line )
				$statement->execute( [ 'amount' => $line['amount_net'], 'id' => $line['id'] ] );

			$document = [ 'number' => $number, 'net' => $totals['net'], 'vat' => $totals['vat'], 'gross' => $totals['gross'], 'state' => 'open' ] + $document;

			$sheet = self::sheet( $appData, $document, $lines, $frozen );
			$made = Render::make( $appData, $sheet, $lines, $frozen );

			if( is_string( $made ) === true )
				return $made;

			$written[$made['path']] = [ 'pending' => $made['pending'], 'sha256' => $made['sha256'] ];
			$electronic = self::electronic( $appData, $sheet, $made['path'], $written );

			if( is_string( $electronic ) === true )
				return $electronic;

			$pdo->prepare( 'UPDATE documents SET number = :number, state = \'open\', net = :net, vat = :vat, gross = :gross, client_json = :client, finalised = :now, updated = :now, pdf_path = :path, pdf_sha256 = :sha,
				xml_path = :xml_path, xml_sha256 = :xml_sha, xml_lacks = :xml_lacks WHERE id = :id AND state = \'draft\'' )
				->execute( [ 'number' => $number, 'net' => $totals['net'], 'vat' => $totals['vat'], 'gross' => $totals['gross'], 'client' => json_encode( $frozen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ),
					'now' => $now, 'path' => $made['path'], 'sha' => $made['sha256'], 'id' => $id ] + $electronic );

			// The credit notes that take every rate of an invoice back, together, cancel it; one that takes a part leaves its state
			if( $parent !== null && ( $account['closes'] ?? false ) === true )
				$pdo->prepare( 'UPDATE documents SET state = \'cancelled\', updated = :now WHERE id = :id' )->execute( [ 'now' => $now, 'id' => $document['parent']['id'] ] );

			return true;
		}

		/**
		 *	The tax of each rate of a credit note, rounded on what its invoice has given back in that rate so far: the tax of the rate on the net given back with this credit note and the credit notes before it, less the tax
		 *	of the rate on the net given back before it. So each credit note is within a cent of its net times the rate, and the credit notes of an invoice come to the tax of the invoice in every rate. The credit notes before
		 *	are those finalised before this one, by the time and, within a second, by the number as a counter reads - the shorter first, so that G-9 comes before G-10 - (all of them while it is a draft)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			A credit note as get() has it (a draft, or finalised)
		 *	@param		array 		$lines				Its lines, each with vat_rate
		 *	@param		array 		$amounts			The net of each of its lines, in cents
		 *
		 *	@return 	array|null								rate => tax in cents; null for what is no credit note of an invoice, or has no tax to round
		 */
		public static function creditVat( array &$appData, array $document, array $lines, array $amounts ): ?array {

			if( $document['category'] !== 'credit' || $document['parent_id'] === null || $document['vat_mode'] !== 'regular' || $document['reverse_charge'] === true )
				return null;

			$finalised = (string) ( $document['finalised'] ?? '' );
			$before = [];

			foreach( Db::rows( $appData, 'SELECT l.qty, l.price_net, l.vat_rate FROM document_lines l JOIN documents d ON d.id = l.document_id WHERE d.parent_id = :parent AND d.category = \'credit\' AND d.state != \'draft\' AND d.id != :id
				AND ( :finalised = \'\' OR d.finalised < :finalised OR ( d.finalised = :finalised AND ( LENGTH( d.number ) < LENGTH( :number ) OR ( LENGTH( d.number ) = LENGTH( :number ) AND d.number < :number ) ) ) )', [ 'parent' => $document['parent_id'], 'id' => $document['id'], 'finalised' => $finalised, 'number' => (string) ( $document['number'] ?? '' ) ] ) as $line )
				$before[(int) $line['vat_rate']] = ( $before[(int) $line['vat_rate']] ?? 0 ) + Money::lineAmount( (int) $line['qty'], (int) $line['price_net'] );

			$own = [];

			foreach( array_values( $lines ) as $index => $line )
				$own[(int) $line['vat_rate']] = ( $own[(int) $line['vat_rate']] ?? 0 ) + (int) $amounts[$index];

			$vat = [];

			foreach( $own as $rate => $net )
				$vat[$rate] = Money::vat( ( $before[$rate] ?? 0 ) + $net, $rate ) - Money::vat( $before[$rate] ?? 0, $rate );

			return $vat;
		}

		/**
		 *	Whether the lines of a credit note give back what its invoice charged, and what the credit notes finalised before have taken. The credit note has the VAT mode and the reverse charge of the invoice,
		 *	every line is in a rate the invoice has, takes back the opposite way (a charge is taken back by a negative line, a discount by a positive one) and is no more than the largest line of the invoice that way
		 *	in that rate. The lines of a rate - those of this credit note and, with $cumulative, those of the credit notes finalised before - are no more than the lines of the invoice that way in that rate, and
		 *	take back no more net in a rate than the invoice charged there. All of it is measured net, in cents
		 *
		 *	@param		array 		&$appData
		 *	@param		int|null	$parentId			The invoice
		 *	@param		string		$vatMode
		 *	@param		bool			$reverse
		 *	@param		array 		$lines				Each with qty, price_net and vat_rate
		 *	@param		array 		$amounts			The net of each line, in cents
		 *	@param		bool			$cumulative		Whether the credit notes of the invoice that are finalised count
		 *
		 *	@return 	array|string							closes (whether every rate of the invoice is taken back in full with this one); or credit_parent (there is no such invoice), credit_lines, credit_over (with $cumulative: the credit notes together take back more than the invoice charged)
		 */
		public static function creditCheck( array &$appData, ?int $parentId, string $vatMode, bool $reverse, array $lines, array $amounts, bool $cumulative ): array|string {

			$invoice = $parentId === null ? null : Db::row( $appData, 'SELECT vat_mode, reverse_charge FROM documents WHERE id = :id AND category = \'invoice\'', [ 'id' => $parentId ] );

			if( $invoice === null )
				return 'credit_parent';

			if( $invoice['vat_mode'] !== $vatMode || ( (int) $invoice['reverse_charge'] === 1 ) !== $reverse )
				return 'credit_lines';

			$zero = $vatMode === 'small_business' || $reverse === true;
			$over = $cumulative === true ? 'credit_over' : 'credit_lines';
			$net = [];
			$charged = [ 'charge' => [], 'discount' => [] ];

			foreach( Db::rows( $appData, 'SELECT qty, price_net, vat_rate FROM document_lines WHERE document_id = :id', [ 'id' => $parentId ] ) as $line ) {

				$amount = Money::lineAmount( (int) $line['qty'], (int) $line['price_net'] );
				$rate = $zero === true ? 0 : (int) $line['vat_rate'];
				$net[$rate] = ( $net[$rate] ?? 0 ) + $amount;
				$kind = $amount < 0 ? 'discount' : 'charge';

				if( $amount !== 0 )
					$charged[$kind][$rate] = [ 'sum' => ( $charged[$kind][$rate]['sum'] ?? 0 ) + abs( $amount ), 'max' => max( $charged[$kind][$rate]['max'] ?? 0, abs( $amount ) ) ];
			}

			$given = [ 'charge' => [], 'discount' => [] ];
			$back = array_fill_keys( array_keys( $net ), 0 );

			if( $cumulative === true ) {

				foreach( Db::rows( $appData, 'SELECT l.qty, l.price_net, l.vat_rate FROM document_lines l JOIN documents d ON d.id = l.document_id WHERE d.parent_id = :id AND d.category = \'credit\' AND d.state != \'draft\'', [ 'id' => $parentId ] ) as $line ) {

					$amount = Money::lineAmount( (int) $line['qty'], (int) $line['price_net'] );
					$rate = $zero === true ? 0 : (int) $line['vat_rate'];
					$kind = $amount < 0 ? 'charge' : 'discount';
					$given[$kind][$rate] = ( $given[$kind][$rate] ?? 0 ) + abs( $amount );
					$back[$rate] = ( $back[$rate] ?? 0 ) + $amount;
				}
			}

			$own = [];

			foreach( $lines as $index => $line ) {

				$amount = (int) $amounts[$index];
				$rate = $zero === true ? 0 : (int) $line['vat_rate'];

				if( isset( $net[$rate] ) === false )
					return 'credit_lines';

				if( $amount === 0 )
					continue;

				// A negative line gives back a charge, a positive one a discount
				$kind = $amount < 0 ? 'charge' : 'discount';
				$own[$kind][$rate] = ( $own[$kind][$rate] ?? 0 ) + abs( $amount );
				$given[$kind][$rate] = ( $given[$kind][$rate] ?? 0 ) + abs( $amount );
				$back[$rate] += $amount;

				if( abs( $amount ) > ( $charged[$kind][$rate]['max'] ?? 0 ) || $own[$kind][$rate] > $charged[$kind][$rate]['sum'] )
					return 'credit_lines';

				if( $given[$kind][$rate] > $charged[$kind][$rate]['sum'] )
					return $over;
			}

			$closes = true;

			// In no rate is more net taken back than was charged, and from the charge of a rate and from nothing else
			foreach( $net as $rate => $charge ) {

				if( ( $charge >= 0 && $charge + $back[$rate] < 0 ) || ( $charge < 0 && $charge + $back[$rate] > 0 ) )
					return $over;

				$closes = $closes && $charge + $back[$rate] === 0;
			}

			return [ 'closes' => $closes ];
		}

		/**
		 *	The electronic invoice of a document that is being finalised: written beside its PDF where the document is an invoice or a credit of a business and nothing is
		 *	missing, otherwise only the list of what is missing is kept. Nothing is made up and no document is held back: it is finalised either way
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			As sheet() has it: with its number, totals, lines with their amounts and the frozen client
		 *	@param		string		$pdfPath			The virtual path of the PDF just written, which names the file
		 *	@param		array 		&$written			Gets the file this call wrote, as finaliseIn() has it
		 *
		 *	@return 	array|string							The values of xml_path, xml_sha and xml_lacks to store; or exists:<path>, write as XRechnung::make() has them
		 */
		private static function electronic( array &$appData, array $document, string $pdfPath, array &$written ): array|string {

			if( XRechnung::applies( $document ) === false )
				return [ 'xml_path' => '', 'xml_sha' => '', 'xml_lacks' => '' ];

			$lacks = XRechnung::lacks( $appData, $document );

			if( $lacks !== [] )
				return [ 'xml_path' => '', 'xml_sha' => '', 'xml_lacks' => json_encode( $lacks, JSON_THROW_ON_ERROR ) ];

			$made = XRechnung::make( $appData, $document, $pdfPath );

			if( is_string( $made ) === true )
				return $made;

			$written[$made['path']] = [ 'pending' => $made['pending'], 'sha256' => $made['sha256'] ];

			return [ 'xml_path' => $made['path'], 'xml_sha' => $made['sha256'], 'xml_lacks' => '[]' ];
		}

		/**
		 *	The client as a finalised document keeps it
		 *
		 *	@param		array 		$client				As Clients::get() has it
		 *
		 *	@return 	array											The fields of CLIENT_FIELDS as text; business is '1' or '0'
		 */
		private static function freeze( array $client ): array {

			$frozen = [];

			foreach( self::CLIENT_FIELDS as $field )
				$frozen[$field] = $field === 'business' ? ( $client[$field] === true ? '1' : '0' ) : (string) $client[$field];

			return $frozen;
		}

		/**
		 *	Take back the files a failed finalise() wrote (the PDF and the electronic invoice): they lie under pending names, which this call made
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$written			The virtual path => pending and sha256 of the file; none for nothing written
		 *
		 *	@return 	void
		 */
		private static function discard( array &$appData, array $written ): void {

			foreach( $written as $file )
				if( is_file( $file['pending'] ) === true && is_link( $file['pending'] ) === false )
					@unlink( $file['pending'] );
		}

		/**
		 *	A draft from another document: the same client, its lines multiplied by $factor
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$source				As get() has it
		 *	@param		string		$category
		 *	@param		int				$factor				1 or -1
		 *	@param		string		$vatMode			The mode of the draft: the source's own for a credit note (it returns what was charged), the setting's for an invoice
		 *
		 *	@param		bool			$joined				The caller has a transaction open, which the insert joins
		 *
		 *	@return 	array|string
		 */
		private static function derive( array &$appData, array $source, string $category, int $factor, string $vatMode, bool $joined ): array|string {

			$client = Clients::get( $appData, $source['client_id'] );

			if( $client === null )
				return 'client';

			if( $client['active'] === false )
				return 'inactive';

			$reverse = $source['reverse_charge'] === true && $vatMode === 'regular';
			$lines = [];

			foreach( $source['lines'] as $index => $line ) {

				$rate = $vatMode === 'small_business' || $reverse === true ? 0 : $line['vat_rate'];

				if( $category === 'invoice' && $vatMode === 'regular' && $reverse === false && Rules::rateAllowed( $appData, $rate, $vatMode ) === false )
					return 'line:'. $index. ':rate:vat_rate';

				$lines[] = [ 'qty' => $line['qty'] * $factor, 'vat_rate' => $rate ] + $line;
			}

			$totals = Money::totals( $lines, $vatMode, $reverse );
			$date = date( 'Y-m-d' );

			$values = [
				'category' => $category, 'client_id' => $source['client_id'], 'parent_id' => $source['id'], 'title' => '', 'vat_mode' => $vatMode, 'reverse_charge' => $reverse,
				'date' => $date, 'due_date' => $category === 'invoice' ? Documents::dayAfter( $appData, $date, 'payment_days' ) : null, 'valid_until' => null, 'period_from' => $source['period_from'], 'period_to' => $source['period_to'],
				'template_key' => '', 'inputs' => [], 'notes' => '', 'lines' => $lines, 'amounts' => $totals['amounts'], 'project' => Documents::projectKept( $appData, (string) $source['project'], $source['client_id'] ),
				'buyer_reference' => $category === 'credit' ? $source['buyer_reference'] : $client['buyer_reference'],
			] + $totals;

			return (array) Documents::get( $appData, Documents::insert( $appData, $values, 'draft', $joined ) );
		}

		/**
		 *	An invoice draft from an offer that is open, sent or accepted: the same client, the lines of the offer, the VAT mode of the
		 *	setting now (every rate is held to it: 0 in small_business, one of the list in regular, else line:<i>:rate:vat_rate). An offer
		 *	that is open or sent is accepted by it
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|string
		 */
		public static function fromOffer( array &$appData, int $id ): array|string {

			Projects::forget( $appData );

			// The invoice takes the VAT mode of the setting now, and the offer is accepted by making it: in one transaction, which
			// reads the offer as well, so an offer that was declined a moment ago is not made an invoice of
			$pdo = Db::pdo( $appData );
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$offer = Documents::get( $appData, $id );

				if( $offer === null || $offer['category'] !== 'offer' || in_array( $offer['state'], [ 'open', 'sent', 'accepted' ], true ) === false ) {
					$pdo->exec( 'ROLLBACK' );
					return $offer === null ? 'unknown' : 'state';
				}

				$draft = self::derive( $appData, $offer, 'invoice', 1, Settings::get( $appData, 'vat_mode' ), true );

				if( is_string( $draft ) === true ) {
					$pdo->exec( 'ROLLBACK' );
					return $draft;
				}

				$accepted = $pdo->prepare( 'UPDATE documents SET state = \'accepted\', updated = :now WHERE id = :id AND state IN ( \'open\', \'sent\', \'accepted\' )' );
				$accepted->execute( [ 'now' => gmdate( Documents::TIME ), 'id' => $offer['id'] ] );

				// An offer that changed its state since it was read is not the offer the invoice was made from
				if( $accepted->rowCount() === 0 ) {
					$pdo->exec( 'ROLLBACK' );
					return 'state';
				}

				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			return (array) Documents::get( $appData, $draft['id'] );
		}

		/**
		 *	A credit note draft from an invoice that is open, sent or paid: the lines negated
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|string
		 */
		public static function creditOf( array &$appData, int $id ): array|string {

			$invoice = Documents::get( $appData, $id );

			if( $invoice === null )
				return 'unknown';

			if( $invoice['category'] !== 'invoice' || in_array( $invoice['state'], [ 'open', 'sent', 'paid' ], true ) === false )
				return 'state';

			return self::derive( $appData, $invoice, 'credit', -1, $invoice['vat_mode'], false );
		}

		/**
		 *	A draft from a document of any state: the same kind and client, the lines (the rates held to the VAT mode of the setting now, as an invoice from an offer is),
		 *	the period and the notes left out, today as the date and the due or valid day the setting gives; the template and its inputs stay where the template is still there.
		 *	A contract hangs on the offer of the original. Nothing links the copy to the original
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|string							The draft as get() has it; or why not, see add() - state for a credit note, and line:<i>:rate:vat_rate for a rate the setting does not allow
		 */
		public static function copy( array &$appData, int $id ): array|string {

			$source = Documents::get( $appData, $id );

			if( $source === null )
				return 'unknown';

			// A credit note takes back an invoice: a copy would hang on none
			if( $source['category'] === 'credit' )
				return 'state';

			$tag = Render::tag( $source['category'] );
			$keeps = $source['template_key'] !== '' && Crm::featureOn( $appData, 'pdf' ) === true && isset( \Nino\Modules\Pdf::templates( $appData, $tag )[$source['template_key']] ) === true;
			$input = [ 'template_key' => $keeps === true ? $source['template_key'] : '', 'inputs' => $keeps === true ? $source['inputs'] : [], 'project' => Documents::projectKept( $appData, (string) $source['project'], $source['client_id'] ) ];

			if( in_array( $source['category'], Documents::PLAIN, true ) === true )
				return Documents::add( $appData, $source['category'], $source['category'] === 'contract' ? 0 : $source['client_id'], $input + [ 'title' => $source['title'] ] + ( $source['category'] === 'contract' ? [ 'parent_id' => $source['parent_id'] ] : [] ) );

			$vatMode = Settings::get( $appData, 'vat_mode' );
			$reverse = $source['reverse_charge'] === true && $vatMode === 'regular';
			$lines = [];

			foreach( $source['lines'] as $line ) {

				// A rate the setting does not list is refused by add(), as line:<i>:rate:vat_rate
				$rate = $vatMode === 'small_business' || $reverse === true ? 0 : $line['vat_rate'];
				$lines[] = [ 'title' => $line['title'], 'description' => $line['description'], 'unit' => $line['unit'], 'unit_code' => $line['unit_code'], 'qty' => $line['qty'], 'price' => $line['price_net'], 'vat_rate' => $rate ];
			}

			return Documents::add( $appData, $source['category'], $source['client_id'], $input + [ 'lines' => $lines, 'reverse_charge' => $reverse, 'buyer_reference' => $source['buyer_reference'] ] );
		}

		/**
		 *	The PDF of a draft as its template would make it now: nothing is stored, no number is taken
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|string							bytes (the PDF) and name (the file name without the extension); or unknown; state (it is no draft: its PDF is the stored one); pdf, template, inputs:<name>, render, words:<key>, markup:<path> as finalise() has them
		 */
		public static function preview( array &$appData, int $id ): array|string {

			$document = Documents::get( $appData, $id );

			if( $document === null )
				return 'unknown';

			if( $document['state'] !== 'draft' )
				return 'state';

			if( Crm::featureOn( $appData, 'pdf' ) === false )
				return 'pdf';

			if( $document['template_key'] === '' || isset( \Nino\Modules\Pdf::templates( $appData, Render::tag( $document['category'] ) )[$document['template_key']] ) === false )
				return 'template';

			foreach( \Nino\Modules\Pdf::inputs( $appData, $document['template_key'] ) as $input )
				if( $input['required'] === true && trim( (string) ( $document['inputs'][$input['name']] ?? '' ) ) === '' )
					return 'inputs:'. $input['name'];

			$client = Clients::get( $appData, $document['client_id'] );

			if( $client === null )
				return 'unknown';

			$frozen = self::freeze( $client );
			$plain = in_array( $document['category'], Documents::PLAIN, true );

			if( $plain === true )
				$document['offer'] = Documents::offerOf( $appData, $document );

			$totals = Money::totals( $document['lines'], $document['vat_mode'], $document['reverse_charge'] );
			$lines = $plain === true ? [] : array_map( static fn( array $line, int $amount ): array => [ 'amount_net' => $amount ] + $line, $document['lines'], $totals['amounts'] );

			// A word or a template the project lacks makes no PDF, and the preview says which
			try {
				$data = Render::data( $appData, $document, $lines, $frozen );
				$title = $plain === true ? $document['title'] : Render::text( $appData, 'title.'. $document['category'] );
			}
			catch( \OutOfBoundsException $e ) {
				trigger_error( 'Crm: the preview of document '. Render::name( $document ). ' was not made: '. $e->getMessage(), E_USER_WARNING );

				return Render::refusal( $e );
			}

			$bytes = \Nino\Modules\Pdf::render( $appData, $document['template_key'], $data, [ 'title' => $title, 'date' => $document['date'] ] );

			return $bytes === null ? 'render' : [ 'bytes' => $bytes, 'name' => Render::name( $document ) ];
		}
	}
}
