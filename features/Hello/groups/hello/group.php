<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Gruß', 'en_US' => 'Greeting' ],
	'knobs' => [
		'hello.space' => [ 'token' => '--hello-space', 'label' => [ 'de_DE' => 'Innenabstand', 'en_US' => 'Padding' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
	],
];
