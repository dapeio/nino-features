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
	'[[/countdown/day]]'			=> 'Tag',
	'[[/countdown/days]]'			=> 'Tage',
	'[[/countdown/hour]]'			=> 'Stunde',
	'[[/countdown/hours]]'		=> 'Stunden',
	'[[/countdown/minute]]'		=> 'Minute',
	'[[/countdown/minutes]]'	=> 'Minuten',
	'[[/countdown/second]]'		=> 'Sekunde',
	'[[/countdown/seconds]]'	=> 'Sekunden',

	// Was dort steht, wenn der Moment vorbei ist, sofern der Shortcode nichts
	// gesagt hat. Wer done="Der Verkauf läuft" schreibt, sagt etwas, das
	// dieser Satz nicht sagen kann
	'[[/countdown/done]]'			=> 'Es ist so weit',
];
