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
		'routes' => [],
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
		],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '1.1.0',
	'nino'				=> '^1.0',
	'requires'		=> [],
	'settings'		=> [],
	// The index files are derived from the Elements and rebuilt on demand -
	// not data a backup has to carry
	'data'				=> [],
];
