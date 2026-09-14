<?php
declare(strict_types=1);

// The three words a pair carries - merged into the project's text/en_US.php
// once, at activation (add-only: a key the project already has stays);
// editors keep them current in the Text panel from then on. See README.md.
return [

	// What the two sides are called where the shortcode did not say. A
	// project that writes before-label="Rohbau" says something these cannot
	'[[/compare/before]]'	=> 'Before',
	'[[/compare/after]]'	=> 'After',

	// What the control is, for somebody who reaches it with the keyboard and
	// has never seen the two pictures
	'[[/compare/handle]]'	=> 'Move the divider between the two pictures',
];
