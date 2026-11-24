<?php
declare(strict_types=1);

return [
	'group'      => 'events',
	'label'      => [ 'de_DE' => 'Termine', 'en_US' => 'Events' ],
	'icon'       => 'calendar',
	'in'         => [ 'page', 'flow', 'section-row' ],
	'offers'     => [ 'flow', 'section-row', 'section-col' ],
	'through'    => true,
	'children'   => 'nodes',
	'preview'    => 'cells',
	'attributes' => [
		'scope'  => [ 'type' => 'choice', 'options' => [ 'upcoming', 'past', 'all' ], 'default' => 'upcoming', 'class' => false, 'input' => 'segment', 'labels' => [ 'upcoming' => [ 'de_DE' => 'Anstehend', 'en_US' => 'Upcoming' ], 'past' => [ 'de_DE' => 'Vergangen', 'en_US' => 'Past' ], 'all' => [ 'de_DE' => 'Alle', 'en_US' => 'All' ] ], 'label' => [ 'de_DE' => 'Termine', 'en_US' => 'Dates' ],
			'hint' => [ 'de_DE' => 'Anstehend: was nicht vorbei ist. Vergangen: was vorbei ist. Alle: beides.', 'en_US' => 'Upcoming: what is not over. Past: what is over. All: both.' ] ],
		'order'  => [ 'type' => 'choice', 'options' => [ 'asc', 'desc' ], 'default' => 'asc', 'class' => false, 'input' => 'segment', 'labels' => [ 'asc' => [ 'de_DE' => 'Aufsteigend', 'en_US' => 'Ascending' ], 'desc' => [ 'de_DE' => 'Absteigend', 'en_US' => 'Descending' ] ], 'label' => [ 'de_DE' => 'Reihenfolge', 'en_US' => 'Order' ],
			'hint' => [ 'de_DE' => 'Aufsteigend beginnt mit dem Termin, der Jetzt am nächsten liegt: dem nächsten anstehenden, dem spätesten der vergangenen. Bei allen Terminen ist es der früheste zuerst.', 'en_US' => 'Ascending starts with the date nearest to now: the next one that is coming, the latest of the past ones. For all dates it is the earliest first.' ] ],
		'limit'  => [ 'type' => 'int', 'default' => 0, 'min' => 0, 'label' => [ 'de_DE' => 'Höchstens', 'en_US' => 'At most' ],
			'hint' => [ 'de_DE' => 'Die Zahl der Termine, die dieser Besucher sieht. 0 zeichnet alle.', 'en_US' => 'The number of dates this visitor sees. 0 draws all of them.' ] ],
		'offset' => [ 'type' => 'int', 'default' => 0, 'min' => 0, 'label' => [ 'de_DE' => 'Überspringen', 'en_US' => 'Skip' ],
			'hint' => [ 'de_DE' => 'Wie viele Termine am Anfang übersprungen werden.', 'en_US' => 'How many dates at the start are skipped.' ] ],
		'query'  => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Eingrenzen', 'en_US' => 'Narrow by' ],
			'hint' => [ 'de_DE' => 'Ein Feld und sein Wert, place=Halle; mehrere mit &.', 'en_US' => 'A field and its value, place=Hall; several joined with &.' ] ],
	],
];
