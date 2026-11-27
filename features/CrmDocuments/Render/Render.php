<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Format;
	use Nino\Modules\Crm\Settings;
	use Nino\Modules\Crm\Surface\Texts;

	/**
	 *	CrmDocuments\Render		The PDF of a finalised document: what the app hands a template of the Pdf feature (tokens, slots
	 *													and the inputs of the document), the file written once below /data/crm/pdf with its SHA-256, and
	 *													the route that answers it. The slots are filled from the templates of the project, /templates/pdf/crm-documents-<name>.tpl
	 *													(template()), and the words of the file are the texts /feature/crm-documents/pdf-<part>/<name> of its text file, in the native
	 *													language of the website (text()) - a document is the owner's letter to his client and has no language of its own. The
	 *													install unit of the module brings both add-only (Crm\Admin::applyUnits(), "Einrichten"), and nothing falls back to the unit.
	 *													A template or a word the project lacks (or a word that has a place its values do not fit) makes no PDF: data() and make() answer
	 *													that with an \OutOfBoundsException and a refusal (refusal()) that names it. Every value that
	 *													enters a slot is escaped.
	 *
	 *													Tokens are the slash keys of Nino's fills, handed to the Pdf feature flat ('/customer/name' =>
	 *													'...'), and a template writes them as [[/customer/name]]. A template of the tags offer and invoice
	 *													may use:
	 *													  /document/number, /title, /date, /net, /vat, /gross, /vatnote, /parent
	 *													  /document/period-line (an offer, an invoice and a credit note: the whole line "Service period: <from> - <to>" in the words of the project; without a period an invoice and a credit note say the sentence of /invoice/period,
	 *													  that the date of the service is the date of the invoice, and an offer has an empty line, so that a template gives it a block of its own and the offer shows no line; /invoice/period and /offer/period stay the period alone, with their own sentence where there is none)
	 *													  /invoice/due, /due-sentence, /period (an invoice and a credit note; the day of /due is empty where the document has none, and /due-sentence says
	 *													  that it is payable without deduction by <day> quoting the number of the invoice or, without a day, that it is payable at once)
	 *													  /offer/number, /date, /valid, /valid-sentence, /gross, /period (an offer: /valid is empty where the offer has no day, /valid-sentence says that the offer is valid until <day>
	 *													  or that it is subject to change; /period says "by arrangement" where it has none, as an invoice's says its own sentence)
	 *													  /customer/number, /company, /name, /street, /zip, /city, /country, /email, /vat
	 *													  /site/portal, /site/login (the addresses of the customer area and of the way in, which the portal says, and which are empty where nobody does)
	 *													  /site/tax, /site/bank, /site/iban, /site/bic (the invoice particulars of the settings: tax number, bank, IBAN, BIC; empty where unset)
	 *													Slots: address, lines, totals. Inputs: the ones the template declares.
	 *
	 *													A template of the tags contract, privacy and information (a document without lines and without a
	 *													number) has /document/title (the title of the document itself), /document/date, /document/parent
	 *													(a contract: the number of its offer), /customer/*, /site/* and the slot address. A contract also has
	 *													/offer/number, /date, /valid, /valid-sentence, /gross, /period and the slots lines and totals of its offer, so
	 *													that it can quote what it is about.
	 */
	class Render {

		public const string DIR = '/data/crm/pdf';

		// The path a stored PDF has: below DIR, a year, the number as file name
		private const string PATH = '~^/data/crm/pdf/[0-9]{4}/[A-Za-z0-9._-]{1,60}\.pdf$~D';

		private const string ROUTE = '/.crm/pdf/';

		/**	The code of the sentence that says the file of a document is missing	*/
		public const string MISSING = 'crm_documents_file_missing';

		/**	The middle of the name a file of the books has until its transaction is committed: no name of a document has it (the pattern of a path ends in .pdf or .xml), and its end is a number nobody else draws	*/
		public const string PENDING = '.pending-';

		// The folder of the markup in the project (the install unit brings it as templates/crm-pdf), and the start of the key of every word
		public const string TEMPLATES = '/templates/pdf';

		/** The class of the sheet of the Pdf feature that styles the address window; it is no class of the library of the site, so it is not written in the fragment */
		private const string ADDRESS_CLASS = ' class="address"';

		public const string PREFIX = 'crm-documents-';
		public const string WORDS = '/feature/crm-documents/pdf-';

		/**
		 *	The tag of the Pdf feature a category's templates carry: offer for an offer, invoice for an invoice and a credit note, and the
		 *	name of the category for a contract, a privacy document and an information document
		 *
		 *	@param		string		$category
		 *
		 *	@return 	string
		 */
		public static function tag( string $category ): string {

			return match( true ) {
				in_array( $category, Documents::PLAIN, true ) => $category,
				$category === 'offer'                         => 'offer',
				default                                       => 'invoice',
			};
		}

		/**
		 *	What a document is called in a file name and in a log line: its number, a document without one its category and id
		 *
		 *	@param		array 		$document			category, id, number
		 *
		 *	@return 	string
		 */
		public static function name( array $document ): string {

			return (string) $document['number'] !== '' ? (string) $document['number'] : $document['category']. '-'. $document['id'];
		}

		/**
		 *	A word of the documents, from the text file of the project in the native language of the website
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key below /project/crm-pdf/ with a dot for the slash: title.offer is /project/crm-pdf/title/offer
		 *	@param		string		...$params		Values for the %s of the word; a word that is given none is checked all the same
		 *
		 *	@return 	string
		 *
		 *	@throws		\OutOfBoundsException			The project has no such word, or one without a text, or one that does not fit its values: a document cannot say a key or leave a gap
		 */
		public static function text( array &$appData, string $key, string ...$params ): string {

			$path = self::WORDS. str_replace( '.', '/', $key );
			$locale = \Nino\Locales::getNativeLocale( $appData );
			$texts = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
			$text = is_array( $texts ) === true ? ( $texts['[['. $path. ']]'] ?? null ) : null;

			if( is_string( $text ) === false || trim( $text ) === '' )
				throw new \OutOfBoundsException( 'the document word '. $path. ' is missing or empty in /text/'. $locale. '.php' );

			// A word the project changed may carry any percent sign: only %s, %n$s and %% are places of the document (a %d would take a value in silence), and the places must not be more than the values
			if( preg_match( '/%(?!(?:\d+\$)?s)/', str_replace( '%%', '', $text ) ) === 1 )
				throw new \OutOfBoundsException( 'the document word '. $path. ' in /text/'. $locale. '.php does not fit its values: it has a place that is none of %s, %n$s and %%' );

			try {
				return vsprintf( $text, $params );
			}
			catch( \ValueError ) {
				throw new \OutOfBoundsException( 'the document word '. $path. ' in /text/'. $locale. '.php does not fit its values: it has more places than the document has values' );
			}
		}

		/**
		 *	A template of the slots, the markup of one of the slots: the template of the project, /templates/pdf/crm-documents-<name>.tpl, read through \Nino\Feature::template()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					A file of the folder without .tpl: lower case letters and hyphens
		 *
		 *	@return 	string										Without the newlines at its end
		 *
		 *	@throws		\OutOfBoundsException			The project has no such template, or an empty one: a document with a slot left out is none (Feature::template() says so in a warning, too)
		 */
		public static function template( array &$appData, string $name ): string {

			if( preg_match( '/^[a-z]+(-[a-z]+)*$/D', $name ) !== 1 )
				throw new \InvalidArgumentException( 'There is no document template "'. $name. '"' );

			$markup = \Nino\Feature::template( $appData, self::TEMPLATES, self::PREFIX. $name );

			if( $markup === '' )
				throw new \OutOfBoundsException( 'the document template '. self::TEMPLATES. '/'. self::PREFIX. $name. '.tpl is missing or empty' );

			return $markup;
		}

		/**
		 *	What a refusal says of a template or a word the project lacks: the kind and the thing, as Documents::finalise() and preview() answer them
		 *
		 *	@param		\OutOfBoundsException	$e			As text() and template() throw it
		 *
		 *	@return 	string										words:<key> (a word), markup:<path> (a template)
		 */
		public static function refusal( \OutOfBoundsException $e ): string {

			return preg_match( '/^the document (word|template) (\S+) /D', $e->getMessage(), $found ) === 1 ? ( $found[1] === 'word' ? 'words:' : 'markup:' ). $found[2] : 'markup:';
		}

		/**
		 *	A value for a slot: escaped, and the non-breaking space as the entity the Pdf lists keep it as
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		public static function escape( string $value ): string {

			return str_replace( Money::NBSP, '&nbsp;', \Nino\Components::escape( $value ) );
		}

		/**
		 *	The hook that asks other packages for the tokens of the site a document may carry: /site/portal and /site/login are the portal's, which says them where it is on
		 */
		public const string TOKENS = '/crm-documents/render/tokens';

		/**
		 *	The tokens /site/<name> a package adds to a document: the hook is asked with the id of the document and an empty list, and each listener puts its tokens into it. A token that is no /site/<word>, one that is no text
		 *	and one that another listener gave first is left out; /site/portal and /site/login are empty where nobody answers them, so that a template that names them draws nothing and never its own placeholder
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			id
		 *
		 *	@return 	array											/site/<name> => text
		 */
		private static function sites( array &$appData, array $document ): array {

			$tokens = [ '/site/portal' => '', '/site/login' => '' ];
			$ask = [ 'document' => (int) ( $document['id'] ?? 0 ), 'tokens' => [] ];

			foreach( \Nino\Callbacks::registered( $appData, self::TOKENS ) as $callback ) {

				$own = $ask;
				$returned = call_user_func_array( $callback, [ &$appData, &$own ] );
				$answer = is_array( $returned ) === true ? $returned : $own;

				foreach( is_array( $answer['tokens'] ?? null ) === true ? $answer['tokens'] : [] as $name => $value )
					if( is_string( $name ) === true && preg_match( '~^/site/[a-z]+(?:-[a-z]+)*$~D', $name ) === 1 && is_string( $value ) === true && ( isset( $tokens[$name] ) === false || $tokens[$name] === '' ) )
						$tokens[$name] = $value;
			}

			return $tokens;
		}

		/**
		 *	What Pdf::render() is handed for a document
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			As Documents::get() has it: category, number, dates, vat_mode, reverse_charge, totals, parent_number, inputs; a document without lines: title, and for a contract its offer (as get() has it) under offer
		 *	@param		array 		$lines				The lines of the document, in their order; none for a document without lines
		 *	@param		array 		$client				The client as it was frozen: company, name, street, zip, city, country, number, vat_id, email
		 *
		 *	@return 	array											tokens, slots, inputs
		 */
		public static function data( array &$appData, array $document, array $lines, array $client ): array {

			$currency = Settings::get( $appData, 'currency' );
			$category = (string) $document['category'];
			$plain = in_array( $category, Documents::PLAIN, true );

			$tokens = self::sites( $appData, $document );

			// The particulars of the owner's invoices, which the settings hold: the footer of the layout shows them (empty where unset)
			$particulars = Settings::all( $appData );

			foreach( [ 'tax' => 'tax_id', 'bank' => 'bank_name', 'iban' => 'iban', 'bic' => 'bic' ] as $token => $key )
				$tokens['/site/'. $token] = $particulars[$key];

			// The client's field vat_id is the token /customer/vat
			foreach( [ 'number', 'company', 'name', 'street', 'zip', 'city', 'country', 'email' ] as $field )
				$tokens['/customer/'. $field] = (string) ( $client[$field] ?? '' );

			$tokens['/customer/vat'] = (string) ( $client['vat_id'] ?? '' );

			$data = [
				'tokens' => $tokens,
				'slots'  => [ 'address' => self::address( $appData, $client ) ],
				'inputs' => array_map( 'strval', array_filter( (array) ( $document['inputs'] ?? [] ), 'is_string' ) ),
			];

			if( $plain === true ) {

				$offer = is_array( $document['offer'] ?? null ) === true ? $document['offer'] : null;
				$data['tokens']['/document/title'] = (string) $document['title'];
				$data['tokens']['/document/date'] = Format::day( (string) $document['date'] );
				$data['tokens']['/document/parent'] = $offer === null ? '' : (string) $offer['number'];

				if( $offer !== null ) {

					$regular = $offer['vat_mode'] === 'regular';
					$totals = Money::totals( $offer['lines'], (string) $offer['vat_mode'], (bool) $offer['reverse_charge'] );

					$data['tokens']['/offer/number'] = (string) $offer['number'];
					$data['tokens']['/offer/date'] = Format::day( (string) $offer['date'] );
					$data['tokens']['/offer/valid'] = Format::day( (string) ( $offer['valid_until'] ?? '' ) );
					$data['tokens']['/offer/valid-sentence'] = self::validSentence( $appData, (string) ( $offer['valid_until'] ?? '' ) );
					$data['tokens']['/offer/gross'] = Money::format( $totals['gross'], $currency );
					$offerPeriod = self::period( (string) ( $offer['period_from'] ?? '' ), (string) ( $offer['period_to'] ?? '' ) );
					$data['tokens']['/offer/period'] = $offerPeriod === '' ? self::text( $appData, 'period.none-offer' ) : $offerPeriod;
					$data['slots']['lines'] = self::lines( $appData, $offer['lines'], $totals, $regular, $currency );
					$data['slots']['totals'] = self::totals( $appData, $totals, $regular, $currency );
				}

				return $data;
			}

			$regular = $document['vat_mode'] === 'regular';
			$totals = Money::totals( $lines, (string) $document['vat_mode'], (bool) $document['reverse_charge'], $document['category'] === 'credit' ? Finalise::creditVat( $appData, $document, $lines, array_map( static fn( array $line ): int => Money::lineAmount( (int) $line['qty'], (int) $line['price_net'] ), array_values( $lines ) ) ) : null );

			$vatnote = '';

			if( $regular === false )
				$vatnote = self::text( $appData, 'vatnote.small-business' );
			elseif( (bool) $document['reverse_charge'] === true )
				$vatnote = self::text( $appData, 'vatnote.reverse' );

			$period = self::period( (string) ( $document['period_from'] ?? '' ), (string) ( $document['period_to'] ?? '' ) );
			$periodLine = $period === '' ? '' : self::text( $appData, 'period.line', $period );

			if( $period === '' ) {
				$period = self::text( $appData, $category === 'offer' ? 'period.none-offer' : 'period.none' );
				// An invoice names the time of the service, and says it is the day of the invoice where it has no period; an offer has no line
				$periodLine = $category === 'offer' ? '' : $period;
			}

			$data['tokens']['/document/title'] = self::text( $appData, 'title.'. $category );
			$data['tokens']['/document/period-line'] = $periodLine;
			$data['tokens']['/document/number'] = (string) $document['number'];
			$data['tokens']['/document/date'] = Format::day( (string) $document['date'] );
			$data['tokens']['/document/parent'] = (string) ( $document['parent_number'] ?? '' );
			$data['tokens']['/document/vatnote'] = $vatnote;
			$data['tokens']['/document/net'] = Money::format( $totals['net'], $currency );
			$data['tokens']['/document/vat'] = Money::format( $totals['vat'], $currency );
			$data['tokens']['/document/gross'] = Money::format( $totals['gross'], $currency );

			// An offer says what it is good until and what it comes to; an invoice and a credit note say when they are due and what they are for
			if( $category === 'offer' ) {
				$data['tokens']['/offer/number'] = (string) $document['number'];
				$data['tokens']['/offer/date'] = Format::day( (string) $document['date'] );
				$data['tokens']['/offer/valid'] = Format::day( (string) ( $document['valid_until'] ?? '' ) );
				$data['tokens']['/offer/valid-sentence'] = self::validSentence( $appData, (string) ( $document['valid_until'] ?? '' ) );
				$data['tokens']['/offer/gross'] = Money::format( $totals['gross'], $currency );
				$data['tokens']['/offer/period'] = $period;
			}
			else {
				$data['tokens']['/invoice/due'] = Format::day( (string) ( $document['due_date'] ?? '' ) );
				$data['tokens']['/invoice/due-sentence'] = self::dueSentence( $appData, (string) ( $document['due_date'] ?? '' ), (string) $document['number'] );
				$data['tokens']['/invoice/period'] = $period;
			}
			$data['slots']['lines'] = self::lines( $appData, $lines, $totals, $regular, $currency );
			$data['slots']['totals'] = self::totals( $appData, $totals, $regular, $currency );

			return $data;
		}

		/**
		 *	The block of the client: company, name, street, zip and city, the country when it is not DE - a line each
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client
		 *
		 *	@return 	string
		 */
		public static function address( array &$appData, array $client ): string {

			$country = (string) ( $client['country'] ?? '' );
			$lines = array_values( array_filter( [
				(string) ( $client['company'] ?? '' ),
				(string) ( $client['name'] ?? '' ),
				(string) ( $client['street'] ?? '' ),
				trim( ( $client['zip'] ?? '' ). ' '. ( $client['city'] ?? '' ) ),
				$country === 'DE' ? '' : Format::country( $country, \Nino\Locales::getNativeLocale( $appData ) ),
			], static fn( string $line ): bool => $line !== '' ) );

			$more = '';
			$line = self::template( $appData, 'address-line' );

			foreach( array_slice( $lines, 1 ) as $text )
				$more .= strtr( $line, [ '[[text]]' => self::escape( $text ) ] );

			return strtr( self::template( $appData, 'address' ), [ '[[class]]' => self::ADDRESS_CLASS, '[[first]]' => self::escape( $lines[0] ?? '' ), '[[more]]' => $more ] );
		}

		/**
		 *	The table of the lines; the column of the rate only where the document has VAT
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$lines
		 *	@param		array 		$totals				Money::totals()
		 *	@param		bool			$regular
		 *	@param		string		$currency
		 *
		 *	@return 	string
		 */
		public static function lines( array &$appData, array $lines, array $totals, bool $regular, string $currency ): string {

			$width = $regular === true ? '40%' : '48%';
			$rows = '';

			foreach( $lines as $index => $line ) {

				$description = (string) $line['description'] === '' ? '' : strtr( self::template( $appData, 'line-description' ), [ '[[text]]' => str_replace( "\n", self::template( $appData, 'line-break' ), self::escape( str_replace( "\r\n", "\n", (string) $line['description'] ) ) ) ] );
				$unit = (string) $line['unit'] === '' ? '' : ' '. $line['unit'];

				$rows .= strtr( self::template( $appData, 'line' ), [
					'[[title-width]]' => $width,
					'[[pos]]'         => (string) ( $index + 1 ),
					'[[title]]'       => self::escape( (string) $line['title'] ),
					'[[description]]' => $description,
					'[[qty]]'         => self::escape( Money::formatQty( (int) $line['qty'] ). $unit ),
					'[[price]]'       => self::escape( Money::format( (int) $line['price_net'], $currency ) ),
					'[[rate-cell]]'   => $regular === true ? strtr( self::template( $appData, 'line-rate' ), [ '[[rate]]' => self::escape( Money::formatRate( (int) $line['vat_rate'] ) ) ] ) : '',
					'[[amount]]'      => self::escape( Money::format( $totals['amounts'][$index], $currency ) ),
				] );
			}

			return strtr( self::template( $appData, 'lines' ), [
				'[[title-width]]' => $width,
				'[[head-pos]]'    => self::escape( self::text( $appData, 'head.pos' ) ),
				'[[head-title]]'  => self::escape( self::text( $appData, 'head.title' ) ),
				'[[head-qty]]'    => self::escape( self::text( $appData, 'head.qty' ) ),
				'[[head-price]]'  => self::escape( self::text( $appData, 'head.price' ) ),
				'[[rate-head]]'   => $regular === true ? strtr( self::template( $appData, 'lines-rate-head' ), [ '[[head-rate]]' => self::escape( self::text( $appData, 'head.rate' ) ) ] ) : '',
				'[[head-amount]]' => self::escape( self::text( $appData, 'head.amount' ) ),
				'[[rows]]'        => $rows,
			] );
		}

		/**
		 *	The totals: in regular the net, the VAT of each rate and the gross, in small_business the gross alone (it is the net)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$totals				Money::totals()
		 *	@param		bool			$regular
		 *	@param		string		$currency
		 *
		 *	@return 	string
		 */
		public static function totals( array &$appData, array $totals, bool $regular, string $currency ): string {

			$rows = '';

			if( $regular === true ) {

				$row = self::template( $appData, 'totals-rate' );
				$rows .= strtr( $row, [ '[[label]]' => self::escape( self::text( $appData, 'totals.net' ) ), '[[amount]]' => self::escape( Money::format( $totals['net'], $currency ) ) ] );

				foreach( $totals['rates'] as $rate => $sums )
					$rows .= strtr( $row, [
						'[[label]]'  => self::escape( self::text( $appData, 'totals.vat', Money::formatRate( $rate ), Money::format( $sums['net'], $currency ) ) ),
						'[[amount]]' => self::escape( Money::format( $sums['vat'], $currency ) ),
					] );
			}

			return strtr( self::template( $appData, 'totals' ), [
				'[[rows]]'        => $rows,
				'[[gross-label]]' => self::escape( self::text( $appData, 'totals.gross' ) ),
				'[[gross]]'       => self::escape( Money::format( $totals['gross'], $currency ) ),
			] );
		}

		/**
		 *	The bytes of the PDF of a document: the template it names, filled with its data - written nowhere. A word or a template the project lacks makes no PDF, and the refusal names it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			As make() has it
		 *	@param		array 		$lines
		 *	@param		array 		$client
		 *
		 *	@return 	array|string							bytes; or 'render' (Pdf made nothing, its warning says why), words:<key> or markup:<path> (see refusal())
		 */
		public static function bytes( array &$appData, array $document, array $lines, array $client ): array|string {

			try {
				$data = self::data( $appData, $document, $lines, $client );
				$title = in_array( $document['category'], Documents::PLAIN, true ) === true ? (string) $document['title'] : self::text( $appData, 'title.'. $document['category'] ). ' '. $document['number'];
			}
			catch( \OutOfBoundsException $e ) {
				trigger_error( 'Crm: the PDF of the document with the id '. (string) ( $document['id'] ?? '' ). ' was not made: '. $e->getMessage(), E_USER_WARNING );

				return self::refusal( $e );
			}

			$bytes = \Nino\Modules\Pdf::render( $appData, (string) $document['template_key'], $data, [ 'title' => $title, 'date' => (string) $document['date'] ] );

			return $bytes === null ? 'render' : [ 'bytes' => $bytes ];
		}

		/**
		 *	Render the PDF of a document with the template it names and keep it, once, below /data/crm/pdf
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$document			With its number (none for a document without lines); see data()
		 *	@param		array 		$lines
		 *	@param		array 		$client
		 *
		 *	@return 	array|string							path (virtual, the name the file gets once the transaction is committed), sha256 and pending (the absolute path of the file as it lies until then - see stage() and publish()); or 'render' (Pdf made nothing, its warning says why), words:<key> or markup:<path> (the project lacks a word or a template: see refusal()), 'exists:<path>' (the file is there already; the path is the virtual one) or 'write'
		 */
		public static function make( array &$appData, array $document, array $lines, array $client ): array|string {

			$year = (int) substr( (string) $document['date'], 0, 4 );
			$path = self::DIR. '/'. sprintf( '%04d', $year ). '/'. self::name( $document ). '.pdf';

			if( preg_match( self::PATH, $path ) !== 1 )
				return 'write';

			$file = \Nino\Filesystem::path( $appData, $path );

			// A file is written once: what lies at the name already - a file, a link, a folder, whoever wrote it and whatever a row says - is never touched, and the document is not finalised
			if( self::free( $appData, $path ) === false )
				return 'exists:'. $path;

			$made = self::bytes( $appData, $document, $lines, $client );

			if( is_string( $made ) === true )
				return $made;

			$bytes = $made['bytes'];

			$staged = self::stage( $appData, $path, $bytes );

			if( $staged === '' ) {
				trigger_error( 'Crm: the PDF of document '. self::name( $document ). ' could not be written', E_USER_WARNING );

				return 'write';
			}

			return [ 'path' => $path, 'sha256' => hash( 'sha256', $bytes ), 'pending' => $staged ];
		}

		/**
		 *	Whether the name of a file of the books is free: nothing lies there - no file, no link, no folder. A file at the name of a document that no row names is a document whose row is missing (a backup that was restored
		 *	older than the files), never the rest of a crash - what a crash leaves has another name (see stage()) - and it is not touched
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The virtual path of the PDF or of the electronic invoice
		 *
		 *	@return 	bool
		 */
		public static function free( array &$appData, string $path ): bool {

			$file = \Nino\Filesystem::path( $appData, $path );

			return $file !== '' && file_exists( $file ) === false && is_link( $file ) === false;
		}

		/**
		 *	What lies at the name of a file of the books, looked at without reading it (one lstat): none - nothing; other - a link, a folder, anything that is no regular file, or a file of no bytes; ok - a regular file with
		 *	bytes. The content is not compared: a file of another content and size is told only by lies(), which the download and the renewal of a file ask
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The virtual path a row names
		 *
		 *	@return 	string										none, other or ok
		 */
		public static function looks( array &$appData, string $path ): string {

			$file = \Nino\Filesystem::path( $appData, $path );

			if( $file === '' )
				return 'other';

			$stat = @lstat( $file );

			if( $stat === false )
				return 'none';

			return ( $stat['mode'] & 0170000 ) === 0100000 && $stat['size'] > 0 ? 'ok' : 'other';
		}

		/**
		 *	What lies at the name a row gives a file of the books: right - the file with the hash of the row; none - nothing (no file, no link, no folder); other - anything else: another file, a link or a folder.
		 *	Nothing is changed. Nothing that lies there is ever replaced, moved or deleted by the package, so what is other stays until the owner takes it away
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The virtual path a row names
		 *	@param		string		$hash					The sha256 the row holds for it
		 *
		 *	@return 	string										right, none or other
		 */
		public static function lies( array &$appData, string $path, string $hash ): string {

			$file = \Nino\Filesystem::path( $appData, $path );

			if( $file === '' )
				return 'other';

			if( is_link( $file ) === false && file_exists( $file ) === false )
				return 'none';

			return self::holds( $file, $hash ) === true ? 'right' : 'other';
		}

		/**
		 *	Write the bytes of a file of the books to a place no document can name: beside the name the file is to have, under that name and PENDING and a number nobody else draws, in a file that is made new and
		 *	never opened over one that is there. The file gets its name only after the transaction that holds its hash is committed (publish()): a process that dies before has left a file that blocks nothing, and one that dies
		 *	after has left a row whose file is found by its hash (settle()). The folder of the year is made with the file
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The virtual path below DIR the file is to have, which the pattern of a PDF or of an electronic invoice has
		 *	@param		string		$bytes
		 *
		 *	@return 	string										The absolute path of the file as it lies now; '' where it could not be written
		 */
		public static function stage( array &$appData, string $path, string $bytes ): string {

			if( preg_match( self::PATH, $path ) !== 1 && preg_match( XRechnung::PATH, $path ) !== 1 )
				return '';

			\Nino\Filesystem::forceDir( $appData, dirname( $path ) );

			$file = \Nino\Filesystem::path( $appData, $path );

			if( $file === '' || is_dir( dirname( $file ) ) === false )
				return '';

			$pending = $file. self::PENDING. bin2hex( random_bytes( 6 ) );
			$handle = @fopen( $pending, 'xb' );

			if( $handle === false )
				return '';

			$written = fwrite( $handle, $bytes );
			$closed = fclose( $handle );

			if( $written !== strlen( $bytes ) || $closed === false ) {
				@unlink( $pending );

				return '';
			}

			return $pending;
		}

		/**
		 *	Whether the file at a name is a regular file with the hash given: whoever put it there
		 *
		 *	@param		string		$file					The absolute path
		 *	@param		string		$hash					sha256
		 *
		 *	@return 	bool
		 */
		public static function holds( string $file, string $hash ): bool {

			return $file !== '' && is_file( $file ) === true && is_link( $file ) === false && preg_match( '/^[0-9a-f]{64}$/D', $hash ) === 1 && hash_equals( $hash, (string) @hash_file( 'sha256', $file ) ) === true;
		}

		/**
		 *	Give a file its name: the file that stage() wrote is linked to the name of the document - made only where the name is free, so that a file that lies there is never replaced - and the pending name goes. Where the name is
		 *	taken, what counts is whether the file that lies there is the one with the hash that was stored: another process may have given the same file its name first, and then the pending name has gone or goes now
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The virtual path the file is to have
		 *	@param		string		$pending			The absolute path stage() answered
		 *	@param		string		$hash					The sha256 of the file, as it is stored
		 *
		 *	@return 	bool											Whether the file with this hash lies at the name now; false leaves the pending file where it is
		 */
		public static function publish( array &$appData, string $path, string $pending, string $hash ): bool {

			$file = \Nino\Filesystem::path( $appData, $path );

			if( ( preg_match( self::PATH, $path ) !== 1 && preg_match( XRechnung::PATH, $path ) !== 1 ) || $file === '' )
				return false;

			if( is_file( $pending ) === true && is_link( $pending ) === false && @link( $pending, $file ) === true ) {
				@unlink( $pending );

				return true;
			}

			if( self::holds( $file, $hash ) === false )
				return false;

			if( is_file( $pending ) === true && is_link( $pending ) === false && self::holds( $pending, $hash ) === true )
				@unlink( $pending );

			return true;
		}

		/**
		 *	Give the file of a committed document its name where a process died between the commit and the name: the file that lies beside the name under a pending name and has the hash the row holds is linked to the name. A file
		 *	with another hash is not the file of the row and is left; nothing is deleted but the pending name of the file that was linked, or that lies beside the right file
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The virtual path a row names
		 *	@param		string		$hash					The sha256 the row holds for it
		 *
		 *	@return 	bool											Whether the file with that hash lies at the name now (it did, or it was given the name, or another process gave it)
		 */
		public static function settle( array &$appData, string $path, string $hash ): bool {

			$file = \Nino\Filesystem::path( $appData, $path );

			if( ( preg_match( self::PATH, $path ) !== 1 && preg_match( XRechnung::PATH, $path ) !== 1 ) || $file === '' || preg_match( '/^[0-9a-f]{64}$/D', $hash ) !== 1 )
				return false;

			foreach( glob( $file. self::PENDING. '*' ) ?: [] as $pending )
				if( preg_match( '/^[0-9a-f]{12}$/D', substr( $pending, strlen( $file. self::PENDING ) ) ) === 1 && self::holds( $pending, $hash ) === true && self::publish( $appData, $path, $pending, $hash ) === true )
					return true;

			return self::holds( $file, $hash );
		}

		/**
		 *	Settle every file that lies under a pending name and belongs to a row: the file of a document whose process died after its commit gets its name, and a pending name that lies beside the right file - the last step of
		 *	publish() was not done - goes, for settle() gives it to publish(), which takes it away where the name holds the right file. A pending file that no row names is left to purge()
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int												How many files lie at their name with the hash of their row now
		 */
		public static function settleAll( array &$appData ): int {

			$settled = 0;
			$done = [];

			foreach( glob( \Nino\Filesystem::path( $appData, self::DIR ). '/[0-9][0-9][0-9][0-9]/*'. self::PENDING. '*' ) ?: [] as $pending ) {

				$at = strrpos( $pending, self::PENDING );
				$file = substr( $pending, 0, (int) $at );
				$path = self::DIR. '/'. basename( dirname( $file ) ). '/'. basename( $file );

				if( isset( $done[$path] ) === true )
					continue;

				$done[$path] = true;
				$row = Db::row( $appData, 'SELECT pdf_path, pdf_sha256, xml_sha256 FROM documents WHERE pdf_path = :pdf OR xml_path = :xml', [ 'pdf' => $path, 'xml' => $path ] );

				if( $row === null )
					continue;

				$settled += self::settle( $appData, $path, (string) ( $row['pdf_path'] === $path ? $row['pdf_sha256'] : $row['xml_sha256'] ) ) === true ? 1 : 0;
			}

			return $settled;
		}

		/**
		 *	Delete the files under pending names whose hash no row holds: what an attempt that was cut off before its commit left - it carries the address and the mail of a client, and is the content of a draft that lies in the
		 *	database unchanged. It is asked under the write lock of a finalising or of the deletion of a client, and so it is free of races: a file is staged only under that lock, and once the transaction is committed its hash
		 *	is in a row. A file whose hash a row holds is never deleted: it is the document, which needs only its name. Nothing is lost where an older backup was restored: a pending file of a document finalised after the
		 *	backup has no row any more, but the document was never handed out - every download, status, export and finalising gives such a file its name before - and the draft it was made from is in the restored database;
		 *	where it had got its name already the file lies under the name of the document, and that is not touched here
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int												How many files were deleted
		 */
		public static function purge( array &$appData ): int {

			$deleted = 0;
			$known = [];

			foreach( Db::rows( $appData, 'SELECT pdf_sha256, xml_sha256 FROM documents WHERE pdf_sha256 <> :none OR xml_sha256 <> :none', [ 'none' => '' ] ) as $row ) {
				$known[(string) $row['pdf_sha256']] = true;
				$known[(string) $row['xml_sha256']] = true;
			}

			foreach( glob( \Nino\Filesystem::path( $appData, self::DIR ). '/[0-9][0-9][0-9][0-9]/*'. self::PENDING. '*' ) ?: [] as $pending ) {

				if( preg_match( '/\.(pdf|xml)'. preg_quote( self::PENDING, '/' ). '[0-9a-f]{12}$/D', $pending ) !== 1 || is_file( $pending ) === false || is_link( $pending ) === true )
					continue;

				$hash = (string) @hash_file( 'sha256', $pending );

				if( $hash !== '' && isset( $known[$hash] ) === false && @unlink( $pending ) === true )
					$deleted++;
			}

			return $deleted;
		}

		/**
		 *	The file of a stored PDF
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					As the document stores it
		 *
		 *	@return 	string|null								The absolute path; null for a path the module did not write and for a file that is not there
		 */
		public static function file( array &$appData, string $path ): ?string {

			if( preg_match( self::PATH, $path ) !== 1 )
				return null;

			$file = \Nino\Filesystem::path( $appData, $path );

			return $file !== '' && is_file( $file ) === true && is_link( $file ) === false ? $file : null;
		}

		/**
		 *	GET /.crm/pdf/<id>: the PDF of a finalised document, to a workbench session that holds the permission of the CRM. The id is a number or the answer is the 404; so are a draft, a document nobody has, a file that is gone
		 *	and a visitor. A file whose hash is not the one stored is a 409 with a line in the log that names the number (a document without one: its category and id). ?download=1 is saved under the name of the file, else it is shown.
		 *	The route is the owner's alone: no address of the files below /data is served, and this is the only way to a file
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackDownload( array &$appData, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$id = str_starts_with( $uri, self::ROUTE ) === true ? substr( $uri, strlen( self::ROUTE ) ) : '';
			$row = null;

			if( preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) === 1 ) {

				try {
					if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === true )
						$row = Db::row( $appData, 'SELECT pdf_path, pdf_sha256 FROM documents WHERE id = :id AND pdf_path != \'\'', [ 'id' => (int) $id ] );
				}
				catch( \PDOException $e ) {

					// A database that cannot be read is no document that is not there
					self::answer( $request, 503, Texts::word( $appData, 'error.crm_database' ) );

					return;
				}
			}

			$file = $row === null ? null : self::file( $appData, (string) $row['pdf_path'] );

			// A process that died between the commit and the name left the file under a pending name: it is given its name where its hash is the one the row holds
			if( $row !== null && $file === null && self::settle( $appData, (string) $row['pdf_path'], (string) $row['pdf_sha256'] ) === true )
				$file = self::file( $appData, (string) $row['pdf_path'] );

			$name = $row === null ? '' : basename( (string) $row['pdf_path'], '.pdf' );

			if( $row === null ) {
				self::answer( $request, 404, 'Not Found' );

				return;
			}

			// The document is there and its file is not: that is told, and no other file is sent
			if( $file === null ) {
				trigger_error( 'Crm: the PDF of document '. $name. ' is gone', E_USER_WARNING );
				self::answer( $request, 409, Texts::word( $appData, 'error.'. self::MISSING ) );

				return;
			}

			if( hash_equals( (string) $row['pdf_sha256'], (string) hash_file( 'sha256', $file ) ) === false ) {
				trigger_error( 'Crm: the PDF of document '. $name. ' does not match its hash', E_USER_WARNING );
				self::answer( $request, 409, 'Conflict' );

				return;
			}

			$query = $request['/nino/http/request']['query'] ?? [];
			$saved = is_array( $query ) === true && ( $query['download'] ?? null ) === '1';

			$request['/nino/http/response']['file'] = $file;
			$request['/nino/http/response']['header']['Content-Type'] = 'application/pdf';
			$request['/nino/http/response']['header']['Content-Disposition'] = ( $saved === true ? 'attachment' : 'inline' ). '; filename="'. $name. '.pdf"';
			$request['/nino/http/response']['header']['Cache-Control'] = 'private, no-store';
			$request['/nino/http/response']['header']['X-Content-Type-Options'] = 'nosniff';
		}

		/**
		 *	A plain-text answer that is no file
		 *
		 *	@param		array 		&$request
		 *	@param		int				$status
		 *	@param		string		$body
		 *
		 *	@return 	void
		 */
		public static function answer( array &$request, int $status, string $body ): void {

			$request['/nino/http/response']['statusCode'] = $status;
			$request['/nino/http/response']['body'] = $body;
			$request['/nino/http/response']['raw'] = true;
			$request['/nino/http/response']['header']['Content-Type'] = 'text/plain; charset=utf-8';
			$request['/nino/http/response']['header']['Cache-Control'] = 'no-store';
		}

		/**
		 *	The sentence of the payment terms: with the day the invoice is due, or without one
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$date					Y-m-d, or '' for an invoice with no due date
		 *	@param		string		$number				The number of the invoice
		 *
		 *	@return 	string
		 */
		public static function dueSentence( array &$appData, string $date, string $number ): string {

			$day = Format::day( $date );

			return $day === '' ? self::text( $appData, 'due.sentence-none', $number ) : self::text( $appData, 'due.sentence', $day, $number );
		}

		/**
		 *	The sentence of the validity of an offer: with the day it is good until, or without one
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$date					Y-m-d, or '' for an offer with no day
		 *
		 *	@return 	string
		 */
		public static function validSentence( array &$appData, string $date ): string {

			$day = Format::day( $date );

			return $day === '' ? self::text( $appData, 'valid.sentence-none' ) : self::text( $appData, 'valid.sentence', $day );
		}

		/**
		 *	The period of a service: both days or none
		 *
		 *	@param		string		$from
		 *	@param		string		$to
		 *
		 *	@return 	string
		 */
		public static function period( string $from, string $to ): string {

			return $from === '' || $to === '' ? '' : Format::day( $from ). ' - '. Format::day( $to );
		}
	}
}
