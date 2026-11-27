<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	/**
	 *	CrmDocuments\Lines		The words and numbers of a line: trimmed and checked. A line is made from an item of the catalogue
	 *												(CrmDocuments\Items) or typed, and what it holds is its own from then on.
	 *
	 *												clean() answers the values or a string: 'long:<field>', 'format:<field>' (a value that is no text, or holds a control character or a character XML cannot carry; a description may hold line breaks and tabs),
	 *												'empty:title', 'amount:price' or 'rate:vat_rate' (the field a value was refused for; 'format:unit_code' for a code that is none of UNIT_CODES).
	 */
	class Lines {

		// The characters a text of a line may not hold: all but the ones XML 1.0 carries, the C1 controls included; the title and the unit may not hold a tab or a break either
		private const string REFUSED_LINE = '/[^\x{20}-\x{7E}\x{A0}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';
		private const string REFUSED_TEXT = '/[^\x{9}\x{A}\x{D}\x{20}-\x{7E}\x{A0}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';

		// What the sheet of Pdf shows as a question mark or turns the text with, and what is nothing to read: the marks of direction (the embeddings, overrides and isolates, LRM, RLM, ALM), the separators of a line and a paragraph, private use,
		// the tag characters and the characters that are none in any plane. The joiners, the soft hyphen, the variation selectors and the byte order mark show nothing and are no harm; emoji and letters the font of the sheet has not are drawn as the sheet draws them
		private const string REFUSED_MARKS = '/[\x{61C}\x{200E}\x{200F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{E000}-\x{F8FF}\x{FDD0}-\x{FDEF}\x{E0000}-\x{E007F}\x{F0000}-\x{10FFFF}\x{1FFFE}\x{1FFFF}\x{2FFFE}\x{2FFFF}\x{3FFFE}\x{3FFFF}\x{4FFFE}\x{4FFFF}\x{5FFFE}\x{5FFFF}\x{6FFFE}\x{6FFFF}\x{7FFFE}\x{7FFFF}\x{8FFFE}\x{8FFFF}\x{9FFFE}\x{9FFFF}\x{AFFFE}\x{AFFFF}\x{BFFFE}\x{BFFFF}\x{CFFFE}\x{CFFFF}\x{DFFFE}\x{DFFFF}\x{EFFFE}\x{EFFFF}]/u';

		// The texts of a line with the longest value each takes
		public const array FIELDS = [ 'title' => 200, 'description' => 2000, 'unit' => 20 ];

		// The unit codes of UN/ECE Recommendation 20 a line may carry, none being the other possibility: the electronic invoice names the unit by its code (the words of
		// the screens are the surface's, unitcode.<code>); the text unit stays for the PDF. H87 piece, HUR hour, MIN minute, DAY day, WEE week, MON month, LS lump sum, C62 one, KMT kilometre
		public const array UNIT_CODES = [ 'H87', 'HUR', 'MIN', 'DAY', 'WEE', 'MON', 'LS', 'C62', 'KMT' ];

		/**
		 *	The fields of a line: trimmed and checked
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$input				title, description, unit, unit_code (one of UNIT_CODES, or nothing), price (text, or cents as an int), vat_rate
		 *	@param		string		$vatMode			Whose rates the rate is held to
		 *
		 *	@return 	array|string							title, description, unit, unit_code, price_net, vat_rate; or why not
		 */
		public static function clean( array &$appData, array $input, string $vatMode ): array|string {

			$values = [];

			foreach( self::FIELDS as $field => $max ) {

				$value = $input[$field] ?? '';

				if( is_string( $value ) === false )
					return 'format:'. $field;

				$value = trim( $value );

				// What the file of the electronic invoice (XML 1.0) cannot carry is refused, and with it every control character - the description may break lines and be indented, the title and the unit take neither: no mark that
				// only shows nothing (a soft hyphen, a joiner, a mark of direction) and no private use character is refused, and so is no sign of an emoji. Text that is no UTF-8 is refused too
				if( preg_match( $field === 'description' ? self::REFUSED_TEXT : self::REFUSED_LINE, $value ) !== 0 || self::marked( $value ) === true )
					return 'format:'. $field;

				if( mb_strlen( $value, 'UTF-8' ) > $max )
					return 'long:'. $field;

				$values[$field] = $value;
			}

			if( $values['title'] === '' )
				return 'empty:title';

			$code = $input['unit_code'] ?? '';

			if( is_string( $code ) === false || ( $code !== '' && in_array( $code, self::UNIT_CODES, true ) === false ) )
				return 'format:unit_code';

			$values['unit_code'] = $code;

			$price = self::amount( $input['price'] ?? 0, 'price' );

			if( $price === null )
				return 'amount:price';

			$rate = $input['vat_rate'] ?? 0;

			if( is_int( $rate ) === false || Rules::rateAllowed( $appData, $rate, $vatMode ) === false )
				return 'rate:vat_rate';

			return $values + [ 'price_net' => $price, 'vat_rate' => $rate ];
		}

		/**
		 *	Whether a text holds a mark that is refused in every text of a document that is printed in a line: a mark of direction, a line or paragraph separator, private use, a tag character, a character that is none
		 *
		 *	@param		string		$value				UTF-8
		 *
		 *	@return 	bool
		 */
		public static function marked( string $value ): bool {

			return preg_match( self::REFUSED_MARKS, $value ) !== 0;
		}

		/**
		 *	A price or a quantity as a whole number: a text is parsed, an int is taken as the smallest part
		 *
		 *	@param		mixed			$value
		 *	@param		string		$kind					price or qty
		 *
		 *	@return 	int|null									Null for a value that is none or out of range
		 */
		public static function amount( mixed $value, string $kind ): ?int {

			if( is_string( $value ) === true )
				return $kind === 'qty' ? Money::parseQty( trim( $value ) ) : Money::parse( trim( $value ) );

			if( is_int( $value ) === false )
				return null;

			return abs( $value ) <= ( $kind === 'qty' ? Money::MAX_QTY : Money::MAX_PRICE ) ? $value : null;
		}
	}
}
