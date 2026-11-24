<?php
declare(strict_types=1);

return [
	'group'      => 'members',
	'label'      => [ 'de_DE' => 'Mitglieder-Anmeldung', 'en_US' => 'Member sign-in' ],
	'icon'       => 'lock',
	'in'         => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'inline' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'In einer Zeile', 'en_US' => 'In one line' ] ],
	],
];
