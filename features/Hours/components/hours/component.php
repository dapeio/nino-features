<?php
declare(strict_types=1);

return [
	'group'      => 'hours',
	'label'      => [ 'de_DE' => 'Öffnungszeiten', 'en_US' => 'Opening hours' ],
	'icon'       => 'clock',
	'in'         => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'location' => [ 'type' => 'choice', 'options' => '\Nino\Modules\Hours::locationOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Standort', 'en_US' => 'Location' ], 'hint' => [ 'de_DE' => 'Leer nimmt in einer Schleife über Standorte den Standort des Elements, sonst alle, einen unter dem anderen.', 'en_US' => 'Empty takes the location of the element in a loop over locations, else all of them, one below the other.' ] ],
		'ahead'    => [ 'type' => 'int', 'min' => 0, 'max' => 366, 'default' => 60, 'label' => [ 'de_DE' => 'Tage voraus', 'en_US' => 'Days ahead' ], 'hint' => [ 'de_DE' => 'Wie viele Tage voraus die Abweichungen aufgelistet werden; eine, die vorbei ist, steht nie da.', 'en_US' => 'How many days ahead the closures are listed; one that is over is never listed.' ] ],
		'name'     => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Name', 'en_US' => 'Name' ], 'hint' => [ 'de_DE' => 'Ob der Name des Standorts über seinen Zeiten steht.', 'en_US' => 'Whether the name of the location is drawn above its hours.' ] ],
		'address'  => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'Adresse', 'en_US' => 'Address' ], 'hint' => [ 'de_DE' => 'Ob die Adresse des Standorts unter seinem Namen steht.', 'en_US' => 'Whether the address of the location is drawn under its name.' ] ],
		'closures' => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Abweichungen', 'en_US' => 'Closures' ], 'hint' => [ 'de_DE' => 'Ob die abweichenden Tage unter der Woche aufgelistet werden.', 'en_US' => 'Whether the days that deviate are listed under the week.' ] ],
	],
];
