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
	'manual'			=> [
		'shortcodes' => [
			'[form key="quote"]' => [
				'en_US' => 'Draws that form.',
				'de_DE' => 'Zeichnet dieses Formular.',
			],
			'[form]' => [
				'en_US' => 'Draws the first form there is.',
				'de_DE' => 'Zeichnet das erste Formular, das es gibt.',
			],
			'[form ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the form. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Formulars. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Forms' => [
				'en_US' => 'Build a form: its fields, where it is sent, the words around it. What visitors send is in the Submissions panel.',
				'de_DE' => 'Ein Formular bauen: seine Felder, wohin es geht, die Worte drumherum. Was Besucher senden, steht im Panel Anfragen.',
			],
			'Forms: fields' => [
				'en_US' => 'One row per field: text, email, tel, url, number, textarea, select, radio, checkbox or date, required or not; the name follows the label until it is typed into. Saved to /nino/form/forms in config.php.',
				'de_DE' => 'Eine Zeile je Feld: text, email, tel, url, number, textarea, select, radio, checkbox oder date, Pflicht oder nicht; der Name folgt der Beschriftung, bis er selbst getippt wird. Gespeichert unter /nino/form/forms in der config.php.',
			],
			'Submissions: keep and record' => [
				'en_US' => 'How long the kernel keeps submissions (1 to 60 months) and whether it records them at all - /nino/form/retention and /nino/form/store.',
				'de_DE' => 'Wie lange der Kernel Einsendungen aufhebt (1 bis 60 Monate) und ob er sie überhaupt speichert – /nino/form/retention und /nino/form/store.',
			],
		],
		'callbacks' => [
			'/nino/http/response/POST://.form' => [
				'en_US' => 'Three spam guards on the kernel\'s own form route: a too fast submission and a blocked word answer 418 like a filled honeypot, a rate limit answers 429. Nothing is mailed or recorded then.',
				'de_DE' => 'Drei Spam-Wächter auf der Route des Kernels: Eine zu schnelle Absendung und ein gesperrtes Wort antworten 418 wie ein gefüllter Honigtopf, ein Limit antwortet 429. Es wird dann nichts gesendet oder gespeichert.',
			],
		],
		'install' => [
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy, added to the Legal module\'s type - never replacing a section. Where there is no such module, nothing happens. A starting point, no legal advice. It names three months, the default of /nino/form/retention, and assumes /nino/form/store is on: whoever changes either changes the section in the Elements panel too.',
				'de_DE' => 'Sein Abschnitt der Datenschutzerklärung, dem Typ des Moduls Legal hinzugefügt – ohne einen Abschnitt zu ersetzen. Wo es das Modul nicht gibt, passiert nichts. Ein Ausgangspunkt, keine Rechtsberatung. Er nennt drei Monate, den Standard von /nino/form/retention, und setzt voraus, dass /nino/form/store an ist: Wer eines davon ändert, passt den Abschnitt im Panel Elemente mit an.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '1.0.0',
	'nino'				=> '^1.4',
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
		its key. Nino 1.5 ignores the key	*/
	'components'	=> [
		'form' => [
			'label'			=> [ 'en_US' => 'Form', 'de_DE' => 'Formular' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'key' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Form', 'de_DE' => 'Formular' ],
					'hint'		=> [
						'en_US' => 'The key of the form, as the Forms panel names it. Empty draws the first form there is.',
						'de_DE' => 'Der Schlüssel des Formulars, wie ihn das Panel Formulare nennt. Leer zeichnet das erste Formular, das es gibt.',
					],
				],
			],
			'preview'		=> 'block',
		],
	],
];
