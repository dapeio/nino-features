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
				'en_US' => 'A row as a component that holds components: what stands between the tags are the items of the row; the Builder offers it among the components of a section.',
				'de_DE' => 'Eine Reihe als Komponente, die Komponenten enthält: Was zwischen den Tags steht, sind die Einträge der Reihe; der Builder bietet sie unter den Komponenten einer Section an.',
			],
			'[ticker speed="60" direction="right" loop="1" toggle="1" pause="0"]' => [
				'en_US' => 'Sets pixels per second (default 40), the direction, the loop, the pause button, and whether the row keeps running under the pointer.',
				'de_DE' => 'Setzt Pixel je Sekunde (Vorgabe 40), die Richtung, die Schleife, den Pause-Knopf und, ob die Reihe unter dem Zeiger weiterläuft.',
			],
		],
		'markup' => [
			'class="nino-ticker"' => [
				'en_US' => 'The row, the box the track runs across and the element the data-ticker-* attributes go on; without a nino-ticker-track inside it does not run.',
				'de_DE' => 'Die Reihe, der Kasten, durch den die Spur läuft, und das Element, an das die data-ticker-*-Attribute gehören; ohne nino-ticker-track darin läuft sie nicht.',
			],
			'class="nino-ticker-track"' => [
				'en_US' => 'The one element inside the row that holds what runs; its children run past one after the other, one cycle, once the row has been in view.',
				'de_DE' => 'Das eine Element in der Reihe, das hält, was läuft; seine Kinder laufen nacheinander vorbei, ein Durchlauf, sobald die Reihe im Bild war.',
			],
			'data-ticker-speed="40"' => [
				'en_US' => 'The speed in pixels per second, 40 by default; data-ticker-direction="right" runs the other way.',
				'de_DE' => 'Das Tempo in Pixeln je Sekunde, vorgegeben 40; data-ticker-direction="right" läuft andersherum.',
			],
			'data-ticker-loop="1"' => [
				'en_US' => 'Starts again without a seam (1, true, yes and on ask for it); data-ticker-pause="off" keeps the row running under the pointer.',
				'de_DE' => 'Beginnt nahtlos von vorn (1, true, yes und on verlangen es); data-ticker-pause="off" lässt die Reihe unter dem Zeiger weiterlaufen.',
			],
			'data-ticker-toggle="[[/feature/ticker/pause/label]]"' => [
				'en_US' => 'A button after the row that pauses it and takes it up again; the value is its label.',
				'de_DE' => 'Ein Knopf hinter der Reihe, der sie anhält und wieder aufnimmt; der Wert ist seine Beschriftung.',
			],
			'--ticker-picture-height' => [
				'en_US' => 'The height of a picture in the track, 3rem by default, which a project sets on the row or on :root to size its logos.',
				'de_DE' => 'Die Höhe eines Bildes in der Spur, vorgegeben 3rem, die ein Projekt an der Reihe oder auf :root setzt, um seine Logos zu bemessen.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the label of the pause button, [[/feature/ticker/pause/label]], into the Text panel.',
				'de_DE' => 'Bringt die Beschriftung des Pause-Knopfs, [[/feature/ticker/pause/label]], ins Panel Texte.',
			],
		],
	],
	// It changes how what is already on the page behaves and brings one text
	// fill of its own, the label of the pause button - the Features panel
	// files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.2.1',
	'nino'				=> '^1.9',
	'requires'		=> [],
	// Nothing under data/: what runs past is the markup a project's own
	// template carries, or the components between the tags of [ticker]
	'data'				=> [],
	// Into the site's style and script bundle, behind the kernel's own files
	'assets'			=> [ '/features/Ticker/assets/ticker.css', '/features/Ticker/assets/ticker.js' ],
	/*	And no settings, for the reason Typewriter has none: every timing
		belongs to the row being run, not to the site, and a static asset could
		not read a site-wide setting anyway - a logo bar in a footer and a line
		of announcements in a header are not one speed	*/
	'settings'		=> [],
	/*	The row as the Builder offers it: a component that holds the components
		of a section, so that a row of logos, references or announcements is
		built from a title, a text and a picture and not written by hand.
		Every default is what ticker.js and ticker.css do for a row that says
		nothing, and the renderer writes an attribute only where a call differs
		from it: 40 pixels per second (the script's SPEED_DEFAULT), to the left,
		one cycle, no pause button, stopping under the pointer. Offered in
		a section, since the script runs a row wherever it finds
		one; not in a card, which holds components itself and takes none that
		hold. Not in the cell of a loop: a row of the elements of a type is not
		something this is for	*/
	'components'	=> [
		'ticker' => [
			'label'			=> [ 'en_US' => 'Ticker', 'de_DE' => 'Laufband' ],
			'source'		=> 'content',
			'loop'			=> false,
			'holds'			=> 'section',
			'attributes'	=> [
				'speed' => [
					'type'		=> 'int',
					'min'			=> 1,
					'max'			=> 1000,
					'default'	=> 40,
					'label'		=> [ 'en_US' => 'Speed', 'de_DE' => 'Tempo' ],
					'hint'		=> [
						'en_US' => 'Pixels per second. 40 is slow enough to read a word on the way past.',
						'de_DE' => 'Pixel je Sekunde. 40 ist langsam genug, um ein Wort im Vorbeilaufen zu lesen.',
					],
				],
				'direction' => [
					'type'		=> 'select',
					'options'	=> '\Nino\Modules\Ticker::directions',
					'default'	=> 'left',
					'label'		=> [ 'en_US' => 'Direction', 'de_DE' => 'Richtung' ],
				],
				'loop' => [
					'type'		=> 'bool',
					'default'	=> false,
					'label'		=> [ 'en_US' => 'Round and round', 'de_DE' => 'In der Schleife' ],
					'hint'		=> [
						'en_US' => 'Starts again without a seam instead of standing still after one cycle.',
						'de_DE' => 'Beginnt nahtlos von vorn, statt nach einem Durchlauf still zu stehen.',
					],
				],
				'toggle' => [
					'type'		=> 'bool',
					'default'	=> false,
					'label'		=> [ 'en_US' => 'Pause button', 'de_DE' => 'Pause-Knopf' ],
					'hint'		=> [
						'en_US' => 'A button after the row that pauses it and takes it up again (WCAG 2.2.2). A loop needs one.',
						'de_DE' => 'Ein Knopf hinter der Reihe, der sie anhält und wieder aufnimmt (WCAG 2.2.2). Eine Schleife braucht ihn.',
					],
				],
				'pause' => [
					'type'		=> 'bool',
					'default'	=> true,
					'label'		=> [ 'en_US' => 'Stop under the pointer', 'de_DE' => 'Unter dem Zeiger anhalten' ],
					'hint'		=> [
						'en_US' => 'The row stops while it is pointed at, so a logo can be looked at. Off keeps it running.',
						'de_DE' => 'Die Reihe hält an, solange man darauf zeigt, damit ein Logo betrachtet werden kann. Aus lässt sie weiterlaufen.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'div .nino-ticker',
			'in'			=> [ 'section' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'm6 17 5-5-5-5' ] ],
				[ 'path', [ 'd' => 'm13 17 5-5-5-5' ] ],
			],
		],
	],
];
