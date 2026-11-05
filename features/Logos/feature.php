<?php
// features/Logos/feature.php - what the Features panel reads. The class is not
// declared here: features/Logos/ can only ever serve \Nino\Modules\Logos.
return [
	'key'					=> 'logos',
	'name'				=> [ 'en_US' => 'Logos', 'de_DE' => 'Logos' ],
	'description'	=> [
		'en_US' => 'A row of logos - the customers a site is proud of - drawn from the elements of one type that the editors keep under Elements, in four looks of the Design feature: a row, a grid, a moving band and cards.',
		'de_DE' => 'Eine Reihe von Logos - die Kunden, auf die eine Website stolz ist -, gezeichnet aus den Elementen eines Typs, den die Redaktion unter Elemente pflegt, in vier Looks des Features Design: Reihe, Raster, laufendes Band und Karten.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[logos]' => [
				'en_US' => 'Every element of the type logo that has a picture, as a row of logos, by the field order - the smaller position first, a logo with none, an empty one or 0 last. A logo with an address is a link. The Builder offers it as Logos.',
				'de_DE' => 'Jedes Element des Typs logo, das ein Bild hat, als Reihe von Logos, nach dem Feld order - die kleinere Position zuerst, ein Logo ohne eine, mit leerer oder mit 0 zuletzt. Ein Logo mit Adresse ist ein Link. Der Builder bietet es als Logos an.',
			],
			'[logos type="logo" limit="8"]' => [
				'en_US' => 'type: the element type that is read, logo where left out. It needs the fields name (plain text: a field with html or breaks draws nothing), image, url and, optionally, image-dark and order: a project that wants two sets of logos copies the type. limit: how many, 0 for all, up to 200.',
				'de_DE' => 'type: der Elementtyp, der gelesen wird, ohne Angabe logo. Er braucht die Felder name (einfacher Text: ein Feld mit html oder breaks zeichnet nichts), image, url und, wenn gewünscht, image-dark und order: Wer zwei Sätze Logos will, kopiert den Typ. limit: wie viele, 0 für alle, bis 200.',
			],
			'[logos sort="name"]' => [
				'en_US' => 'The order of the loop of [elements]: a field, ascending, -name descending, several joined by commas; sort="" keeps the order of the elements. Without the attribute it is order, which puts a logo with no position, an empty one or 0 last: the Elements panel saves an empty field as 0.',
				'de_DE' => 'Die Reihenfolge der Schleife von [elements]: ein Feld aufsteigend, -name absteigend, mehrere durch Komma getrennt; sort="" behält die Reihenfolge der Elemente. Ohne das Attribut gilt order, das ein Logo ohne Position, mit leerer oder mit 0 ans Ende stellt: Das Elemente-Panel speichert ein leeres Feld als 0.',
			],
			'[logos link="0"]' => [
				'en_US' => 'No logo is a link, whatever its address says. A link is drawn only for an https, http or mailto address or a path of the site that starts with one slash; any other address is left out, not changed.',
				'de_DE' => 'Kein Logo ist ein Link, was auch immer seine Adresse sagt. Ein Link entsteht nur für eine https-, http- oder mailto-Adresse oder einen Pfad der Site mit einem Schrägstrich am Anfang; jede andere Adresse bleibt weg, sie wird nicht verändert.',
			],
			'[logos height="l"]' => [
				'en_US' => 'The height of the logos: s is three quarters of the height of the look, m the height of the look, l one and a half times. The height of the look is the knob Height under Design.',
				'de_DE' => 'Die Höhe der Logos: s ist drei Viertel der Höhe des Looks, m die Höhe des Looks, l das Anderthalbfache. Die Höhe des Looks ist der Regler Höhe unter Design.',
			],
			'[logos ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the row. What the Builder writes for its custom classes, such as its spacing classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen der Reihe. Das schreibt der Builder für seine eigenen Klassen, etwa die Abstands-Klassen.',
			],
		],
		'markup' => [
			'<div class="nino-logos-strip nino-logos-strip--h-m">' => [
				'en_US' => 'The row: --h-s, --h-m or --h-l after the height of the call, and the number of logos as --nino-logos-count on its style attribute, which the band reads for its speed. Inside it is nino-logos-strip-view with two lists, the logos and a copy of them, and a pause button, which logos.js shows only where the track runs an animation; its word is the data attribute data-logos-pause, and a press changes aria-pressed and the class nino-logos-strip--paused, not the word.',
				'de_DE' => 'Die Reihe: --h-s, --h-m oder --h-l nach der Höhe des Aufrufs, und die Zahl der Logos als --nino-logos-count im style-Attribut, das das Band für seine Geschwindigkeit liest. Darin stehen nino-logos-strip-view mit zwei Listen, den Logos und einer Kopie von ihnen, und ein Pause-Knopf, den logos.js nur zeigt, wo die Spur eine Animation hat; sein Wort steht im Attribut data-logos-pause, und ein Druck ändert aria-pressed und die Klasse nino-logos-strip--paused, nicht das Wort.',
			],
			'<li class="nino-logos-strip-item">' => [
				'en_US' => 'One logo. The picture is the kernel\'s logo set, nino-logo-set, with the picture of the field image as nino-logo-light and, where the element has one, the picture of image-dark as nino-logo-dark: the ground the row stands on says which is seen. The alt text is the name, the pictures load lazily and carry the size of the field. A link is nino-logos-strip-link with rel="noopener".',
				'de_DE' => 'Ein Logo. Das Bild ist das Logo-Set des Kernels, nino-logo-set, mit dem Bild des Feldes image als nino-logo-light und, wo das Element eines hat, dem Bild von image-dark als nino-logo-dark: Der Grund, auf dem die Reihe steht, bestimmt, welches zu sehen ist. Der Alternativtext ist der Name, die Bilder laden träge und tragen das Maß des Feldes. Ein Link ist nino-logos-strip-link mit rel="noopener".',
			],
			'<ul class="nino-logos-strip-copy" aria-hidden="true">' => [
				'en_US' => 'The copy of the list that the look Band runs behind the first one, so that the band has no seam. Every other look hides it with display: none. A screen reader skips it and the links in it are out of the order of the Tab key. Under the keyboard focus the band stops and stands at its start, so that every stop is in view.',
				'de_DE' => 'Die Kopie der Liste, die der Look Band hinter der ersten laufen lässt, damit das Band keine Naht hat. Jeder andere Look blendet sie mit display: none aus. Ein Screenreader überspringt sie, und die Links darin liegen außerhalb der Tab-Reihenfolge. Unter dem Tastaturfokus hält das Band an und steht an seinem Anfang, so dass jeder Stopp im Bild ist.',
			],
			'--logos-height, --logos-gap, --logos-corners, --logos-ground, --logos-speed' => [
				'en_US' => 'The properties of the look: the height of a logo at m (3.5rem), the space between logos (var(--space-4)), the corners of a cell (var(--radius-large)), its ground (a surface of the palette, with --logos-ground-on, -border, -shadow, -logo-light and -logo-dark) and the time a logo takes to pass in a band (5s). Without the Design feature a project sets them in its own css, on :root; with it, a look and its knobs write them.',
				'de_DE' => 'Die Eigenschaften des Looks: die Höhe eines Logos bei m (3.5rem), der Abstand zwischen den Logos (var(--space-4)), die Ecken einer Zelle (var(--radius-large)), ihr Grund (eine Fläche der Palette, mit --logos-ground-on, -border, -shadow, -logo-light und -logo-dark) und die Zeit, die ein Logo im Band zum Vorbeiziehen braucht (5s). Ohne das Feature Design setzt ein Projekt sie in seinem eigenen CSS, auf :root; mit ihm schreiben ein Look und seine Regler sie.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the logos' => [
				'en_US' => 'With the Design feature on, the tab Logos of the Design panel offers under Base four looks - Row, as the kernel drew it, Grid, Band and Cards - and a select for the style Tone: None is grey logos that take their colour under the pointer and under the keyboard focus, Colour leaves them as they are, Grey keeps them grey. The knobs are the height of a logo and the space between logos, the corners and the ground of a cell, and for Band its speed under Motion; a knob the look does not answer to is greyed out. Band runs the logos past and stops under the pointer; under the keyboard focus it stands at its start. It has a pause button of its own (Text key /feature/logos/action/pause), and under reduced motion it is a row that wraps. The feature Ticker runs anything.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Logos des Design-Panels unter Grundlage vier Looks an - Reihe, wie der Kernel sie zeichnete, Raster, Band und Karten - und eine Auswahl für den Stil Farbton: Keiner sind graue Logos, die unter dem Zeiger und dem Tastaturfokus ihre Farbe annehmen, Farbe lässt sie, wie sie sind, Grau hält sie grau. Die Regler sind die Höhe eines Logos und der Abstand der Logos, die Ecken und der Grund einer Zelle, beim Band seine Geschwindigkeit unter Bewegung; ein Regler, auf den der Look nicht antwortet, steht ausgegraut da. Das Band lässt die Logos vorbeiziehen und steht unter dem Zeiger still; unter dem Tastaturfokus steht es an seinem Anfang. Es hat einen eigenen Pause-Knopf (Text-Schlüssel /feature/logos/action/pause), und bei reduzierter Bewegung ist es eine umbrechende Reihe. Das Feature Ticker lässt beliebige Inhalte laufen.',
			],
		],
		'callbacks' => [
			'/logos/order' => [
				'en_US' => 'The callback a row without a sort of its own orders its logos by: smaller positions first, a logo whose position is empty, missing or 0 last, in the order the elements were found in; registered by init(), applied between the query and the limit of the loop.',
				'de_DE' => 'Der Callback, nach dem eine Reihe ohne eigene Sortierung ihre Logos ordnet: kleinere Positionen zuerst, ein Logo mit leerer, fehlender oder 0 als Position zuletzt, in der Reihenfolge der Elemente; von init() registriert, zwischen Query und Limit der Loop angewandt.',
			],
		],
		'install' => [
			'logo.php' => [
				'en_US' => 'The element type logo, copied to /elements where the project has none: name, image, url, image-dark and order, no element. The pictures are cropped to 540 by 210 pixels when they are uploaded, so a logo of another ratio loses its edges. After the activation, add the logos under Elements and write [logos] where the row belongs.',
				'de_DE' => 'Der Elementtyp logo, nach /elements kopiert, wo das Projekt keinen hat: name, image, url, image-dark und order, kein Element. Die Bilder werden beim Hochladen auf 540 mal 210 Pixel zugeschnitten, ein Logo eines anderen Seitenverhältnisses verliert also seine Ränder. Nach der Aktivierung legst Du die Logos unter Elemente an und schreibst [logos] dorthin, wo die Reihe stehen soll.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The labels of the fields of the type in the Elements panel (blacklisted: the workbench\'s words) and the word of the pause button of a band, into the Text panel: Pause logos.',
				'de_DE' => 'Die Beschriftungen der Felder des Typs im Elemente-Panel (auf der Blacklist: Wörter der Workbench) und das Wort des Pause-Knopfs eines Bandes, ins Panel Texte: Logos anhalten.',
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
	/*	The shortcode as the Builder offers it	*/
	'components'	=> [
		'logos' => [
			'label'			=> [ 'en_US' => 'Logos', 'de_DE' => 'Logos' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'type' => [
					'type'		=> 'string',
					'default'	=> 'logo',
					'label'		=> [ 'en_US' => 'Element type', 'de_DE' => 'Elementtyp' ],
					'hint'		=> [
						'en_US' => 'The type whose elements are the logos. It needs the fields name, image and url, and image-dark if there are pictures for a dark ground.',
						'de_DE' => 'Der Typ, dessen Elemente die Logos sind. Er braucht die Felder name, image und url, und image-dark, wenn es Bilder für einen dunklen Grund gibt.',
					],
				],
				'limit' => [
					'type'		=> 'int',
					'min'			=> 0,
					'max'			=> 200,
					'default'	=> 0,
					'label'		=> [ 'en_US' => 'Number', 'de_DE' => 'Anzahl' ],
					'hint'		=> [
						'en_US' => 'How many logos at most. 0 draws all of them.',
						'de_DE' => 'Wie viele Logos höchstens. 0 zeichnet alle.',
					],
				],
				'sort' => [
					'type'		=> 'string',
					'default'	=> 'order',
					'label'		=> [ 'en_US' => 'Order', 'de_DE' => 'Reihenfolge' ],
					'hint'		=> [
						'en_US' => 'A field to sort by: order, the position of the element, name, or -name for descending. Empty keeps the order of the elements.',
						'de_DE' => 'Ein Feld, nach dem sortiert wird: order, die Position des Elements, name, oder -name für absteigend. Leer behält die Reihenfolge der Elemente.',
					],
				],
				'link' => [
					'type'		=> 'bool',
					'default'	=> true,
					'label'		=> [ 'en_US' => 'Links', 'de_DE' => 'Links' ],
					'hint'		=> [
						'en_US' => 'A logo with an address is a link. Off, none is.',
						'de_DE' => 'Ein Logo mit Adresse ist ein Link. Aus: Keines ist ein Link.',
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
			],
			'preview'		=> 'image',
			'draws'			=> 'div .nino-logos-strip',
			'in'			=> [ 'section', 'hero' ],
			'icon'			=> [
				[ 'rect', [ 'width' => '18', 'height' => '18', 'x' => '3', 'y' => '3', 'rx' => '2' ] ],
				[ 'path', [ 'd' => 'M9 3v18' ] ],
				[ 'path', [ 'd' => 'M15 3v18' ] ],
			],
		],
	],
];
