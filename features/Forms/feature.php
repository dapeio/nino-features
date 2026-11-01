<?php
// features/Forms/feature.php - what the Features panel reads. The class is
// not declared here: features/Forms/ can only ever serve \Nino\Modules\Forms.
return [
	'key'					=> 'forms',
	'name'				=> [ 'en_US' => 'Forms', 'de_DE' => 'Formulare' ],
	'description'	=> [
		'en_US' => 'A builder for Nino\'s own form endpoint: any number of forms with fields of their own, a [form] shortcode that draws them, and spam protection without a captcha - and, with the Design feature, four looks of the fields with knobs for their size, space, corners, lines and labels.',
		'de_DE' => 'Ein Baukasten für Ninos eigenen Formular-Endpunkt: beliebig viele Formulare mit eigenen Feldern, ein Shortcode [form], der sie zeichnet, und Spam-Schutz ohne Captcha – und, mit dem Feature Design, vier Looks der Felder mit Reglern für ihre Größe, ihren Raum, ihre Ecken, Linien und Beschriftungen.',
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
		'markup' => [
			'Custom properties' => [
				'en_US' => 'forms.css declares the properties a field is drawn from - --forms-scale, --forms-space, --forms-corners, --forms-line, --forms-labels with its aspects, --forms-edge, --forms-label and the ones a look sets besides - with the kernel\'s own values as their defaults: lengths, numbers and keywords in :where(:root), the colours in :where(*). A project restyles a field without overriding a rule: a length, a number or a keyword goes on :root in its own stylesheet, a colour on :root, :root * (or on the element that reads it), because every element has a default of its own for the colours - the looks of the Design feature declare them that way. forms.css comes after the project\'s style.css in the bundle, so a rule of its own on .nino-form-input and the other field classes needs more specificity than the class alone - or it moves the properties. The names are declared at the top of the stylesheet.',
				'de_DE' => 'forms.css deklariert die Properties, aus denen ein Feld gezeichnet wird – --forms-scale, --forms-space, --forms-corners, --forms-line, --forms-labels mit seinen Aspekten, --forms-edge, --forms-label und die, die ein Look sonst setzt –, mit den Werten des Kernels als Vorgabe: Längen, Zahlen und Schlüsselwörter in :where(:root), die Farben in :where(*). Ein Projekt stellt ein Feld um, ohne eine Regel zu überschreiben: Eine Länge, eine Zahl oder ein Schlüsselwort steht im eigenen Stylesheet auf :root, eine Farbe auf :root, :root * (oder auf dem Element, das sie liest), denn für die Farben hat jedes Element eine eigene Vorgabe – so deklarieren sie auch die Looks des Features Design. forms.css kommt im Bündel nach dem style.css des Projekts; eine eigene Regel auf .nino-form-input und den anderen Feld-Klassen braucht darum mehr Spezifität als die Klasse allein – oder sie setzt die Properties. Die Namen stehen oben im Stylesheet.',
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
			'Design: the look of the forms' => [
				'en_US' => 'With the Design feature on, the block Forms under Templates offers four looks of the fields - Regular, Line, Filled and Contrast - and its knobs stand on Type (the size of the text), Surfaces (space, corners, the weight of the line, where the label stands: beside, above or floating) and Colours (the colour of the lines and of the labels). A look applies to every form that carries the kernel\'s form classes, drawn by [form] or written by hand; the send button stays the primary button of the page, and on a touch screen a field is never set under 1rem. The preview of the Design panel shows the look on the block this feature brings, design/preview.tpl - a form with its messages, in a section named after the feature, before the footer of the specimen - and a change of the look or of a knob scrolls the preview to it; Apply to website puts it on the site.',
				'de_DE' => 'Ist das Feature Design an, bietet der Block Formulare unter Vorlagen vier Looks der Felder an – Regulär, Linie, Gefüllt und Kontrast –, und seine Regler stehen auf Schrift (die Größe des Textes), Flächen (Raum, Ecken, Stärke der Linie, wo die Beschriftung steht: daneben, darüber oder schwebend) und Farben (die Farbe der Linien und der Beschriftungen). Ein Look gilt für jedes Formular mit den Formular-Klassen des Kernels, ob [form] es zeichnet oder Du es von Hand schreibst; der Absende-Button bleibt der Primär-Button der Seite, und auf einem Touch-Bildschirm wird ein Feld nie unter 1rem gesetzt. Die Vorschau des Design-Panels zeigt den Look an dem Block, den dieses Feature mitbringt, design/preview.tpl – ein Formular mit seinen Meldungen, in einer Sektion mit dem Namen des Features, vor dem Footer der Musterseite –, und eine Änderung des Looks oder eines Reglers scrollt die Vorschau dorthin; Auf Website anwenden bringt ihn auf die Site.',
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
	'version'			=> '1.1.0',
	// 1.8, for the toolset the code and the panel are written on, and for the
	// manifest key 'assets' that bundles the stylesheet
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing: the definitions live in config.php under '/nino/form/forms',
	// where the kernel reads them and every backup already carries them, and
	// the submissions are the kernel's own /data/forms.<Y-m>.php. The one
	// file this feature writes is a spam counter that rebuilds itself
	'data'				=> [],
	// The checkbox and the group of radio buttons [form] draws and the look of the
	// kernel's form family, into the site's style bundle
	'assets'			=> [ '/features/Forms/assets/forms.css' ],
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
