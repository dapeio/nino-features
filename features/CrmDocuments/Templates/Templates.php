<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Settings;

	/**
	 *	Templates		What the documents know of the templates of the Pdf feature, and what Pdf and the screens that edit them ask the documents about: the templates of a tag with their inputs (of()), how many documents name each template (used()),
	 *									the answer to '/pdf/template/uses' - which keeps a template that a document names from being deleted - and to '/pdf/vocabulary' - the tokens, the slots and the sample values a template of a document may use -, and the
	 *									seed of the templates, the text blocks and the layout of the six kinds of document, which the status line mends Pdf with. The screens that edit a template are the package crm-pdf's and know no document: they ask Pdf,
	 *									and Pdf asks this class.
	 *
	 *	@package						Dape/Nino
	 */
	class Templates {

		/**	The tags of Pdf the documents use: the templates of a category carry the tag Render::tag() says	*/
		public const array TAGS = [ 'offer', 'invoice', 'contract', 'privacy', 'information' ];

		/**	The tokens a template of a document may use, by group: the vocabulary of Render::data(). The first label is English, the second German	*/
		private const array TOKENS = [
			'document' => [ [ 'Document', 'Beleg' ], [
				'/document/number' => [ 'Number of the document', 'Nummer des Belegs' ], '/document/title' => [ 'Title (offer, invoice ...)', 'Titel (Angebot, Rechnung ...)' ], '/document/date' => [ 'Date', 'Datum' ],
				'/document/parent' => [ 'Number of the document it refers to', 'Nummer des Belegs, auf den er sich bezieht' ], '/document/vatnote' => [ 'Note on the tax', 'Steuerhinweis' ],
				'/document/net' => [ 'Net', 'Netto' ], '/document/vat' => [ 'VAT', 'Umsatzsteuer' ], '/document/gross' => [ 'Gross', 'Brutto' ],
				'/document/period-line' => [ 'Line of the service period (empty without a period)', 'Zeile Leistungszeitraum (leer ohne Zeitraum)' ],
			] ],
			'invoice' => [ [ 'Invoice', 'Rechnung' ], [
				'/invoice/due' => [ 'Due day', 'Zahlungsziel' ], '/invoice/due-sentence' => [ 'Sentence on the due day', 'Satz zum Zahlungsziel' ], '/invoice/period' => [ 'Time of the service', 'Leistungszeit' ],
				'/site/tax' => [ 'Tax number or VAT id', 'Steuernummer oder USt-IdNr.' ], '/site/bank' => [ 'Bank', 'Bank' ], '/site/iban' => [ 'IBAN', 'IBAN' ], '/site/bic' => [ 'BIC', 'BIC' ],
			] ],
			'offer' => [ [ 'Offer', 'Angebot' ], [
				'/offer/number' => [ 'Number of the offer', 'Nummer des Angebots' ], '/offer/date' => [ 'Date of the offer', 'Datum des Angebots' ], '/offer/valid' => [ 'Valid until', 'Gültig bis' ],
				'/offer/valid-sentence' => [ 'Sentence on the validity', 'Satz zur Gültigkeit' ], '/offer/gross' => [ 'Gross of the offer', 'Brutto des Angebots' ], '/offer/period' => [ 'Time of the service of the offer', 'Leistungszeit des Angebots' ],
			] ],
			'customer' => [ [ 'Client', 'Kunde' ], [
				'/customer/number' => [ 'Client number', 'Kundennummer' ], '/customer/company' => [ 'Company', 'Firma' ], '/customer/name' => [ 'Name', 'Name' ], '/customer/street' => [ 'Street', 'Straße' ],
				'/customer/zip' => [ 'Postcode', 'PLZ' ], '/customer/city' => [ 'Town', 'Ort' ], '/customer/country' => [ 'Country', 'Land' ], '/customer/email' => [ 'Email of the client', 'E-Mail des Kunden' ],
				'/customer/vat' => [ 'VAT id of the client', 'USt-IdNr. des Kunden' ],
			] ],
		];

		/**	The slots a template of a document has: the areas of the document the package fills	*/
		private const array SLOTS = [ 'address' => [ 'Address', 'Adresse' ], 'lines' => [ 'Lines', 'Positionen' ], 'totals' => [ 'Totals', 'Summen' ] ];

		/**	What a preview shows for a token that has no value of a document: a sample that looks like one	*/
		private const array SAMPLE = [
			'/document/number' => 'R-2026-0001', '/document/date' => '01.01.2026', '/document/net' => '100,00 €', '/document/vat' => '19,00 €', '/document/gross' => '119,00 €', '/invoice/due' => '15.01.2026', '/offer/number' => 'A-2026-0001',
			'/offer/date' => '01.01.2026', '/offer/valid' => '31.01.2026', '/offer/gross' => '119,00 €', '/customer/number' => 'K-0001', '/customer/company' => 'Example Ltd', '/customer/name' => 'Ada Example', '/customer/street' => 'Example Street 1',
			'/customer/zip' => '12345', '/customer/city' => 'Example Town', '/customer/country' => 'DE', '/customer/email' => 'ada@example.org', '/customer/vat' => 'DE123456789',
		];

		/**
		 *	The templates of Pdf for the tag of a category, each with its inputs; none while Pdf is not active
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category			A category of Documents::CATEGORIES, or a tag of TAGS
		 *
		 *	@return 	array											key, name, the inputs of each and unsigned (the template has a box for the owner's signature and the layout of the PDF has no signature to draw in it)
		 */
		public static function of( array &$appData, string $category ): array {

			if( \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return [];

			$templates = [];
			$signed = \Nino\Modules\Pdf::picture( $appData, 'signature' ) !== '';

			foreach( \Nino\Modules\Pdf::templates( $appData, in_array( $category, self::TAGS, true ) === true ? $category : Render::tag( $category ) ) as $key => $template )
				$templates[] = [
					'key'      => (string) $key,
					'name'     => $template['name'],
					'inputs'   => \Nino\Modules\Pdf::inputs( $appData, (string) $key ),
					'unsigned' => $signed === false && array_filter( \Nino\Modules\Pdf::fields( $appData, (string) $key ), static fn( array $field ): bool => $field['party'] === 'owner' ) !== [],
				];

			return $templates;
		}

		/**
		 *	How many documents name each template, by its key
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => count, for the keys that a document names
		 */
		public static function used( array &$appData ): array {

			$used = [];

			foreach( Db::rows( $appData, 'SELECT template_key, COUNT(*) AS n FROM documents WHERE template_key <> :none GROUP BY template_key', [ 'none' => '' ] ) as $row )
				$used[(string) $row['template_key']] = (int) $row['n'];

			return $used;
		}

		/**
		 *	'/pdf/template/uses': the templates that documents name, with how many do. A listener that cannot read the database throws: Pdf counts a listener that failed as not knowing, and a template is then not deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				uses
		 *
		 *	@return 	void
		 */
		public static function callbackUses( array &$appData, array &$args ): void {

			foreach( self::used( $appData ) as $key => $count )
				$args['uses'][$key]['crm-documents'] = $count;
		}

		/**
		 *	'/pdf/vocabulary': what a template of a document may use - the tokens in four groups, the three slots, and a sample for the tokens a preview has no document for (the invoice particulars of the settings are the real ones)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				groups, slots and sample
		 *
		 *	@return 	void
		 */
		public static function callbackVocabulary( array &$appData, array &$args ): void {

			$locales = [ 'en_US', 'de_DE' ];

			foreach( self::TOKENS as $group => [ $label, $tokens ] ) {

				$list = [];

				foreach( $tokens as $token => $words )
					$list[] = [ 'token' => $token, 'label' => array_combine( $locales, $words ) ];

				$args['groups'][] = [ 'key' => $group, 'label' => array_combine( $locales, $label ), 'tokens' => $list ];
			}

			foreach( self::SLOTS as $name => $words )
				$args['slots'][] = [ 'name' => $name, 'label' => array_combine( $locales, $words ) ];

			$args['sample'] = array_merge( (array) ( $args['sample'] ?? [] ), self::SAMPLE, self::particulars( $appData ) );
		}

		/**
		 *	The invoice particulars as the tokens a document hands (see Render::data())
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											/site/tax, /site/bank, /site/iban, /site/bic
		 */
		public static function particulars( array &$appData ): array {

			$all = Settings::all( $appData );

			return [ '/site/tax' => $all['tax_id'], '/site/bank' => $all['bank_name'], '/site/iban' => $all['iban'], '/site/bic' => $all['bic'] ];
		}

		/**
		 *	The seed of the native language of the website: the templates, the text blocks and the layout of the six kinds of document
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											templates, blocks and layout; the English seed for a language that has no file of its own
		 */
		public static function seed( array &$appData ): array {

			$locale = \Nino\Locales::getNativeLocale( $appData );
			$file = __DIR__. '/../install/pdf-'. ( in_array( $locale, [ 'de_DE', 'en_US' ], true ) === true ? $locale : 'en_US' ). '.php';

			return array_intersect_key( (array) require $file, [ 'templates' => 1, 'blocks' => 1, 'layout' => 1 ] );
		}
	}
}
