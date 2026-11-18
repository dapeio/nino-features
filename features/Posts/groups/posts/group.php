<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Beiträge', 'en_US' => 'Posts' ],
	'knobs' => [
		'posts-pager.radius' => [ 'token' => '--posts-pager-radius', 'label' => [ 'de_DE' => 'Rundung der Seitenzahlen', 'en_US' => 'Corners of the page numbers' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
		'posts-pager.gap' => [ 'token' => '--posts-pager-gap', 'label' => [ 'de_DE' => 'Abstand zwischen den Seitenzahlen', 'en_US' => 'Space between the page numbers' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
	],
];
