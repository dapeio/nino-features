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
			'[logos /logo image="image" link="url" sort="order"]' => [
				'en_US' => 'The elements of the type logo that have a picture, as a row of logos, the smaller position first and a logo with no position last. A logo with only the picture for a dark ground is drawn too, and that picture is seen on every ground. A loop of the Components module, written as a single tag and closed nowhere: what stands between a pair of tags is not read. A logo with an address in the field url is a link. The Builder offers it as Logos, under Loop.',
				'de_DE' => 'Die Elemente des Typs logo, die ein Bild haben, als Reihe von Logos, die kleinere Position zuerst und ein Logo ohne Position zuletzt. Auch ein Logo mit nur dem Bild für dunklen Grund wird gezeichnet, und dieses Bild ist auf jedem Grund zu sehen. Ein Loop des Moduls Komponenten, als einzelnes Tag geschrieben und nirgends geschlossen: Was zwischen einem Paar von Tags steht, wird nicht gelesen. Ein Logo mit Adresse im Feld url ist ein Link. Der Builder bietet sie als Logos an, unter Loop.',
			],
			'[logos /logo image="image" link="url" name="name" dark="image-dark"]' => [
				'en_US' => 'The fields that make a logo, by name: image the picture, link the address, name the text of the picture, dark the picture for a dark ground. Defaults are image, url, name and image-dark, and a field left out is its default; a field written empty is none, so dark="" draws no second picture, name="" no alt text and link="" no link. A logo has no second picture where its element has none in that field or the type has no such field, and then the first is seen on every ground. A link, name or dark that is no field of the type draws no link, no alt text or no second picture, and no other field is read in its place; an image that is no picture field of the type draws nothing. Any type with a picture field works, so a project with two sets of logos copies the type.',
				'de_DE' => 'Die Felder, aus denen ein Logo besteht, mit Namen: image das Bild, link die Adresse, name der Text des Bildes, dark das Bild für dunklen Grund. Standard sind image, url, name und image-dark, und ein weggelassenes Feld ist sein Standard; ein leer geschriebenes Feld ist keines, dark="" zeichnet also kein zweites Bild, name="" keinen Alternativtext und link="" keinen Link. Ein Logo hat kein zweites Bild, wo sein Element keines in dem Feld hat oder der Typ das Feld nicht kennt, und dann ist das erste auf jedem Grund zu sehen. Ein Link, Name oder dark, das kein Feld des Typs ist, zeichnet keinen Link, keinen Alternativtext oder kein zweites Bild, und kein anderes Feld tritt an seine Stelle; ein image, das kein Bildfeld des Typs ist, zeichnet nichts. Jeder Typ mit einem Bildfeld geht, wer zwei Sätze Logos will, kopiert also den Typ.',
			],
			'[logos /logo sort="name" limit="8"]' => [
				'en_US' => 'The data are the loop of [elements]: sort by a field ascending, -name descending, several joined by commas, sort="" keeps the order of the elements; limit how many, 0 for all; offset and query as there. Without a sort the order is that of the elements. sort="order" puts a logo whose position is empty last; a position of 0 is a number and stands first.',
				'de_DE' => 'Die Daten sind der Loop von [elements]: sort nach einem Feld aufsteigend, -name absteigend, mehrere durch Komma getrennt, sort="" behält die Reihenfolge der Elemente; limit wie viele, 0 für alle; offset und query wie dort. Ohne sort gilt die Reihenfolge der Elemente. sort="order" stellt ein Logo mit leerer Position ans Ende; eine Position 0 ist eine Zahl und steht zuerst.',
			],
			'[logos /logo links="0"]' => [
				'en_US' => 'No logo is a link, whatever its address says; with the field of the link written empty, link="", none is either. A link is drawn only for an https, http or mailto address or a path of the site that starts with one slash; any other address is left out, not changed.',
				'de_DE' => 'Kein Logo ist ein Link, was auch immer seine Adresse sagt; mit leer geschriebenem Linkfeld, link="", ist auch keines einer. Ein Link entsteht nur für eine https-, http- oder mailto-Adresse oder einen Pfad der Site mit einem Schrägstrich am Anfang; jede andere Adresse bleibt weg, sie wird nicht verändert.',
			],
			'[logos /logo height="l"]' => [
				'en_US' => 'The height of the logos: s is three quarters of the height of the look, m the height of the look, l one and a half times. The height of the look is the knob Height under Design.',
				'de_DE' => 'Die Höhe der Logos: s ist drei Viertel der Höhe des Looks, m die Höhe des Looks, l das Anderthalbfache. Die Höhe des Looks ist der Regler Höhe unter Design.',
			],
			'[logos /logo ... class="my-class"]' => [
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
				'en_US' => 'One logo. The picture is the kernel\'s logo set, nino-logo-set, with the picture of the field image as nino-logo-light and, where the call names a field dark and the element has a picture in it, that picture as nino-logo-dark: the ground the row stands on says which is seen. The alt text is the name, the pictures load lazily and carry the size of the file they were set into the box as. A link is nino-logos-strip-link with rel="noopener".',
				'de_DE' => 'Ein Logo. Das Bild ist das Logo-Set des Kernels, nino-logo-set, mit dem Bild des Feldes image als nino-logo-light und, wo der Aufruf ein Feld dark nennt und das Element darin ein Bild hat, diesem Bild als nino-logo-dark: Der Grund, auf dem die Reihe steht, bestimmt, welches zu sehen ist. Der Alternativtext ist der Name, die Bilder laden träge und tragen das Maß der Datei, die in die Box eingepasst wurde. Ein Link ist nino-logos-strip-link mit rel="noopener".',
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
			'Builder: the presentation Logos' => [
				'en_US' => 'With the Builder feature on, Logos is a presentation of the block Loop in the dialog of a column, beside Grid and Tabs: the type and the data of the loop (query, sort, limit, offset; sort by order for the position of the logos), then the fields that make a logo - Picture, Link, Name and Picture for a dark ground, each a choice among the fields of the type, only the Picture required, and the Link while Links is on; for the others the choice None writes the field empty, and a logo then has no alt text or no second picture - the Height and Links. It has no content and no cells to place: the Builder writes the call as a single tag and leaves out what stands at its default.',
				'de_DE' => 'Ist das Feature Builder an, ist Logos eine Darstellung des Blocks Loop im Dialog einer Spalte, neben Raster und Tabs: der Typ und die Daten des Loops (Abfrage, Sortierung, Limit, Offset; für die Position nach order sortieren), dann die Felder, aus denen ein Logo besteht - Bild, Link, Name und Bild für dunklen Grund, jedes eine Auswahl unter den Feldern des Typs, verbindlich nur das Bild und der Link, solange Links an ist; bei den anderen schreibt die Wahl Keines das Feld leer, und ein Logo hat dann keinen Alternativtext oder kein zweites Bild - die Höhe und Links. Es hat keinen Inhalt und keine Zellen zu platzieren: Der Builder schreibt den Aufruf als einzelnes Tag und lässt weg, was auf seinem Standard steht.',
			],
			'Design: the looks of the logos' => [
				'en_US' => 'With the Design feature on, the tab Logos of the Design panel offers under Base four looks - Row, as the kernel drew it, Grid, Band and Cards - and a select for the style Tone: None is grey logos that take their colour under the pointer and under the keyboard focus, Colour leaves them as they are, Grey keeps them grey. The knobs are the height of a logo and the space between logos, the corners and the ground of a cell, and for Band its speed under Motion; a knob the look does not answer to is greyed out. Band runs the logos past and stops under the pointer; under the keyboard focus it stands at its start. It has a pause button of its own (Text key /feature/logos/action/pause), and under reduced motion it is a row that wraps. The feature Ticker runs anything.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Logos des Design-Panels unter Grundlage vier Looks an - Reihe, wie der Kernel sie zeichnete, Raster, Band und Karten - und eine Auswahl für den Stil Farbton: Keiner sind graue Logos, die unter dem Zeiger und dem Tastaturfokus ihre Farbe annehmen, Farbe lässt sie, wie sie sind, Grau hält sie grau. Die Regler sind die Höhe eines Logos und der Abstand der Logos, die Ecken und der Grund einer Zelle, beim Band seine Geschwindigkeit unter Bewegung; ein Regler, auf den der Look nicht antwortet, steht ausgegraut da. Das Band lässt die Logos vorbeiziehen und steht unter dem Zeiger still; unter dem Tastaturfokus steht es an seinem Anfang. Es hat einen eigenen Pause-Knopf (Text-Schlüssel /feature/logos/action/pause), und bei reduzierter Bewegung ist es eine umbrechende Reihe. Das Feature Ticker lässt beliebige Inhalte laufen.',
			],
		],
		'callbacks' => [],
		'install' => [
			'logo.php' => [
				'en_US' => 'The element type logo, copied to /elements where the project has none: name, image, url, image-dark and order, no element. The two pictures are set into a box of 540 by 210 pixels when they are uploaded - fitted, not cropped: the longest side takes the box, nothing is cut off and a smaller picture is not scaled up. After the activation, add the logos under Elements and write the row where it belongs, in the Builder or as [logos /logo image="image" link="url" sort="order"].',
				'de_DE' => 'Der Elementtyp logo, nach /elements kopiert, wo das Projekt keinen hat: name, image, url, image-dark und order, kein Element. Die beiden Bilder werden beim Hochladen in eine Box von 540 mal 210 Pixel eingepasst - nicht beschnitten: Die längere Seite nimmt die Box, nichts wird abgeschnitten und ein kleineres Bild nicht vergrößert. Nach der Aktivierung legst Du die Logos unter Elemente an und schreibst die Reihe dorthin, wo sie stehen soll, im Builder oder als [logos /logo image="image" link="url" sort="order"].',
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
