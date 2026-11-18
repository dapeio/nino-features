<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Galerie', 'en_US' => 'Gallery' ],
	'knobs' => [
		'gallery.gap' => [ 'token' => '--gallery-gap', 'label' => [ 'de_DE' => 'Abstand der Bilder', 'en_US' => 'Space between the pictures' ],
			'ladder' => [ '.25rem', 'var(--page-space-1)', '.75rem', '1.25rem', 'var(--page-space-3)' ] ],
		'gallery.radius' => [ 'token' => '--gallery-radius', 'label' => [ 'de_DE' => 'Ecken der Bilder', 'en_US' => 'Corners of the pictures' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
	],
];
