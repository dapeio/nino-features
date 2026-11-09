<?php
// features/Builder/feature.php - what the Features panel reads. The class is not
// declared here: features/Builder/ can only ever serve \Nino\Modules\Builder, and
// the parts below it - Admin/, Document/, Reader/, Writer/ - resolve the same
// way, one directory per class.
return [
	'key'					=> 'builder',
	'name'				=> [ 'en_US' => 'Builder', 'de_DE' => 'Builder' ],
	'description'	=> [
		'en_US' => 'Builds the page templates of a site out of sections, columns and components in a visual editor with a preview of the page. It writes the page-*.tpl files of the project together with the text keys and image slots they use, and keeps what it cannot read as HTML+.',
		'de_DE' => 'Baut die Seitentemplates einer Website aus Sections, Spalten und Komponenten in einem visuellen Editor mit Vorschau der Seite. Er schreibt die page-*.tpl-Dateien des Projekts samt den Textschlüsseln und Bildplätzen, die sie nutzen, und behält, was er nicht lesen kann, als HTML+.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'<div class="nino-wrap nino-wrap--vpa">' => [
				'en_US' => 'The wrap around all sections of a template; its nino-wrap--vpa and nino-vpa--* classes set the animation, which the row of a section joins with nino-vpa.',
				'de_DE' => 'Der Wrap um alle Sections eines Templates; seine Klassen nino-wrap--vpa und nino-vpa--* setzen die Animation, der sich die Zeile einer Section mit nino-vpa anschließt.',
			],
			'<section class="nino-section">' => [
				'en_US' => 'A section is one row of columns, or tiles with nino-tiles; its settings are classes on the section, its row and its columns, and a picture behind is a block nino-grid-bg holding [image /slot alt=""].',
				'de_DE' => 'Eine Section ist eine Zeile mit Spalten oder Kacheln mit nino-tiles; ihre Einstellungen sind Klassen an Section, Zeile und Spalten, ein Bild dahinter ein Block nino-grid-bg mit [image /slot alt=""].',
			],
			'[sections /type id="news"]…[/sections]' => [
				'en_US' => '[sections /type] makes a section of every element, [repeat /type] repeats a row, [table /type] with [col] a table, [tabs] with [panel] a group; a link goes on with [[.slug]] after the page.',
				'de_DE' => '[sections /type] macht je Element eine Section, [repeat /type] wiederholt eine Zeile, [table /type] mit [col] eine Tabelle, [tabs] mit [panel] eine Gruppe; [[.slug]] führt Links nach der Seite weiter.',
			],
			'[card padding="3"]…[/card]' => [
				'en_US' => 'A component is a shortcode call the registry allows in a column, a card or a panel: its text a /key, value or field, its picture a /slot; [card] holds its children; spacing and alignment are classes.',
				'de_DE' => 'Eine Komponente ist ein Shortcode-Aufruf, den die Registry in Spalte, Karte oder Panel erlaubt: Text ein /key, Wert oder Feld, Bild ein /slot; [card] hält ihre Kinder; Abstand, Ausrichtung: Klassen.',
			],
			'<!-- nino:html -->…<!-- /nino:html -->' => [
				'en_US' => 'The file is read into a model and written back; what the Builder does not read stays byte for byte, and these markers set a block aside as HTML+ without reading it.',
				'de_DE' => 'Die Datei wird in ein Modell gelesen und zurückgeschrieben; was der Builder nicht liest, bleibt Byte für Byte, und diese Marker legen einen Block ungelesen als HTML+ beiseite.',
			],
		],
		'routes' => [],
		'panel' => [
			'Builder' => [
				'en_US' => 'The page-*.tpl files of the project, listed with New, Open, Duplicate and Delete, and the editor of one, which is its preview with a button that adds at every level.',
				'de_DE' => 'Die page-*.tpl-Dateien des Projekts, gelistet mit Neu, Öffnen, Duplizieren und Löschen, und der Editor einer Datei, der seine Vorschau ist, mit einem Knopf zum Hinzufügen auf jeder Ebene.',
			],
			'Views of the preview' => [
				'en_US' => 'The preview draws the page as Mobile, Tablet, Desktop or Global and its texts in the language of the picker; the dialogs do not follow the view, they have a viewport switch of their own.',
				'de_DE' => 'Die Vorschau zeichnet die Seite als Mobile, Tablet, Desktop oder Global und ihre Texte in der Sprache der Auswahl; die Dialoge folgen der Ansicht nicht, sie haben einen eigenen Viewport-Schalter.',
			],
			'Settings dialogs' => [
				'en_US' => 'A section, a column and a component each open a dialog of blocks - among them Content, Link, Surface, Spacing, Width and alignment, Order, Loop, Properties - and show the ones they have.',
				'de_DE' => 'Section, Spalte und Komponente öffnen je einen Dialog aus Blöcken - darunter Inhalt, Link, Fläche, Abstände, Breite und Ausrichtung, Reihenfolge, Loop, Eigenschaften - und zeigen die, die sie haben.',
			],
			'Copy and Paste' => [
				'en_US' => 'Copy keeps a section, a column or a component with its texts and pictures in the clip of the browser, Paste puts it behind a frame or on another page; Ctrl+C and Ctrl+V do it for the selected frame.',
				'de_DE' => 'Kopieren legt Section, Spalte oder Komponente samt Texten und Bildern in die Ablage des Builders, Einfügen setzt sie hinter einen Rahmen oder auf eine andere Seite; Strg+C/V gelten für den gewählten.',
			],
			'Keys and slots' => [
				'en_US' => 'A text is a key and a picture an image slot under /template/<file>/<section>/<name>; a save or an upload makes them, a copy or rename takes them along, with the permissions of the panels owning them.',
				'de_DE' => 'Ein Text ist ein Key und ein Bild ein Bildplatz unter /template/<Datei>/<Section>/<Name>; Speichern oder Upload legt sie an, Kopie oder Umbenennung nimmt sie mit, mit den Rechten der Panels dahinter.',
			],
			'HTML+' => [
				'en_US' => 'Edit as HTML+ opens the source of a section to edit by hand; saving reads it as a section again, and what the Builder still cannot read stays an HTML+ block.',
				'de_DE' => 'Als HTML+ bearbeiten öffnet den Quelltext einer Section zum Bearbeiten von Hand; das Speichern liest ihn wieder als Section, und was der Builder weiter nicht lesen kann, bleibt ein HTML+-Block.',
			],
			'Questions before a change' => [
				'en_US' => 'Deleting a template always asks; deleting a frame that holds others or renaming a section with keys of its own asks in Production only, and a choice that is no yes or no is asked in both modes.',
				'de_DE' => 'Ein Template zu löschen fragt immer; einen Rahmen mit anderen darin zu löschen oder eine Section mit eigenen Keys umzubenennen fragt nur in Produktion, eine Wahl ohne Ja oder Nein in beiden Modi.',
			],
			'What it does not read' => [
				'en_US' => 'The list marks a template the Builder cannot read completely; the editor shows such a section as an HTML+ block with the line and the reason and writes it back byte for byte.',
				'de_DE' => 'Die Liste markiert ein Template, das der Builder nicht ganz lesen kann; der Editor zeigt eine solche Section als HTML+-Block mit Zeile und Grund und schreibt sie Byte für Byte zurück.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'content',
	'version' => '1.3.0',
	// 1.10, for what the builder draws and writes: the section of tiles
	// (nino-tiles, its ratio classes, the tile as a kind of surface and
	// nino-grid--linked on a tile), on top of what 1.9 brought - the card that
	// stretches its link over itself (stretched), the slug of an element
	// ([[.slug]]), the options a method of the component answers, and
	// nino-grid-gap for the space between the cells of a grid
	'nino'				=> '^1.10',
	'requires'		=> [],
	// The page templates it edits are the project's own, in private/templates/,
	// and a backup carries them as project content - none of it belongs to this
	// feature, which is why switching it off leaves every page as it is
	'data'				=> [],
	'settings'		=> [],
];
