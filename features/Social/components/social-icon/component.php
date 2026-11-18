<?php
declare(strict_types=1);

return [
	'group'      => 'social',
	'label'      => [ 'de_DE' => 'Social-Icon', 'en_US' => 'Social icon' ],
	'icon'       => 'share-2',
	'in'         => [ 'flow', 'footer-col', 'header-actions' ],
	'preview'    => 'block',
	'variants'   => [ 'badges', 'outline' ],
	'labels'     => [
		'regular' => [ 'de_DE' => 'Schlicht', 'en_US' => 'Plain' ],
		'badges'  => [ 'de_DE' => 'Plaketten', 'en_US' => 'Badges' ],
		'outline' => [ 'de_DE' => 'Umriss', 'en_US' => 'Outline' ],
	],
	'attributes' => [
		'name'  => [ 'type' => 'choice', 'options' => '\Nino\Modules\Social::iconOptions', 'default' => 'link', 'class' => false, 'label' => [ 'de_DE' => 'Icon', 'en_US' => 'Icon' ], 'hint' => [ 'de_DE' => 'Eines der Icons des Features; was nicht in der Liste steht, zeichnet das Link-Icon.', 'en_US' => 'One of the icons of the feature; anything that is not in the list draws the link icon.' ] ],
		'label' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Name für Vorleseprogramme', 'en_US' => 'Name for screen readers' ], 'hint' => [ 'de_DE' => 'Leer: das Icon steht neben einem Wort und wird nicht vorgelesen. Mit Name steht es allein und heißt so.', 'en_US' => 'Empty: the icon stands beside a word and is not read out. With a name it stands alone and is called so.' ] ],
	],
];
