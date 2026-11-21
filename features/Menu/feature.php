<?php
// features/Menu/feature.php - what the Features panel reads. The class is not
// declared here: features/Menu/ can only ever serve \Nino\Modules\Menu.
return [
	'key'					=> 'menu',
	'name'				=> [ 'en_US' => 'Menu', 'de_DE' => 'Menü' ],
	'description'	=> [
		'en_US' => 'Menu lets you build a navigation whose entries open panels of content of their own: titles, pictures, links and HTML+ in one to four columns. A component draws it in the header or the footer, and a script turns the panels as a keyboard and a screen reader expect. Without a script every panel stands open under its entry.',
		'de_DE' => 'Das Menü ermöglicht eine Navigation, deren Einträge Panels mit eigenem Inhalt öffnen: Titel, Bilder, Links und HTML+ in einer bis vier Spalten. Eine Komponente zeichnet es im Kopfbereich oder im Fußbereich, und ein Skript klappt die Panels so auf, wie es Tastatur und Screenreader erwarten. Ohne Skript steht jedes Panel offen unter seinem Eintrag.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[menu key="main"]' => [
				'en_US' => 'Draws that menu as a list of its entries, each with the button and the panel it has; without a key, or with one no menu has, the first menu.',
				'de_DE' => 'Zeichnet dieses Menü als Liste seiner Einträge, jeder mit dem Knopf und dem Panel, das er hat; ohne key oder mit einem Schlüssel, den kein Menü hat, das erste Menü.',
			],
			'[menu label="Main"]' => [
				'en_US' => 'The name a screen reader gives the navigation; without it the site\'s word for the main navigation, in the footer the one for the foot.',
				'de_DE' => 'Der Name, den ein Screenreader der Navigation gibt; ohne ihn das Wort der Website für die Hauptnavigation, im Fußbereich das für die Fußnavigation.',
			],
			'header-wrap, footer-col' => [
				'en_US' => 'The two places the component is offered in: where the header of Basis has its menu, and a column of the footer; in the header, swap header-nav for it in the Builder.',
				'de_DE' => 'Die zwei Plätze, an denen die Komponente angeboten wird: im Kopfbereich von Basis, wo dessen Menü steht, und in einer Spalte des Fußbereichs; dort tauschst Du header-nav im Builder gegen sie.',
			],
		],
		'markup' => [
			'nav.menu > details > ul.menu-list' => [
				'en_US' => 'What the component writes: li.menu-item with a.menu-link, button.menu-toggle (aria-expanded, aria-controls) and div.menu-panel, in it .menu-cols--<n> and .menu-col.',
				'de_DE' => 'Das schreibt die Komponente: li.menu-item mit a.menu-link, button.menu-toggle (aria-expanded, aria-controls) und div.menu-panel, darin .menu-cols--<n> und .menu-col.',
			],
			'.menu-title, .menu-subtitle, .menu-image, .menu-html, .menu-link--sub' => [
				'en_US' => 'The parts of a column: a title, a subtitle, a picture, HTML+ that the kernel\'s sanitizer has cleaned, and a link of the panel.',
				'de_DE' => 'Die Teile einer Spalte: ein Titel, ein Untertitel, ein Bild, HTML+, das der Sanitizer des Kernels gereinigt hat, und ein Link des Panels.',
			],
			'.menu-panel on a list of two rows' => [
				'en_US' => 'From 768px a panel hangs from its entry; where the list wraps into a second row it can cover the buttons below it, and Esc or a click outside closes it.',
				'de_DE' => 'Ab 768 px hängt ein Panel an seinem Eintrag; wo die Liste in eine zweite Zeile umbricht, kann es die Knöpfe darunter verdecken, und Esc oder ein Klick außerhalb schließt es.',
			],
			'--menu-width, --menu-gap, --menu-radius' => [
				'en_US' => 'The three knobs of the group, width of a panel, space between its columns and corners, which the Themes feature turns and a project sets on :root.',
				'de_DE' => 'Die drei Regler der Gruppe, Breite eines Panels, Abstand seiner Spalten und Ecken, die das Feature Themes dreht und ein Projekt auf :root setzt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Menu' => [
				'en_US' => 'Lists the menus in a table and makes a new one; a menu opens as a view of its own with its entries in order, and an entry as a view with the columns of its panel.',
				'de_DE' => 'Listet die Menüs in einer Tabelle und legt ein neues an; ein Menü öffnet sich als eigene Ansicht mit seinen Einträgen in der Reihenfolge, ein Eintrag als Ansicht mit den Spalten seines Panels.',
			],
			'Menu: saving' => [
				'en_US' => 'A menu is saved as a whole, with the texts of its entries as text keys and its pictures as image slots; a save that makes keys or slots asks for the rights of those panels.',
				'de_DE' => 'Ein Menü wird als Ganzes gespeichert, mit den Texten seiner Einträge als Textschlüssel und seinen Bildern als Bildplätze; wer dabei Schlüssel oder Plätze anlegt, braucht die Rechte dieser Panels.',
			],
			'Menu: HTML+' => [
				'en_US' => 'An HTML+ part is cleaned by the sanitizer of the kernel when it is saved and again when it is drawn, so paragraphs, lists, bold, italic and links stay and calls and fills are text.',
				'de_DE' => 'Ein HTML+-Teil reinigt der Sanitizer des Kernels beim Speichern und noch einmal beim Zeichnen; Absätze, Listen, fett, kursiv und Links bleiben, Aufrufe und Platzhalter sind Text.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the word of the button that opens a panel into the Text panel.',
				'de_DE' => 'Bringt das Wort des Knopfs, der ein Panel öffnet, ins Panel Texte.',
			],
			'blocks/menu-header/' => [
				'en_US' => 'A block: the header with the logo and the first menu, kept at the top.',
				'de_DE' => 'Ein Block: der Kopfbereich mit dem Logo und dem ersten Menü, oben festgehalten.',
			],
			'/data/menus.php' => [
				'en_US' => 'The menus, a map of menu keys to their entries; the file of Nino 1 is read as it is, and its texts stay under /feature/menu/<menu>/<name>.',
				'de_DE' => 'Die Menüs, eine Abbildung von Menüschlüsseln auf ihre Einträge; die Datei von Nino 1 wird so gelesen, wie sie ist, und ihre Texte bleiben unter /feature/menu/<menu>/<name>.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	// The kernel whose vocabulary it uses: the register of components with their groups and parts, the sanitizer and the panel blocks of the workbench
	'nino'				=> '^2.0',
	'requires'		=> [],
	// The menus. No '/nino/admin/restore' callback: the texts and the image slots a menu points
	// at come back with the same backup, so the plain copy of this file is the state they belong to
	'data'				=> [ '/data/menus.php' ],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Menu/assets/menu.js' ],
	'settings'		=> [],
];
