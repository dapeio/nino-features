<?php
// features/Design/library/themes/pop.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a dark pill menu and a footer of two panels over panel sections, regular cards and round key buttons - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Pop',
	'description'		=> 'A friendly, round, playful look for children, events, clubs and apps: a dark bar with a pill menu and a large button, sections as rounded sheets, large tight titles, centred cards with a shadow that lift, round key buttons, pill tabs, boxed accordions, round filled fields, emphasised list marks, a footer of two panels, and a quick zoom as the page scrolls.',
	'description-de'	=> 'Ein freundlicher, runder, verspielter Auftritt für Kinder, Events, Vereine und Apps: dunkle Leiste mit Pillen-Menü und großem Button, Abschnitte als gerundete Bögen, große enge Titel, zentrierte Karten mit Schatten, die sich heben, runde Key-Buttons, Pillen-Tabs, Akkordeons im Rahmen, runde gefüllte Felder, betonte Listenzeichen, Footer aus zwei Flächen und ein schneller Zoom beim Scrollen.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 0,
		'radius'		=> 2,
		'radius-alt'	=> 2,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => 'zoom', 'strength' => 'soft', 'speed' => 'fast', 'hover' => 'zoom-bright', 'hover-strength' => 'medium' ],
		'parts'		=> [ 'header' => 'pill-menu', 'footer' => 'two-panels', 'section' => 'panel', 'tiles' => 'regular', 'card' => 'regular', 'buttons' => 'key', 'lists' => 'regular', 'tables' => 'filled', 'forms' => 'filled', 'tabs' => 'pills', 'accordion' => 'boxed' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => -1, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 1, 'border-size' => 0, 'border-color' => 0, 'shadow' => 0, 'position' => 0, 'cta' => 1, 'behaviour' => 0, 'nav-case' => 0, 'menu-size' => 0, 'menu-weight' => 0, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 1, 'subtitle' => 0, 'title-tracking' => -1, 'space' => 0, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 0, 'align' => 0, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 0, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 0, 'shadow' => 2, 'tone' => 0, 'title-ground' => 0, 'hover' => 1, 'align' => -1, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 1, 'corners' => 2, 'outline' => 0, 'hover' => 1, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 1 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 2, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
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
		'tiers'		=> [ 'm' => [ 'size' => -1 ], 's' => [ 'title' => -2 ] ],
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
