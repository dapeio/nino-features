<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md, "The
// manifest"). The class is not declared here: features/Consent/ can only ever
// serve \Nino\Modules\Consent.
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
			'Custom properties' => [
				'en_US' => 'consent.css declares the properties the banner is drawn from - --consent-ground with its parts (-on, -link, -border, -focus, -shadow), --consent-space, --consent-corners and --consent-shadow - with the page\'s own roles as their defaults, in :where(:root): a project restyles the banner without overriding a rule, with a value on :root in its own stylesheet. --consent-modal, 0 by default, is read by consent.js: at 1 the banner holds the page while it is shown (inert, the focus taken into it and kept there), which the look Dialog of the Design feature sets. The layout of the bar stands in :where(.nino-consent), so a rule on the class beats it.',
				'de_DE' => 'consent.css deklariert die Properties, aus denen das Banner gezeichnet wird – --consent-ground mit seinen Teilen (-on, -link, -border, -focus, -shadow), --consent-space, --consent-corners und --consent-shadow –, mit den Rollen der Seite als Vorgabe, in :where(:root): Ein Projekt gestaltet das Banner um, ohne eine Regel zu überschreiben, mit einem Wert auf :root im eigenen Stylesheet. --consent-modal, vorgegeben 0, liest consent.js: Bei 1 hält das Banner die Seite, solange es zu sehen ist (inert, der Fokus wird hineingenommen und bleibt dort), was der Look Dialog des Features Design setzt. Das Layout der Leiste steht in :where(.nino-consent), eine Regel auf der Klasse gewinnt also dagegen.',
			],
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
		'panel' => [
			'Design: the look of the banner' => [
				'en_US' => 'With the Design feature on, the tab Consent of the Design panel offers under Base three looks of the banner - Bar, Box and Dialog - and its knobs stand under Surface (the space inside the banner, its corners and its shadow) and Colour (the ground it is painted on). Bar is the banner as it has always stood, along the foot of the page; Box is a narrow box in the corner, the bar again on a narrow screen; Dialog stands in the middle of the window, dims the page behind it and holds it until a choice is made. The three buttons are the kernel\'s buttons, so the Buttons of the page reach them. The preview of the Design panel shows the banner in the flow, on the block this feature brings, design/preview.tpl, in a section named after the feature before the footer of the specimen; Apply to website puts the look on the site.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Cookie-Einwilligung des Design-Panels unter Grundlage drei Looks des Banners an – Leiste, Kasten und Dialog –, und seine Regler stehen unter Fläche (der Innenabstand des Banners, seine Ecken und sein Schatten) und Farbe (der Grund, auf den es gemalt ist). Die Leiste ist das Banner, wie es immer stand, am Fuß der Seite; der Kasten ist ein schmaler Kasten in der Ecke, auf einem schmalen Bildschirm wieder die Leiste; der Dialog steht in der Mitte des Fensters, dunkelt die Seite dahinter ab und hält sie fest, bis gewählt ist. Die drei Knöpfe sind die Buttons des Kernels, die Buttons der Seite erreichen sie also. Die Vorschau des Design-Panels zeigt das Banner im Fluss, an dem Block, den dieses Feature mitbringt, design/preview.tpl, in einer Sektion mit dem Namen des Features vor dem Footer der Musterseite; Auf Website anwenden bringt den Look auf die Site.',
			],
		],
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
	'version'			=> '1.1.0',
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
			'in'			=> [ 'section', 'card', 'hero' ],
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
