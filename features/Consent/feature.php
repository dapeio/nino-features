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
			'[consent]' => [
				'en_US' => 'Lets you put the banner into the page frame; it shows on the first visit and never again once a choice is stored.',
				'de_DE' => 'Ermöglicht es, das Banner ins Seitengerüst zu setzen; es erscheint beim ersten Besuch und nie wieder, sobald eine Wahl gespeichert ist.',
			],
			'[consent-settings]' => [
				'en_US' => 'A button that opens the choice again, for a privacy page; class="my-class" adds a class of your own.',
				'de_DE' => 'Ein Knopf, der die Wahl erneut öffnet, etwa auf der Datenschutzseite; class="my-class" hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'<script type="text/plain" data-consent="statistics" data-src="…">' => [
				'en_US' => 'Starts only once the category is allowed, and the host of data-src joins the policy\'s script-src.',
				'de_DE' => 'Startet erst, wenn die Kategorie erlaubt ist, und der Host von data-src kommt in die script-src der Richtlinie.',
			],
			'data-consent-show="statistics" / data-consent-hide="statistics"' => [
				'en_US' => 'Shows or hides a box once the category is allowed; not for an iframe, because a hidden iframe is still fetched.',
				'de_DE' => 'Zeigt oder verbirgt einen Kasten, sobald die Kategorie erlaubt ist; für ein iframe taugt das nicht, denn auch ein verstecktes wird geladen.',
			],
			'<html data-consent="necessary,statistics">' => [
				'en_US' => 'Names the allowed categories on load and on every change; your own code listens for the nino:consent event and reads event.detail.allowed.',
				'de_DE' => 'Nennt die erlaubten Kategorien beim Laden und bei jeder Änderung; eigener Code hört auf das Ereignis nino:consent und liest event.detail.allowed.',
			],
			'Custom properties' => [
				'en_US' => 'consent.css declares --consent-ground, --consent-space, --consent-corners, --consent-shadow and --consent-modal, which a project overrides with a value on :root.',
				'de_DE' => 'consent.css deklariert --consent-ground, --consent-space, --consent-corners, --consent-shadow und --consent-modal, die ein Projekt mit einem Wert auf :root überschreibt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the look of the banner' => [
				'en_US' => 'With the Design feature the banner offers the looks Bar, Box and Dialog, with knobs for surface and colour and a preview on the specimen page.',
				'de_DE' => 'Mit dem Design bietet das Banner die Looks Leiste, Kasten und Dialog an, samt Reglern für Fläche und Farbe und einer Vorschau auf der Musterseite.',
			],
		],
		'callbacks' => [
			'/nino/http/output' => [
				'en_US' => 'Adds the hosts of the page\'s consent-gated scripts to the policy\'s script-src.',
				'de_DE' => 'Ergänzt die script-src der Content-Security-Policy um die Hosts der einwilligungsabhängigen Skripte auf der Seite.',
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
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module, never replacing an existing one; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit, ohne einen vorhandenen zu ersetzen; ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'security',
	'version'			=> '1.2.0',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing under data/: the visitor's choice lives in a cookie in the
	// browser, written by consent.js - never in a project-owned file a
	// backup would have to carry
	'data'				=> [],
	// The banner's stylesheet and its script, into the site's two bundles
	'assets'			=> [ '/features/Consent/assets/consent.css', '/features/Consent/assets/consent.js' ],
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
	/*	The one shortcode of the two that is meant for a page: the button that opens the
		choice again, for a privacy page. [consent] is the banner of the page frame
		and stays a shortcode. The renderer is componentConsentSettings(), which hands
		on to doConsentSettingsShortcode()	*/
	'components'	=> [
		'consent-settings' => [
			'label'			=> [ 'en_US' => 'Consent settings', 'de_DE' => 'Einwilligung ändern' ],
			'source'		=> 'none',
			'loop'			=> false,
			'preview'		=> 'button',
			'draws'			=> 'button .nino-consent-open',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5' ] ],
				[ 'path', [ 'd' => 'M8.5 8.5v.01' ] ],
				[ 'path', [ 'd' => 'M16 15.5v.01' ] ],
				[ 'path', [ 'd' => 'M12 12v.01' ] ],
				[ 'path', [ 'd' => 'M11 17v.01' ] ],
				[ 'path', [ 'd' => 'M7 14v.01' ] ],
			],
		],
	],
];
