<?php
declare(strict_types=1);

// Die Worte, die die Seite sagt - bei jeder Aktivierung in die text/de_DE.php
// des Projekts gemischt, add-only: ein Schlüssel, den das Projekt schon hat,
// bleibt. Ab dann pflegen Redakteure sie im Panel Texte und öffnen nie ein
// Feature-Verzeichnis. Die Worte des Panels sind ein anderer Satz, in
// features/Hello/text/.
return [

	// Die Worte der Seite, die die Route /hello beantwortet: ihr Name im Builder und im Panel Routen, ihr Titel und ihre Beschreibung
	'[[/_nino/webpage/hello/name]]'	=> 'Hallo Welt',
	'[[/_nino/webpage/hello/title]]'	=> 'Hallo Welt',
	'[[/_nino/webpage/hello/description]]'	=> 'Ein Gruß, den ein Feature speichert und zeichnet.',

	'[[/template/page-hello/intro/title]]'	=> 'Hallo Welt',
	'[[/template/page-hello/intro/text]]'	=> 'Diese Seite und der Gruß darunter kamen mit einem Feature. Beides gehört jetzt Dir: die Worte stehen im Panel Texte, die Seite in templates/page-hello.tpl.',
];
