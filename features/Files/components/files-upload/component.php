<?php
declare(strict_types=1);

return [
	'group'      => 'files',
	'label'      => [ 'de_DE' => 'Datei-Upload', 'en_US' => 'File upload' ],
	'icon'       => 'arrow-up',
	'in'         => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'multiple' => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Mehrere Dateien', 'en_US' => 'Several files' ],
			'hint' => [ 'de_DE' => 'Das Skript lässt ein Mitglied mehrere Dateien auf einmal wählen.', 'en_US' => 'The script lets a member choose several files at once.' ] ],
	],
];
