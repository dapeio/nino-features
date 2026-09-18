<?php
// features/Compare/feature.php - what the Features panel reads. The class is
// not declared here: features/Compare/ can only ever serve
// \Nino\Modules\Compare.
return [
	'key'					=> 'compare',
	'name'				=> [ 'en_US' => 'Before/After', 'de_DE' => 'Vorher/Nachher' ],
	'description'	=> [
		'en_US' => 'Two pictures of the same thing under one divider the visitor moves - with the mouse, a finger or the arrow keys, because the divider is a real range control rather than a script pretending to be one.',
		'de_DE' => 'Zwei Bilder derselben Sache unter einem Trenner, den der Besucher bewegt - mit Maus, Finger oder Pfeiltasten, denn der Trenner ist ein echtes Schieberegler-Element und kein Skript, das so tut.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[compare before="a.jpg" after="b.jpg"]' => [
				'en_US' => 'Two images from the project\'s own pictures, one over the other.',
				'de_DE' => 'Zwei Bilder aus den eigenen Bildern des Projekts, eines über dem anderen.',
			],
			'[compare ... before-label="Rohbau" after-label="Fertig"]' => [
				'en_US' => 'What the two sides are called. Without them the Text panel\'s own words are used.',
				'de_DE' => 'Wie die beiden Seiten heißen. Ohne sie werden die Wörter aus dem Panel Texte verwendet.',
			],
			'[compare ... alt="Die Fassade vor und nach der Sanierung"]' => [
				'en_US' => 'What the pair shows, for a visitor who cannot see it. Say it: two pictures with no description are two decorations.',
				'de_DE' => 'Was das Paar zeigt, für Besucher, die es nicht sehen. Angeben: Zwei Bilder ohne Beschreibung sind zwei Verzierungen.',
			],
			'[compare ... start="20"]' => [
				'en_US' => 'Where the divider stands when the page opens, 0 to 100. Default 50.',
				'de_DE' => 'Wo der Trenner steht, wenn die Seite aufgeht, 0 bis 100. Vorgabe 50.',
			],
			'[compare ... ratio="4-3"]' => [
				'en_US' => 'The shape of the box: 16-9, 4-3 (default), 1-1 or 3-2.',
				'de_DE' => 'Die Form des Kastens: 16-9, 4-3 (Vorgabe), 1-1 oder 3-2.',
			],
		],
		'markup' => [
			'class="nino-compare"' => [
				'en_US' => 'What the shortcode writes. Without JavaScript the same markup is two captioned pictures under one another - see README.md.',
				'de_DE' => 'Was der Shortcode schreibt. Ohne JavaScript sind es zwei beschriftete Bilder untereinander - siehe README.md.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The three words the pair carries, into the Text panel.',
				'de_DE' => 'Die drei Wörter des Paars, ins Panel Texte.',
			],
		],
	],
	// It puts a control on the page that changes how what is already there is
	// looked at - the Features panel files that with the sliders and the
	// lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: the two pictures are the project's own images, and
	// where the divider stands is the visitor's, for as long as they look
	'data'				=> [],
	/*	And no settings. Everything a comparison varies - which two pictures,
		what the sides are called, where the divider starts, the shape of the
		box - belongs to the one place it is written, not to the site: a
		before/after of a facade and one of a photo retouch are not the same
		decision	*/
	'settings'		=> [],
];
