<?php
declare(strict_types=1);

// The switch's four words - merged into the project's text/en_US.php once,
// at activation (add-only: a key the project already has stays); editors
// keep them current in the Text panel from then on. See README.md.
return [

	// What the switch is as a whole, for a screen reader - the three buttons
	// inside it do not say on their own what they belong to
	'[[/modeswitch/label]]'		=> 'Appearance',

	'[[/modeswitch/light]]'		=> 'Light',
	// Not "Auto": what happens here is not the site deciding something, it
	// is the site leaving it to the device
	'[[/modeswitch/system]]'	=> 'System',
	'[[/modeswitch/dark]]'		=> 'Dark',
];
