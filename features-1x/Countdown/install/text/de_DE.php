<?php
declare(strict_types=1);

// Die Wörter eines Zählers - bei jeder Aktivierung in die text/de_DE.php des
// Projekts übernommen (nur ergänzend: ein Schlüssel, den das Projekt schon
// hat, bleibt, einer, der fehlt, kommt dazu); ab dann pflegen Redakteure sie
// im Panel Texte.
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

	/*	Die Namen der Monate und der Tage, ausgeschrieben und kurz, für die
		Buchstaben F, M, l und D eines format="...": das Datum unter dem Zähler
		steht in der Sprache der Seite, nicht im Englisch von PHP. Der Name im
		Schlüssel ist in jeder Sprache der englische in Kleinbuchstaben, der
		Wert das Wort dieser Sprache	*/
	'[[/feature/countdown/month/january]]'	=> 'Januar',
	'[[/feature/countdown/month/february]]'	=> 'Februar',
	'[[/feature/countdown/month/march]]'	=> 'März',
	'[[/feature/countdown/month/april]]'	=> 'April',
	'[[/feature/countdown/month/may]]'	=> 'Mai',
	'[[/feature/countdown/month/june]]'	=> 'Juni',
	'[[/feature/countdown/month/july]]'	=> 'Juli',
	'[[/feature/countdown/month/august]]'	=> 'August',
	'[[/feature/countdown/month/september]]'	=> 'September',
	'[[/feature/countdown/month/october]]'	=> 'Oktober',
	'[[/feature/countdown/month/november]]'	=> 'November',
	'[[/feature/countdown/month/december]]'	=> 'Dezember',

	'[[/feature/countdown/month-short/january]]'	=> 'Jan',
	'[[/feature/countdown/month-short/february]]'	=> 'Feb',
	'[[/feature/countdown/month-short/march]]'	=> 'Mär',
	'[[/feature/countdown/month-short/april]]'	=> 'Apr',
	'[[/feature/countdown/month-short/may]]'	=> 'Mai',
	'[[/feature/countdown/month-short/june]]'	=> 'Jun',
	'[[/feature/countdown/month-short/july]]'	=> 'Jul',
	'[[/feature/countdown/month-short/august]]'	=> 'Aug',
	'[[/feature/countdown/month-short/september]]'	=> 'Sep',
	'[[/feature/countdown/month-short/october]]'	=> 'Okt',
	'[[/feature/countdown/month-short/november]]'	=> 'Nov',
	'[[/feature/countdown/month-short/december]]'	=> 'Dez',

	'[[/feature/countdown/day/monday]]'	=> 'Montag',
	'[[/feature/countdown/day/tuesday]]'	=> 'Dienstag',
	'[[/feature/countdown/day/wednesday]]'	=> 'Mittwoch',
	'[[/feature/countdown/day/thursday]]'	=> 'Donnerstag',
	'[[/feature/countdown/day/friday]]'	=> 'Freitag',
	'[[/feature/countdown/day/saturday]]'	=> 'Samstag',
	'[[/feature/countdown/day/sunday]]'	=> 'Sonntag',

	'[[/feature/countdown/day-short/monday]]'	=> 'Mo',
	'[[/feature/countdown/day-short/tuesday]]'	=> 'Di',
	'[[/feature/countdown/day-short/wednesday]]'	=> 'Mi',
	'[[/feature/countdown/day-short/thursday]]'	=> 'Do',
	'[[/feature/countdown/day-short/friday]]'	=> 'Fr',
	'[[/feature/countdown/day-short/saturday]]'	=> 'Sa',
	'[[/feature/countdown/day-short/sunday]]'	=> 'So',

	// Was dort steht, wenn der Moment vorbei ist, sofern der Shortcode nichts
	// gesagt hat. Wer done="Der Verkauf läuft" schreibt, sagt etwas, das
	// dieser Satz nicht sagen kann
	'[[/feature/countdown/end/message]]'	=> 'Es ist so weit',
];
