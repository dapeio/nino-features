<?php
// features/Countdown/feature.php - what the Features panel reads. The class is
// not declared here: features/Countdown/ can only ever serve
// \Nino\Modules\Countdown.
return [
	'key'					=> 'countdown',
	'name'				=> [ 'en_US' => 'Countdown', 'de_DE' => 'Countdown' ],
	'description'	=> [
		'en_US' => 'Countdown lets you count the time left until a date down on the page, plain, in tiles or in big digits. Where the script does not run, the date itself stands there, written out and machine-readable.',
		'de_DE' => 'Der Countdown ermöglicht es, die Zeit bis zu einem Datum auf der Seite herunterzuzählen, schlicht, in Kacheln oder in großen Ziffern. Wo das Skript nicht läuft, steht das Datum ausgeschrieben und maschinenlesbar da.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[countdown to="2026-12-24 18:00"]' => [
				'en_US' => 'Counts down to that moment; real, absolute dates are allowed, nothing relative such as tomorrow, and what is not read draws nothing and says so in the log.',
				'de_DE' => 'Zählt bis zu diesem Moment herunter; erlaubt sind echte, absolute Daten, nichts Relatives wie tomorrow, und was nicht gelesen wird, zeichnet nichts und steht im Log.',
			],
			'[countdown to="..." tz="Europe/Berlin" days="1" hours="1" minutes="0" seconds="0"]' => [
				'en_US' => 'Sets the timezone the moment is read in and switches the parts on or off (each is on unless it says 0; with none on, all four are drawn).',
				'de_DE' => 'Setzt die Zeitzone, in der der Moment gelesen wird, und schaltet die Teile an oder aus (jeder ist an, solange er nicht 0 sagt; sind alle aus, werden alle vier gezeichnet).',
			],
			'[countdown to="..." done="Es ist so weit" dateFormat="d.m.Y H:i" separator="colon"]' => [
				'en_US' => 'Sets the sentence for afterwards, the date format in the letters of date() (F, M, l and D come in the words of the Text panel) and the sign between the parts (none, colon or dot).',
				'de_DE' => 'Setzt den Satz für danach, das Datumsformat in den Buchstaben von date() (F, M, l und D kommen in den Wörtern des Panels Texte) und das Zeichen zwischen den Teilen (none, colon oder dot).',
			],
			'[countdown to="..." variant="tiles"]' => [
				'en_US' => 'The look of the counter: plain (none), tiles or big.',
				'de_DE' => 'Der Look des Zählers: schlicht (keiner), tiles oder big.',
			],
		],
		'markup' => [
			'class="countdown"' => [
				'en_US' => 'This is what the component writes, around a <time datetime="..."> that carries the moment; the counter stands beside it and takes its place once the script runs.',
				'de_DE' => 'Das schreibt die Komponente, um ein <time datetime="..."> herum, das den Moment trägt; der Zähler steht daneben und ersetzt es, sobald das Skript läuft.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group Countdown' => [
				'en_US' => 'The Themes panel offers the counter as the component countdown with the looks Plain, Tiles and Big and the knobs for the size of the digits, the space between the parts and the corners of the tiles.',
				'de_DE' => 'Das Panel Themes bietet den Zähler als Komponente countdown an, mit den Looks Schlicht, Kacheln und Groß und den Reglern für die Größe der Ziffern, den Abstand der Teile und die Ecken der Kacheln.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the units, the names of months and days and the sentence for afterwards into the Text panel.',
				'de_DE' => 'Bringt Einheiten, Monats- und Tagesnamen und den Satz für danach ins Panel Texte.',
			],
			'blocks/countdown-launch/' => [
				'en_US' => 'A block: a headline, a sentence and the counter in big digits.',
				'de_DE' => 'Ein Block: eine Überschrift, ein Satz und der Zähler in großen Ziffern.',
			],
		],
	],
	// It changes how something already on the page reads, over time - the
	// Features panel files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// Nothing under data/: the moment is written into the page it counts down
	// on, and what is left of it is arithmetic in the reader's own browser
	'data'				=> [],
	// The counter's script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Countdown/assets/countdown.js' ],
	/*	One setting, the zone a wall-clock moment is read in where the call
		names none. Which moment, which parts of it and what stands there
		afterwards stay with the one place the countdown is written - a sale
		ending and a conference opening are not the same countdown, and a site
		may well have both. 'server' is a value of its own rather than '': a
		select the Features panel has once filled cannot be emptied again, and
		the kernel refuses '' for a required one anyway. The map is built here
		without a variable, because Features::manifest(), bin/build.php and the
		tests all include() this file	*/
	'settings'		=> [
		'timezone' => [
			'type'			=> 'select',
			'label'			=> [ 'en_US' => 'Default timezone', 'de_DE' => 'Standard-Zeitzone' ],
			'hint'			=> [
				'en_US' => 'The timezone a countdown without tz and without an offset of its own is read in. "As the server is set" takes the one of the PHP process; tz on the countdown always wins.',
				'de_DE' => 'Die Zeitzone, in der ein Countdown ohne tz und ohne eigenen Offset gelesen wird. „Wie der Server eingestellt ist“ nimmt die des PHP-Prozesses; tz am Countdown hat immer Vorrang.',
			],
			'options'		=> [ 'server' => [ 'en_US' => 'As the server is set (PHP default)', 'de_DE' => 'Wie der Server eingestellt ist (PHP-Standard)' ] ]
				+ ( static fn( array $ids ): array => array_combine( $ids, $ids ) )( \DateTimeZone::listIdentifiers() ),
			'required'	=> true,
			'default'		=> 'server',
		],
	],
];
