<?php
// features/Typewriter/feature.php - what the Features panel reads. The class
// is not declared here: features/Typewriter/ can only ever serve
// \Nino\Modules\Typewriter.
return [
	'key'					=> 'typewriter',
	'name'				=> [ 'en_US' => 'Typewriter', 'de_DE' => 'Schreibmaschine' ],
	'description'	=> [
		'en_US' => 'Types the lines of a container one after the other, with a cursor at the writing head - a headline that writes itself, once or in a loop, timed per element with data attributes.',
		'de_DE' => 'Tippt die Zeilen eines Containers nacheinander, mit Cursor am Schreibkopf - eine Überschrift, die sich selbst schreibt, einmal oder in der Schleife, je Element über data-Attribute getaktet.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'class="nino-typewriter"' => [
				'en_US' => 'On a container: its <p> lines are typed one after the other, once. The last line stays.',
				'de_DE' => 'An einem Container: seine <p>-Zeilen werden nacheinander getippt, einmal. Die letzte Zeile bleibt stehen.',
			],
			'data-typewriter-speed="60"' => [
				'en_US' => 'Milliseconds per character.',
				'de_DE' => 'Millisekunden je Zeichen.',
			],
			'data-typewriter-hold="2000"' => [
				'en_US' => 'How long a finished line stands.',
				'de_DE' => 'Wie lange eine fertige Zeile stehen bleibt.',
			],
			'data-typewriter-exit="backspace"' => [
				'en_US' => 'Erases instead of fading.',
				'de_DE' => 'Löscht rückwärts, statt auszublenden.',
			],
			'data-typewriter-loop="1"' => [
				'en_US' => 'Starts over after the last line instead of stopping. Default: types once.',
				'de_DE' => 'Beginnt nach der letzten Zeile von vorn, statt anzuhalten. Vorgabe: tippt einmal.',
			],
			'data-typewriter-toggle="[[/typewriter/toggle]]"' => [
				'en_US' => 'A button after the container that pauses the typing and takes it up again (WCAG 2.2.2). The value is its label; the fill is the one the install unit brings.',
				'de_DE' => 'Ein Knopf hinter dem Container, der das Tippen anhält und wieder aufnimmt (WCAG 2.2.2). Der Wert ist die Beschriftung; der Textfill ist der, den die Install-Einheit mitbringt.',
			],
			'data-typewriter-start="load"' => [
				'en_US' => 'Starts at once instead of when it scrolls into view.',
				'de_DE' => 'Startet sofort statt beim Hereinscrollen.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The label of the pause button, [[/typewriter/toggle]], into the Text panel.',
				'de_DE' => 'Die Beschriftung des Pause-Knopfs, [[/typewriter/toggle]], ins Panel Texte.',
			],
		],
	],
	// What it is for: it changes how what is already on the page behaves and
	// brings one text fill of its own, the label of the pause button - the
	// Features panel files it with the sliders and the lightboxes rather than
	// with the content
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: the feature keeps no state at all - what it
	// animates is the markup a project's own template already carries
	'data'				=> [],
	// No settings either, and that is the design: every timing belongs to
	// the element that is being typed (data-typewriter-speed, -hold, -loop,
	// ... - see README.md), not to the site. typewriter.js is a static
	// asset, never rendered through the fill engine (docs/development.md,
	// "Assets Are Not Templates"), so a site-wide default here could not
	// reach it in the first place - the way consent.js has to carry its
	// cookie name through the banner's own data attributes
	'settings'		=> [],
];
