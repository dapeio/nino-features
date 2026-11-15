<?php
declare(strict_types=1);

// The panel's own words, one file per interface language (see Admin::text()):
// merged into the workbench's fills while the feature is active, never into the
// project's text files. The site's words are the other set, in
// features/Hello/install/text/
return [

	// The rail entry, named by Admin::nav() - and the name the head over the
	// panel's screen shows, which is why the screen draws no heading of its own
	'[[/_admin/hello/nav]]'			=> 'Hello World',

	'[[/_admin/hello/label/name]]'	=> 'Name',
	'[[/_admin/hello/label/save]]'	=> 'Save',

	// What the panel says of a failure the server gave no word for: the list,
	// which is the key Nino.adminUi.panel() takes for a failed load, and the save
	'[[/_admin/hello/error/load]]'	=> 'It could not be loaded.',
	'[[/_admin/hello/error/save]]'	=> 'It could not be saved.',

	// The sentence of the code hello_long, %s the longest name (Admin::apiSave())
	'[[/_admin/error/hello_long]]'	=> 'The name may have up to %s characters.',
];
