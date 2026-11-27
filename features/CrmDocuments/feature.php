<?php
// The class is not declared here: features/CrmDocuments/ can only serve \Nino\Modules\CrmDocuments
return [
	'key'					=> 'crm-documents',
	'name'				=> [ 'en_US' => 'CRM documents', 'de_DE' => 'CRM-Belege' ],
	'description'	=> [
		'en_US' => 'The documents of the CRM: offers, invoices and credit notes with lines, totals in cents and numbers that run without a gap, and the contracts, privacy documents and information sheets that have no amount. A document is a draft until it is finalised; then it has its number, its frozen client and its PDF file, which is written once and never replaced, and no action of the feature changes or deletes it again. The test phase of the core deletes the documents and the files of the books; the removal of the data of a removed package deletes the tables, and the files stay in their folder. The feature adds the settings of the invoices, the electronic invoice for business clients, the export for the accountant and what it holds of a client to the deletion of the client and to the test phase.',
		'de_DE' => 'Die Belege des CRM: Angebote, Rechnungen und Stornorechnungen mit Positionen, Summen in Cent und lückenlosen Nummern, dazu Verträge, Datenschutzdokumente und Informationsblätter ohne Betrag. Ein Beleg ist ein Entwurf, bis Du ihn festschreibst; danach hat er seine Nummer, seinen eingefrorenen Kunden und seine PDF-Datei, die einmal geschrieben und nie ersetzt wird, und keine Aktion des Features ändert oder löscht ihn wieder. Die Testphase des Kerns löscht die Belege und die Dateien der Bücher; das Entfernen der Daten eines entfernten Pakets löscht die Tabellen, und die Dateien bleiben in ihrem Ordner. Das Feature bringt die Einstellungen der Rechnungen, die E-Rechnung für Firmenkunden, den Export für den Steuerberater und das, was es von einem Kunden hält, in die Löschung des Kunden und in die Testphase.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'[[first]], [[more]], [[text]], [[rows]], [[pos]], [[title]], [[qty]], [[price]], [[amount]]' => [
				'en_US' => 'The places of the slots of a PDF (address, lines, totals) in the fragments below templates/pdf, which the feature fills in one pass, escaped; the words around them are texts of the project.',
				'de_DE' => 'Die Plätze der Slots eines PDF (Anschrift, Positionen, Summen) in den Bausteinen unter templates/pdf, die das Feature in einem Durchgang und maskiert füllt; die Worte darum sind Texte des Projekts.',
			],
		],
		'routes' => [
			'/.crm/pdf/*' => [
				'en_US' => 'The PDF file of a finalised document by its id, for the owner only, as an attachment or inline, checked against its stored hash.',
				'de_DE' => 'Die PDF-Datei eines festgeschriebenen Belegs nach seiner Kennung, nur für den Inhaber, als Anhang oder im Fenster, geprüft gegen den gespeicherten Hash.',
			],
			'/.crm/xml/*' => [
				'en_US' => 'The electronic invoice of a finalised invoice or credit note by its id, for the owner only, as an attachment, checked against its stored hash.',
				'de_DE' => 'Die E-Rechnung einer festgeschriebenen Rechnung oder Stornorechnung nach ihrer Kennung, nur für den Inhaber, als Anhang, geprüft gegen den gespeicherten Hash.',
			],
			'/crm/preview/*' => [
				'en_US' => 'The PDF of a draft as its template would make it now, for the account of the CRM; nothing is stored and no number is taken.',
				'de_DE' => 'Das PDF eines Entwurfs, wie seine Vorlage es jetzt machte, für das Konto des CRM; nichts wird gespeichert und keine Nummer vergeben.',
			],
			'/crm/export/*' => [
				'en_US' => 'The documents of a year as a CSV, and the PDF files of a year or of a choice as a ZIP, for the accountant.',
				'de_DE' => 'Die Belege eines Jahres als CSV und die PDF-Dateien eines Jahres oder einer Auswahl als ZIP, für den Steuerberater.',
			],
		],
		'panel' => [],
		'callbacks' => [
			'/pdf/template/uses' => [
				'en_US' => 'Answers which templates of Pdf documents name, so that a template that a document names is not deleted.',
				'de_DE' => 'Antwortet, welche Vorlagen aus PDF Belege nennen, damit eine Vorlage, die ein Beleg nennt, nicht gelöscht wird.',
			],
			'/pdf/vocabulary' => [
				'en_US' => 'Answers the tokens, the slots and the sample values a template of a document may use.',
				'de_DE' => 'Antwortet mit den Platzhaltern, den Slots und den Beispielwerten, die eine Vorlage eines Belegs nutzen darf.',
			],
			'/crm/overview' => [
				'en_US' => 'Adds the books at a glance, the latest documents and what needs the owner to the first screen of the CRM.',
				'de_DE' => 'Fügt dem ersten Bildschirm des CRM die Bücher auf einen Blick, die letzten Belege und das, was den Inhaber braucht, hinzu.',
			],
			'/crm/project/holds' => [
				'en_US' => 'Says that documents name a project, so that the project is neither deleted nor given to another client.',
				'de_DE' => 'Meldet, dass Belege ein Projekt nennen, damit das Projekt weder gelöscht noch einem anderen Kunden gegeben wird.',
			],
			'/crm/project/figures' => [
				'en_US' => 'Adds the number of documents and of open offers to the rows of the list of projects.',
				'de_DE' => 'Fügt den Zeilen der Projektliste die Zahl der Belege und der offenen Angebote hinzu.',
			],
			'/crm/settings/view' => [
				'en_US' => 'Adds the numbers the sequences give next and the templates of Pdf by tag to the view of the settings.',
				'de_DE' => 'Fügt der Ansicht der Einstellungen die Nummern, die die Nummernkreise als nächste geben, und die Vorlagen aus PDF nach Tag hinzu.',
			],
			'/crm/settings/save' => [
				'en_US' => 'Takes the VAT rates written in percent as the rates the settings keep, or refuses the post.',
				'de_DE' => 'Nimmt die in Prozent geschriebenen Steuersätze als die Sätze, die die Einstellungen führen, oder lehnt den Post ab.',
			],
			'/crm/element/start' => [
				'en_US' => 'Starts a new item with the highest rate of the settings and leaves the rate out for a small business.',
				'de_DE' => 'Beginnt eine neue Position mit dem höchsten Satz der Einstellungen und lässt den Satz bei einem Kleinunternehmen aus.',
			],
		],
		'install' => [
			'templates/pdf/crm-documents-address.tpl' => [
				'en_US' => 'Ten fragments of the PDF of a document (address, lines, totals and their rows), add-only: the project may change them.',
				'de_DE' => 'Zehn Bausteine des PDF eines Belegs (Anschrift, Positionen, Summen und ihre Zeilen), nur ergänzend: Das Projekt darf sie ändern.',
			],
			'crm-items' => [
				'en_US' => 'The element type of the items a line of a document is filled from, with a price, a rate and a unit code, add-only.',
				'de_DE' => 'Der Elementtyp der Positionen, aus denen eine Zeile eines Belegs gefüllt wird, mit Preis, Satz und Einheitencode, nur ergänzend.',
			],
			'install/pdf-<locale>.php' => [
				'en_US' => 'The templates, text blocks and layout of the six kinds of document that the status line mends into Pdf, in the native language of the site, add-only.',
				'de_DE' => 'Die Vorlagen, Textbausteine und das Layout der sechs Belegarten, die die Statuszeile in PDF einrichtet, in der Muttersprache der Website, nur ergänzend.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The words the PDF of a document says (titles, table heads, sentences of the terms), in the Text panel, where the project keeps them.',
				'de_DE' => 'Die Worte, die das PDF eines Belegs sagt (Titel, Tabellenköpfe, Sätze der Zahlungsbedingungen), im Panel Texte, wo das Projekt sie pflegt.',
			],
			'/data/crm/pdf' => [
				'en_US' => 'The PDF files and electronic invoices below a folder for each year, written once and never replaced; the daily backup carries them.',
				'de_DE' => 'Die PDF-Dateien und E-Rechnungen unter einem Ordner je Jahr, einmal geschrieben und nie ersetzt; die tägliche Sicherung trägt sie.',
			],
		],
	],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	// xmlwriter writes the electronic invoice; the ZIP of the export needs zip, which the export asks for itself, so that a server without it still runs the feature
	'php'					=> [ 'ext' => [ 'pdo_sqlite', 'xmlwriter' ] ],
	'requires'		=> [ 'crm', 'pdf' ],
	// The PDF files and the electronic invoices of the documents: the database that names them is in the backup of the core, and the files are what its rows point at
	'data'				=> [ '/data/crm/pdf' ],
	'settings'		=> [],
];
