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
		'en_US' => <<<'TXT'
			Name the Element types and the fields to index under
			`/nino/elements/index` in `config.php`, then press Create searchindex in
			the Search panel once. After that the index rebuilds itself with every
			save.

			Two shortcodes put a search on a page. The first is the form; the body
			of the second is the markup of one hit, with [[field]] for anything the
			type's model has:

			  [search placeholder="Search term" submit="Go"]
			  [search-results type="/products"]
			    <h5>[[title]]</h5> <p>[[description]]</p>
			  [/search-results]

			They are a plain GET form and its answer, so a result page can be
			linked and bookmarked. The feature ships no page and no template of its
			own - what a hit looks like is the project's to write.

			Project code searches with `\Nino\Modules\Search::getElements()` and
			gets whole Elements back in score order.
			TXT,
		'de_DE' => <<<'TXT'
			Trage unter `/nino/elements/index` in der `config.php` ein, welche
			Elementtypen mit welchen Feldern indiziert werden, und drücke einmal
			Suchindex erstellen im Panel Suche. Danach baut sich der Index bei jedem
			Speichern selbst neu.

			Zwei Shortcodes setzen eine Suche auf eine Seite. Der erste ist das
			Formular, der Rumpf des zweiten ist das Markup eines Treffers, mit
			[[feld]] für alles, was das Modell des Typs hat:

			  [search placeholder="Suchbegriff" submit="Los"]
			  [search-results type="/products"]
			    <h5>[[title]]</h5> <p>[[description]]</p>
			  [/search-results]

			Es sind ein schlichtes GET-Formular und seine Antwort – eine
			Trefferseite lässt sich also verlinken und als Lesezeichen ablegen. Eine
			eigene Seite oder ein eigenes Template bringt das Feature nicht mit: Wie
			ein Treffer aussieht, schreibt das Projekt.

			Projektcode sucht mit `\Nino\Modules\Search::getElements()` und bekommt
			ganze Elemente in der Reihenfolge ihrer Treffer zurück.
			TXT,
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
