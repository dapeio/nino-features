<?php
// features/Templates/feature.php - what the Features panel reads. The class is
// not declared here: features/Templates/ can only ever serve
// \Nino\Modules\Templates, and every part below it - Admin/, Documents/,
// Library/, Content/, Composer/, AreaComposer/, SectionDocument/ - resolves
// the same way, one directory per class.
return [
	'key'					=> 'templates',
	'name'				=> [ 'en_US' => 'Template Builder', 'de_DE' => 'Template-Baukasten' ],
	'description'	=> [
		'en_US' => 'Builds the project\'s page templates out of whole sections: a library of ready-made ones, a live preview, and page source that stays yours between them.',
		'de_DE' => 'Baut die Seitentemplates des Projekts aus ganzen Abschnitten: eine Bibliothek fertiger Abschnitte, eine Live-Vorschau, und dazwischen bleibt der Quelltext der Seite Deiner.',
	],
	'manual'			=> [
		'en_US' => <<<'TXT'
			The Templates panel lists the project's `page-*.tpl` files. Open one
			and it reads as a stack of sections; Add section opens the library,
			you pick one, fill it in, and it is inserted where you were.

			What the builder does not recognise it does not touch: a page frame,
			a hand-written block, anything above or below the sections stays
			byte for byte as it was. So a template stays editable by hand, and
			this panel is a way of working on it rather than the only one.

			A section that repeats - a grid of articles, a list of offers - is
			bound to an Elements type: either one the builder creates from the
			preset's model, or one the project already has, whose own fields you
			then map. The Elements panel is where the entries themselves live.
			TXT,
		'de_DE' => <<<'TXT'
			Das Panel Templates listet die `page-*.tpl` des Projekts. Öffne eine,
			und sie liest sich als Stapel von Abschnitten; „Abschnitt hinzufügen"
			öffnet die Bibliothek, Du wählst einen, füllst ihn aus, und er wird
			dort eingesetzt, wo Du warst.

			Was der Baukasten nicht erkennt, fasst er nicht an: ein Seitenrahmen,
			ein handgeschriebener Block, alles ober- und unterhalb der Abschnitte
			bleibt Byte für Byte, wie es war. Ein Template bleibt also von Hand
			bearbeitbar, und dieses Panel ist eine Art, daran zu arbeiten – nicht
			die einzige.

			Ein Abschnitt, der sich wiederholt – ein Raster aus Artikeln, eine
			Liste von Angeboten –, hängt an einem Elementtyp: entweder an einem,
			den der Baukasten aus dem Modell des Presets anlegt, oder an einem,
			den das Projekt schon hat und dessen Felder Du dann zuordnest. Die
			Einträge selbst liegen im Panel Elemente.
			TXT,
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	// Not ^1.1: a kernel that still ships _nino/Nino/Modules/Templates/ serves
	// that copy instead of this one - the autoloader resolves _nino/ first, on
	// purpose, so a shipped module can never be shadowed. Installing this on
	// 1.1 would look like it worked and change nothing, so the constraint is
	// what refuses it and says why
	'nino'				=> '^1.2',
	'requires'		=> [],
	// The page templates it edits are the project's own, in private/templates/,
	// and a backup carries them as project content - none of it belongs to this
	// feature, which is why switching it off leaves every page exactly as it is
	'data'				=> [],
	'settings'		=> [],
];
