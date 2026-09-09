<?php
// features/Forms/feature.php - what the Features panel reads. The class is
// not declared here: features/Forms/ can only ever serve \Nino\Modules\Forms.
return [
	'key'					=> 'forms',
	'name'				=> [ 'en_US' => 'Forms', 'de_DE' => 'Formulare' ],
	'description'	=> [
		'en_US' => 'Any number of forms with fields of their own, their submissions in a workbench panel with export, and the mail they send going out through Nino\'s own transport.',
		'de_DE' => 'Beliebig viele Formulare mit eigenen Feldern, ihre Einsendungen als Panel der Workbench mit Export, und die Mail dazu über Ninos eigenen Versand.',
	],
	'category'		=> 'communication',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// One directory: the definitions and one file per form and month. A
	// restore merges it rather than overwriting, see callbackRestore()
	'data'				=> [ '/data/forms' ],
	'settings'		=> [
		'retention' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Keep submissions for', 'de_DE' => 'Einsendungen aufbewahren' ],
			'hint'	=> [
				'en_US' => 'How many months of submissions stay on disk. A month older than this is deleted the next time one comes in - the mail itself is not affected.',
				'de_DE' => 'Wie viele Monate an Einsendungen auf der Platte bleiben. Ein älterer Monat wird bei der nächsten Einsendung gelöscht - die Mail selbst bleibt davon unberührt.',
			],
			'min'			=> 1,
			'max'			=> 60,
			'unit'		=> 'months',
			'default'	=> 3,
		],
		'rateLimit' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Submissions per hour and address', 'de_DE' => 'Einsendungen pro Stunde und Adresse' ],
			'hint'	=> [
				'en_US' => 'How often one ip address may submit before it is turned away. 0 switches the limit off - the mail rate limit of the kernel still applies.',
				'de_DE' => 'Wie oft eine IP-Adresse absenden darf, bevor sie abgewiesen wird. 0 schaltet die Grenze ab - die Mail-Grenze des Kernels gilt weiterhin.',
			],
			'min'			=> 0,
			'max'			=> 1000,
			'default'	=> 10,
		],
		'minSeconds' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Fastest accepted submission', 'de_DE' => 'Schnellste angenommene Einsendung' ],
			'hint'	=> [
				'en_US' => 'A form rendered by [form] carries the moment it was drawn. A submission that comes back faster than this is a script, not a reader. Only forms that carry it are checked - a hand-written form is not; 0 switches the check off.',
				'de_DE' => 'Ein von [form] gezeichnetes Formular trägt den Zeitpunkt seiner Ausgabe. Was schneller zurückkommt, ist ein Skript und kein Mensch. Geprüft wird nur, was ihn mitbringt - ein von Hand geschriebenes Formular also nicht; 0 schaltet die Prüfung ab.',
			],
			'min'			=> 0,
			'max'			=> 120,
			'unit'		=> 'seconds',
			'default'	=> 3,
		],
		'blocklist' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Blocked words', 'de_DE' => 'Gesperrte Wörter' ],
			'hint'	=> [
				'en_US' => 'One per line, case is ignored. A submission carrying one of them in any field is turned away like a filled honeypot - it is neither mailed nor recorded.',
				'de_DE' => 'Eins pro Zeile, Groß- und Kleinschreibung egal. Eine Einsendung, die eines davon in irgendeinem Feld trägt, wird wie ein gefüllter Honeypot abgewiesen - sie wird weder verschickt noch gespeichert.',
			],
		],
		'store' => [
			'type'	=> 'bool',
			'label'	=> [ 'en_US' => 'Record submissions', 'de_DE' => 'Einsendungen speichern' ],
			'hint'	=> [
				'en_US' => 'Off means the mail goes out and nothing is written to disk - the panel then stays empty by design.',
				'de_DE' => 'Aus heißt: Die Mail geht raus und nichts landet auf der Platte - das Panel bleibt dann absichtlich leer.',
			],
			'default'	=> true,
		],
	],
];
