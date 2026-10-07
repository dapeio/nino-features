<?php
declare(strict_types=1);

// Die Beschriftung des Pause-Knopfs - beim Aktivieren einmal in die
// text/de_DE.php des Projekts übernommen (nur ergänzend: ein Schlüssel, den das
// Projekt schon hat, bleibt); ab dann pflegen Redakteure sie im Panel Texte.
// Ein Container verlangt den Knopf mit
// data-typewriter-toggle="[[/feature/typewriter/pause/label]]", und der Wert kommt so, wie
// er ist, in die Seite - er darf also kein doppeltes Anführungszeichen
// enthalten.
return [
	'[[/feature/typewriter/pause/label]]'	=> 'Animation pausieren',
];
