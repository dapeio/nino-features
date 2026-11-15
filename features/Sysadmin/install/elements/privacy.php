<?php
// features/Sysadmin/install/elements/privacy.php - this feature's section of the privacy policy, in German and
// English. Added to the type 'privacy' of Nino's Legal module by \Nino\Features::activate() through the unit's
// 'elements' key, which only ever adds (see \Nino\Elements::seed()): a section an editor changed or deleted is not
// touched again. 'order' is the position among the module's sections, 800-899 is where the features that
// concern the operation of the site stand. The text is in the form the sanitizer gives a field with 'blocks' and
// carries no '&', no entity and no '['.
return [
	'*' => [
		'sysadmin' => [
			'order' => 850,
		],
	],
	'de_DE' => [
		'sysadmin' => [
			'title' => 'Technische Berichte per E-Mail',
			'text' 	=> '<p>Damit wir Störungen dieser Website früh bemerken, schickt sie technische Berichte an die E-Mail-Adresse der Betreuung. Ein Bericht enthält Zahlen und Meldungen zum Betrieb, etwa zu Sicherung, Fehlerprotokoll, Speicherplatz und Versand von E-Mails. Meldungen aus dem Fehlerprotokoll werden gekürzt; E-Mail-Adressen, IP-Adressen, Pfade, Abfragen von Adressen und Zeichenfolgen, die wie Schlüssel aussehen, werden darin vor dem Versand unkenntlich gemacht. Der Bericht wird mit dem Mailserver des E-Mail-Dienstleisters versendet, den diese Website auch sonst benutzt.</p>',
		],
	],
	'en_US' => [
		'sysadmin' => [
			'title' => 'Technical reports by email',
			'text' 	=> '<p>So that we notice faults of this website early, it sends technical reports to the email address of whoever looks after it. A report holds figures and messages about the operation, for example about the backup, the error log, the disk space and the sending of emails. Messages from the error log are shortened; email addresses, IP addresses, paths, queries of addresses and strings that look like keys are made unrecognisable in them before the report is sent. It is sent through the mail server of the email service provider that this website uses for its other emails.</p>',
		],
	],
];
