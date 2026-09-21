<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Search/ can only ever serve
// \Nino\Modules\Search.
return [
	'key'					=> 'search',
	'name'				=> [ 'en_US' => 'Elements search', 'de_DE' => 'Elemente-Suche' ],
	'description'	=> [
		'en_US' => 'A locale-aware fuzzy search index over configured Element fields, rebuilt on every save, with two shortcodes that put a search form and its results on any page.',
		'de_DE' => 'Ein sprachbewusster unscharfer Suchindex über konfigurierte Elementfelder, neu gebaut bei jedem Speichern, mit zwei Shortcodes für Suchformular und Trefferliste auf jeder Seite.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[search placeholder="Search term" submit="Go"]' => [
				'en_US' => 'The form. A plain GET form, so a result page can be linked and bookmarked.',
				'de_DE' => 'Das Formular. Ein einfaches GET-Formular, eine Trefferseite ist also verlinkbar.',
			],
			'[search-results type="/products"]…[/search-results]' => [
				'en_US' => 'Its answer. The body is the markup of one hit, with [[field]] for anything the type has.',
				'de_DE' => 'Seine Antwort. Der Inhalt ist das Markup eines Treffers, mit [[feld]] für alles, was der Typ hat.',
			],
		],
		'markup' => [],
		'routes' => [
			'GET /.search?q=…&type=/products&limit=20&offset=0' => [
				'en_US' => 'The search as json, while the "JSON endpoint" setting is on: the hits of one type, or of every indexed one, each with its indexed fields.',
				'de_DE' => 'Die Suche als JSON, solange die Einstellung „JSON-Endpoint“ an ist: die Treffer eines Typs oder aller indizierten, jeder mit seinen indizierten Feldern.',
			],
		],
		'panel' => [
			'Search' => [
				'en_US' => 'Create the index once. After that it rebuilds itself with every save.',
				'de_DE' => 'Den Index einmal anlegen. Danach baut er sich bei jedem Speichern selbst neu.',
			],
		],
		'callbacks' => [
			'/nino/elements/committed' => [
				'en_US' => 'Refreshes the index when an element is saved.',
				'de_DE' => 'Frischt den Index auf, wenn ein Element gespeichert wird.',
			],
			'/search/hit' => [
				'en_US' => 'Fired for every endpoint hit, then as /search/hit/<type> for one type: shape the fields a hit carries, with the whole Element in hand, or answer false to drop it.',
				'de_DE' => 'Für jeden Endpoint-Treffer, danach als /search/hit/<typ> für einen Typ: die Felder eines Treffers formen, mit dem ganzen Element in der Hand, oder false antworten und ihn weglassen.',
			],
		],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '1.1.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	'settings'		=> [
		'api' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'JSON endpoint', 'de_DE' => 'JSON-Endpoint' ],
			'hint'		=> [
				'en_US' => 'Off by default. On, GET /.search?q=… answers the search as json: the hits of the indexed types with their indexed fields, or what a /search/hit callback makes of them. A public address that hands out content is a decision, not a side effect of a search.',
				'de_DE' => 'Standardmäßig aus. An beantwortet GET /.search?q=… die Suche als JSON: die Treffer der indizierten Typen mit ihren indizierten Feldern, oder was ein /search/hit-Callback daraus macht. Eine öffentliche Adresse, die Inhalte herausgibt, ist eine Entscheidung, kein Nebeneffekt einer Suche.',
			],
			'default'	=> false,
		],
	],
	// The index files are derived from the Elements and rebuilt on demand -
	// not data a backup has to carry
	'data'				=> [],
];
