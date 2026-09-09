<?php
// Die Workbench-Texte des Forms-Features, die in seine Fills gemischt
// werden, solange das Feature aktiv ist (siehe text() des Panels) - gleiche
// Schlüssel und Form wie die eigene text/<locale>.php der Workbench. Ein %s
// füllt das Skript, oder das Panel in einer Meldung, die es selbst
// formuliert (siehe sein _say())
return [
	'[[/_admin/nav/forms]]'								=> 'Formulare',
	'[[/_admin/forms/label/forms]]'				=> 'Formulare',

	'[[/_admin/forms/hint/endpoint]]'			=> 'Das Kernel-Modul, das POST /.form beantwortet, ist nicht eingeschaltet - jedes Formular hier würde also ins Leere senden. Trage \\Nino\\Modules\\Form in der config.php wieder unter /nino/modules ein.',
	'[[/_admin/forms/hint/default]]'			=> 'Das ist das Kontaktformular, auf das Nino zurückfällt, solange ein Projekt keines definiert hat - so gezeigt, wie es wäre. Das erste Speichern schreibt /nino/form/forms in die config.php.',
	'[[/_admin/forms/hint/empty]]'				=> 'Kein Formular definiert. Lege eines an - sein eigener Bildschirm nennt dann den Shortcode, der es zeichnet.',
	'[[/_admin/forms/hint/shortcode]]'		=> 'Schreibe %s in ein Template oder einen Text, um dieses Formular auszugeben.',
	'[[/_admin/forms/hint/fields]]'				=> 'Der Name ist das, womit das Feld gesendet und exportiert wird. Das Label ist das, was ein Besucher liest - ein als Fill geschriebener Textschlüssel wird vor der Ausgabe aufgelöst, ein Label kann also alle Sprachen bedienen. Diese Namen sind vergeben: %s.',
	'[[/_admin/forms/hint/to]]'						=> 'Leer schickt an die Adresse aus dem Textfill /form/email/owner.',
	'[[/_admin/forms/hint/subject]]'			=> 'Leer nutzt den Textfill /form/subject/owner.',
	'[[/_admin/forms/hint/templates]]'		=> 'Die Mail-Templates, die dieses Formular rendert. Beide tragen einen Platzhalter für die ganze Einsendung als Tabelle - ein Formular mit eigenen Feldern braucht also kein eigenes Template.',
	'[[/_admin/forms/hint/retention]]'		=> 'Wie viele Monate an Einsendungen auf der Platte bleiben, 1 bis 60. Ein älterer Monat wird bei der nächsten Einsendung gelöscht - die Mail selbst bleibt davon unberührt.',
	'[[/_admin/forms/hint/store]]'				=> 'Aus heißt: Die Mail geht raus und nichts landet auf der Platte. Das Panel Anfragen bleibt dann absichtlich leer.',

	'[[/_admin/forms/label/new]]'					=> 'Neues Formular',
	'[[/_admin/forms/label/edit]]'				=> 'Bearbeiten',
	'[[/_admin/forms/label/entries]]'			=> '%s gespeichert',
	'[[/_admin/forms/label/delete]]'			=> 'Löschen',
	'[[/_admin/forms/label/form]]'				=> 'Formular',
	'[[/_admin/forms/label/name]]'				=> 'Name',
	'[[/_admin/forms/label/key]]'					=> 'Schlüssel',
	'[[/_admin/forms/label/to]]'					=> 'Senden an',
	'[[/_admin/forms/label/subject]]'			=> 'Betreff',
	'[[/_admin/forms/label/confirm]]'			=> 'Bestätigungsmail an den Besucher',
	'[[/_admin/forms/label/ownertpl]]'		=> 'Template der Betreiber-Mail',
	'[[/_admin/forms/label/usertpl]]'			=> 'Template der Bestätigungsmail',
	'[[/_admin/forms/label/fields]]'			=> 'Felder',
	'[[/_admin/forms/label/fieldname]]'		=> 'Name',
	'[[/_admin/forms/label/fieldlabel]]'	=> 'Label',
	'[[/_admin/forms/label/fieldtype]]'		=> 'Typ',
	'[[/_admin/forms/label/required]]'		=> 'Pflichtfeld',
	'[[/_admin/forms/label/options]]'			=> 'Optionen, eine pro Zeile',
	'[[/_admin/forms/label/addfield]]'		=> 'Feld hinzufügen',
	'[[/_admin/forms/label/removefield]]'	=> 'Entfernen',
	'[[/_admin/forms/label/submissions-settings]]'	=> 'Anfragen',
	'[[/_admin/forms/label/retention]]'		=> 'Aufbewahren (Monate)',
	'[[/_admin/forms/label/store]]'				=> 'Einsendungen speichern',

	'[[/_admin/forms/confirm/delete]]'		=> 'Dieses Formular löschen? Seine Einsendungen bleiben, und das Panel Anfragen zeigt sie weiterhin.',

	'[[/_admin/forms/error/invalid]]'			=> 'Das Formular braucht einen eigenen Schlüssel und mindestens ein Feld.',
	'[[/_admin/forms/error/duplicate]]'		=> 'Ein anderes Formular hat diesen Schlüssel bereits.',
	'[[/_admin/forms/error/save]]'				=> 'Die Formulare konnten nicht geschrieben werden.',
	'[[/_admin/forms/error/key]]'					=> 'Kein Formular hat diesen Schlüssel.',
	'[[/_admin/forms/error/last]]'				=> 'Das ist das letzte Formular. Ein Projekt ohne eines fällt auf das eingebaute Kontaktformular zurück; wer gar keines will, schaltet das Form-Modul unter /nino/modules ab.',
	'[[/_admin/forms/error/retention]]'		=> 'Einsendungen werden zwischen 1 und 60 Monaten aufbewahrt.',
];
