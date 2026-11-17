<?php
declare(strict_types=1);

return [
	'group'      => 'collection',
	'label'      => [ 'de_DE' => 'Filter', 'en_US' => 'Filter' ],
	'in'         => [ 'page', 'flow', 'section-row' ],
	'offers'     => [],
	'through'    => true,
	'children'   => 'nodes',
	'preview'    => 'button',
	'attributes' => [
		'by'  => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Feld', 'en_US' => 'Field' ], 'hint' => [ 'de_DE' => 'Das Feld, dessen Werte zu Knöpfen werden: .category', 'en_US' => 'The field whose values become buttons: .category' ] ],
		'all' => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Beschriftung für alle', 'en_US' => 'Label for all' ] ],
	],
];
