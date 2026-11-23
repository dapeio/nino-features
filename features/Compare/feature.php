<?php
// features/Compare/feature.php - what the Features panel reads. The class is
// not declared here: features/Compare/ can only ever serve
// \Nino\Modules\Compare.
return [
	'key'					=> 'compare',
	'name'				=> [ 'en_US' => 'Before/After', 'de_DE' => 'Vorher/Nachher' ],
	'description'	=> [
		'en_US' => 'Before/After lets you lay two pictures of the same thing over one another, with a divider that visitors move. It works with the mouse, a finger and the arrow keys. Without JavaScript they stay two captioned pictures.',
		'de_DE' => 'Vorher/Nachher ermöglicht es, zwei Bilder derselben Sache übereinander zu legen, mit einem Trenner, den Besucher verschieben. Das geht mit Maus, Finger und Pfeiltasten. Ohne JavaScript bleiben es zwei beschriftete Bilder untereinander.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[compare before="~before" after="~after"]' => [
				'en_US' => 'Lays the two picture slots of the page over one another and gives them the divider; each is a slot of the project, never a file name, and without both nothing is drawn.',
				'de_DE' => 'Legt die zwei Bildplätze der Seite übereinander und gibt ihnen den Trenner; jedes ist ein Bildplatz des Projekts, nie ein Dateiname, und ohne beide wird nichts gezeichnet.',
			],
			'[compare ... alt="~alt" beforeLabel="Rohbau" afterLabel="Fertig" start="20" ratio="16-9"]' => [
				'en_US' => 'Describes the pair, names the sides, sets the divider (start, 0 to 100) and the shape (16-9, 4-3, 1-1, 3-2); a description left out is the alternative text stored with the first picture.',
				'de_DE' => 'Beschreibt das Paar, benennt die Seiten, setzt den Trenner (start, 0 bis 100) und die Form (16-9, 4-3, 1-1, 3-2); eine fehlende Beschreibung ist der zum ersten Bild gespeicherte Alternativtext.',
			],
		],
		'markup' => [
			'class="compare"' => [
				'en_US' => 'This is what the component writes; without JavaScript it is two captioned pictures one under the other, and the script adds the class compare-is-ready when it takes the pair over.',
				'de_DE' => 'Das schreibt die Komponente; ohne JavaScript sind es zwei beschriftete Bilder untereinander, und das Skript setzt die Klasse compare-is-ready, wenn es das Paar übernimmt.',
			],
			'<input type="range" class="compare-range">' => [
				'en_US' => 'The divider: a range control, so the mouse, a finger and the arrow keys move it, and a screen reader announces it with its value.',
				'de_DE' => 'Der Trenner: ein Schieberegler, den Maus, Finger und Pfeiltasten bewegen und den ein Screenreader mit seinem Wert ansagt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group Before/After' => [
				'en_US' => 'The Themes panel offers the pair as the component compare with a knob for the corners of the picture.',
				'de_DE' => 'Das Panel Themes bietet das Paar als Komponente compare an, mit einem Regler für die Ecken des Bildes.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the three words of the pair into the Text panel.',
				'de_DE' => 'Bringt die drei Wörter des Paars ins Panel Texte.',
			],
			'blocks/compare-before-after/' => [
				'en_US' => 'A block: a headline and the pair with two picture slots.',
				'de_DE' => 'Ein Block: eine Überschrift und das Paar mit zwei Bildplätzen.',
			],
		],
	],
	// It puts a control on the page that changes how what is already there is
	// looked at - the Features panel files that with the sliders and the
	// lightboxes
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'basis' ],
	// Nothing under data/: the two pictures are the project's own picture slots, and
	// where the divider stands is the visitor's, for as long as they look
	'data'				=> [],
	// The pair's script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Compare/assets/compare.js' ],
	/*	No settings. Everything a comparison varies - which two pictures,
		what the sides are called, where the divider starts, the shape of the
		box - belongs to the one place it is written, not to the site: a
		before/after of a facade and one of a photo retouch are not the same
		decision	*/
	'settings'		=> [],
];
