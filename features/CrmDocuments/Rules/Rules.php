<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Settings;

	/**
	 *	CrmDocuments\Rules		The settings of the documents: the keys the documents enter in the settings of the CRM (register(), in the init of the package), each with its default and its rule, and the rules themselves.
	 *											The VAT mode and the rates of a line, the days a payment and an offer are open, the currency, the number formats and the start numbers of the three categories, the particulars of the seller an invoice names (the tax number, the bank,
	 *											the IBAN, the BIC, PayPal, the address, the contact, the VAT id and the text of the exemption) and the wordmark of the mails. A rule that fails answers [ 'value:<key>' ], a number format that holds
	 *											no counter [ 'format:<key>' ]. The key seller_name, the name of the owner, is the core's.
	 */
	class Rules {

		// The VAT modes
		public const array VAT_MODES = [ 'small_business', 'regular' ];

		// The number format of a category
		public const array FORMATS = [ 'offer' => 'number_offer', 'invoice' => 'number_invoice', 'credit' => 'number_credit' ];

		// The start number of a category: the counter the first document of a year gets, and the least a sequence gives from then on
		public const array STARTS = [ 'offer' => 'start_offer', 'invoice' => 'start_invoice', 'credit' => 'start_credit' ];

		public const int MAX_DAYS = 365;
		public const int MAX_RATE = 10000;
		public const int MAX_FORMAT = 40;
		public const int MAX_WIDTH = 9;
		public const int MAX_START = 999999999;
		public const int MAX_TAX = 40;
		public const int MAX_BANK = 80;

		// The seller's particulars an electronic invoice names (the block Rechnungsaussteller): the longest value each takes. The text of the exemption is used under the VAT mode small_business only
		public const array SELLER_TEXTS = [ 'seller_street' => 120, 'seller_zip' => 12, 'seller_city' => 80, 'seller_contact' => 120, 'vat_exempt_text' => 200 ];

		// The pattern of the seller's VAT id: the one of the clients'
		public const string VAT_ID = '/^[A-Z]{2}[A-Za-z0-9]{2,18}$/D';

		// The keys of the documents: the default each has, the key of the core it stands in front of and where among the keys that stand there. The keys of the numbers stand in front of the start number of the clients, the
		// particulars of the bank in front of the name of the owner and the particulars of the seller behind it, in front of the wordmark of the mails - a key of the core is all a key of a package can stand in front of
		private const array KEYS = [
			'vat_mode' => [ 'small_business', 'start_client', 10 ], 'vat_rates' => [ '1900,700,0', 'start_client', 20 ], 'payment_days' => [ '14', 'start_client', 30 ], 'offer_days' => [ '30', 'start_client', 40 ], 'currency' => [ '€', 'start_client', 50 ],
			'number_offer' => [ 'A-{YYYY}-{NNNN}', 'start_client', 60 ], 'number_invoice' => [ 'R-{YYYY}-{NNNN}', 'start_client', 70 ], 'number_credit' => [ 'G-{YYYY}-{NNNN}', 'start_client', 80 ],
			'start_offer' => [ '1', 'start_client', 90 ], 'start_invoice' => [ '1', 'start_client', 100 ], 'start_credit' => [ '1', 'start_client', 110 ],
			'tax_id' => [ '', 'seller_name', 10 ], 'bank_name' => [ '', 'seller_name', 20 ], 'iban' => [ '', 'seller_name', 30 ], 'bic' => [ '', 'seller_name', 40 ], 'paypal' => [ '', 'seller_name', 50 ],
			'seller_street' => [ '', 'brand_word', 10 ], 'seller_zip' => [ '', 'brand_word', 20 ], 'seller_city' => [ '', 'brand_word', 30 ], 'seller_country' => [ 'DE', 'brand_word', 40 ], 'seller_contact' => [ '', 'brand_word', 50 ],
			'seller_phone' => [ '', 'brand_word', 60 ], 'seller_email' => [ '', 'brand_word', 70 ], 'vat_id' => [ '', 'brand_word', 80 ], 'vat_exempt_text' => [ '', 'brand_word', 90 ],
		];

		// The code an invoice particular is refused with where it has one of its own; every other key answers crm_documents_setting, the number formats crm_documents_numberformat
		private const array REFUSALS = [
			'tax_id' => 'crm_documents_particular_tax_id', 'bank_name' => 'crm_documents_particular_bank_name', 'iban' => 'crm_documents_particular_iban', 'bic' => 'crm_documents_particular_bic', 'paypal' => 'crm_documents_particular_paypal',
			'seller_street' => 'crm_documents_particular_seller_street', 'seller_zip' => 'crm_documents_particular_seller_zip', 'seller_city' => 'crm_documents_particular_seller_city', 'seller_country' => 'crm_documents_particular_seller_country',
			'seller_contact' => 'crm_documents_particular_seller_contact', 'seller_phone' => 'crm_documents_particular_seller_phone', 'seller_email' => 'crm_documents_particular_seller_email', 'vat_id' => 'crm_documents_particular_vat_id',
			'vat_exempt_text' => 'crm_documents_particular_vat_exempt_text', 'number_offer' => 'crm_documents_numberformat', 'number_invoice' => 'crm_documents_numberformat', 'number_credit' => 'crm_documents_numberformat',
		];

		/**
		 *	Enter the keys of the documents in the settings of the module
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool											Whether the settings took them (Settings::add())
		 */
		public static function register( array &$appData ): bool {

			$keys = [];

			foreach( self::KEYS as $key => [ $default, $before, $rank ] )
				$keys[] = [ 'package' => 'crm-documents', 'key' => $key, 'default' => $default, 'before' => $before, 'rank' => $rank, 'clean' => [ self::class, 'clean' ], 'refusal' => self::REFUSALS[$key] ?? 'crm_documents_setting' ];

			return Settings::add( $appData, $keys );
		}

		/**
		 *	'/crm/element/start': what a new item starts with and what its form leaves out - it has the highest rate of the settings, in percent, and the owner who is a small business under section 19 of the VAT act has no rate to
		 *	enter (the line of a document writes 0 whatever the item says)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				type, defaults and hidden
		 *
		 *	@return 	void
		 */
		public static function callbackStart( array &$appData, array &$args ): void {

			if( ( $args['type'] ?? null ) !== 'crm-items' )
				return;

			try {
				if( Settings::get( $appData, 'vat_mode' ) === 'small_business' )
					$args['hidden'] = [ 'vat' ];
				elseif( self::rates( $appData ) !== [] )
					$args['defaults'] = [ 'vat' => max( self::rates( $appData ) ) / 100 ];
			}
			catch( \PDOException $e ) {
				// The settings cannot be read: the form starts empty and hides nothing, and the log says why
				trigger_error( 'CrmDocuments: the settings of the documents could not be read for the form of an item', E_USER_WARNING );
			}
		}

		/**
		 *	Whether two number formats give the same number - for the year it is now and one counter, without a difference of the case: the counters at which a width of the counter changes are the ones that are tried. A file system
		 *	that does not tell R-2026-0001 from r-2026-0001 would put two documents on one file
		 *
		 *	What is looked at is the same counter in the year it is now, and nothing more: R-{YYYY}-{NNNN} against R-2026-{NNNN} (written out) and against r-{YYYY}-{NNNN}, R-{YYYY}-{NNN}. What is not: formats that meet at another counter
		 *	(R-1{NNN} and R-{NNNN}) or in another year (R-2027-{NNNN}), and a format that meets the name of a privacy document or of another document without lines (privacy-{N}, whose id is fixed). Those stay what they were: the number
		 *	that is given already is refused at the finalising - 409 crm_documents_number, or crm_documents_exists where a file has the name - and is never given twice (the number is unique, without a difference of the case, and
		 *	taken under the write lock)
		 *
		 *	@param		string		$one
		 *	@param		string		$other
		 *
		 *	@return 	bool
		 */
		public static function collide( string $one, string $other ): bool {

			if( self::validFormat( $one ) === false || self::validFormat( $other ) === false )
				return false;

			$year = (int) gmdate( 'Y' );

			foreach( [ 1, 9, 10, 99, 100, 999, 1000, 9999, 10000, 99999, 100000, 999999, 1000000, 9999999, 10000000, 99999999, 100000000, 999999999 ] as $counter )
				if( strtolower( Numbers::render( $one, $year, $counter ) ) === strtolower( Numbers::render( $other, $year, $counter ) ) )
					return true;

			return false;
		}

		/**
		 *	The value a key of the documents takes, or why not
		 *
		 *	@param		string		$key
		 *	@param		string		$value				Trimmed
		 *
		 *	@return 	string|array							The text to store; or [ code ] for a value that fails
		 */
		public static function clean( string $key, string $value ): string|array {

			switch( $key ) {

				case 'vat_mode':
					return Settings::oneOf( $key, $value, self::VAT_MODES );

				case 'vat_rates':
					$rates = self::parseRates( $value );

					return $rates === null ? [ 'value:'. $key ] : implode( ',', $rates );

				case 'payment_days':
				case 'offer_days':
					return preg_match( '/^(0|[1-9][0-9]{0,2})$/D', $value ) === 1 && (int) $value <= self::MAX_DAYS ? $value : [ 'value:'. $key ];

				// The start number of a sequence: a whole number from 1 with at most nine digits - as wide as the counter of a format gets. Zeros in front are no part of it
				case 'start_offer':
				case 'start_invoice':
				case 'start_credit':
					return preg_match( '/^[0-9]{1,9}$/D', $value ) === 1 && (int) $value >= 1 ? (string) (int) $value : [ 'value:'. $key ];

				case 'currency':
					return $value !== '' && mb_strlen( $value, 'UTF-8' ) <= 5 && preg_match( '/^[\p{L}\p{Sc}.]+$/Du', $value ) === 1 ? $value : [ 'value:'. $key ];

				// The particulars of an invoice: each may stay empty, and what is written is a plausible one
				case 'tax_id':
					return mb_strlen( $value, 'UTF-8' ) <= self::MAX_TAX && preg_match( '/^[\p{L}\p{N} .\/-]*$/Du', $value ) === 1 ? $value : [ 'value:'. $key ];

				case 'seller_street':
				case 'seller_city':
				case 'seller_contact':
				case 'vat_exempt_text':
					return mb_strlen( $value, 'UTF-8' ) <= self::SELLER_TEXTS[$key] && preg_match( '/^[^\p{C}]*$/Du', $value ) === 1 ? $value : [ 'value:'. $key ];

				case 'seller_zip':
					return mb_strlen( $value, 'UTF-8' ) <= self::SELLER_TEXTS[$key] && ( $value === '' || preg_match( '/^[A-Za-z0-9][A-Za-z0-9 -]*$/D', $value ) === 1 ) ? $value : [ 'value:'. $key ];

				// The country is a code of two capitals, as the clients' are; the seller's phone is written as theirs
				case 'seller_country':
					$value = strtoupper( $value );

					return $value === '' || preg_match( '/^[A-Z]{2}$/D', $value ) === 1 ? $value : [ 'value:'. $key ];

				case 'seller_phone':
					return mb_strlen( $value, 'UTF-8' ) <= 40 && ( $value === '' || preg_match( '/^[0-9+][0-9 +()\/.-]*$/D', $value ) === 1 ) ? $value : [ 'value:'. $key ];

				case 'seller_email':
					return mb_strlen( $value, 'UTF-8' ) <= 160 && ( $value === '' || filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false ) ? $value : [ 'value:'. $key ];

				case 'vat_id':
					$value = strtoupper( str_replace( ' ', '', $value ) );

					return $value === '' || preg_match( self::VAT_ID, $value ) === 1 ? $value : [ 'value:'. $key ];

				case 'bank_name':
					return mb_strlen( $value, 'UTF-8' ) <= self::MAX_BANK && preg_match( '/^[^\p{C}]*$/Du', $value ) === 1 ? $value : [ 'value:'. $key ];

				case 'iban':
					$iban = self::iban( $value );

					return $iban === null ? [ 'value:'. $key ] : $iban;

				// The name of PayPal.me, nothing else: the address https://paypal.me/<name> or https://www.paypal.com/paypalme/<name> is cut down to it, and the link of the portal is made of the name
				case 'paypal':
					$name = preg_match( '#^https://(?:paypal\.me|www\.paypal\.com/paypalme)/([^/?\#]*)/?$#Di', $value, $found ) === 1 ? $found[1] : $value;

					return $value === '' || preg_match( '/^[A-Za-z0-9]{1,20}$/D', $name ) === 1 ? $name : [ 'value:'. $key ];

				case 'bic':
					$bic = strtoupper( (string) preg_replace( '/\s+/', '', $value ) );

					return $bic === '' || preg_match( '/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}(?:[A-Z0-9]{3})?$/D', $bic ) === 1 ? $bic : [ 'value:'. $key ];
			}

			// The number format of a category
			return self::validFormat( $value ) === true ? $value : [ 'format:'. $key ];
		}

		/**
		 *	The rates as a person writes them, in percent: the form of the screen, stored as the hundredths the key vat_rates holds. The answer of the screen's field vat_rates_percent
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	string|array							The text to store; or [ 'value:vat_rates_percent' ] for a value that fails
		 */
		public static function percent( mixed $value ): string|array {

			if( is_string( $value ) === false && is_int( $value ) === false )
				return [ 'value:vat_rates_percent' ];

			$rates = self::parsePercent( trim( (string) $value ) );

			return $rates === null ? [ 'value:vat_rates_percent' ] : implode( ',', $rates );
		}

		/**
		 *	The sentence that says why no VAT is charged under the VAT mode small_business: the text of the settings, and where that is empty the word of the PDF (/feature/crm-documents/pdf-vatnote/small-business), which is the project's text in the native
		 *	language of the website - the sentence a PDF of the same document carries
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										'' where the settings have none and the project has no such word
		 */
		public static function exemption( array &$appData ): string {

			$set = Settings::get( $appData, 'vat_exempt_text' );

			if( $set !== '' )
				return $set;

			try {
				return Render::text( $appData, 'vatnote.small-business' );
			}
			catch( \OutOfBoundsException $e ) {
				return '';
			}
		}

		/**
		 *	The rates the regular mode offers
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array										Hundredths of a percent, in the order the setting lists them
		 */
		public static function rates( array &$appData ): array {

			return self::parseRates( Settings::get( $appData, 'vat_rates' ) ) ?? [];
		}

		/**
		 *	Whether a line may carry a rate: 0 in small_business, one of the rates of the project in regular
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$rate
		 *	@param		string		$vatMode
		 *
		 *	@return 	bool
		 */
		public static function rateAllowed( array &$appData, int $rate, string $vatMode ): bool {

			return $vatMode === 'small_business' ? $rate === 0 : in_array( $rate, self::rates( $appData ), true );
		}

		/**
		 *	An IBAN as it is written on an invoice: capitals in groups of four. The check is the one every bank makes - the country and the check
		 *	digits, 15 to 34 characters, and the remainder 1 of the number the letters make, divided by 97 - not a list of the countries
		 *
		 *	@param		string		$text					As typed: blanks anywhere
		 *
		 *	@return 	string|null								'' for nothing; null for a text that is no IBAN
		 */
		public static function iban( string $text ): ?string {

			$iban = strtoupper( (string) preg_replace( '/\s+/', '', $text ) );

			if( $iban === '' )
				return '';

			if( preg_match( '/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/D', $iban ) !== 1 )
				return null;

			$digits = '';

			foreach( str_split( substr( $iban, 4 ). substr( $iban, 0, 4 ) ) as $char )
				$digits .= ctype_digit( $char ) === true ? $char : (string) ( ord( $char ) - 55 );

			$rest = 0;

			foreach( str_split( $digits ) as $digit )
				$rest = ( $rest * 10 + (int) $digit ) % 97;

			return $rest === 1 ? trim( chunk_split( $iban, 4, ' ' ) ) : null;
		}

		/**
		 *	The rates of a list: whole hundredths of a percent, from 0 to 10000, none twice
		 *
		 *	@param		string		$list					1900,700,0
		 *
		 *	@return 	array|null								Null for a list that is none
		 */
		public static function parseRates( string $list ): ?array {

			$rates = [];

			foreach( explode( ',', $list ) as $part ) {

				$part = trim( $part );

				if( preg_match( '/^(0|[1-9][0-9]{0,4})$/D', $part ) !== 1 || (int) $part > self::MAX_RATE || in_array( (int) $part, $rates, true ) === true )
					return null;

				$rates[] = (int) $part;
			}

			return $rates;
		}

		/**
		 *	The rates of a list written in percent: apart by a semicolon or white space, each a number of up to three digits with a decimal
		 *	comma or point and up to two decimals - 19; 7; 5,5; 0. Anything else is refused as a whole, 19,7,0 among it (a comma is the
		 *	decimal mark, never a separator)
		 *
		 *	@param		string		$text
		 *
		 *	@return 	array|null								Hundredths of a percent, as parseRates() takes them; null for a list that is none
		 */
		public static function parsePercent( string $text ): ?array {

			$hundredths = [];

			foreach( (array) preg_split( '/[;\s]+/', trim( $text ) ) as $part ) {

				if( preg_match( '/^(\d{1,3})(?:[.,](\d{1,2}))?$/D', (string) $part, $found ) !== 1 )
					return null;

				$hundredths[] = (string) ( (int) $found[1] * 100 + (int) str_pad( $found[2] ?? '', 2, '0' ) );
			}

			return self::parseRates( implode( ',', $hundredths ) );
		}

		/**
		 *	Whether a number format is one: {YYYY} or {YY} at most once, {N...} exactly once, and otherwise letters, digits,
		 *	hyphen, underscore and dot - a slash would be a path
		 *
		 *	@param		string		$format
		 *
		 *	@return 	bool
		 */
		public static function validFormat( string $format ): bool {

			if( strlen( $format ) > self::MAX_FORMAT )
				return false;

			$years = preg_match_all( '/\{(YYYY|YY)\}/', $format );
			$counters = preg_match_all( '/\{(N+)\}/', $format, $found );

			if( $years > 1 || $counters !== 1 || strlen( $found[1][0] ) > self::MAX_WIDTH )
				return false;

			return preg_match( '/^[A-Za-z0-9._-]*$/D', (string) preg_replace( '/\{(YYYY|YY|N+)\}/', '', $format ) ) === 1;
		}
	}
}
