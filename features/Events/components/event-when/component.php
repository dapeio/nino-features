<?php
declare(strict_types=1);

return [
	'group'      => 'events',
	'label'      => [ 'de_DE' => 'Datum', 'en_US' => 'Date' ],
	'icon'       => 'clock',
	'in'         => [ 'flow' ],
	'source'     => 'text',
	'preview'    => 'text',
	'attributes' => [
		'end'  => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Feld des Endes', 'en_US' => 'Field of the end' ],
			'hint' => [ 'de_DE' => 'Der Name des Feldes mit dem Ende, gelesen aus dem Termin der Liste: end. Leer zeigt nur den Beginn.', 'en_US' => 'The name of the field that holds the end, read from the date of the list: end. Empty shows the start alone.' ] ],
		'show' => [ 'type' => 'choice', 'options' => [ 'datetime', 'date', 'time' ], 'default' => 'datetime', 'input' => 'segment', 'labels' => [ 'datetime' => [ 'de_DE' => 'Tag und Uhrzeit', 'en_US' => 'Day and time' ], 'date' => [ 'de_DE' => 'Tag', 'en_US' => 'Day' ], 'time' => [ 'de_DE' => 'Uhrzeit', 'en_US' => 'Time' ] ], 'label' => [ 'de_DE' => 'Zeigt', 'en_US' => 'Shows' ] ],
	],
];
