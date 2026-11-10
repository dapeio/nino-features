<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Newsletter/ can only ever serve
// \Nino\Modules\Newsletter.
return [
	'key'					=> 'newsletter',
	'name'				=> 'Newsletter',
	'description'	=> [
		'en_US' => 'Newsletter lets visitors sign up with double opt-in, with a confirmation and an unsubscribe link. The subscriber list is a panel of the workbench. Sending the letter is a job for a mail client.',
		'de_DE' => 'Der Newsletter ermöglicht die Anmeldung per Double-Opt-in mit Bestätigungs- und Abmeldelink. Die Abonnentenliste gibt es als Panel der Workbench. Das Versenden selbst ist Sache eines Mailprogramms.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[newsletter-signup]' => [
				'en_US' => 'Draws the signup form for POST /.newsletter; it is also the Builder\'s component Newsletter signup, and the words are in the Text panel.',
				'de_DE' => 'Zeichnet das Anmeldeformular für POST /.newsletter; es ist auch die Komponente Newsletter-Anmeldung des Builders, und die Worte stehen im Panel Texte.',
			],
			'[newsletter-signup inline="1"]' => [
				'en_US' => 'The field and the button on one line; the label is then read by screen readers only, and class="my-class" adds a class of your own.',
				'de_DE' => 'Feld und Knopf in einer Zeile; die Beschriftung lesen dann nur noch Screenreader, und class="my-class" hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'<form class="nino-form nino-newsletter-form" action="[[/nino/dir]]/.newsletter">' => [
				'en_US' => 'To write the form yourself, take [csrf], the empty honeypot location, an input email, a submit button and a p.nino-form-message; Nino.ui.js posts it.',
				'de_DE' => 'Wer das Formular selbst schreibt, nimmt [csrf], den leeren Honigtopf location, einen Input email, einen Absende-Button und ein p.nino-form-message; Nino.ui.js schickt es ab.',
			],
		],
		'routes' => [
			'POST /.newsletter' => [
				'en_US' => 'Takes the signup with email and the empty honeypot location and answers 200 once the confirmation mail went out, else 400, 418, 429 or 500.',
				'de_DE' => 'Nimmt die Anmeldung mit email und leerem Honigtopf location entgegen und antwortet 200, sobald die Bestätigungsmail raus ist, sonst 400, 418, 429 oder 500.',
			],
			'/.newsletter?confirm=<token> and ?unsubscribe=<token>' => [
				'en_US' => 'The first step of a link shows a page with a button and changes nothing, so a mail scanner neither confirms nor unsubscribes anyone.',
				'de_DE' => 'Der erste Schritt eines Links zeigt eine Seite mit einem Knopf und ändert nichts, damit ein Mailscanner niemanden bestätigt oder abmeldet.',
			],
			'POST /.newsletter/action' => [
				'en_US' => 'The second step, which the button posts, confirms an address or removes an entry (confirm or unsubscribe, with [csrf]) and answers with the page of the outcome.',
				'de_DE' => 'Der zweite Schritt, den der Knopf abschickt, bestätigt eine Adresse oder entfernt einen Eintrag (confirm oder unsubscribe, mit [csrf]) und antwortet mit der Seite des Ergebnisses.',
			],
			'/.newsletter/unsubscribe' => [
				'en_US' => 'The way out without a link: an address in, a mail with the unsubscribe link out.',
				'de_DE' => 'Der Weg hinaus ohne Link: Adresse hinein, Mail mit dem Abmeldelink hinaus.',
			],
		],
		'panel' => [
			'Newsletter' => [
				'en_US' => 'Shows the addresses with their status, a BCC line of the confirmed ones to copy, and a CSV export; a confirmed entry carries the time and IP address of its confirmation.',
				'de_DE' => 'Zeigt die Adressen mit ihrem Status, eine BCC-Zeile der bestätigten zum Kopieren und einen CSV-Export; ein bestätigter Eintrag trägt Zeit und IP-Adresse seiner Bestätigung.',
			],
		],
		'callbacks' => [
			'/nino/admin/restore' => [
				'en_US' => 'Brings the subscriber list back with a restored backup; an address removed since stays off it until its owner confirms again.',
				'de_DE' => 'Holt die Abonnentenliste mit einer wiederhergestellten Sicherung zurück; eine seither entfernte Adresse bleibt draußen, bis ihr Inhaber erneut bestätigt.',
			],
		],
		'install' => [
			'templates/page-newsletter.tpl' => [
				'en_US' => 'The page for /.newsletter and the answer of /.newsletter/action; with newsletter-action.tpl, page-newsletter-unsubscribe.tpl, mail-newsletter-confirm.tpl and mail-newsletter-unsubscribe.tpl.',
				'de_DE' => 'Die Seite für /.newsletter und die Antwort von /.newsletter/action; dazu newsletter-action.tpl, page-newsletter-unsubscribe.tpl, mail-newsletter-confirm.tpl und mail-newsletter-unsubscribe.tpl.',
			],
			'/nino/newsletter/<name>-template' => [
				'en_US' => 'Keys of config.php (page-template, unsubscribe-template, action-template, confirm-template, unsubscribe-mail-template) with which a project swaps a page or a mail for its own.',
				'de_DE' => 'Schlüssel der config.php (page-template, unsubscribe-template, action-template, confirm-template, unsubscribe-mail-template), mit denen ein Projekt eine Seite oder Mail gegen eine eigene tauscht.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words, among them name, title and description of the two pages, into the Text panel, never replacing a word of the project.',
				'de_DE' => 'Bringt die Worte, auch Name, Titel und Beschreibung der beiden Seiten, ins Panel Texte, ohne ein Wort des Projekts zu ersetzen.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; it names seven days, the default of /nino/newsletter/pending-days.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; er nennt sieben Tage, den Standard von /nino/newsletter/pending-days.',
			],
			'templates/mail-header.tpl, templates/mail-footer.tpl' => [
				'en_US' => 'The mail frame, byte for byte the Form module\'s; whichever unit is applied first provides it.',
				'de_DE' => 'Der Mailrahmen, Byte für Byte der des Form-Moduls; welche Einheit zuerst angewendet wird, liefert ihn.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.2.1',
	// 1.11, for the hero style of a section's title and subtitle
	// (nino-section-title--hero, nino-section-subtitle--hero), which the page
	// template of the install unit draws, on top of 1.9 - the toolset the code
	// and the panel are written on, and the [[url]] of [image] that the header
	// of its mail writes
	'nino'				=> '^1.11',
	'requires'		=> [],
	'settings'		=> [],
	// The files under data/ this feature owns - what a backup carries and
	// callbackRestore() merges
	'data'				=> [ '/data/newsletter.php', '/data/newsletter-removed.php' ],
	/*	The signup form as the Builder offers it: no first argument, the form says all
		it has to say itself, and the one choice is whether it stands in one line. The
		class has no doShortcode() - there was no shortcode before - so the component is
		the shortcode, and componentNewsletterSignup() renders it for the Builder and for
		a page that writes [newsletter-signup]. A template of the feature, not the
		project's: the form is the one the endpoint takes, and an editor does not change
		it but for these words in the Text panel	*/
	'components'	=> [
		'newsletter-signup' => [
			'label'			=> [ 'en_US' => 'Newsletter signup', 'de_DE' => 'Newsletter-Anmeldung' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'inline' => [
					'type'		=> 'bool',
					'default'	=> false,
					'label'		=> [ 'en_US' => 'In one line', 'de_DE' => 'In einer Zeile' ],
					'hint'		=> [
						'en_US' => 'The field and the button side by side. The label is then read by screen readers only.',
						'de_DE' => 'Feld und Knopf nebeneinander. Die Beschriftung lesen dann nur noch Screenreader.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'form .nino-form',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'm22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7' ] ],
				[ 'rect', [ 'x' => '2', 'y' => '4', 'width' => '20', 'height' => '16', 'rx' => '2' ] ],
			],
		],
	],
];
