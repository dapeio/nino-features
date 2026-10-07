<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Consent/ can only ever serve
// \Nino\Modules\Consent.
return [
	'key'					=> 'consent',
	'name'				=> [ 'en_US' => 'Consent', 'de_DE' => 'Cookie-Einwilligung' ],
	'description'	=> [
		'en_US' => 'A cookie/consent banner with categories (statistics, marketing, external media) and consent-gated scripts - no third party involved.',
		'de_DE' => 'Ein Cookie-/Consent-Banner mit Kategorien (Statistik, Marketing, externe Medien) und einwilligungsabhängigen Skripten - ohne Drittanbieter.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[consent]' => [
				'en_US' => 'The banner, into the page frame - nothing is shown until it is there. Shown on the first visit and never again once a choice is stored.',
				'de_DE' => 'Das Banner, ins Seitengerüst - ohne diesen Eintrag zeigt sich nichts. Erscheint beim ersten Besuch und nie wieder, sobald eine Wahl gespeichert ist.',
			],
			'[consent-settings]' => [
				'en_US' => 'A button that opens the choice again - for a privacy page.',
				'de_DE' => 'Eine Schaltfläche, die die Wahl erneut öffnet – für eine Datenschutzseite.',
			],
			'[consent-settings ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the button. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Knopfs. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'<script type="text/plain" data-consent="statistics" data-src="…">' => [
				'en_US' => 'Starts the moment that category is allowed, and not before. The host of data-src joins the policy\'s script-src.',
				'de_DE' => 'Startet in dem Moment, in dem diese Kategorie erlaubt ist – und nicht davor. Der Host von data-src kommt in die script-src der Richtlinie.',
			],
			'data-consent-show="statistics" / data-consent-hide="statistics"' => [
				'en_US' => 'Shows a box once that category is allowed, hides it then. Not for an iframe: a hidden iframe is still fetched.',
				'de_DE' => 'Zeigt einen Kasten, sobald diese Kategorie erlaubt ist, beziehungsweise blendet ihn dann aus. Nicht für einen iframe: Auch ein versteckter iframe wird geladen.',
			],
			'<html data-consent="necessary,statistics"> and the nino:consent event' => [
				'en_US' => 'The allowed categories, on load and on every change. Code that has to be told listens for the event: event.detail.allowed.',
				'de_DE' => 'Die erlaubten Kategorien, beim Laden und bei jeder Änderung. Code, dem man es sagen muss, hört auf das Ereignis: event.detail.allowed.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/http/output' => [
				'en_US' => 'Adds the hosts of the page\'s consent-gated scripts to the policy\'s script-src.',
				'de_DE' => 'Ergänzt die script-src der Content-Security-Policy um die Hosts der einwilligungsabhängigen Skripte auf der Seite.',
			],
			'/nino/legal/section' => [
				'en_US' => 'Adds the "Cookie settings" button to the privacy policy\'s section on consent. Only fired where the Legal module is there.',
				'de_DE' => 'Hängt die Schaltfläche „Cookie-Einstellungen“ an den Abschnitt der Datenschutzerklärung zur Einwilligung. Wird nur dort ausgelöst, wo es das Modul Legal gibt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The banner\'s words, into the Text panel.',
				'de_DE' => 'Die Worte des Banners, ins Panel Texte.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy, added to the Legal module\'s type - never replacing a section. Where there is no such module, nothing happens. A starting point, no legal advice.',
				'de_DE' => 'Sein Abschnitt der Datenschutzerklärung, dem Typ des Moduls Legal hinzugefügt – ohne einen Abschnitt zu ersetzen. Wo es das Modul nicht gibt, passiert nichts. Ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'security',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: the visitor's choice lives in a cookie in the
	// browser, written by consent.js - never in a project-owned file a
	// backup would have to carry
	'data'				=> [],
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
			'pattern'			=> '/^[A-Za-z0-9_-]+$/',
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
		and stays a shortcode. Nino 1.5 ignores the key	*/
	'components'	=> [
		'consent-settings' => [
			'label'			=> [ 'en_US' => 'Consent settings', 'de_DE' => 'Einwilligung ändern' ],
			'source'		=> 'none',
			'loop'			=> false,
			'preview'		=> 'button',
		],
	],
];
