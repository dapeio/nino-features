<?php
declare(strict_types=1);

// The words a counter carries - merged into the project's text/en_US.php
// once, at activation (add-only: a key the project already has stays);
// editors keep them current in the Text panel from then on.
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

	// What stands there once the moment has passed, where the shortcode did
	// not say. A project that writes done="Der Verkauf läuft" says something
	// this cannot
	'[[/feature/countdown/end/message]]'	=> 'The time has come',
];
