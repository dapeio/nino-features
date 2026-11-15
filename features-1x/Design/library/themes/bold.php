<?php
// features/Design/library/themes/bold.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a regular header with a heavy line and a footer of two panels over regular sections, poster tiles, boxed cards and hard buttons - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Bold',
	'description'		=> 'A loud, poster-like look for campaigns, products and events: a bar with a heavy line and a menu in capitals, large titles on a full ground, poster tiles with a gap, boxed cards with a heavy line, hard square buttons in capitals with an offset shadow, contrast tables and forms, a footer of two panels, and sections that slide in.',
	'description-de'	=> 'Ein lauter, plakativer Auftritt für Kampagnen, Produkte und Veranstaltungen: Leiste mit kräftiger Linie und Menü in Versalien, große Titel auf voller Fläche, Poster-Kacheln mit Lücke, Karten im kräftigen Rahmen, harte eckige Buttons in Versalien mit versetztem Schatten, Kontrast-Tabellen und -Formulare, Footer aus zwei Flächen und Abschnitte, die hereingleiten.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 1,
		'whitespace'	=> 1,
		'radius'		=> -2,
		'radius-alt'	=> -2,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => 'slide-up', 'strength' => 'medium', 'speed' => 'fast', 'hover' => 'zoom', 'hover-strength' => 'hard' ],
		'parts'		=> [ 'header' => 'regular', 'footer' => 'two-panels', 'section' => 'regular', 'tiles' => 'poster', 'card' => 'box', 'buttons' => 'hard', 'lists' => 'regular', 'tables' => 'contrast', 'forms' => 'contrast', 'tabs' => 'regular', 'accordion' => 'boxed' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => -1, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 2, 'border-color' => -1, 'shadow' => 0, 'position' => 0, 'cta' => 0, 'behaviour' => 0, 'nav-case' => 1, 'menu-size' => 1, 'menu-weight' => 1, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 1 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 1, 'title' => 2, 'subtitle' => 0, 'title-tracking' => -1, 'space' => 0, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 1, 'rule-color' => 0, 'align' => -1, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 2, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => -1, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 2, 'shadow' => 0, 'tone' => 0, 'title-ground' => 1, 'hover' => 0, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 1, 'spread' => 0, 'tracking' => 1, 'space' => 0, 'corners' => 0, 'outline' => 0, 'hover' => 0, 'case' => 1 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 1 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
			'tabs' 			=> [ 'scale' => 0, 'space' => 0, 'line' => 2, 'active' => '' ],
			'accordion' 	=> [ 'scale' => 0, 'space' => 0, 'line' => 2, 'marker' => 0, 'marker-ink' => '', 'weight' => 0 ],
		],
		'styles'	=> [
			'header' => [ 'nav-hover' => 'pill' ],
			'footer' => [],
			'section' => [],
			'tiles' => [],
			'card' => [],
			'buttons' => [],
			'lists' => [],
			'tables' => [],
			'forms' => [],
			'tabs' => [],
			'accordion' => [],
		],
		'features'	=> [],
		'tiers'		=> [ 'm' => [ 'size' => -2, 'inset' => -2, 'gap' => -2, 'header' => -2 ], 's' => [ 'size' => -1, 'title' => -2 ] ],
		'fonts'		=> [
			'title'		=> [ 'family' => 'open-sans', 'weight' => 'bold', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'bold', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
