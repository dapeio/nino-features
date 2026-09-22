<?php
// features/Embed/feature.php - what the Features panel reads. The class is
// not declared here: features/Embed/ can only ever serve \Nino\Modules\Embed.
return [
	'key'					=> 'embed',
	'name'				=> [ 'en_US' => 'External Embeds', 'de_DE' => 'Externe Einbindungen' ],
	'description'	=> [
		'en_US' => 'A video or a map as a surface the visitor presses, not as an iframe that loads itself: nothing is requested from the provider until they ask for it, or until they have allowed external media.',
		'de_DE' => 'Ein Video oder eine Karte als Fläche, die der Besucher drückt, nicht als iframe, das sich selbst lädt: Beim Anbieter wird nichts angefragt, bevor er es verlangt oder externe Medien erlaubt hat.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[embed youtube="ID"]' => [
				'en_US' => 'A YouTube video, through youtube-nocookie.com.',
				'de_DE' => 'Ein YouTube-Video, über youtube-nocookie.com.',
			],
			'[embed vimeo="ID"]' => [
				'en_US' => 'A Vimeo video, with its own do-not-track flag set.',
				'de_DE' => 'Ein Vimeo-Video, mit dessen eigenem Do-not-track-Schalter.',
			],
			'[embed url="https://..."]' => [
				'en_US' => 'Anything else with an embed address - a map, a booking widget, a calendar. https only.',
				'de_DE' => 'Alles andere mit einer Einbettungsadresse - eine Karte, ein Buchungswidget, ein Kalender. Nur https.',
			],
			'[embed ... title="Anfahrt"]' => [
				'en_US' => 'What the surface says and what the loaded frame is called. Say it for every embed: it is the only name a screen reader gets.',
				'de_DE' => 'Was auf der Fläche steht und wie der geladene Rahmen heißt. Bei jeder Einbindung angeben: Es ist der einzige Name, den ein Screenreader bekommt.',
			],
			'[embed ... poster="video/still.jpg"]' => [
				'en_US' => 'A picture from the project\'s own images as the surface. Never a thumbnail from the provider - fetching one would be the request this feature exists to prevent.',
				'de_DE' => 'Ein Bild aus den eigenen Bildern des Projekts als Fläche. Nie ein Vorschaubild des Anbieters - es zu holen wäre genau die Anfrage, die dieses Feature verhindert.',
			],
			'[embed ... ratio="4-3"]' => [
				'en_US' => 'The shape of the box: 16-9 (default), 4-3, 1-1 or 21-9.',
				'de_DE' => 'Die Form des Kastens: 16-9 (Vorgabe), 4-3, 1-1 oder 21-9.',
			],
		],
		'markup' => [
			'class="nino-embed"' => [
				'en_US' => 'What the shortcode writes. The address is a data attribute, not a src - there is no iframe on the page until one is asked for.',
				'de_DE' => 'Was der Shortcode schreibt. Die Adresse steht in einem data-Attribut, nicht in einem src - vor der Anforderung gibt es kein iframe auf der Seite.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The four words an unreleased embed carries - the two on the surface, the way out without JavaScript and the frame\'s own name - into the Text panel.',
				'de_DE' => 'Die vier Wörter einer nicht freigegebenen Einbindung - die zwei auf der Fläche, der Ausweg ohne JavaScript und der Name des Rahmens - ins Panel Texte.',
			],
		],
	],
	// It puts a control on the page that decides when something already
	// written there is fetched - the Features panel files that with the
	// lightboxes and the switches
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	// \Nino\Features::setting() and the feature contract this reads its
	// consent category through
	'nino'				=> '^1.3',
	/*	Consent is not required. Without it every embed waits for a press,
		which is the safe half of what this does and works on its own. With it,
		a visitor who has already allowed external media does not have to press
		anything - see Embed.php on the two ways a frame is released	*/
	'requires'		=> [],
	// Nothing under data/: what is embedded is written into the project's own
	// templates, and whether a visitor allowed it is Consent's cookie in
	// their own browser
	'data'				=> [],
	'settings'		=> [
		'category' => [
			'type'				=> 'string',
			'label'				=> [ 'en_US' => 'Consent category', 'de_DE' => 'Einwilligungs-Kategorie' ],
			'hint'				=> [
				'en_US' => 'The Consent category that releases an embed without a press. Empty: every embed always waits for one.',
				'de_DE' => 'Die Consent-Kategorie, die eine Einbindung ohne Druck freigibt. Leer: Jede Einbindung wartet immer auf einen.',
			],
			'maxlength'		=> 40,
			'pattern'			=> '/^[a-z0-9_-]*$/',
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
];
