<?php
// The class is not declared: features/Newsletter/ can only serve \Nino\Modules\Newsletter
return [
	'key'					=> 'newsletter',
	'name'				=> 'Newsletter',
	'description'	=> [
		'en_US' => 'Newsletter lets visitors sign up with double opt-in, with a confirmation and an unsubscribe link, with or without JavaScript. A component draws the form and a block puts it on a page. The subscriber list is a panel of the workbench; sending the letter is a job for a mail client.',
		'de_DE' => 'Der Newsletter ermöglicht die Anmeldung per Double-Opt-in mit Bestätigungs- und Abmeldelink, mit und ohne JavaScript. Eine Komponente zeichnet das Formular, ein Block setzt es auf eine Seite. Die Abonnentenliste gibt es als Panel der Workbench; das Versenden selbst ist Sache eines Mailprogramms.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[newsletter-signup]' => [
				'en_US' => 'Draws the signup form for POST /.newsletter; it is the Builder\'s component Newsletter signup (in the flow of a column and in a footer column), and the words are in the Text panel.',
				'de_DE' => 'Zeichnet das Anmeldeformular für POST /.newsletter; es ist die Komponente Newsletter-Anmeldung des Builders (im Fluss einer Spalte und in einer Fußspalte), und die Worte stehen im Panel Texte.',
			],
			'[newsletter-signup inline]' => [
				'en_US' => 'The field and the button on one line; the label is then read by screen readers only.',
				'de_DE' => 'Feld und Knopf in einer Zeile; die Beschriftung lesen dann nur noch Screenreader.',
			],
			'[newsletter-result]' => [
				'en_US' => 'Says what a link of a mail led to - the button of a confirmation or an unsubscribe, the outcome, or that the link no longer counts - and is all that page-newsletter.tpl draws.',
				'de_DE' => 'Sagt, wohin der Link einer Mail geführt hat - der Knopf für Bestätigung oder Abmeldung, das Ergebnis oder dass der Link nicht mehr gilt - und ist alles, was page-newsletter.tpl zeichnet.',
			],
			'[newsletter-unsubscribe]' => [
				'en_US' => 'The form that asks for an address and mails the unsubscribe link to it; it is the one thing page-newsletter-unsubscribe.tpl draws besides its title.',
				'de_DE' => 'Das Formular, das nach einer Adresse fragt und ihr den Abmeldelink schickt; es ist neben der Überschrift das Einzige, was page-newsletter-unsubscribe.tpl zeichnet.',
			],
		],
		'markup' => [
			'<form class="nino-form" data-form-texts="/feature/newsletter/info/" action="[[/nino/dir]]/.newsletter">' => [
				'en_US' => 'To write the form yourself, take [csrf], the empty honeypot location, an input email, a hidden field id, a submit button and a first paragraph for the message; the Form script posts it.',
				'de_DE' => 'Wer das Formular selbst schreibt, nimmt [csrf], den leeren Honigtopf location, einen Input email, ein verstecktes Feld id, einen Absende-Button und einen ersten Absatz als Meldung.',
			],
			'[data-form-outcome]' => [
				'en_US' => 'Without JavaScript the message of a sent signup is written into the paragraph of every signup form on the page and shown by the one the address names (the fragment).',
				'de_DE' => 'Ohne JavaScript steht die Meldung einer gesendeten Anmeldung im Absatz jedes Anmeldeformulars der Seite und wird von dem gezeigt, das die Adresse nennt (das Fragment).',
			],
		],
		'routes' => [
			'POST /.newsletter' => [
				'en_US' => 'Takes the signup and answers 200 once the confirmation mail went out, else 400, 418, 429 or 500; a browser without JavaScript is sent back to the page with ?form=success, email or error.',
				'de_DE' => 'Nimmt die Anmeldung entgegen und antwortet 200, sobald die Bestätigungsmail raus ist, sonst 400, 418, 429 oder 500; ohne JavaScript geht der Browser mit ?form=success, email oder error zurück.',
			],
			'/.newsletter?confirm=<token> and ?unsubscribe=<token>' => [
				'en_US' => 'The first step of a link shows a page with a button and changes nothing, so a mail scanner neither confirms nor unsubscribes anyone; a token that is unknown or has run out is a 404.',
				'de_DE' => 'Der erste Schritt eines Links zeigt eine Seite mit einem Knopf und ändert nichts, damit ein Mailscanner niemanden bestätigt oder abmeldet; ein unbekannter oder abgelaufener Token ist ein 404.',
			],
			'POST /.newsletter/action' => [
				'en_US' => 'The second step, which the button posts, confirms an address or removes an entry (confirm or unsubscribe, with [csrf]) and sends the browser on to /.newsletter?done=<outcome>.',
				'de_DE' => 'Der zweite Schritt, den der Knopf abschickt, bestätigt eine Adresse oder entfernt einen Eintrag (confirm oder unsubscribe, mit [csrf]) und schickt den Browser auf /.newsletter?done=<Ergebnis>.',
			],
			'/.newsletter/unsubscribe' => [
				'en_US' => 'The way out without a link: an address in, a mail with the unsubscribe link out, the same answer for every address.',
				'de_DE' => 'Der Weg hinaus ohne Link: Adresse hinein, Mail mit dem Abmeldelink hinaus, für jede Adresse dieselbe Antwort.',
			],
		],
		'panel' => [
			'Newsletter' => [
				'en_US' => 'Lists the addresses with their status in a table with a search, copies the BCC line of the confirmed ones, exports the list as CSV and deletes an address.',
				'de_DE' => 'Listet die Adressen mit ihrem Status in einer Tabelle mit Suche, kopiert die BCC-Zeile der bestätigten, exportiert die Liste als CSV und löscht eine Adresse.',
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
				'en_US' => 'The page for /.newsletter and the answer of /.newsletter/action, made of components; with page-newsletter-unsubscribe.tpl, mail-newsletter-confirm.tpl and mail-newsletter-unsubscribe.tpl.',
				'de_DE' => 'Die Seite für /.newsletter und die Antwort von /.newsletter/action, aus Komponenten; dazu page-newsletter-unsubscribe.tpl, mail-newsletter-confirm.tpl und mail-newsletter-unsubscribe.tpl.',
			],
			'/nino/newsletter/<name>-template' => [
				'en_US' => 'Keys of config.php (page-template, unsubscribe-template, confirm-template, unsubscribe-mail-template) with which a project swaps a page or a mail for its own template.',
				'de_DE' => 'Schlüssel der config.php (page-template, unsubscribe-template, confirm-template, unsubscribe-mail-template), mit denen ein Projekt eine Seite oder Mail gegen ein eigenes Template tauscht.',
			],
			'/data/newsletter.php, /data/newsletter-removed.php' => [
				'en_US' => 'The two files of the feature: the list with status and token, and the sha256 of every address ever removed; a backup carries both and a restore merges them.',
				'de_DE' => 'Die zwei Dateien des Features: die Liste mit Status und Token und der sha256 jeder je entfernten Adresse; eine Sicherung trägt beide, eine Wiederherstellung führt sie zusammen.',
			],
			'templates/page-newsletter*.tpl' => [
				'en_US' => 'A project that already has its own page-newsletter.tpl or page-newsletter-unsubscribe.tpl keeps it, and copies the two pages of this unit by hand, since they are drawn by components.',
				'de_DE' => 'Ein Projekt mit eigener page-newsletter.tpl oder page-newsletter-unsubscribe.tpl behält sie und übernimmt die zwei Seiten dieser Einheit von Hand, denn sie werden von Komponenten gezeichnet.',
			],
			'/nino/newsletter/pending-days' => [
				'en_US' => 'Key of config.php: how many days an unconfirmed signup lives, seven by default; its confirmation link stops working with it.',
				'de_DE' => 'Schlüssel der config.php: wie viele Tage eine unbestätigte Anmeldung lebt, standardmäßig sieben; ihr Bestätigungslink gilt dann nicht mehr.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words, among them name, title and description of the two pages, into the Text panel, never replacing a word of the project.',
				'de_DE' => 'Bringt die Worte, auch Name, Titel und Beschreibung der beiden Seiten, ins Panel Texte, ohne ein Wort des Projekts zu ersetzen.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; it names seven days, the default of /nino/newsletter/pending-days, and is a starting point, not legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; er nennt sieben Tage, den Standard von /nino/newsletter/pending-days, und ist ein Ausgangspunkt, keine Rechtsberatung.',
			],
			'templates/mail-header.tpl, templates/mail-footer.tpl' => [
				'en_US' => 'The mail frame, byte for byte the Form module\'s; whichever unit is applied first provides it.',
				'de_DE' => 'Der Mailrahmen, Byte für Byte der des Form-Moduls; welche Einheit zuerst angewendet wird, liefert ihn.',
			],
			'blocks/newsletter-signup/' => [
				'en_US' => 'A block: a headline, a sentence and the signup form in a narrow column.',
				'de_DE' => 'Ein Block: eine Überschrift, ein Satz und das Anmeldeformular in einer schmalen Spalte.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'basis' ],
	'settings'		=> [],
	// The files under data/ this feature owns - what a backup carries and
	// callbackRestore() merges
	'data'				=> [ '/data/newsletter.php', '/data/newsletter-removed.php' ],
];
