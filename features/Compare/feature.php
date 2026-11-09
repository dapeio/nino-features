<?php
// features/Compare/feature.php - what the Features panel reads. The class is
// not declared here: features/Compare/ can only ever serve
// \Nino\Modules\Compare.
return [
	'key'					=> 'compare',
	'name'				=> [ 'en_US' => 'Before/After', 'de_DE' => 'Vorher/Nachher' ],
	'description'	=> [
		'en_US' => 'Before/After lets you lay two pictures of the same thing over one another, with a divider that visitors move. It works with the mouse, a finger and the arrow keys. Without JavaScript they stay two captioned pictures.',
		'de_DE' => 'Vorher/Nachher ermöglicht es, zwei Bilder derselben Sache übereinander zu legen, mit einem Trenner, den Besucher verschieben. Das geht mit Maus, Finger und Pfeiltasten. Ohne JavaScript bleiben es zwei beschriftete Bilder untereinander.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[compare before="a.jpg" after="b.jpg"]' => [
				'en_US' => 'Lays two images from the project\'s own pictures over one another and gives them the divider.',
				'de_DE' => 'Legt zwei Bilder aus den eigenen Bildern des Projekts übereinander und gibt ihnen den Trenner.',
			],
			'[compare ... before-label="Rohbau" after-label="Fertig" alt="Die Fassade" start="20" ratio="4-3" class="my-class"]' => [
				'en_US' => 'Names the sides, describes the pair, sets the divider (start, 0 to 100) and the shape (16-9, 4-3, 1-1, 3-2), and adds a class of your own.',
				'de_DE' => 'Benennt die Seiten, beschreibt das Paar, setzt den Trenner (start, 0 bis 100) und die Form (16-9, 4-3, 1-1, 3-2) und hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'class="nino-compare"' => [
				'en_US' => 'This is what the shortcode writes; without JavaScript it is two captioned pictures one under the other.',
				'de_DE' => 'Das schreibt der Shortcode; ohne JavaScript sind es zwei beschriftete Bilder untereinander.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/html/shortcode/compare' => [
				'en_US' => 'Runs ahead of the component and hands before-label and after-label on as beforeLabel and afterLabel, as the Builder writes them.',
				'de_DE' => 'Läuft vor der Komponente und gibt before-label und after-label als beforeLabel und afterLabel weiter, wie der Builder sie schreibt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the three words of the pair into the Text panel.',
				'de_DE' => 'Bringt die drei Wörter des Paars ins Panel Texte.',
			],
		],
	],
	// It puts a control on the page that changes how what is already there is
	// looked at - the Features panel files that with the sliders and the
	// lightboxes
	'category'		=> 'ui',
	'version'			=> '1.1.1',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing under data/: the two pictures are the project's own images, and
	// where the divider stands is the visitor's, for as long as they look
	'data'				=> [],
	// The pair's stylesheet and its script, into the site's two bundles
	'assets'			=> [ '/features/Compare/assets/compare.css', '/features/Compare/assets/compare.js' ],
	/*	No settings. Everything a comparison varies - which two pictures,
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
		and start() has always answered the default for it	*/
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
			'draws'			=> 'figure .nino-compare',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M8 19H5c-1 0-2-1-2-2V7c0-1 1-2 2-2h3' ] ],
				[ 'path', [ 'd' => 'M16 5h3c1 0 2 1 2 2v10c0 1-1 2-2 2h-3' ] ],
				[ 'line', [ 'x1' => '12', 'x2' => '12', 'y1' => '4', 'y2' => '20' ] ],
			],
		],
	],
];
