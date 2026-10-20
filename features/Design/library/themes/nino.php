<?php
// features/Design/library/themes/nino.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php. It names no colours: the brand colour
// of a project is the project's, and loading the theme leaves it as it is
return [
	'format'			=> 1,
	'name'				=> 'Nino',
	'description'		=> 'The framework as it is: every template the default one, every knob in the middle, no css of its own.',
	'description-de'	=> 'Das Framework, wie es ist: jede Vorlage die Standardvorlage, jeder Regler in der Mitte, kein eigenes CSS.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 0,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'classic-default', 'footer' => 'one-legal-row', 'content' => 'regular', 'buttons' => 'regular', 'components' => 'regular' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 0, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 0, 'section-subtitle' => 0, 'section-space' => 0, 'section-line' => 0, 'card-title' => 0, 'card-subtitle' => 0, 'card-space' => 0, 'corners' => 0, 'border' => 0, 'shadow' => 0 ],
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
