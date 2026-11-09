<?php
// features/Countdown/feature.php - what the Features panel reads. The class is
// not declared here: features/Countdown/ can only ever serve
// \Nino\Modules\Countdown.
return [
	'key'					=> 'countdown',
	'name'				=> [ 'en_US' => 'Countdown', 'de_DE' => 'Countdown' ],
	'description'	=> [
		'en_US' => 'Countdown lets you count the time left until a date down on the page. Where the script does not run, the date itself stands there, written out and machine-readable.',
		'de_DE' => 'Der Countdown ermöglicht es, die Zeit bis zu einem Datum auf der Seite herunterzuzählen. Wo das Skript nicht läuft, steht das Datum ausgeschrieben und maschinenlesbar da.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[countdown to="2026-12-24 18:00"]' => [
				'en_US' => 'Counts down to that moment; real, absolute dates are allowed, nothing relative such as tomorrow, and what is not read draws nothing and says so in the log.',
				'de_DE' => 'Zählt bis zu diesem Moment herunter; erlaubt sind echte, absolute Daten, nichts Relatives wie tomorrow, und was nicht gelesen wird, zeichnet nichts und steht im Log.',
			],
			'[countdown ... tz="Europe/Berlin" units="days,hours,minutes" done="Es ist so weit" format="d.m.Y H:i" class="my-class"]' => [
				'en_US' => 'Sets the timezone, the visible units, the sentence for afterwards, the date format in the letters of date() and a class of your own.',
				'de_DE' => 'Setzt Zeitzone, sichtbare Einheiten, den Satz für danach, das Datumsformat in den Buchstaben von date() und eine eigene Klasse.',
			],
		],
		'markup' => [
			'class="nino-countdown"' => [
				'en_US' => 'This is what the shortcode writes, around a <time datetime="..."> that carries the moment.',
				'de_DE' => 'Das schreibt der Shortcode, um ein <time datetime="..."> herum, das den Moment trägt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the counter' => [
				'en_US' => 'With the Design feature the counter offers the looks Plain, Tiles and Big, a select for the separator and knobs for text, surface and colour, with a preview.',
				'de_DE' => 'Mit dem Design bietet der Zähler die Looks Schlicht, Kacheln und Groß, eine Auswahl für den Trenner und Regler für Text, Fläche und Farbe, samt Vorschau.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the units, the names of months and days and the sentence for afterwards into the Text panel.',
				'de_DE' => 'Bringt Einheiten, Monats- und Tagesnamen und den Satz für danach ins Panel Texte.',
			],
		],
	],
	// It changes how something already on the page reads, over time - the
	// Features panel files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.2.0',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing under data/: the moment is written into the page it counts down
	// on, and what is left of it is arithmetic in the reader's own browser
	'data'				=> [],
	// The counter's stylesheet and its script, into the site's two bundles
	'assets'			=> [ '/features/Countdown/assets/countdown.css', '/features/Countdown/assets/countdown.js' ],
	/*	One setting, the zone a wall-clock moment is read in where the shortcode
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
				'en_US' => 'The timezone a countdown without tz= and without an offset of its own is read in. "As the server is set" keeps the behaviour from before; tz= on the shortcode always wins.',
				'de_DE' => 'Die Zeitzone, in der ein Countdown ohne tz= und ohne eigenen Offset gelesen wird. „Wie der Server eingestellt ist“ lässt alles wie bisher; tz= am Shortcode hat immer Vorrang.',
			],
			'options'		=> [ 'server' => [ 'en_US' => 'As the server is set (PHP default)', 'de_DE' => 'Wie der Server eingestellt ist (PHP-Standard)' ] ]
				+ ( static fn( array $ids ): array => array_combine( $ids, $ids ) )( \DateTimeZone::listIdentifiers() ),
			'required'	=> true,
			'default'		=> 'server',
		],
	],
	/*	The shortcode as the Builder offers it: no first argument, because the moment
		is written in the call itself and a date is no text key's value here - and
		every attribute a string, as the shortcode reads it. The class has no
		componentCountdown(): doShortcode() renders the component	*/
	'components'	=> [
		'countdown' => [
			'label'			=> [ 'en_US' => 'Countdown', 'de_DE' => 'Countdown' ],
			'source'		=> 'none',
			'attributes'	=> [
				'to' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Moment', 'de_DE' => 'Zeitpunkt' ],
					'hint'		=> [
						'en_US' => 'The moment to count down to, a real, absolute date written as 2026-12-24 18:00. Without it, or with a date that does not exist or that is relative (tomorrow, +3 days), nothing is drawn.',
						'de_DE' => 'Der Zeitpunkt, bis zu dem heruntergezählt wird, ein echtes, absolutes Datum, geschrieben als 2026-12-24 18:00. Ohne ihn, mit einem Datum, das es nicht gibt, oder einem relativen (tomorrow, +3 days) wird nichts gezeichnet.',
					],
				],
				'tz' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Timezone', 'de_DE' => 'Zeitzone' ],
					'hint'		=> [
						'en_US' => 'A name such as Europe/Berlin. Empty takes the timezone of the setting.',
						'de_DE' => 'Ein Name wie Europe/Berlin. Leer nimmt die Zeitzone aus der Einstellung.',
					],
				],
				'units' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Parts', 'de_DE' => 'Teile' ],
					'hint'		=> [
						'en_US' => 'Which parts are shown, separated by commas: days, hours, minutes, seconds. Empty shows all four.',
						'de_DE' => 'Welche Teile gezeigt werden, durch Komma getrennt: days, hours, minutes, seconds. Leer zeigt alle vier.',
					],
				],
				'done' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Sentence for afterwards', 'de_DE' => 'Satz für danach' ],
					'hint'		=> [
						'en_US' => 'What stands there once the moment has passed. Empty takes the sentence of the Text panel.',
						'de_DE' => 'Was dort steht, wenn der Moment vorbei ist. Leer nimmt den Satz aus dem Panel Texte.',
					],
				],
				'format' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Date format', 'de_DE' => 'Datumsformat' ],
					'hint'		=> [
						'en_US' => 'How the date under the counter is written, in the letters of PHP\'s date(). Empty takes Y-m-d H:i. Month and day names come in the language of the page.',
						'de_DE' => 'Wie das Datum unter dem Zähler geschrieben wird, in den Buchstaben von PHPs date(). Leer nimmt Y-m-d H:i. Monats- und Tagesnamen stehen in der Sprache der Seite.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'div .nino-countdown',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'line', [ 'x1' => '10', 'x2' => '14', 'y1' => '2', 'y2' => '2' ] ],
				[ 'line', [ 'x1' => '12', 'x2' => '15', 'y1' => '14', 'y2' => '11' ] ],
				[ 'circle', [ 'cx' => '12', 'cy' => '14', 'r' => '8' ] ],
			],
		],
	],
];
