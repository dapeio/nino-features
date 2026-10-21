<?php
// features/Design/library/themes/corporate-loud.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php. It names no colours: the brand colour
// of a project is the project's, and loading the theme leaves it as it is
return [
	'format'			=> 1,
	'name'				=> 'Corporate, loud',
	'description'		=> 'A corporate look that is large and airy: a root size a step up, larger titles, taller sections, twice the whitespace and sharp corners.',
	'description-de'	=> 'Ein modernes Corporate Design mit großen Abständen und Schriften sowie scharfen Ecken.',
	'setup'				=> [
		'size'		=> 1,
		'width'		=> 0,
		'whitespace'	=> 2,
		'radius'		=> -2,
		'radius-alt'	=> 1,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'classic-big', 'footer' => 'one-legal-row', 'content' => 'regular', 'buttons' => 'regular', 'components' => 'regular' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 1, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 1, 'section-subtitle' => 0, 'section-space' => 2, 'section-line' => 0, 'card-title' => 1, 'card-subtitle' => 0, 'card-space' => 0, 'border' => 0, 'shadow' => 0 ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'space' => 0, 'corners' => 0 ],
			'components' 	=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'border' => 0, 'zebra' => 0, 'labels' => 0 ],
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
