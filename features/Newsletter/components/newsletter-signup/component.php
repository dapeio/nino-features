<?php
declare(strict_types=1);

return [
	'group'      => 'newsletter',
	'label'      => [ 'de_DE' => 'Newsletter-Anmeldung', 'en_US' => 'Newsletter signup' ],
	'icon'       => 'mail',
	'in'         => [ 'flow', 'footer-col' ],
	'source'     => 'none',
	'preview'    => 'block',
	'attributes' => [
		'inline' => [ 'type' => 'bool', 'default' => false,
			'label' => [ 'de_DE' => 'In einer Zeile', 'en_US' => 'In one line' ],
			'hint'  => [ 'de_DE' => 'Feld und Knopf nebeneinander. Die Beschriftung lesen dann nur noch Screenreader.', 'en_US' => 'The field and the button side by side. The label is then read by screen readers only.' ] ],
	],
];
