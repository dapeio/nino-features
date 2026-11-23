<?php
// The class is not declared: features/Search/ can only serve \Nino\Modules\Search
return [
	'key'					=> 'search',
	'name'				=> [ 'en_US' => 'Elements search', 'de_DE' => 'Elemente-Suche' ],
	'description'	=> [
		'en_US' => 'Elements search lets visitors search the fields of your elements, locale-aware and fuzzy. A search field, the list of hits and their number are components you put on any page, and the block "Search page" brings all three. The index rebuilds on every save, and the panel sets which fields of a type it holds and tries the ranking.',
		'de_DE' => 'Die Elemente-Suche ermöglicht Besuchern eine sprachbewusste, unscharfe Suche über Felder Deiner Elemente. Ein Suchfeld, die Liste der Treffer und ihre Zahl sind Komponenten, die Du auf jede Seite setzt, und der Block „Suchseite“ bringt alle drei mit. Der Index baut sich bei jedem Speichern neu, und das Panel legt fest, welche Felder eines Typs er enthält, und probiert das Ranking aus.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[search-form action="page:/search" key="q"]' => [
				'en_US' => 'A search field with its button as a GET form that works without JavaScript and sends the words to the page action names, else to its own page, under the query variable key (q if none).',
				'de_DE' => 'Ein Suchfeld mit Knopf als GET-Formular, das ohne JavaScript funktioniert und die Worte unter der Query-Variable key (ohne Angabe q) an die Seite action schickt, sonst an die eigene Seite.',
			],
			'[search-results type="/products,/posts" key="q" limit="20" empty="~nothing"]…[/search-results]' => [
				'en_US' => 'Draws what it holds once for each hit, best first, like a collection: type names one type or several (empty: every indexed one), limit 200 hits at most, empty the text for none.',
				'de_DE' => 'Zeichnet, was zwischen den Marken steht, für jeden Treffer, den besten zuerst, wie eine Sammlung: type nennt Typen (leer: jeden indizierten), limit höchstens 200 Treffer, empty den Text für keinen.',
			],
			'[search-count type="/products" key="q" bare]' => [
				'en_US' => 'The number of all hits with its word behind it, or with bare as a plain number inside a sentence of your own: nothing while nothing was searched for, and 0 when nothing was found.',
				'de_DE' => 'Die Zahl aller Treffer mit ihrem Wort dahinter oder mit bare als reine Zahl in einem eigenen Satz: nichts, solange nichts gesucht wurde, und 0, wenn nichts gefunden wurde.',
			],
		],
		'markup' => [
			'[section-title .title] [text .summary] [text .score]' => [
				'en_US' => 'Inside a list a component reads the fields of the hit as its source: any field of the type, .uri, .slug and .score, the match value, which a field of the type called score replaces.',
				'de_DE' => 'Innerhalb einer Liste liest eine Komponente die Felder des Treffers als Quelle: jedes Feld des Typs, .uri, .slug und .score, den Treffer-Wert, den ein Feld des Typs namens score ersetzt.',
			],
			'[section-title .title href=".url"]' => [
				'en_US' => 'The list asks /search/hit for each hit it draws and reads .url, a path of this site that a feature answers, and .link for a block, which reads /_placeholder; a hit with none has no link.',
				'de_DE' => 'Die Liste fragt /search/hit für jeden gezeichneten Treffer und liest .url, einen Pfad dieser Website, den ein Feature antwortet, und .link für einen Block; ein Treffer ohne hat keinen Link.',
			],
		],
		'routes' => [
			'GET /.search?q=…&type=/products&limit=20&offset=0' => [
				'en_US' => 'The search as json while the setting JSON endpoint is on and not otherwise: the hits of one type or of every indexed one, each with its indexed fields and nothing else of the element.',
				'de_DE' => 'Die Suche als JSON, solange die Einstellung JSON-Endpoint an ist und sonst nicht: die Treffer eines Typs oder aller indizierten, jeder mit seinen indizierten Feldern und sonst nichts vom Element.',
			],
		],
		'panel' => [
			'Search' => [
				'en_US' => 'Index rebuilds the index of each type, Types ticks up to four fields of a type for /nino/elements/index in config.php, the first counts most, and Try it asks the real index.',
				'de_DE' => 'Index zeigt den Stand des Index jedes Typs und baut ihn neu, Typen kreuzt bis zu vier Felder für /nino/elements/index in der config.php an, das erste zählt am meisten, Ausprobieren fragt den Index.',
			],
		],
		'callbacks' => [
			'/nino/elements/committed' => [
				'en_US' => 'Refreshes the index of the type when one of its elements is saved.',
				'de_DE' => 'Frischt den Index des Typs auf, wenn eines seiner Elemente gespeichert wird.',
			],
			'/search/hit' => [
				'en_US' => 'Fired for every hit of the json endpoint and of the list, then as /search/hit/<type>; a feature adds url, the endpoint also takes its other fields or false to drop the hit, the list only the url.',
				'de_DE' => 'Wird für jeden Treffer des JSON-Endpoints und der Liste gefeuert, danach als /search/hit/<type>; ein Feature setzt url, der Endpoint nimmt auch andere Felder oder false, die Liste nur die url.',
			],
			'/search/hits' => [
				'en_US' => 'Fired with the whole ranked list before it is counted or paged; a feature answers it without the hits a visitor may not find, and the components, the count and the endpoint read what is left.',
				'de_DE' => 'Wird mit der ganzen Rangliste gefeuert, bevor sie gezählt oder geblättert wird; ein Feature antwortet ohne die Treffer, die ein Besucher nicht finden darf, und alle Leser sehen nur den Rest.',
			],
		],
		'install' => [
			'text/<locale>.php' => [ 'en_US' => 'The words of the search field, of the number of hits and of the list of hits.', 'de_DE' => 'Die Worte des Suchfelds, der Trefferzahl und der Trefferliste.' ],
			'blocks/search-page/' => [ 'en_US' => 'A block: a headline, the search field, the number of hits and the list.', 'de_DE' => 'Ein Block: eine Überschrift, das Suchfeld, die Zahl der Treffer und die Liste.' ],
			'data/index-<type>.php' => [ 'en_US' => 'The index of one type, derived from its elements and rebuilt on every save; no backup carries it, and one made without its fields named finds nothing until rebuilt.', 'de_DE' => 'Der Index eines Typs, aus seinen Elementen abgeleitet und bei jedem Speichern neu gebaut; kein Backup trägt ihn, und ohne Feldangabe gebaut findet er bis zum Neuaufbau nichts.' ],
		],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'basis' ],
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
	// The index files are derived from the elements and rebuilt on demand -
	// not data a backup has to carry
	'data'				=> [],
	'assets'			=> [],
];
