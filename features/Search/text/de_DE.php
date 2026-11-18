<?php
declare(strict_types=1);

// Die eigenen Worte des Panels, eine Datei je Oberflächensprache (siehe
// Admin::text()): in die Fills der Workbench gemischt, solange das Feature
// aktiv ist, nie in die Textdateien des Projekts. Die Worte der Seite - des
// Suchfelds und der Trefferliste - sind der andere Satz, in
// features/Search/install/text/. Ein %s, %d und %n füllt das Skript
return [

	// Der Eintrag in der Navigation und der Name über dem Panel
	'[[/_admin/nav/search]]'								=> 'Suche',

	'[[/_admin/search/label/tile]]'					=> 'Elemente durchsuchbar',
	'[[/_admin/search/label/tile-stale]]'		=> 'Durchsuchbar (Index veraltet)',

	// Die drei Reiter
	'[[/_admin/search/tab/index]]'						=> 'Index',
	'[[/_admin/search/tab/types]]'						=> 'Typen',
	'[[/_admin/search/tab/probe]]'						=> 'Ausprobieren',

	// Die Hilfe hinter dem (?) der Abschnitte
	'[[/_admin/search/hint/index]]'						=> 'Eine Zeile je Elementtyp, den das Projekt hat. Was indiziert ist, wird durchsuchbar – alles andere findet die Suche nicht, auch wenn es auf der Seite steht. Ein Index baut sich bei jedem Speichern eines Elements selbst neu; hier erneuerst Du ihn von Hand.',
	'[[/_admin/search/hint/types]]'						=> 'Welche Felder eines Typs der Index enthält. Ein Typ ohne Felder wird nicht indiziert.',
	'[[/_admin/search/hint/fields]]'						=> 'Bis zu vier Felder, das stärkste zuerst: In der Reihenfolge, in der Du sie ankreuzt, zählen sie 1,00, 0,70, 0,45 und 0,25. Die Gewichtung entscheidet über die Reihenfolge der Treffer, nicht darüber, ob ein Wort als gefunden gilt. Angeboten werden die Felder, die Text tragen; Bilder, Verweise und Ja/Nein-Felder stehen nicht zur Wahl, daraus lässt sich nichts suchen.',
	'[[/_admin/search/hint/stale]]'						=> 'Der Index ist älter als der Typ, wurde aus anderen Feldern gebaut, als hier stehen, oder stammt aus einer früheren Version des Features – mit anders gefalteten Wörtern oder ohne die Elemente, die eine Sprache aus einer anderen Sprache zeigt. Speichere und baue ihn neu auf.',
	'[[/_admin/search/hint/probe]]'						=> 'Sucht genau so, wie die Seite es täte – mit dem Index, der jetzt auf der Platte liegt, in der Sprache, in der Du gerade arbeitest. Wer die Felder ändert, sieht die Reihenfolge hier kippen, ohne eine Seite dafür zu bauen.',
	'[[/_admin/search/hint/probe-limit]]'			=> 'Die besten %d Treffer.',

	'[[/_admin/search/label/type]]'					=> 'Typ',
	'[[/_admin/search/label/fields]]'				=> 'Indizierte Felder',
	'[[/_admin/search/label/index]]'					=> 'Suchindex',
	'[[/_admin/search/label/elements]]'			=> 'Elemente',
	'[[/_admin/search/label/state]]'					=> 'Zustand',
	'[[/_admin/search/label/edit]]'					=> 'Bearbeiten',
	'[[/_admin/search/label/rebuild]]'				=> 'Neu aufbauen',
	'[[/_admin/search/label/rebuild-all]]'		=> 'Alle neu aufbauen',
	'[[/_admin/search/label/search]]'				=> 'Typen suchen',
	'[[/_admin/search/label/search-hits]]'		=> 'Treffer suchen',
	'[[/_admin/search/label/uri]]'						=> 'Adresse des Elements',
	'[[/_admin/search/label/slot]]'						=> 'Priorität %d, zählt %s',
	'[[/_admin/search/label/saveandbuild]]'	=> 'Speichern und aufbauen',

	'[[/_admin/search/label/query]]'					=> 'Suchbegriff',
	'[[/_admin/search/label/probe-types]]'		=> 'Typen durchsuchen',
	'[[/_admin/search/label/run]]'						=> 'Suchen',
	'[[/_admin/search/label/hit]]'						=> 'Treffer',
	'[[/_admin/search/label/score]]'					=> 'Score',
	'[[/_admin/search/label/coverage]]'			=> 'Abdeckung',
	'[[/_admin/search/label/matched]]'				=> 'gefunden in',

	'[[/_admin/search/empty/types]]'					=> 'Noch keine Elementtypen. Lege im Panel Typen einen an, dann ist hier etwas zu indizieren.',
	'[[/_admin/search/empty/nomatch]]'				=> 'Keine Typen gefunden.',
	'[[/_admin/search/empty/probe]]'					=> 'Kein Treffer.',
	'[[/_admin/search/empty/probe-types]]'		=> 'Noch kein Typ ist indiziert. Kreuze unter Typen die Felder eines Typs an.',

	'[[/_admin/search/state/current]]'				=> 'aktuell',
	'[[/_admin/search/state/stale]]'					=> 'veraltet',
	'[[/_admin/search/state/missing]]'				=> 'nicht aufgebaut',
	'[[/_admin/search/state/off]]'						=> 'nicht indiziert',
	'[[/_admin/search/state/broken]]'					=> 'fehlerhaft',
	'[[/_admin/search/state/built]]'					=> 'aufgebaut: %s',

	'[[/_admin/search/msg/none]]'						=> 'Es ist kein Typ zum Indizieren konfiguriert.',
	'[[/_admin/search/msg/created]]'					=> '%d Suchindex für %n Elemente erstellt.',
	'[[/_admin/search/msg/created-plural]]'	=> '%d Suchindexe für %n Elemente erstellt.',
	'[[/_admin/search/msg/removed]]'					=> 'Gespeichert. Der Typ wird nicht mehr indiziert, sein Index wurde entfernt.',
	'[[/_admin/search/msg/rebuild]]'					=> 'Gespeichert. Bis der Index neu aufgebaut ist, zählen nur die Felder, die alte und neue Auswahl gemeinsam haben.',

	// Was an der Konfiguration in der config.php nicht stimmt, in den Worten der Oberfläche; %s füllt das Skript
	'[[/_admin/search/issue/name]]'					=> 'Das ist kein Name eines Elementtyps.',
	'[[/_admin/search/issue/list]]'					=> 'Der Eintrag ist keine Liste von Feldern.',
	'[[/_admin/search/issue/notype]]'					=> 'Es gibt keinen Elementtyp "%s".',
	'[[/_admin/search/issue/priority]]'				=> 'Die Priorität "%s" gibt es nicht - die vier Plätze sind 0, 1, 2 und 3.',
	'[[/_admin/search/issue/noname]]'					=> 'Die Priorität %s nennt kein Feld.',
	'[[/_admin/search/issue/nofield]]'				=> 'Das Modell von "%s" hat kein Feld "%s".',
	'[[/_admin/search/issue/none]]'					=> 'Es ist kein Feld genannt.',

	'[[/_admin/search/error/create]]'				=> 'Die Suchindexe konnten nicht erstellt werden.',
	'[[/_admin/search/error/save]]'					=> 'Die Konfiguration konnte nicht gespeichert werden.',
	'[[/_admin/search/error/probe]]'					=> 'Die Suche konnte nicht ausgeführt werden.',

	// Die Sätze zu den Codes, die Admin::apiSave() nennt
	'[[/_admin/error/search_type]]'					=> 'Das ist kein Name eines Elementtyps.',
	'[[/_admin/error/search_notype]]'				=> 'Es gibt keinen Elementtyp "%s".',
	'[[/_admin/error/search_fields]]'				=> 'Die Prioritäten wurden nicht als Feldnamen gesendet.',
	'[[/_admin/error/search_priority]]'			=> 'Die Priorität %s gibt es nicht - die vier Plätze sind 0, 1, 2 und 3.',
	'[[/_admin/error/search_field]]'					=> 'Das Modell von "%s" hat kein Feld "%s".',
	'[[/_admin/error/search_text]]'					=> 'Das Feld "%s" enthält keinen Text, der sich durchsuchen ließe.',
];
