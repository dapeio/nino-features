<?php
declare(strict_types=1);

// The words a counter carries - merged into the project's text/en_US.php
// once, at activation (add-only: a key the project already has stays);
// editors keep them current in the Text panel from then on. See README.md.
return [

	/*	Both forms of every unit, because "1 days" is what a counter says
		otherwise. The script is handed the two words on the part itself and
		only chooses between them - a static asset cannot read a fill	*/
	'[[/countdown/day]]'			=> 'day',
	'[[/countdown/days]]'			=> 'days',
	'[[/countdown/hour]]'			=> 'hour',
	'[[/countdown/hours]]'		=> 'hours',
	'[[/countdown/minute]]'		=> 'minute',
	'[[/countdown/minutes]]'	=> 'minutes',
	'[[/countdown/second]]'		=> 'second',
	'[[/countdown/seconds]]'	=> 'seconds',

	// What stands there once the moment has passed, where the shortcode did
	// not say. A project that writes done="Der Verkauf läuft" says something
	// this cannot
	'[[/countdown/done]]'			=> 'The time has come',
];
