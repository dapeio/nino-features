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
		'en_US' => <<<'TXT'
			The feature ships no form. Place the Newsletter form section from the
			Templates panel on the page that should collect addresses.

			A visitor gets a confirmation mail and is on the list only once the link
			in it is opened; every mail carries an unsubscribe link of its own. The
			addresses, a BCC line to copy and a CSV export are in the Newsletter
			panel - sending the letter itself is a job for a mail client, not for
			this feature.
			TXT,
		'de_DE' => <<<'TXT'
			Das Feature bringt kein Formular mit. Setze den Abschnitt
			Newsletter-Formular aus dem Panel Templates auf die Seite, die Adressen
			sammeln soll.

			Ein Besucher bekommt eine Bestätigungsmail und steht erst auf der Liste,
			wenn der Link darin geöffnet wurde; jede Mail trägt ihren eigenen
			Abmeldelink. Die Adressen, eine BCC-Zeile zum Kopieren und ein
			CSV-Export liegen im Panel Newsletter – das Verschicken selbst ist Sache
			eines Mailprogramms, nicht dieses Features.
			TXT,
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
