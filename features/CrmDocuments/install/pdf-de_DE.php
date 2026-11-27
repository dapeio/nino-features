<?php
declare(strict_types=1);

// install/pdf-de_DE.php - what the status line of the templates seeds into the Pdf feature through \Nino\Modules\Pdf::install(), add-only: the templates of the documents, the text blocks they share and the header and the footer
// of the layout, written in German - the file of the native language of the website is the one that is read, and a site whose language has no file here takes the English one. The wizard does not read this file (the manifest
// does not name it); the fix of the line does.
//
// The tokens are the ones \Nino\Modules\CrmDocuments\Render::data() hands a template ([[/document/number]], [[/customer/company]], ...) and the site's own texts ([[/project/company/general/name]], ... - the words of the
// company step of the wizard), so a document speaks with the company of the site and the client of the document. A template of the tags contract and privacy carries a field of the owner and one of the member, which
// is what Sign needs to ask a member for a signature. The footer of a new install carries the owner's particulars ([[/site/tax]], [[/site/iban]], which the settings of the CRM hold) as one line the owner edits.
// The texts of a document are the owner's letter to the client: no pronoun, so that the owner's tone is the owner's to set in the templates
return [

	'blocks' => [
		'payment-terms' => [ 'title' => 'Zahlungsbedingungen', 'html' => '<p>[[/invoice/due-sentence]]</p>' ],
		'validity'      => [ 'title' => 'Gültigkeit', 'html' => '<p>[[/offer/valid-sentence]]</p>' ],
		'signature'     => [ 'title' => 'Unterschrift', 'html' => '<p>Mit ihrer Unterschrift bestätigen die Parteien den Inhalt dieses Dokuments.</p>' ],
		'contact'       => [ 'title' => 'Kontakt', 'html' => '<p><strong>[[/project/company/general/name]]</strong><br>[[/project/company/contact/address]]<br>[[/project/company/contact/email]] · [[/project/company/contact/phone]]</p>' ],
	],

	'layout' => [
		'header' => '<p><strong>[[/project/company/general/name]]</strong><br>[[/project/company/contact/address]]<br>[[/project/company/contact/email]] · [[/project/company/contact/phone]]</p>',
		'footer' => '<p align="center">[[/project/company/general/name]] · [[/project/company/contact/email]] · [[/project/company/contact/phone]] · Steuernummer [[/site/tax]] · IBAN [[/site/iban]]</p>',
	],

	'templates' => [

		'offer' => [ 'name' => 'Angebot', 'tag' => 'offer', 'blocks' => [
			[ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]] [[/document/number]]</h1><p>Datum: [[/document/date]]<br>Kundennummer: [[/customer/number]]</p>' ],
			[ 'type' => 'html', 'html' => '<p>[[/document/period-line]]</p>' ],
			[ 'type' => 'input', 'name' => 'intro', 'label' => 'Einleitung', 'multiline' => true, 'required' => false ],
			[ 'type' => 'slot', 'name' => 'lines' ],
			[ 'type' => 'slot', 'name' => 'totals' ],
			[ 'type' => 'html', 'html' => '<p>[[/document/vatnote]]</p>' ],
			[ 'type' => 'html', 'block' => 'validity' ],
			[ 'type' => 'html', 'block' => 'contact' ],
		] ],

		'invoice' => [ 'name' => 'Rechnung', 'tag' => 'invoice', 'blocks' => [
			[ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]] [[/document/number]]</h1><p>Datum: [[/document/date]]<br>Kundennummer: [[/customer/number]]</p>' ],
			[ 'type' => 'html', 'html' => '<p>[[/document/period-line]]</p>' ],
			[ 'type' => 'input', 'name' => 'intro', 'label' => 'Einleitung', 'multiline' => true, 'required' => false ],
			[ 'type' => 'slot', 'name' => 'lines' ],
			[ 'type' => 'slot', 'name' => 'totals' ],
			[ 'type' => 'html', 'html' => '<p>[[/document/vatnote]]</p>' ],
			[ 'type' => 'html', 'block' => 'payment-terms' ],
			[ 'type' => 'html', 'block' => 'contact' ],
		] ],

		'credit-note' => [ 'name' => 'Stornorechnung', 'tag' => 'invoice', 'blocks' => [
			[ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]] [[/document/number]]</h1><p>Datum: [[/document/date]]<br>Zur Rechnung: [[/document/parent]]<br>Kundennummer: [[/customer/number]]</p>' ],
			[ 'type' => 'input', 'name' => 'reason', 'label' => 'Grund der Stornierung', 'multiline' => true, 'required' => false ],
			[ 'type' => 'slot', 'name' => 'lines' ],
			[ 'type' => 'slot', 'name' => 'totals' ],
			[ 'type' => 'html', 'html' => '<p>[[/document/vatnote]]</p>' ],
			[ 'type' => 'html', 'block' => 'contact' ],
		] ],

		'contract' => [ 'name' => 'Vertrag', 'tag' => 'contract', 'blocks' => [
			[ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]]</h1><p>Datum: [[/document/date]]<br>Zum Angebot [[/offer/number]] vom [[/offer/date]]<br>Kundennummer: [[/customer/number]]</p>' ],
			[ 'type' => 'input', 'name' => 'content', 'label' => 'Inhalt des Vertrags', 'multiline' => true, 'required' => false ],
			[ 'type' => 'slot', 'name' => 'lines' ],
			[ 'type' => 'slot', 'name' => 'totals' ],
			[ 'type' => 'html', 'block' => 'signature' ],
			[ 'type' => 'signature', 'name' => 'owner', 'party' => 'owner', 'label' => 'Inhaber', 'page' => 'last', 'x' => 20, 'y' => 230, 'w' => 70, 'h' => 20, 'line' => true ],
			[ 'type' => 'signature', 'name' => 'client', 'party' => 'member', 'label' => 'Kunde', 'page' => 'last', 'x' => 110, 'y' => 230, 'w' => 70, 'h' => 20, 'line' => true ],
		] ],

		'privacy' => [ 'name' => 'Datenschutz', 'tag' => 'privacy', 'blocks' => [
			[ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]]</h1><p>Datum: [[/document/date]]<br>Kundennummer: [[/customer/number]]</p>' ],
			[ 'type' => 'input', 'name' => 'content', 'label' => 'Hinweise zum Datenschutz', 'multiline' => true, 'required' => false ],
			[ 'type' => 'html', 'block' => 'signature' ],
			[ 'type' => 'signature', 'name' => 'owner', 'party' => 'owner', 'label' => 'Inhaber', 'page' => 'last', 'x' => 20, 'y' => 230, 'w' => 70, 'h' => 20, 'line' => true ],
			[ 'type' => 'signature', 'name' => 'client', 'party' => 'member', 'label' => 'Kunde', 'page' => 'last', 'x' => 110, 'y' => 230, 'w' => 70, 'h' => 20, 'line' => true ],
		] ],

		'information' => [ 'name' => 'Information', 'tag' => 'information', 'blocks' => [
			[ 'type' => 'slot', 'name' => 'address' ],
			[ 'type' => 'html', 'html' => '<h1>[[/document/title]]</h1><p>Datum: [[/document/date]]<br>Kundennummer: [[/customer/number]]</p>' ],
			[ 'type' => 'input', 'name' => 'text', 'label' => 'Text', 'multiline' => true, 'required' => false ],
			[ 'type' => 'html', 'block' => 'contact' ],
		] ],
	],
];
