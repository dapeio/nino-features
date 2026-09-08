<?php
// Die eigenen Workbench-Texte des Mailer-Features, in seine Fills gemischt,
// solange das Feature aktiv ist (siehe text() des Panels) - dieselben
// Schlüssel und dieselbe Form wie text/<locale>.php der Workbench. Die
// beiden "mail/*"-Schlüssel sind Betreff und Text der Testmail - der
// einzigen Mail, die dieses Feature selbst verfasst.
return [
	'[[/_admin/nav/mailer]]'							=> 'Mailer',
	'[[/_admin/mailer/label/title]]'			=> 'SMTP-Mailversand',
	'[[/_admin/mailer/hint/intro]]'				=> 'Sendet eine Testmail über die im Panel Features für dieses Feature hinterlegten Einstellungen, über denselben Versandweg und dasselbe Limit je IP (5 pro Stunde) wie jede andere Mail der Website.',
	'[[/_admin/mailer/label/status]]'		=> 'Versand über %host:%port (%encryption).',
	'[[/_admin/mailer/label/unconfigured]]'	=> 'Noch nicht konfiguriert - Mail geht weiterhin über das mail() des Servers hinaus. Zuerst einen Host im Panel Features hinterlegen.',
	'[[/_admin/mailer/label/to]]'					=> 'Testmail senden an',
	'[[/_admin/mailer/label/send]]'				=> 'Testmail senden',
	'[[/_admin/mailer/msg/loading]]'			=> 'Wird geladen …',
	'[[/_admin/mailer/msg/sending]]'			=> 'Wird gesendet …',
	'[[/_admin/mailer/msg/sent]]'					=> 'Testmail gesendet.',
	'[[/_admin/mailer/error/load]]'				=> 'Der Status konnte nicht geladen werden.',
	'[[/_admin/mailer/error/send]]'				=> 'Die Testmail konnte nicht gesendet werden.',
	'[[/_admin/mailer/mail/subject]]'		=> 'Nino-Testmail',
	'[[/_admin/mailer/mail/body]]'				=> '<p>Dies ist eine Testmail Ihrer Nino-Installation, versendet über die SMTP-Einstellungen des Mailer-Features.</p><p>Ist sie angekommen, funktionieren Host, Port, Verschlüsselung und Zugangsdaten wie konfiguriert.</p>',
];
