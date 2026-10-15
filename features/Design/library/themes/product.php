<?php
// features/Design/library/themes/product.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php
return [
	'format'			=> 1,
	'name'				=> 'Product',
	'name-de'			=> 'Produkt',
	'description'		=> 'Panel bands, pill buttons and filled fields; badges as pills and tabular figures - the system\'s own sans in every role.',
	'description-de'	=> 'Panel-Bänder, Pillen-Buttons und gefüllte Felder; Badges als Pillen und tabellarische Ziffern – die Sans des Systems in jeder Rolle.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'v8', 'footer' => 'v4', 'content' => 'v5', 'buttons' => 'v2', 'components' => 'v4' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 0, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 0, 'section-subtitle' => 0, 'section-space' => 0, 'section-line' => 0, 'card-title' => 0, 'card-subtitle' => 0, 'card-space' => -1, 'corners' => 1, 'border' => 0, 'shadow' => 0 ],
			'buttons' 		=> [ 'size' => 0, 'spread' => 0, 'space' => 0, 'corners' => 0 ],
			'components' 	=> [ 'scale' => 0, 'space' => -1, 'corners' => 0, 'border' => 0, 'zebra' => 0, 'labels' => 0 ],
		],
		'fonts'		=> [
			'title'		=> [ 'family' => 'system-sans', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'subtitle'	=> [ 'family' => 'system-sans', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'text'		=> [ 'family' => 'system-sans', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
			'button'	=> [ 'family' => 'system-sans', 'weight' => 'bold', 'uppercase' => false, 'size' => '' ],
			'code'		=> [ 'family' => '', 'weight' => 'normal', 'uppercase' => false, 'size' => '' ],
		],
		'colours'	=> [ 'primary' => '#4faae8', 'secondary' => '', 'harmony' => 2, 'temperature' => 2, 'saturation' => 3, 'contrast' => 3, 'depth' => 3, 'mode' => 'auto' ],
	],
	'css'				=> <<<'CSS'
	/*	Product - the signatures of this theme, in roles only:
	 *	pills    badges as pills
	 *	figures  tabular figures on tables and on the amounts of plans	*/

	.nino-badge {
		padding-right: .9em;
		padding-left: .9em;
	}
	/*	A badge that is no pill takes the corners of the components template, with the selector it says it by - the plain one would lose to it */
	.nino-badge,
	.nino-badge:not(.nino-badge--pill) {
		border-radius: 999rem;
	}

	.nino-table,
	.nino-pricing-price {
		font-variant-numeric: tabular-nums;
	}
	CSS,
	'presets'			=> [],
];
