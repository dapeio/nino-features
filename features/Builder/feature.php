<?php
// The class is not declared: features/Builder/ can only serve \Nino\Modules\Builder
return [
	'key'					=> 'builder',
	'name'				=> [ 'en_US' => 'Builder', 'de_DE' => 'Builder' ],
	'description'	=> [
		'en_US' => 'Builder shows a page of the site as a row of blocks. A block is moved, copied or removed on the page itself, and a text is clicked and written where it stands; whoever may design opens a block as a tree of components with their settings, chooses where each content comes from, keeps a block as a block of the project and makes, copies and deletes pages. Nothing is written before Save, which keeps the template, the texts and the picture slots together and the state before it as the last state.',
		'de_DE' => 'Der Builder zeigt eine Seite der Website als Folge von Blocks. Ein Block wird auf der Seite selbst verschoben, kopiert oder entfernt, und ein Text wird dort angeklickt und geschrieben, wo er steht; wer gestalten darf, öffnet einen Block als Baum aus Komponenten mit ihren Einstellungen, wählt, woher jeder Inhalt kommt, legt einen Block als Block des Projekts ab und macht, kopiert und löscht Seiten. Vor dem Speichern wird nichts geschrieben; es hält Vorlage, Texte und Bildplätze zusammen und behält den Stand davor als letzten Stand.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'page-<name>.tpl' => [
				'en_US' => 'The template of a page is read as a row of blocks, each a call on its top level; free markup and calls without a schema stand as locked blocks.',
				'de_DE' => 'Die Vorlage einer Seite wird als Folge von Blocks gelesen, je ein Aufruf auf oberster Ebene; freies HTML und Aufrufe ohne Schema stehen als gesperrte Blocks da.',
			],
			'frame-header.tpl' => [
				'en_US' => 'The two frames of the site, the header and the footer, are listed beside the pages and open in the same view with their blocks fixed.',
				'de_DE' => 'Die beiden Rahmen der Website, Header und Footer, stehen neben den Seiten und öffnen in derselben Ansicht mit festen Blocks.',
			],
		],
		'routes' => [
			'?nino-preview=<token>' => [
				'en_US' => 'Any page of the site, asked with the token of a draft by the session that holds it, shows the draft with its blocks marked while the token lasts.',
				'de_DE' => 'Jede Seite der Website, mit dem Token eines Entwurfs von der Sitzung aufgerufen, die ihn hält, zeigt den Entwurf mit markierten Blocks, solange das Token gilt.',
			],
			'?nino-preview=<token> (tile)' => [
				'en_US' => 'The same query with the token of a tile draws one block alone on a bare page, small and without scripts, for the picture of the block in the dialog Insert block.',
				'de_DE' => 'Dieselbe Abfrage mit dem Token einer Kachel zeichnet einen Block allein auf einer nackten Seite, klein und ohne Skripte, für das Bild des Blocks im Dialog Block einfügen.',
			],
		],
		'panel' => [
			'Builder' => [
				'en_US' => 'The screen for the pages: the table with its search, and for a page the page with its outline and a bar on the block under the pointer; the right is /_admin/builder/manage.',
				'de_DE' => 'Der Bildschirm für die Seiten: die Tabelle mit Suche, und je Seite die Seite mit ihrer Gliederung und einer Leiste am Block unter dem Zeiger; das Recht: /_admin/builder/manage.',
			],
			'Advanced' => [
				'en_US' => 'On a block of the page: the tree of its components, which are put in, moved and taken out where the register allows, and the settings of each by its schema.',
				'de_DE' => 'An einem Block der Seite: der Baum seiner Komponenten, die eingefügt, verschoben und entfernt werden, wo das Register es erlaubt, und die Einstellungen jeder nach ihrem Schema.',
			],
			'/_admin/builder/*' => [
				'en_US' => 'The door /_admin/builder/manage is needed by every role; content/*, design/* and develop/* below it make that blocks and texts, components and frames, or also the markup of html.',
				'de_DE' => 'Die Tür /_admin/builder/manage braucht jede Rolle; content/*, design/* und develop/* darunter machen daraus Blocks und Texte, Komponenten und Rahmen oder auch das Markup von html.',
			],
			'Source' => [
				'en_US' => 'Where the text, the address or the picture of a component comes from: a text that is there or a new one, a fixed text, a page, an address, a picture slot or a field of the element of a loop.',
				'de_DE' => 'Woher Text, Adresse oder Bild einer Komponente kommen: ein Text, der da ist, oder ein neuer, ein fester Text, eine Seite, eine Adresse, ein Bildplatz oder ein Feld des Elements einer Schleife.',
			],
			'Save as own block …' => [
				'en_US' => 'In the settings of a block, for whoever designs: the block of the page is kept as a block of the project, which then stands in the dialog Insert block and can be deleted there.',
				'de_DE' => 'In den Einstellungen eines Blocks, für alle, die gestalten: der Block der Seite wird als Block des Projekts abgelegt, steht danach im Dialog Block einfügen und lässt sich dort wieder löschen.',
			],
			'New page …' => [
				'en_US' => 'In the view #builder/@new, for whoever designs and may write routes: names, addresses, menus and a first block make the template, routes, menu entry and words in one step.',
				'de_DE' => 'In der Ansicht #builder/@new, für alle, die gestalten und Routen schreiben dürfen: Namen, Adressen, Menüs und ein erster Block machen Vorlage, Routen, Menüeintrag und Texte in einem Schritt.',
			],
			'Duplicate (row)' => [
				'en_US' => 'In a row of the table, for the same accounts: the view #builder/@new/<page> makes a copy of the page with its texts and pictures, from names and addresses that are free.',
				'de_DE' => 'In einer Zeile der Tabelle, für dieselben Konten: die Ansicht #builder/@new/<Seite> macht eine Kopie der Seite mit ihren Texten und Bildern, aus freien Namen und Adressen.',
			],
			'Delete (row)' => [
				'en_US' => 'In a row of the table, for the same accounts: one question names the page, the texts that stay because others read them and the pages that link to it, and a yes takes the page away whole.',
				'de_DE' => 'In einer Zeile der Tabelle, für dieselben Konten: eine Rückfrage nennt die Seite, die Texte, die bleiben, weil andere sie lesen, und die Seiten, die auf sie verweisen; ein Ja nimmt die Seite ganz weg.',
			],
			'+ Insert block' => [
				'en_US' => 'Between the blocks, at the end of the page and in the outline: a dialog of the available blocks by category, each drawn in the active theme, put in as a draft until Save.',
				'de_DE' => 'Zwischen den Blocks, am Ende der Seite und in der Gliederung: ein Dialog der verfügbaren Blocks nach Kategorie, jeder im aktiven Theme gezeichnet, bis zum Speichern nur ein Entwurf.',
			],
		],
		'callbacks' => [
			'/nino/pages/removed' => [
				'en_US' => 'When the kernel has removed a page, the Builder takes away what it kept of it: the last state and the drafts of its previews.',
				'de_DE' => 'Hat der Kernel eine Seite entfernt, nimmt der Builder weg, was er von ihr hielt: den letzten Stand und die Entwürfe ihrer Vorschauen.',
			],
		],
		'install' => [
			'blocks/<key>/' => [
				'en_US' => 'The blocks of the project, one folder each with block.json, block.tpl and text/<language>.json, made by Save as own block and read by the kernel like every block.',
				'de_DE' => 'Die Blocks des Projekts, je ein Ordner mit block.json, block.tpl und text/<Sprache>.json, angelegt von „Als eigenen Block speichern“ und vom Kernel wie jeder Block gelesen.',
			],
			'data/builder/' => [
				'en_US' => 'The last state of each page that was saved, one step back, which the backup carries, an update does not change and the removal of the page takes away.',
				'de_DE' => 'Der letzte Stand jeder gespeicherten Seite, einen Schritt zurück, den das Backup mitnimmt, ein Update nicht ändert und das Entfernen der Seite wegnimmt.',
			],
		],
	],
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',					// the kernel with the draft of a preview, the trace of the drawing, the words put in one step and the bars of the workbench
	'php'					=> [],
	'requires'		=> [],
	'data'				=> [ '/data/builder' ],
	// How the block under the pointer shows in the page: "frame" draws a frame of the accent colour round it and leaves the others as they are, "fade" leaves it as it is and
	// draws the others at four fifths of their strength
	'settings'		=> [
		'dim' => [
			'type'		=> 'select',
			'label'		=> [ 'en_US' => 'Show the block under the pointer', 'de_DE' => 'Block unter dem Zeiger zeigen' ],
			'hint'		=> [
				'en_US' => 'How the page of the Builder tells which block the pointer or the keyboard is on.',
				'de_DE' => 'Wie die Seite im Builder zeigt, auf welchem Block der Zeiger oder die Tastatur steht.',
			],
			'options'	=> [
				'frame'	=> [ 'en_US' => 'Frame round the block', 'de_DE' => 'Rahmen um den Block' ],
				'fade'	=> [ 'en_US' => 'Fade the other blocks', 'de_DE' => 'Andere Blocks abdunkeln' ],
			],
			'default'	=> 'frame',
		],
	],
	'assets'			=> [],
];
