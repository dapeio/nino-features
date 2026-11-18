<?php
declare(strict_types=1);

return [
	'group'      => 'toc',
	'label'      => [ 'de_DE' => 'Inhaltsverzeichnis', 'en_US' => 'Table of contents' ],
	'icon'       => 'list',
	'in'         => [ 'flow' ],
	'preview'    => 'text',
	'variants'   => [ 'numbered', 'box' ],
	'labels'     => [
		'regular'  => [ 'de_DE' => 'Linie', 'en_US' => 'Line' ],
		'numbered' => [ 'de_DE' => 'Nummeriert', 'en_US' => 'Numbered' ],
		'box'      => [ 'de_DE' => 'Kasten', 'en_US' => 'Box' ],
	],
	'attributes' => [
		'levels' => [ 'type' => 'choice', 'options' => [ '2,3', '2', '3' ], 'default' => '2,3', 'class' => false,
			'labels' => [ '2,3' => [ 'de_DE' => 'Ebene 2 und 3', 'en_US' => 'Levels 2 and 3' ], '2' => [ 'de_DE' => 'Nur Ebene 2', 'en_US' => 'Level 2 only' ], '3' => [ 'de_DE' => 'Nur Ebene 3', 'en_US' => 'Level 3 only' ] ],
			'label' => [ 'de_DE' => 'Ebenen', 'en_US' => 'Levels' ], 'hint' => [ 'de_DE' => 'Die Überschriften, aus denen die Liste gebaut wird.', 'en_US' => 'The headings the list is built from.' ] ],
		'within' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Entnommen aus', 'en_US' => 'Taken from' ], 'hint' => [ 'de_DE' => 'Ein Selektor wie #article: Die Überschriften kommen aus diesem Element. Leer nimmt die ganze Seite.', 'en_US' => 'A selector such as #article: the headings come from inside that element. Empty takes the whole page.' ] ],
		'title'  => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Titel', 'en_US' => 'Title' ], 'hint' => [ 'de_DE' => 'Was über der Liste steht. Leer nimmt das Wort aus dem Panel Texte.', 'en_US' => 'What stands over the list. Empty takes the word of the Text panel.' ] ],
		'sticky' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'Klebt am Rand', 'en_US' => 'Sticks to the edge' ], 'hint' => [ 'de_DE' => 'Die Liste bleibt oben im Fenster stehen, solange die Spalte reicht, in der sie steht. Unter 1024 px bleibt sie, wo sie steht.', 'en_US' => 'The list stays at the top of the window for as long as the column it stands in lasts. Below 1024 px it stays where it stands.' ] ],
	],
];
