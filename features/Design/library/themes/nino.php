<?php
// features/Design/library/themes/nino.php - the theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php. It names no colours: the brand colour
// of a project is the project's, and loading the theme leaves it as it is. It names no feature either, and that is a choice
// like the rest - `features` is empty, so that loading the theme puts every feature back to the look it has of its own
return [
	'format'			=> 1,
	'name'				=> 'Nino',
	'description'		=> 'The framework as it is: every template the default one, every knob in the middle but the navigation of the header on the right and its button hidden, no css of its own.',
	'description-de'	=> 'Das Framework, wie es ist: jede Vorlage die Standardvorlage, jeder Regler in der Mitte bis auf die Navigation des Headers rechts und seinen Button unsichtbar, kein eigenes CSS.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'whitespace'	=> 0,
		'radius'		=> 0,
		'radius-alt'	=> 1,
		'radii'		=> [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'card' => 'standard', 'image' => 'standard' ],
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'regular', 'footer' => 'one-legal-row', 'section' => 'regular', 'card' => 'regular', 'buttons' => 'regular', 'lists' => 'regular', 'tables' => 'regular', 'forms' => 'regular' ],
		'knobs'		=> [
			'header' 		=> [ 'nav' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'shadow' => 0, 'position' => 1, 'cta' => -1, 'behaviour' => 0 ],
			'footer' 		=> [ 'scale' => 0, 'height' => 0, 'space' => 0, 'logo' => 0, 'ground' => 0, 'border-size' => 0, 'border-color' => 0, 'divider' => 0, 'position' => 0 ],
			'section' 		=> [ 'quiet' => 0, 'loud' => 0, 'title' => 0, 'subtitle' => 0, 'space' => 0, 'rule' => 0, 'dim' => 0 ],
			'card' 			=> [ 'title' => 0, 'subtitle' => 0, 'space' => 0, 'border' => 0, 'shadow' => 0, 'tone' => 0, 'hover' => 0 ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'space' => 0, 'corners' => 0, 'hover' => 0 ],
			'lists' 		=> [ 'scale' => 0, 'space' => 0, 'marker' => 0 ],
			'tables' 		=> [ 'scale' => 0, 'space' => 0, 'border' => 0, 'zebra' => 0, 'stripes' => 0 ],
			'forms' 		=> [ 'scale' => 0, 'space' => 0, 'corners' => 0, 'line' => 0, 'labels' => 0, 'edge' => '', 'label' => '' ],
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
