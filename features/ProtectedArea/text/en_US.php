<?php
// The Protected area feature's own workbench strings, merged into its fills
// while the feature is active (see the panel's text()) - same keys and shape
// the workbench's own text/<locale>.php has
return [
	'[[/_admin/nav/protected]]'							=> 'Protected area',
	'[[/_admin/protected/hint/intro]]'					=> 'The password and the pages behind it. These are the settings of the Features panel too - a change made here is a change made there.',
	'[[/_admin/protected/label/password]]'				=> 'Password',
	'[[/_admin/protected/label/newpw]]'					=> 'New password',
	'[[/_admin/protected/label/newpw2]]'				=> 'New password again',
	'[[/_admin/protected/label/setpw]]'					=> 'Set password',
	'[[/_admin/protected/hint/password]]'				=> 'A new password signs everybody out: whoever unlocked the area with the old one is asked again. At least %d characters, typed twice. It is never shown here.',
	'[[/_admin/protected/msg/haspw]]'					=> 'A password is set.',
	'[[/_admin/protected/msg/nopw]]'					=> 'No password is set - nothing is protected yet, whatever is chosen below.',
	'[[/_admin/protected/error/short]]'					=> 'The password needs at least %d characters.',
	'[[/_admin/protected/error/mismatch]]'				=> 'The two entries are not the same.',
	'[[/_admin/protected/msg/pwsaved]]'					=> 'Password changed. Everybody is signed out.',
	'[[/_admin/protected/label/pages]]'					=> 'Protected pages',
	'[[/_admin/protected/hint/pages]]'					=> 'A page you tick and everything below it asks for the password. A page that exists in several languages is protected in all of them.',
	'[[/_admin/protected/label/savepages]]'				=> 'Save pages',
	'[[/_admin/protected/empty/pages]]'					=> 'The site has no pages to choose from yet.',
	'[[/_admin/protected/label/covered]]'				=> 'through a wider path',
	'[[/_admin/protected/label/extra]]'					=> 'Also protected, set in the Features panel and kept as they are: %s',
	'[[/_admin/protected/confirm/none]]'				=> 'No page is chosen. The protection of every page in this list is switched off. Continue?',
	'[[/_admin/protected/msg/pagessaved]]'				=> 'Pages saved.',
	'[[/_admin/protected/label/signout]]'				=> 'Sign out',
	'[[/_admin/protected/hint/signout]]'				=> 'Locks the area again for everybody who has unlocked it.',
	'[[/_admin/protected/label/signoutall]]'			=> 'Sign everybody out',
	'[[/_admin/protected/confirm/signout]]'				=> 'Everybody who has unlocked the protected area is asked for the password again. Continue?',
	'[[/_admin/protected/msg/signedout]]'				=> 'Everybody is signed out.',
];
