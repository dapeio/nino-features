<?php
declare(strict_types=1);

return [
	'group'      => 'hello',
	'label'      => [ 'de_DE' => 'Gruß', 'en_US' => 'Greeting' ],
	'icon'       => 'text',
	'in'         => [ 'flow' ],
	'preview'    => 'text',
	'attributes' => [
		'name'  => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Name', 'en_US' => 'Name' ], 'hint' => [ 'de_DE' => 'Leer grüßt den Namen aus dem Panel.', 'en_US' => 'Empty greets the name of the panel.' ] ],
		'shout' => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'Großbuchstaben', 'en_US' => 'Capitals' ] ],
	],
];
