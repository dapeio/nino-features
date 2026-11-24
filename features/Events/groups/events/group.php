<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Termine', 'en_US' => 'Events' ],
	'knobs' => [
		'event-when.space' => [ 'token' => '--event-when-space', 'label' => [ 'de_DE' => 'Innenabstand des Chips', 'en_US' => 'Space inside the chip' ],
			'ladder' => [ 'calc( var(--page-space-1) * .5 )', 'calc( var(--page-space-1) * .75 )', 'var(--page-space-1)', 'var(--page-space-2)', 'var(--page-space-3)' ] ],
		'event-when.radius' => [ 'token' => '--event-when-radius', 'label' => [ 'de_DE' => 'Ecken des Chips', 'en_US' => 'Corners of the chip' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
