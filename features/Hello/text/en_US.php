<?php
declare(strict_types=1);

// The panel's own words, one file per interface language (see
// Admin::text()). These are the workbench's, not the site's: they are read
// straight out of this directory while the panel is drawn and are never
// merged into the project, which is why an operator cannot edit them and an
// editor never sees them. The site's words are the other set, in
// features/Hello/install/text/.
return [

	// The rail entry, named by Admin::nav()
	'[[/_admin/hello/nav]]'					=> 'Hello World',

	'[[/_admin/hello/title]]'				=> 'Hello World',
	/*	No shortcode in here, and that is the lesson: a text fill is rendered,
		so "[hello]" written into one is not the word "[hello]" on screen - it
		is the shortcode, expanded, wherever that fill is shown. Name it in
		prose, or escape the bracket	*/
	'[[/_admin/hello/hint]]'				=> 'Who the shortcode greets when it names nobody. The greeting itself is a setting of this feature - Features, the row\'s Settings.',

	'[[/_admin/hello/label/name]]'	=> 'Name',
	'[[/_admin/hello/label/save]]'	=> 'Save',

	'[[/_admin/hello/msg/saved]]'		=> 'Saved.',
	'[[/_admin/hello/error/long]]'	=> 'That is longer than a name - 60 characters at most.',
	'[[/_admin/hello/error/save]]'	=> 'It could not be saved.',
];
