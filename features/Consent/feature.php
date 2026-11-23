<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md, "The
// manifest"). The class is not declared here: features/Consent/ can only ever
// serve \Nino\Modules\Consent.
return [
	'key'					=> 'consent',
	'name'				=> [ 'en_US' => 'Consent', 'de_DE' => 'Cookie-Einwilligung' ],
	'description'	=> [
		'en_US' => 'Consent gives the site a banner with the categories statistics, marketing and external media. Scripts and content start only once their category is allowed. No third party is involved.',
		'de_DE' => 'Die Cookie-Einwilligung ermöglicht ein Banner mit den Kategorien Statistik, Marketing und externe Medien. Skripte und Inhalte starten erst, wenn ihre Kategorie erlaubt ist. Dafür braucht es keinen Drittanbieter.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[consent inline]' => [
				'en_US' => 'The consent banner, drawn by the page itself and written by no one; a theme chooses its look as a bar, a box or a dialog, and inline shows it where it stands, for a preview.',
				'de_DE' => 'Das Einwilligungs-Banner, von der Seite selbst gezeichnet und von niemandem geschrieben; ein Theme wählt es als Leiste, Kasten oder Dialog, und inline zeigt es dort, wo es steht, als Vorschau.',
			],
			'[consent-settings]' => [
				'en_US' => 'A button that opens the choice again, for a privacy page or a footer column.',
				'de_DE' => 'Ein Knopf, der die Wahl erneut öffnet, etwa auf der Datenschutzseite oder in einer Fußspalte.',
			],
		],
		'markup' => [
			'<script type="text/plain" data-consent="statistics" data-src="…">' => [
				'en_US' => 'Starts only once the category is allowed and joins the policy\'s script-src with its host; withdrawing the category reloads the page once.',
				'de_DE' => 'Startet erst, wenn die Kategorie erlaubt ist, und kommt mit seinem Host in die script-src der Richtlinie; wird die Kategorie widerrufen, lädt die Seite einmal neu.',
			],
			'<script type="text/plain" data-consent="statistics">' => [
				'en_US' => 'A placeholder with no data-src does not run: the policy of the page refuses an inline script that has no nonce.',
				'de_DE' => 'Ein Platzhalter ohne data-src läuft nicht: die Richtlinie der Seite weist ein Inline-Skript ohne Nonce ab.',
			],
			'data-consent-show="statistics" / data-consent-hide="statistics"' => [
				'en_US' => 'Shows or hides a box as long as the category is allowed and takes it back once it is withdrawn; not for an iframe, because a hidden iframe is still fetched.',
				'de_DE' => 'Zeigt oder verbirgt einen Kasten, solange die Kategorie erlaubt ist, und nimmt ihn zurück, sobald sie widerrufen wird; für ein iframe taugt das nicht, denn auch ein verstecktes wird geladen.',
			],
			'<html data-consent="necessary,statistics">' => [
				'en_US' => 'Names the allowed categories on load and on every change; your own code listens for the nino:consent event and reads event.detail.allowed.',
				'de_DE' => 'Nennt die erlaubten Kategorien beim Laden und bei jeder Änderung; eigener Code hört auf das Ereignis nino:consent und liest event.detail.allowed.',
			],
			'Custom properties' => [
				'en_US' => 'The part of the group Consent declares --consent-space, --consent-radius, --consent-shadow and --consent-dim, which a theme turns or a project overrides with a value on :root.',
				'de_DE' => 'Der Teil der Gruppe Einwilligung deklariert --consent-space, --consent-radius, --consent-shadow und --consent-dim, die ein Theme dreht oder ein Projekt mit einem Wert auf :root überschreibt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the look of the banner' => [
				'en_US' => 'The Themes panel offers the banner as the component consent with the looks Bar, Box and Dialog and the knobs for space and corners; the look that stands there is the one the banner has.',
				'de_DE' => 'Das Panel Themes bietet das Banner als Komponente consent an, mit den Looks Leiste, Kasten und Dialog und den Reglern für Abstand und Ecken; der Look, der dort steht, ist der des Banners.',
			],
		],
		'callbacks' => [
			'/nino/http/output' => [
				'en_US' => 'Draws the banner at the start of the body of every page, names the offered categories on the root element and adds the hosts of consent-gated scripts to the policy.',
				'de_DE' => 'Zeichnet das Banner an den Anfang des Bodys jeder Seite, nennt die angebotenen Kategorien am Wurzelelement und ergänzt die Richtlinie um die Hosts der einwilligungsabhängigen Skripte.',
			],
			'/nino/legal/section' => [
				'en_US' => 'Adds the "Cookie settings" button to the privacy policy\'s section on consent, where the Legal module is there.',
				'de_DE' => 'Hängt den Knopf „Cookie-Einstellungen“ an den Abschnitt der Datenschutzerklärung zur Einwilligung, wo es das Modul Legal gibt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the banner\'s words into the Text panel.',
				'de_DE' => 'Bringt die Worte des Banners ins Panel Texte.',
			],
			'blocks/consent-note/' => [
				'en_US' => 'A block: a headline, a sentence and the button that opens the choice again.',
				'de_DE' => 'Ein Block: eine Überschrift, ein Satz und der Knopf, der die Wahl wieder öffnet.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module, never replacing an existing one; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit, ohne einen vorhandenen zu ersetzen; ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'security',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'basis' ],
	// Nothing under data/: the visitor's choice lives in a cookie in the
	// browser, written by consent.js - never in a project-owned file a
	// backup would have to carry
	'data'				=> [],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Consent/assets/consent.js' ],
	'settings'		=> [
		'statistics' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Statistics category', 'de_DE' => 'Kategorie Statistik' ],
			'hint'		=> [
				'en_US' => 'Show the statistics category in the banner and allow visitors to store it.',
				'de_DE' => 'Zeigt die Kategorie Statistik im Banner und erlaubt Besuchern, sie zu speichern.',
			],
			'default'	=> false,
		],
		'marketing' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Marketing category', 'de_DE' => 'Kategorie Marketing' ],
			'hint'		=> [
				'en_US' => 'Show the marketing category in the banner and allow visitors to store it.',
				'de_DE' => 'Zeigt die Kategorie Marketing im Banner und erlaubt Besuchern, sie zu speichern.',
			],
			'default'	=> false,
		],
		'external' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'External media category', 'de_DE' => 'Kategorie externe Medien' ],
			'hint'		=> [
				'en_US' => 'Show the external media category (maps, videos, ...) in the banner and allow visitors to store it.',
				'de_DE' => 'Zeigt die Kategorie externe Medien (Karten, Videos, ...) im Banner und erlaubt Besuchern, sie zu speichern.',
			],
			'default'	=> false,
		],
		'policyUrl' => [
			'type'		=> 'url',
			'label'		=> [ 'en_US' => 'Privacy policy URL', 'de_DE' => 'URL der Datenschutzerklärung' ],
			'hint'		=> [
				'en_US' => 'Linked from the banner text. Empty: the privacy policy of the Legal module, otherwise no link.',
				'de_DE' => 'Wird im Bannertext verlinkt. Leer: die Datenschutzerklärung des Moduls Legal, sonst kein Link.',
			],
		],
		'cookieName' => [
			'type'				=> 'string',
			'label'				=> [ 'en_US' => 'Cookie name', 'de_DE' => 'Cookie-Name' ],
			'hint'				=> [
				'en_US' => 'Name of the cookie the banner writes the consent choice into. The privacy policy\'s section on consent names it - change it there too.',
				'de_DE' => 'Name des Cookies, in das das Banner die Einwilligung schreibt. Der Abschnitt zur Einwilligung in der Datenschutzerklärung nennt ihn – änderst Du den Wert, passe ihn dort mit an.',
			],
			'maxlength'		=> 100,
			'pattern'			=> '/^[A-Za-z0-9_-]+$/D',
			'default'			=> 'nino_consent',
		],
		'days' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Consent lifetime', 'de_DE' => 'Gültigkeitsdauer' ],
			'hint'		=> [
				'en_US' => 'How many days the cookie is kept before the banner asks again. The privacy policy\'s section on consent names 180 days - change it there too.',
				'de_DE' => 'Wie viele Tage das Cookie gültig bleibt, bevor das Banner erneut fragt. Der Abschnitt zur Einwilligung in der Datenschutzerklärung nennt 180 Tage – änderst Du den Wert, passe ihn dort mit an.',
			],
			'min'			=> 1,
			'max'			=> 365,
			'unit'		=> 'days',
			'default'	=> 180,
		],
	],
];
