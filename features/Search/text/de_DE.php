<?php
// Die eigenen Workbench-Texte des Search-Features, in seine Fills gemischt,
// solange das Feature aktiv ist (siehe text() des Panels) - dieselben
// Schlüssel und dieselbe Form wie text/<locale>.php der Workbench. Ein %s
// füllt das Skript
return [
	'[[/_admin/nav/search]]'								=> 'Suche',

	'[[/_admin/search/label/title]]'				=> 'Suchindex der Elemente',
	'[[/_admin/search/label/tile]]'					=> 'Elemente durchsuchbar',
	'[[/_admin/search/label/tile-stale]]'		=> 'Durchsuchbar (Index veraltet)',
	'[[/_admin/search/hint/intro]]'					=> 'Eine Zeile je Elementtyp, den das Projekt hat. Was indiziert ist, wird durchsuchbar – alles andere findet die Suche nicht, auch wenn es auf der Seite steht.',

	'[[/_admin/search/label/type]]'					=> 'Typ',
	'[[/_admin/search/label/fields]]'				=> 'Indizierte Felder',
	'[[/_admin/search/label/elements]]'			=> 'Elemente',
	'[[/_admin/search/label/state]]'				=> 'Zustand',
	'[[/_admin/search/label/edit]]'					=> 'Bearbeiten',
	'[[/_admin/search/label/rebuild]]'			=> 'Neu aufbauen',
	'[[/_admin/search/label/rebuild-all]]'	=> 'Alle neu aufbauen',
	'[[/_admin/search/label/probe]]'				=> 'Ausprobieren',
	'[[/_admin/search/label/back]]'					=> 'Zurück zur Übersicht',

	'[[/_admin/search/state/current]]'			=> 'aktuell',
	'[[/_admin/search/state/stale]]'				=> 'veraltet',
	'[[/_admin/search/state/missing]]'			=> 'nicht aufgebaut',
	'[[/_admin/search/state/off]]'					=> 'nicht indiziert',
	'[[/_admin/search/state/broken]]'				=> 'fehlerhaft',
	'[[/_admin/search/state/built]]'				=> 'aufgebaut: %s',

	'[[/_admin/search/label/slot]]'					=> 'Priorität %d',
	'[[/_admin/search/label/weight]]'				=> 'zählt %s',
	'[[/_admin/search/label/nofield]]'			=> '— kein Feld —',
	'[[/_admin/search/label/save]]'					=> 'Speichern',
	'[[/_admin/search/label/saveandbuild]]'	=> 'Speichern und aufbauen',
	'[[/_admin/search/hint/slots]]'					=> 'Vier Plätze, vom stärksten zum schwächsten. Die Gewichtung entscheidet über die Reihenfolge der Treffer, nicht darüber, ob ein Wort als gefunden gilt: Was in Priorität 3 steht, wird genauso gefunden wie Priorität 0 – nur weiter unten.',
	'[[/_admin/search/hint/indexable]]'			=> 'Angeboten werden die Felder des Modells, die Text tragen. Bilder, Verweise und Ja/Nein-Felder stehen nicht zur Wahl – daraus lässt sich nichts suchen.',
	'[[/_admin/search/hint/empty]]'					=> 'Dieses Projekt hat noch keine Elementtypen. Lege im Panel Typen einen an, dann ist hier etwas zu indizieren.',
	'[[/_admin/search/hint/nofields]]'			=> 'Kein Feld gewählt – dieser Typ wird nicht indiziert, und ein vorhandener Index wird beim Speichern entfernt.',
	'[[/_admin/search/hint/stale]]'					=> 'Der Index ist älter als der Typ oder wurde aus anderen Feldern gebaut, als hier stehen. Neu aufbauen.',

	'[[/_admin/search/label/query]]'				=> 'Suchbegriff',
	'[[/_admin/search/label/locale]]'				=> 'Sprache',
	'[[/_admin/search/label/run]]'					=> 'Suchen',
	'[[/_admin/search/label/hit]]'					=> 'Treffer',
	'[[/_admin/search/label/score]]'				=> 'Score',
	'[[/_admin/search/label/coverage]]'			=> 'Abdeckung',
	'[[/_admin/search/label/matched]]'			=> 'gefunden in',
	'[[/_admin/search/hint/probe]]'					=> 'Sucht genau so, wie die Seite es täte – mit dem Index, der jetzt auf der Platte liegt. Wer die Prioritäten ändert, sieht die Reihenfolge hier kippen, ohne eine Seite dafür zu bauen.',
	'[[/_admin/search/hint/probe-empty]]'		=> 'Kein Treffer.',
	'[[/_admin/search/hint/probe-limit]]'		=> 'Die besten %d Treffer.',

	'[[/_admin/search/msg/creating]]'				=> 'Suchindexe werden erstellt …',
	'[[/_admin/search/msg/none]]'						=> 'Es ist kein Typ zum Indizieren konfiguriert.',
	'[[/_admin/search/msg/created]]'				=> '%d Suchindex für %n Elemente erstellt.',
	'[[/_admin/search/msg/created-plural]]'	=> '%d Suchindexe für %n Elemente erstellt.',
	'[[/_admin/search/msg/saved]]'					=> 'Gespeichert. Der Index wird beim nächsten Aufbauen oder Speichern eines Elements neu geschrieben.',
	'[[/_admin/search/msg/removed]]'				=> 'Gespeichert. Der Typ wird nicht mehr indiziert, sein Index wurde entfernt.',
	'[[/_admin/search/error/create]]'				=> 'Die Suchindexe konnten nicht erstellt werden.',
	'[[/_admin/search/error/save]]'					=> 'Die Konfiguration konnte nicht gespeichert werden.',
];
