<?php
declare(strict_types=1);

// The words an unreleased embed carries - merged into the project's
// text/en_US.php once, at activation (add-only: a key the project already
// has stays); editors keep them current in the Text panel from then on.
// See README.md.
return [

	// Under the play mark, where the shortcode was written without a title
	// of its own. A project that writes title="Anfahrt" says something this
	// cannot, so this is the fallback rather than the rule
	'[[/embed/load]]'		=> 'Load external content',

	// The sentence that decides whether somebody presses. The host is put
	// after it by the shortcode, so this one ends where a name follows
	'[[/embed/note]]'		=> 'Pressing this loads content from',

	// The <noscript> way out, for a visitor who cannot be given a frame at
	// all. Same shape: a name follows
	'[[/embed/open]]'		=> 'Open in a new tab at',

	// What the frame itself is called once it is there, where the shortcode
	// named nothing. A frame with no name is announced as "iframe"
	'[[/embed/frame]]'	=> 'External content',
];
