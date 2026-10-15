<?php
// features/Design/library/themes/statement.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php
return [
	'format'			=> 1,
	'name'				=> 'Statement',
	'name-de'			=> 'Statement',
	'description'		=> 'Open cards, quiet buttons and line fields; a big opening, large numbers, titles underlined in the accent and room around everything.',
	'description-de'	=> 'Offene Karten, leise Buttons und Linien-Felder; ein großer Auftakt, große Zahlen, Titel im Akzent unterstrichen und Luft um alles.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'v10', 'footer' => 'v11', 'content' => 'v4', 'buttons' => 'v5', 'components' => 'v3' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 2, 'hero-text' => -1, 'quiet' => 0, 'loud' => 1, 'section-title' => 0, 'section-subtitle' => 0, 'section-space' => 1, 'section-line' => 0, 'card-title' => 0, 'card-subtitle' => 0, 'card-space' => 0, 'corners' => 0, 'border' => 0, 'shadow' => 0 ],
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
		'colours'	=> [ 'primary' => '#4faae8', 'secondary' => '', 'harmony' => 1, 'temperature' => 1, 'saturation' => 3, 'contrast' => 3, 'depth' => 3, 'mode' => 'auto' ],
	],
	'css'				=> <<<'CSS'
	/*	Statement - the signatures of this theme, in roles only:
	 *	figures    the amount of a plan, the number of a timeline step and the counters at 1.5 times, tabular
	 *	underline  every section title underlined in the accent	*/

	.nino-pricing-price:not(.nino-pricing-price--quiet, .nino-pricing-price--loud),
	.nino-timeline--counted > .nino-timeline-step::after,
	.nino-stat-counter {
		font-variant-numeric: tabular-nums;
	}
	.nino-pricing-price:not(.nino-pricing-price--quiet, .nino-pricing-price--loud) {
		font-size: 3rem;
	}
	.nino-pricing-item--featured .nino-pricing-price:not(.nino-pricing-price--quiet, .nino-pricing-price--loud) {
		font-size: 4.2rem;
	}
	.nino-timeline--counted > .nino-timeline-step::after {
		font-size: 1.5rem;
	}
	.nino-stat-counter {
		font-size: 3.75rem;
	}

	.nino-section-title {
		text-decoration: underline;
		text-decoration-color: var(--color-accent);
		text-decoration-thickness: .12em;
		text-underline-offset: .2em;
	}
	CSS,
	'presets'			=> [],
];
