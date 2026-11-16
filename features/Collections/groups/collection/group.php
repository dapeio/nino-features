<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Sammlungen', 'en_US' => 'Collections' ],
	'knobs' => [
		'collection-list.gap' => [ 'token' => '--collection-list-gap', 'label' => [ 'de_DE' => 'Abstand der Zeilen einer Liste', 'en_US' => 'Space between the rows of a list' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'collection-table.pad' => [ 'token' => '--collection-table-pad', 'label' => [ 'de_DE' => 'Innenabstand der Zellen', 'en_US' => 'Padding of the cells' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'collection-filter.gap' => [ 'token' => '--collection-filter-gap', 'label' => [ 'de_DE' => 'Abstand der Filterknöpfe', 'en_US' => 'Space between the filter buttons' ],
			'ladder' => [ 'var(--page-space-1)', 'calc( var(--page-space-1) * 1.5 )', 'var(--page-space-2)', 'var(--page-space-3)', 'var(--page-space-4)' ] ],
		'collection-filter.radius' => [ 'token' => '--collection-filter-radius', 'label' => [ 'de_DE' => 'Rundung der Filterknöpfe', 'en_US' => 'Radius of the filter buttons' ],
			'ladder' => [ '0px', 'calc( var(--page-radius) / 2 )', 'var(--page-radius)', 'calc( var(--page-radius) * 2 )', '999px' ] ],
	],
];
