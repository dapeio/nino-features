<?php
declare(strict_types=1);

// The words of the opening hours - merged into the project's text/en_US.php on
// every activation (add-only: a key the project already has stays, one it
// lacks is added); editors keep them current in the Text panel from then on.
//
// %1$s, %2$s and %3$s in a word are filled by the feature in this order, and a
// language may put them where its sentence wants them. The names of the days
// are the English ones in lower case in every language, as the key of a
// month is. 'time/clock' says whether the hours of this language are counted to
// 24 ('24') or in two halves ('12'), 'week/first' on which day its week begins.
return [

	'[[/feature/hours/day-short/mon]]'	=> 'Mon',
	'[[/feature/hours/day-short/tue]]'	=> 'Tue',
	'[[/feature/hours/day-short/wed]]'	=> 'Wed',
	'[[/feature/hours/day-short/thu]]'	=> 'Thu',
	'[[/feature/hours/day-short/fri]]'	=> 'Fri',
	'[[/feature/hours/day-short/sat]]'	=> 'Sat',
	'[[/feature/hours/day-short/sun]]'	=> 'Sun',

	'[[/feature/hours/day-long/mon]]'		=> 'Monday',
	'[[/feature/hours/day-long/tue]]'		=> 'Tuesday',
	'[[/feature/hours/day-long/wed]]'		=> 'Wednesday',
	'[[/feature/hours/day-long/thu]]'		=> 'Thursday',
	'[[/feature/hours/day-long/fri]]'		=> 'Friday',
	'[[/feature/hours/day-long/sat]]'		=> 'Saturday',
	'[[/feature/hours/day-long/sun]]'		=> 'Sunday',

	'[[/feature/hours/word/closed]]'		=> 'Closed',
	'[[/feature/hours/word/deviation]]'		=> 'Deviation',
	'[[/feature/hours/word/today]]'			=> 'Today',

	'[[/feature/hours/time/clock]]'			=> '12',
	'[[/feature/hours/time/range]]'			=> '%1$s – %2$s',
	'[[/feature/hours/time/am]]'				=> 'am',
	'[[/feature/hours/time/midnight]]'		=> 'midnight',
	'[[/feature/hours/time/pm]]'				=> 'pm',
	'[[/feature/hours/date/range]]'			=> '%1$s to %2$s',
	'[[/feature/hours/week/first]]'			=> 'sun',

	'[[/feature/hours/field/location]]'	=> 'Location',
	'[[/feature/hours/field/from]]'			=> 'From',
	'[[/feature/hours/field/until]]'		=> 'Until',
	'[[/feature/hours/field/times]]'		=> 'Times',

	'[[/feature/hours/error/unreadable]]'	=> '%1$s of “%2$s” cannot be read: “%3$s”. Write the times like 9-12, 14-18.',
	'[[/feature/hours/error/many]]'		=> '%1$s of “%2$s”: “%3$s” has more than 8 times.',
	'[[/feature/hours/error/minutes]]'		=> '%1$s of “%2$s”: “%3$s” has minutes above 59.',
	'[[/feature/hours/error/hours]]'			=> '%1$s of “%2$s”: “%3$s” has an hour out of range, 0 to 24 and 24 only as an end.',
	'[[/feature/hours/error/equal]]'			=> '%1$s of “%2$s”: “%3$s” has a start and an end that are the same.',
	'[[/feature/hours/error/overlap]]'		=> '%1$s of “%2$s”: “%3$s” has times that overlap.',
	'[[/feature/hours/error/date]]'				=> '%1$s of “%2$s”: “%3$s” is not a date that exists, written like 2026-12-24.',
	'[[/feature/hours/error/order]]'			=> '%1$s of “%2$s”: the last day is before the first day.',
	'[[/feature/hours/error/unknown]]'		=> '%1$s of “%2$s”: “%3$s” is not a location.',

	// The Elements panel's labels for the fields of the two types: the workbench's
	// words, which the unit blacklists. A day is written like 9-12, 14-18
	'[[/_admin/elements/field/locations/name]]'		=> 'Name',
	'[[/_admin/elements/field/locations/address]]'	=> 'Address (optional)',
	'[[/_admin/elements/field/locations/mon]]'		=> 'Monday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/tue]]'		=> 'Tuesday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/wed]]'		=> 'Wednesday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/thu]]'		=> 'Thursday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/fri]]'		=> 'Friday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/sat]]'		=> 'Saturday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/sun]]'		=> 'Sunday (like 9-12, 14-18 - empty: closed)',
	'[[/_admin/elements/field/locations/note]]'		=> 'Note (optional, like by appointment)',
	'[[/_admin/elements/field/closures/location]]'	=> 'Location (empty: every location)',
	'[[/_admin/elements/field/closures/from]]'		=> 'First day',
	'[[/_admin/elements/field/closures/until]]'		=> 'Last day (empty: only the first day)',
	'[[/_admin/elements/field/closures/times]]'		=> 'Times on these days (like 10-14 - empty: closed)',
	'[[/_admin/elements/field/closures/reason]]'	=> 'Reason (optional)',
];
