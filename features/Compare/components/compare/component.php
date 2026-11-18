<?php
declare(strict_types=1);

return [
	'group'      => 'compare',
	'label'      => [ 'de_DE' => 'Vorher und nachher', 'en_US' => 'Before and after' ],
	'icon'       => 'square-split-horizontal',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'image',
	'attributes' => [
		'before'      => [ 'type' => 'image', 'default' => '', 'label' => [ 'de_DE' => 'Bild vorher', 'en_US' => 'Picture before' ],
			'hint' => [ 'de_DE' => 'Ohne beide Bilder wird nichts gezeichnet.', 'en_US' => 'Without both pictures nothing is drawn.' ] ],
		'after'       => [ 'type' => 'image', 'default' => '', 'label' => [ 'de_DE' => 'Bild nachher', 'en_US' => 'Picture after' ] ],
		'alt'         => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Beschreibung', 'en_US' => 'Description' ],
			'hint' => [ 'de_DE' => 'Was das Paar zeigt, für Besucher, die es nicht sehen. Angeben: Zwei Bilder ohne Beschreibung sind zwei Verzierungen. Leer nimmt den Alternativtext des ersten Bildes.', 'en_US' => 'What the pair shows, for a visitor who cannot see it. Say it: two pictures with no description are two decorations. Empty takes the alternative text of the first picture.' ] ],
		'beforeLabel' => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Name der ersten Seite', 'en_US' => 'Name of the first side' ],
			'hint' => [ 'de_DE' => 'Wie die erste Seite heißt. Leer nimmt das Wort aus dem Panel Texte.', 'en_US' => 'What the first side is called. Empty takes the word of the Text panel.' ] ],
		'afterLabel'  => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Name der zweiten Seite', 'en_US' => 'Name of the second side' ],
			'hint' => [ 'de_DE' => 'Wie die zweite Seite heißt. Leer nimmt das Wort aus dem Panel Texte.', 'en_US' => 'What the second side is called. Empty takes the word of the Text panel.' ] ],
		'start'       => [ 'type' => 'int', 'min' => 0, 'max' => 100, 'default' => 50, 'class' => false, 'label' => [ 'de_DE' => 'Start des Trenners', 'en_US' => 'Start of the divider' ],
			'hint' => [ 'de_DE' => 'Wo der Trenner steht, wenn die Seite aufgeht, 0 bis 100.', 'en_US' => 'Where the divider stands when the page opens, 0 to 100.' ] ],
		'ratio'       => [ 'type' => 'choice', 'options' => [ '16-9', '4-3', '1-1', '3-2' ], 'default' => '4-3', 'label' => [ 'de_DE' => 'Seitenverhältnis', 'en_US' => 'Ratio' ],
			'labels' => [ '16-9' => [ 'de_DE' => '16:9', 'en_US' => '16:9' ], '4-3' => [ 'de_DE' => '4:3', 'en_US' => '4:3' ], '1-1' => [ 'de_DE' => '1:1', 'en_US' => '1:1' ], '3-2' => [ 'de_DE' => '3:2', 'en_US' => '3:2' ] ] ],
	],
];
