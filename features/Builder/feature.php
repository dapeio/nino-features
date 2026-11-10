<?php
// features/Builder/feature.php - what the Features panel reads. The class is not
// declared here: features/Builder/ can only ever serve \Nino\Modules\Builder, and
// the parts below it - Admin/, Document/, Reader/, Writer/ - resolve the same
// way, one directory per class.
return [
	'key'					=> 'builder',
	'name'				=> [ 'en_US' => 'Builder', 'de_DE' => 'Builder' ],
	'description'	=> [
		'en_US' => 'The Builder lets you design the page templates of a site graphically. It offers every relevant component of a page and its main properties, with no need to touch code. Complex templates, in several languages and for three device sizes, are built by clicking alone. Components of other features are available in it as well.',
		'de_DE' => 'Der Builder ermöglicht die grafische Gestaltung der Seiten-Templates. Er bietet dafür alle relevanten Komponenten einer Seite und ihre wichtigsten Eigenschaften, ganz ohne Eingriff am Code. Damit lassen sich komplexe Seitentemplates, mehrsprachig und für drei Gerätegrößen, allein grafisch umsetzen. Komponenten anderer Features stehen ebenfalls zur Verfügung.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Builder' => [
				'en_US' => 'Lets you create, open, duplicate and delete the page templates of the project, and edit one right in its preview.',
				'de_DE' => 'Ermöglicht, die Seitentemplates des Projekts anzulegen, zu öffnen, zu duplizieren und zu löschen und eines direkt in seiner Vorschau zu bearbeiten.',
			],
			'Views of the preview' => [
				'en_US' => 'Lets you see the page as Mobile, Tablet, Desktop or Global and switch the language of its texts.',
				'de_DE' => 'Ermöglicht, die Seite als Mobile, Tablet, Desktop oder Global zu sehen und die Sprache ihrer Texte umzuschalten.',
			],
			'Settings dialogs' => [
				'en_US' => 'Lets you set every section, column and component in a dialog of blocks, such as Content, Link, Surface, Spacing, Width and alignment, Order and Loop.',
				'de_DE' => 'Ermöglicht, Section, Spalte und Komponente in einem Dialog aus Blöcken einzustellen, etwa Inhalt, Link, Fläche, Abstände, Breite und Ausrichtung, Reihenfolge und Loop.',
			],
			'Copy and Paste' => [
				'en_US' => 'Lets you copy a section, a column or a component with its texts and pictures and paste it on this page or another, also with Ctrl+C and Ctrl+V.',
				'de_DE' => 'Ermöglicht, eine Section, Spalte oder Komponente samt Texten und Bildern zu kopieren und auf dieser oder einer anderen Seite einzufügen, auch mit Strg+C und Strg+V.',
			],
			'Keys and slots' => [
				'en_US' => 'Lets you write the texts and choose the pictures of a page where they stand; they are kept as text keys and image slots of the project and go along with a copy.',
				'de_DE' => 'Ermöglicht, die Texte und Bilder einer Seite dort zu schreiben und zu wählen, wo sie stehen; sie liegen als Textschlüssel und Bildplätze im Projekt und gehen bei einer Kopie mit.',
			],
			'HTML+' => [
				'en_US' => 'Lets you edit a section as HTML+, in its source, where the blocks of the Builder do not reach.',
				'de_DE' => 'Ermöglicht, eine Section als HTML+ im Quelltext zu bearbeiten, wo die Blöcke des Builders nicht hinreichen.',
			],
			'Questions before a change' => [
				'en_US' => 'Asks you before a template is deleted, and in Production also before a frame is deleted or a section is renamed.',
				'de_DE' => 'Fragt Dich, bevor ein Template gelöscht wird, und in Produktion auch, bevor ein Rahmen gelöscht oder eine Section umbenannt wird.',
			],
			'Sections it cannot read' => [
				'en_US' => 'A template the Builder cannot show completely is marked in the list, and its unreadable sections stay as HTML+ blocks that you can still edit as source.',
				'de_DE' => 'Ein Template, das der Builder nicht ganz zeigen kann, ist in der Liste markiert, und seine unlesbaren Sections bleiben HTML+-Blöcke, die Du weiter im Quelltext bearbeiten kannst.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'content',
	'version' => '1.4.1',
	// 1.11, for what the builder draws and writes: the loop that sorts at
	// random (sort="random" of [stack] and [elements]), the alignment of a
	// title, a subtitle and a text for each viewport, which the registry of
	// the components names, and the hero as the style "hero" of a section's
	// title and subtitle, on top of what 1.10 brought - the section of tiles
	// (nino-tiles, its ratio classes, the tile as a kind of surface and
	// nino-grid--linked on a tile) - and 1.9 - the card that stretches its
	// link over itself (stretched), the slug of an element ([[.slug]]), the
	// options a method of the component answers, and nino-grid-gap for the
	// space between the cells of a grid
	'nino'				=> '^1.11',
	'requires'		=> [],
	// The page templates it edits are the project's own, in private/templates/,
	// and a backup carries them as project content - none of it belongs to this
	// feature, which is why switching it off leaves every page as it is
	'data'				=> [],
	'settings'		=> [],
];
