<?php
declare(strict_types=1);

return [
	'group'      => 'mode',
	'label'      => [ 'de_DE' => 'Hell/Dunkel-Schalter', 'en_US' => 'Light/dark switch' ],
	'icon'       => 'text',
	'in'         => [ 'flow', 'header-actions', 'footer-col' ],
	'preview'    => 'button',
	'attributes' => [
		'icons' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'Nur Icons', 'en_US' => 'Icons only' ], 'hint' => [ 'de_DE' => 'Ohne die Wörter - für eine volle Kopfzeile.', 'en_US' => 'Without the words - for a crowded header bar.' ] ],
	],
];
