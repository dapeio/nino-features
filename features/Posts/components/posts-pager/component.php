<?php
declare(strict_types=1);

return [
	'group'      => 'posts',
	'label'      => [ 'de_DE' => 'Blättern', 'en_US' => 'Pager' ],
	'icon'       => 'chevrons-right',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'block',
	'attributes' => [
		'section' => [ 'type' => 'choice', 'options' => '\Nino\Modules\Posts::sectionOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Rubrik', 'en_US' => 'Section' ],
			'hint' => [ 'de_DE' => 'Die Rubrik, deren Seiten es nennt. Die Rubrik der Seite nimmt die, unter deren Adresse die Seite steht, sonst die erste.', 'en_US' => 'The section whose pages it names. The section of the page takes the one the page stands under, else the first.' ] ],
		'prev'    => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Wort für die neuere Seite', 'en_US' => 'Word for the newer page' ],
			'hint' => [ 'de_DE' => 'Leer nimmt die Wörter aus dem Panel Texte.', 'en_US' => 'Empty takes the words of the Text panel.' ] ],
		'next'    => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Wort für die ältere Seite', 'en_US' => 'Word for the older page' ],
			'hint' => [ 'de_DE' => 'Leer nimmt die Wörter aus dem Panel Texte.', 'en_US' => 'Empty takes the words of the Text panel.' ] ],
		'label'   => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Name der Navigation', 'en_US' => 'Name of the navigation' ],
			'hint' => [ 'de_DE' => 'Wie ein Screenreader sie nennt. Leer nimmt die Wörter aus dem Panel Texte.', 'en_US' => 'What a screen reader calls it. Empty takes the words of the Text panel.' ] ],
	],
];
