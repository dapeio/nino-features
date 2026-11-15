<?php
// features/Embed/feature.php - what the Features panel reads. The class is
// not declared here: features/Embed/ can only ever serve \Nino\Modules\Embed.
return [
	'key'					=> 'embed',
	'name'				=> [ 'en_US' => 'External Embeds', 'de_DE' => 'Externe Einbindungen' ],
	'description'	=> [
		'en_US' => 'External Embeds lets you show a video or a map as a surface visitors press, instead of an iframe that loads itself. Nothing is requested from the provider until someone asks or has allowed external media.',
		'de_DE' => 'Externe Einbindungen ermöglichen ein Video oder eine Karte als Fläche, die Besucher drücken, statt als iframe, das sich selbst lädt. Beim Anbieter wird nichts angefragt, bevor jemand es verlangt oder externe Medien erlaubt hat.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[embed youtube="ID"]' => [
				'en_US' => 'Embeds a YouTube video through youtube-nocookie.com, by video ID or the address from the browser; vimeo="ID" does the same for Vimeo.',
				'de_DE' => 'Bindet ein YouTube-Video über youtube-nocookie.com ein, mit der Video-ID oder der Adresse aus dem Browser; vimeo="ID" tut dasselbe für Vimeo.',
			],
			'[embed url="https://..."]' => [
				'en_US' => 'Embeds anything with an embed address, such as a map, a booking widget or a calendar; https only.',
				'de_DE' => 'Bindet alles mit einer Einbettungsadresse ein, etwa eine Karte, ein Buchungswidget oder einen Kalender; nur https.',
			],
			'[embed ... title="Anfahrt" poster="video/still.jpg" ratio="4-3" class="my-class"]' => [
				'en_US' => 'Sets the title (the only name for screen readers), a picture of your own as the surface, the shape (16-9, 4-3, 1-1, 21-9) and a class of your own.',
				'de_DE' => 'Setzt Titel (der einzige Name für Screenreader), ein eigenes Bild als Fläche, die Form (16-9, 4-3, 1-1, 21-9) und eine eigene Klasse.',
			],
		],
		'markup' => [
			'class="nino-embed"' => [
				'en_US' => 'This is what the shortcode writes; the address is a data attribute, and until it is asked for there is no iframe on the page.',
				'de_DE' => 'Das schreibt der Shortcode; die Adresse steht in einem data-Attribut, und vor der Anforderung gibt es kein iframe auf der Seite.',
			],
			'nino:embed event' => [
				'en_US' => 'Fired on the document with the host once an embed is released by a press or by consent.',
				'de_DE' => 'Wird am Dokument mit dem Host ausgelöst, sobald ein Embed durch Klick oder Einwilligung freigegeben ist.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/http/output' => [
				'en_US' => 'Adds the hosts of the embeds on this page to the policy\'s frame-src.',
				'de_DE' => 'Ergänzt die frame-src der Content-Security-Policy um die Hosts der Einbindungen auf der Seite.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the four words of an unreleased embed into the Text panel.',
				'de_DE' => 'Bringt die vier Wörter einer nicht freigegebenen Einbindung ins Panel Texte.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings three sections of the privacy policy for the Legal module (embedded content, YouTube, Vimeo); a starting point, no legal advice.',
				'de_DE' => 'Bringt drei Abschnitte der Datenschutzerklärung für das Modul Legal mit (eingebettete Inhalte, YouTube, Vimeo); ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	// It puts a control on the page that decides when something already
	// written there is fetched - the Features panel files that with the
	// lightboxes and the switches
	'category'		=> 'ui',
	'version'			=> '1.1.2',
	// The kernel whose toolset it calls: the template and class helpers, the
	// origin and policy helpers, the shortcode registration, the manifest's
	// assets and Nino.events
	'nino'				=> '^1.8',
	/*	Consent is not required. Without it every embed waits for a press,
		which is the safe half of what this does and works on its own. With it,
		a visitor who has already allowed external media does not have to press
		anything - see Embed.php on the two ways a frame is released	*/
	'requires'		=> [],
	// Nothing under data/: what is embedded is written into the project's own
	// templates, and whether a visitor allowed it is Consent's cookie in
	// their own browser
	'data'				=> [],
	// The surface's stylesheet and the script that builds the frame, into the
	// site's two bundles
	'assets'			=> [ '/features/Embed/assets/embed.css', '/features/Embed/assets/embed.js' ],
	'settings'		=> [
		'category' => [
			'type'				=> 'string',
			'label'				=> [ 'en_US' => 'Consent category', 'de_DE' => 'Einwilligungs-Kategorie' ],
			'hint'				=> [
				'en_US' => 'The Consent category that releases an embed without a press. Empty: every embed always waits for one. The privacy policy\'s section on embedded content names External media, the label of the default - change it there too.',
				'de_DE' => 'Die Consent-Kategorie, die eine Einbindung ohne Druck freigibt. Leer: Jede Einbindung wartet immer auf einen. Der Abschnitt zu eingebetteten Inhalten in der Datenschutzerklärung nennt Externe Medien, die Bezeichnung des Standards – änderst Du den Wert, passe ihn dort mit an.',
			],
			'maxlength'		=> 40,
			'pattern'			=> '/^[a-z0-9_-]*$/D',
			'default'			=> 'external',
		],
		'remember' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Remember a press for the visit', 'de_DE' => 'Druck für den Besuch merken' ],
			'hint'		=> [
				'en_US' => 'After one embed of a provider is loaded, load that provider\'s others on this page too. Nothing is stored: it lasts as long as the page is open.',
				'de_DE' => 'Ist eine Einbindung eines Anbieters geladen, werden dessen übrige auf dieser Seite mitgeladen. Gespeichert wird nichts: Es gilt, solange die Seite offen ist.',
			],
			'default'	=> false,
		],
	],
	/*	The shortcode as the Builder offers it: no first argument, the provider is named
		by the attribute that carries the address. The class has no componentEmbed():
		doShortcode() renders the component	*/
	'components'	=> [
		'embed' => [
			'label'			=> [ 'en_US' => 'Embedded content', 'de_DE' => 'Eingebetteter Inhalt' ],
			'source'		=> 'none',
			'attributes'	=> [
				'youtube' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'YouTube video', 'de_DE' => 'YouTube-Video' ],
					'hint'		=> [
						'en_US' => 'The video ID, or the address copied from the browser. Shown through youtube-nocookie.com.',
						'de_DE' => 'Die Video-ID oder die aus dem Browser kopierte Adresse. Gezeigt über youtube-nocookie.com.',
					],
				],
				'vimeo' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Vimeo video', 'de_DE' => 'Vimeo-Video' ],
					'hint'		=> [
						'en_US' => 'The video ID, or the address copied from the browser.',
						'de_DE' => 'Die Video-ID oder die aus dem Browser kopierte Adresse.',
					],
				],
				'url' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Address', 'de_DE' => 'Adresse' ],
					'hint'		=> [
						'en_US' => 'Anything else with an embed address - a map, a booking widget, a calendar. https only. Used where neither video is named.',
						'de_DE' => 'Alles andere mit einer Einbettungsadresse - eine Karte, ein Buchungswidget, ein Kalender. Nur https. Gilt, wo kein Video genannt ist.',
					],
				],
				'title' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Title', 'de_DE' => 'Titel' ],
					'hint'		=> [
						'en_US' => 'What the surface says and what the loaded frame is called. Say it for every embed: it is the only name a screen reader gets.',
						'de_DE' => 'Was auf der Fläche steht und wie der geladene Rahmen heißt. Bei jeder Einbindung angeben: Es ist der einzige Name, den ein Screenreader bekommt.',
					],
				],
				'poster' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Picture on the surface', 'de_DE' => 'Bild auf der Fläche' ],
					'hint'		=> [
						'en_US' => 'A file name below the project\'s images. Never a thumbnail from the provider.',
						'de_DE' => 'Ein Dateiname unter den Bildern des Projekts. Nie ein Vorschaubild des Anbieters.',
					],
				],
				'ratio' => [
					'type'		=> 'select',
					'options'	=> [ '16-9', '4-3', '1-1', '21-9' ],
					'default'	=> '16-9',
					'label'		=> [ 'en_US' => 'Shape', 'de_DE' => 'Form' ],
					'hint'		=> [
						'en_US' => 'The shape of the box.',
						'de_DE' => 'Die Form des Kastens.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'div .nino-embed',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M15.033 9.44a.647.647 0 0 1 0 1.12l-4.065 2.352a.645.645 0 0 1-.968-.56V7.648a.645.645 0 0 1 .967-.56z' ] ],
				[ 'path', [ 'd' => 'M12 17v4' ] ],
				[ 'path', [ 'd' => 'M8 21h8' ] ],
				[ 'rect', [ 'x' => '2', 'y' => '3', 'width' => '20', 'height' => '14', 'rx' => '2' ] ],
			],
		],
	],
];
