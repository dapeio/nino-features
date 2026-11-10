<?php
// features/Design/feature.php - what the Features panel reads. The class is
// not declared here: features/Design/ can only ever serve \Nino\Modules\Design.
return [
	'key'					=> 'design',
	'name'				=> [ 'en_US' => 'Design', 'de_DE' => 'Design' ],
	'description'	=> [
		'en_US' => 'The Design lets you shape the look of the whole site in one place. You choose templates for header, footer, content, cards, buttons, forms and more, and set type, surfaces, colours and motion, with a preview of the page. Themes keep a look under a name, and typefaces can be installed and served by the site itself. Features that bring variants appear here too.',
		'de_DE' => 'Das Design ermöglicht die Gestaltung des gesamten Aussehens der Website an einer Stelle. Du wählst Vorlagen für Header, Footer, Inhalt, Karten, Buttons, Formulare und mehr und stellst Schrift, Flächen, Farben und Bewegung ein, mit Vorschau der Seite. Themes halten ein Aussehen unter einem Namen fest, und Schriftarten lassen sich installieren und von der Website selbst ausliefern. Auch Features mit Varianten erscheinen hier.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Design' => [
				'en_US' => 'Lets you set the look in three tabs - Themes, Typefaces and Settings - and, for each object, in the aspects Base, Text, Surface, Colour and Motion, plus CSS for the page.',
				'de_DE' => 'Ermöglicht, das Aussehen in drei Tabs - Themes, Schriftarten und Einstellungen - und je Objekt in den Aspekten Grundlage, Text, Fläche, Farbe und Bewegung einzustellen, dazu CSS für die Seite.',
			],
			'Tabs and objects' => [
				'en_US' => 'Lets you set each object on its own: Page, Header, Footer, Content, Tiles, Card (its space insets a painted column too), Buttons, Lists, Tables, Form, Tabs, Accordion, one per feature with variants.',
				'de_DE' => 'Ermöglicht, jedes Objekt einzustellen: Seite, Kopf, Fuß, Inhalt, Kacheln, Karte (Abstand auch der Spalten mit Grund), Buttons, Listen, Tabellen, Formular, Tabs, Accordion, dazu Features mit Varianten.',
			],
			'Knobs' => [
				'en_US' => 'Every template offers its own knobs, as strips from less to more or as switches, so a look is tuned in steps and not written as css.',
				'de_DE' => 'Jede Vorlage bietet eigene Regler, als Leisten von weniger bis mehr oder als Schalter, sodass sich ein Aussehen in Stufen einstellen lässt und nicht als CSS geschrieben wird.',
			],
			'Preview' => [
				'en_US' => 'Lets you see every change at once on a specimen page of your project, at phone, tablet or desktop width.',
				'de_DE' => 'Ermöglicht, jede Änderung sofort auf einer Musterseite Deines Projekts zu sehen, in Handy-, Tablet- oder Desktopbreite.',
			],
			'Themes' => [
				'en_US' => 'Lets you keep a look as a named theme and Load, Duplicate, Export, Delete, Edit and Import themes; six ship with the Design, and a theme can carry css of its own.',
				'de_DE' => 'Ermöglicht, ein Aussehen als Theme festzuhalten und Themes zu Laden, Duplizieren, Exportieren, Löschen, Bearbeiten und zu importieren; sechs liegen bei, und ein Theme kann eigenes CSS tragen.',
			],
			'Typefaces' => [
				'en_US' => 'Lets you install typefaces from a list and use them for the font roles of the setup; they are served by the site itself.',
				'de_DE' => 'Ermöglicht, Schriftarten aus einer Liste zu installieren und für die Schriftrollen des Setups zu nutzen; sie werden von der Website selbst ausgeliefert.',
			],
			'Apply to website' => [
				'en_US' => 'Save draft keeps your work, Apply to website puts the look live, Reset drops changes, and Restore previous version undoes the last apply.',
				'de_DE' => 'Entwurf speichern sichert Deine Arbeit, Auf Website anwenden schaltet das Aussehen live, Zurücksetzen verwirft Änderungen, Vorherige Version wiederherstellen macht das letzte Anwenden rückgängig.',
			],
			'Variants from features' => [
				'en_US' => 'A feature that brings variants of its own gets a tab after the objects, where you choose a variant and set its knobs.',
				'de_DE' => 'Ein Feature mit eigenen Varianten bekommt einen Tab nach den Objekten, in dem Du eine Variante wählst und ihre Regler einstellst.',
			],
			'Navigation of a frame' => [
				'en_US' => 'In Base, header and footer let you choose their navigation: As the project has it, a menu of the kernel or one that a feature offers.',
				'de_DE' => 'In Grundlage lassen Kopf und Fuß die Wahl ihrer Navigation: Wie das Projekt sie hat, ein Menü des Kerns oder eines, das ein Feature anbietet.',
			],
			'CSS' => [
				'en_US' => 'CSS, the last aspect of the Page, lets you add css of your own to a theme; it applies after the templates, so it wins over them.',
				'de_DE' => 'CSS, der letzte Aspekt der Seite, ermöglicht eigenes CSS in einem Theme; es gilt nach den Vorlagen und setzt sich so gegen sie durch.',
			],
		],
		'callbacks' => [
			'/feature/design/navigations' => [
				'en_US' => 'Lets a feature offer its own navigation to the header and the footer: it adds options, each a call such as [menu /main burger] with a title.',
				'de_DE' => 'Ermöglicht einem Feature, Header und Footer eine eigene Navigation anzubieten: Es fügt Optionen hinzu, je ein Aufruf wie [menu /main burger] mit einem Titel.',
			],
			'/nino/http/output' => [
				'en_US' => 'Lets the workbench load the previews of the typefaces from the repository that the setting Typeface repository names.',
				'de_DE' => 'Ermöglicht der Workbench, die Vorschauen der Schriftarten aus dem Repository zu laden, das die Einstellung Schrift-Repository nennt.',
			],
		],
		'install' => [],
	],
	'category'		=> 'ui',
	'version' => '2.5.0',
	/*	Two floors, and the higher one wins. 1.2 is where the wizard stopped
		asking about the look and the base unit started delivering
		assets/theme.css as one file; everything here writes over that file, so
		a kernel that still spread the look over style.design.css,
		style.theme.*.css and two frame stylesheets would be compiled for a
		bundle it does not have. And the sectioned 'manual' map below is only
		read by a kernel newer than the v1.2.0-beta tag - on that one
		Features::manifest() refuses this file outright - so a constraint
		admitting 1.2 offered a feature that could not be installed. ^1.3 is
		the first that names only a kernel which does both. And the highest is
		1.11: its delivered theme.css is the compile of this library at the
		defaults - the hero as the style "hero" of a section's title and
		subtitle with the rule of a title under it, --surface-inset for a card
		and a painted column, the shorter upper steps of the space scale below
		768 px, the note of a form as nino-form-note, and the header whose
		frame slides out of the window as a whole - and the delivered file and
		this compile are held to each other byte for byte. 1.10 brought the
		section of tiles - nino-tiles, the tokens a tile
		reads, the sixth kind of surface, --radius-tile, and the link over a
		whole column - which the tiles sets, the specimen and the sixth radius
		kind are written for, on top of 1.9, which brought [logo], which draws
		the logo as the pair of the light and the dark one, and a delivered
		theme.css that is the body this compiler writes of the library - the
		hero as nino-hero, the font roles, the grounds and the counter ground as
		custom properties, the card as nino-card with its tokens, and [logo]
		with its pair. The section and card templates, the phone rule of a
		header, the font roles the compiler writes, the logo templates and the
		specimen are written for them, so on an older kernel they style nothing
		and the compile is not the delivered file. */
	'nino'				=> '^1.11',
	'requires'		=> [],
	// The whole setup: which template per part, the knob positions of the three
	// template tabs, the size, width and animation, the font roles, the palette's
	// colours and knobs and mode, the theme it was taken from and its css, and
	// the fingerprint of what was last compiled. Small, and the one thing that
	// cannot be derived again if it is lost. And the one version before the last
	// apply - the three files it replaced and the setup that went with them -
	// which a backup carries too and a restore of one may simply replace. And the
	// themes of the project, one file each: the shipped ones are the feature's
	// own files, the directory is the project's
	'data'				=> [ '/data/design.php', '/data/design-previous.php', '/data/themes' ],
	// Where the Typefaces tab reads the signed list of typefaces from, and the
	// key that list has to be signed with. The two are the feature's own repository by
	// default; a project that publishes typefaces of its own changes both
	'settings'		=> [
		'webfontsUrl' => [
			'type'		=> 'url',
			'label'		=> [ 'en_US' => 'Typeface repository', 'de_DE' => 'Schrift-Repository' ],
			'hint'		=> [
				'en_US' => 'Where the signed list of typefaces is published. The Typefaces tab loads it when asked and never on its own. Empty, or an address that is not https, switches the Available half of that tab off. A changed address reaches the previews after the page is reloaded.',
				'de_DE' => 'Wo die signierte Liste der Schriftarten veröffentlicht ist. Der Tab Schriftarten lädt sie auf Knopfdruck und nie von selbst. Leer oder keine https-Adresse schaltet die Hälfte „Verfügbar“ dieses Tabs ab. Eine geänderte Adresse erreicht die Vorschauen nach dem Neuladen der Seite.',
			],
			'default'	=> 'https://webfonts.getnino.dev/webfonts.json',
		],
		'webfontsKey' => [
			'type'		=> 'text',
			'label'		=> [ 'en_US' => 'Repository public key', 'de_DE' => 'Öffentlicher Schlüssel des Repositorys' ],
			'hint'		=> [
				'en_US' => 'The PEM public key that signed the list, for a repository of your own. Empty means the feature\'s own.',
				'de_DE' => 'Der öffentliche Schlüssel im PEM-Format, mit dem die Liste signiert ist, für ein eigenes Repository. Leer heißt: der des Features.',
			],
			'default'	=> '',
		],
	],
];
