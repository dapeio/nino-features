<?php
// features/Sysadmin/feature.php - what the Features panel reads. The class is
// not declared here: features/Sysadmin/ can only ever serve \Nino\Modules\Sysadmin.
return [
	'key'					=> 'sysadmin',
	'name'				=> [ 'en_US' => 'Sysadmin', 'de_DE' => 'Sysadmin' ],
	'description'	=> [
		'en_US' => 'Sysadmin tells the person who looks after the site when something is wrong, by mail: a problem mail as soon as the Dashboard shows a notice that was not there before, the backup did not run, a feature was refused or the disk runs full, and a sign of life at a fixed rhythm that says all is well. A page request sets it off, so it needs no cron job.',
		'de_DE' => 'Sysadmin sagt dem, der die Website betreut, per Mail Bescheid, wenn etwas nicht stimmt: eine Problem-Mail, sobald das Dashboard einen Hinweis zeigt, der vorher nicht da war, das Backup ausgeblieben ist, ein Feature abgelehnt wurde oder die Platte voll läuft, und ein Lebenszeichen in festem Rhythmus, das sagt, dass alles in Ordnung ist. Ein Seitenaufruf löst es aus, einen Cronjob braucht es nicht.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Sysadmin' => [
				'en_US' => 'Lists who is told, the two clocks and what the report finds right now, and sends a test mail to the saved recipients; the settings are on the Settings tab.',
				'de_DE' => 'Listet, wer Bescheid bekommt, die beiden Uhren und was der Bericht jetzt findet, und schickt den Empfängern eine Testmail; die Einstellungen stehen im Reiter Einstellungen.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The words of the mail, under /feature/sysadmin, in the native language of the site; the project may change them.',
				'de_DE' => 'Die Worte der Mail unter /feature/sysadmin, in der Hauptsprache der Website; das Projekt darf sie ändern.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy for the Legal module; the operator adds who receives the reports.',
				'de_DE' => 'Der Abschnitt der Datenschutzerklärung für das Modul Legal; den Empfänger der Berichte ergänzt der Betreiber.',
			],
		],
	],
	'category'		=> 'system',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// What it remembers between two requests: when it last looked, what it told, what failed. No address of a
	// visitor and no message of the log - only keys of findings and times
	'data'				=> [ '/data/sysadmin.php' ],
	'settings'		=> [
		'to' => [
			'type'			=> 'string',
			'label'			=> [ 'en_US' => 'Recipients', 'de_DE' => 'Empfänger' ],
			'hint'			=> [
				'en_US' => 'One email address, or up to five separated by commas, in letters, digits and hyphens (an address with other letters is written in punycode). Without an address the feature does nothing.',
				'de_DE' => 'Eine E-Mail-Adresse oder bis zu fünf, durch Kommas getrennt, mit Buchstaben, Ziffern und Bindestrichen (eine Adresse mit anderen Buchstaben schreibt man in Punycode). Ohne Adresse tut das Feature nichts.',
			],
			'maxlength'	=> 400,
			'pattern'		=> '/^(?=[^@\s,]{1,64}@)[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)+(?:\s*,\s*(?=[^@\s,]{1,64}@)[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)+){0,4}$/D',
			'default'		=> '',
		],
		'problemGap' => [
			'type'			=> 'select',
			'label'			=> [ 'en_US' => 'Problem mail at most every', 'de_DE' => 'Problem-Mail höchstens alle' ],
			'hint'			=> [
				'en_US' => 'A problem mail is sent when a finding is new or has become worse, and never sooner than this after the last one.',
				'de_DE' => 'Eine Problem-Mail geht raus, wenn ein Befund neu ist oder sich verschärft hat, und nie früher als so lange nach der letzten.',
			],
			'options'		=> [
				'1'		=> [ 'en_US' => '1 hour', 'de_DE' => '1 Stunde' ],
				'6'		=> [ 'en_US' => '6 hours', 'de_DE' => '6 Stunden' ],
				'24'	=> [ 'en_US' => '24 hours', 'de_DE' => '24 Stunden' ],
				'72'	=> [ 'en_US' => '3 days', 'de_DE' => '3 Tage' ],
				'168'	=> [ 'en_US' => '7 days', 'de_DE' => '7 Tage' ],
			],
			'default'		=> '24',
		],
		'beat' => [
			'type'			=> 'select',
			'label'			=> [ 'en_US' => 'Sign of life', 'de_DE' => 'Lebenszeichen' ],
			'hint'			=> [
				'en_US' => 'A mail at a fixed rhythm that says the site is running, with everything the report finds. It goes out with the first page request after it is due.',
				'de_DE' => 'Eine Mail in festem Rhythmus, die sagt, dass die Website läuft, mit allem, was der Bericht findet. Sie geht mit dem ersten Seitenaufruf nach der Fälligkeit raus.',
			],
			'options'		=> [
				'off'				=> [ 'en_US' => 'Off', 'de_DE' => 'Aus' ],
				'daily'			=> [ 'en_US' => 'Daily', 'de_DE' => 'Täglich' ],
				'weekly'		=> [ 'en_US' => 'Weekly', 'de_DE' => 'Wöchentlich' ],
				'monthly'		=> [ 'en_US' => 'Monthly', 'de_DE' => 'Monatlich' ],
			],
			'default'		=> 'weekly',
		],
	],
];
