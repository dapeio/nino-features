<?php
// The class is not declared: features/Blocks/ can only serve \Nino\Modules\Blocks
return [
	'key'					=> 'blocks',
	'name'				=> [ 'en_US' => 'Blocks', 'de_DE' => 'Blocks' ],
	'description'	=> [
		'en_US' => 'Blocks lists the blocks of the project and of the theme in use, and opens each in an editor. A block is made of the components the register has - a section with its rows and columns, texts and picture places - and is kept as a block of the project, ready for the Builder to put into a page. It needs the Builder, whose tree view and lightbox it works with.',
		'de_DE' => 'Blocks listet die Blocks des Projekts und des angewandten Themes und öffnet jeden in einem Editor. Ein Block besteht aus den Komponenten, die das Register hat – einer Sektion mit Zeilen und Spalten, Texten und Bildplätzen – und wird als Block des Projekts abgelegt, damit der Builder ihn in eine Seite setzen kann. Es setzt den Builder voraus, mit dessen Baum-Ansicht und Lightbox es arbeitet.',
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
				'en_US' => 'The blocks of the project and of the theme in use: new block, copy, new name, removal, and the export and import of own blocks as a package; the right is /_admin/blocks/manage.',
				'de_DE' => 'Die Blocks des Projekts und des angewandten Themes: neuer Block, Kopie, neuer Name, Löschen sowie Export und Import eigener Blocks als Paket; das Recht: /_admin/blocks/manage.',
			],
			'Frames' => [
				'en_US' => 'The third tab writes the header and the footer in the same editor, saved in frame-header and frame-footer; it is the only place that writes a frame or the call of html.',
				'de_DE' => 'Der dritte Reiter schreibt Header und Footer im selben Editor, gespeichert in frame-header und frame-footer; nur hier wird ein Rahmen oder der Aufruf html geschrieben.',
			],
			'Block editor' => [
				'en_US' => 'The tree of a block, its picture drawn by the site and the lightbox of each component - in the views of the Builder - with Save, Save as and the steps of Undo.',
				'de_DE' => 'Der Baum eines Blocks, sein Bild von der Website und die Lightbox jeder Komponente – in den Ansichten des Builders – mit Speichern, Speichern unter und den Schritten von Rückgängig.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',					// the kernel with the draft of a preview and the blocks with variants
	'php'					=> [],
	'requires'		=> [ 'builder' ],
	'data'				=> [],
	'settings'		=> [],
	'assets'			=> [],
];
