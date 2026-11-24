<?php
declare(strict_types=1);

return [
	'group'      => 'sign',
	'label'      => [ 'de_DE' => 'Unterschriftenliste', 'en_US' => 'Signature list' ],
	'icon'       => 'check',
	'in'         => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'open'   => [ 'type' => 'bool', 'default' => true, 'label' => [ 'de_DE' => 'Nur was nicht unterschrieben ist', 'en_US' => 'Only what is not signed' ] ],
		'sample' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'Nur zur Ansicht', 'en_US' => 'Sample, for the look' ] ],
	],
];
