<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Hell/Dunkel', 'en_US' => 'Light/dark' ],
	'knobs' => [
		'mode-switch.radius' => [ 'token' => '--mode-switch-radius', 'label' => [ 'de_DE' => 'Ecken des Schalters', 'en_US' => 'Corners of the switch' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
