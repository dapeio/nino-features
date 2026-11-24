<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Dateien', 'en_US' => 'Files' ],
	'knobs' => [
		'files-browser.space' => [ 'token' => '--files-browser-space', 'label' => [ 'de_DE' => 'Innenabstand des Browsers', 'en_US' => 'Space inside the browser' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'files-browser.radius' => [ 'token' => '--files-browser-radius', 'label' => [ 'de_DE' => 'Ecken des Browsers', 'en_US' => 'Corners of the browser' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
		'files-upload.space' => [ 'token' => '--files-upload-space', 'label' => [ 'de_DE' => 'Innenabstand des Formulars', 'en_US' => 'Space inside the form' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'files-upload.radius' => [ 'token' => '--files-upload-radius', 'label' => [ 'de_DE' => 'Ecken des Formulars', 'en_US' => 'Corners of the form' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
