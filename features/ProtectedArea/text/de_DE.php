<?php
// Die eigenen Workbench-Texte des Features Geschützter Bereich, in seine
// Fills gemischt, solange das Feature aktiv ist (siehe text() des Panels) -
// dieselben Schlüssel und dieselbe Form wie text/<locale>.php der Workbench
return [
	'[[/_admin/nav/protected]]'							=> 'Geschützter Bereich',
	'[[/_admin/protected/hint/intro]]'					=> 'Das Passwort und die Seiten dahinter. Es sind auch die Einstellungen im Panel Features - was Du hier änderst, änderst Du dort.',
	'[[/_admin/protected/label/password]]'				=> 'Passwort',
	'[[/_admin/protected/label/newpw]]'					=> 'Neues Passwort',
	'[[/_admin/protected/label/newpw2]]'				=> 'Neues Passwort noch einmal',
	'[[/_admin/protected/label/setpw]]'					=> 'Passwort setzen',
	'[[/_admin/protected/hint/password]]'				=> 'Ein neues Passwort meldet alle ab: Wer den Bereich mit dem alten entsperrt hat, wird erneut gefragt. Mindestens %d Zeichen, zweimal eingegeben. Hier wird es nie angezeigt.',
	'[[/_admin/protected/msg/haspw]]'					=> 'Ein Passwort ist gesetzt.',
	'[[/_admin/protected/msg/nopw]]'					=> 'Es ist kein Passwort gesetzt - nichts ist geschützt, egal was unten ausgewählt ist.',
	'[[/_admin/protected/error/short]]'					=> 'Das Passwort braucht mindestens %d Zeichen.',
	'[[/_admin/protected/error/mismatch]]'				=> 'Die beiden Eingaben sind nicht gleich.',
	'[[/_admin/protected/msg/pwsaved]]'					=> 'Passwort geändert. Alle sind abgemeldet.',
	'[[/_admin/protected/label/pages]]'					=> 'Geschützte Seiten',
	'[[/_admin/protected/hint/pages]]'					=> 'Eine angekreuzte Seite und alles darunter fragt nach dem Passwort. Eine Seite, die es in mehreren Sprachen gibt, wird in allen geschützt.',
	'[[/_admin/protected/label/savepages]]'				=> 'Seiten speichern',
	'[[/_admin/protected/empty/pages]]'					=> 'Die Website hat noch keine Seiten zur Auswahl.',
	'[[/_admin/protected/label/covered]]'				=> 'über einen weiteren Pfad',
	'[[/_admin/protected/label/extra]]'					=> 'Außerdem geschützt, im Panel Features gesetzt und unverändert behalten: %s',
	'[[/_admin/protected/confirm/none]]'				=> 'Es ist keine Seite ausgewählt. Der Schutz aller Seiten dieser Liste wird ausgeschaltet. Fortfahren?',
	'[[/_admin/protected/msg/pagessaved]]'				=> 'Seiten gespeichert.',
	'[[/_admin/protected/label/signout]]'				=> 'Abmelden',
	'[[/_admin/protected/hint/signout]]'				=> 'Sperrt den Bereich wieder für alle, die ihn entsperrt haben.',
	'[[/_admin/protected/label/signoutall]]'			=> 'Alle abmelden',
	'[[/_admin/protected/confirm/signout]]'				=> 'Alle, die den geschützten Bereich entsperrt haben, werden erneut nach dem Passwort gefragt. Fortfahren?',
	'[[/_admin/protected/msg/signedout]]'				=> 'Alle sind abgemeldet.',
];
