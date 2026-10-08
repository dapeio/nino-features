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
			[ 'en_US' => 'The feature ships no signup form: write one into an HTML+ block of the Builder, or into a page template of your own, on the page that collects addresses. It is <form class="nino-form nino-newsletter-form" action="[[/nino/dir]]/.newsletter"> with [csrf], the empty honeypot input location (class nino-form-trap, plus tabindex="-1" autocomplete="off" aria-hidden="true"), an input named email, a submit button reading [[/feature/newsletter/label/submit]] and a <p class="nino-form-message"> inside the form. Nino.ui.js posts it and shows the /feature/newsletter/info/ texts in the form\'s first <p>, whatever its class.',
			  'de_DE' => 'Das Feature bringt kein Anmeldeformular mit: schreibe eines in einen HTML+-Block des Builders oder in ein eigenes Seitentemplate, auf der Seite, die Adressen sammelt. Es ist <form class="nino-form nino-newsletter-form" action="[[/nino/dir]]/.newsletter"> mit [csrf], dem leeren Honigtopf-Input location (Klasse nino-form-trap, dazu tabindex="-1" autocomplete="off" aria-hidden="true"), einem Input namens email, einem Absende-Button mit [[/feature/newsletter/label/submit]] und einem <p class="nino-form-message"> im Formular. Nino.ui.js schickt es ab und zeigt die Texte unter /feature/newsletter/info/ im ersten <p> des Formulars, welche Klasse es auch hat.' ],
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
			'POST /.newsletter' => [
				'en_US' => 'The signup: email and the honeypot location, which has to stay empty, with [csrf] in the form. 200 once the confirmation mail went out - the same for a new, a pending and a subscribed address - 400, 418, 429 or 500.',
				'de_DE' => 'Die Anmeldung: email und der Honigtopf location, der leer bleiben muss, mit [csrf] im Formular. 200, sobald die Bestätigungsmail raus ist – gleich für eine neue, eine ausstehende und eine bestätigte Adresse – sonst 400, 418, 429 oder 500.',
			],
			'/.newsletter?confirm=<token> and ?unsubscribe=<token>' => [
				'en_US' => 'Confirms a pending address, or removes an entry and records the removal. An unknown token answers 404.',
				'de_DE' => 'Bestätigt eine ausstehende Adresse, beziehungsweise entfernt einen Eintrag und vermerkt die Entfernung. Ein unbekannter Token antwortet 404.',
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
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy, added to the Legal module\'s type - never replacing a section. Where there is no such module, nothing happens. A starting point, no legal advice. It names seven days, the default of /nino/newsletter/pending-days in config.php: whoever changes the key changes the section in the Elements panel too.',
				'de_DE' => 'Sein Abschnitt der Datenschutzerklärung, dem Typ des Moduls Legal hinzugefügt – ohne einen Abschnitt zu ersetzen. Wo es das Modul nicht gibt, passiert nichts. Ein Ausgangspunkt, keine Rechtsberatung. Er nennt sieben Tage, den Standard von /nino/newsletter/pending-days in der config.php: Wer den Wert ändert, passt den Abschnitt im Panel Elemente mit an.',
			],
			'templates/mail-header.tpl, templates/mail-footer.tpl' => [
				'en_US' => 'The mail frame, the Form module\'s two files byte for byte: whichever unit is applied first provides them.',
				'de_DE' => 'Der Mailrahmen, die beiden Dateien des Form-Moduls Byte für Byte: Welche Einheit zuerst angewendet wird, liefert sie.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.0.0',
	'nino'				=> '^1.4',
	'requires'		=> [],
	'settings'		=> [],
	// The files under data/ this feature owns - what a backup carries and
	// callbackRestore() merges
	'data'				=> [ '/data/newsletter.php', '/data/newsletter-removed.php' ],
];
