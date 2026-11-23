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
				'en_US' => 'Embeds anything with an embed address, such as a map, a booking widget or a calendar; https only, never an IP address or an address with credentials.',
				'de_DE' => 'Bindet alles mit einer Einbettungsadresse ein, etwa eine Karte, ein Buchungswidget oder einen Kalender; nur https, nie eine IP-Adresse oder eine Adresse mit Zugangsdaten.',
			],
			'[embed ... title="~title" poster="~poster" ratio="4-3"]' => [
				'en_US' => 'Sets the title (the only name for screen readers), a picture slot of the project as the surface and the shape (16-9, 4-3, 1-1, 21-9); the title is text, and the picture is never one of the provider\'s.',
				'de_DE' => 'Setzt Titel (der einzige Name für Screenreader), einen Bildplatz des Projekts als Fläche und die Form (16-9, 4-3, 1-1, 21-9); der Titel ist Text, und das Bild ist nie eines des Anbieters.',
			],
		],
		'markup' => [
			'class="embed"' => [
				'en_US' => 'This is what the component writes; the address is a data attribute, and until it is asked for there is no iframe on the page and no request to the provider.',
				'de_DE' => 'Das schreibt die Komponente; die Adresse steht in einem data-Attribut, und vor der Anforderung gibt es kein iframe auf der Seite und keine Anfrage an den Anbieter.',
			],
			'nino:embed event' => [
				'en_US' => 'Fired on the document with the host once an embed is released by a press or by consent.',
				'de_DE' => 'Wird am Dokument mit dem Host ausgelöst, sobald ein Embed durch Klick oder Einwilligung freigegeben ist.',
			],
			'data-embed-consent' => [
				'en_US' => 'The Consent category of the setting, which the Consent feature reads to reload the page when that category is withdrawn after a frame was released.',
				'de_DE' => 'Die Consent-Kategorie der Einstellung, die das Feature Consent liest, um die Seite neu zu laden, wenn die Kategorie widerrufen wird, nachdem ein Rahmen freigegeben wurde.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group External Embeds' => [
				'en_US' => 'The Themes panel offers the surface as the component embed with a knob for its corners.',
				'de_DE' => 'Das Panel Themes bietet die Fläche als Komponente embed an, mit einem Regler für ihre Ecken.',
			],
		],
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
			'blocks/embed-video/' => [
				'en_US' => 'A block: a headline, a sentence and a video that loads only after a press.',
				'de_DE' => 'Ein Block: eine Überschrift, ein Satz und ein Video, das erst nach einem Klick geladen wird.',
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
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	/*	Consent is not required. Without it every embed waits for a press,
		which is the safe half of what this does and works on its own. With it,
		a visitor who has already allowed external media does not have to press
		anything - see Embed.php on the two ways a frame is released	*/
	'requires'		=> [ 'basis' ],			// the blocks and the frame are made of its components
	// Nothing under data/: what is embedded is written into the project's own
	// templates, and whether a visitor allowed it is Consent's cookie in
	// their own browser
	'data'				=> [],
	// The script that builds the frame, into the site's script bundle; the look
	// is the standard part of the group
	'assets'			=> [ '/features/Embed/assets/embed.js' ],
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
];
