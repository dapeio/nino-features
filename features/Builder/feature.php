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
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Builder' => [
				'en_US' => 'The page-*.tpl files of the project. #builder lists them - frames, sections, HTML+ blocks, whether the Builder reads the file completely, the routes that use it - with New and Delete (a template a route renders is kept). #builder/<file> is the editor: the tree of sections, columns, stacks and components on the left (a row is dragged inside its level; cut, paste, duplicate and delete are in its menu), a static preview drawn from the model alone on the right, and the settings of a node in a dialog from the button on its row or frame. A source is a text key, an image slot or a fixed value - in a stack a field of the element; one that means nothing where it stands is red and not saved. A block the Builder does not read is edited as HTML+. Saving checks the hash of the file: after a change the editor offers to load it again or to save anyway. A save that makes or moves text keys or image slots also needs the permissions of the Text Keys and Image Slots panels.',
				'de_DE' => 'Die page-*.tpl-Dateien des Projekts. #builder listet sie - Rahmen, Sections, HTML+-Blöcke, ob der Builder die Datei ganz liest, ihre Routen - mit Neu und Löschen (ein Template mit Route bleibt). #builder/<Datei> ist der Editor: links der Baum aus Sections, Spalten, Stapeln und Komponenten (Zeilen werden innerhalb ihrer Ebene gezogen; Ausschneiden, Einfügen, Duplizieren, Löschen im Menü), rechts eine statische Vorschau, und die Einstellungen eines Knotens in einem Dialog vom Knopf auf Zeile oder Rahmen. Eine Quelle ist ein Textschlüssel, ein Bildplatz oder ein fester Wert - im Stapel ein Feld des Elements; eine, die dort nichts bedeutet, ist rot und wird nicht gespeichert. Ein Block, den der Builder nicht liest, wird als HTML+ bearbeitet. Das Speichern prüft den Hash der Datei und bietet nach einer Änderung Neu laden oder Trotzdem speichern an. Ein Speichern, das Textschlüssel oder Bildplätze anlegt, braucht auch die Berechtigungen der Panels Textschlüssel und Bildplätze.',
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
