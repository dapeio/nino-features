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
		'shortcodes' => [
			'[ticker]...[/ticker]' => [
				'en_US' => 'A row that runs, as a component that holds components: what stands between the tags is the calls of the components of a section - a title, a text, a picture - and each of them is one item of the row. It draws the box and the track of the markup below around them, and writes a data-ticker-* attribute only where the call differs from what the script does without it. The Builder offers it among the components of a section and of the hero, and its children are edited where they stand. A row inside a row is not possible, since the shortcode reads its content up to the first closing tag, and the Builder puts no component that holds components into another.',
				'de_DE' => 'Eine Reihe, die läuft, als Komponente, die Komponenten enthält: Was zwischen den Tags steht, sind die Aufrufe der Komponenten einer Sektion - ein Titel, ein Text, ein Bild -, und jeder davon ist ein Eintrag der Reihe. Sie zeichnet den Kasten und die Spur des Markups unten darum und schreibt ein data-ticker-*-Attribut nur dort, wo der Aufruf von dem abweicht, was das Skript ohne es tut. Der Builder bietet sie unter den Komponenten einer Sektion und des Heros an, und ihre Kinder bearbeitest Du dort, wo sie stehen. Eine Reihe in einer Reihe geht nicht, denn der Shortcode liest seinen Inhalt bis zum ersten schließenden Tag, und der Builder setzt keine Komponente, die Komponenten enthält, in eine andere.',
			],
			'[ticker speed="60"]' => [
				'en_US' => 'Pixels per second. Default 40. Written as data-ticker-speed only where it is another number.',
				'de_DE' => 'Pixel je Sekunde. Vorgabe 40. Als data-ticker-speed nur geschrieben, wo es eine andere Zahl ist.',
			],
			'[ticker direction="right"]' => [
				'en_US' => 'Runs the other way. Default is left.',
				'de_DE' => 'Läuft andersherum. Vorgabe ist links.',
			],
			'[ticker loop="1"]' => [
				'en_US' => 'Starts again without a seam instead of standing still after one cycle. Default no.',
				'de_DE' => 'Beginnt nahtlos von vorn, statt nach einem Durchlauf still zu stehen. Vorgabe nein.',
			],
			'[ticker toggle="1"]' => [
				'en_US' => 'A pause button after the row, labelled with the text fill of the install unit. Default no.',
				'de_DE' => 'Ein Pause-Knopf hinter der Reihe, beschriftet mit dem Textfill der Install-Einheit. Vorgabe nein.',
			],
			'[ticker pause="0"]' => [
				'en_US' => 'Keeps running under the pointer. Default yes: the row stops while it is pointed at.',
				'de_DE' => 'Läuft unter dem Zeiger weiter. Vorgabe ja: Die Reihe hält an, solange man darauf zeigt.',
			],
		],
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
				'en_US' => 'Starts again without a seam instead of standing still after one cycle. The words 1, true, yes and on ask for it; any other value leaves the default: one cycle.',
				'de_DE' => 'Beginnt nahtlos von vorn, statt nach einem Durchlauf still zu stehen. Die Wörter 1, true, yes und on verlangen es; jeder andere Wert lässt die Vorgabe stehen: ein Durchlauf.',
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
	'version'			=> '1.1.0',
	'nino'				=> '^1.8',
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
		one cycle, no pause button, stopping under the pointer. Offered in a
		section and in the hero, since the script runs a row wherever it finds
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
			'in'			=> [ 'section', 'hero' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'm6 17 5-5-5-5' ] ],
				[ 'path', [ 'd' => 'm13 17 5-5-5-5' ] ],
			],
		],
	],
];
