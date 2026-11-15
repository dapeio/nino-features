<?php
declare(strict_types=1);

// The panel's own words, one file per interface language (see Admin::text()):
// merged into the workbench's fills while the feature is active, never into the
// project's text files - the words of the card and of the type of its sentences
// among them, since they are the workbench's too
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/greeting/nav]]'	=> 'Begrüßung',

	// The panel: what it says of the card and of each of its three lines, the button, and what it says of a failure the server gave no word for
	'[[/_admin/greeting/intro]]'	=> 'Die Karte, mit der das Dashboard Dich begrüßt, hat drei Zeilen. Jede lässt sich einzeln abschalten; sind alle drei aus, erscheint die Karte nicht.',
	'[[/_admin/greeting/label/hello]]'	=> 'Gruß mit Namen',
	'[[/_admin/greeting/hint/hello]]'	=> 'Die große Zeile: die Grußformel zur Tageszeit und der Name, den das Konto unter Nutzer trägt.',
	'[[/_admin/greeting/label/sentence]]'	=> 'Satz zum Tag',
	'[[/_admin/greeting/hint/sentence]]'	=> 'Ein zufälliger Satz zur Tageszeit, zum Wochentag oder zum Anlass. Die Sätze sind der Elementtyp Begrüßungen und lassen sich dort ändern.',
	'[[/_admin/greeting/label/facts]]'	=> 'Angaben zum letzten Besuch',
	'[[/_admin/greeting/hint/facts]]'	=> 'Die kleine Zeile: wann Du zuletzt hier warst, was seitdem im Protokoll steht und wie alt das letzte Backup ist.',
	'[[/_admin/greeting/label/save]]'	=> 'Speichern',
	'[[/_admin/greeting/error/load]]'	=> 'Es konnte nicht geladen werden.',
	'[[/_admin/greeting/error/save]]'	=> 'Es konnte nicht gespeichert werden.',
	'[[/_admin/greeting/hint/head]]'	=> 'Dieses Nino hat am Kopf seines Dashboards keinen Platz für die Karte eines Features: Das Dashboard zeichnet seine eigene Begrüßung, und die drei Schalter sind die, die diese Begrüßung liest. Aktualisiere Nino, damit dieses Feature die Karte zeichnet.',

	// The words of the card itself: the formula of the time of day, and the facts about the last visit
	'[[/_admin/greeting/hello/early]]'	=> 'Guten Morgen',
	'[[/_admin/greeting/hello/morning]]'	=> 'Guten Morgen',
	'[[/_admin/greeting/hello/noon]]'	=> 'Guten Tag',
	'[[/_admin/greeting/hello/afternoon]]'	=> 'Guten Tag',
	'[[/_admin/greeting/hello/evening]]'	=> 'Guten Abend',
	'[[/_admin/greeting/hello/night]]'	=> 'Hallo',
	'[[/_admin/greeting/fact/first]]'	=> 'Dein erster Besuch – willkommen',
	'[[/_admin/greeting/fact/today]]'	=> 'Zuletzt warst Du heute schon hier',
	'[[/_admin/greeting/fact/yesterday]]'	=> 'Zuletzt warst Du gestern hier',
	'[[/_admin/greeting/fact/days]]'	=> 'Zuletzt warst Du vor %s Tagen hier',
	'[[/_admin/greeting/fact/activity]]'	=> 'Seit Deinem letzten Besuch gab es %s Einträge im Protokoll',
	'[[/_admin/greeting/fact/activity-one]]'	=> 'Seit Deinem letzten Besuch gab es einen Eintrag im Protokoll',
	'[[/_admin/greeting/fact/backup]]'	=> 'Das letzte Backup ist %s Tage her',

	// The Elements panel's own words for the type of the sentences: its fields, the words of the options and a line under the list
	'[[/_admin/elements/type/greetings/hint]]'	=> 'Die Sätze, aus denen das Dashboard seine Begrüßung wählt. Tageszeit, Wochentag und Anlass sagen, wann ein Satz passt, „Beliebig“ passt immer. %s steht für den Namen des Kontos; ein Satz mit %s erscheint nur bei einem Konto mit Namen.',
	'[[/_admin/elements/field/greetings/text]]'	=> 'Satz',
	'[[/_admin/elements/field/greetings/phase]]'	=> 'Tageszeit',
	'[[/_admin/elements/field/greetings/day]]'	=> 'Wochentag',
	'[[/_admin/elements/field/greetings/occasion]]'	=> 'Anlass',
	'[[/_admin/elements/field/greetings/hidden]]'	=> 'Ausgeblendet',
	'[[/_admin/elements/option/greetings/phase/any]]'	=> 'Beliebig',
	'[[/_admin/elements/option/greetings/phase/early]]'	=> 'Früh (5–7 Uhr)',
	'[[/_admin/elements/option/greetings/phase/morning]]'	=> 'Morgen (8–10 Uhr)',
	'[[/_admin/elements/option/greetings/phase/noon]]'	=> 'Mittag (11–13 Uhr)',
	'[[/_admin/elements/option/greetings/phase/afternoon]]'	=> 'Nachmittag (14–16 Uhr)',
	'[[/_admin/elements/option/greetings/phase/evening]]'	=> 'Abend (17–21 Uhr)',
	'[[/_admin/elements/option/greetings/phase/night]]'	=> 'Nacht (22–4 Uhr)',
	'[[/_admin/elements/option/greetings/day/any]]'	=> 'Beliebig',
	'[[/_admin/elements/option/greetings/day/monday]]'	=> 'Montag',
	'[[/_admin/elements/option/greetings/day/friday]]'	=> 'Freitag',
	'[[/_admin/elements/option/greetings/day/weekend]]'	=> 'Wochenende',
	'[[/_admin/elements/option/greetings/occasion/none]]'	=> 'Keiner',
	'[[/_admin/elements/option/greetings/occasion/christmas]]'	=> 'Feiertage (24.–26.12.)',
	'[[/_admin/elements/option/greetings/occasion/newyear]]'	=> 'Jahreswechsel (31.12., 1.1.)',
];
