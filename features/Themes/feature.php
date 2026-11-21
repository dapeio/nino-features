<?php
// The class is not declared: features/Themes/ can only serve \Nino\Modules\Themes
return [
	'key'					=> 'themes',
	'name'				=> [ 'en_US' => 'Themes', 'de_DE' => 'Themes' ],
	'description'	=> [
		'en_US' => 'Themes gives the site a look that is chosen and not written: a theme brings a part for the page and for each group of components, and the project mixes them, sets its colours and fonts and keeps the result as one stylesheet. The css of a theme, checked by the rule of a theme package, goes to the end of it. The last state is kept, one step back.',
		'de_DE' => 'Themes gibt der Website ein Aussehen, das gewählt und nicht geschrieben wird: Ein Theme bringt einen Teil für die Seite und für jede Gruppe von Komponenten, das Projekt mischt sie, setzt Farben und Schriften und behält das Ergebnis als ein Stylesheet. Das CSS eines Themes, nach der Regel eines Theme-Pakets geprüft, steht am Ende. Der letzte Stand bleibt erhalten, einen Schritt zurück.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'themes/nino/' => [
				'en_US' => 'Nino is the theme that comes with the feature: every knob in the middle, a bar for the header, a dark surface for the footer, a band for sections, a rule below large titles, a hero.',
				'de_DE' => 'Nino ist das Theme des Features: jeder Regler in der Mitte, ein Balken für den Kopfbereich, eine dunkle Fläche für den Fußbereich, ein Band für Abschnitte, eine Linie unter großen Titeln, ein Hero.',
			],
			'themes/<key>/theme.json' => [
				'en_US' => 'A theme is a folder with its key, version, name, description and suggestions for the brand; nothing in it is code and the feature reads no file but theme.json, theme.css, part.json and part.css.',
				'de_DE' => 'Ein Theme ist ein Ordner mit Schlüssel, Version, Name, Beschreibung und Vorschlägen für die Marke; nichts darin ist Code, und das Feature liest nur theme.json, theme.css, part.json und part.css.',
			],
			'private/packages/themes/<key>/' => [
				'en_US' => 'A theme from the catalogue or from an upload is a folder like the one of a shipped theme, and the kernel puts it here.',
				'de_DE' => 'Ein Theme aus dem Katalog oder aus einem Upload ist ein Ordner wie der eines mitgelieferten Themes, und der Kernel legt ihn hier ab.',
			],
			'a theme with the key of an earlier folder' => [
				'en_US' => 'Themes are read from the feature, then from the packages, then from the folders of the hook, and a key that an earlier folder has is not read and is named in the list of what was refused.',
				'de_DE' => 'Themes werden aus dem Feature gelesen, dann aus den Paketen, dann aus den Ordnern des Hooks, und ein Schlüssel, den ein früherer Ordner hat, wird nicht gelesen und im Abgelehnten genannt.',
			],
			'a package that is installed, updated or removed' => [
				'en_US' => 'The website never changes by it: it draws from the copy of the parts it uses, the panel says where a package is missing, and a theme is applied only by hand.',
				'de_DE' => 'Die Website ändert sich dadurch nie: sie zeichnet aus der Kopie der Teile, die sie nutzt, das Panel sagt, wo ein Paket fehlt, und ein Theme gilt erst, wenn es von Hand angewendet wird.',
			],
			'themes/<key>/blocks/<block>/' => [
				'en_US' => 'A theme may bring blocks, each a folder of block.json, block.tpl and text/<locale>.json; the kernel checks every block and leaves out one that fails.',
				'de_DE' => 'Ein Theme kann Blocks mitbringen, je ein Ordner mit block.json, block.tpl und text/<locale>.json; der Kernel prüft jeden Block und lässt einen aus, der nicht besteht.',
			],
			'parts/<group>/part.json' => [
				'en_US' => 'The part of a group names its contract, sets the ladders of its knobs, brings variants and a standard one, and may give the steps it is delivered at (values).',
				'de_DE' => 'Der Teil einer Gruppe nennt Vertrag und Leitern der Regler, bringt Varianten und eine Standard-Variante mit und kann die Stufen nennen, auf denen er ausgeliefert wird (values).',
			],
			'parts/<group>/part.css' => [
				'en_US' => 'The css of a part lies on the standard part and keeps the rule of mixing; it rules over its modifiers, so it computes with tokens and calc() and sets no absolute value.',
				'de_DE' => 'Das CSS eines Teils liegt auf dem Standard-Teil und hält die Regel des Mischens; es schlägt dessen Modifikatoren, darum rechnet es mit Tokens und calc() statt mit festen Werten.',
			],
			'a step of a ladder' => [
				'en_US' => 'A step is a length with its unit, 0px and not 0, because the factors of the fixed steps multiply it; its parentheses stand as pairs.',
				'de_DE' => 'Eine Stufe ist eine Länge mit Einheit, 0px und nicht 0, weil die Faktoren der festen Stufen sie multiplizieren; ihre Klammern stehen paarweise.',
			],
			'parts/page/part.json' => [
				'en_US' => 'The part for the page has knobs of five sets of the --page tokens each and no css; a knob for small screens is the density.',
				'de_DE' => 'Der Teil für die Seite hat Regler aus je fünf Sätzen der --page-Tokens und kein CSS; ein Regler für kleine Bildschirme ist die Dichte.',
			],
			'public/fonts/<slug>/font.json' => [
				'en_US' => 'A font is a folder with its name, a fallback and the .woff2 files of its faces; the stylesheet loads it from the site and from nowhere else.',
				'de_DE' => 'Eine Schrift ist ein Ordner mit Namen, Ersatzschrift und den .woff2-Dateien ihrer Schnitte; das Stylesheet lädt sie von der Website und von sonst nirgends.',
			],
		],
		'routes' => [
			'?nino-preview=<token>' => [
				'en_US' => 'Any page of the site, asked with the token of a preview by the session and the account that hold it, shows the draft while the token lasts; the public stylesheet is not touched.',
				'de_DE' => 'Jede Seite der Website, mit dem Token einer Vorschau von der Sitzung und dem Konto aufgerufen, die es halten, zeigt den Entwurf, solange das Token gilt; das öffentliche Stylesheet bleibt unberührt.',
			],
			'&mark=<component>' => [
				'en_US' => 'A preview that names a component draws an outline round its elements; a request without a preview of its own has no outline.',
				'de_DE' => 'Eine Vorschau, die eine Komponente nennt, zeichnet einen Umriss um deren Elemente; eine Anfrage ohne eigene Vorschau hat keinen Umriss.',
			],
			'&nino-theme-demo=<group>' => [
				'en_US' => 'A preview that names a group of the register answers a page of its own: the demo of that group in the stylesheet of the draft, not kept.',
				'de_DE' => 'Eine Vorschau, die eine Gruppe des Registers nennt, antwortet mit einer eigenen Seite: der Demo der Gruppe im Stylesheet des Entwurfs, nicht gespeichert.',
			],
		],
		'panel' => [
			'Themes' => [
				'en_US' => 'The screen of the workbench for the themes: the list, and for a theme its groups, brand, settings and own css beside a preview of the draft or a demo; the right is /_admin/themes/manage.',
				'de_DE' => 'Der Bildschirm der Werkbank für die Themes: die Liste, und je Theme Gruppen, Marke, Einstellungen und eigenes CSS neben einer Vorschau des Entwurfs oder einer Demo; das Recht: /_admin/themes/manage.',
			],
			'Themes > Upload theme' => [
				'en_US' => 'A theme package (.tar.gz) is checked and shown first; one uploaded here is replaced by a higher version and removed here, and blocks ask the right of the features too.',
				'de_DE' => 'Ein Theme-Paket (.tar.gz) wird zuerst geprüft und gezeigt; ein hier hochgeladenes wird durch eine höhere Version ersetzt und hier entfernt, Blocks verlangen auch das Recht der Features.',
			],
			'Themes > Fonts' => [
				'en_US' => 'The fonts of public/fonts/ with their licence and their place in the brand, and the signed list of the repository, loaded on the button alone, to install a family from.',
				'de_DE' => 'Die Schriften aus public/fonts/ mit Lizenz und ihrem Platz in der Marke, dazu die signierte Liste des Repositorys, geladen nur auf Knopfdruck, um eine Familie zu installieren.',
			],
			'Themes > Own CSS' => [
				'en_US' => 'One field for private/assets/style.css, the stylesheet of the project, under every theme; the right /_admin/themes/css alone opens this tab, so an account with only that right lands in it.',
				'de_DE' => 'Ein Feld für private/assets/style.css, das Stylesheet des Projekts unter jedem Theme; das Recht /_admin/themes/css allein öffnet diesen Reiter, ein Konto nur mit ihm landet direkt darin.',
			],
			'Themes > Settings' => [
				'en_US' => 'The form of the two settings of the repository, its address and its public key, which the workbench draws from the manifest; it needs the right /_admin/features/manage.',
				'de_DE' => 'Das Formular der zwei Einstellungen des Repositorys, Adresse und öffentlicher Schlüssel, das die Werkbank aus dem Manifest zeichnet; es braucht das Recht /_admin/features/manage.',
			],
		],
		'callbacks' => [
			'/nino/components/variants' => [
				'en_US' => 'Adds the variants of the parts in use to the components that have them, each with its label, and chooses the one that stands where a page names none.',
				'de_DE' => 'Fügt den Komponenten die Varianten der verwendeten Teile hinzu, jede mit ihrer Beschriftung, und wählt die, die gilt, wo eine Seite keine nennt.',
			],
			'/nino/blocks' => [
				'en_US' => 'Names the folder blocks/ of every installed theme, not only the one that is on, with the key of the theme as the origin of its blocks.',
				'de_DE' => 'Nennt den Ordner blocks/ jedes installierten Themes, nicht nur des aktiven, mit dem Schlüssel des Themes als Herkunft seiner Blocks.',
			],
			'/nino/themes/roots' => [
				'en_US' => 'Names more folders to read themes from, each holding <key>/theme.json, after the one this feature brings and the one of the installed packages.',
				'de_DE' => 'Nennt weitere Ordner, aus denen Themes gelesen werden, je mit <key>/theme.json, nach dem, den dieses Feature mitbringt, und dem der installierten Pakete.',
			],
		],
		'install' => [
			'assets/theme.css' => [
				'en_US' => 'The stylesheet of the layer nino.theme, written by the compiler with the brand, the steps of the knobs, the parts and the own css; a file somebody else wrote is replaced only when told to.',
				'de_DE' => 'Das Stylesheet der Lage nino.theme, vom Compiler geschrieben mit Marke, Reglerstufen, Teilen und eigenem CSS; eine Datei, die jemand anderes schrieb, wird nur auf Anweisung ersetzt.',
			],
			'data/theme/' => [
				'en_US' => 'The state of the website - the copy of its theme, the brand, the stylesheet - and the one before it, and the keys of uploaded themes; the backup carries them and an update does not change them.',
				'de_DE' => 'Der Stand der Website - die Kopie ihres Themes, die Marke, das Stylesheet - und der davor sowie die Schlüssel hochgeladener Themes; das Backup nimmt sie mit und ein Update ändert sie nicht.',
			],
			'private/themes/<key>/' => [
				'en_US' => 'An own theme: a folder in the form of a theme package that the panel writes whole; a package folder copied in by hand is one as well.',
				'de_DE' => 'Ein eigenes Theme: ein Ordner in der Form eines Theme-Pakets, den das Panel ganz schreibt; ein von Hand hineinkopierter Paketordner ist ebenfalls eines.',
			],
			'public/fonts/<slug>/' => [
				'en_US' => 'A family installed from the repository: .woff2 files, LICENSE.txt and font.json, checked against the signed list and moved into place whole; a folder put there by hand is not replaced.',
				'de_DE' => 'Eine aus dem Repository installierte Familie: .woff2-Dateien, LICENSE.txt und font.json, gegen die signierte Liste geprüft und als Ganzes abgelegt; ein Ordner von Hand wird nicht ersetzt.',
			],
			'assets/style.css' => [
				'en_US' => 'The stylesheet of the project that every site has, kept in the layer project after the theme and written in the tab Own CSS; the kernel loads it with Themes off, and no theme or package holds it.',
				'de_DE' => 'Das Stylesheet des Projekts, das jede Site hat, in der Lage project nach dem Theme und im Reiter Eigenes CSS geschrieben; der Kernel lädt es auch ohne Themes, kein Theme oder Paket enthält es.',
			],
			'data/theme/.fonts/' => [
				'en_US' => 'The list of the repository as it was fetched last, with its signature, and the lock of an install; a backup does not carry them.',
				'de_DE' => 'Die zuletzt geholte Liste des Repositorys mit ihrer Signatur und die Sperre einer Installation; das Backup nimmt beides nicht mit.',
			],
		],
	],
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',					// the kernel with the register of components, the hook of the variants and of the blocks, the drafts of \Nino\Preview and the bundle in memory
	'php'					=> [],
	'requires'		=> [],
	'data'				=> [ '/data/theme' ],
	// Where the Fonts tab reads the signed list of fonts from, and the key that list has to be signed with. The two are the feature's own
	// repository by default; a project that publishes fonts of its own changes both
	'settings'		=> [
		'fontsUrl' => [
			'type'		=> 'url',
			'label'		=> [ 'en_US' => 'Font repository', 'de_DE' => 'Schrift-Repository' ],
			'hint'		=> [
				'en_US' => 'Where the signed list of fonts is published. The Fonts tab loads it when asked and never on its own. Empty, an address that is not https or the name of a host that is not public switches the Available half of that tab off.',
				'de_DE' => 'Wo die signierte Liste der Schriftarten veröffentlicht ist. Der Reiter Schriftarten lädt sie auf Knopfdruck und nie von selbst. Leer, keine https-Adresse oder ein Rechnername, der nicht öffentlich ist, schaltet die Hälfte „Verfügbar“ dieses Reiters ab.',
			],
			'default'	=> 'https://webfonts.getnino.dev/webfonts.json',
		],
		'fontsKey' => [
			'type'		=> 'text',
			'label'		=> [ 'en_US' => 'Repository public key', 'de_DE' => 'Öffentlicher Schlüssel des Repositorys' ],
			'hint'		=> [
				'en_US' => 'The PEM public key that signed the list, for a repository of your own. Empty means the feature\'s own.',
				'de_DE' => 'Der öffentliche Schlüssel im PEM-Format, mit dem die Liste signiert ist, für ein eigenes Repository. Leer heißt: der des Features.',
			],
			'default'	=> '',
		],
	],
	'assets'			=> [],
];
