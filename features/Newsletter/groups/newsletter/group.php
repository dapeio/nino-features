<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Newsletter', 'en_US' => 'Newsletter' ],
	'knobs' => [
		'newsletter-signup.gap'    => [ 'token' => '--newsletter-signup-gap', 'label' => [ 'de_DE' => 'Abstand der Felder', 'en_US' => 'Space between the fields' ],
			'ladder' => [ 'var(--page-space-2)', 'calc( var(--page-space-2) * 1.5 )', 'var(--page-space-3)', 'var(--page-space-4)', 'var(--page-space-5)' ] ],
		'newsletter-signup.radius' => [ 'token' => '--newsletter-signup-radius', 'label' => [ 'de_DE' => 'Ecken der Felder', 'en_US' => 'Corners of the fields' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
		'newsletter-signup.size'   => [ 'token' => '--newsletter-signup-size', 'label' => [ 'de_DE' => 'Schriftgröße der Felder', 'en_US' => 'Text size of the fields' ],
			'ladder' => [ 'var(--page-text-1)', 'calc( ( var(--page-text-1) + var(--page-text-2) ) / 2 )', 'var(--page-text-2)', 'calc( ( var(--page-text-2) + var(--page-text-3) ) / 2 )', 'var(--page-text-3)' ] ],
	],
];
