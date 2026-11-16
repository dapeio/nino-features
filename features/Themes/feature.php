<?php
// The class is not declared: features/Themes/ can only serve \Nino\Modules\Themes
return [
	'key'					=> 'themes',
	'name'				=> [ 'en_US' => 'Themes', 'de_DE' => 'Themes' ],
	'description'	=> [
		'en_US' => 'Themes gives the site a look that is chosen and not written: a theme brings a part for the page and for each group of components, and the project mixes them, sets its colours and fonts and keeps the result as one stylesheet. Own css goes to the end of it as it was written. The last state is kept, one step back.',
		'de_DE' => 'Themes gibt der Website ein Aussehen, das gewählt und nicht geschrieben wird: Ein Theme bringt einen Teil für die Seite und für jede Gruppe von Komponenten, das Projekt mischt sie, setzt Farben und Schriften und behält das Ergebnis als ein Stylesheet. Eigenes CSS steht am Ende, wie es geschrieben wurde. Der letzte Stand bleibt erhalten, einen Schritt zurück.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'themes/nino/' => [
				'en_US' => 'Nino is the theme that comes with the feature: every knob in the middle, a bar for the header, a dark surface for the footer, a band for sections and a rule below large titles.',
				'de_DE' => 'Nino ist das Theme, das dem Feature beiliegt: jeder Regler in der Mitte, ein Balken für den Header, eine dunkle Fläche für den Footer, ein Band für Abschnitte und eine Linie unter großen Titeln.',
			],
			'themes/<key>/theme.json' => [
				'en_US' => 'A theme is a folder with its key, version, name, description and suggestions for the brand; nothing in it is code and no file but theme.json, part.json and part.css is read.',
				'de_DE' => 'Ein Theme ist ein Ordner mit Schlüssel, Version, Name, Beschreibung und Vorschlägen für die Marke; nichts darin ist Code, und gelesen werden nur theme.json, part.json und part.css.',
			],
			'parts/<group>/part.json' => [
				'en_US' => 'The part of a group names its contract, sets the steps of the knobs of the group, gives a component up to two knobs of its own, brings variants and chooses the standard one.',
				'de_DE' => 'Der Teil einer Gruppe nennt seinen Vertrag, stuft die Regler der Gruppe um, gibt einer Komponente bis zu zwei eigene Regler, bringt Varianten mit und wählt die Standard-Variante.',
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
			'?nino-theme-preview=<token>' => [
				'en_US' => 'Any page of the site, asked with the token of a preview by the session that holds it, shows the draft while the token lasts; the public stylesheet is not touched.',
				'de_DE' => 'Jede Seite der Website, mit dem Token einer Vorschau von der Sitzung aufgerufen, die es hält, zeigt den Entwurf, solange das Token gilt; das öffentliche Stylesheet bleibt unberührt.',
			],
		],
		'panel' => [
			'Themes' => [
				'en_US' => 'The API of the workbench for the themes - list, open, preview, save, save-as, apply, restore and delete - for the right /_admin/themes/manage.',
				'de_DE' => 'Die API der Werkbank für die Themes - list, open, preview, save, save-as, apply, restore und delete - für das Recht /_admin/themes/manage.',
			],
		],
		'callbacks' => [
			'/nino/components/variants' => [
				'en_US' => 'Adds the variants of the parts in use to the components that have them and chooses the one that stands where a page names none.',
				'de_DE' => 'Fügt den Komponenten die Varianten der verwendeten Teile hinzu und wählt die, die gilt, wo eine Seite keine nennt.',
			],
			'/nino/themes/roots' => [
				'en_US' => 'Names more folders to read themes from, each holding <key>/theme.json, beside the one this feature brings.',
				'de_DE' => 'Nennt weitere Ordner, aus denen Themes gelesen werden, je mit <key>/theme.json, neben dem, den dieses Feature mitbringt.',
			],
		],
		'install' => [
			'assets/theme.css' => [
				'en_US' => 'The stylesheet of the layer nino.theme, written by the compiler with the brand, the steps of the knobs, the parts and the own css; a file somebody else wrote is replaced only when told to.',
				'de_DE' => 'Das Stylesheet der Lage nino.theme, vom Compiler geschrieben mit Marke, Reglerstufen, Teilen und eigenem CSS; eine Datei, die jemand anderes schrieb, wird nur auf Anweisung ersetzt.',
			],
			'data/theme/' => [
				'en_US' => 'The state, the last state, the own themes, the brand and a copy of every part in use, which the backup carries and an update does not change.',
				'de_DE' => 'Der Stand, der letzte Stand, die eigenen Themes, die Marke und eine Kopie jedes verwendeten Teils, die das Backup mitnimmt und ein Update nicht ändert.',
			],
		],
	],
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',					// the kernel with the register of components, the hook of the variants and the bundle in memory
	'php'					=> [],
	'requires'		=> [],
	'data'				=> [ '/data/theme' ],
	'settings'		=> [],
	'assets'			=> [],
];
