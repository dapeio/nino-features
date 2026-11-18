<?php
declare(strict_types=1);

// The Protected area feature's own workbench strings, merged into the workbench's fills while the feature is
// active (see the panel's text()) - same keys and shape the workbench's own text/<locale>.php has. The site's
// words are the other set, in features/ProtectedArea/install/text/
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/nav/protected]]'							=> 'Protected area',

	'[[/_admin/protected/label/area]]'					=> 'Protected area',
	'[[/_admin/protected/hint/intro]]'					=> 'The paths that ask for the password, and the password itself. These are the settings of the Features panel too - a change made here is a change made there.',
	'[[/_admin/protected/label/paths]]'					=> 'Protected paths',
	'[[/_admin/protected/help/paths]]'					=> 'One path of the site per line, eg. /intern, or the path of the page in one language, eg. /members. The page and everything below it asks for the password at every address it has, in every language. Leave empty to protect nothing.',
	'[[/_admin/protected/label/newpw]]'					=> 'New password',
	'[[/_admin/protected/help/newpw]]'					=> 'A new password signs everybody out: whoever unlocked the area with the old one is asked again. At least %s characters, typed twice. Leave both fields empty to keep the password. It is never shown here.',
	'[[/_admin/protected/label/newpw2]]'				=> 'New password again',
	'[[/_admin/protected/msg/haspw]]'					=> 'A password is set.',
	'[[/_admin/protected/msg/nopw]]'					=> 'No password is set - nothing is protected yet, whatever paths are written.',
	'[[/_admin/protected/error/mismatch]]'				=> 'The two entries are not the same.',
	'[[/_admin/protected/msg/pwsaved]]'					=> 'Password changed. Everybody is signed out.',

	'[[/_admin/protected/label/signoutall]]'			=> 'Sign everybody out',
	'[[/_admin/protected/confirm/signout]]'				=> 'Everybody who has unlocked the protected area is asked for the password again.',
	'[[/_admin/protected/msg/signedout]]'				=> 'Everybody is signed out.',

	// What the panel says of a failure the server gave no word for: the screen, which is the key
	// Nino.adminUi.panel() takes for a failed load, and the save
	'[[/_admin/protected/error/fields]]'					=> 'Please check the fields that are marked.',
	'[[/_admin/protected/error/load]]'					=> 'It could not be loaded.',
	'[[/_admin/protected/error/save]]'					=> 'It could not be saved.',

	// The sentences of the codes the panel answers with (Admin::apiSave())
	'[[/_admin/error/protected_paths]]'					=> 'The paths have to be written as lines.',
	'[[/_admin/error/protected_path]]'					=> '%s cannot be protected: a path starts with a single slash and is no tool or endpoint of Nino.',
	'[[/_admin/error/protected_short]]'					=> 'The password needs at least %s characters.',
	'[[/_admin/error/protected_long]]'					=> 'The password may have up to %s characters.',
	'[[/_admin/error/protected_setting]]'				=> 'The setting was refused: %s',
];
