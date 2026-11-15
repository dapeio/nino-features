<?php
declare(strict_types=1);

// Die eigenen Worte des Panels, eine Datei je Oberflächensprache (siehe
// Admin::text()): in die Fills der Workbench gemischt, solange das Feature
// aktiv ist, nie in die Textdateien des Projekts. Die Worte der Seite sind der
// andere Satz, in features/Hello/install/text/
return [

	// Der Eintrag in der Navigation, benannt von Admin::nav() - und der Name,
	// den der Kopf über dem Panel zeigt; deshalb zeichnet der Schirm keine
	// eigene Überschrift
	'[[/_admin/hello/nav]]'			=> 'Hallo Welt',

	'[[/_admin/hello/label/name]]'	=> 'Name',
	'[[/_admin/hello/label/save]]'	=> 'Speichern',

	// Was das Panel zu einem Fehler sagt, für den der Server kein Wort hatte: die
	// Liste, der Schlüssel, den Nino.adminUi.panel() für ein Laden nimmt, das
	// scheitert, und das Speichern
	'[[/_admin/hello/error/load]]'	=> 'Es konnte nicht geladen werden.',
	'[[/_admin/hello/error/save]]'	=> 'Es konnte nicht gespeichert werden.',

	// Der Satz zum Code hello_long, %s der längste Name (Admin::apiSave())
	'[[/_admin/error/hello_long]]'	=> 'Der Name darf bis zu %s Zeichen haben.',
];
