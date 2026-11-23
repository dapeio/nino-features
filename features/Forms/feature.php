<?php
// features/Forms/feature.php - what the Features panel reads. The class is
// not declared here: features/Forms/ can only ever serve \Nino\Modules\Forms.
return [
	'key'					=> 'forms',
	'name'				=> [ 'en_US' => 'Forms', 'de_DE' => 'Formulare' ],
	'description'	=> [
		'en_US' => 'Forms lets you build any number of forms with fields of their own, without touching code. A component draws each form, with or without JavaScript, and spam protection without a captcha comes with it. Two blocks put a form on a page.',
		'de_DE' => 'Formulare ermöglicht beliebig viele Formulare auf der Website, mit eigenen Feldern, ganz ohne Eingriff am Code. Eine Komponente zeichnet jedes Formular, mit und ohne JavaScript, und ein Spam-Schutz ohne Captcha ist dabei. Zwei Blöcke setzen ein Formular auf eine Seite.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[form key="quote"]' => [
				'en_US' => 'Draws that form; without a key, or with a key no form has, it draws the first form.',
				'de_DE' => 'Zeichnet dieses Formular; ohne key oder mit einem Schlüssel, den kein Formular hat, zeichnet es das erste.',
			],
			'[form variant="line"]' => [
				'en_US' => 'Draws the first form in the look "line" (fields are a rule under the words); "filled" gives tinted fields.',
				'de_DE' => 'Zeichnet das erste Formular im Aussehen „line“ (die Felder sind eine Linie unter den Wörtern); „filled“ gibt getönte Felder.',
			],
		],
		'markup' => [
			'.nino-form' => [
				'en_US' => 'The one class of the kernel the form carries; it hands the form to the script of the Form module, which writes into its first <p> and holds back its first <button>.',
				'de_DE' => 'Die eine Klasse des Kernels, die das Formular trägt; sie übergibt es dem Skript des Moduls Form, das in den ersten <p> schreibt und den ersten <button> sperrt.',
			],
			'.form-field, .form-label, .form-control, .form-check' => [
				'en_US' => 'The classes of the fields; the standard part of the group Form draws them from the tokens of the brand and of the page.',
				'de_DE' => 'Die Klassen der Felder; der Standard-Teil der Gruppe Formular zeichnet sie aus den Tokens der Marke und der Seite.',
			],
			'[data-form-outcome]' => [
				'en_US' => 'Without JavaScript the message of a sent form is written into the paragraph of every form on the page and shown by the one the address names (the fragment).',
				'de_DE' => 'Ohne JavaScript steht die Meldung eines gesendeten Formulars im Absatz jedes Formulars der Seite und wird von dem gezeigt, das die Adresse nennt (das Fragment).',
			],
		],
		'routes' => [],
		'panel' => [
			'Forms' => [
				'en_US' => 'The directory of the forms; each form has its fields, its delivery and its protection on tabs of its own, and what visitors send is in the Submissions panel.',
				'de_DE' => 'Das Verzeichnis der Formulare; jedes Formular hat Felder, Versand und Schutz auf eigenen Reitern, und was Besucher senden, steht im Panel Anfragen.',
			],
			'Forms: submissions' => [
				'en_US' => 'Sets how long the kernel keeps submissions (/nino/form/retention, 1 to 60 months) and whether it records them (/nino/form/store).',
				'de_DE' => 'Stellt ein, wie lange der Kernel Einsendungen aufhebt (/nino/form/retention, 1 bis 60 Monate) und ob er sie speichert (/nino/form/store).',
			],
		],
		'callbacks' => [
			'/nino/http/response/POST://.form' => [
				'en_US' => 'Guards the kernel\'s form route: too fast and blocked words answer 418 like a filled honeypot and a rate limit 429; a browser without JavaScript is sent back by the kernel with ?form=error.',
				'de_DE' => 'Schützt die Formular-Route des Kernels: Zu schnell und gesperrte Wörter antworten 418, ein Limit 429; einen Browser ohne JavaScript schickt der Kernel mit ?form=error zurück.',
			],
		],
		'install' => [
			'blocks/contact-form/' => [
				'en_US' => 'A block: headline, text and the first form side by side; the form of the starter contact page.',
				'de_DE' => 'Ein Block: Überschrift, Text und das erste Formular nebeneinander; das Formular der Kontaktseite der Startseiten.',
			],
			'blocks/form-plain/' => [
				'en_US' => 'A block with the first form alone, in a narrow column.',
				'de_DE' => 'Ein Block mit dem ersten Formular allein, in einer schmalen Spalte.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; it names three months and assumes /nino/form/store is on.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; er nennt drei Monate und setzt voraus, dass /nino/form/store an ist.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'basis' ],
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
				'en_US' => 'A form drawn by the component carries the moment it was drawn. A submission that comes back faster than this is a script, not a reader. Only forms that carry it are checked - a hand-written one is not; 0 switches the check off.',
				'de_DE' => 'Ein von der Komponente gezeichnetes Formular trägt den Zeitpunkt seiner Ausgabe. Was schneller zurückkommt, ist ein Skript und kein Mensch. Geprüft wird nur, was ihn mitbringt - ein von Hand geschriebenes Formular also nicht; 0 schaltet die Prüfung ab.',
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
	// No script and no stylesheet of its own: the script of the form is the kernel's (module Form), the look is the standard part of the group form
	'assets'			=> [],
];
