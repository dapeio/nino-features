<?php
// features/Design/library/themes/civic.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a top bar and a sitemap footer over regular sections, boxed cards and square regular buttons, in the system typeface - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Civic',
	'description'		=> 'A plain, dense and legible look for authorities, associations, clubs and schools: a utility strip with phone and mail over a bar with a line in the brand colour, a sitemap footer, left-aligned sections with titles in the colour of the text, boxed cards and accordions, bordered striped tables, contrast forms, square buttons, the system typeface and no motion.',
	'description-de'	=> 'Ein sachlicher, dichter und gut lesbarer Auftritt für Behörden, Vereine, Verbände und Schulen: Serviceleiste mit Telefon und E-Mail über einer Leiste mit Linie in der Markenfarbe, Sitemap-Footer, linksbündige Abschnitte mit Titeln in Textfarbe, Karten und Akkordeons im Rahmen, gestreifte Tabellen mit Linien, Kontrast-Formulare, eckige Buttons, die Systemschrift und keine Bewegung.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> -1,
		'whitespace'	=> -1,
		'radius'		=> 0,
		'radius-alt'	=> 0,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => 'none', 'hover-strength' => '' ],
		'parts'		=> [ 'header' => 'top-bar', 'footer' => 'sitemap', 'section' => 'regular', 'tiles' => 'regular', 'card' => 'box', 'buttons' => 'regular', 'lists' => 'regular', 'tables' => 'regular', 'forms' => 'contrast', 'tabs' => 'regular', 'accordion' => 'boxed' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 2, 'border-color' => 1, 'shadow' => 0, 'position' => -1, 'cta' => -1, 'behaviour' => 0, 'nav-case' => 0, 'menu-size' => 0, 'menu-weight' => 1, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 1, 'border-size' => 0, 'border-color' => 0, 'divider' => 1, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => -1, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 0, 'align' => -1, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 0, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 1, 'shadow' => 0, 'tone' => 0, 'title-ground' => 0, 'hover' => -1, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 0, 'corners' => -2, 'outline' => 0, 'hover' => -1, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 1, 'zebra' => 1, 'stripes' => 0 ],
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
		'tiers'		=> [ 'm' => [ 'size' => -2, 'header' => -2 ], 's' => [] ],
		'fonts'		=> [
			'title'		=> [ 'family' => 'system-sans', 'weight' => 'bold', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'system-sans', 'weight' => 'normal', 'size' => '' ],
			'text'		=> [ 'family' => 'system-sans', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'system-sans', 'weight' => 'bold', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
