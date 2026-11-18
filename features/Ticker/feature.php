<?php
// features/Ticker/feature.php - what the Features panel reads. The class is
// not declared here: features/Ticker/ can only ever serve
// \Nino\Modules\Ticker.
return [
	'key'					=> 'ticker',
	'name'				=> [ 'en_US' => 'Ticker', 'de_DE' => 'Laufband' ],
	'description'	=> [
		'en_US' => 'Ticker lets you run a row: logos, references or a line of announcements. It runs once or loops without a seam, pauses when it is pointed at, and stands still for visitors who asked for less motion. A pause button is available on request.',
		'de_DE' => 'Das Laufband ermöglicht eine Reihe, die läuft: Logos, Referenzen oder eine Zeile Ankündigungen. Sie läuft einmal oder nahtlos in der Schleife, hält an, wenn man darauf zeigt, und steht still für Besucher, die weniger Bewegung wollen. Auf Wunsch gibt es einen Pause-Knopf.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[ticker]...[/ticker]' => [
				'en_US' => 'A row that holds components: what stands between the tags are the items that run past, one cycle once the row has been in view.',
				'de_DE' => 'Eine Reihe, die Komponenten enthält: Was zwischen den Tags steht, sind die Einträge, die vorbeilaufen, ein Durchlauf, sobald die Reihe im Bild war.',
			],
			'[ticker speed="60" direction="right" loop toggle pause="0"]...[/ticker]' => [
				'en_US' => 'Sets pixels per second (40 by default), the direction, the loop, the pause button, and that the row keeps running under the pointer.',
				'de_DE' => 'Setzt Pixel je Sekunde (vorgegeben 40), die Richtung, die Schleife, den Pause-Knopf und dass die Reihe unter dem Zeiger weiterläuft.',
			],
		],
		'markup' => [
			'class="ticker-track"' => [
				'en_US' => 'The one element inside the row that holds what runs; the script copies its children, and the copies are hidden from assistive technology and cannot take the focus.',
				'de_DE' => 'Das eine Element in der Reihe, das hält, was läuft; das Skript kopiert seine Kinder, und die Kopien sind für Hilfstechnik verborgen und nehmen den Fokus nicht an.',
			],
			'data-ticker-label="[[/feature/ticker/pause/label]]"' => [
				'en_US' => 'The label of the pause button, which the row draws after itself where toggle is on.',
				'de_DE' => 'Die Beschriftung des Pause-Knopfs, den die Reihe hinter sich zeichnet, wo toggle an ist.',
			],
			'--ticker-picture-height' => [
				'en_US' => 'The height of a picture in the track, 3rem by default, which a project sets on the row or on :root to size its logos.',
				'de_DE' => 'Die Höhe eines Bildes in der Spur, vorgegeben 3rem, die ein Projekt an der Reihe oder auf :root setzt, um seine Logos zu bemessen.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group Ticker' => [
				'en_US' => 'The Themes panel offers the row as the component ticker with a knob for the height of its pictures.',
				'de_DE' => 'Das Panel Themes bietet die Reihe als Komponente ticker an, mit einem Regler für die Höhe ihrer Bilder.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the label of the pause button, [[/feature/ticker/pause/label]], into the Text panel.',
				'de_DE' => 'Bringt die Beschriftung des Pause-Knopfs, [[/feature/ticker/pause/label]], ins Panel Texte.',
			],
			'blocks/ticker-line/' => [
				'en_US' => 'A block: a line of announcements that runs round and round, with a pause button.',
				'de_DE' => 'Ein Block: eine Zeile Ankündigungen, die in der Schleife läuft, mit einem Pause-Knopf.',
			],
		],
	],
	// It changes how what is already on the page behaves and brings one text
	// fill of its own, the label of the pause button - the Features panel
	// files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// Nothing under data/: what runs past is what stands between the tags of [ticker]
	'data'				=> [],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Ticker/assets/ticker.js' ],
	/*	And no settings: every timing belongs to the row being run, not to the
		site - a logo bar in a footer and a line of announcements in a header
		are not one speed	*/
	'settings'		=> [],
];
