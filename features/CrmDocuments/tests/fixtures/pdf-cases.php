<?php
declare(strict_types=1);

/**
 *	pdf-cases.php		The documents whose PDF files and electronic invoices the suite pins by their hashes: made up for this, no data of anybody. The same file is run by a script on the Nino 1 stand that records
 *											the sha256 and the length of what that stand writes (pdf-hashes.json), and by the suite of the package, which finalises the same documents and compares: the bytes of a document are what they
 *											were. The settings, the clients, the templates of Pdf and the documents come in the order they are made; a document names its client and the document it hangs on by the name of the step.
 */
return [

	'settings' => [ 'vat_mode' => 'regular', 'vat_rates' => '1900,700,0', 'currency' => '€', 'payment_days' => '14', 'offer_days' => '30', 'seller_name' => 'Studio Example', 'seller_street' => 'Hauptstr. 2', 'seller_zip' => '10115', 'seller_city' => 'Berlin',
		'seller_country' => 'DE', 'seller_contact' => 'Max Example', 'seller_phone' => '+49 30 123456', 'seller_email' => 'studio@example.org', 'vat_id' => 'DE123456789', 'tax_id' => '12/345/67890', 'iban' => 'DE89 3704 0044 0532 0130 00',
		'bic' => 'COBADEFFXXX', 'bank_name' => 'Example Bank', 'vat_exempt_text' => 'Kein Ausweis von Umsatzsteuer gemäß § 19 UStG' ],

	'clients' => [
		'firm' => [ 'company' => 'Example GmbH', 'name' => 'Ada Example', 'street' => 'Weg 1', 'zip' => '12345', 'city' => 'Berlin', 'country' => 'DE', 'email' => 'ada@example.org', 'vat_id' => 'DE999999999', 'business' => true, 'buyer_reference' => '991-12345-67' ],
		'private' => [ 'name' => 'Ben Beispiel', 'street' => 'Lindenweg 3', 'zip' => '1010', 'city' => 'Wien', 'country' => 'AT', 'email' => 'ben@example.org' ],
	],

	'templates' => [
		'offer' => [ 'name' => 'Angebot', 'tag' => 'offer', 'blocks' => [
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]] [[/document/number]]</h1>' ], [ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<p>Datum [[/document/date]] Gueltig [[/offer/valid]] [[/offer/valid-sentence]] Zeitraum [[/offer/period]] Kunde [[/customer/number]] [[/customer/company]] [[/customer/vat]]</p>' ],
			[ 'type' => 'input', 'name' => 'intro', 'label' => 'Intro', 'multiline' => true, 'required' => false ], [ 'type' => 'slot', 'name' => 'lines' ], [ 'type' => 'slot', 'name' => 'totals' ],
			[ 'type' => 'html', 'html' => '<p>Hinweis [[/document/vatnote]] Summe [[/document/gross]] [[/site/tax]] [[/site/iban]]</p>' ],
		] ],
		'invoice' => [ 'name' => 'Rechnung', 'tag' => 'invoice', 'blocks' => [
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]] [[/document/number]]</h1>' ], [ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<p>Datum [[/document/date]] Faellig [[/invoice/due]] [[/invoice/due-sentence]] Zeitraum [[/document/period-line]] Bezug [[/document/parent]] Kunde [[/customer/number]] [[/customer/company]] [[/customer/name]] [[/customer/city]] [[/customer/country]]</p>' ],
			[ 'type' => 'input', 'name' => 'intro', 'label' => 'Intro', 'multiline' => true, 'required' => false ], [ 'type' => 'slot', 'name' => 'lines' ], [ 'type' => 'slot', 'name' => 'totals' ],
			[ 'type' => 'html', 'html' => '<p>Hinweis [[/document/vatnote]] Netto [[/document/net]] USt [[/document/vat]] Brutto [[/document/gross]] [[/site/bank]] [[/site/bic]]</p>' ],
		] ],
		'contract' => [ 'name' => 'Vertrag', 'tag' => 'contract', 'blocks' => [
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]]</h1><p>Datum [[/document/date]] Angebot [[/offer/number]] [[/offer/date]] [[/offer/gross]] [[/document/parent]]</p>' ], [ 'type' => 'slot', 'name' => 'address' ], [ 'type' => 'slot', 'name' => 'lines' ], [ 'type' => 'slot', 'name' => 'totals' ],
		] ],
		'privacy' => [ 'name' => 'Datenschutz', 'tag' => 'privacy', 'blocks' => [
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]]</h1><p>Datum [[/document/date]]</p>' ], [ 'type' => 'slot', 'name' => 'address' ], [ 'type' => 'input', 'name' => 'content', 'label' => 'Inhalt', 'multiline' => true, 'required' => false ],
		] ],
		'information' => [ 'name' => 'Information', 'tag' => 'information', 'blocks' => [
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]]</h1>' ], [ 'type' => 'slot', 'name' => 'address' ],
		] ],
	],

	// Each step: name, kind (the category), client, the date of the document, what is posted for it, and what is done to it: finalise, and then maybe the credit note it takes back (as its own step)
	'documents' => [
		[ 'name' => 'offer', 'category' => 'offer', 'client' => 'firm', 'date' => '2026-02-10', 'template_key' => 'offer', 'period_from' => '2026-03-01', 'period_to' => '2026-03-31', 'inputs' => [ 'intro' => 'Wie besprochen.' ],
			'lines' => [ [ 'title' => 'Mixing', 'description' => "Zehn Spuren\nStereo", 'unit' => 'Std.', 'unit_code' => 'HUR', 'qty' => '12,5', 'price' => '80,00', 'vat_rate' => 1900 ], [ 'title' => 'Handbuch', 'qty' => '2', 'price' => '19,99', 'vat_rate' => 700 ] ] ],
		[ 'name' => 'contract', 'category' => 'contract', 'client' => 'firm', 'date' => '2026-02-11', 'template_key' => 'contract', 'title' => 'Vertrag zum Angebot', 'parent' => 'offer' ],
		[ 'name' => 'invoice', 'category' => 'invoice', 'client' => 'firm', 'date' => '2026-02-12', 'template_key' => 'invoice', 'period_from' => '2026-02-01', 'period_to' => '2026-02-28', 'inputs' => [ 'intro' => 'Danke für den Auftrag.' ],
			'lines' => [ [ 'title' => 'Mixing', 'description' => 'Zehn Spuren', 'unit' => 'Std.', 'unit_code' => 'HUR', 'qty' => '12,5', 'price' => '80,00', 'vat_rate' => 1900 ], [ 'title' => 'Handbuch', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '2', 'price' => '19,99', 'vat_rate' => 700 ],
				[ 'title' => 'Rabatt', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '1', 'price' => '-10,00', 'vat_rate' => 1900 ], [ 'title' => 'Drei Cent', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '1', 'price' => '0,03', 'vat_rate' => 1900 ] ] ],
		[ 'name' => 'credit-one', 'category' => 'credit', 'client' => 'firm', 'date' => '2026-02-20', 'template_key' => 'invoice', 'credit_of' => 'invoice', 'inputs' => [ 'intro' => 'Teilgutschrift.' ],
			'lines' => [ [ 'title' => 'Mixing', 'unit' => 'Std.', 'unit_code' => 'HUR', 'qty' => '-2,5', 'price' => '80,00', 'vat_rate' => 1900 ], [ 'title' => 'Drei Cent', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '-1', 'price' => '0,03', 'vat_rate' => 1900 ] ] ],
		[ 'name' => 'credit-two', 'category' => 'credit', 'client' => 'firm', 'date' => '2026-02-21', 'template_key' => 'invoice', 'credit_of' => 'invoice',
			'lines' => [ [ 'title' => 'Mixing', 'unit' => 'Std.', 'unit_code' => 'HUR', 'qty' => '-10', 'price' => '80,00', 'vat_rate' => 1900 ], [ 'title' => 'Handbuch', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '-2', 'price' => '19,99', 'vat_rate' => 700 ],
				[ 'title' => 'Rabatt', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '-1', 'price' => '-10,00', 'vat_rate' => 1900 ] ] ],
		[ 'name' => 'private', 'category' => 'invoice', 'client' => 'private', 'date' => '2026-03-02', 'template_key' => 'invoice', 'lines' => [ [ 'title' => 'Lektion', 'qty' => '1', 'price' => '49,50', 'vat_rate' => 1900 ] ] ],
		[ 'name' => 'reverse', 'category' => 'invoice', 'client' => 'firm', 'date' => '2026-03-03', 'template_key' => 'invoice', 'reverse_charge' => true, 'lines' => [ [ 'title' => 'Leistung', 'unit' => 'Stk.', 'unit_code' => 'H87', 'qty' => '1', 'price' => '1000,00', 'vat_rate' => 0 ] ] ],
		[ 'name' => 'privacy', 'category' => 'privacy', 'client' => 'private', 'date' => '2026-03-04', 'template_key' => 'privacy', 'title' => 'Datenschutz', 'inputs' => [ 'content' => 'Wir speichern Deine Daten.' ] ],
		[ 'name' => 'information', 'category' => 'information', 'client' => 'firm', 'date' => '2026-03-05', 'template_key' => 'information', 'title' => 'Information zum Projekt' ],
		[ 'name' => 'small', 'category' => 'invoice', 'client' => 'firm', 'date' => '2026-03-06', 'template_key' => 'invoice', 'vat_mode' => 'small_business', 'lines' => [ [ 'title' => 'Beratung', 'unit' => 'Std.', 'unit_code' => 'HUR', 'qty' => '3', 'price' => '60,00', 'vat_rate' => 0 ] ] ],
	],
];
