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
				'en_US' => 'A set per part of a page, a knob per value, a preview beside them - and a compile that writes assets/theme.css.',
				'de_DE' => 'Ein Set je Bauteil einer Seite, ein Regler je Wert, eine Vorschau daneben – und ein Kompilieren, das assets/theme.css schreibt.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'ui',
	'version'			=> '0.1.0',
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
	'nino'				=> '^1.3',
	'requires'		=> [],
	// The whole setup: which set per part, the knob positions, the deviations,
	// the palette's colours and knobs, and the fingerprint of what was last
	// compiled. Small, and the one thing that cannot be derived again if it is
	// lost
	'data'				=> [ '/data/design.php' ],
	'settings'		=> [],
];
