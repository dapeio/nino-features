<?php
// features/Forms/feature.php - what the Features panel reads. The class is
// not declared here: features/Forms/ can only ever serve \Nino\Modules\Forms.
return [
	'key'					=> 'forms',
	'name'				=> [ 'en_US' => 'Forms', 'de_DE' => 'Formulare' ],
	'description'	=> [
		'en_US' => 'Forms lets you build any number of forms with fields of their own, without touching code. A shortcode draws them, and spam protection without a captcha comes with it. The Design feature sets how the fields look.',
		'de_DE' => 'Formulare ermöglicht beliebig viele Formulare auf der Website, mit eigenen Feldern, ganz ohne Eingriff am Code. Ein Shortcode zeichnet sie, und ein Spam-Schutz ohne Captcha ist dabei. Das Aussehen der Felder stellt das Design ein.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[form key="quote"]' => [
				'en_US' => 'Draws that form; without a key, or with a key no form has, it draws the first form.',
				'de_DE' => 'Zeichnet dieses Formular; ohne key oder mit einem Schlüssel, den kein Formular hat, zeichnet es das erste.',
			],
			'[form ... class="my-class"]' => [
				'en_US' => 'Adds a class of your own; in the Builder you choose the form under Properties from the list of the existing ones.',
				'de_DE' => 'Hängt eine eigene Klasse an; im Builder wählst Du das Formular unter Eigenschaften aus der Liste der vorhandenen.',
			],
		],
		'markup' => [
			'.nino-forms-check, .nino-forms-group' => [
				'en_US' => 'Fields, labels, status line, checkbox and radio group are drawn by Nino.css from the --forms-* properties, which the Design feature sets.',
				'de_DE' => 'Felder, Beschriftungen, Statuszeile, Checkbox und Radiogruppe zeichnet Nino.css aus den Properties --forms-*, die das Design einstellt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Forms' => [
				'en_US' => 'Builds a form with fields (text, email, tel, url, number, textarea, select, radio, checkbox, date), target and words; what visitors send is in the Submissions panel.',
				'de_DE' => 'Baut ein Formular mit Feldern (text, email, tel, url, number, textarea, select, radio, checkbox, date), Ziel und Worten; was Besucher senden, steht im Panel Anfragen.',
			],
			'Submissions: keep and record' => [
				'en_US' => 'Sets how long the kernel keeps submissions (/nino/form/retention, 1 to 60 months) and whether it records them (/nino/form/store).',
				'de_DE' => 'Stellt ein, wie lange der Kernel Einsendungen aufhebt (/nino/form/retention, 1 bis 60 Monate) und ob er sie speichert (/nino/form/store).',
			],
		],
		'callbacks' => [
			'/nino/http/response/POST://.form' => [
				'en_US' => 'Guards the kernel\'s form route with three checks: too fast and blocked words answer 418 like a filled honeypot, a rate limit answers 429.',
				'de_DE' => 'Schützt die Formular-Route des Kernels mit drei Wächtern: Zu schnell und gesperrte Wörter antworten 418 wie ein gefüllter Honigtopf, ein Limit antwortet 429.',
			],
		],
		'install' => [
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; it names three months and assumes /nino/form/store is on.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; er nennt drei Monate und setzt voraus, dass /nino/form/store an ist.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.2.0',
	// 1.9, for the toolset the code and the panel are written on, and for the
	// options of a field, which a method of the feature answers
	'nino'				=> '^1.9',
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
				'en_US' => 'How many submissions one ip address may have accepted before the next is turned away. Only accepted ones count - a visitor who mistypes their address is not spending their allowance. 0 switches the limit off; the kernel\'s own mail cap still applies. Behind a reverse proxy without /nino/http/proxies every visitor has the proxy\'s address, so the first to use up the limit turns the form off for everybody.',
				'de_DE' => 'Wie viele Einsendungen von einer IP-Adresse angenommen werden, bevor die nächste abgewiesen wird. Gezählt wird nur, was angenommen wurde - wer sich bei der Adresse vertippt, verbraucht sein Kontingent also nicht. 0 schaltet die Grenze ab; die Mail-Grenze des Kernels gilt weiterhin. Hinter einem Reverse-Proxy ohne /nino/http/proxies haben alle Besucher die Adresse des Proxys, der Erste, der das Limit aufbraucht, schaltet das Formular also für alle ab.',
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
	/*	The shortcode as the Builder offers it: no first argument, the form is named by
		its key. The class has no componentForm(): doShortcode() renders it	*/
	'components'	=> [
		'form' => [
			'label'			=> [ 'en_US' => 'Form', 'de_DE' => 'Formular' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				/*	A choice among the forms there are: the list is a method of the class
					(Forms::options()), which the Components module asks, and a tool offers
					the forms by their names. The empty word is the first form	*/
				'key' => [
					'type'		=> 'select',
					'options'	=> '\Nino\Modules\Forms::options',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Form', 'de_DE' => 'Formular' ],
					'hint'		=> [
						'en_US' => 'The form to draw. The first form is the one at the top of the Forms panel.',
						'de_DE' => 'Das Formular, das gezeichnet wird. Das erste Formular steht oben im Panel Formulare.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'form .nino-form',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M4 14h6' ] ],
				[ 'path', [ 'd' => 'M4 2h10' ] ],
				[ 'rect', [ 'x' => '4', 'y' => '18', 'width' => '16', 'height' => '4', 'rx' => '1' ] ],
				[ 'rect', [ 'x' => '4', 'y' => '6', 'width' => '16', 'height' => '4', 'rx' => '1' ] ],
			],
		],
	],
];
