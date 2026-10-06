<?php
// features/Stats/install/elements/privacy.php - this feature's section of the
// privacy policy, in German and English, written for it. Added to the type
// 'privacy' of Nino's Legal module by \Nino\Features::activate() through the
// unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()): a
// section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, statistics stand in
// 600-699. The text is in the form the sanitizer gives a field with 'blocks'
// and carries no '&', no entity and no '['. It names what the code does: a
// count per page and day and the host of the referrer, no cookie and no ip
// address (see README.md, "What is counted, and where").
return [
	'*' => [
		'stats' => [
			'order' => 600,
		],
	],
	'de_DE' => [
		'stats' => [
			'title' => 'Statistik',
			'text' 	=> '<p>Um zu sehen, welche Seiten gefragt sind, zählt diese Website Seitenaufrufe je Seite und Tag und hält fest, von welcher Website ein Aufruf kam (nur deren Domain). Dafür setzt sie kein Cookie, liest keine IP-Adresse und erkennt Dich bei einem späteren Besuch nicht wieder. Die Zahlen lassen sich keiner Person zuordnen; personenbezogene Daten fallen dabei nicht an.</p>',
		],
	],
	'en_US' => [
		'stats' => [
			'title' => 'Statistics',
			'text' 	=> '<p>To see which pages are in demand, this website counts page views per page and day and records which website a visit came from (its domain only). It sets no cookie for this, reads no IP address and does not recognise you on a later visit. The figures cannot be linked to any person; no personal data arise.</p>',
		],
	],
];
