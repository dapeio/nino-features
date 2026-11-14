<?php
// An application: a named set of features that work together, and the place to start in. See README.md
return [
	'key'					=> 'vcard',
	'name'				=> [ 'en_US' => 'vCard', 'de_DE' => 'vCard' ],
	'description'	=> [
		'en_US' => 'One quiet page with your contact details, a photo or logo, links, a button that saves the contact, and the legal pages.',
		'de_DE' => 'Eine ruhige Seite mit Ihren Kontaktdaten, Foto oder Logo, Links, einem Knopf, der den Kontakt speichert, und den Rechtsseiten.',
	],
	'features'		=> [ 'vcard' ],
	'next'				=> '/_admin/#vcard',
	'start'				=> 'own',
	'menu'				=> 'folded',
];
