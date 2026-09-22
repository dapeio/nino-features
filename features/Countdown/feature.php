<?php
// features/Countdown/feature.php - what the Features panel reads. The class is
// not declared here: features/Countdown/ can only ever serve
// \Nino\Modules\Countdown.
return [
	'key'					=> 'countdown',
	'name'				=> [ 'en_US' => 'Countdown', 'de_DE' => 'Countdown' ],
	'description'	=> [
		'en_US' => 'The time left until a date, counted down on the page - and, where the script never runs, the date itself, written out and machine-readable.',
		'de_DE' => 'Die Zeit bis zu einem Datum, auf der Seite heruntergezählt - und dort, wo das Skript nie läuft, das Datum selbst, ausgeschrieben und maschinenlesbar.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[countdown to="2026-12-24 18:00"]' => [
				'en_US' => 'Counts down to that moment. Without tz= it is read in the timezone the server runs in.',
				'de_DE' => 'Zählt bis zu diesem Moment herunter. Ohne tz= wird er in der Zeitzone gelesen, in der der Server läuft.',
			],
			'[countdown ... tz="Europe/Berlin"]' => [
				'en_US' => 'The timezone a wall-clock moment is read in. A name PHP does not know renders nothing and says so in the log.',
				'de_DE' => 'Die Zeitzone, in der ein Moment ohne eigenen Offset gelesen wird. Ein Name, den PHP nicht kennt, zeigt nichts und sagt es im Log.',
			],
			'[countdown ... units="days,hours,minutes"]' => [
				'en_US' => 'Which parts are shown. Always largest first, whatever order they are written in. Default: days, hours, minutes, seconds.',
				'de_DE' => 'Welche Teile gezeigt werden. Immer die größten zuerst, in welcher Reihenfolge sie auch geschrieben sind. Vorgabe: Tage, Stunden, Minuten, Sekunden.',
			],
			'[countdown ... done="Es ist so weit"]' => [
				'en_US' => 'What stands there once the moment has passed. Without it the Text panel\'s own sentence is used.',
				'de_DE' => 'Was dort steht, wenn der Moment vorbei ist. Ohne die Angabe wird der Satz aus dem Panel Texte verwendet.',
			],
		],
		'markup' => [
			'class="nino-countdown"' => [
				'en_US' => 'What the shortcode writes, around a <time datetime="..."> that carries the moment.',
				'de_DE' => 'Was der Shortcode schreibt, um ein <time datetime="..."> herum, das den Moment trägt.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The unit names in both their forms, and the sentence for afterwards, into the Text panel.',
				'de_DE' => 'Die Einheitennamen in beiden Formen und der Satz für danach, ins Panel Texte.',
			],
		],
	],
	// It changes how something already on the page reads, over time - the
	// Features panel files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: the moment is written into the page it counts down
	// on, and what is left of it is arithmetic in the reader's own browser
	'data'				=> [],
	/*	And no settings. Which moment, which parts of it and what stands there
		afterwards all belong to the one place the countdown is written - a sale
		ending and a conference opening are not the same countdown, and a site
		may well have both	*/
	'settings'		=> [],
];
