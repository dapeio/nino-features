<?php
// Die Workbench-Texte des Gallery-Features, die in seine Fills gemischt
// werden, solange das Feature aktiv ist (siehe text() des Panels) - gleiche
// Schlüssel und Form wie die eigene text/<locale>.php der Workbench. Ein %s
// füllt das Skript, oder das Panel in einer Meldung, die es selbst formuliert
// (siehe sein _say())
return [
	'[[/_admin/nav/gallery]]'						=> 'Galerie',
	'[[/_admin/gallery/label/images]]'	=> 'Galeriebilder',

	'[[/_admin/gallery/hint/empty]]'		=> 'Noch kein Album. Lege eines an – sein Schlüssel ist das, was der Shortcode nennt und worunter seine Bilder abgelegt werden, und lässt sich später nicht mehr ändern.',
	'[[/_admin/gallery/hint/no-images]]'	=> 'Noch kein Bild in diesem Album.',
	'[[/_admin/gallery/hint/shortcode]]'	=> 'Schreibe %s in ein Template oder einen Text',
	'[[/_admin/gallery/hint/sizes-fit]]'	=> 'Aus jedem Upload werden zwei Bilder: eine Vorschau, zugeschnitten auf %1, und eine große Ansicht des ganzen Bildes, skaliert in %2. Die gewählte Datei wird nicht aufbewahrt – die große Ansicht ist das Größte, was ein Besucher je zu sehen bekommt.',
	'[[/_admin/gallery/hint/sizes-crop]]'	=> 'Aus jedem Upload werden zwei Bilder: eine Vorschau, zugeschnitten auf %1, und eine große Ansicht, zugeschnitten auf %2. Die gewählte Datei wird nicht aufbewahrt – die große Ansicht ist das Größte, was ein Besucher je zu sehen bekommt.',

	'[[/_admin/gallery/label/new]]'			=> 'Album anlegen',
	'[[/_admin/gallery/label/open]]'		=> 'Bilder',
	'[[/_admin/gallery/label/delete]]'	=> 'Löschen',
	'[[/_admin/gallery/label/key]]'			=> 'Schlüssel',
	'[[/_admin/gallery/label/name]]'		=> 'Name',
	'[[/_admin/gallery/label/count]]'		=> '%s Bilder',
	'[[/_admin/gallery/label/upload]]'	=> 'Bilder hinzufügen',
	'[[/_admin/gallery/label/caption]]'	=> 'Bildunterschrift',
	'[[/_admin/gallery/label/earlier]]'	=> 'Eine Position nach vorn',
	'[[/_admin/gallery/label/later]]'		=> 'Eine Position nach hinten',

	'[[/_admin/gallery/msg/uploading]]'	=> '%s wird hochgeladen …',
	'[[/_admin/gallery/msg/uploaded]]'	=> '%s hinzugefügt.',

	'[[/_admin/gallery/confirm/album]]'	=> 'Das Album „%s“ mit seinen %d Bildern löschen? Die Bilder gehen mit – nichts anderes zeigt auf sie.',
	'[[/_admin/gallery/confirm/image]]'	=> 'Dieses Bild löschen? Beide Größen verschwinden vom Server.',

	'[[/_admin/gallery/error/key]]'			=> 'Ein Albumschlüssel ist ein Slug: klein geschrieben, beginnend mit einem Buchstaben.',
	'[[/_admin/gallery/error/album]]'		=> 'Kein Album hat diesen Schlüssel.',
	'[[/_admin/gallery/error/image]]'		=> 'Dieses Bild liegt nicht in diesem Album.',
	'[[/_admin/gallery/error/order]]'		=> 'Die Reihenfolge nannte nicht jedes Bild des Albums – es wurde nichts geändert.',
	'[[/_admin/gallery/error/upload]]'	=> 'Die Datei konnte nicht gelesen werden.',
	'[[/_admin/gallery/error/save]]'		=> 'Die Alben konnten nicht geschrieben werden.',
];
