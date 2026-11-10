<?php
// features/Progress/feature.php - what the Features panel reads. The class is
// not declared here: features/Progress/ can only ever serve
// \Nino\Modules\Progress.
return [
	'key'					=> 'progress',
	'name'				=> [ 'en_US' => 'Reading Progress', 'de_DE' => 'Lesefortschritt' ],
	'description'	=> [
		'en_US' => 'Reading Progress lets you show a thin bar that tells readers how far through a long text they are. It measures the whole page, or the one element that holds the article.',
		'de_DE' => 'Der Lesefortschritt ermöglicht einen schmalen Balken, der zeigt, wie weit Leser in einem langen Text sind. Er misst die ganze Seite oder das eine Element, das den Artikel enthält.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[progress of="#article" label="Lesefortschritt"]' => [
				'en_US' => 'Writes the bar as the Builder\'s component does; of is the CSS selector of the measured element (default #article, empty measures the page), label its name for screen readers.',
				'de_DE' => 'Schreibt den Balken, wie es die Komponente des Builders tut; of ist der CSS-Selektor des gemessenen Elements (Standard #article, leer misst die Seite), label sein Name für Screenreader.',
			],
			'[progress ... class="my-class"]' => [
				'en_US' => 'Adds a class of your own to the bar.',
				'de_DE' => 'Hängt eine eigene Klasse an den Balken.',
			],
		],
		'markup' => [
			'class="nino-progress"' => [
				'en_US' => 'An empty element that becomes the bar; the script moves it to the body, so it may stand anywhere in the page.',
				'de_DE' => 'Ein leeres Element, das zum Balken wird; das Skript verschiebt es in den Body, es darf also überall auf der Seite stehen.',
			],
			'data-progress-of="#article"' => [
				'en_US' => 'Measures that element rather than the whole page.',
				'de_DE' => 'Misst dieses Element statt der ganzen Seite.',
			],
			'data-progress-label="Lesefortschritt"' => [
				'en_US' => 'Gives the bar its name for screen readers, else it is hidden from them.',
				'de_DE' => 'Gibt dem Balken seinen Namen für Screenreader, sonst bleibt er ihnen verborgen.',
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
	'version'			=> '1.1.2',
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
			'draws'			=> 'div .nino-progress',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'm12 14 4-4' ] ],
				[ 'path', [ 'd' => 'M3.34 19a10 10 0 1 1 17.32 0' ] ],
			],
		],
	],
];
