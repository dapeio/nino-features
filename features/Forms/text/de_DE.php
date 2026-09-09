<?php
// Die eigenen Workbench-Texte des Forms-Features, in seine Fills gemischt,
// solange das Feature aktiv ist (siehe text() des Panels) - dieselben
// Schlüssel und dieselbe Form wie text/<locale>.php der Workbench. Ein %s
// füllt das Skript, oder das Panel in einer Meldung, die es selbst
// formuliert (siehe sein _say())
return [
	'[[/_admin/nav/forms]]'								=> 'Formulare',
	'[[/_admin/forms/label/submissions]]'	=> 'Einsendungen',

	'[[/_admin/forms/hint/blocked]]'			=> 'Ninos eigenes Kontaktformular ist in /nino/modules noch eingeschaltet, deshalb tut dieses Feature nichts: Beide beantworten POST /.form, und zwei Handler an einem Endpunkt heißen zwei Mails und zwei Einträge für eine Einsendung. Nimm \\Nino\\Modules\\Form in der config.php aus /nino/modules heraus.',
	'[[/_admin/forms/hint/default]]'			=> 'Das ist das Formular, an das Ninos Kontaktseite immer schon gesendet hat, gezeigt wie es wäre. Gespeichert ist noch nichts - einmal speichern legt die Definitionsdatei an.',
	'[[/_admin/forms/hint/empty]]'				=> 'Es ist kein Formular angelegt. Lege eines an - sein eigener Bildschirm nennt dann den Shortcode, der es ausgibt.',
	'[[/_admin/forms/hint/entries-empty]]'	=> 'Es wurde noch nichts eingesendet.',
	'[[/_admin/forms/hint/nomatch]]'			=> 'Keine Einsendung passt.',
	'[[/_admin/forms/hint/shortcode]]'		=> 'Setze %s in ein Template oder einen Text, um dieses Formular auszugeben.',
	'[[/_admin/forms/hint/fields]]'				=> 'Der Name ist das, womit das Feld gesendet und exportiert wird. Die Bezeichnung ist das, was eine Besucherin liest - ein als Fill geschriebener Textschlüssel wird vorher aufgelöst, so trägt eine Bezeichnung jede Sprache.',
	'[[/_admin/forms/hint/to]]'						=> 'Leer sendet an die Adresse im Textfill /form/email/owner.',
	'[[/_admin/forms/hint/subject]]'			=> 'Leer verwendet den Textfill /form/subject/owner.',
	'[[/_admin/forms/hint/templates]]'		=> 'Die Mail-Templates, die dieses Formular rendert. Beide tragen einen Platzhalter für die ganze Einsendung als Tabelle, ein Formular mit eigenen Feldern braucht also kein eigenes Template.',

	'[[/_admin/forms/label/new]]'					=> 'Neues Formular',
	'[[/_admin/forms/label/edit]]'				=> 'Bearbeiten',
	'[[/_admin/forms/label/entries]]'			=> 'Einsendungen (%s)',
	'[[/_admin/forms/label/delete]]'			=> 'Löschen',
	'[[/_admin/forms/label/form]]'				=> 'Formular',
	'[[/_admin/forms/label/name]]'				=> 'Name',
	'[[/_admin/forms/label/key]]'					=> 'Schlüssel',
	'[[/_admin/forms/label/to]]'					=> 'Senden an',
	'[[/_admin/forms/label/subject]]'			=> 'Betreff',
	'[[/_admin/forms/label/confirm]]'			=> 'Bestätigungsmail an die Besucherin',
	'[[/_admin/forms/label/ownertpl]]'		=> 'Template der Betreibermail',
	'[[/_admin/forms/label/usertpl]]'			=> 'Template der Bestätigungsmail',
	'[[/_admin/forms/label/fields]]'			=> 'Felder',
	'[[/_admin/forms/label/fieldname]]'		=> 'Name',
	'[[/_admin/forms/label/fieldlabel]]'	=> 'Bezeichnung',
	'[[/_admin/forms/label/fieldtype]]'		=> 'Typ',
	'[[/_admin/forms/label/required]]'		=> 'Pflichtfeld',
	'[[/_admin/forms/label/options]]'			=> 'Optionen, eine pro Zeile',
	'[[/_admin/forms/label/addfield]]'		=> 'Feld hinzufügen',
	'[[/_admin/forms/label/removefield]]'	=> 'Entfernen',
	'[[/_admin/forms/label/date]]'				=> 'Eingegangen',
	'[[/_admin/forms/label/search]]'			=> 'Einsendungen durchsuchen',
	'[[/_admin/forms/label/export]]'			=> 'Als CSV exportieren',
	'[[/_admin/forms/label/filename]]'		=> 'einsendungen.csv',
	'[[/_admin/forms/label/count]]'				=> '%s Einsendungen gespeichert',
	'[[/_admin/forms/label/count-one]]'		=> '1 Einsendung gespeichert',

	'[[/_admin/forms/confirm/delete]]'		=> 'Dieses Formular löschen? Seine Einsendungen bleiben, bis die Aufbewahrungsfrist sie entfernt.',
	'[[/_admin/forms/confirm/entry]]'			=> 'Diese Einsendung löschen? Die verschickte Mail bleibt davon unberührt.',

	'[[/_admin/forms/error/invalid]]'			=> 'Das Formular braucht einen eigenen Schlüssel und mindestens ein Feld.',
	'[[/_admin/forms/error/duplicate]]'		=> 'Ein anderes Formular hat diesen Schlüssel bereits.',
	'[[/_admin/forms/error/save]]'				=> 'Die Formulare konnten nicht geschrieben werden.',
	'[[/_admin/forms/error/key]]'					=> 'Kein Formular hat diesen Schlüssel.',
	'[[/_admin/forms/error/entry]]'				=> 'Diese Einsendung ist nicht mehr gespeichert.',
];
