<?php
// features/Progress/feature.php - what the Features panel reads. The class is
// not declared here: features/Progress/ can only ever serve
// \Nino\Modules\Progress.
return [
	'key'					=> 'progress',
	'name'				=> [ 'en_US' => 'Reading Progress', 'de_DE' => 'Lesefortschritt' ],
	'description'	=> [
		'en_US' => 'Reading Progress shows a thin bar across the top of the window that tells readers how far through a long text they are. It measures the whole page, or the one element that holds the article. Where JavaScript does not run, the bar is not drawn.',
		'de_DE' => 'Der Lesefortschritt zeigt einen schmalen Balken am oberen Rand des Fensters, der zeigt, wie weit Leser in einem langen Text sind. Er misst die ganze Seite oder das eine Element, das den Artikel enthält. Wo JavaScript nicht läuft, wird der Balken nicht gezeichnet.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[progress]' => [
				'en_US' => 'The bar for the element #article; an empty of measures the page.',
				'de_DE' => 'Der Balken für das Element #article; ein leeres of misst die Seite.',
			],
			'[progress of="#text" label="Reading progress"]' => [
				'en_US' => 'Measures the element a CSS selector names and gives the bar its name for screen readers, which an empty label leaves out.',
				'de_DE' => 'Misst das Element, das ein CSS-Selektor nennt, und gibt dem Balken seinen Namen für Screenreader, den ein leeres label weglässt.',
			],
		],
		'markup' => [
			'class="progress"' => [
				'en_US' => 'An empty element that becomes the bar; the script moves it into the body, so it may stand anywhere in the page.',
				'de_DE' => 'Ein leeres Element, das zum Balken wird; das Skript verschiebt es in den Body, es darf also überall auf der Seite stehen.',
			],
			'--progress-value' => [
				'en_US' => 'The share that is filled, which the script writes on the bar as it is scrolled.',
				'de_DE' => 'Der Anteil, der gefüllt ist, den das Skript beim Scrollen an den Balken schreibt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group Reading progress' => [
				'en_US' => 'The Themes panel offers the bar as the component progress with a knob for its height.',
				'de_DE' => 'Das Panel Themes bietet den Balken als Komponente progress an, mit einem Regler für seine Höhe.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	// It changes how what is already on the page reads and brings nothing but
	// an empty element to write - the Features panel files that with the
	// sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	'data'				=> [],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Progress/assets/progress.js' ],
	/*	And no settings: where the bar sits and what it measures belong to the
		one place it is written, and a static asset could not read a site-wide
		setting anyway	*/
	'settings'		=> [],
];
