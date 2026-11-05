<?php
// features/Logos/install/manifest.php - what a project gets when the feature
// is activated. A unit is applied without overwriting (see
// \Nino\Features::activate()): a project that already has an element type
// called logo keeps it, with every field and every element it holds, and a text
// key it already has stays as it is.
//
// The type, copied to /elements/logo.php; the Elements panel's labels for its
// fields and the word of the pause button of a band, merged into
// text/<locale>.php. The labels are the workbench's words, not the site's, so the
// unit blacklists them and the Text panel leaves them out. No template, no route:
// [logos] is written where a row of logos belongs.
return [
	'elementTypes'	=> [ 'logo.php' ],
	'blacklist'			=> [
		'/_admin/elements/field/logo/name',
		'/_admin/elements/field/logo/image',
		'/_admin/elements/field/logo/url',
		'/_admin/elements/field/logo/image-dark',
		'/_admin/elements/field/logo/order',
	],
];
