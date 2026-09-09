<?php
// features/Seo/feature.php - what the Features panel reads. The class is
// not declared here: features/Seo/ can only ever serve \Nino\Modules\Seo.
return [
	'key'					=> 'seo',
	'name'				=> [ 'en_US' => 'SEO', 'de_DE' => 'SEO' ],
	'description'	=> [
		'en_US' => 'Sitemap, robots.txt and llms.txt generated from the routes, locales and texts Nino already has - nothing to maintain by hand.',
		'de_DE' => 'Sitemap, robots.txt und llms.txt, erzeugt aus den Routen, Sprachen und Texten, die Nino bereits kennt - nichts, das von Hand gepflegt werden muss.',
	],
	'category'		=> 'marketing',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// Nothing under data/: everything this feature answers is derived from
	// config.php's routes and the project's text files on every request,
	// and the settings below - there is no state of its own to back up
	'data'				=> [],
	'settings'		=> [
		'exclude' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Never list these', 'de_DE' => 'Nie auflisten' ],
			'hint'	=> [
				'en_US' => 'One uri per line, eg. /internal or /internal/* for a whole subtree - left out of the sitemap and llms.txt.',
				'de_DE' => 'Eine Uri pro Zeile, zB /internal oder /internal/* für einen ganzen Teilbaum - wird aus Sitemap und llms.txt ausgelassen.',
			],
		],
		'disallow' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Additional robots.txt disallows', 'de_DE' => 'Zusätzliche robots.txt-Sperren' ],
			'hint'	=> [
				'en_US' => 'One path per line, added to robots.txt as its own "Disallow:" line - besides /_admin/ and /. , which are always disallowed.',
				'de_DE' => 'Ein Pfad pro Zeile, wird robots.txt als eigene "Disallow:"-Zeile hinzugefügt - zusätzlich zu /_admin/ und /. , die immer gesperrt sind.',
			],
		],
		'robots' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Additional robots.txt lines', 'de_DE' => 'Zusätzliche robots.txt-Zeilen' ],
			'hint'	=> [
				'en_US' => 'Appended to robots.txt verbatim, one line each, after the Sitemap line - a "User-agent:" block of your own, a "Crawl-delay:", anything the settings above do not cover.',
				'de_DE' => 'Wird robots.txt wortwörtlich angehängt, eine Zeile je Eintrag, nach der Sitemap-Zeile - ein eigener "User-agent:"-Block, ein "Crawl-delay:", alles, was die obigen Einstellungen nicht abdecken.',
			],
		],
		'agents' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Publish llms.txt', 'de_DE' => 'llms.txt veröffentlichen' ],
			'hint'		=> [
				'en_US' => 'Switches /llms.txt on for AI agents and assistants (llmstxt.org) - off answers a plain 404 there and drops its mention from robots.txt.',
				'de_DE' => 'Schaltet /llms.txt für KI-Agenten und -Assistenten ein (llmstxt.org) - aus liefert dort ein einfaches 404 und lässt die Erwähnung in robots.txt weg.',
			],
			'default'	=> true,
		],
		'description' => [
			'type'			=> 'string',
			'label'			=> [ 'en_US' => 'Site description', 'de_DE' => 'Seitenbeschreibung' ],
			'hint'			=> [
				'en_US' => 'One line describing the site, shown under the heading in llms.txt.',
				'de_DE' => 'Eine Zeile zur Beschreibung der Seite, erscheint in llms.txt unter der Überschrift.',
			],
			'maxlength'	=> 300,
		],
		'llms' => [
			'type'	=> 'text',
			'label'	=> [ 'en_US' => 'llms.txt free text', 'de_DE' => 'llms.txt Freitext' ],
			'hint'	=> [
				'en_US' => 'An optional block of markdown placed after the description and before the page list, eg. contact details or usage notes for an AI agent.',
				'de_DE' => 'Ein optionaler Markdown-Block nach der Beschreibung und vor der Seitenliste, zB Kontaktdaten oder Hinweise für einen KI-Agenten.',
			],
		],
		'logo' => [
			'type'	=> 'url',
			'label'	=> [ 'en_US' => 'Logo url', 'de_DE' => 'Logo-Url' ],
			'hint'	=> [
				'en_US' => 'An absolute image url, added to the [seo-jsonld] Organization block when set.',
				'de_DE' => 'Eine absolute Bild-Url, wird bei Angabe dem Organization-Block von [seo-jsonld] hinzugefügt.',
			],
		],
	],
];
