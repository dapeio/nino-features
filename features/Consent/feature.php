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
		'en_US' => <<<'TXT'
			Put `[consent]` into the footer template - the banner appears on the
			first visit and never again once a choice is stored. A script that needs
			consent is written as `<script type="text/plain"
			data-consent="statistics" data-src="...">` and starts the moment that
			category is allowed. `[consent-settings]` opens the choice again, for a
			privacy page.
			TXT,
		'de_DE' => <<<'TXT'
			Setze `[consent]` in das Footer-Template – das Banner erscheint beim
			ersten Besuch und nie wieder, sobald eine Wahl gespeichert ist. Ein
			Skript, das Einwilligung braucht, wird als `<script type="text/plain"
			data-consent="statistics" data-src="...">` geschrieben und startet,
			sobald diese Kategorie erlaubt ist. `[consent-settings]` öffnet die Wahl
			erneut, etwa auf einer Datenschutzseite.
			TXT,
	],
	'category'		=> 'security',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
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
				'en_US' => 'Linked from the banner text. Empty: no link is shown.',
				'de_DE' => 'Wird im Bannertext verlinkt. Leer: es wird kein Link angezeigt.',
			],
		],
		'cookieName' => [
			'type'				=> 'string',
			'label'				=> [ 'en_US' => 'Cookie name', 'de_DE' => 'Cookie-Name' ],
			'hint'				=> [
				'en_US' => 'Name of the cookie the banner writes the consent choice into.',
				'de_DE' => 'Name des Cookies, in das das Banner die Einwilligung schreibt.',
			],
			'maxlength'		=> 100,
			'pattern'			=> '/^[A-Za-z0-9_-]+$/',
			'default'			=> 'nino_consent',
		],
		'days' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Consent lifetime', 'de_DE' => 'Gültigkeitsdauer' ],
			'hint'		=> [
				'en_US' => 'How many days the cookie is kept before the banner asks again.',
				'de_DE' => 'Wie viele Tage das Cookie gültig bleibt, bevor das Banner erneut fragt.',
			],
			'min'			=> 1,
			'max'			=> 365,
			'unit'		=> 'days',
			'default'	=> 180,
		],
	],
];
