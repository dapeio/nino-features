<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Vorher/Nachher', 'en_US' => 'Before and after' ],
	'knobs' => [
		'compare.radius' => [ 'token' => '--compare-radius', 'label' => [ 'de_DE' => 'Ecken des Bildes', 'en_US' => 'Corners of the picture' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
	],
];
