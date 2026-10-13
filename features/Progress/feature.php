<?php
// features/Progress/feature.php - what the Features panel reads. The class is
// not declared here: features/Progress/ can only ever serve
// \Nino\Modules\Progress.
return [
	'key'					=> 'progress',
	'name'				=> [ 'en_US' => 'Reading Progress', 'de_DE' => 'Lesefortschritt' ],
	'description'	=> [
		'en_US' => 'A thin bar that says how far through a long text the reader is - over the whole page, or over the one element that holds the article.',
		'de_DE' => 'Ein schmaler Balken, der zeigt, wie weit der Leser in einem langen Text ist - über die ganze Seite oder über das eine Element, das den Artikel enthält.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[progress of="#article" label="Lesefortschritt"]' => [
				'en_US' => 'The bar\'s element as a shortcode - what the Builder\'s component writes. of= is the CSS selector of the element whose reading the bar measures: without it #article is taken, and an empty one measures the whole page. label= is what the bar is called for a screen reader; without it the bar is hidden from them.',
				'de_DE' => 'Das Element des Balkens als Shortcode - das schreibt die Komponente des Builders. of= ist der CSS-Selektor des Elements, dessen Lesen der Balken misst: Ohne die Angabe gilt #article, leer misst die ganze Seite. label= ist, wie der Balken für Screenreader heißt; ohne die Angabe wird er ihnen verborgen.',
			],
			'[progress ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to that of the bar. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu der des Balkens. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'class="nino-progress"' => [
				'en_US' => 'An empty element: it becomes the bar. The script moves it to the body, so it may stand anywhere in the page. The Builder places it as the component Reading progress, in a section, or the div is written by hand.',
				'de_DE' => 'Ein leeres Element: Es wird zum Balken. Das Skript verschiebt es in den Body, es darf also an beliebiger Stelle der Seite stehen. Der Builder setzt es als Komponente Lesefortschritt in eine Section, oder das div wird von Hand geschrieben.',
			],
			'data-progress-of="#article"' => [
				'en_US' => 'Measure that element rather than the whole page - so the footer is not part of the article.',
				'de_DE' => 'Misst dieses Element statt der ganzen Seite - damit der Fußbereich nicht zum Artikel zählt.',
			],
			'data-progress-label="Lesefortschritt"' => [
				'en_US' => 'What the bar is, for a reader who cannot see it. Without it the bar is hidden from them instead of announced as an unnamed number.',
				'de_DE' => 'Was der Balken ist, für Leser, die ihn nicht sehen. Ohne die Angabe wird er ihnen verborgen statt als unbenannte Zahl angesagt.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [],
	],
	// It changes how what is already on the page reads and brings nothing but
	// an empty element to write - the Features panel files that with the
	// sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.1.0',
	'nino'				=> '^1.8',
	'requires'		=> [],
	'data'				=> [],
	// Into the site's style and script bundle, behind the kernel's own files
	'assets'			=> [ '/features/Progress/assets/progress.css', '/features/Progress/assets/progress.js' ],
	/*	And no settings, for the reason Typewriter has none: where the bar sits
		and what it measures belong to the one place it is written, and a static
		asset could not read a site-wide setting anyway	*/
	'settings'		=> [],
	/*	The bar as the Builder places it: no first argument, the element is
		pointed with its two attributes, and a page has one bar - so it does not
		stand in a loop. An empty "of" is a value of its own, the whole page, and
		the default is the article	*/
	'components'	=> [
		'progress' => [
			'label'			=> [ 'en_US' => 'Reading progress', 'de_DE' => 'Lesefortschritt' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'of' => [
					'type'		=> 'string',
					'default'	=> '#article',
					'label'		=> [ 'en_US' => 'What is measured', 'de_DE' => 'Gemessen wird' ],
					'hint'		=> [
						'en_US' => 'A CSS selector of the element whose reading the bar measures, such as #article. Empty measures the whole page.',
						'de_DE' => 'Ein CSS-Selektor des Elements, dessen Lesen der Balken misst, etwa #article. Leer misst die ganze Seite.',
					],
				],
				'label' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Label', 'de_DE' => 'Beschriftung' ],
					'hint'		=> [
						'en_US' => 'What a screen reader calls the bar. Empty hides the bar from screen readers.',
						'de_DE' => 'Wie ein Screenreader den Balken nennt. Leer versteckt den Balken vor Screenreadern.',
					],
				],
			],
			'preview'		=> 'block',
		],
	],
];
