<?php
// features/Social/feature.php - what the Features panel reads. The class is
// not declared here: features/Social/ can only ever serve
// \Nino\Modules\Social.
return [
	'key'					=> 'social',
	'name'				=> [ 'en_US' => 'Social links', 'de_DE' => 'Social-Media-Links' ],
	'description'	=> [
		'en_US' => 'Links to the profiles a site keeps elsewhere, each with its icon - an element type the editors keep under Elements, drawn wherever a template asks for it.',
		'de_DE' => 'Links zu den Profilen, die eine Website anderswo führt, jeder mit seinem Icon - ein Elementtyp, den die Redaktion unter Elemente pflegt, gezeichnet, wo immer ein Template danach fragt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[social]' => [
				'en_US' => 'Every link that is not hidden, as a row of icons in the order of the field Position. The name is there for screen readers.',
				'de_DE' => 'Alle nicht ausgeblendeten Links als Reihe von Icons, in der Reihenfolge des Felds Position. Der Name steht für Screenreader da.',
			],
			'[social only="instagram,youtube"]' => [
				'en_US' => 'Only these, by element ID; exclude="telegram" leaves some out instead. The order stays the one of the field Position.',
				'de_DE' => 'Nur diese, nach Element-ID; exclude="telegram" lässt stattdessen welche weg. Die Reihenfolge bleibt die des Felds Position.',
			],
			'[social show="both" size="large"]' => [
				'en_US' => 'show: icon (the default), both (icon and name) or label (the name alone). size: small or large.',
				'de_DE' => 'show: icon (Standard), both (Icon und Name) oder label (nur der Name). size: small oder large.',
			],
			'[social-link instagram]' => [
				'en_US' => 'One link with icon and name, for running text. show works here too.',
				'de_DE' => 'Ein einzelner Link mit Icon und Name, für den Fließtext. show geht auch hier.',
			],
			'[social-link id="instagram"]' => [
				'en_US' => 'The same as [social-link instagram], the form the Builder writes.',
				'de_DE' => 'Dasselbe wie [social-link instagram], die Form, die der Builder schreibt.',
			],
			'[social-icon telegram]' => [
				'en_US' => 'The icon alone, for markup of your own - inside [elements /social] as [social-icon name="[[icon]]"].',
				'de_DE' => 'Nur das Icon, für eigenes Markup - in [elements /social] als [social-icon name="[[icon]]"].',
			],
			'[social-icon name="telegram"]' => [
				'en_US' => 'The same as [social-icon telegram], the form the Builder writes.',
				'de_DE' => 'Dasselbe wie [social-icon telegram], die Form, die der Builder schreibt.',
			],
		],
		'markup' => [
			'rel="me"' => [
				'en_US' => 'On every http(s) link, so a profile that links back can verify it is the site\'s. Every address is checked first: javascript:, data: and anything with a space is left out without a word.',
				'de_DE' => 'An jedem http(s)-Link, damit ein Profil, das zurückverlinkt, prüfen kann, dass es das der Seite ist. Jede Adresse wird vorher geprüft: javascript:, data: und alles mit Leerzeichen bleibt ohne Meldung weg.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'elements/social.php' => [
				'en_US' => 'The element type Social Media with four links to edit, hide or delete - under Elements.',
				'de_DE' => 'Der Elementtyp Social Media mit vier Links zum Bearbeiten, Ausblenden oder Löschen - unter Elemente.',
			],
			'templates/social-links.tpl' => [
				'en_US' => 'Holds [social]. The Design feature\'s header and footer frames include it, and draw nothing where it is missing.',
				'de_DE' => 'Enthält [social]. Die Header- und Footer-Rahmen des Design-Features binden es ein und zeichnen nichts, wo es fehlt.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The labels of the type\'s fields in the Elements panel.',
				'de_DE' => 'Die Beschriftungen der Felder des Typs im Panel Elemente.',
			],
			'icons/*.svg (not installed)' => [
				'en_US' => 'One Lucide icon per option, named after what it is for: replacing icons/telegram.svg changes every Telegram link. Never typed by an editor - an element picks one by name.',
				'de_DE' => 'Ein Lucide-Icon je Auswahl, benannt nach dem Zweck: Wer icons/telegram.svg ersetzt, ändert jeden Telegram-Link. Nie von einem Redakteur getippt – ein Element wählt eines mit seinem Namen.',
			],
		],
	],
	// It brings content an editor maintains, the reasoning Gallery files
	// itself under
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// The links are elements, in /elements/social.php - the project's own type
	// file, which a backup carries like every other
	'data'				=> [],
	// Nothing to set: which links, how many and how large is said where they
	// are drawn, and a header and a footer want different answers
	'settings'		=> [],
	/*	The three shortcodes as the Builder offers them: [social-link] and [social-icon]
		name their link and their icon by an attribute, and by the first argument where
		a call writes it bare. Nino 1.5 ignores the key	*/
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
					'type'		=> 'select',
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
		],
	],
];
