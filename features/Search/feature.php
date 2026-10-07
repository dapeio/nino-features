<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Search/ can only ever serve
// \Nino\Modules\Search.
return [
	'key'					=> 'search',
	'name'				=> [ 'en_US' => 'Elements search', 'de_DE' => 'Elemente-Suche' ],
	'description'	=> [
		'en_US' => 'A locale-aware fuzzy search index over configured Element fields, rebuilt on every save, with two shortcodes that draw the hits and their number on any page, under a form the project writes itself.',
		'de_DE' => 'Ein sprachbewusster unscharfer Suchindex über konfigurierte Elementfelder, neu gebaut bei jedem Speichern, mit zwei Shortcodes, die Treffer und Trefferzahl auf jeder Seite zeichnen, unter einem Formular, das das Projekt selbst schreibt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[search-results type="/products"]…[/search-results]' => [
				'en_US' => 'The hits, under a GET form the project writes itself. The body is the markup of one hit, with [[field]] for anything the type has.',
				'de_DE' => 'Die Treffer, unter einem GET-Formular, das das Projekt selbst schreibt. Der Inhalt ist das Markup eines Treffers, mit [[feld]] für alles, was der Typ hat.',
			],
			'[search-results ... key="q"]' => [
				'en_US' => 'The query variable it reads: the name of the input in the form. Default q.',
				'de_DE' => 'Die Query-Variable, die gelesen wird: der Name des Eingabefelds im Formular. Standard q.',
			],
			'[search-results ... empty="search-empty"]' => [
				'en_US' => 'What is drawn when nothing was found: a template of the project, here /templates/search-empty.tpl. Without it, nothing.',
				'de_DE' => 'Was gezeigt wird, wenn nichts gefunden wurde: ein Template des Projekts, hier /templates/search-empty.tpl. Ohne die Angabe nichts.',
			],
			'[search-count type="/products" key="q"]' => [
				'en_US' => 'How many hits the search has, as a plain number - all of them, not the page limit draws. Nothing while nothing was searched for, 0 when nothing was found.',
				'de_DE' => 'Wie viele Treffer die Suche hat, als reine Zahl - alle, nicht die Seite, die limit zeichnet. Nichts, solange nichts gesucht wurde, 0, wenn nichts gefunden wurde.',
			],
			'[search-results ... limit="20" tag="none"]' => [
				'en_US' => 'How many hits to draw (default 20, at most 200), and the wrapper: a div with class nino-search-results by default, tag="none" leaves the rows unwrapped. type takes several types, separated by commas.',
				'de_DE' => 'Wie viele Treffer gezeichnet werden (Standard 20, höchstens 200), und die Hülle: standardmäßig ein div mit der Klasse nino-search-results, tag="none" lässt die Zeilen ungehüllt. type nimmt mehrere Typen, durch Komma getrennt.',
			],
		],
		'markup' => [
			'[[.uri]] [[.slug]] [[.type]] [[.locale]] [[.score]] [[.n]]' => [
				'en_US' => 'Inside [search-results], beside the type\'s own [[field]]s: the element uri, its last segment, the type, the locale, the score and the place in the list from 1.',
				'de_DE' => 'Innerhalb von [search-results], neben den [[Feldern]] des Typs: die Element-Uri, ihr letztes Segment, der Typ, die Sprache, der Score und die Stelle in der Liste ab 1.',
			],
		],
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
			'Index, Type, Try it' => [
				'en_US' => 'Index: one row per Element type the project has, with its state. Type: four slots of weight over the type\'s own text fields, written to /nino/elements/index in config.php. Try it: the real ranking against the index on disk.',
				'de_DE' => 'Index: eine Zeile je Element-Typ des Projekts, mit seinem Stand. Typ: vier Gewichtsplätze über die Textfelder des Typs, geschrieben nach /nino/elements/index in der config.php. Ausprobieren: das echte Ranking gegen den Index auf der Platte.',
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
