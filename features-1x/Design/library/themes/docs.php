<?php
// features/Design/library/themes/docs.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a side rail and a one-legal-row footer over regular sections, boxed cards and key buttons, set small and dense - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Docs',
	'description'		=> 'A narrow, dense, sober look for documentation, manuals and knowledge bases: a side rail for the navigation, small type on a reading column with little air, left-aligned titles in the colour of the text over a rule, subtitles in the monospace face, hairline boxes for cards and accordions, key buttons, tables with a fixed head, filled fields and one plain footer row.',
	'description-de'	=> 'Ein schmaler, dichter, nüchterner Auftritt für Dokumentation, Handbücher und Wissensdatenbanken: Seitenleiste für die Navigation, kleine Schrift auf einer Lesespalte mit wenig Luft, linksbündige Titel in Textfarbe über einer Linie, Untertitel in Monospace, Haarlinien-Kästen für Karten und Akkordeons, Key-Buttons, Tabellen mit festem Kopf, gefüllte Felder und eine schlichte Footer-Zeile.',
	'setup'				=> [
		'size'		=> -1,
		'width'		=> -2,
		'whitespace'	=> -1,
		'radius'		=> 0,
		'radius-alt'	=> 1,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => 'none', 'hover-strength' => '' ],
		'parts'		=> [ 'header' => 'side-rail', 'footer' => 'one-legal-row', 'section' => 'regular', 'tiles' => 'regular', 'card' => 'box', 'buttons' => 'key', 'lists' => 'regular', 'tables' => 'filled', 'forms' => 'filled', 'tabs' => 'regular', 'accordion' => 'boxed' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'shadow' => 0, 'position' => 0, 'cta' => 0, 'behaviour' => 0, 'nav-case' => 0, 'menu-size' => 0, 'menu-weight' => 0, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => -1, 'border-size' => 1, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => -1, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'rule' => 1, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 0, 'align' => -1, 'measure' => -1, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 0, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'title-ground' => 0, 'hover' => -1, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 0, 'corners' => 0, 'outline' => 0, 'hover' => 0, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
			'tabs' 			=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'active' => '' ],
			'accordion' 	=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'marker' => 1, 'marker-ink' => '', 'weight' => 0 ],
		],
		'styles'	=> [
			'header' => [],
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
		'fonts'		=> [
			'title'		=> [ 'family' => 'open-sans', 'weight' => 'bold', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'system-mono', 'weight' => 'normal', 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'bold', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
