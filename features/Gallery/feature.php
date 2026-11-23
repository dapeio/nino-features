<?php
// features/Gallery/feature.php - what the Features panel reads. The class is
// not declared here: features/Gallery/ can only ever serve
// \Nino\Modules\Gallery.
return [
	'key'					=> 'gallery',
	'name'				=> [ 'en_US' => 'Gallery', 'de_DE' => 'Galerie' ],
	'description'	=> [
		'en_US' => 'Gallery lets you keep any number of image galleries, each a grid of thumbnails that open full screen. The overlay has a series, swipe, the keyboard and a focus trap, and without JavaScript a thumbnail is a link to the large picture. Two sizes are made on upload, and nothing is kept that no visitor sees.',
		'de_DE' => 'Die Galerie ermöglicht beliebig viele Bildergalerien, je ein Raster aus Vorschaubildern, die sich bildschirmfüllend öffnen. Das Overlay hat eine Serie, Wischen, Tastatur und eine Fokusfalle, und ohne JavaScript ist eine Vorschau ein Link auf das große Bild. Beim Hochladen entstehen zwei Größen, und es wird nichts gespeichert, was kein Besucher sieht.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[gallery album="trip"]' => [
				'en_US' => 'Draws an album\'s grid in the columns set for that album; without album, or with a key no album has, the first album.',
				'de_DE' => 'Zeichnet das Raster eines Albums in den Spalten, die für das Album eingestellt sind; ohne album oder mit einem Schlüssel, den kein Album hat, das erste Album.',
			],
			'[gallery album="trip" columns="3"]' => [
				'en_US' => 'Sets the number of columns for this one place, 2 to 6; anything else is no choice, and the grid takes the album\'s own.',
				'de_DE' => 'Setzt die Spaltenzahl für diese eine Stelle, 2 bis 6; alles andere ist keine Wahl, und das Raster nimmt die des Albums.',
			],
			'[gallery variant="mat"]' => [
				'en_US' => 'The look of the grid: mat puts a ground and a line round each picture, seamless sets the pictures edge to edge; without variant it is the grid.',
				'de_DE' => 'Das Aussehen des Rasters: mat legt Grund und Linie um jedes Bild, seamless setzt die Bilder Kante an Kante; ohne variant ist es das Raster.',
			],
		],
		'markup' => [
			'data-lightbox="gallery-<album>"' => [
				'en_US' => 'Carried by the link of each thumbnail so the overlay opens it; links that share a value are one series, and a link of your own to an image opens the same way.',
				'de_DE' => 'Trägt der Link jeder Vorschau, damit das Overlay ihn öffnet; Links mit gleichem Wert sind eine Serie, und ein eigener Link auf ein Bild öffnet sich genauso.',
			],
			'data-caption="Above the pass"' => [
				'en_US' => 'The caption under the picture in the overlay; empty means no caption, and without the attribute the alt text of the thumbnail applies.',
				'de_DE' => 'Die Bildunterschrift unter dem Bild im Overlay; leer heißt keine Unterschrift, und ohne das Attribut gilt der Alternativtext der Vorschau.',
			],
			'data-label-close, data-label-prev, data-label-next' => [
				'en_US' => 'The labels of the three controls of the overlay for screen readers; without them the script\'s own English or German words are used.',
				'de_DE' => 'Die Beschriftungen der drei Bedienelemente des Overlays für Screenreader; ohne sie gelten die englischen oder deutschen Worte des Skripts.',
			],
			'--gallery-gap, --gallery-radius' => [
				'en_US' => 'The two knobs of the grid, space between the pictures and corners of a picture, which the Themes feature turns and a project sets on :root.',
				'de_DE' => 'Die zwei Regler des Rasters, Abstand der Bilder und Ecken eines Bildes, die das Feature Themes dreht und ein Projekt auf :root setzt.',
			],
			'<img width="500" height="500" alt="Trip, picture 3">' => [
				'en_US' => 'The thumbnail carries its album\'s size and an alt text taken from the picture, its caption, or the album\'s name with the picture\'s place.',
				'de_DE' => 'Die Vorschau trägt das Maß ihres Albums und einen Alternativtext, der aus dem Bild, der Bildunterschrift oder dem Albumnamen mit der Stelle des Bildes kommt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Gallery' => [
				'en_US' => 'Lists the albums in a table with search and makes a new one; an album opens as a view of its own with its pictures as cards and a tab for its name and sizes.',
				'de_DE' => 'Listet die Alben in einer Tabelle mit Suche und legt ein neues an; ein Album öffnet sich als eigene Ansicht mit seinen Bildern als Karten und einem Reiter für Name und Maße.',
			],
			'Gallery: pictures' => [
				'en_US' => 'A picture is added (several at once), given a caption and an alt text in the working language, moved to another position at once, or deleted with both of its files.',
				'de_DE' => 'Ein Bild wird hinzugefügt (auch mehrere auf einmal), erhält Bildunterschrift und Alternativtext in der Arbeitssprache, rückt sofort auf eine andere Position oder wird samt beiden Dateien gelöscht.',
			],
			'Gallery: uploads' => [
				'en_US' => 'Takes JPEG, PNG, GIF and WebP only, makes two sizes of each and keeps no original; at most 50 albums and 200 pictures in an album.',
				'de_DE' => 'Nimmt nur JPEG, PNG, GIF und WebP, macht von jedem zwei Größen und behält kein Original; höchstens 50 Alben und 200 Bilder in einem Album.',
			],
		],
		'callbacks' => [
			'/nino/http/request' => [
				'en_US' => 'Forgets the groups of the overlay the last request drew, so each page starts again with gallery-<album>.',
				'de_DE' => 'Vergisst die Gruppen des Overlays vom vorigen Aufruf, damit jede Seite wieder mit gallery-<album> beginnt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the sentence "%s, picture %s" for a picture with neither alt text nor caption into the Text panel.',
				'de_DE' => 'Bringt den Satz „%s, Bild %s“ für ein Bild ohne Alternativtext und Bildunterschrift ins Panel Texte.',
			],
			'blocks/gallery-album/' => [
				'en_US' => 'A block: a headline and the grid of the first album.',
				'de_DE' => 'Ein Block: eine Überschrift und das Raster des ersten Albums.',
			],
		],
	],
	// It brings content an editor maintains, which is what puts it here
	// rather than with the effects that only change how a page behaves
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'basis' ],
	// The albums with their alt texts and captions. The images themselves live under
	// /images/gallery/, where every other uploaded image lives and where a
	// backup already carries them
	'data'				=> [ '/data/gallery.php' ],
	// The overlay's script, into the site's script bundle; the grid and the overlay's look
	// are the standard part of the group gallery
	'assets'			=> [ '/features/Gallery/assets/gallery.js' ],
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
			'label'	=> [ 'en_US' => 'Keep the aspect ratio - default for new albums', 'de_DE' => 'Seitenverhältnis beibehalten – Vorgabe für neue Alben' ],
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
];
