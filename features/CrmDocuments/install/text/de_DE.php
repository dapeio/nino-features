<?php
declare(strict_types=1);

// The words the PDF of a document says - merged into the project's own text/de_DE.php on every activation, add-only: a key the project already has stays. From then on an editor keeps them current in the Text panel
// and never opens a feature directory. A %s is a place for a value, in the order Render::text() gives them; a document is the owner's letter to the client and is written in the native language of the website, so the words
// are read in that language and a word the project lacks makes no PDF
return [

	'[[/feature/crm-documents/pdf-title/offer]]' => 'Angebot',
	'[[/feature/crm-documents/pdf-title/invoice]]' => 'Rechnung',
	'[[/feature/crm-documents/pdf-title/credit]]' => 'Stornorechnung',
	'[[/feature/crm-documents/pdf-due/sentence]]' => 'Zahlbar ohne Abzug bis zum %s unter Angabe der Rechnungsnummer %s.',
	'[[/feature/crm-documents/pdf-due/sentence-none]]' => 'Zahlbar sofort ohne Abzug unter Angabe der Rechnungsnummer %s.',
	'[[/feature/crm-documents/pdf-valid/sentence]]' => 'Dieses Angebot ist gültig bis zum %s.',
	'[[/feature/crm-documents/pdf-valid/sentence-none]]' => 'Dieses Angebot ist freibleibend.',
	'[[/feature/crm-documents/pdf-period/none]]' => 'Leistungsdatum entspricht dem Rechnungsdatum',
	'[[/feature/crm-documents/pdf-period/none-offer]]' => 'nach Absprache',
	'[[/feature/crm-documents/pdf-period/line]]' => 'Leistungszeitraum: %s',
	'[[/feature/crm-documents/pdf-vatnote/small-business]]' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
	'[[/feature/crm-documents/pdf-vatnote/reverse]]' => 'Steuerschuldnerschaft des Leistungsempfängers.',
	'[[/feature/crm-documents/pdf-head/pos]]' => 'Pos.',
	'[[/feature/crm-documents/pdf-head/title]]' => 'Bezeichnung',
	'[[/feature/crm-documents/pdf-head/qty]]' => 'Menge',
	'[[/feature/crm-documents/pdf-head/price]]' => 'Einzelpreis',
	'[[/feature/crm-documents/pdf-head/rate]]' => 'USt.',
	'[[/feature/crm-documents/pdf-head/amount]]' => 'Betrag',
	'[[/feature/crm-documents/pdf-totals/net]]' => 'Nettobetrag',
	'[[/feature/crm-documents/pdf-totals/vat]]' => 'zzgl. %s USt. auf %s',
	'[[/feature/crm-documents/pdf-totals/gross]]' => 'Gesamtbetrag',
];
