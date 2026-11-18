<?php
// The Newsletter feature's own workbench strings, merged into its fills
// while the feature is active (see the panel's text()) - same keys and
// shape the editor's own text/<locale>.php has
return [
	'[[/_admin/nav/newsletter]]'							=> 'Newsletter',
	'[[/_admin/dashboard/label/newsletter]]'	=> 'Newsletter-Abonnenten',

	'[[/_admin/newsletter/error/load]]'				=> 'Die Abonnenten konnten nicht geladen werden.',
	'[[/_admin/newsletter/error/delete]]'			=> 'Die Adresse konnte nicht gelöscht werden.',
	'[[/_admin/error/newsletter_copy]]'				=> 'Die BCC-Zeile konnte nicht kopiert werden. Markiere die Adressen und kopiere sie mit der Tastatur.',
	'[[/_admin/newsletter/empty]]'						=> 'Noch keine Anmeldungen.',
	'[[/_admin/newsletter/emptyfilter]]'			=> 'Keine Anmeldung hat diesen Status.',
	'[[/_admin/newsletter/nomatch]]'					=> 'Keine Anmeldung passt zu dieser Suche.',

	'[[/_admin/newsletter/label/subscribers]]'	=> 'Abonnenten',
	'[[/_admin/newsletter/label/search]]'			=> 'Abonnenten suchen',
	'[[/_admin/newsletter/label/mail]]'				=> 'E-Mail',
	'[[/_admin/newsletter/label/status]]'			=> 'Status',
	'[[/_admin/newsletter/label/date]]'				=> 'Datum',
	'[[/_admin/newsletter/label/copy-bcc]]'		=> 'BCC-Zeile kopieren',
	'[[/_admin/newsletter/label/export]]'			=> 'Als CSV exportieren',
	'[[/_admin/newsletter/label/filename]]'		=> 'newsletter.csv',
	'[[/_admin/newsletter/status/subscribed]]'	=> 'Bestätigt',
	'[[/_admin/newsletter/status/pending]]'		=> 'Ausstehend',

	'[[/_admin/newsletter/confirm/delete-title]]'	=> 'Abonnent löschen',
	'[[/_admin/newsletter/confirm/delete]]'		=> '%s aus dem Newsletter löschen? Die Adresse bleibt auch nach dem Einspielen einer Sicherung draußen.',
	'[[/_admin/newsletter/msg/bcc-one]]'			=> 'BCC-Zeile mit 1 Adresse kopiert.',
	'[[/_admin/newsletter/msg/bcc-many]]'			=> 'BCC-Zeile mit %s Adressen kopiert.',
	'[[/_admin/newsletter/hint/unsubscribe]]'	=> 'Abonnenten ohne persönlichen Link fordern ihn unter dieser Adresse an. Setze sie in jede BCC-Mail, die Du versendest: %s',

	// The sentence of the code newsletter_unknown (Admin::apiDelete())
	'[[/_admin/error/newsletter_unknown]]'		=> 'Diese Adresse steht nicht auf der Liste.',
];
