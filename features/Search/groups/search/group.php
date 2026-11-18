<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Suche', 'en_US' => 'Search' ],
	'knobs' => [
		'search-form.radius' => [ 'token' => '--search-form-radius', 'label' => [ 'de_DE' => 'Rundung von Feld und Knopf', 'en_US' => 'Corners of field and button' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
		'search-form.gap' => [ 'token' => '--search-form-gap', 'label' => [ 'de_DE' => 'Abstand zwischen Feld und Knopf', 'en_US' => 'Space between field and button' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
	],
];
