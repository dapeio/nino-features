<?php
// features/Hours/install/manifest.php - what a project gets when the feature
// is activated. A unit is applied without overwriting (see
// \Nino\Features::activate()): a project that already has an element type
// called locations or closures keeps it, with every field and every element it
// holds, and a text key it already has stays as it is.
//
// The two types, copied to /elements; the words of the feature, merged into
// text/<locale>.php, among them the Elements panel's labels for the fields of
// the types - the workbench's words, not the site's, so the unit blacklists
// them and the Text panel leaves them out. No template, no route: [hours] is
// written where the hours belong.
return [
	'elementTypes'	=> [ 'locations.php', 'closures.php' ],
	'blacklist'			=> [
		'/_admin/elements/field/locations/name',
		'/_admin/elements/field/locations/address',
		'/_admin/elements/field/locations/mon',
		'/_admin/elements/field/locations/tue',
		'/_admin/elements/field/locations/wed',
		'/_admin/elements/field/locations/thu',
		'/_admin/elements/field/locations/fri',
		'/_admin/elements/field/locations/sat',
		'/_admin/elements/field/locations/sun',
		'/_admin/elements/field/locations/note',
		'/_admin/elements/field/closures/location',
		'/_admin/elements/field/closures/from',
		'/_admin/elements/field/closures/until',
		'/_admin/elements/field/closures/times',
		'/_admin/elements/field/closures/reason',
	],
];
