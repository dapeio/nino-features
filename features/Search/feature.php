<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Search/ can only ever serve
// \Nino\Modules\Search.
return [
	'key'					=> 'search',
	'name'				=> [ 'en_US' => 'Elements search', 'de_DE' => 'Elemente-Suche' ],
	'description'	=> [
		'en_US' => 'Elements search lets you search the fields of your elements, locale-aware and fuzzy. The index rebuilds on every save. Two shortcodes draw the hits and their number on any page, under a form the project writes itself.',
		'de_DE' => 'Die Elemente-Suche ermöglicht eine sprachbewusste, unscharfe Suche über Felder der Elemente. Der Index baut sich bei jedem Speichern neu. Zwei Shortcodes zeichnen Treffer und Trefferzahl auf jeder Seite, unter einem Formular, das das Projekt selbst schreibt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[search-results type="/products"]…[/search-results]' => [
				'en_US' => 'Draws the hits under a GET form; the body is the markup of one hit, with [[field]] for anything the type has.',
				'de_DE' => 'Zeichnet die Treffer unter einem GET-Formular; der Inhalt ist das Markup eines Treffers, mit [[field]] für alles, was der Typ hat.',
			],
			'[search-results ... key="q" empty="search-empty" limit="20"]' => [
				'en_US' => 'Sets the query variable read, the template for nothing found and the number of hits (200 at most); type takes several types, separated by commas.',
				'de_DE' => 'Setzt die gelesene Query-Variable, das Template für „nichts gefunden“ und die Zahl der Treffer (höchstens 200); type nimmt mehrere Typen, durch Komma getrennt.',
			],
			'[search-results ... tag="none" class="my-class"]' => [
				'en_US' => 'The wrapper is a div.nino-search-results; tag picks another element, tag="none" leaves the rows unwrapped, class adds a class of your own.',
				'de_DE' => 'Die Hülle ist ein div.nino-search-results; tag wählt ein anderes Element, tag="none" lässt die Zeilen ungehüllt, class hängt eine eigene Klasse an.',
			],
			'[search-count type="/products" key="q"]' => [
				'en_US' => 'The number of all hits as a plain number: nothing while nothing was searched for, and 0 when nothing was found.',
				'de_DE' => 'Die Zahl aller Treffer als reine Zahl: nichts, solange nichts gesucht wurde, und 0, wenn nichts gefunden wurde.',
			],
		],
		'markup' => [
			'[[.uri]] [[.slug]] [[.type]] [[.locale]] [[.score]] [[.n]]' => [
				'en_US' => 'Fills inside [search-results], beside the type\'s own fields: the uri, its last segment, the type, the locale, the score and the place in the list from 1.',
				'de_DE' => 'Fills innerhalb von [search-results], neben den Feldern des Typs: Uri, ihr letztes Segment, Typ, Sprache, Score und die Stelle in der Liste ab 1.',
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
				'en_US' => 'Creates the index once, after that it rebuilds itself on every save; an index from an earlier version is marked stale and renewed with "Rebuild".',
				'de_DE' => 'Legt den Index einmal an, danach baut er sich bei jedem Speichern selbst neu; ein Index aus einer früheren Version ist als veraltet markiert und wird mit „Neu aufbauen“ erneuert.',
			],
			'Index, Type, Try it' => [
				'en_US' => 'Shows the state of the index per element type, sets four slots of weight over its text fields (/nino/elements/index in config.php) and tries the real ranking.',
				'de_DE' => 'Zeigt je Elementtyp den Stand des Index, stellt vier Gewichtsplätze über seine Textfelder ein (/nino/elements/index in der config.php) und probiert das echte Ranking aus.',
			],
		],
		'callbacks' => [
			'/nino/elements/committed' => [
				'en_US' => 'Refreshes the index when an element is saved.',
				'de_DE' => 'Frischt den Index auf, wenn ein Element gespeichert wird.',
			],
			'/search/hit' => [
				'en_US' => 'Fired for every endpoint hit, then as /search/hit/<type> for one type; a feature shapes the fields of a hit or answers false to drop it.',
				'de_DE' => 'Wird für jeden Endpoint-Treffer gefeuert, danach als /search/hit/<type> für einen Typ; ein Feature formt die Felder des Treffers oder antwortet false und lässt ihn weg.',
			],
			'/search/hits' => [
				'en_US' => 'Fired with the whole ranked list before it is counted or paged; a feature answers it without the hits a visitor may not find, and cannot add or change one.',
				'de_DE' => 'Wird mit der ganzen Rangliste gefeuert, bevor sie gezählt oder geblättert wird; ein Feature antwortet ohne die Treffer, die ein Besucher nicht finden darf, und kann keinen hinzufügen oder ändern.',
			],
		],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '1.3.1',
	'nino'				=> '^1.9',
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
	/*	Only the count as the Builder offers it. [search-results] is written around the
		markup of one hit, with [[field]] for what it holds, and the Builder can neither
		carry that markup nor edit it as it is: it reads a [[title]] as a call of the title
		component and its editor for a body would flatten the record. It stays a
		shortcode, like [consent]. The count is a bare number in the line it stands
		in, with no element round it, so the component says no draws - the one that
		does not (tests/components-smoke.php)	*/
	'components'	=> [
		'search-count' => [
			'label'			=> [ 'en_US' => 'Number of hits', 'de_DE' => 'Anzahl der Treffer' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'type' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Element types', 'de_DE' => 'Elementtypen' ],
					'hint'		=> [
						'en_US' => 'One element type, or several separated by commas, such as /products. Without one nothing is drawn.',
						'de_DE' => 'Ein Elementtyp oder mehrere, durch Komma getrennt, etwa /products. Ohne einen wird nichts gezeichnet.',
					],
				],
				'key' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Query variable', 'de_DE' => 'Query-Variable' ],
					'hint'		=> [
						'en_US' => 'The name of the input of the search form. Empty takes q.',
						'de_DE' => 'Der Name des Eingabefelds im Suchformular. Leer nimmt q.',
					],
				],
			],
			'preview'		=> 'text',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'line', [ 'x1' => '4', 'x2' => '20', 'y1' => '9', 'y2' => '9' ] ],
				[ 'line', [ 'x1' => '4', 'x2' => '20', 'y1' => '15', 'y2' => '15' ] ],
				[ 'line', [ 'x1' => '10', 'x2' => '8', 'y1' => '3', 'y2' => '21' ] ],
				[ 'line', [ 'x1' => '16', 'x2' => '14', 'y1' => '3', 'y2' => '21' ] ],
			],
		],
	],
];
