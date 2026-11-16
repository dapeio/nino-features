<?php
declare(strict_types=1);

// The words of the API of the workbench, one file per interface language (see Admin::text()): the rail entry and the sentence
// of each failure a person can cause, %s where a param goes
return [

	'[[/_admin/themes/nav]]'						=> 'Themes',
	'[[/_admin/themes/panel/active]]'			=> 'Die Website zeigt das Theme',
	'[[/_admin/themes/panel/none]]'				=> 'keines - die Standard-Teile',
	'[[/_admin/themes/panel/settings]]'		=> 'Die Einstellungen der Themes kommen mit der Ansicht dieses Panels.',

	'[[/_admin/error/themes_unknown]]'			=> 'Es gibt kein Theme "%s".',
	'[[/_admin/error/themes_mix]]'					=> 'Die Mischung stimmt nicht: %s',
	'[[/_admin/error/themes_brand]]'				=> 'Der Wert "%s" der Marke stimmt nicht.',
	'[[/_admin/error/themes_not_own]]'			=> 'Ein Theme aus einem Paket wird nicht verändert. Speichere es stattdessen als eigenes Theme.',
	'[[/_admin/error/themes_name]]'					=> 'Der Name braucht mindestens einen Buchstaben oder eine Ziffer.',
	'[[/_admin/error/themes_name_taken]]'		=> 'Ein eigenes Theme namens "%s" gibt es schon.',
	'[[/_admin/error/themes_limit]]'				=> 'Es kann höchstens %s eigene Themes geben.',
	'[[/_admin/error/themes_active]]'				=> 'Das Theme "%s" ist das der Website und wird nicht gelöscht.',
	'[[/_admin/error/themes_nothing]]'			=> 'Es gibt noch keinen letzten Stand.',
	'[[/_admin/error/themes_foreign]]'			=> 'Das Stylesheet des Projekts stammt nicht von diesem Feature. Ersetze es nur, wenn der letzte Stand es behalten darf.',
	'[[/_admin/error/themes_busy]]'					=> 'Die Themes werden gerade von einer anderen Anfrage geändert. Versuche es gleich noch einmal.',
];
