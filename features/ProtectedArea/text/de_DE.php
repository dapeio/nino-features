<?php
declare(strict_types=1);

// The Protected area feature's own workbench strings, merged into the workbench's fills while the feature is
// active (see the panel's text()) - same keys and shape the workbench's own text/<locale>.php has. The site's
// words are the other set, in features/ProtectedArea/install/text/
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/nav/protected]]'							=> 'Geschützter Bereich',

	'[[/_admin/protected/label/area]]'					=> 'Geschützter Bereich',
	'[[/_admin/protected/hint/intro]]'					=> 'Die Pfade, die nach dem Passwort fragen, und das Passwort selbst. Das sind auch die Einstellungen im Panel Features - was Du hier änderst, ist dort geändert.',
	'[[/_admin/protected/label/paths]]'					=> 'Geschützte Pfade',
	'[[/_admin/protected/help/paths]]'					=> 'Ein Pfad der Website pro Zeile, zB /intern, oder der Pfad der Seite in einer Sprache, zB /members. Die Seite und alles darunter fragt an jeder Adresse nach dem Passwort, die sie hat, also in jeder Sprache. Leer lassen, um nichts zu schützen.',
	'[[/_admin/protected/label/newpw]]'					=> 'Neues Passwort',
	'[[/_admin/protected/help/newpw]]'					=> 'Ein neues Passwort meldet alle ab: Wer den Bereich mit dem alten entsperrt hat, wird erneut gefragt. Mindestens %s Zeichen, zweimal getippt. Beide Felder leer lassen, um das Passwort zu behalten. Es wird hier nie angezeigt.',
	'[[/_admin/protected/label/newpw2]]'				=> 'Neues Passwort wiederholen',
	'[[/_admin/protected/msg/haspw]]'					=> 'Ein Passwort ist gesetzt.',
	'[[/_admin/protected/msg/nopw]]'					=> 'Kein Passwort gesetzt - noch ist nichts geschützt, welche Pfade auch dastehen.',
	'[[/_admin/protected/error/mismatch]]'				=> 'Die beiden Eingaben sind nicht gleich.',
	'[[/_admin/protected/msg/pwsaved]]'					=> 'Passwort geändert. Alle sind abgemeldet.',

	'[[/_admin/protected/label/signoutall]]'			=> 'Alle abmelden',
	'[[/_admin/protected/confirm/signout]]'				=> 'Alle, die den geschützten Bereich entsperrt haben, werden erneut nach dem Passwort gefragt.',
	'[[/_admin/protected/msg/signedout]]'				=> 'Alle sind abgemeldet.',

	// What the panel says of a failure the server gave no word for: the screen, which is the key
	// Nino.adminUi.panel() takes for a failed load, and the save
	'[[/_admin/protected/error/fields]]'					=> 'Bitte prüfe die markierten Felder.',
	'[[/_admin/protected/error/load]]'					=> 'Es konnte nicht geladen werden.',
	'[[/_admin/protected/error/save]]'					=> 'Es konnte nicht gespeichert werden.',

	// The sentences of the codes the panel answers with (Admin::apiSave())
	'[[/_admin/error/protected_paths]]'					=> 'Die Pfade müssen als Zeilen geschrieben sein.',
	'[[/_admin/error/protected_path]]'					=> '%s lässt sich nicht schützen: Ein Pfad beginnt mit einem einzelnen Schrägstrich und ist kein Werkzeug oder Endpunkt von Nino.',
	'[[/_admin/error/protected_short]]'					=> 'Das Passwort braucht mindestens %s Zeichen.',
	'[[/_admin/error/protected_long]]'					=> 'Das Passwort darf bis zu %s Zeichen haben.',
	'[[/_admin/error/protected_setting]]'				=> 'Die Einstellung wurde abgelehnt: %s',
];
