<?php
declare(strict_types=1);

return [
	'group'      => 'collection',
	'label'      => [ 'de_DE' => 'Tabelle', 'en_US' => 'Table' ],
	'icon'       => 'table',
	'in'         => [ 'flow', 'collection-filter' ],
	'offers'     => [],
	'children'   => 'nodes',
	'preview'    => 'table',
	'attributes' => [
		'sort'    => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Sortierung', 'en_US' => 'Sort' ], 'hint' => [ 'de_DE' => 'Felder mit Komma, ein Minus davor kehrt um: -date', 'en_US' => 'Fields with a comma, a minus in front reverses: -date' ] ],
		'limit'   => [ 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 10000, 'label' => [ 'de_DE' => 'Höchstens', 'en_US' => 'At most' ], 'hint' => [ 'de_DE' => '0 zeigt alle', 'en_US' => '0 shows all' ] ],
		'offset'  => [ 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 1000000, 'label' => [ 'de_DE' => 'Überspringen', 'en_US' => 'Skip' ] ],
		'query'   => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Abfrage', 'en_US' => 'Query' ], 'hint' => [ 'de_DE' => 'feld=wert&feld=%teil%', 'en_US' => 'field=value&field=%part%' ] ],
		'empty'   => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Text ohne Elemente', 'en_US' => 'Text without elements' ] ],
		'caption' => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Beschriftung', 'en_US' => 'Caption' ], 'hint' => [ 'de_DE' => 'Der Name der Tabelle für Screenreader', 'en_US' => 'The name of the table for screen readers' ] ],
	],
];
