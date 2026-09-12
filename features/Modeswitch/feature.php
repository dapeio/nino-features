<?php
// features/Modeswitch/feature.php - what the Features panel reads. The class
// is not declared here: features/Modeswitch/ can only ever serve
// \Nino\Modules\Modeswitch.
return [
	'key'					=> 'modeswitch',
	'name'				=> [ 'en_US' => 'Light/Dark Switch', 'de_DE' => 'Hell/Dunkel-Schalter' ],
	'description'	=> [
		'en_US' => 'Lets a visitor read the site light, dark, or the way their system asks for it - a three-state switch put anywhere with [mode-switch]. The dark palette is the one the project already ships.',
		'de_DE' => 'Lässt Besucher die Seite hell, dunkel oder so lesen, wie ihr System es vorgibt - ein Schalter mit drei Zuständen, den [mode-switch] an eine beliebige Stelle setzt. Die dunkle Palette ist die, die das Projekt ohnehin mitbringt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[mode-switch]' => [
				'en_US' => 'Three buttons: light, follow the system, dark.',
				'de_DE' => 'Drei Schaltflächen: hell, dem System folgen, dunkel.',
			],
			'[mode-switch icons]' => [
				'en_US' => 'The same without the words - for a crowded header bar.',
				'de_DE' => 'Dasselbe ohne die Wörter – für eine volle Kopfzeile.',
			],
		],
		'markup' => [
			'data-nino-mode="dark"' => [
				'en_US' => 'What the switch writes on <html>. assets/theme.css already answers to it; no attribute means "follow the system".',
				'de_DE' => 'Was der Schalter auf <html> schreibt. assets/theme.css hört längst darauf; kein Attribut heißt „dem System folgen".',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The switch\'s four words, into the Text panel.',
				'de_DE' => 'Die vier Worte des Schalters, ins Panel Texte.',
			],
		],
	],
	// It adds a control to a page that changes how what is already there is
	// read - the Features panel files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	// 1.2 is where the dark half of the palette became part of what a project
	// is delivered with (assets/theme.css carries :root[data-nino-mode="dark"]
	// and the matching prefers-color-scheme block). Before that there is
	// nothing for this switch to switch
	'nino'				=> '^1.2',
	'requires'		=> [],
	// Nothing under data/: the choice belongs to the reader's browser and is
	// kept there. A site that stored it would be storing a preference about a
	// person, which is a consent question this feature deliberately does not
	// raise
	'data'				=> [],
	// ...and no settings. Which of the three the switch starts on is not the
	// site's decision - it is "whatever this reader chose last, and their
	// system until they choose". The only thing a project can vary is whether
	// the buttons carry their words, and that belongs to the one place the
	// switch is written (see manual: [mode-switch icons])
	'settings'		=> [],
];
