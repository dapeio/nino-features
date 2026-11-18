<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Menü mit Panels', 'en_US' => 'Menu with panels' ],
	'knobs' => [
		'menu.width'  => [ 'token' => '--menu-width', 'label' => [ 'de_DE' => 'Breite des Panels', 'en_US' => 'Width of the panel' ],
			'ladder' => [ '24rem', '30rem', '36rem', '44rem', '52rem' ] ],
		'menu.gap'    => [ 'token' => '--menu-gap', 'label' => [ 'de_DE' => 'Abstand der Spalten', 'en_US' => 'Space between the columns' ],
			'ladder' => [ 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)', 'var(--page-space-5)', 'var(--page-space-6)' ] ],
		'menu.radius' => [ 'token' => '--menu-radius', 'label' => [ 'de_DE' => 'Ecken des Panels', 'en_US' => 'Corners of the panel' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
	],
];
