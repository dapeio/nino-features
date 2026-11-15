<?php
// An application: a named set of features that work together, and the place to start in. See README.md
return [
	'key'					=> 'blog',
	'name'				=> [ 'en_US' => 'Blog', 'de_DE' => 'Blog' ],
	'description'	=> [
		'en_US' => 'The website, and posts that readers find by search, with a table of contents and a reading progress bar.',
		'de_DE' => 'Die Webseite, dazu Beiträge, die Leserinnen und Leser über die Suche finden, mit Inhaltsverzeichnis und Lesefortschritt.',
	],
	'features'		=> [ 'builder', 'design', 'consent', 'forms', 'modeswitch', 'seo', 'posts', 'search', 'toc', 'progress' ],
	'next'				=> '/_admin/#builder',
	'start'				=> 'starter',
	'menu'				=> null,
];
