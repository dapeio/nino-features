<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Geschützter Bereich', 'en_US' => 'Protected area' ],
	'knobs' => [
		'protected-form.gap' => [ 'token' => '--protected-form-gap', 'label' => [ 'de_DE' => 'Abstand im Formular', 'en_US' => 'Space in the form' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'protected-form.radius' => [ 'token' => '--protected-form-radius', 'label' => [ 'de_DE' => 'Ecken von Feld und Knopf', 'en_US' => 'Corners of field and button' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
