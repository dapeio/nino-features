<?php
// features/Design/library/themes/office.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php
return [
	'format'			=> 1,
	'name'				=> 'Office',
	'name-de'			=> 'Verwaltung',
	'description'		=> 'Nino\'s own look, denser: smaller buttons, tighter fields and tables, ruled fields, the root size a step down.',
	'description-de'	=> 'Ninos eigenes Aussehen, dichter: kleinere Buttons, engere Felder und Tabellen, Felder im Rahmen, die Grundgröße eine Stufe kleiner.',
	'setup'				=> [
		'size'		=> -1,
		'width'		=> 0,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'v1', 'footer' => 'v9', 'content' => 'v1', 'buttons' => 'v1', 'components' => 'v2' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 0, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 0, 'section-subtitle' => 0, 'section-space' => 0, 'section-line' => 0, 'card-title' => 0, 'card-subtitle' => 0, 'card-space' => 0, 'corners' => 0, 'border' => 0, 'shadow' => 0 ],
			'buttons' 		=> [ 'size' => -1, 'spread' => 0, 'space' => -1, 'corners' => 0 ],
			'components' 	=> [ 'scale' => -1, 'space' => -2, 'corners' => 0, 'border' => 0, 'zebra' => 0, 'labels' => 0 ],
		],
		'fonts'		=> [
			'title'		=> [ 'family' => 'open-sans', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'subtitle'	=> [ 'family' => 'open-sans', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'text'		=> [ 'family' => 'open-sans', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'button'	=> [ 'family' => 'open-sans', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
		],
		'colours'	=> [ 'primary' => '#4faae8', 'secondary' => '', 'harmony' => 1, 'temperature' => 3, 'saturation' => 3, 'contrast' => 3, 'depth' => 3, 'mode' => 'auto' ],
	],
	'css'				=> '',
	'presets'			=> [],
];
