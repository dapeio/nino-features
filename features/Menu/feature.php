<?php
// features/Menu/feature.php - what the Features panel reads. The class is not
// declared here: features/Menu/ can only ever serve \Nino\Modules\Menu.
return [
	'key'					=> 'menu',
	'name'				=> [ 'en_US' => 'Menu', 'de_DE' => 'Menü' ],
	'description'	=> [
		'en_US' => 'Gives the site a menu whose entries open a dropdown panel of content of their own - titles, pictures, links and HTML+ in one to four columns - and a phone gets the same panels as an accordion in the burger overlay. It holds the menus in a file of its own and a small editor in the workbench; the Design chooses it for a navigation.',
		'de_DE' => 'Gibt der Website ein Menü, dessen Einträge ein Dropdown-Panel mit eigenem Inhalt öffnen - Titel, Bilder, Links und HTML+ in einer bis vier Spalten - und auf dem Telefon werden daraus Ziehharmonika-Panels im Burger-Overlay. Es hält die Menüs in einer eigenen Datei und bringt einen kleinen Editor in der Workbench mit; das Design wählt es für eine Navigation.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[menu /main]' => [
				'en_US' => 'The menu "main" as a list of its entries, each with its panel where it has one. The first word is the key of a menu of the Menu panel; it may be a key of the kernel\'s navigations.',
				'de_DE' => 'Das Menü „main“ als Liste seiner Einträge, jeder mit seinem Dropdown-Panel, wo er eines hat. Das erste Wort ist der Schlüssel eines Menüs aus dem Panel Menü; es darf ein Schlüssel der Navigationen des Kerns sein.',
			],
			'[menu /main burger]' => [
				'en_US' => 'The same inside the hull of the kernel\'s burger menu: below 768 px the list folds away behind the button and the panels open as an accordion under their entry.',
				'de_DE' => 'Dasselbe in der Hülle des Burger-Menüs des Kerns: unter 768 px klappt die Liste hinter dem Knopf weg, und die Panels öffnen sich als Ziehharmonika unter ihrem Eintrag.',
			],
		],
		'markup' => [
			'class="nino-menu"' => [
				'en_US' => 'ul.nino-menu with li.nino-menu-item (--panel, --current), a.nino-menu-link, button.nino-menu-toggle, div.nino-menu-panel with .nino-menu-cols-<n> and .nino-menu-col, and in a column p.nino-menu-title, p.nino-menu-subtitle, a.nino-menu-link--sub and img.nino-menu-image.',
				'de_DE' => 'ul.nino-menu mit li.nino-menu-item (--panel, --current), a.nino-menu-link, button.nino-menu-toggle, div.nino-menu-panel mit .nino-menu-cols-<n> und .nino-menu-col, darin p.nino-menu-title, p.nino-menu-subtitle, a.nino-menu-link--sub und img.nino-menu-image.',
			],
		],
		'routes' => [],
		'panel' => [
			'Menu' => [
				'en_US' => 'The menus and their editor: entries as links to a page or an address, each with an optional panel of one to four columns of titles, subtitles, pictures, links and HTML+. A developer\'s panel, because HTML+ and addresses are written as typed. Words are text keys /feature/menu/<menu>/<name>, pictures image slots of the same name.',
				'de_DE' => 'Die Menüs und ihr Editor: Einträge als Links auf eine Seite oder eine Adresse, jeder mit einem Dropdown-Panel aus einer bis vier Spalten mit Titeln, Untertiteln, Bildern, Links und HTML+. Ein Panel für Entwickler, denn HTML+ und Adressen stehen so da, wie sie getippt sind. Wörter sind Textschlüssel /feature/menu/<Menü>/<Name>, Bilder Bild-Slots desselben Namens.',
			],
		],
		'callbacks' => [
			'/feature/design/navigations' => [
				'en_US' => 'Answers the Design with one navigation for each menu: [menu /<key> burger] for the header and [menu /<key>] for the footer.',
				'de_DE' => 'Antwortet dem Design mit einer Navigation je Menü: [menu /<Schlüssel> burger] für die Kopfzeile und [menu /<Schlüssel>] für die Fußzeile.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The word of the button that opens a panel, into the Text panel.',
				'de_DE' => 'Das Wort des Knopfs, der ein Panel öffnet, ins Panel Texte.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^1.10',
	'requires'		=> [],
	// The menus. No '/nino/admin/restore' callback: the texts and the image slots a menu points
	// at come back with the same backup, so the plain copy of this file is the state they belong to
	'data'				=> [ '/data/menus.php' ],
	'assets'			=> [ '/features/Menu/assets/menu.css', '/features/Menu/assets/menu.js' ],
	'settings'		=> [],
];
