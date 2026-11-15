<?php
// features/Gallery/feature.php - what the Features panel reads. The class is
// not declared here: features/Gallery/ can only ever serve
// \Nino\Modules\Gallery.
return [
	'key'					=> 'gallery',
	'name'				=> [ 'en_US' => 'Gallery', 'de_DE' => 'Galerie' ],
	'description'	=> [
		'en_US' => 'Gallery lets you keep any number of image galleries, each a grid of thumbnails that open full screen. Two sizes are made on upload, and nothing is kept that no visitor sees.',
		'de_DE' => 'Die Galerie ermöglicht beliebig viele Bildergalerien, je ein Raster aus Vorschaubildern, die sich bildschirmfüllend öffnen. Beim Hochladen entstehen zwei Größen, und es wird nichts gespeichert, was kein Besucher sieht.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[gallery album="trip"]' => [
				'en_US' => 'Draws an album\'s grid in the columns set for that album; without album, or with a key no album has, the first album.',
				'de_DE' => 'Zeichnet das Raster eines Albums in den Spalten, die für das Album eingestellt sind; ohne album oder mit einem Schlüssel, den kein Album hat, das erste Album.',
			],
			'[gallery album="trip" columns="3"]' => [
				'en_US' => 'Sets the column count for this one place, a number from 1 to 8; anything else is no count, and the grid takes the album\'s own.',
				'de_DE' => 'Setzt die Spaltenzahl für diese eine Stelle, eine Zahl von 1 bis 8; alles andere ist keine Spaltenzahl, und das Raster nimmt die des Albums.',
			],
			'[gallery ... class="my-class"]' => [
				'en_US' => 'Adds a class of your own to the grid; the Builder offers the albums there are as a list.',
				'de_DE' => 'Hängt eine eigene Klasse an das Raster; der Builder bietet die vorhandenen Alben als Liste an.',
			],
		],
		'markup' => [
			'data-lightbox="gallery-<album>"' => [
				'en_US' => 'Carried by the link of each thumbnail so the Lightbox opens it; the same album a second time on the page is gallery-<album>-2, and the caption is data-caption.',
				'de_DE' => 'Trägt der Link jeder Vorschau, damit die Lightbox ihn öffnet; dasselbe Album ein zweites Mal auf der Seite heißt gallery-<album>-2, und die Bildunterschrift steht in data-caption.',
			],
			'--gallery-gap, --gallery-corners, --gallery-ground' => [
				'en_US' => 'The three properties of the grid\'s look, which a project sets in its own css on :root, or which a look of the Design feature writes.',
				'de_DE' => 'Die drei Eigenschaften für den Look des Rasters, die ein Projekt in seinem CSS auf :root setzt, oder die ein Look des Designs schreibt.',
			],
			'<span class="nino-gallery-frame nino-image nino-hover">' => [
				'en_US' => 'The frame round each thumbnail, inside its link; the kernel\'s two classes give the picture the hover of the kernel\'s pictures.',
				'de_DE' => 'Der Rahmen um jede Vorschau, innerhalb ihres Links; die zwei Klassen des Kernels geben dem Bild den Hover der Bilder des Kernels.',
			],
			'<img width="500" height="500" alt="Trip, picture 3">' => [
				'en_US' => 'The thumbnail carries its album\'s size and an alt text taken from the picture, its caption, or the album\'s name with the picture\'s place.',
				'de_DE' => 'Die Vorschau trägt das Maß ihres Albums und einen Alternativtext, der aus dem Bild, der Bildunterschrift oder dem Albumnamen mit der Stelle des Bildes kommt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Gallery' => [
				'en_US' => 'Creates albums on a page of their own (key, name, image sizes, columns) and uploads pictures: two sizes per upload, the original never kept.',
				'de_DE' => 'Legt Alben auf einer eigenen Seite an (Schlüssel, Name, Bildmaße, Spalten) und lädt Bilder hoch: zwei Größen je Upload, das Original wird nie behalten.',
			],
			'Gallery: pictures' => [
				'en_US' => 'Each picture has an alt text and a caption in each language, two buttons to move it and one to delete it, which takes both files with it.',
				'de_DE' => 'Je Bild gibt es einen Alternativtext und eine Bildunterschrift in jeder Sprache, zwei Knöpfe zum Verschieben und einen zum Löschen, der beide Dateien mitnimmt.',
			],
			'Design: the looks of the gallery' => [
				'en_US' => 'With the Design feature the gallery offers the looks Grid, Seamless and Mat, knobs for space, corners and ground, and the hover of the pictures is the kernel\'s.',
				'de_DE' => 'Mit dem Design bietet die Galerie die Looks Raster, Fugenlos und Passepartout, Regler für Abstand, Ecken und Grund, und der Hover der Bilder kommt vom Kernel.',
			],
		],
		'callbacks' => [
			'/nino/http/request' => [
				'en_US' => 'Forgets the lightbox groups the last request drew, so each page starts again with gallery-<album>.',
				'de_DE' => 'Vergisst die Lightbox-Gruppen des vorigen Aufrufs, damit jede Seite wieder mit gallery-<album> beginnt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the sentence "%s, picture %s" for a picture with neither alt text nor caption into the Text panel.',
				'de_DE' => 'Bringt den Satz „%s, Bild %s“ für ein Bild ohne Alternativtext und Bildunterschrift ins Panel Texte.',
			],
		],
	],
	// It brings content an editor maintains, which is what puts it here
	// rather than with the effects that only change how a page behaves
	'category'		=> 'content',
	'version'			=> '1.2.1',
	'nino'				=> '^1.9',
	// The overlay a thumbnail opens into is the Lightbox feature's, not a
	// second copy of one. Installing this from the catalogue brings it along
	'requires'		=> [ 'lightbox' ],
	// The albums with their alt texts and captions. The images themselves live under
	// /images/gallery/, where every other uploaded image lives and where a
	// backup already carries them
	'data'				=> [ '/data/gallery.php' ],
	// The grid's stylesheet goes into the site's style bundle; no script, what a
	// thumbnail opens into is the Lightbox feature's
	'assets'			=> [ '/features/Gallery/assets/gallery.css' ],
	'settings'		=> [
		'thumbWidth' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Thumbnail width - default for new albums', 'de_DE' => 'Breite der Vorschau – Vorgabe für neue Alben' ],
			'hint'	=> [
				'en_US' => 'What a new album starts with; each album has its own sizes, set on its page, and one that has none yet is made with these. Thumbnails are cropped to exactly this, so a grid stays a grid. Changing it applies to albums made from then on and to images uploaded from then on into an album with no sizes of its own - the ones already there keep the size they were made at.',
				'de_DE' => 'Womit ein neues Album beginnt; jedes Album hat eigene Maße, eingestellt auf seiner Seite, und eines, das noch keine hat, wird mit diesen erzeugt. Vorschaubilder werden genau darauf zugeschnitten, damit ein Raster ein Raster bleibt. Eine Änderung gilt für Alben, die danach angelegt werden, und für Bilder, die danach in ein Album ohne eigene Maße hochgeladen werden - vorhandene Bilder behalten die Größe, in der sie erzeugt wurden.',
			],
			'min'			=> 40,
			'max'			=> 1200,
			'default'	=> 500,
		],
		'thumbHeight' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Thumbnail height - default for new albums', 'de_DE' => 'Höhe der Vorschau – Vorgabe für neue Alben' ],
			'min'			=> 40,
			'max'			=> 1200,
			'default'	=> 500,
		],
		'largeWidth' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Large width - default for new albums', 'de_DE' => 'Breite der großen Ansicht – Vorgabe für neue Alben' ],
			'hint'	=> [
				'en_US' => 'The size behind a thumbnail. The original upload is never kept - this is the largest a visitor can ever see.',
				'de_DE' => 'Die Größe hinter einem Vorschaubild. Das Original wird nie aufbewahrt - das hier ist das Größte, was ein Besucher je zu sehen bekommt.',
			],
			'min'			=> 200,
			'max'			=> 4000,
			'default'	=> 1800,
		],
		'largeHeight' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Large height - default for new albums', 'de_DE' => 'Höhe der großen Ansicht – Vorgabe für neue Alben' ],
			'min'			=> 200,
			'max'			=> 4000,
			'default'	=> 1800,
		],
		'keepRatio' => [
			'type'	=> 'bool',
			'label'	=> [ 'en_US' => 'Keep the original ratio - default for new albums', 'de_DE' => 'Originalratio sichern – Vorgabe für neue Alben' ],
			'hint'	=> [
				'en_US' => 'On, the large view is the whole picture scaled into the box above, in its own proportions - which is what somebody opening a thumbnail wanted. Off, it is cropped to those dimensions exactly, like the thumbnail.',
				'de_DE' => 'An: Die große Ansicht ist das ganze Bild, in die obigen Maße skaliert und in seinen eigenen Proportionen - genau das, was jemand sehen will, der ein Vorschaubild öffnet. Aus: Sie wird wie die Vorschau exakt auf diese Maße zugeschnitten.',
			],
			'default'	=> true,
		],
		'columns' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Columns - default for new albums', 'de_DE' => 'Spalten – Vorgabe für neue Alben' ],
			'hint'	=> [
				'en_US' => 'How many thumbnails a row holds at full width; the grid falls to fewer on a narrow screen by itself. Each album has its own count, set on its page, and [gallery columns="3"] overrides it for one gallery.',
				'de_DE' => 'Wie viele Vorschaubilder eine Zeile in voller Breite trägt; auf schmalen Bildschirmen fällt das Raster von selbst auf weniger zurück. Jedes Album hat eine eigene Zahl, eingestellt auf seiner Seite, und [gallery columns="3"] überschreibt sie für eine einzelne Galerie.',
			],
			'min'			=> 1,
			'max'			=> 8,
			'default'	=> 4,
		],
	],
	/*	The shortcode as the Builder offers it: no first argument, the album is named by
		its key. album is a string here because a manifest is read without the data of a
		project and cannot name its albums: Gallery::init() registers the component again,
		this schema with the album as a select of the keys there are. columns is a string, not
		a select of the numbers: doShortcode() reads the call's count from 1 to 8 and takes
		the album's otherwise, so a count outside the range is the album's whatever the
		wrapper made of it. An explicit columns="" is the one form the wrapper cannot tell
		from an omitted attribute, and it takes the album's count	*/
	'components'	=> [
		'gallery' => [
			'label'			=> [ 'en_US' => 'Gallery', 'de_DE' => 'Galerie' ],
			'source'		=> 'none',
			'attributes'	=> [
				'album' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Album', 'de_DE' => 'Album' ],
					'hint'		=> [
						'en_US' => 'The album to draw, by the key the Gallery panel gives it. If none is chosen, it is the first album there is.',
						'de_DE' => 'Das Album, das gezeichnet wird, mit dem Schlüssel, den das Panel Galerie ihm gibt. Ist keines gewählt, ist es das erste Album, das es gibt.',
					],
				],
				'columns' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Columns', 'de_DE' => 'Spalten' ],
					'hint'		=> [
						'en_US' => 'How many thumbnails stand beside each other, 1 to 8. Empty takes the number set for the album.',
						'de_DE' => 'Wie viele Vorschaubilder nebeneinander stehen, 1 bis 8. Leer nimmt die Zahl, die für das Album eingestellt ist.',
					],
				],
			],
			'preview'		=> 'image',
			'draws'			=> 'ul .nino-gallery-grid',
			'in'			=> [ 'section' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'm22 11-1.296-1.296a2.4 2.4 0 0 0-3.408 0L11 16' ] ],
				[ 'path', [ 'd' => 'M4 8a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2' ] ],
				[ 'circle', [ 'cx' => '13', 'cy' => '7', 'r' => '1' ] ],
				[ 'rect', [ 'x' => '8', 'y' => '2', 'width' => '14', 'height' => '14', 'rx' => '2' ] ],
			],
		],
	],
];
