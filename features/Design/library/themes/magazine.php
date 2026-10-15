<?php
// features/Design/library/themes/magazine.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php
return [
	'format'			=> 1,
	'name'				=> 'Magazine',
	'name-de'			=> 'Magazin',
	'description'		=> 'Lifted cards, keys and ruled fields; a drop cap opens a section, the subtitle runs in small capitals and the rule under a title takes the accent.',
	'description-de'	=> 'Schwebende Karten, Tasten und Felder im Rahmen; eine Initiale eröffnet die Sektion, der Untertitel läuft in Kapitälchen, und die Linie unter dem Titel nimmt den Akzent.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'v9', 'footer' => 'v2', 'content' => 'v3', 'buttons' => 'v4', 'components' => 'v2' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 0, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 0, 'section-subtitle' => 0, 'section-space' => 0, 'section-line' => 0, 'card-title' => 0, 'card-subtitle' => 0, 'card-space' => 1, 'corners' => 0, 'border' => 0, 'shadow' => 1 ],
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
		'colours'	=> [ 'primary' => '#4faae8', 'secondary' => '', 'harmony' => 1, 'temperature' => 4, 'saturation' => 3, 'contrast' => 3, 'depth' => 3, 'mode' => 'auto' ],
	],
	'css'				=> <<<'CSS'
	/*	Magazine - the signatures of this theme, in roles only:
	 *	initial     the first letter of a section text, floated, three lines tall, in the accent
	 *	small caps  the subtitle of a section, spaced a little
	 *	rule        the line under a section title, in the accent	*/

	.nino-section-text:not(.nino-section-text ~ .nino-section-text) {
		display: flow-root;
	}
	.nino-section-text:not(.nino-section-text ~ .nino-section-text)::first-letter {
		float: left;
		padding-right: .08em;
		font-size: 5.2em;
		font-weight: var(--font-title-weight);
		line-height: .87;
		color: var(--color-accent);
	}

	.nino-section-subtitle {
		font-variant-caps: small-caps;
		letter-spacing: .08em;
	}

	.nino-section-title {
		border-bottom-color: var(--color-accent);
	}
	CSS,
	'presets'			=> [],
];
