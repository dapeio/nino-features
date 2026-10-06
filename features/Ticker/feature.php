<?php
// features/Ticker/feature.php - what the Features panel reads. The class is
// not declared here: features/Ticker/ can only ever serve
// \Nino\Modules\Ticker.
return [
	'key'					=> 'ticker',
	'name'				=> [ 'en_US' => 'Ticker', 'de_DE' => 'Laufband' ],
	'description'	=> [
		'en_US' => 'A row that runs - logos, references, a line of announcements - once, or looping without a seam, pausing when it is pointed at, with a pause button on request, and standing still for a visitor who asked for less motion.',
		'de_DE' => 'Eine Reihe, die läuft - Logos, Referenzen, eine Zeile Ankündigungen -, einmal oder nahtlos in der Schleife, angehalten, wenn man darauf zeigt, auf Wunsch mit Pause-Knopf, und still für Besucher, die weniger Bewegung wollen.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'class="nino-ticker"' => [
				'en_US' => 'On the row: the box the track runs across, and the element the `data-ticker-*` attributes below go on. A row without a `nino-ticker-track` inside it does not run.',
				'de_DE' => 'An der Reihe: der Kasten, durch den die Spur läuft, und das Element, an das die `data-ticker-*`-Attribute unten gehören. Eine Reihe ohne `nino-ticker-track` darin läuft nicht.',
			],
			'class="nino-ticker-track"' => [
				'en_US' => 'On the one element inside the row that holds what runs: its children run past, one after the other - one cycle, once the row has been scrolled into view, and then it stands still.',
				'de_DE' => 'An dem einen Element in der Reihe, das hält, was läuft: Seine Kinder laufen nacheinander vorbei - ein Durchlauf, sobald die Reihe im Bild war, danach steht sie still.',
			],
			'data-ticker-speed="40"' => [
				'en_US' => 'Pixels per second. Default 40 - slow enough to read a word on the way past.',
				'de_DE' => 'Pixel je Sekunde. Vorgabe 40 - langsam genug, um ein Wort im Vorbeilaufen zu lesen.',
			],
			'data-ticker-direction="right"' => [
				'en_US' => 'Runs the other way. Default is to the left.',
				'de_DE' => 'Läuft andersherum. Vorgabe ist nach links.',
			],
			'data-ticker-loop="1"' => [
				'en_US' => 'Starts again without a seam instead of standing still after one cycle. Default: one cycle.',
				'de_DE' => 'Beginnt nahtlos von vorn, statt nach einem Durchlauf still zu stehen. Vorgabe: ein Durchlauf.',
			],
			'data-ticker-toggle="[[/feature/ticker/pause/label]]"' => [
				'en_US' => 'A button after the row that pauses it and takes it up again (WCAG 2.2.2). The value is its label; the fill is the one the install unit brings.',
				'de_DE' => 'Ein Knopf hinter der Reihe, der sie anhält und wieder aufnimmt (WCAG 2.2.2). Der Wert ist die Beschriftung; der Textfill ist der, den die Install-Einheit mitbringt.',
			],
			'data-ticker-pause="off"' => [
				'en_US' => 'Keeps running under the pointer. Default is to stop, so a logo can be looked at.',
				'de_DE' => 'Läuft unter dem Zeiger weiter. Vorgabe ist anzuhalten, damit ein Logo betrachtet werden kann.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The label of the pause button, [[/feature/ticker/pause/label]], into the Text panel.',
				'de_DE' => 'Die Beschriftung des Pause-Knopfs, [[/feature/ticker/pause/label]], ins Panel Texte.',
			],
		],
	],
	// It changes how what is already on the page behaves and brings one text
	// fill of its own, the label of the pause button - the Features panel
	// files that with the sliders and the lightboxes
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
