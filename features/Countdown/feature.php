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
				'en_US' => 'Counts down to that moment. Without tz= it is read in the timezone the Countdown setting names - the one the server runs in, by default.',
				'de_DE' => 'Zählt bis zu diesem Moment herunter. Ohne tz= wird er in der Zeitzone gelesen, die die Einstellung von Countdown nennt - standardmäßig die, in der der Server läuft.',
			],
			'[countdown ... tz="Europe/Berlin"]' => [
				'en_US' => 'The timezone a wall-clock moment is read in, for this one countdown instead of the setting. A name PHP does not know renders nothing and says so in the log.',
				'de_DE' => 'Die Zeitzone, in der ein Moment ohne eigenen Offset gelesen wird, für diesen einen Countdown statt der Einstellung. Ein Name, den PHP nicht kennt, zeigt nichts und sagt es im Log.',
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
];
