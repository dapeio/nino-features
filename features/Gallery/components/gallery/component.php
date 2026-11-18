<?php
declare(strict_types=1);

return [
	'group'      => 'gallery',
	'label'      => [ 'de_DE' => 'Galerie', 'en_US' => 'Gallery' ],
	'icon'       => 'images',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'image',
	'variants'   => [ 'mat', 'seamless' ],
	'labels'     => [
		'regular'  => [ 'de_DE' => 'Raster', 'en_US' => 'Grid' ],
		'mat'      => [ 'de_DE' => 'Passepartout', 'en_US' => 'Mat' ],
		'seamless' => [ 'de_DE' => 'Fugenlos', 'en_US' => 'Seamless' ],
	],
	'attributes' => [
		'album'   => [ 'type' => 'choice', 'options' => '\Nino\Modules\Gallery::albumOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Album', 'en_US' => 'Album' ],
			'hint' => [ 'de_DE' => 'Das Album, das gezeichnet wird. Ohne Wahl ist es das erste Album. Alben legst Du im Panel Galerie an.', 'en_US' => 'The album to draw. Without a choice it is the first album. You make albums in the Gallery panel.' ] ],
		'columns' => [ 'type' => 'choice', 'options' => [ '', '2', '3', '4', '5', '6' ], 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Spalten', 'en_US' => 'Columns' ],
			'labels' => [ '' => [ 'de_DE' => 'Wie im Album', 'en_US' => 'As in the album' ] ],
			'hint' => [ 'de_DE' => 'Wie viele Vorschaubilder höchstens nebeneinander stehen; auf schmalen Bildschirmen werden es von selbst weniger. Ohne Wahl gilt die Zahl, die für das Album eingestellt ist.', 'en_US' => 'How many thumbnails stand beside each other at most; on a narrow screen there are fewer by themselves. Without a choice the number set for the album applies.' ] ],
	],
];
