<?php
declare(strict_types=1);

// The panel's own words, one file per interface language (see Admin::text()):
// merged into the workbench's fills while the feature is active, never into the
// project's text files - the words of the card and of the type of its sentences
// among them, since they are the workbench's too
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/greeting/nav]]'	=> 'Begrüßung',

	// The panel: the card as the Dashboard draws it, the sentence for a card with all three lines off, and what it says of a failure the server gave no word for
	'[[/_admin/greeting/empty]]'	=> 'Alle drei Zeilen der Karte sind ausgeschaltet, das Dashboard zeichnet also keine.',
	'[[/_admin/greeting/error/load]]'	=> 'Es konnte nicht geladen werden.',

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
