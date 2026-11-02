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
				'en_US' => 'The album\'s grid of thumbnails, in the columns set for that album. The Builder offers the albums there are as a list to choose from. The same album twice on one page is two galleries and two sets in the Lightbox: each opens on its own pictures, not on both together.',
				'de_DE' => 'Das Raster der Vorschaubilder dieses Albums, in den Spalten, die für das Album eingestellt sind. Der Builder bietet die vorhandenen Alben als Liste zur Auswahl an. Steht dasselbe Album zweimal auf einer Seite, sind das zwei Galerien und zwei Sätze in der Lightbox: Jede blättert nur durch ihre eigenen Bilder.',
			],
			'[gallery album="trip" columns="3"]' => [
				'en_US' => 'The same, with the column count for this one place: a number from 1 to 8. Anything else - 9, 0, a word - is no count, and the grid takes the album\'s own.',
				'de_DE' => 'Dasselbe, mit der Spaltenzahl für diese eine Stelle: eine Zahl von 1 bis 8. Alles andere – 9, 0, ein Wort – ist keine Spaltenzahl, und das Raster nimmt die des Albums.',
			],
			'[gallery]' => [
				'en_US' => 'The first album there is.',
				'de_DE' => 'Das erste Album, das es gibt.',
			],
			'[gallery album="gone"]' => [
				'en_US' => 'A key that no album has - the album was deleted, or the key is misspelt - draws the first album, the way a call with no key does, because the album is a list in the Builder and the Components module reads a key outside the list as none. The Builder does not keep such a call: a section that holds one is shown as an HTML+ block. With the Components module switched off the key is read as it stands, and nothing is drawn.',
				'de_DE' => 'Ein Schlüssel, den kein Album hat - das Album wurde gelöscht oder der Schlüssel ist falsch geschrieben - zeichnet das erste Album, so wie ein Aufruf ohne Schlüssel, weil das Album im Builder eine Liste ist und das Modul Components einen Schlüssel außerhalb der Liste als keinen liest. Der Builder behält so einen Aufruf nicht: eine Section, die ihn enthält, zeigt er als HTML+-Block. Bei abgeschaltetem Modul Components gilt der Schlüssel, wie er dasteht, und es wird nichts gezeichnet.',
			],
			'[gallery ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the grid. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Rasters. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'data-lightbox="gallery-<album>"' => [
				'en_US' => 'What each thumbnail\'s link carries, so the Lightbox feature opens it and two galleries on one page stay two sets. The first gallery of an album on a page carries gallery-<album>, the same album again on that page gallery-<album>-2, then gallery-<album>-3, counted for each page request; a name already taken on the page is skipped. The caption is data-caption, the alt text the thumbnail\'s alt.',
				'de_DE' => 'Was der Link jeder Vorschau trägt, damit das Feature Lightbox ihn öffnet und zwei Galerien auf einer Seite zwei Sätze bleiben. Die erste Galerie eines Albums auf einer Seite trägt gallery-<album>, dasselbe Album noch einmal auf dieser Seite gallery-<album>-2, dann gallery-<album>-3, gezählt je Seitenaufruf; ein auf der Seite schon vergebener Name wird übersprungen. Die Bildunterschrift steht in data-caption, der Alternativtext im alt der Vorschau.',
			],
			'--gallery-gap, --gallery-corners, --gallery-ground' => [
				'en_US' => 'The three properties of the grid\'s look: the space between the cells, --gallery-gap (.75rem), the corners of a cell, --gallery-corners (3px), and its ground, --gallery-ground (a surface of the palette, with --gallery-ground-on for the text on it and --gallery-ground-border for its line). Without the Design feature a project sets them in its own css, on :root; with it, a look and its knobs write them.',
				'de_DE' => 'Die drei Eigenschaften für den Look des Rasters: der Abstand der Zellen, --gallery-gap (.75rem), die Ecken einer Zelle, --gallery-corners (3px), und ihr Grund, --gallery-ground (eine Fläche der Palette, mit --gallery-ground-on für den Text darauf und --gallery-ground-border für seine Linie). Ohne das Feature Design setzt ein Projekt sie in seinem eigenen CSS, auf :root; mit ihm schreiben ein Look und seine Regler sie.',
			],
			'<span class="nino-gallery-frame nino-image nino-hover">' => [
				'en_US' => 'The frame round each thumbnail, inside its link. The two classes of the kernel are what give the picture the hover of the kernel\'s pictures, under the pointer and under the keyboard focus of the link; the frame has no corners of its own, the cell\'s cut it.',
				'de_DE' => 'Der Rahmen um jede Vorschau, innerhalb ihres Links. Die zwei Klassen des Kernels geben dem Bild den Hover der Bilder des Kernels, unter dem Zeiger und unter dem Tastaturfokus des Links; der Rahmen hat keine eigenen Ecken, die der Zelle schneiden ihn zu.',
			],
			'<img width="500" height="500" alt="Trip, picture 3">' => [
				'en_US' => 'The thumbnail carries the size its album crops its thumbnails to, so the browser knows the room of a cell before the picture has loaded, and the list carries it as --nino-gallery-thumb-w and --nino-gallery-thumb-h for the stylesheet. The alt text is the one written for the picture, else its caption, else - for a picture with neither - the album\'s name and the picture\'s place in it, so that no link is left without a name; that picture says it has no caption, so the Lightbox does not show the name under it.',
				'de_DE' => 'Die Vorschau trägt das Maß, auf das ihr Album die Vorschaubilder zuschneidet, damit der Browser den Platz einer Zelle kennt, bevor das Bild geladen ist; die Liste trägt es als --nino-gallery-thumb-w und --nino-gallery-thumb-h für das Stylesheet. Der Alternativtext ist der für das Bild geschriebene, sonst seine Bildunterschrift, sonst – bei einem Bild mit keinem von beiden – der Name des Albums und die Stelle des Bildes darin, damit kein Link ohne Namen bleibt; dieses Bild sagt, dass es keine Bildunterschrift hat; die Lightbox zeigt den Namen deshalb nicht darunter.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the gallery' => [
				'en_US' => 'With the Design feature on, the tab Gallery of the Design panel offers under Base three looks of the grid - Grid, as it has always been drawn, Seamless and Mat - and three knobs: the space between the pictures and the corners of a picture under Surface, the ground behind a picture, the frame of the mat, under Colour. Seamless has neither space nor corners, so the panel greys those two out. The columns are no knob; they stay a setting of the album and the attribute columns. The hover of the pictures is the kernel\'s: Design sets it for all of them under Page, Motion. The block design/preview.tpl shows six pictures in the preview.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Galerie des Design-Panels unter Grundlage drei Looks des Rasters an - Raster, wie es immer gezeichnet wurde, Fugenlos und Passepartout - und drei Regler: den Abstand der Bilder und die Ecken eines Bildes unter Fläche, den Grund hinter einem Bild, den Rahmen des Passepartouts, unter Farbe. Fugenlos hat weder Abstand noch Ecken, das Panel stellt diese beiden ausgegraut dar. Die Spalten sind kein Regler; sie bleiben eine Einstellung des Albums und das Attribut columns. Der Hover der Bilder ist der des Kernels: Design regelt ihn für alle unter Seite, Bewegung. Der Block design/preview.tpl zeigt sechs Bilder in der Vorschau.',
			],
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
		'callbacks' => [
			'/nino/http/request' => [
				'en_US' => 'Forgets the lightbox groups the last request drew, so each page starts again with gallery-<album>.',
				'de_DE' => 'Vergisst die Lightbox-Gruppen des vorigen Aufrufs, damit jede Seite wieder mit gallery-<album> beginnt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The sentence that names a picture with neither an alt text nor a caption - "%s, picture %s", the album and the place - into the Text panel. It is a word of the project like its others, changed there; where a project has no word for it, the alt text shows the key.',
				'de_DE' => 'Der Satz, der ein Bild ohne Alternativtext und ohne Bildunterschrift benennt – „%s, Bild %s“, das Album und die Stelle –, ins Panel Texte. Er ist ein Wort des Projekts wie seine anderen, dort änderst Du ihn; hat ein Projekt kein Wort dafür, zeigt der Alternativtext den Schlüssel.',
			],
		],
	],
	// It brings content an editor maintains, which is what puts it here
	// rather than with the effects that only change how a page behaves
	'category'		=> 'content',
	'version'			=> '1.2.0',
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
