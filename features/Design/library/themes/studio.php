<?php
// features/Design/library/themes/studio.php - a theme the feature ships: loaded and duplicated in the panel, never written or deleted.
// The shape of the file is described in features/Design/Themes/Themes.php. It is a construction of the library - a floating header and a centred footer over open sections and cards, with lines where other looks have boxes - with the
// position of every knob written out. It names no colours, no feature and no css of its own: the brand colour of a project stays the
// project's, and loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Studio',
	'description'		=> 'A light, rounded look for studios and portfolios: a floating header, open sections and cards without frames, ruled lists and lines instead of boxes, and generous whitespace.',
	'description-de'	=> 'Ein leichter, abgerundeter Auftritt für Studios und Portfolios: schwebender Header, offene Sektionen und Karten ohne Rahmen, Linienlisten und Linien statt Kästen, dazu viel Weißraum.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 1,
		'radius'		=> 1,
		'radius-alt'	=> 2,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => '' ],
		'parts'		=> [ 'header' => 'floating', 'footer' => 'centred-stack', 'section' => 'open', 'card' => 'open', 'buttons' => 'regular', 'lists' => 'ruled', 'tables' => 'line', 'forms' => 'line' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'shadow' => 1, 'position' => 1, 'cta' => 1, 'behaviour' => 0, 'nav-case' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'divider' => 0, 'position' => 0, 'nav-case' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 0, 'subtitle' => 0, 'title-tracking' => -1, 'space' => 0, 'rule' => 0, 'rule-length' => 0, 'title-shadow' => 0, 'dim' => 0, 'title-ground' => 0, 'rule-color' => 0, 'align' => 0, 'measure' => 0, 'title-case' => 0, 'subtitle-case' => 0 ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'title-tracking' => -1, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'title-ground' => 0, 'hover' => 0, 'align' => 0, 'title-case' => 0, 'subtitle-case' => 0 ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'tracking' => 0, 'space' => 1, 'corners' => 2, 'outline' => 0, 'hover' => 0, 'case' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '', 'label-case' => 0 ],
		],
		'styles'	=> [
			'header' => [],
			'footer' => [],
			'section' => [],
			'card' => [],
			'buttons' => [],
			'lists' => [],
			'tables' => [],
			'forms' => [],
		],
		'features'	=> [],
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
