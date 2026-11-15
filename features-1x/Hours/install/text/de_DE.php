<?php
declare(strict_types=1);

// Die Wörter der Öffnungszeiten - bei jeder Aktivierung in text/de_DE.php des
// Projekts gemischt (nur hinzufügen: ein Schlüssel, den das Projekt hat, bleibt,
// ein fehlender kommt dazu); danach pflegt die Redaktion sie im Panel Texte.
return [

	'[[/feature/hours/day-short/mon]]'	=> 'Mo',
	'[[/feature/hours/day-short/tue]]'	=> 'Di',
	'[[/feature/hours/day-short/wed]]'	=> 'Mi',
	'[[/feature/hours/day-short/thu]]'	=> 'Do',
	'[[/feature/hours/day-short/fri]]'	=> 'Fr',
	'[[/feature/hours/day-short/sat]]'	=> 'Sa',
	'[[/feature/hours/day-short/sun]]'	=> 'So',

	'[[/feature/hours/day-long/mon]]'		=> 'Montag',
	'[[/feature/hours/day-long/tue]]'		=> 'Dienstag',
	'[[/feature/hours/day-long/wed]]'		=> 'Mittwoch',
	'[[/feature/hours/day-long/thu]]'		=> 'Donnerstag',
	'[[/feature/hours/day-long/fri]]'		=> 'Freitag',
	'[[/feature/hours/day-long/sat]]'		=> 'Samstag',
	'[[/feature/hours/day-long/sun]]'		=> 'Sonntag',

	'[[/feature/hours/word/closed]]'		=> 'Geschlossen',
	'[[/feature/hours/word/deviation]]'		=> 'Abweichung',
	'[[/feature/hours/word/today]]'			=> 'Heute',

	'[[/feature/hours/time/clock]]'			=> '24',
	'[[/feature/hours/time/range]]'			=> '%1$s–%2$s Uhr',
	'[[/feature/hours/time/am]]'				=> 'vormittags',
	'[[/feature/hours/time/midnight]]'		=> 'Mitternacht',
	'[[/feature/hours/time/pm]]'				=> 'nachmittags',
	'[[/feature/hours/date/range]]'			=> '%1$s bis %2$s',
	'[[/feature/hours/week/first]]'			=> 'mon',

	'[[/feature/hours/field/location]]'	=> 'Standort',
	'[[/feature/hours/field/from]]'			=> 'Von',
	'[[/feature/hours/field/until]]'		=> 'Bis',
	'[[/feature/hours/field/times]]'		=> 'Zeiten',

	'[[/feature/hours/error/unreadable]]'	=> '%1$s von „%2$s“ ist nicht lesbar: „%3$s“. Die Zeiten werden so geschrieben: 9-12, 14-18.',
	'[[/feature/hours/error/many]]'		=> '%1$s von „%2$s“: „%3$s“ hat mehr als 8 Zeiten.',
	'[[/feature/hours/error/minutes]]'		=> '%1$s von „%2$s“: „%3$s“ hat Minuten über 59.',
	'[[/feature/hours/error/hours]]'			=> '%1$s von „%2$s“: „%3$s“ hat eine Stunde außerhalb von 0 bis 24, und 24 gilt nur als Ende.',
	'[[/feature/hours/error/equal]]'			=> '%1$s von „%2$s“: „%3$s“ hat einen Beginn, der dem Ende gleicht.',
	'[[/feature/hours/error/overlap]]'		=> '%1$s von „%2$s“: „%3$s“ hat Zeiten, die sich überschneiden.',
	'[[/feature/hours/error/date]]'				=> '%1$s von „%2$s“: „%3$s“ ist kein Datum, das es gibt, geschrieben wie 2026-12-24.',
	'[[/feature/hours/error/order]]'			=> '%1$s von „%2$s“: der letzte Tag liegt vor dem ersten Tag.',
	'[[/feature/hours/error/unknown]]'		=> '%1$s von „%2$s“: „%3$s“ ist kein Standort.',

	// Die Beschriftungen des Elements-Panels für die Felder der beiden Typen: Wörter
	// der Verwaltung, die die Einheit auf die Blacklist setzt
	'[[/_admin/elements/field/locations/name]]'		=> 'Name',
	'[[/_admin/elements/field/locations/address]]'	=> 'Adresse (optional)',
	'[[/_admin/elements/field/locations/mon]]'		=> 'Montag (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/tue]]'		=> 'Dienstag (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/wed]]'		=> 'Mittwoch (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/thu]]'		=> 'Donnerstag (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/fri]]'		=> 'Freitag (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/sat]]'		=> 'Samstag (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/sun]]'		=> 'Sonntag (so: 9-12, 14-18 - leer: geschlossen)',
	'[[/_admin/elements/field/locations/note]]'		=> 'Hinweis (optional, etwa nach Vereinbarung)',
	'[[/_admin/elements/field/closures/location]]'	=> 'Standort (leer: jeder Standort)',
	'[[/_admin/elements/field/closures/from]]'		=> 'Erster Tag',
	'[[/_admin/elements/field/closures/until]]'		=> 'Letzter Tag (leer: nur der erste Tag)',
	'[[/_admin/elements/field/closures/times]]'		=> 'Zeiten an diesen Tagen (so: 10-14 - leer: geschlossen)',
	'[[/_admin/elements/field/closures/reason]]'	=> 'Grund (optional)',
];
