<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Social-Links', 'en_US' => 'Social links' ],
	'knobs' => [
		'social.size' => [ 'token' => '--social-size', 'label' => [ 'de_DE' => 'Größe eines Icons', 'en_US' => 'Size of an icon' ],
			'ladder' => [ '1em', '1.25em', '1.5em', '2em', '2.5em' ] ],
		'social.gap'  => [ 'token' => '--social-gap', 'label' => [ 'de_DE' => 'Abstand der Links', 'en_US' => 'Space between the links' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-2) * .75 )', 'var(--page-space-2)', 'var(--page-space-4)', 'var(--page-space-5)' ] ],
	],
];
