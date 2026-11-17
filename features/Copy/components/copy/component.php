<?php
declare(strict_types=1);

return [
	'group'      => 'copy',
	'label'      => [ 'de_DE' => 'Text zum Kopieren', 'en_US' => 'Copy text' ],
	'icon'       => 'copy',
	'in'         => [ 'flow' ],
	'source'     => 'text',
	'format'     => 'plain',
	'preview'    => 'text',
	'attributes' => [
		'copied' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Kopierter Text', 'en_US' => 'Text that is copied' ], 'hint' => [ 'de_DE' => 'Leer kopiert das Gezeigte.', 'en_US' => 'Empty copies what is shown.' ] ],
		'name'   => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Name des Knopfs', 'en_US' => 'Name of the button' ], 'hint' => [ 'de_DE' => 'Wofür der Knopf da ist, etwa IBAN.', 'en_US' => 'What the button is for, such as IBAN.' ] ],
		'block'  => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'Als Block', 'en_US' => 'As a block' ] ],
	],
];
