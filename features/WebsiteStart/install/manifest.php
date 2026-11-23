<?php return [
	// The start of a site: applied as a whole where the project has no page yet, and not at all where it has one
	'whenNoPages' => true,
	'routes' => [
		'GET://' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]', 'navs' => [ 'main' => 1, 'footer' => 1 ] ],
		'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]', 'navs' => [ 'main' => 2, 'footer' => 2 ] ],
		'GET://404' => [ 'uri' => '/404', 'body' => '[template /templates/page-404]', 'statusCode' => 404 ],
	],
	'templates' => [ 'frame-header.tpl', 'frame-footer.tpl', 'page-home.tpl', 'page-contact.tpl', 'page-404.tpl' ],
	'files' => [ 'images/template/page-home/hero/background.svg' ],
	// The picture of the title area is a place like any other: replace it in the Images panel
	'imageSlots' => [
		'/template/page-home/hero/background' => [
			'label' => [ 'en_US' => 'Home – hero image', 'de_DE' => 'Startseite – Titelbild' ],
			'width' => 1920,
			'height' => 1080,
			'filename' => 'template/page-home/hero/background.svg',
		],
	],
	'blacklist' => [ '/_nino/webpage/home/uri', '/_nino/webpage/contact/uri', '/_nino/webpage/404/uri' ],
];
