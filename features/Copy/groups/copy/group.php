<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Kopierknopf', 'en_US' => 'Copy button' ],
	'knobs' => [
		'copy.radius' => [ 'token' => '--copy-radius', 'label' => [ 'de_DE' => 'Rundung des Knopfs', 'en_US' => 'Radius of the button' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
