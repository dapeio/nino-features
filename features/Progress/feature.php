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
		'shortcodes' => [],
		'markup' => [
			'class="nino-progress"' => [
				'en_US' => 'An empty element, usually first thing in the body: it becomes the bar.',
				'de_DE' => 'Ein leeres Element, meist als Erstes im Body: Es wird zum Balken.',
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
	// It changes how what is already on the page reads and brings nothing of
	// its own to write - the Features panel files that with the sliders and
	// the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	'data'				=> [],
	/*	And no settings, for the reason Typewriter has none: where the bar sits
		and what it measures belong to the one template it is written into, and
		a static asset could not read a site-wide setting anyway	*/
	'settings'		=> [],
];
