<?php
declare(strict_types=1);

// The words a counter carries - merged into the project's text/en_US.php on
// every activation (add-only: a key the project already has stays, one it
// lacks is added); editors keep them current in the Text panel from then on.
return [

	/*	Both forms of every unit, because "1 days" is what a counter says
		otherwise. The script is handed the two words on the part itself and
		only chooses between them - a static asset cannot read a fill	*/
	'[[/feature/countdown/unit-day/one]]'		=> 'day',
	'[[/feature/countdown/unit-day/many]]'		=> 'days',
	'[[/feature/countdown/unit-hour/one]]'		=> 'hour',
	'[[/feature/countdown/unit-hour/many]]'		=> 'hours',
	'[[/feature/countdown/unit-minute/one]]'	=> 'minute',
	'[[/feature/countdown/unit-minute/many]]'	=> 'minutes',
	'[[/feature/countdown/unit-second/one]]'	=> 'second',
	'[[/feature/countdown/unit-second/many]]'	=> 'seconds',

	/*	The names of the months and of the days, whole and short, for the
		letters F, M, l and D of a format="...": the date under the counter
		is written in the language of the page, not in PHP's English. The
		name in the key is the English one in lower case in every language;
		the value is the word of this one	*/
	'[[/feature/countdown/month/january]]'	=> 'January',
	'[[/feature/countdown/month/february]]'	=> 'February',
	'[[/feature/countdown/month/march]]'	=> 'March',
	'[[/feature/countdown/month/april]]'	=> 'April',
	'[[/feature/countdown/month/may]]'	=> 'May',
	'[[/feature/countdown/month/june]]'	=> 'June',
	'[[/feature/countdown/month/july]]'	=> 'July',
	'[[/feature/countdown/month/august]]'	=> 'August',
	'[[/feature/countdown/month/september]]'	=> 'September',
	'[[/feature/countdown/month/october]]'	=> 'October',
	'[[/feature/countdown/month/november]]'	=> 'November',
	'[[/feature/countdown/month/december]]'	=> 'December',

	'[[/feature/countdown/month-short/january]]'	=> 'Jan',
	'[[/feature/countdown/month-short/february]]'	=> 'Feb',
	'[[/feature/countdown/month-short/march]]'	=> 'Mar',
	'[[/feature/countdown/month-short/april]]'	=> 'Apr',
	'[[/feature/countdown/month-short/may]]'	=> 'May',
	'[[/feature/countdown/month-short/june]]'	=> 'Jun',
	'[[/feature/countdown/month-short/july]]'	=> 'Jul',
	'[[/feature/countdown/month-short/august]]'	=> 'Aug',
	'[[/feature/countdown/month-short/september]]'	=> 'Sep',
	'[[/feature/countdown/month-short/october]]'	=> 'Oct',
	'[[/feature/countdown/month-short/november]]'	=> 'Nov',
	'[[/feature/countdown/month-short/december]]'	=> 'Dec',

	'[[/feature/countdown/day/monday]]'	=> 'Monday',
	'[[/feature/countdown/day/tuesday]]'	=> 'Tuesday',
	'[[/feature/countdown/day/wednesday]]'	=> 'Wednesday',
	'[[/feature/countdown/day/thursday]]'	=> 'Thursday',
	'[[/feature/countdown/day/friday]]'	=> 'Friday',
	'[[/feature/countdown/day/saturday]]'	=> 'Saturday',
	'[[/feature/countdown/day/sunday]]'	=> 'Sunday',

	'[[/feature/countdown/day-short/monday]]'	=> 'Mon',
	'[[/feature/countdown/day-short/tuesday]]'	=> 'Tue',
	'[[/feature/countdown/day-short/wednesday]]'	=> 'Wed',
	'[[/feature/countdown/day-short/thursday]]'	=> 'Thu',
	'[[/feature/countdown/day-short/friday]]'	=> 'Fri',
	'[[/feature/countdown/day-short/saturday]]'	=> 'Sat',
	'[[/feature/countdown/day-short/sunday]]'	=> 'Sun',

	// What stands there once the moment has passed, where the shortcode did
	// not say. A project that writes done="Der Verkauf läuft" says something
	// this cannot
	'[[/feature/countdown/end/message]]'	=> 'The time has come',
];
