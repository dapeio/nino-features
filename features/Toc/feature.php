<?php
// features/Toc/feature.php - what the Features panel reads. The class is not
// declared here: features/Toc/ can only ever serve \Nino\Modules\Toc.
return [
	'key'					=> 'toc',
	'name'				=> [ 'en_US' => 'Table of Contents', 'de_DE' => 'Inhaltsverzeichnis' ],
	'description'	=> [
		'en_US' => 'A list of a long page\'s own headings, built from the page as it stands, that says which section is being read - and an anchor on every heading, so a passage can be linked to.',
		'de_DE' => 'Eine Liste der eigenen Überschriften einer langen Seite, aus der Seite selbst gebaut, die zeigt, welcher Abschnitt gerade gelesen wird - und ein Anker an jeder Überschrift, damit sich eine Stelle verlinken lässt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[toc]' => [
				'en_US' => 'The list, built from the headings that follow it on the page.',
				'de_DE' => 'Die Liste, gebaut aus den Überschriften, die ihr auf der Seite folgen.',
			],
			'[toc levels="2"]' => [
				'en_US' => 'Only h2. Default is h2 and h3.',
				'de_DE' => 'Nur h2. Vorgabe sind h2 und h3.',
			],
			'[toc within="#content"]' => [
				'en_US' => 'Take the headings from inside one element rather than from the whole page.',
				'de_DE' => 'Nimmt die Überschriften aus einem Element statt von der ganzen Seite.',
			],
			'[toc title="Auf dieser Seite"]' => [
				'en_US' => 'What stands over the list. Without it the Text panel\'s own words are used.',
				'de_DE' => 'Was über der Liste steht. Ohne die Angabe werden die Wörter aus dem Panel Texte verwendet.',
			],
			'[toc ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the list. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen der Liste. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'class="nino-toc-skip"' => [
				'en_US' => 'On a heading: left out of the list, and given no anchor.',
				'de_DE' => 'An einer Überschrift: bleibt aus der Liste und bekommt keinen Anker.',
			],
			'id="…" on a listed heading' => [
				'en_US' => 'Every heading in the list gets an id made from its own words; an id the page already had is kept exactly as it was. Two headings with the same words get kontakt and kontakt-2.',
				'de_DE' => 'Jede Überschrift der Liste bekommt eine id aus ihren eigenen Worten; eine id, die die Seite schon hatte, bleibt, wie sie war. Zwei Überschriften mit denselben Worten bekommen kontakt und kontakt-2.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the list' => [
				'en_US' => 'With the Design feature on, the tab Table of Contents of the Design panel offers under Base three looks of the list - Line, as it has always been drawn, Box and Numbered - and a select for the place: None, or Sticky, which keeps the list at the top of the window from 1024 px up, for as long as the column it stands in reaches - a header that slides back in while the page scrolls up covers it. The knobs stand under Surface (the thickness of the line, the space inside, the indent of the sub-levels) and Colour (the colour of the line, the ground of the box); a knob the look does not answer to is greyed out - Numbered has no line. Line draws the list as it is without Design. A box on a section of its own ground is told apart by its line. The markup stays that of [toc]. The block design/preview.tpl shows a title and three entries in the preview; Apply to website puts the look on the site.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Inhaltsverzeichnis des Design-Panels unter Grundlage drei Looks der Liste an - Linie, wie sie immer gezeichnet wurde, Kasten und Nummeriert - und eine Auswahl für den Platz: Keiner oder Klebt am Rand, das die Liste ab 1024 px oben im Fenster hält, solange die Spalte reicht, in der sie steht - ein Header, der beim Hochscrollen zurückkommt, verdeckt sie. Die Regler stehen unter Fläche (die Stärke der Linie, der Innenabstand, der Einzug der Unterebenen) und Farbe (die Farbe der Linie, der Grund des Kastens); ein Regler, auf den der Look nicht antwortet, steht ausgegraut da - Nummeriert hat keine Linie. Linie zeichnet die Liste wie ohne Design. Ein Kasten auf einer Sektion mit seinem eigenen Grund hebt sich durch seine Linie ab. Das Markup bleibt das von [toc]. Der Block design/preview.tpl zeigt einen Titel und drei Einträge in der Vorschau; Auf Website anwenden bringt den Look auf die Site.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The two words the list and its anchors carry, into the Text panel.',
				'de_DE' => 'Die zwei Wörter der Liste und ihrer Anker, ins Panel Texte.',
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
