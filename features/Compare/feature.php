<?php
// features/Compare/feature.php - what the Features panel reads. The class is
// not declared here: features/Compare/ can only ever serve
// \Nino\Modules\Compare.
return [
	'key'					=> 'compare',
	'name'				=> [ 'en_US' => 'Before/After', 'de_DE' => 'Vorher/Nachher' ],
	'description'	=> [
		'en_US' => 'Two pictures of the same thing under one divider the visitor moves - with the mouse, a finger or the arrow keys, because the divider is a real range control rather than a script pretending to be one.',
		'de_DE' => 'Zwei Bilder derselben Sache unter einem Trenner, den der Besucher bewegt - mit Maus, Finger oder Pfeiltasten, denn der Trenner ist ein echtes Schieberegler-Element und kein Skript, das so tut.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[compare before="a.jpg" after="b.jpg"]' => [
				'en_US' => 'Two images from the project\'s own pictures, one over the other.',
				'de_DE' => 'Zwei Bilder aus den eigenen Bildern des Projekts, eines über dem anderen.',
			],
			'[compare ... before-label="Rohbau" after-label="Fertig"]' => [
				'en_US' => 'What the two sides are called. Without them the Text panel\'s own words are used.',
				'de_DE' => 'Wie die beiden Seiten heißen. Ohne sie werden die Wörter aus dem Panel Texte verwendet.',
			],
			'[compare ... alt="Die Fassade vor und nach der Sanierung"]' => [
				'en_US' => 'What the pair shows, for a visitor who cannot see it. Say it: two pictures with no description are two decorations.',
				'de_DE' => 'Was das Paar zeigt, für Besucher, die es nicht sehen. Angeben: Zwei Bilder ohne Beschreibung sind zwei Verzierungen.',
			],
			'[compare ... start="20"]' => [
				'en_US' => 'Where the divider stands when the page opens, 0 to 100. Default 50.',
				'de_DE' => 'Wo der Trenner steht, wenn die Seite aufgeht, 0 bis 100. Vorgabe 50.',
			],
			'[compare ... ratio="4-3"]' => [
				'en_US' => 'The shape of the box: 16-9, 4-3 (default), 1-1 or 3-2.',
				'de_DE' => 'Die Form des Kastens: 16-9, 4-3 (Vorgabe), 1-1 oder 3-2.',
			],
			'[compare ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the box. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Kastens. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'class="nino-compare"' => [
				'en_US' => 'What the shortcode writes. Without JavaScript the same markup is two captioned pictures under one another.',
				'de_DE' => 'Was der Shortcode schreibt. Ohne JavaScript sind es zwei beschriftete Bilder untereinander.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/html/shortcode/compare' => [
				'en_US' => 'Runs ahead of the component: before-label and after-label of a call are handed on as the attributes beforeLabel and afterLabel, which the Builder writes. Nino 1.5 has no component and no need for it.',
				'de_DE' => 'Läuft vor der Komponente: before-label und after-label eines Aufrufs werden als die Attribute beforeLabel und afterLabel weitergegeben, wie der Builder sie schreibt. Nino 1.5 kennt keine Komponente und braucht es nicht.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The three words the pair carries, into the Text panel.',
				'de_DE' => 'Die drei Wörter des Paars, ins Panel Texte.',
			],
		],
	],
	// It puts a control on the page that changes how what is already there is
	// looked at - the Features panel files that with the sliders and the
	// lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: the two pictures are the project's own images, and
	// where the divider stands is the visitor's, for as long as they look
	'data'				=> [],
	/*	And no settings. Everything a comparison varies - which two pictures,
		what the sides are called, where the divider starts, the shape of the
		box - belongs to the one place it is written, not to the site: a
		before/after of a facade and one of a photo retouch are not the same
		decision	*/
	'settings'		=> [],
	/*	The shortcode as the Builder offers it: no first argument, the two pictures are
		named by their attributes. The two captions are written without a hyphen here,
		because an attribute of a component is a lowerCamel name - the callback of the
		class keeps before-label and after-label of the manual as they were. start is a
		string, not an int with bounds: the wrapper would clamp a value outside 0 to 100,
		and start() has always answered the default for it. Nino 1.5 ignores the key	*/
	'components'	=> [
		'compare' => [
			'label'			=> [ 'en_US' => 'Before and after', 'de_DE' => 'Vorher und nachher' ],
			'source'		=> 'none',
			'attributes'	=> [
				'before' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Picture before', 'de_DE' => 'Bild vorher' ],
					'hint'		=> [
						'en_US' => 'A file name below the project\'s images. Without both pictures nothing is drawn.',
						'de_DE' => 'Ein Dateiname unter den Bildern des Projekts. Ohne beide Bilder wird nichts gezeichnet.',
					],
				],
				'after' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Picture after', 'de_DE' => 'Bild nachher' ],
					'hint'		=> [
						'en_US' => 'A file name below the project\'s images.',
						'de_DE' => 'Ein Dateiname unter den Bildern des Projekts.',
					],
				],
				'beforeLabel' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Name of the first side', 'de_DE' => 'Name der ersten Seite' ],
					'hint'		=> [
						'en_US' => 'What the first side is called. Empty takes the words of the Text panel.',
						'de_DE' => 'Wie die erste Seite heißt. Leer nimmt die Wörter aus dem Panel Texte.',
					],
				],
				'afterLabel' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Name of the second side', 'de_DE' => 'Name der zweiten Seite' ],
					'hint'		=> [
						'en_US' => 'What the second side is called. Empty takes the words of the Text panel.',
						'de_DE' => 'Wie die zweite Seite heißt. Leer nimmt die Wörter aus dem Panel Texte.',
					],
				],
				'alt' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Description', 'de_DE' => 'Beschreibung' ],
					'hint'		=> [
						'en_US' => 'What the pair shows, for a visitor who cannot see it. Say it: two pictures with no description are two decorations.',
						'de_DE' => 'Was das Paar zeigt, für Besucher, die es nicht sehen. Angeben: Zwei Bilder ohne Beschreibung sind zwei Verzierungen.',
					],
				],
				'start' => [
					'type'		=> 'string',
					'default'	=> '50',
					'label'		=> [ 'en_US' => 'Start of the divider', 'de_DE' => 'Start des Trenners' ],
					'hint'		=> [
						'en_US' => 'Where the divider stands when the page opens, 0 to 100.',
						'de_DE' => 'Wo der Trenner steht, wenn die Seite aufgeht, 0 bis 100.',
					],
				],
				'ratio' => [
					'type'		=> 'select',
					'options'	=> [ '16-9', '4-3', '1-1', '3-2' ],
					'default'	=> '4-3',
					'label'		=> [ 'en_US' => 'Shape', 'de_DE' => 'Form' ],
					'hint'		=> [
						'en_US' => 'The shape of the box.',
						'de_DE' => 'Die Form des Kastens.',
					],
				],
			],
			'preview'		=> 'image',
		],
	],
];
