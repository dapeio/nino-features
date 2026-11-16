<?php
declare(strict_types=1);

return [
	'group'      => 'collection',
	'label'      => [ 'de_DE' => 'Spalte', 'en_US' => 'Column' ],
	'in'         => [ 'collection-table' ],
	'preview'    => 'cells',
	'attributes' => [
		'title'   => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Kopf', 'en_US' => 'Heading' ], 'hint' => [ 'de_DE' => '~name, ein Schlüssel oder ein fester Text', 'en_US' => '~name, a key or a fixed text' ] ],
		'align'   => [ 'type' => 'choice', 'options' => [ 'start', 'center', 'end' ], 'default' => 'start', 'label' => [ 'de_DE' => 'Ausrichtung', 'en_US' => 'Alignment' ] ],
		'as'      => [ 'type' => 'choice', 'options' => '\\Nino\\Modules\\Collections::formats', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Formatierung', 'en_US' => 'Formatting' ], 'hint' => [ 'de_DE' => 'Wie eine Zahl oder ein Datum geschrieben wird', 'en_US' => 'How a number or a date is written' ] ],
		'rowhead' => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'Zellen sind Zeilenköpfe', 'en_US' => 'Cells are row headings' ] ],
	],
];
