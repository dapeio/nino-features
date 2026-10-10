<?php
// features/Builder/feature.php - what the Features panel reads. The class is not
// declared here: features/Builder/ can only ever serve \Nino\Modules\Builder, and
// the parts below it - Admin/, Document/, Reader/, Writer/ - resolve the same
// way, one directory per class.
return [
	'key'					=> 'builder',
	'name'				=> [ 'en_US' => 'Builder', 'de_DE' => 'Builder' ],
	'description'	=> [
		'en_US' => 'Builds a page template out of sections, columns and components: it reads the file into a model and writes it back, and what it does not read it leaves byte for byte.',
		'de_DE' => 'Baut ein Seitentemplate aus Sections, Spalten und Komponenten: Es liest die Datei in ein Modell und schreibt sie zurück, und was es nicht liest, lässt es Byte für Byte stehen.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'<!-- nino:template-vpa nino-vpa ... -->' => [
				'en_US' => 'In the head of a page template, after the name line: the classes of the animation a new section is given (off, or no line, for none). The Builder writes it when the animation of the template is set, and a section can be like it.',
				'de_DE' => 'Im Kopf eines Seitentemplates, nach der Namenszeile: die Klassen der Animation, die eine neue Section bekommt (off oder keine Zeile für keine). Der Builder schreibt sie, wenn die Animation des Templates gesetzt wird, und eine Section kann ihr gleichen.',
			],
		],
		'routes' => [],
		'panel' => [
			'Builder' => [
				'en_US' => 'The page-*.tpl files of the project. #builder lists them by file - name (marked where the Builder cannot read it completely), frames, sections, routes - with New, Open, Duplicate (a copy under a new name, with copies of its text keys and image slots) and Delete (a template a route renders is kept). #builder/<file> is the editor, and the editor is the preview: every frame has tools - settings, up, down, duplicate, delete, and for a section edit as HTML+; a loop has settings and delete - every level ends in a button that adds to it, and settings open in a dialog. A section has the animation of the template, none or its own. A source is a text key, an image slot or a fixed value - in a loop a field of the element; one that means nothing where it stands is red and not saved. Saving checks the hash of the file; a save or copy that makes keys or slots needs the permissions of the Text Keys and Image Slots panels.',
				'de_DE' => 'Die page-*.tpl-Dateien des Projekts. #builder listet sie nach Datei - Name (markiert, wo der Builder sie nicht ganz liest), Rahmen, Sections, Routen - mit Neu, Öffnen, Duplizieren (Kopie unter neuem Namen, samt Textschlüsseln und Bildplätzen) und Löschen (mit Route bleibt es). #builder/<Datei> ist der Editor, und der Editor ist die Vorschau: Jeder Rahmen hat Werkzeuge - Einstellungen, hoch, runter, Duplizieren, Löschen, bei einer Section Als HTML+ bearbeiten; ein Loop nur Einstellungen und Löschen - jede Ebene endet in einem Knopf, der etwas hinzufügt, und Einstellungen öffnen im Dialog. Eine Section hat die Animation des Templates, keine oder ihre eigene. Eine Quelle ist ein Textschlüssel, ein Bildplatz oder ein fester Wert - im Loop ein Feld des Elements; eine, die dort nichts bedeutet, ist rot und wird nicht gespeichert. Das Speichern prüft den Hash der Datei; was Schlüssel oder Bildplätze anlegt, braucht auch die Rechte der Panels Textschlüssel und Bildplätze.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	// The Components module of 1.6: the registry the builder reads and the
	// shortcodes it writes calls of
	'nino'				=> '^1.6',
	'requires'		=> [],
	// The page templates it edits are the project's own, in private/templates/,
	// and a backup carries them as project content - none of it belongs to this
	// feature, which is why switching it off leaves every page as it is
	'data'				=> [],
	'settings'		=> [],
];
