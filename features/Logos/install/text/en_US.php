<?php
// features/Logos/install/text/en_US.php - the Elements panel's labels for the
// fields of the type Logos, and the word of the pause button a moving band
// draws. Merged into the project's own text file without overwriting a key it
// already has. The labels are the workbench's words and the unit blacklists
// them; the word is the site's, kept in the Text panel from then on, and
// stands in an attribute as it is, so it must not contain a double quote.
return [
	'[[/_admin/elements/field/logo/name]]'			=> 'Name',
	'[[/_admin/elements/field/logo/image]]'			=> 'Logo',
	'[[/_admin/elements/field/logo/url]]'				=> 'Link (https://, http://, mailto: or a path of the website like /contact; empty: no link)',
	'[[/_admin/elements/field/logo/image-dark]]'	=> 'Logo for a dark ground (optional)',
	'[[/_admin/elements/field/logo/order]]'			=> 'Position (10, 20, … - empty or 0 goes last)',

	// What the button says that pauses the moving band; it stays the same word, and the state is aria-pressed
	'[[/feature/logos/action/pause]]'	=> 'Pause logos',
];
