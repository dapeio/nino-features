<?php
declare(strict_types=1);

// Die drei Wörter eines Paars - beim Aktivieren einmal in die text/de_DE.php
// des Projekts übernommen (nur ergänzend: ein Schlüssel, den das Projekt
// schon hat, bleibt); ab dann pflegen Redakteure sie im Panel Texte.
// Siehe README.md.
return [

	// Wie die beiden Seiten heißen, wenn der Shortcode nichts gesagt hat. Wer
	// before-label="Rohbau" schreibt, sagt etwas, das diese nicht sagen können
	'[[/feature/compare/side/before]]'	=> 'Vorher',
	'[[/feature/compare/side/after]]'	=> 'Nachher',

	// Was das Bedienelement ist, für jemanden, der es mit der Tastatur
	// erreicht und die beiden Bilder nie gesehen hat
	'[[/feature/compare/handle/label]]'	=> 'Trenner zwischen den beiden Bildern bewegen',
];
