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
			'[social ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the list. What the Builder writes for its custom classes, such as its spacing classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen der Liste. Das schreibt der Builder für seine eigenen Klassen, etwa die Abstands-Klassen.',
			],
			'[social-link instagram]' => [
				'en_US' => 'One link with icon and name, for running text. show works here too.',
				'de_DE' => 'Ein einzelner Link mit Icon und Name, für den Fließtext. show geht auch hier.',
			],
			'[social-link id="instagram"]' => [
				'en_US' => 'The same as [social-link instagram], the form the Builder writes.',
				'de_DE' => 'Dasselbe wie [social-link instagram], die Form, die der Builder schreibt.',
			],
			'[social-link instagram] in [card linked="1"]' => [
				'en_US' => 'A lone link in a card whose whole surface is the link is that link - as a component of the Builder and as the shortcode in an HTML+ block or the template of the card; the links of [social] are not.',
				'de_DE' => 'Ein einzelner Link in einer Karte, deren ganze Fläche der Link ist, ist deren Link - als Komponente des Builders wie als Shortcode in einem HTML+-Block oder im Template der Karte; die Links von [social] sind es nicht.',
			],
			'[social-link ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the link. What the Builder writes for its custom classes, such as its spacing classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Links. Das schreibt der Builder für seine eigenen Klassen, etwa die Abstands-Klassen.',
			],
			'[social-icon telegram]' => [
				'en_US' => 'The icon alone, for markup of your own - inside [elements /social] as [social-icon name="[[icon]]"].',
				'de_DE' => 'Nur das Icon, für eigenes Markup - in [elements /social] als [social-icon name="[[icon]]"].',
			],
			'[social-icon name="telegram"]' => [
				'en_US' => 'The same as [social-icon telegram], the form the Builder writes.',
				'de_DE' => 'Dasselbe wie [social-icon telegram], die Form, die der Builder schreibt.',
			],
			'[social-icon ... class="my-class"]' => [
				'en_US' => 'A class of your own, on the wrapper of the icon. What the Builder writes for its custom classes, such as its spacing classes.',
				'de_DE' => 'Eine eigene Klasse, am Wrapper des Icons. Das schreibt der Builder für seine eigenen Klassen, etwa die Abstands-Klassen.',
			],
		],
		'markup' => [
			'target="_blank" rel="me noopener"' => [
				'en_US' => 'On every http(s) link, and on no other: it opens in a new tab, noopener keeps that tab from reaching back into the site, and rel="me" lets a profile that links back verify it is the site\'s. A path of the website, mailto: and tel: carry neither. Every address is checked first: javascript:, data: and anything with a space is left out without a word.',
				'de_DE' => 'An jedem http(s)-Link und an keinem anderen: Er öffnet in einem neuen Tab, noopener hindert diesen Tab daran, auf die Seite zuzugreifen, und rel="me" lässt ein Profil, das zurückverlinkt, prüfen, dass es das der Seite ist. Ein Pfad der Website, mailto: und tel: tragen weder das eine noch das andere. Jede Adresse wird vorher geprüft: javascript:, data: und alles mit Leerzeichen bleibt ohne Meldung weg.',
			],
			'href="/contact"' => [
				'en_US' => 'A link that is a path of the website is written without the directory (/contact, not /shop/contact) and gets the directory the website is served from: on a website at /shop it leads to /shop/contact.',
				'de_DE' => 'Ein Link, der ein Pfad der Website ist, wird ohne das Verzeichnis geschrieben (/kontakt, nicht /shop/kontakt) und bekommt das Verzeichnis, in dem die Website liegt: Auf einer Website unter /shop führt er nach /shop/kontakt.',
			],
			'--social-size, --social-gap, --social-stroke, --social-icon, --social-badge' => [
				'en_US' => 'The properties of the look of the links: the size of an icon, --social-size (1.5em; size="small" and size="large" are three quarters and four thirds of it), the space between two links, --social-gap (1rem), the weight of the line, --social-stroke (2), the tone of the icons, --social-icon (the colour of the link) and the ground of a badge, --social-badge (a surface of the palette, with --social-badge-on for the icon on it and --social-badge-border for its line). Without the Design feature a project sets them in its own css, on :root; with it, a look and its knobs write them.',
				'de_DE' => 'Die Eigenschaften für den Look der Links: die Größe eines Icons, --social-size (1.5em; size="small" und size="large" sind drei Viertel und vier Drittel davon), der Abstand zweier Links, --social-gap (1rem), die Stärke der Linie, --social-stroke (2), der Ton der Icons, --social-icon (die Farbe des Links) und der Grund einer Plakette, --social-badge (eine Fläche der Palette, mit --social-badge-on für das Icon darauf und --social-badge-border für ihre Linie). Ohne das Feature Design setzt ein Projekt sie in seinem eigenen CSS, auf :root; mit ihm schreiben ein Look und seine Regler sie.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the social links' => [
				'en_US' => 'With the Design feature on, the tab Social links of the Design panel offers under Base three looks - Plain, as the links have always been drawn, Badges, an icon on a round ground, and Outline, an icon in a circle of line - and a select Hover: None, Lift or Tint. The knobs stand under Text (the size of an icon, the weight of the line), Surface (the space between the links) and Colour (in Badges and Outline the colour of the icons and, in Badges only, the ground of a badge; Outline has none, its line takes the colour of the icon; Plain has no colour, its icons stay the colour of the link). On a section of the ground Page, alternate only the line of a badge shows - choose another ground there. size="small" and size="large" of [social] are three quarters and four thirds of the size the knob sets, not sizes of their own. The block design/preview.tpl shows four links in the preview.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Social-Media-Links des Design-Panels unter Grundlage drei Looks an - Schlicht, wie die Links immer gezeichnet wurden, Plaketten, ein Icon auf rundem Grund, und Umriss, ein Icon in einem Kreis aus Linie - und eine Auswahl Hover: Keiner, Heben oder Tönen. Die Regler stehen unter Text (Größe eines Icons, Strichstärke), Fläche (der Abstand der Links) und Farbe (bei Plaketten und Umriss die Farbe der Icons und, nur bei Plaketten, der Grund einer Plakette; Umriss hat keinen, seine Linie nimmt die Farbe des Icons; Schlicht hat keine, seine Icons bleiben in der Farbe des Links). Auf einer Sektion mit dem Grund Seite alternativ zeigt sich nur die Linie einer Plakette - dort wählst Du einen anderen Grund. size="small" und size="large" bei [social] sind drei Viertel und vier Drittel der Größe, die der Regler setzt, keine eigenen Größen. Der Block design/preview.tpl zeigt vier Links in der Vorschau.',
			],
		],
		'callbacks' => [],
		'install' => [
			'elements/social.php' => [
				'en_US' => 'The element type Social Media with four links to edit, hide or delete - under Elements.',
				'de_DE' => 'Der Elementtyp Social Media mit vier Links zum Bearbeiten, Ausblenden oder Löschen - unter Elemente.',
			],
			'templates/social-links.tpl' => [
				'en_US' => 'Holds [social], for a template of the project to include. The frames of the Design feature do not include it: a frame that wants the links writes [social] into the project\'s frame-header.tpl or frame-footer.tpl - the next apply of Design replaces those files and names [social] among the shortcodes it would lose.',
				'de_DE' => 'Enthält [social], damit ein Template des Projekts es einbinden kann. Die Rahmen des Design-Features binden es nicht ein: Ein Rahmen, der die Links will, schreibt [social] in die frame-header.tpl oder frame-footer.tpl des Projekts - das nächste Apply von Design ersetzt diese Dateien und nennt [social] unter den Shortcodes, die es verlöre.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The labels of the type\'s fields in the Elements panel. They are added, never replaced: a project that has the feature already keeps the label of the field Link until it changes it by hand.',
				'de_DE' => 'Die Beschriftungen der Felder des Typs im Panel Elemente. Sie werden nur hinzugefügt, nie ersetzt: Ein Projekt, das das Feature schon hat, behält die Beschriftung des Felds Link, bis es sie von Hand ändert.',
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
			'in'			=> [ 'section', 'card', 'hero' ],
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
			'in'			=> [ 'section', 'card', 'hero' ],
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
			'in'			=> [ 'section', 'card', 'hero' ],
			'icon'			=> [
				[ 'circle', [ 'cx' => '12', 'cy' => '12', 'r' => '4' ] ],
				[ 'path', [ 'd' => 'M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8' ] ],
			],
		],
	],
];
