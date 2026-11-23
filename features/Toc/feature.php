<?php
// features/Toc/feature.php - what the Features panel reads. The class is not
// declared here: features/Toc/ can only ever serve \Nino\Modules\Toc.
return [
	'key'					=> 'toc',
	'name'				=> [ 'en_US' => 'Table of Contents', 'de_DE' => 'Inhaltsverzeichnis' ],
	'description'	=> [
		'en_US' => 'Table of Contents lists the headings of a long page, built from the page itself. It marks the section that is being read, and every heading can carry an anchor, so a passage can be linked to. The list comes in three looks: a line, numbered, or a box.',
		'de_DE' => 'Das Inhaltsverzeichnis listet die Überschriften einer langen Seite, aus der Seite selbst gebaut. Es markiert den Abschnitt, der gerade gelesen wird, und jede Überschrift kann einen Anker tragen, damit sich eine Stelle verlinken lässt. Die Liste gibt es in drei Ausführungen: mit Linie, nummeriert oder als Kasten.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[toc]' => [
				'en_US' => 'Builds the list from the headings of the page, h2 and h3 by default, and stays hidden where JavaScript does not run.',
				'de_DE' => 'Baut die Liste aus den Überschriften der Seite, standardmäßig h2 und h3, und bleibt verborgen, wo JavaScript nicht läuft.',
			],
			'[toc levels="2" within="#article" title="On this page" sticky]' => [
				'en_US' => 'Takes h2 only, the headings from inside one element, a title of its own, and keeps the list at the edge of the window beside a long text.',
				'de_DE' => 'Nimmt nur h2, die Überschriften aus einem Element, einen eigenen Titel und hält die Liste neben einem langen Text am Rand des Fensters.',
			],
			'[toc variant="numbered"]' => [
				'en_US' => 'The entries with numbers in front of them and no line, and variant="box" for a ground of its own with a line all round.',
				'de_DE' => 'Die Einträge mit Zahlen davor und ohne Linie, und variant="box" für einen eigenen Grund mit einer Linie ringsum.',
			],
		],
		'markup' => [
			'class="toc-skip"' => [
				'en_US' => 'On a heading it is left out of the list and gets no anchor.',
				'de_DE' => 'An einer Überschrift bleibt sie aus der Liste und bekommt keinen Anker.',
			],
			'id="…" on a listed heading' => [
				'en_US' => 'Every listed heading gets an id made from its words, and an id already there is kept.',
				'de_DE' => 'Jede Überschrift der Liste bekommt eine id aus ihren Worten, und eine vorhandene id bleibt.',
			],
			'aria-current="true"' => [
				'en_US' => 'The script sets it on the entry of the section that is being read, on one entry at a time.',
				'de_DE' => 'Das Skript setzt es auf den Eintrag des Abschnitts, der gerade gelesen wird, immer auf einen Eintrag.',
			],
		],
		'routes' => [],
		'panel' => [
			'Settings: Anchors on every heading' => [
				'en_US' => 'On puts a link on every heading the list holds, and off draws no link.',
				'de_DE' => 'An setzt an jede Überschrift der Liste einen Link, und aus zeichnet keinen Link.',
			],
			'Themes: the part of the group Table of contents' => [
				'en_US' => 'The Themes panel offers the list with knobs for the indent of the sub-level and the space between the entries.',
				'de_DE' => 'Das Panel Themes bietet die Liste mit Reglern für den Einzug der Unterebene und den Abstand der Einträge an.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the two words of the list and its anchors into the Text panel.',
				'de_DE' => 'Bringt die zwei Wörter der Liste und ihrer Anker ins Panel Texte.',
			],
			'blocks/toc-article/' => [
				'en_US' => 'A block: the list beside a narrow article with headings of two levels.',
				'de_DE' => 'Ein Block: die Liste neben einem schmalen Artikel mit Überschriften auf zwei Ebenen.',
			],
		],
	],
	// It is a way through what is already written on a page, which is the
	// thing the Features panel files under content
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'basis' ],
	// Nothing under data/: the list is the page, read as it stands
	'data'				=> [],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Toc/assets/toc.js' ],
	'settings'		=> [
		'anchors' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Anchors on every heading', 'de_DE' => 'Anker an jeder Überschrift' ],
			'hint'		=> [
				'en_US' => 'Put a link on every heading the list holds, so a passage can be linked to. Off: no link is drawn, and those headings keep only the ids the list needs to reach them.',
				'de_DE' => 'Setzt an jede Überschrift der Liste einen Link, damit sich eine Stelle verlinken lässt. Aus: Es wird kein Link gezeichnet, und die Überschriften behalten nur die ids, über die die Liste sie erreicht.',
			],
			'default'	=> true,
		],
	],
];
