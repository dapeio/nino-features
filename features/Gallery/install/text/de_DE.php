<?php
declare(strict_types=1);

// Der Name eines Bildes, das weder einen Alternativtext noch eine
// Bildunterschrift hat - bei jeder Aktivierung in die text/de_DE.php des
// Projekts übernommen (nur ergänzend: ein Schlüssel, den das Projekt schon
// hat, bleibt, einer, der fehlt, kommt dazu). Die beiden %s sind der Name des
// Albums und die Stelle des Bildes darin, in dieser Reihenfolge: „Reise, Bild 3“.
// Ohne ihn wäre eine Vorschau ein Link ohne Namen, und ein Screenreader läse
// statt seiner die Adresse des Bildes vor.
return [
	'[[/feature/gallery/image/unnamed]]'	=> '%s, Bild %s',
];
