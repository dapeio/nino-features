<?php
// features/Vcard/install/manifest.php - what a project gets when the feature is
// activated. A unit is applied without overwriting (see \Nino\Features::activate()):
// a project that already has a route for / keeps it, and so do a template and a
// word it already has
return [

	// The start page, where the project has none. The uri is the page's own Element-URI: the
	// feature's init() puts the page at /vcard when / belongs to another page
	'routes'		=> [
		'GET://' => [ 'uri' => '/vcard', 'body' => '[template /templates/page-vcard]' ],
	],

	// The page: the project's head and foot around [vcard]
	'templates'	=> [ 'page-vcard.tpl' ],
];
