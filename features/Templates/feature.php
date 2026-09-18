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
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Templates' => [
				'en_US' => 'A page-*.tpl as a stack of sections: add one from the library, fill it in. What the builder does not recognise it leaves byte for byte.',
				'de_DE' => 'Eine page-*.tpl als Stapel von Sections: eine aus der Bibliothek hinzufügen, ausfüllen. Was der Baukasten nicht erkennt, lässt er Byte für Byte stehen.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	// Two floors, and the higher one wins. Not 1.1, because a kernel that
	// still ships _nino/Nino/Modules/Templates/ serves that copy instead of
	// this one - the autoloader resolves _nino/ first, on purpose, so a
	// shipped module can never be shadowed, and installing this there would
	// look like it worked and change nothing. And not 1.2 either, because the
	// sectioned 'manual' map below is only read by a kernel newer than the
	// v1.2.0-beta tag: on that one Features::manifest() refuses this file
	// outright, so a constraint admitting it offered a feature that could not
	// be installed. ^1.3 is the first that names only a kernel which does
	// both
	'nino'				=> '^1.3',
	'requires'		=> [],
	// The page templates it edits are the project's own, in private/templates/,
	// and a backup carries them as project content - none of it belongs to this
	// feature, which is why switching it off leaves every page exactly as it is
	'data'				=> [],
	'settings'		=> [],
];
