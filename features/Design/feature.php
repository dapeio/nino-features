<?php
// features/Design/feature.php - what the Features panel reads. The class is
// not declared here: features/Design/ can only ever serve \Nino\Modules\Design.
return [
	'key'					=> 'design',
	'name'				=> [ 'en_US' => 'Design', 'de_DE' => 'Design' ],
	'description'	=> [
		'en_US' => 'The look, per part of a page rather than per page: a set for headings, surfaces, articles, buttons, forms, lists and blocks, compiled into one stylesheet.',
		'de_DE' => 'Das Aussehen, pro Bauteil einer Seite statt pro Seite: je ein Set für Überschriften, Flächen, Artikel, Buttons, Formulare, Listen und Bausteine, in ein Stylesheet kompiliert.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Design' => [
				'en_US' => 'A set per part of a page, a knob per value, a preview beside them - and an apply that asks first, writes assets/theme.css and the two frame templates, and keeps the previous version to restore.',
				'de_DE' => 'Ein Set je Bauteil einer Seite, ein Regler je Wert, eine Vorschau daneben – und ein Anwenden, das vorher nachfragt, assets/theme.css und die beiden Rahmen-Templates schreibt und die Vorversion zum Wiederherstellen aufhebt.',
			],
			'Structure: Part and Variant' => [
				'en_US' => 'Which of the nine parts is open - header, footer, atf, section, article, buttons, forms, lists, blocks - or Global, and which variant it is given.',
				'de_DE' => 'Welcher der neun Bauteile offen ist - header, footer, atf, section, article, buttons, forms, lists, blocks - oder Global, und welche Variante er bekommt.',
			],
			'Structure: Finetuning' => [
				'en_US' => 'One row per knob the variant answers to - headings, spacing, corners, width - at -1, 0 or +1; a row follows the level above until it is moved. Global holds the root size.',
				'de_DE' => 'Eine Zeile je Regler, auf den die Variante hört - Überschriften, Abstände, Ecken, Breite - auf -1, 0 oder +1; eine Zeile folgt der Ebene darüber, bis sie bewegt wird. Global hält die Grundgröße.',
			],
			'Colours' => [
				'en_US' => 'The second tab: a brand colour, a second colour and the palette knobs - temperature, saturation, contrast.',
				'de_DE' => 'Der zweite Reiter: eine Markenfarbe, eine zweite Farbe und die Regler der Palette – Temperatur, Sättigung, Kontrast.',
			],
			'Save draft / Apply to website / Reset' => [
				'en_US' => 'Save draft writes data/design.php and nothing else. Apply asks first - it lists which files are replaced and which shortcodes of a frame would be lost - then writes the three files. Reset goes back to what was saved.',
				'de_DE' => 'Entwurf speichern schreibt nur data/design.php. Anwenden fragt vorher nach – es nennt, welche Dateien ersetzt werden und welche Shortcodes eines Rahmens verloren gingen – und schreibt dann die drei Dateien. Zurücksetzen geht zurück auf das Gespeicherte.',
			],
			'Restore previous version' => [
				'en_US' => 'One slot: the three files as they were before the last apply. Restoring swaps the slot with the present, so it can be undone by restoring again.',
				'de_DE' => 'Ein Platz: die drei Dateien, wie sie vor dem letzten Anwenden waren. Wiederherstellen tauscht den Platz mit dem Gegenwärtigen, lässt sich also durch erneutes Wiederherstellen rückgängig machen.',
			],
			'Preview' => [
				'en_US' => 'The specimen page against this project - its menu, logo and fonts - under the stylesheet the selection on screen compiles to, at phone, tablet or desktop width.',
				'de_DE' => 'Die Musterseite gegen dieses Projekt – Menü, Logo und Schriften – unter dem Stylesheet, das die Auswahl auf dem Bildschirm ergibt, in Handy-, Tablet- oder Desktopbreite.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	/*	Two floors, and the higher one wins. 1.2 is where the wizard stopped
		asking about the look and the base unit started delivering
		assets/theme.css as one file; everything here writes over that file, so
		a kernel that still spread the look over style.design.css,
		style.theme.*.css and two frame stylesheets would be compiled for a
		bundle it does not have. And the sectioned 'manual' map below is only
		read by a kernel newer than the v1.2.0-beta tag - on that one
		Features::manifest() refuses this file outright - so a constraint
		admitting 1.2 offered a feature that could not be installed. ^1.3 is
		the first that names only a kernel which does both. */
	'nino'				=> '^1.4',
	'requires'		=> [],
	// The whole setup: which set per part, the knob positions, the deviations,
	// the palette's colours and knobs, and the fingerprint of what was last
	// compiled. Small, and the one thing that cannot be derived again if it is
	// lost. And the one version before the last apply - the three files it
	// replaced and the setup that went with them - which a backup carries too
	// and a restore of one may simply replace
	'data'				=> [ '/data/design.php', '/data/design-previous.php' ],
	'settings'		=> [],
];
