<?php
// features/Social/install/manifest.php - what a project gets when the feature
// is activated. A unit is applied without overwriting (see
// \Nino\Features::activate()): a project that already has an element type
// called social, the template or one of the labels keeps every one of them.
return [

	// Copied to /elements/social.php: the model and four links to start from
	'elementTypes'	=> [ 'social.php' ],

	// What the Design feature's header and footer frames include, holding
	// [social]. A frame cannot name the shortcode itself: without this
	// feature, [social] would stand on the page as text, while a template the
	// project does not have renders as nothing
	'templates'			=> [ 'social-links.tpl' ],

	// The Elements panel's labels for the type's fields - the workbench's
	// words, not the site's, so the Text panel does not offer them
	'blacklist'			=> [
		'/_admin/elements/field/social/title',
		'/_admin/elements/field/social/icon',
		'/_admin/elements/field/social/link',
		'/_admin/elements/field/social/order',
		'/_admin/elements/field/social/hidden',
	],
];
