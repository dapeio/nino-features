<?php
declare(strict_types=1);

// Die Worte, die die Seite sagt - bei der Aktivierung einmal in die
// text/de_DE.php des Projekts gemischt (add-only: ein Schlüssel, den das
// Projekt schon hat, bleibt). Ab dann pflegen Redakteure sie im Panel Texte
// und öffnen nie ein Feature-Verzeichnis. Die Worte des Panels sind ein
// anderer Satz, in features/Hello/text/ - siehe README.md.
return [

	'[[/hello/note]]'				=> 'Gerendert vom Feature Hallo Welt.',

	'[[/hello/page/title]]'	=> 'Hallo Welt',
	'[[/hello/page/text]]'	=> 'Diese Seite und der Gruß darunter kamen mit einem Feature. Beides gehört jetzt Dir: die Worte stehen im Panel Texte, die Seite in templates/page-hello.tpl.',
];
