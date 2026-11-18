<?php
declare(strict_types=1);

return [
	'group'      => 'posts',
	'label'      => [ 'de_DE' => 'Beiträge', 'en_US' => 'Posts' ],
	'icon'       => 'newspaper',
	'in'         => [ 'page', 'flow', 'section-row' ],
	'offers'     => [ 'flow', 'section-row', 'section-col' ],
	'through'    => true,
	'children'   => 'nodes',
	'preview'    => 'cells',
	'attributes' => [
		'section' => [ 'type' => 'choice', 'options' => '\Nino\Modules\Posts::sectionOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Rubrik', 'en_US' => 'Section' ],
			'hint' => [ 'de_DE' => 'Die Rubrik aus /data/posts.php, deren Beiträge gezeichnet werden. Die Rubrik der Seite nimmt die, unter deren Adresse die Seite steht, sonst die erste.', 'en_US' => 'The section of /data/posts.php whose posts are drawn. The section of the page takes the one the page stands under, else the first.' ] ],
		'limit'   => [ 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 100, 'label' => [ 'de_DE' => 'Höchstens', 'en_US' => 'At most' ],
			'hint' => [ 'de_DE' => 'Die neuesten Beiträge ohne Seitenzahlen, als Anreißer. 0 zeichnet die Seite der Liste, die gerade dran ist.', 'en_US' => 'The newest posts without paging, as a teaser. 0 draws the page of the list that is on.' ] ],
	],
];
