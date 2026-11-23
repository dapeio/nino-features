<?php
// features/Basis/feature.php - what the Features panel reads. The class is not declared here: this
// directory can only ever serve \Nino\Modules\Basis. The features that build their pages of its components
// name it under 'requires'.
return [
	'key'					=> 'basis',
	'name'				=> [ 'en_US' => 'Basic components', 'de_DE' => 'Basis-Komponenten' ],
	'description'	=> [
		'en_US' => 'Header, footer, section, button, images and text blocks: the components a page is built of, with their standard look - readable, responsive, light and dark, and nothing more. Most features of the catalogue build their pages from it.',
		'de_DE' => 'Kopfbereich, Fußbereich, Sektion, Button, Bilder und Textblöcke: die Komponenten, aus denen eine Seite gebaut wird, mit ihrem Standard-Aussehen - lesbar, responsiv, hell und dunkel, und mehr nicht. Die meisten Features des Katalogs bauen ihre Seiten daraus.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[header-wrap]' => [
				'en_US' => 'The header of the page, built like a section of rows and columns with [header-logo], [header-nav] and a button in them; ground, image, dim and height are its own, and sticky keeps it at the top.',
				'de_DE' => 'Der Kopf der Seite, wie ein Abschnitt aus Zeilen und Spalten gebaut, darin [header-logo], [header-nav] und ein Button; Grund, Bild, Abdunkeln und Höhe sind seine eigenen, sticky hält ihn oben fest.',
			],
			'[footer-wrap]' => [
				'en_US' => 'The foot of the page, built like a section of rows and columns - [footer-logo], [footer-nav] and what flows - with one [footer-legal] for the legal pages and the languages.',
				'de_DE' => 'Der Fuß der Seite, wie ein Abschnitt aus Zeilen und Spalten gebaut - [footer-logo], [footer-nav] und Fließendes - mit einem [footer-legal] für die Rechtstexte und die Sprachen.',
			],
			'[header-wrap ground="dark"]' => [
				'en_US' => 'The header takes what a section takes: ground (base, alt, tint, primary, dark), image, picture to show or hide the picture, dim and height as its own words.',
				'de_DE' => 'Der Kopf nimmt, was ein Abschnitt nimmt: ground (base, alt, tint, primary, dark), image, picture zum Zeigen oder Verbergen des Bildes, dim und height als eigene Worte.',
			],
			'[footer-wrap ground="dark"]' => [
				'en_US' => 'The foot takes what a section takes: ground (base, alt, tint, primary, dark), image, picture to show or hide the picture, dim and height as its own words.',
				'de_DE' => 'Der Fuß nimmt, was ein Abschnitt nimmt: ground (base, alt, tint, primary, dark), image, picture zum Zeigen oder Verbergen des Bildes, dim und height als eigene Worte.',
			],
			'[section-wrap]' => [
				'en_US' => 'A section of the page, with [section-row]s of [section-col]s; ground, image, picture on or off, dim and height are its own.',
				'de_DE' => 'Ein Abschnitt der Seite, mit [section-row]-Zeilen aus [section-col]-Spalten; Grund, Bild, picture on oder off, Abdunkeln und Höhe sind seine eigenen.',
			],
			'[section-col variant="card"]' => [
				'en_US' => 'A card, a column that is a surface of its own: ground and pad are its own, bleed lets a picture that is the first child reach the edge, and link makes the link of the title cover the whole card.',
				'de_DE' => 'Eine Karte, eine Spalte als eigene Fläche: ground und pad sind ihre eigenen, bleed lässt ein Bild als erstes Kind bis an den Rand reichen, und link macht den Link des Titels zur ganzen Karte.',
			],
			'[section-col]' => [
				'en_US' => 'A column of a row: size 16 to 100, auto or fill; align start, center or end; hide off or on; and [section-title], [text], [button] and the rest in it.',
				'de_DE' => 'Eine Spalte einer Zeile: size 16 bis 100, auto oder fill; align start, center oder end; hide off oder on; und [section-title], [text], [button] und das Übrige darin.',
			],
			'[section-col size="50 m:25 l:16"]' => [
				'en_US' => 'size, align and hide take m: or l: in front to count from 768 px or from the large screen, so that 50 m:25 l:16 is a half, then a quarter, then a sixth of the row.',
				'de_DE' => 'size, align und hide nehmen m: oder l: davor und zählen dann ab 768 px oder ab dem großen Schirm, sodass 50 m:25 l:16 erst die Hälfte, dann ein Viertel, dann ein Sechstel der Zeile ist.',
			],
			'[section-row]' => [
				'en_US' => 'A row of columns in a section, a header or a footer: width regular, narrow, wide or full; gap 0 to 4; valign stretch, start, center or end; halign start, center, end or between.',
				'de_DE' => 'Eine Zeile aus Spalten in Abschnitt, Kopf oder Fuß: width regular, narrow, wide oder full; gap 0 bis 4; valign stretch, start, center oder end; halign start, center, end oder between.',
			],
			'[section-row reverse="on"]' => [
				'en_US' => 'reverse off or on turns the order of the columns around, and takes m: or l: in front to do so from 768 px or from the large screen.',
				'de_DE' => 'reverse off oder on dreht die Reihenfolge der Spalten um und nimmt m: oder l: davor, um das ab 768 px oder ab dem großen Schirm zu tun.',
			],
			'[section-title]' => [
				'en_US' => 'The title of a column, a heading in size s, m, l or xl with a link where the call has href, underlined as a link is; tag h1, h2, h3, h4 or div names the element.',
				'de_DE' => 'Der Titel einer Spalte, eine Überschrift in der Größe s, m, l oder xl, mit Link, wo der Aufruf href hat, unterstrichen wie ein Link; tag h1, h2, h3, h4 oder div nennt das Element.',
			],
			'[section-subtitle]' => [
				'en_US' => 'The quieter line below the title of a column, a leaf with no attribute of its own, written as plain text with inline markup.',
				'de_DE' => 'Die ruhigere Zeile unter dem Titel einer Spalte, ein Blatt ohne eigenes Attribut, als schlichter Text mit Inline-Markup geschrieben.',
			],
			'[header-logo]' => [
				'en_US' => 'The logo of the project in the header: light for a light ground, dark for a dark ground and href for the link, the start page by default; it stands once in a header.',
				'de_DE' => 'Das Logo des Projekts im Kopf: light für hellen Grund, dark für dunklen Grund und href für den Link, standardmäßig die Startseite; es steht einmal in einem Kopf.',
			],
			'[header-nav]' => [
				'en_US' => 'The menu of the header, a list that is a button on a small screen; menu names the menu, label its name for a screen reader, collapse small, always or never; once in a header.',
				'de_DE' => 'Das Menü des Kopfs, eine Liste, die auf kleinen Schirmen ein Knopf ist; menu nennt das Menü, label seinen Namen für Screenreader, collapse small, always oder never; einmal im Kopf.',
			],
			'[header-actions]' => [
				'en_US' => 'The room in the header beside the menu for buttons, a picture or a text.',
				'de_DE' => 'Der Platz im Kopf neben dem Menü für Buttons, ein Bild oder einen Text.',
			],
			'[footer-col]' => [
				'en_US' => 'A column of the footer that holds flow, a logo or a menu, as wide as size says - 25, 33, 50, 66, 75 or 100, with m: or l: in front to count from 768 px or from the large screen.',
				'de_DE' => 'Eine Spalte des Fußes, die Fließendes, ein Logo oder ein Menü hält, so breit, wie size es sagt - 25, 33, 50, 66, 75 oder 100, mit m: oder l: davor, um ab 768 px oder ab dem großen Schirm zu zählen.',
			],
			'[footer-logo]' => [
				'en_US' => 'The logo of the project in the footer, one picture on every ground, with the alt text the picture has, else the one of alt, or marked as decoration with decorative.',
				'de_DE' => 'Das Logo des Projekts im Fuß, ein Bild auf jedem Grund, mit dem Alternativtext des Bildes, sonst dem von alt, oder mit decorative als Schmuck gekennzeichnet.',
			],
			'[footer-nav]' => [
				'en_US' => 'The menu of the footer: the entries of a menu of the project as a list that has a name for a screen reader; menu names the menu and label that name.',
				'de_DE' => 'Das Menü des Fußes: die Einträge eines Menüs des Projekts als Liste, die für einen Screenreader einen Namen hat; menu nennt das Menü und label diesen Namen.',
			],
			'[footer-legal]' => [
				'en_US' => 'The line of the law at the foot of the page: the copyright, the legal pages and the languages of the project; it stands once in a footer.',
				'de_DE' => 'Die Rechtszeile am Fuß der Seite: das Urheberrecht, die Rechtstexte und die Sprachen des Projekts; sie steht einmal in einem Fuß.',
			],
			'[button]' => [
				'en_US' => 'A link that looks like a button, or a span of the same look where it has no address: href and target self or blank, tone primary, secondary, outline or quiet, size s, m or l, and wide.',
				'de_DE' => 'Ein Link, der wie ein Button aussieht, oder ohne Adresse ein span in derselben Form: href und target self oder blank, tone primary, secondary, outline oder quiet, size s, m oder l und wide.',
			],
			'[button-group]' => [
				'en_US' => 'A row of [button]s that stands at the start of its box, or with align center or end in the middle or at the end of it.',
				'de_DE' => 'Eine Reihe von [button]-Knöpfen, die am Anfang ihres Kastens steht oder mit align center oder end in der Mitte oder am Ende.',
			],
			'[image]' => [
				'en_US' => 'The picture of a slot or of an image field with alt, decorative, caption and href; ratio and fit shape it, height 1 to 4 sets a fixed height, and focus says which part stays when it is cropped.',
				'de_DE' => 'Das Bild eines Platzes oder eines Bildfelds mit alt, decorative, caption und href; ratio und fit formen es, height 1 bis 4 setzt eine feste Höhe, und focus sagt, welcher Teil beim Beschneiden bleibt.',
			],
			'[text]' => [
				'en_US' => 'A text with paragraphs and lists: size s, m or l, format inline, lines or blocks, and the room between its paragraphs set by the knob text.gap.',
				'de_DE' => 'Ein Text mit Absätzen und Listen: size s, m oder l, format inline, lines oder blocks, und der Raum zwischen den Absätzen durch den Regler text.gap.',
			],
			'[spacer]' => [
				'en_US' => 'Room between two things, size 1 to 6 with 2 by default, a step of the page and no knob of a part.',
				'de_DE' => 'Raum zwischen zwei Dingen, size 1 bis 6, standardmäßig 2, ein Schritt der Seite und kein Regler eines Teils.',
			],
			'[html]' => [
				'en_US' => 'A block of markup that is cleaned before it is drawn, in a page or in flow; where Basis is off it and what stands in it draws nothing.',
				'de_DE' => 'Ein Block Markup, der gesäubert wird, bevor er gezeichnet wird, in einer Seite oder im Fluss; wo Basis aus ist, zeichnet er nichts, und was darin steht, auch nicht.',
			],
		],
		'markup' => [
			'<name>--ground-<word>' => [
				'en_US' => 'The class a ground (base, alt, tint, primary, dark) is written as, for every component that has one; Nino.css names the roles for what stands on it.',
				'de_DE' => 'Die Klasse, als die ein Grund (base, alt, tint, primary, dark) geschrieben wird, für jede Komponente, die einen hat; Nino.css nennt die Rollen für das, was darauf steht.',
			],
			'--brand-..., --page-...' => [
				'en_US' => 'The tokens the standard parts read, set in Nino.css; a project turns them in assets/style.css.',
				'de_DE' => 'Die Tokens, die die Standard-Teile lesen, gesetzt in Nino.css; ein Projekt dreht sie in assets/style.css.',
			],
			'--header-wrap-space, --header-logo-height, --header-nav-gap' => [
				'en_US' => 'The knobs of the group header, each a token with a ladder of five steps that a theme turns: header-wrap.space, header-logo.height and header-nav.gap.',
				'de_DE' => 'Die Regler der Gruppe header, je ein Token mit einer Leiter aus fünf Stufen, die ein Theme dreht: header-wrap.space, header-logo.height und header-nav.gap.',
			],
			'--footer-wrap-space, --footer-col-gap' => [
				'en_US' => 'The knobs of the group footer, each with a ladder of five steps: footer-wrap.space for the room above and below, footer-col.gap for the gap between columns.',
				'de_DE' => 'Die Regler der Gruppe footer, je mit einer Leiter aus fünf Stufen: footer-wrap.space für den Raum oben und unten, footer-col.gap für die Lücke zwischen Spalten.',
			],
			'--section-wrap-space, --section-row-gap, --section-col-pad, --section-col-radius, --section-title-space' => [
				'en_US' => 'The knobs of the group section, each with a ladder of five steps: section-wrap.space, section-row.gap, section-col.pad, section-col.radius and section-title.space.',
				'de_DE' => 'Die Regler der Gruppe section, je mit einer Leiter aus fünf Stufen: section-wrap.space, section-row.gap, section-col.pad, section-col.radius und section-title.space.',
			],
			'--button-space, --button-radius, --button-size' => [
				'en_US' => 'The knobs of the group button, each with a ladder of five steps: button.space for the padding, button.radius for the corners and button.size for the writing.',
				'de_DE' => 'Die Regler der Gruppe button, je mit einer Leiter aus fünf Stufen: button.space für den Innenabstand, button.radius für die Ecken und button.size für die Schrift.',
			],
			'--image-radius, --image-height' => [
				'en_US' => 'The knobs of the group image, each with a ladder of five steps: image.radius for the corners and image.height for the fixed heights 1 to 4 of an [image].',
				'de_DE' => 'Die Regler der Gruppe image, je mit einer Leiter aus fünf Stufen: image.radius für die Ecken und image.height für die festen Höhen 1 bis 4 eines [image].',
			],
			'--text-size, --text-gap' => [
				'en_US' => 'The knobs of the group text, each with a ladder of five steps: text.size for the writing of a [text] and text.gap for the room between its paragraphs.',
				'de_DE' => 'Die Regler der Gruppe text, je mit einer Leiter aus fünf Stufen: text.size für die Schrift eines [text] und text.gap für den Raum zwischen seinen Absätzen.',
			],
			'gap="2", pad="2", height="3"' => [
				'en_US' => 'The fixed steps 0 to 4 of gap, pad and height are factors on the knob of the same size, so a theme that turns a knob turns every step with it and the steps keep their order.',
				'de_DE' => 'Die festen Stufen 0 bis 4 von gap, pad und height sind Faktoren auf dem Regler gleicher Größe, sodass ein Theme, das einen Regler dreht, jede Stufe mitdreht und die Stufen ihre Reihenfolge behalten.',
			],
			'details[data-header-menu]' => [
				'en_US' => 'The folded menu is a details of the browser whose open list lies over the window under the header; a menu of another component that says data-header-menu is held the same way.',
				'de_DE' => 'Das gefaltete Menü ist ein details des Browsers, dessen offene Liste unter dem Kopf über dem Fenster liegt; ein Menü einer anderen Komponente, das data-header-menu sagt, wird ebenso gehalten.',
			],
			'assets/header-nav.js' => [
				'en_US' => 'The one script of the feature, in the script bundle while Basis is on: it closes an open menu with Escape and keeps the page behind it inert; without it the menu is the details and nothing more.',
				'de_DE' => 'Das eine Skript des Features, im Skript-Bündel, solange Basis an ist: es schließt ein offenes Menü mit Escape und hält die Seite dahinter inert; ohne es ist das Menü das details und nicht mehr.',
			],
			'/module/basis/navigation/<name>' => [
				'en_US' => 'Words from the time Basis was a module are read here where the feature has none; the next switch-on or update puts the standard words below /feature/basis/navigation/, where own ones go.',
				'de_DE' => 'Worte aus der Modul-Zeit von Basis werden hier gelesen, wo das Feature keine hat; das nächste Einschalten oder Update legt Standardworte unter /feature/basis/navigation/ ab, dort trägt man eigene ein.',
			],
			'blocks/hero-full' => [
				'en_US' => 'A hero with a full screen picture under a veil, a headline, a line and a button; its variants are image-full, image-compact and plain without the picture.',
				'de_DE' => 'Ein Hero mit einem Bild über den ganzen Schirm unter einem Schleier, mit Überschrift, Zeile und Button; seine Varianten sind image-full, image-compact und plain ohne das Bild.',
			],
			'blocks/hero-centered' => [
				'en_US' => 'A hero centered on a colour with a headline, a line and two buttons.',
				'de_DE' => 'Ein Hero, zentriert auf einer Farbe, mit Überschrift, Zeile und zwei Buttons.',
			],
			'blocks/page-header' => [
				'en_US' => 'A small header of a page: a title and a line in a narrow row.',
				'de_DE' => 'Ein kleiner Kopf einer Seite: ein Titel und eine Zeile in einer schmalen Zeile.',
			],
			'blocks/split-image-text' => [
				'en_US' => 'A section of picture and text side by side, one above the other on a phone; its variants are image-left and image-right.',
				'de_DE' => 'Ein Abschnitt aus Bild und Text nebeneinander, auf dem Telefon untereinander; seine Varianten sind image-left und image-right.',
			],
			'blocks/text-narrow' => [
				'en_US' => 'A narrow section of a title and a text for a page that is read.',
				'de_DE' => 'Ein schmaler Abschnitt aus Titel und Text für eine Seite, die gelesen wird.',
			],
			'blocks/three-columns' => [
				'en_US' => 'Three columns of cards, each a [section-col variant="card"] with a title and a text.',
				'de_DE' => 'Drei Spalten aus Karten, jede ein [section-col variant="card"] mit Titel und Text.',
			],
			'blocks/header-line' => [
				'en_US' => 'A header in one line: the logo, the menu and a button, built of a row and its columns.',
				'de_DE' => 'Ein Kopf in einer Zeile: das Logo, das Menü und ein Button, aus einer Zeile und ihren Spalten gebaut.',
			],
			'blocks/footer-columns' => [
				'en_US' => 'A footer with columns: a short text, the menu and the legal line, built of a row and its columns.',
				'de_DE' => 'Ein Fuß mit Spalten: ein kurzer Text, das Menü und die Rechtszeile, aus einer Zeile und ihren Spalten gebaut.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/components/parts' => [
				'en_US' => 'Asked for the standard part of each of its six groups before it goes into the style bundle; a theme answers another css file, or an empty one for none.',
				'de_DE' => 'Wird für den Standard-Teil jeder seiner sechs Gruppen gefragt, bevor er ins Stil-Bündel kommt; ein Theme antwortet eine andere css-Datei oder eine leere für keine.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the three words of the menus - Main, Menu, Footer - into the Text panel, for the names of the buttons and lists a screen reader reads.',
				'de_DE' => 'Bringt die drei Worte der Menüs - Haupt, Menü, Fußbereich - ins Panel Texte, für die Namen der Knöpfe und Listen, die ein Screenreader liest.',
			],
		],
	],
	'assets'			=> [ '/features/Basis/assets/header-nav.js' ],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',
];
