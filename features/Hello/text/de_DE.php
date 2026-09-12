<?php
declare(strict_types=1);

// Die eigenen Worte des Panels, eine Datei je Oberflächensprache (siehe
// Admin::text()). Das sind die der Workbench, nicht die der Seite: sie
// werden beim Zeichnen direkt aus diesem Verzeichnis gelesen und nie ins
// Projekt gemischt - deshalb kann ein Betreiber sie nicht ändern und ein
// Redakteur sieht sie nie. Die Worte der Seite sind der andere Satz, in
// features/Hello/install/text/.
return [

	// Der Eintrag in der Navigation, benannt von Admin::nav()
	'[[/_admin/hello/nav]]'					=> 'Hallo Welt',

	'[[/_admin/hello/title]]'				=> 'Hallo Welt',
	/*	Kein Shortcode hier drin, und das ist die Lektion: ein Textfill wird
		gerendert - „[hello]" in einem Fill ist also nicht das Wort „[hello]"
		auf dem Schirm, sondern der Shortcode, expandiert, überall wo dieser
		Fill gezeigt wird. Nenn ihn in Prosa, oder escape die Klammer	*/
	'[[/_admin/hello/hint]]'				=> 'Wen der Shortcode grüßt, wenn er niemanden nennt. Der Gruß selbst ist eine Einstellung dieses Features - Features, Einstellungen der Zeile.',

	'[[/_admin/hello/label/name]]'	=> 'Name',
	'[[/_admin/hello/label/save]]'	=> 'Speichern',

	'[[/_admin/hello/msg/saved]]'		=> 'Gespeichert.',
	'[[/_admin/hello/error/long]]'	=> 'Das ist länger als ein Name - höchstens 60 Zeichen.',
	'[[/_admin/hello/error/save]]'	=> 'Es konnte nicht gespeichert werden.',
];
