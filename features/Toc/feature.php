<?php
// features/Toc/feature.php - what the Features panel reads. The class is not
// declared here: features/Toc/ can only ever serve \Nino\Modules\Toc.
return [
	'key'					=> 'toc',
	'name'				=> [ 'en_US' => 'Table of Contents', 'de_DE' => 'Inhaltsverzeichnis' ],
	'description'	=> [
		'en_US' => 'Table of Contents lets you list the headings of a long page, built from the page itself. It shows which section is being read. Every heading gets an anchor, so a passage can be linked to.',
		'de_DE' => 'Das Inhaltsverzeichnis ermöglicht eine Liste der Überschriften einer langen Seite, aus der Seite selbst gebaut. Sie zeigt, welcher Abschnitt gerade gelesen wird. Jede Überschrift bekommt einen Anker, damit sich eine Stelle verlinken lässt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[toc]' => [
				'en_US' => 'Builds the list from the headings that follow it on the page; the default is h2 and h3.',
				'de_DE' => 'Baut die Liste aus den Überschriften, die ihr auf der Seite folgen; Standard sind h2 und h3.',
			],
			'[toc levels="2" within="#content" title="Auf dieser Seite" class="my-class"]' => [
				'en_US' => 'Takes h2 only, the headings from one element, sets the title over the list and adds a class of your own.',
				'de_DE' => 'Nimmt nur h2, die Überschriften aus einem Element, setzt den Titel über der Liste und hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'class="nino-toc-skip"' => [
				'en_US' => 'On a heading it is left out of the list and gets no anchor.',
				'de_DE' => 'An einer Überschrift bleibt sie aus der Liste und bekommt keinen Anker.',
			],
			'id="…" on a listed heading' => [
				'en_US' => 'Every listed heading gets an id made from its words, an id already there is kept, and two equal headings become kontakt and kontakt-2.',
				'de_DE' => 'Jede Überschrift der Liste bekommt eine id aus ihren Worten, eine vorhandene id bleibt, und zwei gleiche Überschriften werden kontakt und kontakt-2.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the list' => [
				'en_US' => 'With the Design feature the list offers the looks Line, Box and Numbered, a select for the place (also sticky) and knobs for surface and colour.',
				'de_DE' => 'Mit dem Design bietet die Liste die Looks Linie, Kasten und Nummeriert, eine Auswahl für den Platz (auch klebend am Rand) und Regler für Fläche und Farbe.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the two words of the list and its anchors into the Text panel.',
				'de_DE' => 'Bringt die zwei Wörter der Liste und ihrer Anker ins Panel Texte.',
			],
		],
	],
	// It is a way through what is already written on a page, which is the
	// thing the Features panel files under content
	'category'		=> 'content',
	'version'			=> '1.2.0',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing under data/: the list is the page, read as it stands
	'data'				=> [],
	// Into the site's style and script bundle, behind the kernel's own files
	'assets'			=> [ '/features/Toc/assets/toc.css', '/features/Toc/assets/toc.js' ],
	'settings'		=> [
		'anchors' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Anchors on every heading', 'de_DE' => 'Anker an jeder Überschrift' ],
			'hint'		=> [
				'en_US' => 'Put a link on every heading the list holds, so a passage can be linked to. Off: no link is drawn, and those headings keep only the ids the list needs to reach them.',
				'de_DE' => 'Setzt an jede Überschrift der Liste einen Link, damit sich eine Stelle verlinken lässt. Aus: Es wird kein Link gezeichnet, und die Überschriften behalten nur die ids, über die die Liste sie erreicht.',
			],
			'default'	=> true,
		],
	],
	/*	The shortcode as the Builder offers it	*/
	'components'	=> [
		'toc' => [
			'label'			=> [ 'en_US' => 'Table of contents', 'de_DE' => 'Inhaltsverzeichnis' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'levels' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Levels', 'de_DE' => 'Ebenen' ],
					'hint'		=> [
						'en_US' => 'The heading levels the list is built from, separated by commas: 2 or 2,3. Empty takes h2 and h3.',
						'de_DE' => 'Die Überschriftsebenen, aus denen die Liste gebaut wird, durch Komma getrennt: 2 oder 2,3. Leer nimmt h2 und h3.',
					],
				],
				'within' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Taken from', 'de_DE' => 'Entnommen aus' ],
					'hint'		=> [
						'en_US' => 'A selector such as #content: the headings come from inside that element. Empty takes the whole page.',
						'de_DE' => 'Ein Selektor wie #content: Die Überschriften kommen aus diesem Element. Leer nimmt die ganze Seite.',
					],
				],
				'title' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Title', 'de_DE' => 'Titel' ],
					'hint'		=> [
						'en_US' => 'What stands over the list. Empty takes the words of the Text panel.',
						'de_DE' => 'Was über der Liste steht. Leer nimmt die Wörter aus dem Panel Texte.',
					],
				],
			],
			'preview'		=> 'text',
			'draws'			=> 'nav .nino-toc',
			'in'			=> [ 'section' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M16 5H3' ] ],
				[ 'path', [ 'd' => 'M16 12H3' ] ],
				[ 'path', [ 'd' => 'M16 19H3' ] ],
				[ 'path', [ 'd' => 'M21 5h.01' ] ],
				[ 'path', [ 'd' => 'M21 12h.01' ] ],
				[ 'path', [ 'd' => 'M21 19h.01' ] ],
			],
		],
	],
];
