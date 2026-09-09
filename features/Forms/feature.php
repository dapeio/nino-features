<?php
// features/Forms/feature.php - what the Features panel reads. The class is
// not declared here: features/Forms/ can only ever serve \Nino\Modules\Forms.
return [
	'key'					=> 'forms',
	'name'				=> [ 'en_US' => 'Forms', 'de_DE' => 'Formulare' ],
	'description'	=> [
		'en_US' => 'A builder for Nino\'s own form endpoint: any number of forms with fields of their own, a [form] shortcode that draws them, and spam protection without a captcha.',
		'de_DE' => 'Ein Baukasten für Ninos eigenen Formular-Endpunkt: beliebig viele Formulare mit eigenen Feldern, ein Shortcode [form], der sie zeichnet, und Spam-Schutz ohne Captcha.',
	],
	'category'		=> 'communication',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// Nothing: the definitions live in config.php under '/nino/form/forms',
	// where the kernel reads them and every backup already carries them, and
	// the submissions are the kernel's own /data/forms.<Y-m>.php. The one
	// file this feature writes is a spam counter that rebuilds itself
	'data'				=> [],
	'settings'		=> [
		'rateLimit' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Submissions per hour and address', 'de_DE' => 'Einsendungen pro Stunde und Adresse' ],
			'hint'	=> [
				'en_US' => 'How many submissions one ip address may have accepted before the next is turned away. Only accepted ones count - a visitor who mistypes their address is not spending their allowance. 0 switches the limit off; the kernel\'s own mail cap still applies.',
				'de_DE' => 'Wie viele Einsendungen von einer IP-Adresse angenommen werden, bevor die nächste abgewiesen wird. Gezählt wird nur, was angenommen wurde - wer sich bei der Adresse vertippt, verbraucht sein Kontingent also nicht. 0 schaltet die Grenze ab; die Mail-Grenze des Kernels gilt weiterhin.',
			],
			'min'			=> 0,
			'max'			=> 1000,
			'default'	=> 10,
		],
		'minSeconds' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Fastest accepted submission', 'de_DE' => 'Schnellste angenommene Einsendung' ],
			'hint'	=> [
				'en_US' => 'A form drawn by [form] carries the moment it was drawn. A submission that comes back faster than this is a script, not a reader. Only forms that carry it are checked - a hand-written one is not; 0 switches the check off.',
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
	],
];
