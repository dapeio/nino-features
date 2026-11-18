<?php
declare(strict_types=1);

return [
	'group'      => 'social',
	'label'      => [ 'de_DE' => 'Social-Links', 'en_US' => 'Social links' ],
	'icon'       => 'share-2',
	'in'         => [ 'flow', 'footer-col', 'header-actions' ],
	'preview'    => 'block',
	'variants'   => [ 'badges', 'outline' ],
	'labels'     => [
		'regular' => [ 'de_DE' => 'Schlicht', 'en_US' => 'Plain' ],
		'badges'  => [ 'de_DE' => 'Plaketten', 'en_US' => 'Badges' ],
		'outline' => [ 'de_DE' => 'Umriss', 'en_US' => 'Outline' ],
	],
	'attributes' => [
		'only'    => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Nur diese', 'en_US' => 'Only these' ], 'hint' => [ 'de_DE' => 'Element-IDs mit Komma, etwa instagram,youtube. Die Reihenfolge bleibt die des Felds Position.', 'en_US' => 'Element IDs with a comma, such as instagram,youtube. The order stays the one of the field Position.' ] ],
		'exclude' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Weglassen', 'en_US' => 'Leave out' ], 'hint' => [ 'de_DE' => 'Element-IDs mit Komma.', 'en_US' => 'Element IDs with a comma.' ] ],
		'show'    => [ 'type' => 'choice', 'options' => [ 'icon', 'text', 'both' ], 'default' => 'icon', 'labels' => [ 'icon' => [ 'de_DE' => 'Icon', 'en_US' => 'Icon' ], 'text' => [ 'de_DE' => 'Name', 'en_US' => 'Name' ], 'both' => [ 'de_DE' => 'Icon und Name', 'en_US' => 'Icon and name' ] ], 'label' => [ 'de_DE' => 'Gezeigt', 'en_US' => 'Shown' ], 'hint' => [ 'de_DE' => 'Beim Icon allein trägt der Link seinen Namen für Vorleseprogramme.', 'en_US' => 'With the icon alone the link still carries its name for screen readers.' ] ],
		'size'    => [ 'type' => 'choice', 'options' => [ 'regular', 'small', 'large' ], 'default' => 'regular', 'labels' => [ 'regular' => [ 'de_DE' => 'Wie die Seite', 'en_US' => 'As the page' ], 'small' => [ 'de_DE' => 'Klein', 'en_US' => 'Small' ], 'large' => [ 'de_DE' => 'Groß', 'en_US' => 'Large' ] ], 'label' => [ 'de_DE' => 'Größe', 'en_US' => 'Size' ] ],
	],
];
