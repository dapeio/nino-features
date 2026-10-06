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
			[ 'en_US' => 'The feature ships no signup form: place the Newsletter section from the Templates panel on the page that collects addresses.',
			  'de_DE' => 'Das Feature bringt kein Anmeldeformular mit: setze die Newsletter-Section aus dem Panel Templates auf die Seite, die Adressen sammelt.' ],
		],
		'routes' => [
			'/.newsletter' => [
				'en_US' => 'Where the confirmation and unsubscribe links land.',
				'de_DE' => 'Wo die Bestätigungs- und Abmeldelinks ankommen.',
			],
			'/.newsletter/unsubscribe' => [
				'en_US' => 'The way out without a link: an address in, a mail with the unsubscribe link out.',
				'de_DE' => 'Der Weg hinaus ohne Link: Adresse hinein, Mail mit dem Abmeldelink hinaus.',
			],
		],
		'panel' => [
			'Newsletter' => [
				'en_US' => 'The addresses with their status, a BCC line of the confirmed ones to copy, a CSV export. Sending the letter is a job for a mail client.',
				'de_DE' => 'Die Adressen mit ihrem Status, eine BCC-Zeile der bestätigten zum Kopieren, ein CSV-Export. Das Versenden selbst ist Sache eines Mailprogramms.',
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
			'templates/page-newsletter-unsubscribe.tpl' => [
				'en_US' => 'What /.newsletter/unsubscribe renders: the form that asks for the address.',
				'de_DE' => 'Was /.newsletter/unsubscribe rendert: das Formular, das nach der Adresse fragt.',
			],
			'templates/mail-newsletter-confirm.tpl' => [
				'en_US' => 'The confirmation mail.',
				'de_DE' => 'Die Bestätigungsmail.',
			],
			'templates/mail-newsletter-unsubscribe.tpl' => [
				'en_US' => 'The mail that carries the unsubscribe link.',
				'de_DE' => 'Die Mail, die den Abmeldelink trägt.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Its words, into the Text panel.',
				'de_DE' => 'Seine Worte, ins Panel Texte.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	'settings'		=> [],
	// The files under data/ this feature owns - what a backup carries and
	// callbackRestore() merges
	'data'				=> [ '/data/newsletter.php', '/data/newsletter-removed.php' ],
];
