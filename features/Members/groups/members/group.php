<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Mitglieder', 'en_US' => 'Members' ],
	'knobs' => [
		'members-login.space' => [ 'token' => '--members-login-space', 'label' => [ 'de_DE' => 'Innenabstand des Formulars', 'en_US' => 'Space inside the form' ],
			'ladder' => [ 'var(--page-space-1)', 'var(--page-space-2)', 'calc( var(--page-space-2) * 1.5 )', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'members-login.radius' => [ 'token' => '--members-login-radius', 'label' => [ 'de_DE' => 'Ecken von Formular, Feld und Knopf', 'en_US' => 'Corners of form, field and button' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
