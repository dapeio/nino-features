<?php
// features/Forms/feature.php - what the Features panel reads. The class is
// not declared here: features/Forms/ can only ever serve \Nino\Modules\Forms.
return [
	'key'					=> 'forms',
	'name'				=> [ 'en_US' => 'Forms', 'de_DE' => 'Formulare' ],
	'description'	=> [
		'en_US' => 'A builder for Nino\'s own form endpoint: any number of forms with fields of their own, a [form] shortcode that draws them, and spam protection without a captcha. How the fields look is the kernel\'s, and the Design feature sets it.',
		'de_DE' => 'Ein Baukasten für Ninos eigenen Formular-Endpunkt: beliebig viele Formulare mit eigenen Feldern, ein Shortcode [form], der sie zeichnet, und Spam-Schutz ohne Captcha. Wie die Felder aussehen, bestimmt der Kernel, und das Feature Design stellt es ein.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[form key="quote"]' => [
				'en_US' => 'Draws that form. A key no form has draws the first form.',
				'de_DE' => 'Zeichnet dieses Formular. Ein Schlüssel, den kein Formular hat, zeichnet das erste Formular.',
			],
			'[form]' => [
				'en_US' => 'Draws the first form there is.',
				'de_DE' => 'Zeichnet das erste Formular, das es gibt.',
			],
			'[form] in the Builder' => [
				'en_US' => 'Under Properties, Form is a select of the forms there are, by their names, with the first form at the top; choosing one writes its key into the call, the first form writes none. The Builder reads the list when it opens a page, so a form made in the Forms panel is offered once the page is opened again. A key whose form is gone is read as empty wherever the Components module renders the call, and the first form is drawn.',
				'de_DE' => 'Unter Eigenschaften ist Formular ein Select der vorhandenen Formulare mit ihren Namen, das erste Formular steht oben; die Auswahl schreibt den Schlüssel in den Aufruf, das erste Formular schreibt keinen. Der Builder liest die Liste, wenn er eine Seite öffnet, ein im Panel Formulare angelegtes Formular wird also angeboten, sobald die Seite erneut geöffnet wird. Ein Schlüssel, dessen Formular es nicht mehr gibt, gilt überall als leer, wo das Komponenten-Modul den Aufruf rendert, und das erste Formular wird gezeichnet.',
			],
			'[form] and the kernel it needs' => [
				'en_US' => 'The key is a select whose options a method of the feature answers (the Components module keeps options of that kind for a select or a variant only) - a bound the shortcode itself never declared: written by hand without that module it still draws nothing for a key no form has. Needs the Nino of the kernel patch after 1.8.0, whose Components module takes the options of an attribute from a method of the feature. On a kernel before it the whole manifest of this feature is refused - no panel, no guards, no [form] - and Forms cannot be installed, until the floor of the feature rises with that release.',
				'de_DE' => 'Der Schlüssel ist ein Select, dessen Optionen eine Methode des Features beantwortet (das Komponenten-Modul behält Optionen dieser Art nur für ein Select oder eine Variante) - eine Schranke, die der Shortcode selbst nie hatte: Von Hand ohne dieses Modul geschrieben zeichnet er für einen Schlüssel, den kein Formular hat, weiterhin nichts. Braucht das Nino des Kernel-Patches nach 1.8.0, dessen Komponenten-Modul die Optionen eines Attributs von einer Methode des Features nimmt. Auf einem Kernel davor wird das ganze Manifest dieses Features abgewiesen - kein Panel, keine Schutzmaßnahmen, kein [form] - und Formulare lässt sich nicht installieren, bis die Untergrenze des Features mit diesem Release steigt.',
			],
			'[form ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the form. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Formulars. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'The look of the fields' => [
				'en_US' => 'The fields, their labels, the status line, the checkbox (.nino-forms-check) and the group of radio buttons (.nino-forms-group) are drawn by Nino.css from the properties --forms-*, which the Design feature sets under the object Form, so this feature brings no stylesheet - and on a Nino before the one that draws the form there, a form looks as the contact form of the starter site does and the checkbox and the radio group are as the browser draws them.',
				'de_DE' => 'Die Felder, ihre Beschriftungen, die Statuszeile, die Checkbox (.nino-forms-check) und die Gruppe von Radio-Buttons (.nino-forms-group) zeichnet Nino.css aus den Properties --forms-*, die das Feature Design unter dem Objekt Formular einstellt, darum bringt dieses Feature kein Stylesheet mit – und auf einem Nino vor dem, das das Formular dort zeichnet, sieht ein Formular aus wie das Kontaktformular der Start-Site, und die Checkbox und die Radiogruppe sind, wie der Browser sie zeichnet.',
			],
		],
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
