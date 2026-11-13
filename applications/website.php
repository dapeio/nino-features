<?php
// An application: a named set of features that work together, and the place to start in. See README.md
return [
	'key'					=> 'website',
	'name'				=> [ 'en_US' => 'Website', 'de_DE' => 'Webseite' ],
	'description'	=> [
		'en_US' => 'A website you compose and style yourself, with a consent banner, forms, a dark mode and search engine settings.',
		'de_DE' => 'Eine Webseite, die Sie selbst aufbauen und gestalten, mit Einwilligungsbanner, Formularen, Dunkelmodus und Suchmaschineneinstellungen.',
	],
	'features'		=> [ 'builder', 'design', 'consent', 'forms', 'modeswitch', 'seo' ],
	'next'				=> '/_admin/#builder',
	'start'				=> 'starter',
];
