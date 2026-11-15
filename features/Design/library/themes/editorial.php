<?php
// features/Design/library/themes/editorial.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a stacked header and a statement footer over open sections, panel cards and quiet buttons, in serif type - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Editorial',
	'description'		=> 'A reading-first look for magazines, blogs and journals: a masthead with the name over a row of small capitals, serif type throughout with large titles in the colour of the text, open left-aligned sections on a narrow column, cards as rows, quiet buttons, ruled lists and tables, fields as lines and a statement footer, set larger on a contained page.',
	'description-de'	=> 'Ein Auftritt, der das Lesen voranstellt, für Magazine, Blogs und Journale: Zeitungskopf mit dem Namen über einer Zeile kleiner Versalien, durchgehend Serifenschrift mit großen Titeln in Textfarbe, offene linksbündige Abschnitte auf schmaler Spalte, Karten als Zeilen, ruhige Buttons, Linienlisten und -tabellen, Felder als Linien und ein Statement-Footer, größer gesetzt auf begrenzter Seite.',
	'setup'				=> [
		'size'		=> 1,
		'width'		=> -1,
		'whitespace'	=> 0,
		'radius'		=> 0,
		'radius-alt'	=> 1,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'tile' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => 'bright', 'hover-strength' => 'soft' ],
		'parts'		=> [ 'header' => 'stacked', 'footer' => 'statement', 'section' => 'open', 'tiles' => 'regular', 'card' => 'panel', 'buttons' => 'quiet', 'lists' => 'ruled', 'tables' => 'ruled', 'forms' => 'line', 'tabs' => 'regular', 'accordion' => 'regular' ],
		'navs'		=> [ 'header' => '', 'footer' => '' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => -1, 'height' => 1, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 1, 'border-color' => -1, 'shadow' => 0, 'position' => 0, 'cta' => -1, 'behaviour' => 0, 'nav-case' => 1, 'menu-size' => 0, 'menu-weight' => 0, 'menu-tracking' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 1, 'title' => 2, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 0, 'align' => -1, 'measure' => -1, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'tiles' 		=> [ 'title' => 0, 'subtitle' => 0, 'text' => 0, 'title-tracking' => 0, 'space' => 0, 'gap' => 0, 'place' => 0, 'dim' => 0, 'hover' => 0, 'title-ink' => '', 'subtitle-ink' => '' ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => 0, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'title-ground' => 0, 'hover' => 0, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0, 'title-ink' => 'ink', 'subtitle-ink' => '' ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 0, 'corners' => 0, 'outline' => 0, 'hover' => 0, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
			'tabs' 			=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'active' => '' ],
			'accordion' 	=> [ 'scale' => 0, 'space' => 0, 'line' => 0, 'marker' => 0, 'marker-ink' => '', 'weight' => 1 ],
		],
		'styles'	=> [
			'header' => [ 'nav-hover' => 'fade' ],
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
		'tiers'		=> [ 'm' => [ 'size' => -1, 'title' => -1, 'header' => -2 ], 's' => [ 'size' => 0, 'title' => -2 ] ],
		'fonts'		=> [
			'title'		=> [ 'family' => 'system-serif', 'weight' => 'bold', 'size' => '' ],
			'subtitle'	=> [ 'family' => 'system-serif', 'weight' => 'normal', 'size' => '' ],
			'text'		=> [ 'family' => 'system-serif', 'weight' => 'normal', 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'bold', 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
