<?php
// features/Design/library/themes/studio.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a floating header and a centred-stack footer over open sections, open cards and round regular buttons, with twice the whitespace - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Studio',
	'description'		=> 'A light, airy look for agencies, studios and freelancers: a floating bar with a small button, twice the whitespace, large titles in the regular weight with tight tracking and light subtitles, open sections and cards without frames, well-rounded pictures and round buttons, ruled lists, lines for tables and fields, pill tabs, a centred footer on the page ground and a soft blur as the page scrolls.',
	'description-de'	=> 'Ein leichter, luftiger Auftritt für Agenturen, Studios und Freiberufler: schwebende Leiste mit kleinem Button, doppelter Weißraum, große Titel im normalen Schnitt mit enger Laufweite, leichte Untertitel, offene Abschnitte und Karten ohne Rahmen, runde Bilder und Buttons, Linien für Listen, Tabellen und Felder, Pillen-Tabs, zentrierter Footer auf dem Seitengrund, weiche Unschärfe beim Scrollen.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 2,
		'radius'		=> 1,
		'radius-alt'	=> 2,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'alternative', 'image' => 'alternative' ],
		'motion'	=> [ 'effect' => 'blur', 'strength' => 'soft', 'speed' => 'medium', 'hover' => 'zoom', 'hover-strength' => 'soft' ],
		'parts'		=> [ 'header' => 'floating', 'footer' => 'centred-stack', 'section' => 'open', 'tiles' => 'regular', 'card' => 'open', 'buttons' => 'regular', 'lists' => 'ruled', 'tables' => 'line', 'forms' => 'line', 'tabs' => 'pills', 'accordion' => 'regular' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'shadow' => 1, 'position' => 0, 'cta' => 0, 'behaviour' => 0, 'nav-case' => 0, 'menu-size' => 0, 'menu-weight' => 0, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => -1, 'border-size' => 1, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 1, 'subtitle' => 0, 'title-tracking' => -1, 'space' => 0, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 0, 'align' => 0, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 0, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => -1, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'title-ground' => 0, 'hover' => 0, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 1, 'corners' => 2, 'outline' => 0, 'hover' => 0, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
			'tabs' 			=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'active' => '' ],
			'accordion' 	=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'marker' => 0, 'marker-ink' => '', 'weight' => 1 ],
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
		'tiers'		=> [ 'm' => [ 'size' => -2, 'inset' => -2, 'gap' => -2 ], 's' => [ 'title' => -1 ] ],
		'fonts'		=> [
			'title'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'open-sans', 'weight' => 'light', 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'bold', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
