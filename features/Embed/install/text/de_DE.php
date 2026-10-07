<?php
declare(strict_types=1);

// Die Wörter einer noch nicht freigegebenen Einbindung - beim Aktivieren
// einmal in die text/de_DE.php des Projekts übernommen (nur ergänzend: ein
// Schlüssel, den das Projekt schon hat, bleibt); ab dann pflegen Redakteure
// sie im Panel Texte.
return [

	// Unter dem Abspielzeichen, wenn der Shortcode ohne eigenen Titel
	// geschrieben wurde. Wer title="Anfahrt" schreibt, sagt etwas, das dieser
	// Text nicht sagen kann - er ist der Rückfall, nicht die Regel
	'[[/feature/embed/placeholder/button]]'	=> 'Externen Inhalt laden',

	// Der Satz, an dem sich entscheidet, ob jemand drückt. Der Anbieter wird
	// vom Shortcode dahinter gesetzt, der Satz endet also vor einem Namen
	'[[/feature/embed/placeholder/note]]'	=> 'Beim Drücken werden Inhalte geladen von',

	// Der Ausweg im <noscript>, für Besucher, denen gar kein Rahmen gegeben
	// werden kann. Gleiche Form: Es folgt ein Name
	'[[/feature/embed/fallback/link]]'	=> 'In neuem Tab öffnen bei',

	// Wie der Rahmen selbst heißt, sobald er da ist, wenn der Shortcode
	// nichts benannt hat. Ein Rahmen ohne Namen wird als „iframe" angesagt
	'[[/feature/embed/frame/title]]'	=> 'Externer Inhalt',
];
