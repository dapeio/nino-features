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
		'en_US' => <<<'TXT'
			Create an album in the Gallery panel and upload its pictures - two sizes
			are made from each one and the upload itself is never kept. Then put
			`[gallery album="trip"]` where the grid belongs.

			`[gallery album="trip" columns="3"]` overrides the column count for one
			gallery, and `[gallery]` alone renders the first album. A thumbnail
			opens full screen through the Lightbox feature, which this one brings
			along.
			TXT,
		'de_DE' => <<<'TXT'
			Lege im Panel Galerie ein Album an und lade seine Bilder hoch – aus
			jedem entstehen zwei Größen, der Upload selbst wird nie aufbewahrt.
			Setze dann `[gallery album="trip"]` dorthin, wo das Raster hingehört.

			`[gallery album="trip" columns="3"]` überschreibt die Spaltenzahl für
			eine einzelne Galerie, `[gallery]` allein zeigt das erste Album. Ein
			Vorschaubild öffnet sich bildschirmfüllend über das Feature Lightbox,
			das dieses mitbringt.
			TXT,
	],
	// It brings content an editor maintains, which is what puts it here
	// rather than with the effects that only change how a page behaves
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	// The overlay a thumbnail opens into is the Lightbox feature's, not a
	// second copy of one. Installing this from the catalogue brings it along
	'requires'		=> [ 'lightbox' ],
	// The albums and their captions. The images themselves live under
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
];
