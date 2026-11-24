<?php
declare(strict_types=1);

return [
	'group'      => 'sign',
	'label'      => [ 'de_DE' => 'Unterschriftsformular', 'en_US' => 'Signature form' ],
	'icon'       => 'pencil',
	'in'         => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'sample' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'Nur zur Ansicht', 'en_US' => 'Sample, for the look' ] ],
	],
];
