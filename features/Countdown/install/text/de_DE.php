<?php
declare(strict_types=1);

// Die Wörter eines Zählers - beim Aktivieren einmal in die text/de_DE.php des
// Projekts übernommen (nur ergänzend: ein Schlüssel, den das Projekt schon
// hat, bleibt); ab dann pflegen Redakteure sie im Panel Texte.
// Siehe README.md.
return [

	/*	Beide Formen jeder Einheit, denn sonst steht dort „1 Tage". Das Skript
		bekommt die zwei Wörter am Teil selbst und wählt nur zwischen ihnen -
		ein statisches Asset kann keinen Textfill lesen	*/
	'[[/feature/countdown/unit-day/one]]'		=> 'Tag',
	'[[/feature/countdown/unit-day/many]]'		=> 'Tage',
	'[[/feature/countdown/unit-hour/one]]'		=> 'Stunde',
	'[[/feature/countdown/unit-hour/many]]'		=> 'Stunden',
	'[[/feature/countdown/unit-minute/one]]'	=> 'Minute',
	'[[/feature/countdown/unit-minute/many]]'	=> 'Minuten',
	'[[/feature/countdown/unit-second/one]]'	=> 'Sekunde',
	'[[/feature/countdown/unit-second/many]]'	=> 'Sekunden',

	// Was dort steht, wenn der Moment vorbei ist, sofern der Shortcode nichts
	// gesagt hat. Wer done="Der Verkauf läuft" schreibt, sagt etwas, das
	// dieser Satz nicht sagen kann
	'[[/feature/countdown/end/message]]'	=> 'Es ist so weit',
];
