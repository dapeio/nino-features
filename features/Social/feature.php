<?php
// features/Social/feature.php - what the Features panel reads. The class is
// not declared here: features/Social/ can only ever serve
// \Nino\Modules\Social.
return [
	'key'					=> 'social',
	'name'				=> [ 'en_US' => 'Social links', 'de_DE' => 'Social-Media-Links' ],
	'description'	=> [
		'en_US' => 'Social links lets you link to the profiles a site keeps elsewhere, each with its icon. The editors keep them as an element type under Elements. A template draws them where it asks for them.',
		'de_DE' => 'Social-Media-Links ermöglichen Links zu den Profilen, die eine Website anderswo führt, jeder mit seinem Icon. Die Redaktion pflegt sie als Elementtyp unter Elemente. Gezeichnet werden sie, wo immer ein Template danach fragt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[social]' => [
				'en_US' => 'Draws every link that is not hidden as a row of icons in the order of the field Position; only="instagram,youtube" takes only these, exclude="telegram" leaves some out.',
				'de_DE' => 'Zeichnet alle nicht ausgeblendeten Links als Reihe von Icons in der Reihenfolge des Felds Position; only="instagram,youtube" nimmt nur diese, exclude="telegram" lässt welche weg.',
			],
			'[social show="both" size="large" class="my-class"]' => [
				'en_US' => 'show chooses icon, both (icon and name) or label (the name alone), size small or large, and class adds a class of your own.',
				'de_DE' => 'show wählt icon, both (Icon und Name) oder label (nur der Name), size small oder large, und class hängt eine eigene Klasse an.',
			],
			'[social-link instagram]' => [
				'en_US' => 'One link with icon and name, for running text; id="instagram" is the same in the form the Builder writes, and show and class work here too.',
				'de_DE' => 'Ein einzelner Link mit Icon und Name, für den Fließtext; id="instagram" ist dieselbe Form, wie der Builder sie schreibt, und show und class gehen auch hier.',
			],
			'[social-icon telegram]' => [
				'en_US' => 'The icon alone, for markup of your own, such as [social-icon name="[[icon]]"] inside [elements /social]; class adds a class of your own to the wrapper.',
				'de_DE' => 'Nur das Icon, für eigenes Markup, etwa in [elements /social] als [social-icon name="[[icon]]"]; class hängt eine eigene Klasse an den Wrapper.',
			],
		],
		'markup' => [
			'target="_blank" rel="me noopener"' => [
				'en_US' => 'On every http(s) link and no other; javascript:, data: and addresses with a space are checked and left out.',
				'de_DE' => 'Steht an jedem http(s)-Link und an keinem anderen; javascript:, data: und Adressen mit Leerzeichen werden geprüft und bleiben weg.',
			],
			'href="/contact"' => [
				'en_US' => 'A path of the website is written without the directory and gets the directory the website is served from.',
				'de_DE' => 'Ein Pfad der Website wird ohne das Verzeichnis geschrieben und bekommt das Verzeichnis, in dem die Website liegt.',
			],
			'--social-size, --social-gap, --social-stroke, --social-icon, --social-badge' => [
				'en_US' => 'The properties of the look of the links, which a project sets in its own css on :root, or which a look of the Design feature writes.',
				'de_DE' => 'Die Eigenschaften für den Look der Links, die ein Projekt in seinem CSS auf :root setzt, oder die ein Look des Designs schreibt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the social links' => [
				'en_US' => 'With the Design feature Social offers the looks Plain, Badges and Outline, a select for the hover and knobs for size, stroke, space and colour.',
				'de_DE' => 'Mit dem Design bietet Social die Looks Schlicht, Plaketten und Umriss, eine Auswahl für den Hover und Regler für Größe, Strich, Abstand und Farbe.',
			],
		],
		'callbacks' => [],
		'install' => [
			'elements/social.php' => [
				'en_US' => 'The element type Social Media with four links to edit, hide or delete, under Elements.',
				'de_DE' => 'Der Elementtyp Social Media mit vier Links zum Bearbeiten, Ausblenden oder Löschen, unter Elemente.',
			],
			'templates/social-links.tpl' => [
				'en_US' => 'Holds [social], for a template of the project to include; the frames of the Design feature do not include it.',
				'de_DE' => 'Enthält [social], damit ein Template des Projekts es einbinden kann; die Rahmen des Designs binden es nicht ein.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the labels of the type\'s fields into the Elements panel, only added, never replaced.',
				'de_DE' => 'Bringt die Beschriftungen der Typfelder ins Panel Elemente, nur hinzugefügt, nie ersetzt.',
			],
			'icons/*.svg (not installed)' => [
				'en_US' => 'One Lucide icon per option, named after its purpose; replacing icons/telegram.svg changes every Telegram link.',
				'de_DE' => 'Ein Lucide-Icon je Auswahl, benannt nach dem Zweck; wer icons/telegram.svg ersetzt, ändert jeden Telegram-Link.',
			],
		],
	],
	// It brings content an editor maintains, the reasoning Gallery files
	// itself under
	'category'		=> 'content',
	'version'			=> '1.2.0',
	'nino'				=> '^1.9',
	'requires'		=> [],
	// The links are elements, in /elements/social.php - the project's own type
	// file, which a backup carries like every other
	'data'				=> [],
	// Into the site's style bundle, behind the kernel's own files
	'assets'			=> [ '/features/Social/assets/social.css' ],
	// Nothing to set: which links, how many and how large is said where they
	// are drawn, and a header and a footer want different answers
	'settings'		=> [],
	/*	The three shortcodes as the Builder offers them: [social-link] and [social-icon]
		name their link and their icon by an attribute, and by the first argument where
		a call writes it bare	*/
	'components'	=> [
		'social' => [
			'label'			=> [ 'en_US' => 'Social links', 'de_DE' => 'Social-Links' ],
			'source'		=> 'none',
			'attributes'	=> [
				'only' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Only these', 'de_DE' => 'Nur diese' ],
					'hint'		=> [
						'en_US' => 'Element IDs separated by commas, such as instagram,youtube. The order stays the one of the field Position.',
						'de_DE' => 'Element-IDs, durch Komma getrennt, etwa instagram,youtube. Die Reihenfolge bleibt die des Felds Position.',
					],
				],
				'exclude' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Leave out', 'de_DE' => 'Weglassen' ],
					'hint'		=> [
						'en_US' => 'Element IDs separated by commas.',
						'de_DE' => 'Element-IDs, durch Komma getrennt.',
					],
				],
				'show' => [
					'type'		=> 'select',
					'options'	=> [ 'icon', 'both', 'label' ],
					'default'	=> 'icon',
					'label'		=> [ 'en_US' => 'Shown', 'de_DE' => 'Gezeigt' ],
					'hint'		=> [
						'en_US' => 'icon, both (icon and name) or label (the name alone).',
						'de_DE' => 'icon, both (Icon und Name) oder label (nur der Name).',
					],
				],
				'size' => [
					'type'		=> 'variant',
					'options'	=> [ '', 'small', 'large' ],
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Size', 'de_DE' => 'Größe' ],
					'hint'		=> [
						'en_US' => 'Empty takes the size of the stylesheet.',
						'de_DE' => 'Leer nimmt die Größe des Stylesheets.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'ul .nino-social',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'circle', [ 'cx' => '18', 'cy' => '5', 'r' => '3' ] ],
				[ 'circle', [ 'cx' => '6', 'cy' => '12', 'r' => '3' ] ],
				[ 'circle', [ 'cx' => '18', 'cy' => '19', 'r' => '3' ] ],
				[ 'line', [ 'x1' => '8.59', 'x2' => '15.42', 'y1' => '13.51', 'y2' => '17.49' ] ],
				[ 'line', [ 'x1' => '15.41', 'x2' => '8.59', 'y1' => '6.51', 'y2' => '10.49' ] ],
			],
		],
		'social-link' => [
			'label'			=> [ 'en_US' => 'Social link', 'de_DE' => 'Social-Link' ],
			'source'		=> 'none',
			'attributes'	=> [
				'id' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Link', 'de_DE' => 'Link' ],
					'hint'		=> [
						'en_US' => 'The element ID of one link, such as instagram. Without one nothing is drawn.',
						'de_DE' => 'Die Element-ID eines Links, etwa instagram. Ohne eine wird nichts gezeichnet.',
					],
				],
				'show' => [
					'type'		=> 'select',
					'options'	=> [ 'icon', 'both', 'label' ],
					'default'	=> 'both',
					'label'		=> [ 'en_US' => 'Shown', 'de_DE' => 'Gezeigt' ],
					'hint'		=> [
						'en_US' => 'icon, both (icon and name) or label (the name alone).',
						'de_DE' => 'icon, both (Icon und Name) oder label (nur der Name).',
					],
				],
			],
			'preview'		=> 'button',
			'draws'			=> 'a .nino-social-link',
			'stretched'	=> true,
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71' ] ],
				[ 'path', [ 'd' => 'M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71' ] ],
			],
		],
		'social-icon' => [
			'label'			=> [ 'en_US' => 'Social icon', 'de_DE' => 'Social-Icon' ],
			'source'		=> 'none',
			'attributes'	=> [
				'name' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Icon', 'de_DE' => 'Icon' ],
					'hint'		=> [
						'en_US' => 'The name of the icon, such as telegram. One the feature does not have draws the link icon.',
						'de_DE' => 'Der Name des Icons, etwa telegram. Eines, das das Feature nicht hat, zeichnet das Link-Icon.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'span .nino-social-icon',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'circle', [ 'cx' => '12', 'cy' => '12', 'r' => '4' ] ],
				[ 'path', [ 'd' => 'M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8' ] ],
			],
		],
	],
];
