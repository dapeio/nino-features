<?php
// features/Lightbox/feature.php - what the Features panel reads. The class
// is not declared here: features/Lightbox/ can only ever serve
// \Nino\Modules\Lightbox.
return [
	'key'					=> 'lightbox',
	'name'				=> [ 'en_US' => 'Lightbox', 'de_DE' => 'Lightbox' ],
	'description'	=> [
		'en_US' => 'Opens any link to an image full screen, with the group it belongs to as a set - arrows, swipe, captions and a focus trap, and no library.',
		'de_DE' => 'Öffnet jeden Link auf ein Bild bildschirmfüllend, mit seiner Gruppe als Serie - Pfeile, Wischen, Bildunterschriften und ein Fokusrahmen, ohne Bibliothek.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'data-lightbox="trip"' => [
				'en_US' => 'On a link to an image: opens it full screen. Links sharing a value are one set - arrows, swipe and keyboard move between them.',
				'de_DE' => 'An einem Link auf ein Bild: öffnet es bildschirmfüllend. Links mit gleichem Wert sind ein Satz – Pfeile, Wischen und Tastatur bewegen sich darin.',
			],
			'data-caption="Above the pass"' => [
				'en_US' => 'The caption. Without it the image\'s alt, else the link\'s title.',
				'de_DE' => 'Die Bildunterschrift. Ohne sie das alt des Bildes, sonst das title des Links.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [],
	],
	// It changes how something already on the page behaves and brings
	// nothing of its own to show
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// Nothing: what it opens is markup the page already carries
	'data'				=> [],
	// No settings either, and for the reason typewriter.js has none: the two
	// files are static assets that never pass the fill engine (see
	// docs/development.md, "Assets Are Not Templates"), so a site-wide value
	// could not reach them. What one lightbox does differently from another
	// is a data attribute on the link - see README.md
	'settings'		=> [],
];
