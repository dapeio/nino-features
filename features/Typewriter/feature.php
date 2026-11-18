<?php
// features/Typewriter/feature.php - what the Features panel reads. The class is
// not declared here: features/Typewriter/ can only ever serve
// \Nino\Modules\Typewriter.
return [
	'key'					=> 'typewriter',
	'name'				=> [ 'en_US' => 'Typewriter', 'de_DE' => 'Schreibmaschine' ],
	'description'	=> [
		'en_US' => 'Typewriter lets you type the lines of a text one after the other, with a cursor at the writing head. A headline writes itself that way, once or in a loop. It stands still, with every line readable, for visitors who asked for less motion or have no JavaScript, and a pause button stops it on request of any visitor.',
		'de_DE' => 'Die Schreibmaschine ermöglicht es, die Zeilen eines Textes nacheinander zu tippen, mit Cursor am Schreibkopf. Eine Überschrift schreibt sich so selbst, einmal oder in der Schleife. Für Besucher, die weniger Bewegung wollen oder kein JavaScript haben, steht sie still da, jede Zeile lesbar, und ein Pause-Knopf hält sie auf Wunsch jedes Besuchers an.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[typewriter ~lines]' => [
				'en_US' => 'Types the lines of a text one after the other, once, and the last line stays; the text is a key, a field or text="…" with &#10; for a line break.',
				'de_DE' => 'Tippt die Zeilen eines Textes nacheinander, einmal, und die letzte Zeile bleibt stehen; der Text ist ein Schlüssel, ein Feld oder text="…" mit &#10; für einen Zeilenumbruch.',
			],
			'[typewriter ~lines tag="h2"]' => [
				'en_US' => 'Sets the element that holds the lines: p by default, or h1 to h4 or span, and nothing else.',
				'de_DE' => 'Setzt das Element, das die Zeilen hält: vorgegeben p, oder h1 bis h4 oder span, und nichts sonst.',
			],
			'[typewriter ~lines speed="45" hold="1600"]' => [
				'en_US' => 'Sets the milliseconds per character, 45 by default, and the milliseconds a finished line stands, 1600 by default.',
				'de_DE' => 'Setzt die Millisekunden je Zeichen, vorgegeben 45, und die Millisekunden, die eine fertige Zeile steht, vorgegeben 1600.',
			],
			'[typewriter ~lines loop]' => [
				'en_US' => 'Starts over after the last line instead of staying on it.',
				'de_DE' => 'Beginnt nach der letzten Zeile von vorn, statt auf ihr stehen zu bleiben.',
			],
		],
		'markup' => [
			'class="typewriter-line"' => [
				'en_US' => 'The element of one line inside the typewriter, which the script writes as text and never as markup.',
				'de_DE' => 'Das Element einer Zeile in der Schreibmaschine, das das Skript als Text schreibt und nie als Markup.',
			],
			'data-typewriter-label="[[/feature/typewriter/pause/label]]"' => [
				'en_US' => 'The label of the pause button that the script draws after the typewriter and takes away when the last line of one that does not loop is typed.',
				'de_DE' => 'Die Beschriftung des Pause-Knopfs, den das Skript hinter der Schreibmaschine zeichnet und wegnimmt, wenn die letzte Zeile einer Schreibmaschine ohne Schleife getippt ist.',
			],
			'--typewriter-cursor-width' => [
				'en_US' => 'The width of the cursor, 2px by default, which a project sets on the typewriter or on :root.',
				'de_DE' => 'Die Breite des Cursors, vorgegeben 2px, die ein Projekt an der Schreibmaschine oder auf :root setzt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group Typewriter' => [
				'en_US' => 'The Themes panel offers the typewriter as a component with a knob for the width of its cursor.',
				'de_DE' => 'Das Panel Themes bietet die Schreibmaschine als Komponente an, mit einem Regler für die Breite ihres Cursors.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the label of the pause button, [[/feature/typewriter/pause/label]], into the Text panel.',
				'de_DE' => 'Bringt die Beschriftung des Pause-Knopfs, [[/feature/typewriter/pause/label]], ins Panel Texte.',
			],
			'assets/typewriter.js' => [
				'en_US' => 'The script that types the lines; it reaches the browser in the site\'s script bundle, the look in its style bundle.',
				'de_DE' => 'Das Skript, das die Zeilen tippt; es kommt im Skript-Bündel der Seite zum Browser, das Aussehen im Stil-Bündel.',
			],
			'blocks/typewriter-headline/' => [
				'en_US' => 'A block: a headline that types three lines one after the other and starts over.',
				'de_DE' => 'Ein Block: eine Überschrift, die drei Zeilen nacheinander tippt und von vorn beginnt.',
			],
		],
	],
	// It changes how a text behaves and brings one text fill of its own, the
	// label of the pause button - the Features panel files that with the
	// sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// Nothing under data/: what is typed is the text of the call
	'data'				=> [],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Typewriter/assets/typewriter.js' ],
	/*	And no settings: every timing belongs to the typewriter being typed, not
		to the site - a headline and a line under a photo are not one speed	*/
	'settings'		=> [],
];
