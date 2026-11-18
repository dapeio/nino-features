<?php
declare(strict_types=1);

return [
	'group'      => 'hours',
	'label'      => [ 'de_DE' => 'Öffnungszeiten heute', 'en_US' => 'Opening hours today' ],
	'icon'       => 'clock',
	'in'         => [ 'flow', 'footer-col', 'header-actions' ],
	'preview'    => 'text',
	'attributes' => [
		'location' => [ 'type' => 'choice', 'options' => '\Nino\Modules\Hours::locationOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Standort', 'en_US' => 'Location' ], 'hint' => [ 'de_DE' => 'Leer nimmt in einer Schleife über Standorte den Standort des Elements, sonst den ersten.', 'en_US' => 'Empty takes the location of the element in a loop over locations, else the first one.' ] ],
		'name'     => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'Name', 'en_US' => 'Name' ], 'hint' => [ 'de_DE' => 'Ob der Name des Standorts vor der Zeile steht.', 'en_US' => 'Whether the name of the location stands in front of the line.' ] ],
	],
];
