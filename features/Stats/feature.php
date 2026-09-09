<?php
// features/Stats/feature.php - what the Features panel reads. The class is
// not declared here: features/Stats/ can only ever serve \Nino\Modules\Stats.
return [
	'key'					=> 'stats',
	'name'				=> [ 'en_US' => 'Stats', 'de_DE' => 'Statistik' ],
	'description'	=> [
		'en_US' => 'Page-view counts for the workbench - no cookies, no ip addresses, no fingerprints, nothing stored per visitor.',
		'de_DE' => 'Seitenaufruf-Zählung für die Workbench - keine Cookies, keine IP-Adressen, keine Fingerprints, nichts wird pro Besucher gespeichert.',
	],
	'manual'			=> [
		'en_US' => <<<'TXT'
			Nothing to place and nothing to set: counting begins when the feature is
			switched on, and the numbers are in the Stats panel and on the
			dashboard.

			No cookie is set, no ip address is read and nothing is stored per
			visitor - so no consent banner has to mention it, and there is nothing
			anybody could ask to have deleted.
			TXT,
		'de_DE' => <<<'TXT'
			Zu setzen gibt es nichts und einzustellen auch nichts: Gezählt wird,
			sobald das Feature an ist, und die Zahlen stehen im Panel Statistik und
			auf dem Dashboard.

			Es wird kein Cookie gesetzt, keine IP-Adresse gelesen und nichts pro
			Besucher gespeichert – ein Einwilligungsbanner muss das also nicht
			erwähnen, und es gibt nichts, dessen Löschung jemand verlangen könnte.
			TXT,
	],
	'category'		=> 'marketing',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// The whole directory this feature owns under data/ - what a backup
	// carries. There is nothing here a restore has to merge rather than
	// overwrite (see README.md, "Data and retention"): every value is a
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
