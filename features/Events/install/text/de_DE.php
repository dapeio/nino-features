<?php
declare(strict_types=1);

// The words of the components and of the feed, merged into the project's text/de_DE.php on every activation (add-only: a key
// the project already has stays, one that is missing is added); the editors keep them current in the Text panel from then on.
// The month and the day of a date are no words of the feature's: they come from the locale (see Events::pattern())
return [

	'[[/feature/events/when/today]]'		=> 'Heute',
	'[[/feature/events/when/tomorrow]]'	=> 'Morgen',
	'[[/feature/events/when/until]]'		=> 'bis',

	'[[/feature/events/ics/label]]'			=> 'Zum Kalender hinzufügen',
	'[[/feature/events/feed/label]]'		=> 'Kalender abonnieren',
	'[[/feature/events/feed/member]]'		=> 'Deinen Kalender abonnieren',

	'[[/feature/events/feed/name]]'			=> 'Termine',

	'[[/feature/events/organizer/at]]'		=> 'Termin bei',

	'[[/feature/events/state/upcoming]]'	=> 'Es stehen keine Termine an.',
	'[[/feature/events/state/past]]'			=> 'Es hat noch nichts stattgefunden.',
	'[[/feature/events/state/all]]'			=> 'Es gibt keine Termine.',
];
