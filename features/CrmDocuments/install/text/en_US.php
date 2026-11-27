<?php
declare(strict_types=1);

// The words the PDF of a document says - merged into the project's own text/en_US.php on every activation, add-only: a key the project already has stays. From then on an editor keeps them current in the Text panel
// and never opens a feature directory. A %s is a place for a value, in the order Render::text() gives them; a document is the owner's letter to the client and is written in the native language of the website, so the words
// are read in that language and a word the project lacks makes no PDF
return [

	'[[/feature/crm-documents/pdf-title/offer]]' => 'Offer',
	'[[/feature/crm-documents/pdf-title/invoice]]' => 'Invoice',
	'[[/feature/crm-documents/pdf-title/credit]]' => 'Credit note',
	'[[/feature/crm-documents/pdf-due/sentence]]' => 'Payable without deduction by %s, quoting invoice number %s.',
	'[[/feature/crm-documents/pdf-due/sentence-none]]' => 'Payable immediately without deduction, quoting invoice number %s.',
	'[[/feature/crm-documents/pdf-valid/sentence]]' => 'This offer is valid until %s.',
	'[[/feature/crm-documents/pdf-valid/sentence-none]]' => 'This offer is subject to change.',
	'[[/feature/crm-documents/pdf-period/none]]' => 'Date of service is the invoice date',
	'[[/feature/crm-documents/pdf-period/none-offer]]' => 'by arrangement',
	'[[/feature/crm-documents/pdf-period/line]]' => 'Service period: %s',
	'[[/feature/crm-documents/pdf-vatnote/small-business]]' => 'No VAT is charged pursuant to § 19 UStG (small business regulation).',
	'[[/feature/crm-documents/pdf-vatnote/reverse]]' => 'VAT is owed by the recipient of the service (reverse charge).',
	'[[/feature/crm-documents/pdf-head/pos]]' => 'No.',
	'[[/feature/crm-documents/pdf-head/title]]' => 'Description',
	'[[/feature/crm-documents/pdf-head/qty]]' => 'Quantity',
	'[[/feature/crm-documents/pdf-head/price]]' => 'Unit price',
	'[[/feature/crm-documents/pdf-head/rate]]' => 'VAT',
	'[[/feature/crm-documents/pdf-head/amount]]' => 'Amount',
	'[[/feature/crm-documents/pdf-totals/net]]' => 'Net amount',
	'[[/feature/crm-documents/pdf-totals/vat]]' => 'plus %s VAT on %s',
	'[[/feature/crm-documents/pdf-totals/gross]]' => 'Total amount',
];
