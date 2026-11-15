<?php
// features/Design/library/themes/corporate.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a dark top bar and a footer with brand and contact over panel sections, regular cards and key buttons - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Corporate',
	'description'		=> 'A calm, trustworthy look for companies, law firms and services: a dark bar under a utility strip with phone and mail, sections as inset sheets with a short rule in the brand colour under dark titles, cards with a line and a shadow that lift, key buttons, tables with a fixed head, hardly rounded corners and a footer with brand and contact.',
	'description-de'	=> 'Ein ruhiger, verlässlicher Auftritt für Unternehmen, Kanzleien und Dienstleister: dunkle Leiste unter einer Serviceleiste mit Telefon und E-Mail, Abschnitte als eingerückte Bögen mit kurzer Linie in der Markenfarbe unter dunklen Titeln, Karten mit Linie und Schatten, die sich heben, Key-Buttons, Tabellen mit festem Kopf, kaum gerundete Ecken und ein Footer mit Marke und Kontakt.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 0,
		'radius'		=> -1,
		'radius-alt'	=> 0,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => 'zoom', 'hover-strength' => 'soft' ],
		'parts'		=> [ 'header' => 'top-bar', 'footer' => 'brand-and-contact', 'section' => 'panel', 'tiles' => 'regular', 'card' => 'regular', 'buttons' => 'key', 'lists' => 'regular', 'tables' => 'filled', 'forms' => 'regular', 'tabs' => 'regular', 'accordion' => 'regular' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => -1, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 1, 'border-size' => 0, 'border-color' => 0, 'shadow' => 1, 'position' => 0, 'cta' => 0, 'behaviour' => 0, 'nav-case' => 1, 'menu-size' => 0, 'menu-weight' => 0, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 1, 'border-size' => 0, 'border-color' => 0, 'divider' => 1, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'rule' => 2, 'rule-length' => -1, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 1, 'align' => -1, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 0, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 1, 'shadow' => 1, 'tone' => 0, 'title-ground' => 0, 'hover' => 1, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
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
		'tiers'		=> [ 'm' => [ 'size' => -2, 'header' => -1 ], 's' => [ 'title' => -1 ] ],
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
