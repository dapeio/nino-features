<?php
// features/Design/library/themes/corporate-cozy.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php. It names no colours: the brand colour
// of a project is the project's, and loading the theme leaves it as it is
return [
	'format'			=> 1,
	'name'				=> 'Corporate, cozy',
	'description'		=> 'A corporate look with soft shapes and a friendly appearance: rounder corners, smaller buttons with more room around the label, larger section and card titles and twice the whitespace.',
	'description-de'	=> 'Ein modernes Corporate Design mit weichen Formen und freundlicher Erscheinung.',
	'setup'				=> [
		'size'		=> 1,
		'width'		=> 0,
		'whitespace'	=> 2,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'center-big', 'footer' => 'one-legal-row', 'content' => 'regular', 'buttons' => 'regular', 'components' => 'regular' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 0, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 2, 'section-subtitle' => 0, 'section-space' => 0, 'section-line' => 0, 'card-title' => 2, 'card-subtitle' => 0, 'card-space' => 0, 'corners' => 2, 'border' => 0, 'shadow' => 0 ],
			'buttons' 		=> [ 'size' => -1, 'spread' => 0, 'space' => 1, 'corners' => 2 ],
			'components' 	=> [ 'scale' => 0, 'space' => 0, 'corners' => 2, 'border' => 0, 'zebra' => 0, 'labels' => 0 ],
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
