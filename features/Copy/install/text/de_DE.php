<?php
declare(strict_types=1);

// Die drei Wörter des Knopfs - beim Aktivieren einmal in die text/de_DE.php
// des Projekts übernommen (nur ergänzend: ein Schlüssel, den das Projekt
// schon hat, bleibt); ab dann pflegen Redakteure sie im Panel Texte.
// Siehe README.md.
return [

	// Auf dem Knopf, und die Hälfte seines zugänglichen Namens, wenn der
	// Shortcode gesagt hat, was kopiert wird: „Kopieren: IBAN"
	'[[/copy/do]]'			=> 'Kopieren',

	// Für den Moment danach. Das Wort ändert sich, nicht nur die Farbe - eine
	// Farbe sagt es denen, die sie sehen, und sonst niemandem
	'[[/copy/done]]'		=> 'Kopiert',

	// Wenn keiner der beiden Wege funktioniert hat. Nennt, was stattdessen zu
	// tun ist, denn der Text ist markiert und jetzt ist der Leser dran
	'[[/copy/failed]]'	=> 'Strg+C drücken',
];
