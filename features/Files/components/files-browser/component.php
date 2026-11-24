<?php
declare(strict_types=1);

return [
	'group'      => 'files',
	'label'      => [ 'de_DE' => 'Dateibrowser', 'en_US' => 'File browser' ],
	'icon'       => 'list',
	'in'         => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'downloads' => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Link zum Speichern', 'en_US' => 'Link to save' ],
			'hint' => [ 'de_DE' => 'Ein Link neben dem Namen, der die Datei speichert, statt sie zu öffnen.', 'en_US' => 'A link beside the name that saves the file instead of opening it.' ] ],
	],
];
