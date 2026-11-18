<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Eingebettete Inhalte', 'en_US' => 'Embedded content' ],
	'knobs' => [
		'embed.radius' => [ 'token' => '--embed-radius', 'label' => [ 'de_DE' => 'Ecken der Fläche', 'en_US' => 'Corners of the surface' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
	],
];
