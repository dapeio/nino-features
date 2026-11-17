<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Einwilligung', 'en_US' => 'Consent' ],
	'knobs' => [
		'consent.space'  => [ 'token' => '--consent-space', 'label' => [ 'de_DE' => 'Innenabstand des Banners', 'en_US' => 'Space inside the banner' ],
			'ladder' => [ 'var(--page-space-2)', 'calc( var(--page-space-2) * 1.5 )', 'var(--page-space-3)', 'var(--page-space-4)', 'var(--page-space-5)' ] ],
		'consent.radius' => [ 'token' => '--consent-radius', 'label' => [ 'de_DE' => 'Ecken des Banners', 'en_US' => 'Corners of the banner' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', 'calc( var(--page-radius) * 4 )' ] ],
	],
];
