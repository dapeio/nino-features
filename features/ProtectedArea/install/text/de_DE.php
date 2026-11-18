<?php
declare(strict_types=1);

// The words the site says - merged into the project's own text/de_DE.php on every activation, add-only: a key
// the project already has stays. From then on an editor keeps them current in the Text panel. The panel's own
// words are a different set, in features/ProtectedArea/text/
return [

	// The details of the sign-in page: the gate answers under the address /.protected, so this is what its title
	// reads and what the Builder lists it by
	'[[/_nino/webpage/.protected/name]]'	=> 'Geschützter Bereich',
	'[[/_nino/webpage/.protected/title]]'	=> 'Geschützter Bereich',
	'[[/_nino/webpage/.protected/description]]'	=> 'Dieser Bereich der Website ist durch ein Passwort geschützt.',

	'[[/template/page-protected/intro/title]]'	=> 'Geschützter Bereich',
	'[[/template/page-protected/intro/text]]'	=> 'Dieser Bereich der Website ist passwortgeschützt. Bitte gib das Passwort ein, um fortzufahren.',

	// The form (components/protected-form/) and the link (components/protected-logout/)
	'[[/feature/protected/form/password]]'	=> 'Passwort',
	'[[/feature/protected/form/submit]]'	=> 'Entsperren',
	'[[/feature/protected/error/wrong]]'	=> 'Falsches Passwort. Bitte versuche es erneut.',
	'[[/feature/protected/error/locked]]'	=> 'Zu viele falsche Versuche. Bitte versuche es in einer Stunde erneut.',
	'[[/feature/protected/logout/label]]'	=> 'Diesen Bereich wieder sperren',
];
