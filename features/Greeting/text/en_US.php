<?php
declare(strict_types=1);

// The panel's own words, one file per interface language (see Admin::text()):
// merged into the workbench's fills while the feature is active, never into the
// project's text files - the words of the card and of the type of its sentences
// among them, since they are the workbench's too
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/greeting/nav]]'	=> 'Greeting',

	// The panel: what it says of the card and of each of its three lines, the button, and what it says of a failure the server gave no word for
	'[[/_admin/greeting/intro]]'	=> 'The card the dashboard greets you with has three lines. Each can be switched off on its own; with all three off, the card is not drawn.',
	'[[/_admin/greeting/label/hello]]'	=> 'Greeting with the name',
	'[[/_admin/greeting/hint/hello]]'	=> 'The large line: the greeting for the time of day and the name the account carries under Users.',
	'[[/_admin/greeting/label/sentence]]'	=> 'Sentence of the day',
	'[[/_admin/greeting/hint/sentence]]'	=> 'A random sentence for the hour, the weekday or the occasion. The sentences are the element type Greetings and can be changed there.',
	'[[/_admin/greeting/label/facts]]'	=> 'Facts about the last visit',
	'[[/_admin/greeting/hint/facts]]'	=> 'The small line: when you were last here, what the log has recorded since and how old the last backup is.',
	'[[/_admin/greeting/label/save]]'	=> 'Save',
	'[[/_admin/greeting/error/load]]'	=> 'It could not be loaded.',
	'[[/_admin/greeting/error/save]]'	=> 'It could not be saved.',
	'[[/_admin/greeting/hint/head]]'	=> 'This Nino has no place at the head of its Dashboard for the card of a feature: the Dashboard draws its own greeting, and the three switches are the ones that greeting reads. Update Nino to have this feature draw the card.',

	// The words of the card itself: the formula of the time of day, and the facts about the last visit
	'[[/_admin/greeting/hello/early]]'	=> 'Good morning',
	'[[/_admin/greeting/hello/morning]]'	=> 'Good morning',
	'[[/_admin/greeting/hello/noon]]'	=> 'Hello',
	'[[/_admin/greeting/hello/afternoon]]'	=> 'Good afternoon',
	'[[/_admin/greeting/hello/evening]]'	=> 'Good evening',
	'[[/_admin/greeting/hello/night]]'	=> 'Hello',
	'[[/_admin/greeting/fact/first]]'	=> 'Your first visit – welcome',
	'[[/_admin/greeting/fact/today]]'	=> 'You were last here earlier today',
	'[[/_admin/greeting/fact/yesterday]]'	=> 'You were last here yesterday',
	'[[/_admin/greeting/fact/days]]'	=> 'You were last here %s days ago',
	'[[/_admin/greeting/fact/activity]]'	=> 'Since your last visit there were %s entries in the log',
	'[[/_admin/greeting/fact/activity-one]]'	=> 'Since your last visit there was one entry in the log',
	'[[/_admin/greeting/fact/backup]]'	=> 'The last backup is %s days old',

	// The Elements panel's own words for the type of the sentences: its fields, the words of the options and a line under the list
	'[[/_admin/elements/type/greetings/hint]]'	=> 'The sentences the dashboard picks its greeting from. Hour, weekday and occasion say when a sentence fits; "Any" fits always. %s stands for the name of the account, and a sentence with %s is shown only to an account that has a name.',
	'[[/_admin/elements/field/greetings/text]]'	=> 'Sentence',
	'[[/_admin/elements/field/greetings/phase]]'	=> 'Time of day',
	'[[/_admin/elements/field/greetings/day]]'	=> 'Weekday',
	'[[/_admin/elements/field/greetings/occasion]]'	=> 'Occasion',
	'[[/_admin/elements/field/greetings/hidden]]'	=> 'Hidden',
	'[[/_admin/elements/option/greetings/phase/any]]'	=> 'Any',
	'[[/_admin/elements/option/greetings/phase/early]]'	=> 'Early (5–7)',
	'[[/_admin/elements/option/greetings/phase/morning]]'	=> 'Morning (8–10)',
	'[[/_admin/elements/option/greetings/phase/noon]]'	=> 'Midday (11–13)',
	'[[/_admin/elements/option/greetings/phase/afternoon]]'	=> 'Afternoon (14–16)',
	'[[/_admin/elements/option/greetings/phase/evening]]'	=> 'Evening (17–21)',
	'[[/_admin/elements/option/greetings/phase/night]]'	=> 'Night (22–4)',
	'[[/_admin/elements/option/greetings/day/any]]'	=> 'Any',
	'[[/_admin/elements/option/greetings/day/monday]]'	=> 'Monday',
	'[[/_admin/elements/option/greetings/day/friday]]'	=> 'Friday',
	'[[/_admin/elements/option/greetings/day/weekend]]'	=> 'Weekend',
	'[[/_admin/elements/option/greetings/occasion/none]]'	=> 'None',
	'[[/_admin/elements/option/greetings/occasion/christmas]]'	=> 'Holidays (24–26 December)',
	'[[/_admin/elements/option/greetings/occasion/newyear]]'	=> 'New Year (31 December, 1 January)',
];
