<?php
// Die Workbench-Texte des Gallery-Features, die in seine Fills gemischt
// werden, solange das Feature aktiv ist (siehe text() des Panels) - gleiche
// Schlüssel und Form wie die eigene text/<locale>.php der Workbench. Ein %s
// füllt das Skript
return [
	'[[/_admin/nav/gallery]]'						=> 'Galerie',
	'[[/_admin/gallery/label/images]]'	=> 'Galeriebilder',

	'[[/_admin/gallery/hint/empty]]'		=> 'Noch kein Album. Lege eines an – sein Schlüssel ist das, was der Shortcode nennt und worunter seine Bilder abgelegt werden, und lässt sich später nicht mehr ändern.',
	'[[/_admin/gallery/hint/no-images]]'	=> 'Noch kein Bild in diesem Album.',
	'[[/_admin/gallery/hint/shortcode]]'	=> 'Schreibe %s in ein Template oder einen Text',
	'[[/_admin/gallery/hint/sizes-fit]]'	=> 'Aus jedem Upload werden zwei Bilder: eine Vorschau, zugeschnitten auf %s, und eine große Ansicht des ganzen Bildes, skaliert in %s. Die gewählte Datei wird nicht aufbewahrt – die große Ansicht ist das Größte, was ein Besucher je zu sehen bekommt.',
	'[[/_admin/gallery/hint/sizes-crop]]'	=> 'Aus jedem Upload werden zwei Bilder: eine Vorschau, zugeschnitten auf %s, und eine große Ansicht, zugeschnitten auf %s. Die gewählte Datei wird nicht aufbewahrt – die große Ansicht ist das Größte, was ein Besucher je zu sehen bekommt.',
	'[[/_admin/gallery/hint/key]]'			=> 'Kleinbuchstaben, Ziffern und Bindestriche, beginnend mit einem Buchstaben. Der Shortcode nennt ihn und die Bilder werden darunter abgelegt, daher lässt er sich später nicht mehr ändern.',
	'[[/_admin/gallery/hint/upload-after]]'	=> 'Lege das Album an, dann kannst Du Bilder hochladen.',
	'[[/_admin/gallery/hint/settings]]'		=> 'Diese Maße gelten ab dem nächsten Hochladen – vorhandene Bilder behalten die Größe, in der sie erzeugt wurden.',
	'[[/_admin/gallery/hint/thumb-width]]'	=> 'Vorschaubilder werden genau darauf zugeschnitten, damit ein Raster ein Raster bleibt.',
	'[[/_admin/gallery/hint/large-width]]'	=> 'Die Größe hinter einem Vorschaubild. Das Original wird nie aufbewahrt – das hier ist das Größte, was ein Besucher je zu sehen bekommt.',
	'[[/_admin/gallery/hint/keep-ratio]]'	=> 'An: Die große Ansicht ist das ganze Bild, in die obigen Maße skaliert und in seinen eigenen Proportionen – genau das, was jemand sehen will, der ein Vorschaubild öffnet. Aus: Sie wird wie die Vorschau exakt auf diese Maße zugeschnitten.',
	'[[/_admin/gallery/hint/columns]]'		=> 'Wie viele Vorschaubilder eine Zeile in voller Breite trägt; auf schmalen Bildschirmen fällt das Raster von selbst auf weniger zurück. Ein Shortcode kann für eine einzelne Stelle eine eigene Zahl setzen.',

	'[[/_admin/gallery/label/new]]'			=> 'Album anlegen',
	'[[/_admin/gallery/label/delete]]'	=> 'Löschen',
	'[[/_admin/gallery/label/key]]'			=> 'Schlüssel',
	'[[/_admin/gallery/label/name]]'		=> 'Name',
	'[[/_admin/gallery/label/count]]'		=> '%s Bilder',
	'[[/_admin/gallery/label/upload]]'	=> 'Bilder hinzufügen',
	'[[/_admin/gallery/label/caption]]'	=> 'Bildunterschrift',
	'[[/_admin/gallery/label/alt]]'			=> 'Alternativtext',
	'[[/_admin/gallery/label/locale]]'	=> 'Sprache',
	'[[/_admin/gallery/label/earlier]]'	=> 'Eine Position nach vorn',
	'[[/_admin/gallery/label/later]]'		=> 'Eine Position nach hinten',
	'[[/_admin/gallery/label/sizes]]'		=> 'Bildmaße',
	'[[/_admin/gallery/label/thumb-width]]'	=> 'Breite der Vorschau',
	'[[/_admin/gallery/label/thumb-height]]'	=> 'Höhe der Vorschau',
	'[[/_admin/gallery/label/large-width]]'	=> 'Breite der großen Ansicht',
	'[[/_admin/gallery/label/large-height]]'	=> 'Höhe der großen Ansicht',
	'[[/_admin/gallery/label/keep-ratio]]'	=> 'Originalratio sichern',
	'[[/_admin/gallery/label/columns]]'		=> 'Spalten',

	'[[/_admin/gallery/msg/uploading]]'	=> '%s wird hochgeladen …',
	'[[/_admin/gallery/msg/uploaded]]'	=> '%s hinzugefügt.',

	'[[/_admin/gallery/confirm/album]]'	=> 'Das Album „%s“ mit seinen %d Bildern löschen? Die Bilder gehen mit – nichts anderes zeigt auf sie.',
	'[[/_admin/gallery/confirm/image]]'	=> 'Dieses Bild löschen? Beide Größen verschwinden vom Server.',

	'[[/_admin/error/gallery_key]]'				=> 'Ein Albumschlüssel ist ein Slug: klein geschrieben, beginnend mit einem Buchstaben.',
	'[[/_admin/error/gallery_reserved]]'	=> 'Der Schlüssel „new“ ist die Adresse der Seite, die ein Album anlegt, daher kann ihn kein Album haben.',
	'[[/_admin/error/gallery_long]]'			=> 'Der Schlüssel ist zu lang: Ein Albumschlüssel hat höchstens %s Zeichen.',
	'[[/_admin/error/gallery_taken]]'			=> 'Ein Album mit diesem Schlüssel gibt es schon.',
	'[[/_admin/error/gallery_settings]]'	=> 'Diese Werte liegen außerhalb des Erlaubten: %s.',
	'[[/_admin/error/gallery_album]]'			=> 'Kein Album hat diesen Schlüssel.',
	'[[/_admin/error/gallery_image]]'			=> 'Dieses Bild liegt nicht in diesem Album.',
	'[[/_admin/error/gallery_order]]'			=> 'Die Reihenfolge nannte nicht jedes Bild des Albums – es wurde nichts geändert.',
	'[[/_admin/error/gallery_locale]]'		=> 'Diese Sprache gibt es nicht.',
	'[[/_admin/error/gallery_upload]]'			=> 'Die Datei konnte nicht gelesen werden.',
	'[[/_admin/error/gallery_size]]'			=> 'Die Datei ist größer als %s.',
	'[[/_admin/error/gallery_process]]'		=> 'Der Server konnte aus diesem Bild die zwei Größen nicht erzeugen.',
];
