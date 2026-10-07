<?php
// features/Gallery/feature.php - what the Features panel reads. The class is
// not declared here: features/Gallery/ can only ever serve
// \Nino\Modules\Gallery.
return [
	'key'					=> 'gallery',
	'name'				=> [ 'en_US' => 'Gallery', 'de_DE' => 'Galerie' ],
	'description'	=> [
		'en_US' => 'Any number of image galleries, each a grid of thumbnails that open full screen - two sizes made on upload, nothing kept that a visitor never sees.',
		'de_DE' => 'Beliebig viele Bildergalerien, je ein Raster aus Vorschaubildern, die sich bildschirmfüllend öffnen - zwei Größen beim Hochladen, nichts gespeichert, was ein Besucher nie zu sehen bekommt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[gallery album="trip"]' => [
				'en_US' => 'The album\'s grid of thumbnails.',
				'de_DE' => 'Das Raster der Vorschaubilder dieses Albums.',
			],
			'[gallery album="trip" columns="3"]' => [
				'en_US' => 'The same, with the column count for this one place.',
				'de_DE' => 'Dasselbe, mit der Spaltenzahl für diese eine Stelle.',
			],
			'[gallery]' => [
				'en_US' => 'The first album there is.',
				'de_DE' => 'Das erste Album, das es gibt.',
			],
			'[gallery ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the grid. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Rasters. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'data-lightbox="gallery-<album>"' => [
				'en_US' => 'What each thumbnail\'s link carries, so the Lightbox feature opens it and two galleries on one page stay two sets. The caption is data-caption, the alt text the thumbnail\'s alt.',
				'de_DE' => 'Was der Link jeder Vorschau trägt, damit das Feature Lightbox ihn öffnet und zwei Galerien auf einer Seite zwei Sätze bleiben. Die Bildunterschrift steht in data-caption, der Alternativtext im alt der Vorschau.',
			],
		],
		'routes' => [],
		'panel' => [
			'Gallery' => [
				'en_US' => 'Create an album and upload its pictures - two sizes per upload, the original never kept. Alt text and caption are written per language.',
				'de_DE' => 'Ein Album anlegen und Bilder hochladen – zwei Größen je Upload, das Original wird nie behalten. Alternativtext und Bildunterschrift schreibst Du je Sprache.',
			],
			'Gallery: pictures' => [
				'en_US' => 'Several files at once, uploaded one after the other; per picture an alt text and a caption in each language, two buttons to move it and one to delete it, which takes both of its files with it.',
				'de_DE' => 'Mehrere Dateien auf einmal, nacheinander hochgeladen; je Bild ein Alternativtext und eine Bildunterschrift in jeder Sprache, zwei Schaltflächen zum Verschieben und eine zum Löschen, die beide Dateien mitnimmt.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	// It brings content an editor maintains, which is what puts it here
	// rather than with the effects that only change how a page behaves
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	// The overlay a thumbnail opens into is the Lightbox feature's, not a
	// second copy of one. Installing this from the catalogue brings it along
	'requires'		=> [ 'lightbox' ],
	// The albums with their alt texts and captions. The images themselves live under
	// /images/gallery/, where every other uploaded image lives and where a
	// backup already carries them
	'data'				=> [ '/data/gallery.php' ],
	'settings'		=> [
		'thumbWidth' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Thumbnail width', 'de_DE' => 'Breite der Vorschau' ],
			'hint'	=> [
				'en_US' => 'Thumbnails are cropped to exactly this, so a grid stays a grid. Changing it applies to images uploaded from then on - the ones already there keep the size they were made at.',
				'de_DE' => 'Vorschaubilder werden genau darauf zugeschnitten, damit ein Raster ein Raster bleibt. Eine Änderung gilt ab dem nächsten Hochladen - vorhandene Bilder behalten die Größe, in der sie erzeugt wurden.',
			],
			'min'			=> 40,
			'max'			=> 1200,
			'default'	=> 500,
		],
		'thumbHeight' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Thumbnail height', 'de_DE' => 'Höhe der Vorschau' ],
			'min'			=> 40,
			'max'			=> 1200,
			'default'	=> 500,
		],
		'largeWidth' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Large width', 'de_DE' => 'Breite der großen Ansicht' ],
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
			'label'	=> [ 'en_US' => 'Large height', 'de_DE' => 'Höhe der großen Ansicht' ],
			'min'			=> 200,
			'max'			=> 4000,
			'default'	=> 1800,
		],
		'keepRatio' => [
			'type'	=> 'bool',
			'label'	=> [ 'en_US' => 'Keep the original ratio', 'de_DE' => 'Originalratio sichern' ],
			'hint'	=> [
				'en_US' => 'On, the large view is the whole picture scaled into the box above, in its own proportions - which is what somebody opening a thumbnail wanted. Off, it is cropped to those dimensions exactly, like the thumbnail.',
				'de_DE' => 'An: Die große Ansicht ist das ganze Bild, in die obigen Maße skaliert und in seinen eigenen Proportionen - genau das, was jemand sehen will, der ein Vorschaubild öffnet. Aus: Sie wird wie die Vorschau exakt auf diese Maße zugeschnitten.',
			],
			'default'	=> true,
		],
		'columns' => [
			'type'	=> 'int',
			'label'	=> [ 'en_US' => 'Columns', 'de_DE' => 'Spalten' ],
			'hint'	=> [
				'en_US' => 'How many thumbnails a row holds at full width; the grid falls to fewer on a narrow screen by itself. [gallery columns="3"] overrides it for one gallery.',
				'de_DE' => 'Wie viele Vorschaubilder eine Zeile in voller Breite trägt; auf schmalen Bildschirmen fällt das Raster von selbst auf weniger zurück. [gallery columns="3"] überschreibt es für eine einzelne Galerie.',
			],
			'min'			=> 1,
			'max'			=> 8,
			'default'	=> 4,
		],
	],
	/*	The shortcode as the Builder offers it: no first argument, the album is named by
		its key. columns is a string, not a select of the numbers: the wrapper would turn what
		is not one of them into the setting, and doShortcode() has always read an (int) of it
		with 4 where that is none. An explicit columns="" is the one form the wrapper cannot
		tell from an omitted attribute, and it takes the setting. Nino 1.5 ignores the key	*/
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
						'en_US' => 'The key of the album, as the Gallery panel names it. Empty draws the first album there is.',
						'de_DE' => 'Der Schlüssel des Albums, wie ihn das Panel Galerie nennt. Leer zeichnet das erste Album, das es gibt.',
					],
				],
				'columns' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Columns', 'de_DE' => 'Spalten' ],
					'hint'		=> [
						'en_US' => 'How many thumbnails stand beside each other, 1 to 8. Empty takes the setting\'s number.',
						'de_DE' => 'Wie viele Vorschaubilder nebeneinander stehen, 1 bis 8. Leer nimmt die Zahl aus der Einstellung.',
					],
				],
			],
			'preview'		=> 'image',
		],
	],
];
