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
				'en_US' => 'The album\'s grid of thumbnails, in the columns set for that album. The Builder offers the albums there are as a list to choose from.',
				'de_DE' => 'Das Raster der Vorschaubilder dieses Albums, in den Spalten, die für das Album eingestellt sind. Der Builder bietet die vorhandenen Alben als Liste zur Auswahl an.',
			],
			'[gallery album="trip" columns="3"]' => [
				'en_US' => 'The same, with the column count for this one place.',
				'de_DE' => 'Dasselbe, mit der Spaltenzahl für diese eine Stelle.',
			],
			'[gallery]' => [
				'en_US' => 'The first album there is.',
				'de_DE' => 'Das erste Album, das es gibt.',
			],
			'[gallery album="gone"]' => [
				'en_US' => 'A key that no album has - the album was deleted, or the key is misspelt - draws nothing before Nino 1.6. From Nino 1.6, where the album is a list in the Builder, it draws the first album, the way a call with no key does, and the Builder does not keep such a call: a section that holds one is shown as an HTML+ block.',
				'de_DE' => 'Ein Schlüssel, den kein Album hat - das Album wurde gelöscht oder der Schlüssel ist falsch geschrieben - zeichnet vor Nino 1.6 nichts. Ab Nino 1.6, wo das Album im Builder eine Liste ist, zeichnet er das erste Album, so wie ein Aufruf ohne Schlüssel, und der Builder behält so einen Aufruf nicht: eine Section, die ihn enthält, zeigt er als HTML+-Block.',
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
				'en_US' => 'Create an album on a page of its own - key, name and the sizes of its pictures - and upload its pictures: two sizes per upload, the original never kept. Alt text and caption are written per language.',
				'de_DE' => 'Ein Album auf einer eigenen Seite anlegen – Schlüssel, Name und die Maße seiner Bilder – und Bilder hochladen: zwei Größen je Upload, das Original wird nie behalten. Alternativtext und Bildunterschrift schreibst Du je Sprache.',
			],
			'Gallery: image sizes' => [
				'en_US' => 'The sizes of the pictures and the count of columns are set per album, in the folded group Image sizes on its page. The sizes apply to pictures uploaded from then on, the columns to the grid at once. The settings of the feature are what a new album starts with.',
				'de_DE' => 'Die Maße der Bilder und die Spaltenzahl stellst Du je Album ein, in der eingeklappten Gruppe Bildmaße auf seiner Seite. Die Maße gelten ab dem nächsten Hochladen, die Spalten sofort für das Raster. Die Einstellungen des Features sind das, womit ein neues Album beginnt.',
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
		a select of the numbers: the wrapper would turn what is not one of them into the
		album's count, and doShortcode() has always read an (int) of it with 4 where that is
		none. An explicit columns="" is the one form the wrapper cannot tell from an omitted
		attribute, and it takes the album's count. Nino 1.5 ignores the key	*/
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
		],
	],
];
