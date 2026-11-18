<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Öffnungszeiten', 'en_US' => 'Opening hours' ],
	'knobs' => [
		'hours.gap' => [ 'token' => '--hours-gap', 'label' => [ 'de_DE' => 'Abstand von Woche, Abweichungen und Hinweis', 'en_US' => 'Space between the week, the closures and the note' ],
			'ladder' => [ 'var(--page-space-2)', 'calc( var(--page-space-3) * .75 )', 'var(--page-space-3)', 'var(--page-space-4)', 'var(--page-space-5)' ] ],
	],
];
