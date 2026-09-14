<?php
declare(strict_types=1);

// The three words the button says - merged into the project's text/en_US.php
// once, at activation (add-only: a key the project already has stays);
// editors keep them current in the Text panel from then on. See README.md.
return [

	// On the button, and half of its accessible name where the shortcode said
	// what is being copied: "Copy: IBAN"
	'[[/copy/do]]'			=> 'Copy',

	// For the moment after. The word changes, not only the colour - a colour
	// says it to whoever can see it and to nobody else
	'[[/copy/done]]'		=> 'Copied',

	// Where neither way of copying worked. It names what to do instead,
	// because the text is selected and that is now the reader's move
	'[[/copy/failed]]'	=> 'Press Ctrl+C',
];
