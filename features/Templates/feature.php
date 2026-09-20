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
