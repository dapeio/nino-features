<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Countdown', 'en_US' => 'Countdown' ],
	'knobs' => [
		'countdown.size'   => [ 'token' => '--countdown-size', 'label' => [ 'de_DE' => 'Größe der Ziffern', 'en_US' => 'Size of the digits' ],
			'ladder' => [ '.8em', '.9em', '1em', '1.25em', '1.6em' ] ],
		'countdown.gap'    => [ 'token' => '--countdown-gap', 'label' => [ 'de_DE' => 'Abstand der Teile', 'en_US' => 'Space between the parts' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'countdown.radius' => [ 'token' => '--countdown-radius', 'label' => [ 'de_DE' => 'Ecken der Kacheln', 'en_US' => 'Corners of the tiles' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
	],
];
