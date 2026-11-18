<?php
declare(strict_types=1);

return [
	'group'      => 'posts',
	'label'      => [ 'de_DE' => 'Beitrag', 'en_US' => 'Post' ],
	'icon'       => 'newspaper',
	'in'         => [ 'page', 'flow', 'section-row' ],
	'offers'     => [ 'flow', 'section-row', 'section-col' ],
	'through'    => true,
	'children'   => 'nodes',
	'preview'    => 'cells',
	'attributes' => [],
];
