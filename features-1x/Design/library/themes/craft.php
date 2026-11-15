<?php
// features/Design/library/themes/craft.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a stacked header with a line in the brand colour and a footer with a description over regular sections, poster tiles, regular cards and regular buttons, with serif titles - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Craft',
	'description'		=> 'A warm look for cafés, restaurants, shops and trades: serif titles in the middle over a short rule in the brand colour, the name in the centre over the menu, the title of a cover on a soft ground, rounded poster tiles with a gap, centred cards with a soft shadow, ruled lists, a price list without boxes, filled fields, pill tabs and a talkative footer, set a little larger on a contained page.',
	'description-de'	=> 'Ein warmer Auftritt für Cafés, Restaurants, Läden und Handwerk: Serifentitel mittig über kurzer Linie in der Markenfarbe, der Name mittig über dem Menü, Cover-Titel auf weichem Grund, gerundete Poster-Kacheln mit Lücke, zentrierte Karten mit weichem Schatten, Linienlisten, Preisliste ohne Kästen, gefüllte Felder, Pillen-Tabs, gesprächiger Footer, etwas größer gesetzt auf begrenzter Seite.',
	'setup'				=> [
		'size'		=> 1,
		'width'		=> -1,
		'whitespace'	=> 0,
		'radius'		=> 1,
		'radius-alt'	=> 1,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => 'slide-up', 'strength' => 'soft', 'speed' => 'medium', 'hover' => 'bright', 'hover-strength' => 'medium' ],
		'parts'		=> [ 'header' => 'stacked', 'footer' => 'brand-with-description', 'section' => 'regular', 'tiles' => 'poster', 'card' => 'regular', 'buttons' => 'regular', 'lists' => 'ruled', 'tables' => 'line', 'forms' => 'filled', 'tabs' => 'pills', 'accordion' => 'regular' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => -1, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 1, 'border-color' => 1, 'shadow' => 0, 'position' => 0, 'cta' => -1, 'behaviour' => 0, 'nav-case' => 0, 'menu-size' => 0, 'menu-weight' => 0, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'rule' => 2, 'rule-length' => -1, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => -1, 'rule-color' => 1, 'align' => 0, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 2, 'place' => 1, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 0, 'shadow' => 1, 'tone' => 1, 'title-ground' => 0, 'hover' => 1, 'align' => -1, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 0, 'corners' => 1, 'outline' => 0, 'hover' => 0, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
			'tabs' 			=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'active' => '' ],
			'accordion' 	=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'marker' => 1, 'marker-ink' => '', 'weight' => 1 ],
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
		'tiers'		=> [ 'm' => [ 'size' => -1, 'header' => -2 ], 's' => [ 'size' => 0, 'title' => -1 ] ],
		'fonts'		=> [
			'title'		=> [ 'family' => 'system-serif', 'weight' => 'bold', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'system-serif', 'weight' => 'normal', 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'system-serif', 'weight' => 'bold', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
