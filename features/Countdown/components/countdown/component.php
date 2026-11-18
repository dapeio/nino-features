<?php
declare(strict_types=1);

return [
	'group'      => 'countdown',
	'label'      => [ 'de_DE' => 'Countdown', 'en_US' => 'Countdown' ],
	'icon'       => 'timer',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'block',
	'variants'   => [ 'tiles', 'big' ],
	'labels'     => [
		'regular' => [ 'de_DE' => 'Schlicht', 'en_US' => 'Plain' ],
		'tiles'   => [ 'de_DE' => 'Kacheln', 'en_US' => 'Tiles' ],
		'big'     => [ 'de_DE' => 'Groß', 'en_US' => 'Big' ],
	],
	'attributes' => [
		'to'         => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Zeitpunkt', 'en_US' => 'Moment' ],
			'hint' => [ 'de_DE' => 'Der Zeitpunkt, bis zu dem heruntergezählt wird: ein echtes, absolutes Datum, geschrieben als 2026-12-24 18:00. Ohne ihn, mit einem Datum, das es nicht gibt, oder einem relativen (tomorrow, +3 days) wird nichts gezeichnet.', 'en_US' => 'The moment to count down to: a real, absolute date written as 2026-12-24 18:00. Without it, or with a date that does not exist or that is relative (tomorrow, +3 days), nothing is drawn.' ] ],
		'tz'         => [ 'type' => 'choice', 'options' => '\\Nino\\Modules\\Countdown::zones', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Zeitzone', 'en_US' => 'Timezone' ],
			'hint' => [ 'de_DE' => 'Die Zeitzone, in der der Zeitpunkt gelesen wird. Leer nimmt die Zeitzone aus den Einstellungen des Features.', 'en_US' => 'The timezone the moment is read in. Empty takes the timezone of the feature\'s settings.' ] ],
		'days'       => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Tage zeigen', 'en_US' => 'Show days' ],
			'hint' => [ 'de_DE' => 'Sind alle vier Teile aus, zeigt der Zähler alle vier.', 'en_US' => 'With all four parts off the counter shows all four.' ] ],
		'hours'      => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Stunden zeigen', 'en_US' => 'Show hours' ] ],
		'minutes'    => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Minuten zeigen', 'en_US' => 'Show minutes' ] ],
		'seconds'    => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Sekunden zeigen', 'en_US' => 'Show seconds' ] ],
		'separator'  => [ 'type' => 'choice', 'options' => [ 'none', 'colon', 'dot' ], 'default' => 'none', 'label' => [ 'de_DE' => 'Trenner', 'en_US' => 'Separator' ],
			'labels' => [ 'none' => [ 'de_DE' => 'Keiner', 'en_US' => 'None' ], 'colon' => [ 'de_DE' => 'Doppelpunkt', 'en_US' => 'Colon' ], 'dot' => [ 'de_DE' => 'Punkt', 'en_US' => 'Dot' ] ] ],
		'done'       => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Satz für danach', 'en_US' => 'Sentence for afterwards' ],
			'hint' => [ 'de_DE' => 'Was dort steht, wenn der Zeitpunkt vorbei ist. Leer nimmt den Satz aus dem Panel Texte.', 'en_US' => 'What stands there once the moment has passed. Empty takes the sentence of the Text panel.' ] ],
		'dateFormat' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Datumsformat', 'en_US' => 'Date format' ],
			'hint' => [ 'de_DE' => 'Wie das Datum unter dem Zähler geschrieben wird, in den Buchstaben von PHPs date(). Leer nimmt Y-m-d H:i. Monats- und Tagesnamen stehen in der Sprache der Seite.', 'en_US' => 'How the date under the counter is written, in the letters of PHP\'s date(). Empty takes Y-m-d H:i. Month and day names come in the language of the page.' ] ],
	],
];
