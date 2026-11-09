<?php
// features/Logos/feature.php - what the Features panel reads. The class is not
// declared here: features/Logos/ can only ever serve \Nino\Modules\Logos.
return [
	'key'					=> 'logos',
	'name'				=> [ 'en_US' => 'Logos', 'de_DE' => 'Logos' ],
	'description'	=> [
		'en_US' => 'Logos lets you show a row of logos, such as the customers a site is proud of. They come from the elements of one type that the editors keep under Elements. The Design feature offers four looks: row, grid, moving band and cards.',
		'de_DE' => 'Logos ermöglichen eine Reihe von Logos, etwa der Kunden, auf die eine Website stolz ist. Sie kommen aus den Elementen eines Typs, den die Redaktion unter Elemente pflegt. Das Design bietet vier Looks: Reihe, Raster, laufendes Band und Karten.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[logos /logo image="image" link="url" sort="order"]' => [
				'en_US' => 'Draws the elements of the type logo that have a picture as a row, the smaller position first; a logo with an address in the field url is a link; the Builder offers it under Loop.',
				'de_DE' => 'Zeichnet die Elemente des Typs logo, die ein Bild haben, als Reihe, die kleinere Position zuerst; ein Logo mit Adresse im Feld url ist ein Link; der Builder bietet es unter Loop an.',
			],
			'[logos /logo image="image" link="url" name="name" dark="image-dark"]' => [
				'en_US' => 'Names the fields that make a logo: picture, link, text of the picture and picture for a dark ground; a field written empty is none.',
				'de_DE' => 'Benennt die Felder, aus denen ein Logo besteht: Bild, Link, Text des Bildes und Bild für dunklen Grund; ein leer geschriebenes Feld ist keines.',
			],
			'[logos /logo sort="name" limit="8"]' => [
				'en_US' => 'The data are the loop of [elements]: sort, limit, offset and query.',
				'de_DE' => 'Die Daten sind der Loop von [elements]: sort, limit, offset und query.',
			],
			'[logos /logo links="0"]' => [
				'en_US' => 'Makes no logo a link, whatever its address says; otherwise a link is drawn only for https, http, mailto or a path of the site.',
				'de_DE' => 'Macht kein Logo zum Link, was auch immer seine Adresse sagt; ein Link entsteht sonst nur für https, http, mailto oder einen Pfad der Site.',
			],
			'[logos /logo height="l"]' => [
				'en_US' => 'Sets the height of the logos (s, m or l, shares of the height of the look); class="my-class" adds a class of your own.',
				'de_DE' => 'Setzt die Höhe der Logos (s, m oder l, Anteile der Höhe des Looks); class="my-class" hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'<div class="nino-logos-strip nino-logos-strip--h-m">' => [
				'en_US' => 'The row with the height of the call, nino-logos-strip-view with two lists and a pause button, which logos.js shows only where the track runs an animation.',
				'de_DE' => 'Die Reihe mit der Höhe des Aufrufs, nino-logos-strip-view mit zwei Listen und einem Pause-Knopf, den logos.js nur zeigt, wo die Spur eine Animation hat.',
			],
			'<li class="nino-logos-strip-item">' => [
				'en_US' => 'One logo; the picture is the kernel\'s logo set with nino-logo-light and nino-logo-dark, and a link is nino-logos-strip-link with rel="noopener".',
				'de_DE' => 'Ein Logo; das Bild ist das Logo-Set des Kernels mit nino-logo-light und nino-logo-dark, und ein Link ist nino-logos-strip-link mit rel="noopener".',
			],
			'<ul class="nino-logos-strip-copy" aria-hidden="true">' => [
				'en_US' => 'The copy of the list that the look Band runs behind the first one; every other look hides it, and screen readers skip it.',
				'de_DE' => 'Die Kopie der Liste, die der Look Band hinter der ersten laufen lässt; jeder andere Look blendet sie aus, und Screenreader überspringen sie.',
			],
			'--logos-height, --logos-gap, --logos-corners, --logos-ground, --logos-speed' => [
				'en_US' => 'The properties of the look, which a project sets in its own css on :root, or which a look of the Design feature writes.',
				'de_DE' => 'Die Eigenschaften des Looks, die ein Projekt in seinem CSS auf :root setzt, oder die ein Look des Designs schreibt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Builder: the presentation Logos' => [
				'en_US' => 'With the Builder, Logos is a presentation of the block Loop in the dialog of a column, beside Grid and Tabs, with type, data, fields, height and links.',
				'de_DE' => 'Mit dem Builder ist Logos eine Darstellung des Blocks Loop im Dialog einer Spalte, neben Raster und Tabs, mit Typ, Daten, Feldern, Höhe und Links.',
			],
			'Design: the looks of the logos' => [
				'en_US' => 'With the Design feature Logos offers the looks Row, Grid, Band and Cards, a select for the tone, knobs for height, space, corners, ground and speed, and a pause button in the band.',
				'de_DE' => 'Mit dem Design bietet Logos die Looks Reihe, Raster, Band und Karten, eine Auswahl für den Farbton, Regler für Höhe, Abstand, Ecken, Grund und Tempo, und einen Pause-Knopf im Band.',
			],
		],
		'callbacks' => [],
		'install' => [
			'logo.php' => [
				'en_US' => 'The element type logo with name, image, url, image-dark and order, copied to /elements where the project has none; the pictures are fitted into a box of 540 by 210 pixels.',
				'de_DE' => 'Der Elementtyp logo mit name, image, url, image-dark und order, nach /elements kopiert, wo das Projekt keinen hat; die Bilder werden in eine Box von 540 mal 210 Pixel eingepasst.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the labels of the type\'s fields and the word of the pause button into the Text panel.',
				'de_DE' => 'Bringt die Beschriftungen der Typfelder und das Wort des Pause-Knopfs ins Panel Texte.',
			],
		],
	],
	// It is for being found and being trusted, which is what a list of customers
	// is on a site, rather than content the site is about
	'category'		=> 'marketing',
	'version'			=> '1.0.0',
	'nino'				=> '^1.10',
	'requires'		=> [],
	// The logos are elements of a type, which is where a project keeps them
	'data'				=> [],
	// The row's stylesheet goes into the site's style bundle; the band is an animation, and the script only draws the button that pauses it
	'assets'			=> [ '/features/Logos/assets/logos.css', '/features/Logos/assets/logos.js' ],
	'settings'		=> [],
	// The row is a loop, a stack of the cell none: the kernel brings the data (type, sort, limit, query) and the Builder offers the fields it draws from
	'components'	=> [],
	'stacks'		=> [
		'logos' => [
			'label'			=> [ 'en_US' => 'Logos', 'de_DE' => 'Logos' ],
			'cell'			=> 'none',
			'grid'			=> false,
			'attributes'	=> [
				'image' => [
					'type'		=> 'field',
					'default'	=> 'image',
					'required'	=> true,
					'label'		=> [ 'en_US' => 'Picture', 'de_DE' => 'Bild' ],
					'hint'		=> [
						'en_US' => 'The picture field of the type that holds the logo. An element with no picture in it is left out, unless it has the picture for a dark ground, which is then seen on every ground.',
						'de_DE' => 'Das Bildfeld des Typs, in dem das Logo liegt. Ein Element ohne Bild darin entfällt, außer es hat das Bild für dunklen Grund, das dann auf jedem Grund zu sehen ist.',
					],
				],
				'link' => [
					'type'		=> 'field',
					'default'	=> 'url',
					'required'	=> 'links',
					'label'		=> [ 'en_US' => 'Link', 'de_DE' => 'Link' ],
					'hint'		=> [
						'en_US' => 'The field with the address a logo links to. Required while Links is on; left empty, no logo is a link.',
						'de_DE' => 'Das Feld mit der Adresse, zu der ein Logo verlinkt. Verbindlich, solange Links an ist; leer gelassen ist kein Logo ein Link.',
					],
				],
				'name' => [
					'type'		=> 'field',
					'default'	=> 'name',
					'label'		=> [ 'en_US' => 'Name', 'de_DE' => 'Name' ],
					'hint'		=> [
						'en_US' => 'The field with the name of the customer, the alt text of the picture. Optional: left empty, the picture has no alt text.',
						'de_DE' => 'Das Feld mit dem Namen des Kunden, der Alternativtext des Bildes. Optional: leer gelassen hat das Bild keinen Alternativtext.',
					],
				],
				'dark' => [
					'type'		=> 'field',
					'default'	=> 'image-dark',
					'label'		=> [ 'en_US' => 'Picture for a dark ground', 'de_DE' => 'Bild für dunklen Grund' ],
					'hint'		=> [
						'en_US' => 'The picture field with the logo for a dark ground. Optional: left empty, a logo has no second picture. A logo that has no picture in the field has none either, and the first one is seen on every ground.',
						'de_DE' => 'Das Bildfeld mit dem Logo für einen dunklen Grund. Optional: leer gelassen hat ein Logo kein zweites Bild. Ein Logo ohne Bild in dem Feld hat auch keines, und das erste ist auf jedem Grund zu sehen.',
					],
				],
				'height' => [
					'type'		=> 'select',
					'options'	=> [ 's', 'm', 'l' ],
					'default'	=> 'm',
					'input'		=> 'segment',
					'label'		=> [ 'en_US' => 'Height', 'de_DE' => 'Höhe' ],
					'hint'		=> [
						'en_US' => 'The height of the logos: three quarters of the height of the look, that height, one and a half times.',
						'de_DE' => 'Die Höhe der Logos: drei Viertel der Höhe des Looks, diese Höhe, das Anderthalbfache.',
					],
				],
				'links' => [
					'type'		=> 'bool',
					'default'	=> true,
					'label'		=> [ 'en_US' => 'Links', 'de_DE' => 'Links' ],
					'hint'		=> [
						'en_US' => 'A logo with an address is a link. Off, none is.',
						'de_DE' => 'Ein Logo mit Adresse ist ein Link. Aus: Keines ist ein Link.',
					],
				],
			],
			'preview'		=> 'image',
		],
	],
];
