<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Newsletter/ can only ever serve
// \Nino\Modules\Newsletter.
return [
	'key'					=> 'newsletter',
	'name'				=> 'Newsletter',
	'description'	=> [
		'en_US' => 'Double opt-in signup with confirmation and unsubscribe links, and the subscriber list as a workbench panel.',
		'de_DE' => 'Double-Opt-in-Anmeldung mit Bestätigungs- und Abmeldelink, und die Abonnentenliste als Panel der Workbench.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			[ 'en_US' => 'The feature ships no form: place the Newsletter section from the Templates panel on the page that collects addresses.',
			  'de_DE' => 'Das Feature bringt kein Formular mit: setze die Newsletter-Section aus dem Panel Templates auf die Seite, die Adressen sammelt.' ],
		],
		'routes' => [
			'/.newsletter' => [
				'en_US' => 'Where the confirmation and unsubscribe links land.',
				'de_DE' => 'Wo die Bestätigungs- und Abmeldelinks ankommen.',
			],
		],
		'panel' => [
			'Newsletter' => [
				'en_US' => 'The addresses, a BCC line to copy, a CSV export. Sending the letter is a job for a mail client.',
				'de_DE' => 'Die Adressen, eine BCC-Zeile zum Kopieren, ein CSV-Export. Das Versenden selbst ist Sache eines Mailprogramms.',
			],
		],
		'callbacks' => [
			'/nino/admin/restore' => [
				'en_US' => 'Brings the subscriber list back with a restored backup.',
				'de_DE' => 'Holt die Abonnentenliste mit einer wiederhergestellten Sicherung zurück.',
			],
		],
		'install' => [
			'templates/page-newsletter.tpl' => [
				'en_US' => 'What /.newsletter renders.',
				'de_DE' => 'Was /.newsletter rendert.',
			],
			'templates/mail-newsletter-confirm.tpl' => [
				'en_US' => 'The confirmation mail.',
				'de_DE' => 'Die Bestätigungsmail.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Its words, into the Text panel.',
				'de_DE' => 'Seine Worte, ins Panel Texte.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.0.0',
	'nino'				=> '^1.0',
	'requires'		=> [],
	'settings'		=> [],
	// The files under data/ this feature owns - what a backup carries and
	// callbackRestore() merges
	'data'				=> [ '/data/newsletter.php', '/data/newsletter-removed.php' ],
];
