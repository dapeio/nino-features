<?php
// The class is not declared: features/Themelab/ can only serve \Nino\Modules\Themelab
return [
	'key'					=> 'themelab',
	'name'				=> [ 'en_US' => 'Themelab', 'de_DE' => 'Themelab' ],
	'description'	=> [
		'en_US' => 'Themelab is where designs and blocks are developed, in the form of a theme. It lists the blocks of the project and of the theme in use and opens each in an editor, writes the header and the footer of the site as frames, and shows what a theme package will hold. A block is made of the components the register has - a section with its rows and columns, texts and picture places - and is kept as a block of the project, ready for the Builder to put into a page. It needs the Builder, whose tree view and properties column it works with, and Themes, whose theme it develops on.',
		'de_DE' => 'Themelab ist der Ort, an dem Designs und Blocks in Form eines Themes entwickelt werden. Es listet die Blocks des Projekts und des angewandten Themes und öffnet jeden in einem Editor, schreibt Header und Footer der Website als Rahmen und zeigt, was ein Theme-Paket enthalten wird. Ein Block besteht aus den Komponenten, die das Register hat – einer Sektion mit Zeilen und Spalten, Texten und Bildplätzen – und wird als Block des Projekts abgelegt, damit der Builder ihn in eine Seite setzen kann. Es setzt den Builder voraus, mit dessen Baum-Ansicht und Eigenschaften-Spalte es arbeitet, und Themes, auf dessen Theme es aufbaut.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'private/blocks/<key>/' => [
				'en_US' => 'A block of the project is a folder of block.json, block.tpl and text/<language>.json; what the panel saves is held to the rule of the kernel before a file is made.',
				'de_DE' => 'Ein Block des Projekts ist ein Ordner aus block.json, block.tpl und text/<Sprache>.json; was das Panel speichert, wird vor dem ersten Schreiben an die Regel des Kernels gehalten.',
			],
		],
		'routes' => [
			'?nino-preview=<token>' => [
				'en_US' => 'A page of the site asked with the token of a block draft by the session that holds it shows the block alone, with the stylesheet of the site and its components marked.',
				'de_DE' => 'Eine Seite der Website, mit dem Token eines Blockentwurfs von der haltenden Sitzung aufgerufen, zeigt den Block allein mit dem Stylesheet der Website und markierten Komponenten.',
			],
		],
		'panel' => [
			'Blocks' => [
								'en_US' => 'The first tab: the own blocks and those of the theme in use, one switch apart - new, copy, rename, delete, export and import; the right is /_admin/themelab/manage.',
								'de_DE' => 'Der erste Reiter: die eigenen Blocks und die des angewandten Themes, ein Umschalter trennt sie – neu, Kopie, Name, Löschen, Export, Import; das Recht: /_admin/themelab/manage.',
			],
			'Frames' => [
				'en_US' => 'The second tab writes the header and the footer in the same editor, saved in frame-header and frame-footer; it is the only place that writes a frame or the call of html.',
				'de_DE' => 'Der zweite Reiter schreibt Header und Footer im selben Editor, gespeichert in frame-header und frame-footer; nur hier wird ein Rahmen oder der Aufruf html geschrieben.',
			],
			'Theme' => [
				'en_US' => 'The third tab shows the theme in use - name, version, origin - the number of own blocks and frames, and what a theme package will hold; it only reads.',
				'de_DE' => 'Der dritte Reiter zeigt das angewandte Theme – Name, Version, Herkunft –, die Zahl eigener Blocks und Rahmen und was ein Theme-Paket enthalten wird; er liest nur.',
			],
			'Block editor' => [
				'en_US' => 'The tree of a block, its picture drawn by the site and the properties of each component beside the preview - with Save, Save as and the steps of Undo.',
				'de_DE' => 'Der Baum eines Blocks, sein Bild von der Website und die Eigenschaften jeder Komponente neben der Vorschau – mit Speichern, Speichern unter und den Schritten von Rückgängig.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',					// the kernel with the draft of a preview and the blocks with variants
	'php'					=> [],
	'requires'		=> [ 'builder', 'themes' ],
	'data'				=> [],
	'settings'		=> [],
	'assets'			=> [],
];
