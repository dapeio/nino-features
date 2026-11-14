<?php
// An application: a named set of features that work together, and the place to start in. See README.md
return [
	'key'					=> 'full',
	'name'				=> [ 'en_US' => 'Full installation', 'de_DE' => 'Vollinstallation' ],
	'description'	=> [
		'en_US' => 'Everything the catalogue offers apart from examples, switched on - to try it all, and for projects that need it all.',
		'de_DE' => 'Alles, was der Katalog bietet, außer Beispielen, eingeschaltet - zum Ausprobieren und für Projekte, die alles brauchen.',
	],
	// Every feature of the catalogue but the examples (maturity "Example"); the vCard takes its own route for the card, since the website of this application provides the start page
	'features'		=> [
		'builder', 'design', 'consent', 'forms', 'modeswitch', 'seo',
		'posts', 'search', 'toc', 'progress',
		'lightbox', 'gallery', 'menu', 'social', 'embed', 'compare', 'copy', 'typewriter', 'ticker', 'countdown', 'hours', 'logos',
		'mailer', 'newsletter', 'stats', 'redirects', 'protected', 'vcard',
	],
	'next'				=> '/_admin/#dashboard',
	'start'				=> 'starter',
	'menu'				=> null,
];
