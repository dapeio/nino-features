<?php
// features/Lightbox/feature.php - what the Features panel reads. The class
// is not declared here: features/Lightbox/ can only ever serve
// \Nino\Modules\Lightbox.
return [
	'key'					=> 'lightbox',
	'name'				=> [ 'en_US' => 'Lightbox', 'de_DE' => 'Lightbox' ],
	'description'	=> [
		'en_US' => 'Lightbox lets you open any link to an image full screen, with its group as a set. Arrows, swipe, captions and a focus trap come with it, and no library.',
		'de_DE' => 'Die Lightbox ermöglicht es, jeden Link auf ein Bild bildschirmfüllend zu öffnen, mit seiner Gruppe als Serie. Dazu gehören Pfeile, Wischen, Bildunterschriften und eine Fokusfalle, ganz ohne Bibliothek.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'data-lightbox="trip"' => [
				'en_US' => 'On a link to an image it opens the image full screen; links sharing a value are one set, which arrows, swipe and keyboard move through.',
				'de_DE' => 'An einem Link auf ein Bild öffnet es bildschirmfüllend; Links mit gleichem Wert sind ein Satz, in dem Pfeile, Wischen und Tastatur blättern.',
			],
			'data-caption="Above the pass"' => [
				'en_US' => 'The caption; without the attribute the image\'s alt applies, else the link\'s title, and empty means no caption.',
				'de_DE' => 'Die Bildunterschrift; ohne das Attribut gilt das alt des Bildes, sonst das title des Links, und leer heißt: keine Unterschrift.',
			],
			'data-label-close, data-label-prev, data-label-next' => [
				'en_US' => 'The labels of the three controls for screen readers; without them the script\'s own English or German words are used.',
				'de_DE' => 'Die Beschriftungen der drei Bedienelemente für Screenreader; ohne sie gelten die englischen oder deutschen Worte des Skripts.',
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
	'version'			=> '1.1.1',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing: what it opens is markup the page already carries
	'data'				=> [],
	// The overlay's stylesheet and its script, into the site's two bundles
	'assets'			=> [ '/features/Lightbox/assets/lightbox.css', '/features/Lightbox/assets/lightbox.js' ],
	// No settings, and for the reason typewriter.js has none: the two
	// files are static assets that never pass the fill engine (see
	// docs/development.md, "Assets Are Not Templates"), so a site-wide value
	// could not reach them. What one lightbox does differently from another
	// is a data attribute on the link
	'settings'		=> [],
];
