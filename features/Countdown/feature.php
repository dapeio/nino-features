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
				'en_US' => 'Counts down to that moment. Without tz= it is read in the timezone the Countdown setting names - the one the server runs in, by default. A moment is a real, absolute date, and nothing in it is relative to the day it is read: 2026-12-24 18:00, 24 December 2026, a month alone (2026-12 or Dec 2026, which is its first day), 2026-12-24T18:00:00+01:00 or a Unix timestamp such as @1798132800. Nothing is drawn, and the log says so, for a date that does not exist (2027-02-30) and for what is not read: anything relative (tomorrow, next friday, +3 days, 18:00 alone, 24 December without a year) and an ISO week (2026-W52) - it would end at another instant at every rendering.',
				'de_DE' => 'Zählt bis zu diesem Moment herunter. Ohne tz= wird er in der Zeitzone gelesen, die die Einstellung von Countdown nennt - standardmäßig die, in der der Server läuft. Ein Moment ist ein echtes, absolutes Datum, und nichts darin hängt vom Tag des Lesens ab: 2026-12-24 18:00, 24 December 2026, ein Monat allein (2026-12 oder Dec 2026, das ist sein erster Tag), 2026-12-24T18:00:00+01:00 oder ein Unix-Zeitstempel wie @1798132800. Nichts wird gezeichnet, und das Log sagt es, bei einem Datum, das es nicht gibt (2027-02-30), und bei allem, was nicht gelesen wird: allem Relativen (tomorrow, next friday, +3 days, 18:00 allein, 24 December ohne Jahr) und einer ISO-Woche (2026-W52) - es würde bei jedem Rendern an einem anderen Zeitpunkt enden.',
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
			'[countdown ... format="d.m.Y H:i"]' => [
				'en_US' => 'How the date under the counter is written, in PHP\'s date() letters. Default Y-m-d H:i. The names of months and days (F, M, l, D) are written in the language of the page, from the Text panel\'s keys /feature/countdown/month/<name>, month-short, day and day-short; a name that no language has a word for stands there as its key. \\F is a plain F.',
				'de_DE' => 'Wie das Datum unter dem Zähler geschrieben wird, in den Buchstaben von PHPs date(). Standard: Y-m-d H:i. Die Namen der Monate und Tage (F, M, l, D) stehen in der Sprache der Seite, aus den Schlüsseln /feature/countdown/month/<name>, month-short, day und day-short des Panels Texte; ein Name, für den keine Sprache ein Wort hat, steht als sein Schlüssel da. \\F ist ein einfaches F.',
			],
			'[countdown ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the counter. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen des Zählers. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [
			'class="nino-countdown"' => [
				'en_US' => 'What the shortcode writes, around a <time datetime="..."> that carries the moment.',
				'de_DE' => 'Was der Shortcode schreibt, um ein <time datetime="..."> herum, das den Moment trägt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Design: the looks of the counter' => [
				'en_US' => 'With the Design feature on, the tab Countdown of the Design panel offers under Base three looks of the counter - Plain, Tiles and Big - and a select for the separator between the parts: None, Colon or Dot. The knobs stand under Text (the size of the digits), Surface (the space between the parts, where a name stands - under, beside or above its number - and, in Tiles only, the corners) and Colour (in Tiles and Big the colour of the digits, in Tiles only the ground of a tile); a knob the look does not answer to is greyed out. Plain draws the counter as it is without Design. On a section of the ground Page, alternate the default ground of a tile disappears into it - choose another there. The markup stays that of [countdown]. The block design/preview.tpl shows two counters in the preview; Apply to website puts the look on the site.',
				'de_DE' => 'Ist das Feature Design an, bietet der Tab Countdown des Design-Panels unter Grundlage drei Looks des Zählers an - Schlicht, Kacheln und Groß - und eine Auswahl für den Trenner zwischen den Teilen: Keiner, Doppelpunkt oder Punkt. Die Regler stehen unter Text (die Größe der Ziffern), Fläche (der Abstand der Teile, wo ein Name steht - unter, neben oder über seiner Zahl - und, nur bei Kacheln, die Ecken) und Farbe (bei Kacheln und Groß die Farbe der Ziffern, nur bei Kacheln der Grund einer Kachel); ein Regler, auf den der Look nicht antwortet, steht ausgegraut da. Schlicht zeichnet den Zähler wie ohne Design. Auf einer Sektion mit dem Grund Seite alternativ geht der Standard-Grund einer Kachel darin unter - dort wählst Du einen anderen. Das Markup bleibt das von [countdown]. Der Block design/preview.tpl zeigt zwei Zähler in der Vorschau; Auf Website anwenden bringt den Look auf die Site.',
			],
		],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The unit names in both their forms, the names of the months and days (whole and short) and the sentence for afterwards, into the Text panel.',
				'de_DE' => 'Die Einheitennamen in beiden Formen, die Namen der Monate und Tage (ausgeschrieben und kurz) und der Satz für danach, ins Panel Texte.',
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
