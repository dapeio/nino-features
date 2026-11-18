<?php
declare(strict_types=1);

// The words the site says - merged into the project's own text/en_US.php on every activation, add-only: a key
// the project already has stays. From then on an editor keeps them current in the Text panel. The panel's own
// words are a different set, in features/ProtectedArea/text/
return [

	// The details of the sign-in page: the gate answers under the address /.protected, so this is what its title
	// reads and what the Builder lists it by
	'[[/_nino/webpage/.protected/name]]'	=> 'Protected area',
	'[[/_nino/webpage/.protected/title]]'	=> 'Protected area',
	'[[/_nino/webpage/.protected/description]]'	=> 'This part of the site is protected by a password.',

	'[[/template/page-protected/intro/title]]'	=> 'Protected area',
	'[[/template/page-protected/intro/text]]'	=> 'This part of the site is password protected. Please enter the password to continue.',

	// The form (components/protected-form/) and the link (components/protected-logout/)
	'[[/feature/protected/form/password]]'	=> 'Password',
	'[[/feature/protected/form/submit]]'	=> 'Unlock',
	'[[/feature/protected/error/wrong]]'	=> 'Wrong password. Please try again.',
	'[[/feature/protected/error/locked]]'	=> 'Too many wrong attempts. Please try again in an hour.',
	'[[/feature/protected/logout/label]]'	=> 'Lock this area again',
];
