<?php
// features/Design/feature.php - what the Features panel reads. The class is
// not declared here: features/Design/ can only ever serve \Nino\Modules\Design.
return [
	'key'					=> 'design',
	'name'				=> [ 'en_US' => 'Design', 'de_DE' => 'Design' ],
	'description'	=> [
		'en_US' => 'Sets the look of the whole site from one model: templates for header, footer, content, cards, buttons, forms and more, type, surfaces, colours and motion, compiled to one stylesheet with a preview of the page. Themes keep a setup under a name, and the Typefaces tab installs self-hosted fonts.',
		'de_DE' => 'Legt das Aussehen der ganzen Website über ein Modell fest: Vorlagen für Header, Footer, Inhalt, Karten, Buttons, Formulare und mehr, Schrift, Flächen, Farben und Bewegung, kompiliert zu einem Stylesheet mit Vorschau der Seite. Themes halten ein Setup unter einem Namen fest, und der Tab Schriftarten installiert selbst gehostete Schriften.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'[logo]' => [
				'en_US' => 'The frames write [logo] where the logo stands: the image slot /logo for a light ground and /logo/dark for a dark one.',
				'de_DE' => 'Die Frames schreiben [logo] dorthin, wo das Logo steht: der Bildplatz /logo für einen hellen Grund und /logo/dark für einen dunklen.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design' => [
				'en_US' => 'Three tabs - Themes, the library and the setup; Typefaces, the loader; Settings - and the setup has the aspects Base, Text, Surface, Colour and Motion for each object, and CSS for the page.',
				'de_DE' => 'Drei Tabs - Themes, die Bibliothek und das Setup; Schriftarten, der Loader; Einstellungen - und das Setup hat je Objekt die Aspekte Grundlage, Text, Fläche, Farbe und Bewegung, für die Seite CSS.',
			],
			'Tabs and objects' => [
				'en_US' => 'The setup is sorted by object: Page, Header, Footer, Content, Tiles, Card, Buttons, Lists, Tables, Form, Tabs and Accordion, then one for each feature that brings variants.',
				'de_DE' => 'Das Setup ist nach Objekten sortiert: Seite, Kopf, Fuß, Inhalt, Kacheln, Karte, Buttons, Listen, Tabellen, Formular, Tabs und Accordion, dann eins je Feature mit Varianten.',
			],
			'Knobs and ladders' => [
				'en_US' => 'A template declares a knob as a ladder of custom properties: --least to --most for a scale, --less, --default, --more for a choice, --default and --more for a toggle, --default for an ink.',
				'de_DE' => 'Eine Vorlage deklariert einen Regler als Leiter von Properties: --least bis --most für eine Skala, --less/--default/--more für eine Wahl, --default/--more für einen Schalter, --default für eine Tinte.',
			],
			'Preview' => [
				'en_US' => 'One specimen page of this project under the stylesheet the selection compiles to, at phone, tablet or desktop width; a feature that brings variants adds a block with design/preview.tpl.',
				'de_DE' => 'Eine Musterseite dieses Projekts unter dem Stylesheet, zu dem die Auswahl kompiliert, in Handy-, Tablet- oder Desktopbreite; ein Feature mit Varianten bringt mit design/preview.tpl einen Block mit.',
			],
			'Themes' => [
				'en_US' => 'The library of themes, library/themes/<slug>.php and data/themes/<slug>.php: Load, Duplicate, Export to nino-theme-<slug>.json, Delete, Edit and Import; six ship, and a theme can carry css of its own.',
				'de_DE' => 'Themes, library/themes/<slug>.php und data/themes/<slug>.php: Laden, Duplizieren, Exportieren (nino-theme-<slug>.json), Löschen, Bearbeiten, Import; sechs liegen bei, jedes kann eigenes CSS tragen.',
			],
			'Typefaces' => [
				'en_US' => 'The loader installs typefaces from a signed list into assets/fonts/<slug>/, served by the project itself, and the font roles of the setup offer them from then on.',
				'de_DE' => 'Der Loader installiert Schriftarten aus einer signierten Liste nach assets/fonts/<slug>/, vom Projekt selbst ausgeliefert, und die Schriftrollen des Setups bieten sie danach an.',
			],
			'Apply to website' => [
				'en_US' => 'Save draft writes data/design.php; Apply to website writes assets/theme.css and templates/frame-header.tpl and -footer.tpl; Reset drops changes, Restore previous version undoes the last apply.',
				'de_DE' => 'Entwurf speichern schreibt data/design.php; Auf Website anwenden assets/theme.css, templates/frame-header.tpl und -footer.tpl; Zurücksetzen verwirft Änderungen, Vorherige Version das letzte Anwenden.',
			],
			'Variants from features' => [
				'en_US' => 'A feature with a design/ folder of stylesheets brings variants: a tab after the objects with a select of them; @name and @description of a file name the look, @knob declares a knob.',
				'de_DE' => 'Ein Feature mit einem Ordner design/ voller Stylesheets bringt Varianten: einen Tab nach den Objekten mit Auswahl; @name und @description einer Datei nennen den Look, @knob deklariert einen Regler.',
			],
			'Navigation of a frame' => [
				'en_US' => 'In Base, header and footer choose their navigation: As the project has it, a menu of the kernel or what a feature offers; Apply writes html-header-nav.tpl or html-footer-nav.tpl.',
				'de_DE' => 'In Grundlage wählen Kopf und Fuß ihre Navigation: Wie das Projekt sie hat, ein Menü des Kerns oder was ein Feature anbietet; Anwenden schreibt html-header-nav.tpl oder html-footer-nav.tpl.',
			],
			'CSS' => [
				'en_US' => 'CSS, the last aspect of the Page, is the css of the theme, written as the last section of theme.css so that it wins against the templates; the save refuses among others @import and more than 64 KB.',
				'de_DE' => 'CSS, der letzte Aspekt der Seite, ist das CSS des Themes, als letzter Abschnitt von theme.css geschrieben, damit es gegen die Vorlagen gewinnt; Speichern lehnt unter anderem @import und über 64 KB ab.',
			],
		],
		'callbacks' => [
			'/feature/design/navigations' => [
				'en_US' => 'Fired by the Design, with $args = [ part, options ], to collect the navigations of a frame: a feature adds key => [ shortcode => one call such as [menu /main burger], title ] to options.',
				'de_DE' => 'Vom Design gefeuert, mit $args = [ part, options ], um die Navigationen eines Frames zu sammeln: ein Feature fügt options key => [ shortcode => ein Aufruf wie [menu /main burger], title ] hinzu.',
			],
			'/nino/http/output' => [
				'en_US' => 'Adds the origin of the typeface repository to the font-src of the workbench policy for /_admin, only while the setting Typeface repository names an https address.',
				'de_DE' => 'Ergänzt die font-src der Policy der Workbench für /_admin um die Herkunft des Schriftarten-Repositorys, nur solange die Einstellung Schrift-Repository eine https-Adresse nennt.',
			],
		],
		'install' => [],
	],
	'category'		=> 'ui',
	'version' => '2.4.0',
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
		1.10: it brings the section of tiles - nino-tiles, the tokens a tile
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
	'nino'				=> '^1.10',
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
