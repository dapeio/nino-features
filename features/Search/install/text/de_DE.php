<?php
declare(strict_types=1);

// Die Worte des Suchfelds und der Trefferliste - bei jeder Aktivierung in die
// text/de_DE.php des Projekts übernommen (nur ergänzend: ein Schlüssel, den das
// Projekt schon hat, bleibt, einer, der fehlt, kommt dazu); ab dann pflegen
// Redakteure sie im Panel Texte.
return [

	// Der Name des Eingabefelds für Screenreader; auf dem Bildschirm steht der Platzhalter
	'[[/feature/search/form/label]]'	=> 'Suchen auf dieser Website',
	'[[/feature/search/form/placeholder]]'	=> 'Suchbegriff',
	'[[/feature/search/form/button]]'	=> 'Suchen',

	// Was die Liste sagt, wenn gesucht wurde und nichts passt - und kein Text am Aufruf steht
	'[[/feature/search/results/empty]]'	=> 'Nichts gefunden.',

	// Das Wort hinter der Zahl der Treffer, für einen Treffer und für alle anderen Zahlen
	'[[/feature/search/count/hit]]'	=> 'Treffer',
	'[[/feature/search/count/hits]]'	=> 'Treffer',
];
