<?php
// The class is not declared: features/Pdf/ can only serve \Nino\Modules\Pdf
return [
	'key'					=> 'pdf',
	'name'				=> [ 'en_US' => 'PDF', 'de_DE' => 'PDF' ],
	'description'	=> [
		'en_US' => 'A PDF builder with no library: the owner sets the layout once - paper, margins, font, header and footer with page numbers, a logo and a signature - and keeps a library of text blocks and templates built from them. render() turns a template and what a caller knows into the bytes of a PDF, the same bytes every time, and templates(), fields(), inputs(), tags() and layout() tell a caller what to offer. Every PDF is set by rules in CSS notation that the owner overrides property by property, and install() adds templates, text blocks and layout texts to a project without touching what it has. A package that brings a screen of its own builds on the writers of Store, the four views of View, preview(), uses(), vocabulary() and editorAddress().',
		'de_DE' => 'Ein PDF-Baukasten ohne Bibliothek: Der Inhaber stellt das Layout einmal ein - Papier, Ränder, Schrift, Kopfbereich und Fußbereich mit Seitenzahlen, ein Logo und eine Unterschrift - und führt eine Bibliothek von Textbausteinen und Vorlagen daraus. render() macht aus einer Vorlage und dem, was ein Aufrufer weiß, die Bytes eines PDFs, jedes Mal dieselben, und templates(), fields(), inputs(), tags() und layout() sagen einem Aufrufer, was er anbieten kann. Jedes PDF wird nach Regeln in CSS-Schreibweise gesetzt, die der Inhaber Eigenschaft für Eigenschaft überschreibt, und install() legt Vorlagen, Textbausteine und Layout-Texte in einem Projekt an, ohne anzufassen, was es hat. Ein Paket mit eigenem Bildschirm baut auf den Schreibern von Store, den vier Ansichten von View, preview(), uses(), vocabulary() und editorAddress() auf.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'PDF' => [
				'en_US' => 'Four tabs - Layout, Pictures, Text blocks and Templates - and the Settings tab, where the tags of the project are listed; everything the panel stores lies below data/pdf and a backup carries it.',
				'de_DE' => 'Vier Tabs - Layout, Bilder, Textbausteine und Vorlagen - und der Tab Einstellungen, in dem die Tags des Projekts stehen; was das Panel speichert, liegt unter data/pdf und kommt ins Backup.',
			],
			'Layout' => [
				'en_US' => 'Sets the paper (A4 or US Letter), the four margins in millimetres, the font and the base size, the header and the footer as HTML, and the design as CSS.',
				'de_DE' => 'Legt das Papier (A4 oder US Letter), die vier Ränder in Millimetern, die Schrift und die Grundgröße, Kopfbereich und Fußbereich als HTML und das Design als CSS fest.',
			],
			'Pictures' => [
				'en_US' => 'The Pictures tab holds the logo and the owner\'s signature, each a PNG or a JPEG of at most 4000 by 4000 pixels, put in and taken away at once.',
				'de_DE' => 'Der Tab Bilder hält Logo und Unterschrift des Inhabers, je ein PNG oder JPEG bis 4000 mal 4000 Pixel, sofort hineingelegt und herausgenommen.',
			],
			'Paper' => [
				'en_US' => 'A change of paper is refused while a signature box of a template would lie outside the other page, and the answer names those templates.',
				'de_DE' => 'Ein Wechsel des Papiers wird abgelehnt, solange ein Unterschriftfeld einer Vorlage auf dem anderen Papier außerhalb der Seite läge, und die Antwort nennt die Vorlagen.',
			],
			'Text blocks' => [
				'en_US' => 'A directory of the texts a template uses by reference, with a record for each: a changed block changes every template that uses it, and a block in use cannot be deleted.',
				'de_DE' => 'Ein Verzeichnis der Texte, die eine Vorlage als Verweis nutzt, mit einem Eintrag je Baustein: Ein geänderter Baustein ändert jede Vorlage, die ihn nutzt, und ein genutzter lässt sich nicht löschen.',
			],
			'Cleaning' => [
				'en_US' => 'The HTML of a block, of the header, of the footer and of a slot is cleaned by the feature\'s own list of tags and attributes, and any other tag is unwrapped to its text.',
				'de_DE' => 'Das HTML eines Bausteins, des Kopfbereichs, des Fußbereichs und eines Platzhalters wird nach der eigenen Liste des Features bereinigt, und jedes andere Tag wird zu seinem Text aufgelöst.',
			],
			'Font size in a text' => [
				'en_US' => 'A p, div, heading, cell, li or span takes style="font-size: ..." in pt, px, mm, em, rem or percent between 0.5 and 72 pt, and any other value is dropped when the text is saved.',
				'de_DE' => 'Ein p, div, eine Überschrift, Zelle, li oder span nimmt style="font-size: ..." in pt, px, mm, em, rem oder Prozent zwischen 0,5 und 72 pt, und jeder andere Wert fällt beim Speichern weg.',
			],
			'Ground and margin in a text' => [
				'en_US' => 'A td, th or tr takes style="background:#rrggbb" and a p or div takes style="margin:..." of one to four lengths up to 200 mm, and anything else is dropped when the text is saved.',
				'de_DE' => 'Ein td, th oder tr nimmt style="background:#rrggbb", ein p oder div nimmt style="margin:..." aus ein bis vier Längen bis 200 mm, und alles andere fällt beim Speichern weg.',
			],
			'Templates' => [
				'en_US' => 'A directory with search, the tag as category, New, Duplicate and Delete, and the editor of one template: a name, a key, an optional tag of the project\'s list and at most 60 blocks in their order.',
				'de_DE' => 'Ein Verzeichnis mit Suche, Tag als Kategorie, Neu, Duplizieren und Löschen und der Editor einer Vorlage: Name, Schlüssel, ein Tag des Projekts nach Wahl und höchstens 60 Blöcke in ihrer Reihenfolge.',
			],
			'Blocks of a template' => [
				'en_US' => 'A block is a text of the library or of its own, a slot for the HTML a caller hands in, an input for a free text, a signature field at a place in millimetres, or a page break.',
				'de_DE' => 'Ein Block ist ein Text aus der Bibliothek oder ein eigener, ein Platzhalter für HTML, ein Eingabefeld für einen Freitext, ein Unterschriftfeld an einer Stelle in Millimetern oder ein Seitenumbruch.',
			],
			'Preview' => [
				'en_US' => 'Renders what the editor holds, saved or not, with sample data and shows it with a link to download it; nothing is stored.',
				'de_DE' => 'Erzeugt, was der Editor hält, gespeichert oder nicht, mit Beispieldaten und zeigt es mit einem Link zum Herunterladen; gespeichert wird nichts.',
			],
			'Placeholders' => [
				'en_US' => 'A text may hold [[page]] and [[pages]] in the header and the footer, a key with slashes such as [[/customer/name]], or a name such as [[a.b]], and a key nobody has is removed.',
				'de_DE' => 'Ein Text darf [[page]] und [[pages]] im Kopf- und Fußbereich, einen Schlüssel wie [[/customer/name]] oder einen Namen wie [[a.b]] enthalten, und ein Schlüssel, den niemand hat, fällt weg.',
			],
			'Design' => [
				'en_US' => 'Every PDF is set by rules written as CSS: the feature\'s own are shown read-only and the owner\'s are laid over them property by property, and what the sheet does not know is refused with its line.',
				'de_DE' => 'Jedes PDF wird nach Regeln in CSS gesetzt: Die des Features stehen nur lesend da, die des Inhabers liegen Eigenschaft für Eigenschaft darüber, und Unbekanntes wird mit seiner Zeile abgelehnt.',
			],
			'Design selectors' => [
				'en_US' => 'The sheet reads body, h1 to h3, p, li, ul, ol, table, th, td, hr, a, the rows of a table by parity, and the classes .address, .header, .footer and .signature.',
				'de_DE' => 'Das Blatt liest body, h1 bis h3, p, li, ul, ol, table, th, td, hr, a, die Zeilen einer Tabelle nach Parität und die Klassen .address, .header, .footer und .signature.',
			],
			'Design properties' => [
				'en_US' => 'The sheet reads font-family, font-size, font-weight, font-style, color, line-height, text-align, background, text-decoration, margin, padding, border-top and border-bottom where they apply.',
				'de_DE' => 'Das Blatt liest font-family, font-size, font-weight, font-style, color, line-height, text-align, background, text-decoration, margin, padding, border-top und border-bottom, jede wo sie etwas bedeutet.',
			],
			'Fonts' => [
				'en_US' => 'TrueType files named <Family>-<Style>.ttf in the folder private/pdf-fonts, such as Satoshi-Regular.ttf, put the family in the font list and in font-family, and a family needs its Regular file.',
				'de_DE' => 'TrueType-Dateien namens <Familie>-<Schnitt>.ttf in private/pdf-fonts, etwa Satoshi-Regular.ttf, stellen die Familie in die Schriftauswahl und in font-family; sie braucht ihre Regular-Datei.',
			],
			'Font limits' => [
				'en_US' => 'Text is set in the WinAnsi code page, so a character it lacks becomes a question mark, and every PDF carries the whole TrueType file, at most 2 MB each, with no subset.',
				'de_DE' => 'Der Text wird in der Codepage WinAnsi gesetzt, ein Zeichen, das sie nicht kennt, wird ein Fragezeichen, und jedes PDF trägt die ganze TrueType-Datei, je höchstens 2 MB, ohne Subsetting.',
			],
			'Settings' => [
				'en_US' => 'The Settings tab lists the tags of the templates, one per line; a template needs none, and there are none until the project sets them.',
				'de_DE' => 'Der Tab Einstellungen listet die Tags der Vorlagen, eines pro Zeile; eine Vorlage braucht keines, und es gibt keine, bis das Projekt sie setzt.',
			],
		],
		'callbacks' => [
			'/pdf/template/uses' => [
				'en_US' => 'Asked of each listener by uses(), and so by every deletion of a template: it answers template key, package and count, and a count above nought, or an answer that is none, keeps the template.',
				'de_DE' => 'Wird von uses() und so bei jedem Löschen einer Vorlage gefragt: Ein Listener antwortet Schlüssel, Paket und Anzahl, und eine Anzahl über null oder eine ungültige Antwort behält die Vorlage.',
			],
			'/pdf/vocabulary' => [
				'en_US' => 'Asked of each listener by vocabulary() for the groups of tokens, the slots and the sample data that a caller\'s templates may use; what is invalid in an answer is left out and the rest stays.',
				'de_DE' => 'Wird von vocabulary() nach den Tokengruppen, Platzhaltern und Beispieldaten gefragt, die die Vorlagen eines Aufrufers nutzen dürfen; Ungültiges fällt weg, der Rest bleibt.',
			],
			'/pdf/editor' => [
				'en_US' => 'Asked of each listener by editorAddress() for where the editor of a template or a block is: a path or a hash of the site, and whether it opens inside the screen that asked.',
				'de_DE' => 'Wird von editorAddress() bei jedem Listener gefragt, wo der Editor einer Vorlage oder eines Bausteins ist: ein Pfad oder ein Hash der Website und ob er im fragenden Bildschirm aufgeht.',
			],
			'/nino/http/output' => [
				'en_US' => 'Lets the responses of the workbench embed a blob: address, by one source added to their frame-src, so that the preview shows; a page of the site keeps its policy.',
				'de_DE' => 'Lässt die Antworten der Workbench eine blob:-Adresse einbetten, durch eine Quelle mehr im frame-src, damit die Vorschau erscheint; eine Seite der Website behält ihre Policy.',
			],
		],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	// gd decodes a picture that is no JPEG, dom reads the HTML of a document, zlib compresses every stream of the file and
	// iconv writes the text in the code page of the fonts; mbstring, which the kernel names, is not named again
	'php'					=> [ 'ext' => [ 'gd', 'dom', 'iconv', 'zlib' ] ],
	'requires'		=> [],
	// The layout, the blocks, the templates and the two pictures, and the numbers read from the owner's fonts
	'data'				=> [ '/data/pdf', '/data/pdf-fonts' ],
	'settings'		=> [
		'tags' => [
			'type'		=> 'lines',
			'label'		=> [ 'en_US' => 'Tags of the templates', 'de_DE' => 'Tags der Vorlagen' ],
			'hint'		=> [
				'en_US' => 'One tag per line - lower case letters, digits and hyphens, 2 to 40 characters, starting with a letter. A caller asks the templates by them, and a template keeps the tag it was saved with; one that is no longer on this list is marked in the list of templates and can still be saved, while a tag chosen anew has to be on the list.',
				'de_DE' => 'Ein Tag pro Zeile - Kleinbuchstaben, Ziffern und Bindestriche, 2 bis 40 Zeichen, mit einem Buchstaben am Anfang. Ein Aufrufer fragt die Vorlagen danach, und eine Vorlage behält das Tag, mit dem sie gespeichert wurde; eine, die nicht mehr auf dieser Liste steht, ist in der Liste der Vorlagen markiert und lässt sich weiter speichern, ein neu gewähltes Tag aber muss auf der Liste stehen.',
			],
			'default'	=> [],
		],
	],
];
