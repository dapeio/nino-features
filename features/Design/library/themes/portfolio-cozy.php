<?php
// features/Design/library/themes/portfolio-cozy.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php. It names no colours: the brand colour
// of a project is the project's, and loading the theme leaves it as it is
return [
	'format'			=> 1,
	'name'				=> 'Portfolio, cozy',
	'description'		=> 'A restrained portfolio look with soft shapes: a floating header, rounder corners, larger section and card titles and a little more whitespace.',
	'description-de'	=> 'Ein zurückhaltendes Portfolio-Design mit weichen Formen.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 1,
		'radius'		=> 1,
		'radius-alt'	=> 2,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'card' => 'alternative', 'image' => 'alternative' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'center-floating', 'footer' => 'logo-contact-light', 'section' => 'regular', 'card' => 'regular', 'buttons' => 'regular', 'lists' => 'regular', 'tables' => 'regular' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'ground' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'ground' => 0 ],
			'section' 		=> [ 'hero-title' => 0, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'title' => 2, 'subtitle' => 0, 'space' => 0, 'rule' => 0, 'dim' => 0 ],
			'card' 			=> [ 'title' => 2, 'subtitle' => 0, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'hover' => 0 ],
			'buttons' 		=> [ 'size' => -1, 'spread' => 0, 'space' => 1, 'corners' => 1, 'hover' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
		],
		'fonts'		=> [
			'title'		=> [ 'family' => 'open-sans', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'subtitle'	=> [ 'family' => 'open-sans', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
		],
	],
	'css'				=> '',
	'presets'			=> [],
];
