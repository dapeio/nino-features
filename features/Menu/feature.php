<?php
// features/Menu/feature.php - what the Features panel reads. The class is not
// declared here: features/Menu/ can only ever serve \Nino\Modules\Menu.
return [
	'key'					=> 'menu',
	'name'				=> [ 'en_US' => 'Menu', 'de_DE' => 'Menü' ],
	'description'	=> [
		'en_US' => 'Menu lets you build a navigation whose entries open dropdown panels of content of their own: titles, pictures, links and HTML+ in one to four columns. On a phone they become accordion panels in the burger overlay. The Design feature chooses it for a navigation.',
		'de_DE' => 'Das Menü ermöglicht eine Navigation, deren Einträge Dropdown-Panels mit eigenem Inhalt öffnen: Titel, Bilder, Links und HTML+ in einer bis vier Spalten. Auf dem Telefon werden daraus Ziehharmonika-Panels im Burger-Overlay. Das Design wählt es für eine Navigation.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[menu /main]' => [
				'en_US' => 'Draws the menu main as a list of its entries, each with its dropdown panel where it has one; the first word is the key of a menu.',
				'de_DE' => 'Zeichnet das Menü main als Liste seiner Einträge, jeder mit seinem Dropdown-Panel, wo er eines hat; das erste Wort ist der Schlüssel eines Menüs.',
			],
			'[menu /main burger]' => [
				'en_US' => 'The same inside the kernel\'s burger menu; below 768 px the list folds away behind the button.',
				'de_DE' => 'Dasselbe in der Hülle des Burger-Menüs des Kernels; unter 768 px klappt die Liste hinter dem Knopf weg.',
			],
		],
		'markup' => [
			'class="nino-menu"' => [
				'en_US' => 'This is what the shortcode writes: ul.nino-menu with li.nino-menu-item, a.nino-menu-link, button.nino-menu-toggle and div.nino-menu-panel with .nino-menu-cols-<n> and .nino-menu-col.',
				'de_DE' => 'Das schreibt der Shortcode: ul.nino-menu mit li.nino-menu-item, a.nino-menu-link, button.nino-menu-toggle und div.nino-menu-panel mit .nino-menu-cols-<n> und .nino-menu-col.',
			],
			'class="nino-menu-title"' => [
				'en_US' => 'In a column p.nino-menu-title, p.nino-menu-subtitle, a.nino-menu-link--sub and img.nino-menu-image; the item carries nino-menu-item--panel and nino-menu-item--current.',
				'de_DE' => 'In einer Spalte p.nino-menu-title, p.nino-menu-subtitle, a.nino-menu-link--sub und img.nino-menu-image; der Eintrag trägt nino-menu-item--panel und nino-menu-item--current.',
			],
		],
		'routes' => [],
		'panel' => [
			'Menu' => [
				'en_US' => 'Edits the menus: entries as links to a page or an address, each with a panel of titles, subtitles, pictures, links and HTML+, as text keys /feature/menu/<menu>/<name>.',
				'de_DE' => 'Bearbeitet die Menüs: Einträge als Links auf eine Seite oder Adresse, jeder mit einem Panel aus Titeln, Untertiteln, Bildern, Links und HTML+, als Textschlüssel /feature/menu/<menu>/<name>.',
			],
		],
		'callbacks' => [
			'/feature/design/navigations' => [
				'en_US' => 'Answers the Design with one navigation for each menu: [menu /<key> burger] for the header, [menu /<key>] for the footer.',
				'de_DE' => 'Antwortet dem Design mit einer Navigation je Menü: [menu /<key> burger] für die Kopfzeile, [menu /<key>] für die Fußzeile.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the word of the button that opens a panel into the Text panel.',
				'de_DE' => 'Bringt das Wort des Knopfs, der ein Panel öffnet, ins Panel Texte.',
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
