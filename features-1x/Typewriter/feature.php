<?php
// features/Typewriter/feature.php - what the Features panel reads. The class
// is not declared here: features/Typewriter/ can only ever serve
// \Nino\Modules\Typewriter.
return [
	'key'					=> 'typewriter',
	'name'				=> [ 'en_US' => 'Typewriter', 'de_DE' => 'Schreibmaschine' ],
	'description'	=> [
		'en_US' => 'Typewriter lets you type the lines of a container one after the other, with a cursor at the writing head. A headline writes itself that way, once or in a loop, timed per element with data attributes.',
		'de_DE' => 'Die Schreibmaschine ermöglicht es, die Zeilen eines Containers nacheinander zu tippen, mit Cursor am Schreibkopf. Eine Überschrift schreibt sich so selbst, einmal oder in der Schleife, und lässt sich je Element über data-Attribute takten.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'class="nino-typewriter"' => [
				'en_US' => 'On a container its <p> lines are typed one after the other, once; the last line stays.',
				'de_DE' => 'An einem Container werden seine <p>-Zeilen nacheinander getippt, einmal; die letzte Zeile bleibt stehen.',
			],
			'data-typewriter-speed="60" data-typewriter-hold="2000" data-typewriter-pause="300" data-typewriter-start-delay="0"' => [
				'en_US' => 'The timings in milliseconds: per character, how long a finished line stands, between two lines and before the first line.',
				'de_DE' => 'Die Takte in Millisekunden: je Zeichen, wie lange eine fertige Zeile steht, zwischen zwei Zeilen und vor der ersten Zeile.',
			],
			'data-typewriter-exit="backspace" data-typewriter-backspace-speed="25" data-typewriter-fade="400"' => [
				'en_US' => 'Erases instead of fading, at the speed per erased character; fade is the fade in and out in milliseconds, 0 switches it off.',
				'de_DE' => 'Löscht rückwärts statt auszublenden, mit dem Tempo je gelöschtem Zeichen; fade ist das Ein- und Ausblenden in Millisekunden, 0 schaltet es ab.',
			],
			'data-typewriter-loop="1"' => [
				'en_US' => 'Starts over after the last line; the words 1, true, yes and on ask for it, any other value types the lines once.',
				'de_DE' => 'Beginnt nach der letzten Zeile von vorn; die Wörter 1, true, yes und on verlangen es, jeder andere Wert lässt es bei einmal.',
			],
			'data-typewriter-start="load" data-typewriter-lines="p" data-typewriter-cursor="|"' => [
				'en_US' => 'Starts at once instead of on scrolling into view, picks the lines by CSS selector (default p) and sets the cursor character, empty leaves it out.',
				'de_DE' => 'Startet sofort statt beim Hereinscrollen, wählt die Zeilen per CSS-Selektor (Standard p) und setzt das Cursor-Zeichen, leer lässt es weg.',
			],
			'data-typewriter-toggle="[[/feature/typewriter/pause/label]]"' => [
				'en_US' => 'A button after the container that pauses the typing and takes it up again; the value is its label.',
				'de_DE' => 'Ein Knopf hinter dem Container, der das Tippen anhält und wieder aufnimmt; der Wert ist seine Beschriftung.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the label of the pause button, [[/feature/typewriter/pause/label]], into the Text panel.',
				'de_DE' => 'Bringt die Beschriftung des Pause-Knopfs, [[/feature/typewriter/pause/label]], ins Panel Texte.',
			],
		],
	],
	// What it is for: it changes how what is already on the page behaves and
	// brings one text fill of its own, the label of the pause button - the
	// Features panel files it with the sliders and the lightboxes rather than
	// with the content
	'category'		=> 'ui',
	'version'			=> '1.1.1',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing under data/: the feature keeps no state at all - what it
	// animates is the markup a project's own template already carries
	'data'				=> [],
	// Into the site's style and script bundle, behind the kernel's own files
	'assets'			=> [ '/features/Typewriter/assets/typewriter.css', '/features/Typewriter/assets/typewriter.js' ],
	// No settings either, and that is the design: every timing belongs to
	// the element that is being typed (data-typewriter-speed, -hold, -loop,
	// ...), not to the site. typewriter.js is a static
	// asset, never rendered through the fill engine (docs/development.md,
	// "Assets Are Not Templates"), so a site-wide default here could not
	// reach it in the first place - the way consent.js has to carry its
	// cookie name through the banner's own data attributes
	'settings'		=> [],
];
