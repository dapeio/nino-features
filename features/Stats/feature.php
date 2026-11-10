<?php
// features/Stats/feature.php - what the Features panel reads. The class is
// not declared here: features/Stats/ can only ever serve \Nino\Modules\Stats.
return [
	'key'					=> 'stats',
	'name'				=> [ 'en_US' => 'Stats', 'de_DE' => 'Statistik' ],
	'description'	=> [
		'en_US' => 'Stats lets you count page views for the workbench. There are no cookies, no ip addresses and no fingerprints, and nothing is stored per visitor.',
		'de_DE' => 'Die Statistik ermöglicht eine Zählung der Seitenaufrufe für die Workbench. Es gibt keine Cookies, keine IP-Adressen und keine Fingerprints, und nichts wird pro Besucher gespeichert.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Stats' => [
				'en_US' => 'Shows the page views with a month selector, one bar per day and the top 50 pages and referrer hosts, read-only, behind the permission /_admin/stats/view, with a tile on the Dashboard.',
				'de_DE' => 'Zeigt die Seitenaufrufe mit Monatsauswahl, einem Balken je Tag und den Top 50 der Seiten und Referrer-Hosts, nur lesend, hinter dem Recht /_admin/stats/view, dazu eine Kachel auf dem Dashboard.',
			],
		],
		'callbacks' => [
			'/nino/http/response' => [
				'en_US' => 'Counts the page view with no cookie, no ip address and nothing stored per visitor; counting begins when the feature is switched on.',
				'de_DE' => 'Zählt den Seitenaufruf, ohne Cookie, ohne IP-Adresse und ohne etwas pro Besucher zu speichern; gezählt wird ab dem Einschalten.',
			],
		],
		'install' => [
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'marketing',
	'version'			=> '1.1.2',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// The whole directory this feature owns under data/ - what a backup
	// carries. There is nothing here a restore has to merge rather than
	// overwrite: every value is a
	// count, so the plain whole-directory copy the daily backup already
	// does is enough, and no '/nino/admin/restore' callback is registered
	'data'				=> [ '/data/stats' ],
	'settings'		=> [
		'countSignedIn' => [
			'type'	=> 'bool',
			'label'	=> [ 'en_US' => 'Count signed-in visitors', 'de_DE' => 'Angemeldete Besucher zählen' ],
			'hint'	=> [
				'en_US' => 'Off by default: a page opened by someone signed in to the workbench is not counted, so an editor working on the site does not skew the numbers.',
				'de_DE' => 'Standardmäßig aus: Eine Seite, die von einer in der Workbench angemeldeten Person aufgerufen wird, wird nicht gezählt, damit eine an der Website arbeitende Person die Zahlen nicht verfälscht.',
			],
			'default' => false,
		],
		'exclude' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Never count these', 'de_DE' => 'Nie zählen' ],
			'hint'	=> [
				'en_US' => 'One uri per line, eg. /internal or /internal/* for a whole subtree.',
				'de_DE' => 'Eine Uri pro Zeile, zB /internal oder /internal/* für einen ganzen Teilbaum.',
			],
		],
		'maxUris' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Distinct uris per day', 'de_DE' => 'Unterschiedliche Uris pro Tag' ],
			'hint'	=> [
				'en_US' => 'Beyond this many distinct pages counted on one day, every further one is folded into a single "other" total - keeps a crawler that walks thousands of never-repeated uris from filling a day\'s file.',
				'de_DE' => 'Über diese Anzahl unterschiedlicher, an einem Tag gezählter Seiten hinaus wird jede weitere in eine gemeinsame "Sonstige"-Summe zusammengefasst - verhindert, dass ein Crawler, der tausende nie wiederholte Uris abläuft, die Datei eines Tages füllt.',
			],
			'min'			=> 50,
			'max'			=> 5000,
			'unit'		=> 'uris',
			'default'	=> 500,
		],
		'retentionMonths' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Keep for', 'de_DE' => 'Aufbewahren für' ],
			'hint'	=> [
				'en_US' => 'How many monthly files to keep before the oldest is deleted, checked once whenever the first view of a new day is counted.',
				'de_DE' => 'Wie viele Monatsdateien aufbewahrt werden, bevor die älteste gelöscht wird - geprüft, sobald der erste Aufruf eines neuen Tages gezählt wird.',
			],
			'min'			=> 1,
			'max'			=> 60,
			'unit'		=> 'months',
			'default'	=> 13,
		],
	],
];
