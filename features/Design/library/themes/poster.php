<?php
// features/Design/library/themes/poster.php - a theme the feature ships: loaded and duplicated in the panel, never written
// or deleted. The shape of the file is described in features/Design/Themes/Themes.php
return [
	'format'			=> 1,
	'name'				=> 'Poster',
	'name-de'			=> 'Plakat',
	'description'		=> 'Boxed cards, square buttons and contrast components; an eyebrow over every title, its first letter in the accent, and the sections counted.',
	'description-de'	=> 'Kastenkarten, eckige Buttons und Kontrast-Komponenten; ein Eyebrow über jedem Titel, dessen erster Buchstabe im Akzent, und die Sektionen gezählt.',
	'setup'				=> [
		'size'		=> 0,
		'width'		=> 0,
		'motion'	=> [ 'effect' => '', 'strength' => '', 'speed' => '' ],
		'parts'		=> [ 'header' => 'v5', 'footer' => 'v10', 'content' => 'v2', 'buttons' => 'v3', 'components' => 'v5' ],
		'knobs'		=> [
			'content' 		=> [ 'hero-title' => 1, 'hero-text' => 0, 'quiet' => 0, 'loud' => 0, 'section-title' => 0, 'section-subtitle' => 0, 'section-space' => 1, 'section-line' => 1, 'card-title' => 0, 'card-subtitle' => 0, 'card-space' => 0, 'corners' => 0, 'border' => 0, 'shadow' => 0 ],
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
		'colours'	=> [ 'primary' => '#4faae8', 'secondary' => '', 'harmony' => 4, 'temperature' => 3, 'saturation' => 3, 'contrast' => 3, 'depth' => 3, 'mode' => 'auto' ],
	],
	'css'				=> <<<'CSS'
	/*	Poster - the signatures of this theme, in roles only:
	 *	eyebrow  the subtitle that follows the title of a section, lifted above it: capitals on a block of the accent, at the start or in the middle as the text is
	 *	initial  the first letter of a section title in the accent, the rest of it in the ink
	 *	counted  the section titles numbered 01, 02, ... in the accent, above the title	*/
	:has(> .nino-section-title:first-child + .nino-section-subtitle:last-child) { display: grid; }
	.nino-section-title:first-child + .nino-section-subtitle:last-child {
		order: -1;
		justify-self: var(--poster-align, center);
		margin-bottom: var(--space-1);
		padding: .2em .8em;
		background-color: var(--color-accent);
		color: var(--color-ground);
		text-transform: uppercase;
		letter-spacing: .14em;
		opacity: 1;
	}
	.nino-text-left { --poster-align: start; }
	.nino-text-center { --poster-align: center; }

	.nino-section-title { color: var(--color-ink); }
	.nino-section-title::first-letter { color: var(--color-accent); }

	main { counter-reset: poster-section; }
	.nino-grid-row > * > .nino-section-title {
		position: relative;
		padding-top: calc( var(--font-subtitle-size) * var(--section-subtitle-scale) * 1.8 );
		counter-increment: poster-section;
	}
	.nino-grid-row > * > .nino-section-title::before {
		content: counter(poster-section, decimal-leading-zero);
		position: absolute;
		inset: 0 0 auto;
		font-size: calc( var(--font-subtitle-size) * var(--section-subtitle-scale) );
		font-weight: var(--font-subtitle-weight);
		line-height: 1.3;
		letter-spacing: .1em;
		color: var(--color-accent);
	}
	CSS,
	'presets'			=> [],
];
