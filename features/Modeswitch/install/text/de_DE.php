<?php
declare(strict_types=1);

// Die vier Wörter des Schalters - bei der Aktivierung einmal in die
// text/de_DE.php des Projekts gemischt (add-only: ein Schlüssel, den das
// Projekt schon hat, bleibt); ab dann pflegen Redakteure sie im Panel
// Texte. Siehe README.md.
return [

	// Was der Schalter als Ganzes ist, für Screenreader - die drei Knöpfe
	// darin sagen für sich genommen nicht, wozu sie zusammengehören
	'[[/modeswitch/label]]'		=> 'Darstellung',

	'[[/modeswitch/light]]'		=> 'Hell',
	// Nicht "Automatisch": was hier passiert, ist nicht, dass die Seite
	// etwas entscheidet, sondern dass sie es dem Gerät überlässt
	'[[/modeswitch/system]]'	=> 'System',
	'[[/modeswitch/dark]]'		=> 'Dunkel',
];
