<?php
// features/Ticker/feature.php - what the Features panel reads. The class is
// not declared here: features/Ticker/ can only ever serve
// \Nino\Modules\Ticker.
return [
	'key'					=> 'ticker',
	'name'				=> [ 'en_US' => 'Ticker', 'de_DE' => 'Laufband' ],
	'description'	=> [
		'en_US' => 'A row that runs - logos, references, a line of announcements - looping without a seam, pausing when it is pointed at, and standing still for a visitor who asked for less motion.',
		'de_DE' => 'Eine Reihe, die läuft - Logos, Referenzen, eine Zeile Ankündigungen -, nahtlos in der Schleife, angehalten, wenn man darauf zeigt, und still für Besucher, die weniger Bewegung wollen.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'class="nino-ticker"' => [
				'en_US' => 'On a container: its children run past, one after the other, and start again.',
				'de_DE' => 'An einem Container: Seine Kinder laufen nacheinander vorbei und beginnen von vorn.',
			],
			'data-ticker-speed="40"' => [
				'en_US' => 'Pixels per second. Default 40 - slow enough to read a word on the way past.',
				'de_DE' => 'Pixel je Sekunde. Vorgabe 40 - langsam genug, um ein Wort im Vorbeilaufen zu lesen.',
			],
			'data-ticker-direction="right"' => [
				'en_US' => 'Runs the other way. Default is to the left.',
				'de_DE' => 'Läuft andersherum. Vorgabe ist nach links.',
			],
			'data-ticker-pause="off"' => [
				'en_US' => 'Keeps running under the pointer. Default is to stop, so a logo can be looked at.',
				'de_DE' => 'Läuft unter dem Zeiger weiter. Vorgabe ist anzuhalten, damit ein Logo betrachtet werden kann.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [],
	],
	// It changes how what is already on the page behaves and brings nothing of
	// its own to write - the Features panel files that with the sliders and
	// the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: what runs past is the markup a project's own
	// template already carries
	'data'				=> [],
	/*	And no settings, for the reason Typewriter has none: every timing
		belongs to the row being run, not to the site, and a static asset could
		not read a site-wide setting anyway - a logo bar in a footer and a line
		of announcements in a header are not one speed	*/
	'settings'		=> [],
];
