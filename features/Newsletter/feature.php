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
				'en_US' => 'The first step of a link, and it changes nothing: a mail scanner that opens links ahead of the reader neither confirms nor unsubscribes anyone. A known token shows a page with a button (newsletter-action.tpl, drawn by page-newsletter.tpl where it reads [[/feature/newsletter/page/form]]); an unknown token answers 404, and so does the token of a pending signup that has waited longer than /nino/newsletter/pending-days (seven by default): the entry stays until the next signup sweeps it. A confirm token stays good once the address is confirmed - the page then says so, with no button - and an unsubscribe token goes with the entry.',
				'de_DE' => 'Der erste Schritt eines Links, und er ändert nichts: Ein Mailscanner, der Links vor dem Leser öffnet, bestätigt oder meldet niemanden ab. Ein bekannter Token zeigt eine Seite mit einem Knopf (newsletter-action.tpl, von page-newsletter.tpl dort gezeichnet, wo es [[/feature/newsletter/page/form]] liest); ein unbekannter Token antwortet 404, ebenso der Token einer ausstehenden Anmeldung, die länger als /nino/newsletter/pending-days (standardmäßig sieben) wartet: Der Eintrag bleibt, bis die nächste Anmeldung ihn aufräumt. Ein Bestätigungs-Token bleibt auch nach der Bestätigung gültig – die Seite sagt es dann, ohne Knopf –, ein Abmelde-Token geht mit dem Eintrag.',
			],
			'POST /.newsletter/action' => [
				'en_US' => 'The second step, which the button of that page posts: exactly one of confirm=<token> and unsubscribe=<token>, with [csrf] in the form. Confirms a pending address, or removes an entry and records the removal, and answers with the page of the outcome (200); an unknown or expired token, both fields or neither answer the invalid page (404), a post without the csrf token does nothing and keeps its 403, with a page that says it has expired in place of the kernel\'s bare false. Every answer is a page of /.newsletter, which gives its title and description to the head: this uri has no page of its own and so no details of a page, and a GET there is the site\'s 404.',
				'de_DE' => 'Der zweite Schritt, den der Knopf jener Seite abschickt: genau eins von confirm=<token> und unsubscribe=<token>, mit [csrf] im Formular. Bestätigt eine ausstehende Adresse, beziehungsweise entfernt einen Eintrag und vermerkt die Entfernung, und antwortet mit der Seite des Ergebnisses (200); ein unbekannter oder verfallener Token, beide Felder oder keins antworten mit der Seite „ungültig“ (404), ein Post ohne CSRF-Token tut nichts und behält sein 403, mit einer Seite, die sagt, dass sie abgelaufen ist, statt des nackten false des Kernels. Jede Antwort ist eine Seite von /.newsletter, die dem Kopf ihren Titel und ihre Beschreibung gibt: Diese Adresse hat keine eigene Seite und damit keine Seitendetails, und ein GET dorthin ist das 404 der Website.',
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
				'en_US' => 'What /.newsletter and the answer of /.newsletter/action render: the title and the text of the outcome at hand, the form where a link has just been opened, and a home button that leads to the home page under the website\'s directory.',
				'de_DE' => 'Was /.newsletter und die Antwort von /.newsletter/action rendern: Titel und Text des jeweiligen Ergebnisses, das Formular, wo gerade ein Link geöffnet wurde, und einen Home-Knopf, der zur Startseite unterhalb des Verzeichnisses der Website führt.',
			],
			'templates/newsletter-action.tpl' => [
				'en_US' => 'The form with the button that an opened link shows: a post to /.newsletter/action with [csrf] and the token in a hidden field named confirm or unsubscribe.',
				'de_DE' => 'Das Formular mit dem Knopf, das ein geöffneter Link zeigt: ein Post an /.newsletter/action mit [csrf] und dem Token in einem versteckten Feld namens confirm oder unsubscribe.',
			],
			'templates/page-newsletter-unsubscribe.tpl' => [
				'en_US' => 'What /.newsletter/unsubscribe renders: the form that asks for the address.',
				'de_DE' => 'Was /.newsletter/unsubscribe rendert: das Formular, das nach der Adresse fragt.',
			],
			'templates/mail-newsletter-confirm.tpl' => [
				'en_US' => 'The confirmation mail: its button opens the page where the signup is confirmed.',
				'de_DE' => 'Die Bestätigungsmail: Ihr Knopf öffnet die Seite, auf der die Anmeldung bestätigt wird.',
			],
			'templates/mail-newsletter-unsubscribe.tpl' => [
				'en_US' => 'The mail that carries the unsubscribe link: its button opens the page where the unsubscribe is made.',
				'de_DE' => 'Die Mail, die den Abmeldelink trägt: Ihr Knopf öffnet die Seite, auf der abgemeldet wird.',
			],
			'/nino/newsletter/<name>-template' => [
				'en_US' => 'Keys of config.php, none of them set by the unit - page-template, unsubscribe-template, action-template, confirm-template and unsubscribe-mail-template under /nino/newsletter/: the way a project swaps a page or a mail for a template of its own (the page that a link opens and answers with, the form that asks for the address, the form with the button, the confirmation mail, the mail with the unsubscribe link). Each defaults to the file of the unit under /templates.',
				'de_DE' => 'Schlüssel der config.php, keiner von der Einheit gesetzt – page-template, unsubscribe-template, action-template, confirm-template und unsubscribe-mail-template unter /nino/newsletter/: So tauscht ein Projekt eine Seite oder eine Mail gegen ein eigenes Template (die Seite, die ein Link öffnet und mit der geantwortet wird, das Formular, das nach der Adresse fragt, das Formular mit dem Knopf, die Bestätigungsmail, die Mail mit dem Abmeldelink). Jeder gilt standardmäßig für die Datei der Einheit unter /templates.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Its words, into the Text panel - among them the name, the title and the description of its two pages, /_nino/webpage/.newsletter/... and /_nino/webpage/.newsletter/unsubscribe/..., which the Routes panel changes like those of any page and which never replace a word the project wrote.',
				'de_DE' => 'Seine Worte, ins Panel Texte – darunter Name, Titel und Beschreibung seiner beiden Seiten, /_nino/webpage/.newsletter/... und /_nino/webpage/.newsletter/unsubscribe/..., die das Panel Routen wie die jeder Seite ändert und die ein Wort des Projekts nie ersetzen.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy, added to the Legal module\'s type - never replacing a section. Where there is no such module, nothing happens. A starting point, no legal advice. It names seven days, the default of /nino/newsletter/pending-days in config.php: whoever changes the key changes the section in the Elements panel too.',
				'de_DE' => 'Sein Abschnitt der Datenschutzerklärung, dem Typ des Moduls Legal hinzugefügt – ohne einen Abschnitt zu ersetzen. Wo es das Modul nicht gibt, passiert nichts. Ein Ausgangspunkt, keine Rechtsberatung. Er nennt sieben Tage, den Standard von /nino/newsletter/pending-days in der config.php: Wer den Wert ändert, passt den Abschnitt im Panel Elemente mit an.',
			],
			'templates/mail-header.tpl, templates/mail-footer.tpl' => [
				'en_US' => 'The mail frame, the Form module\'s two files byte for byte: whichever unit is applied first provides them. The logo of the header is the content form of [image], which carries the website\'s directory once.',
				'de_DE' => 'Der Mailrahmen, die beiden Dateien des Form-Moduls Byte für Byte: Welche Einheit zuerst angewendet wird, liefert sie. Das Logo im Kopf ist die Inhaltsform von [image], die das Verzeichnis der Website einmal trägt.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.1.0',
	// 1.8, for the toolset the code and the panel are written on. The hero of
	// page-newsletter.tpl has the classes Nino 1.7 renamed it to (nino-hero), with
	// no alias for the old ones
	'nino'				=> '^1.8',
	'requires'		=> [],
	'settings'		=> [],
	// The files under data/ this feature owns - what a backup carries and
	// callbackRestore() merges
	'data'				=> [ '/data/newsletter.php', '/data/newsletter-removed.php' ],
];
