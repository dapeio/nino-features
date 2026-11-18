<?php
declare(strict_types=1);

return [
	'group'      => 'posts',
	'label'      => [ 'de_DE' => 'Weitere Beiträge', 'en_US' => 'More posts' ],
	'icon'       => 'move-horizontal',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'block',
	'attributes' => [
		'section' => [ 'type' => 'choice', 'options' => '\Nino\Modules\Posts::sectionOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Rubrik', 'en_US' => 'Section' ],
			'hint' => [ 'de_DE' => 'Die Rubrik, in deren Reihenfolge der vorherige und der nächste Beitrag stehen. Die Rubrik der Seite nimmt die, unter deren Adresse die Seite steht, sonst die erste.', 'en_US' => 'The section whose order the previous and the next post are in. The section of the page takes the one the page stands under, else the first.' ] ],
		'prev'    => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Wort für den vorherigen Beitrag', 'en_US' => 'Word for the previous post' ],
			'hint' => [ 'de_DE' => 'Leer nimmt die Wörter aus dem Panel Texte.', 'en_US' => 'Empty takes the words of the Text panel.' ] ],
		'next'    => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Wort für den nächsten Beitrag', 'en_US' => 'Word for the next post' ],
			'hint' => [ 'de_DE' => 'Leer nimmt die Wörter aus dem Panel Texte.', 'en_US' => 'Empty takes the words of the Text panel.' ] ],
		'label'   => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Name der Navigation', 'en_US' => 'Name of the navigation' ],
			'hint' => [ 'de_DE' => 'Wie ein Screenreader sie nennt. Leer nimmt die Wörter aus dem Panel Texte.', 'en_US' => 'What a screen reader calls it. Empty takes the words of the Text panel.' ] ],
	],
];
