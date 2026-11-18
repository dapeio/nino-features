<?php
declare(strict_types=1);

return [
	'group'      => 'social',
	'label'      => [ 'de_DE' => 'Social-Link', 'en_US' => 'Social link' ],
	'icon'       => 'share-2',
	'in'         => [ 'flow', 'footer-col', 'header-actions' ],
	'preview'    => 'button',
	'variants'   => [ 'badges', 'outline' ],
	'labels'     => [
		'regular' => [ 'de_DE' => 'Schlicht', 'en_US' => 'Plain' ],
		'badges'  => [ 'de_DE' => 'Plaketten', 'en_US' => 'Badges' ],
		'outline' => [ 'de_DE' => 'Umriss', 'en_US' => 'Outline' ],
	],
	'attributes' => [
		'profile' => [ 'type' => 'choice', 'options' => '\Nino\Modules\Social::linkOptions', 'default' => '', 'class' => false, 'label' => [ 'de_DE' => 'Link', 'en_US' => 'Link' ], 'hint' => [ 'de_DE' => 'Ein Link des Elementtyps Social Media. Ohne Wahl wird nichts gezeichnet.', 'en_US' => 'One link of the element type Social Media. Without a choice nothing is drawn.' ] ],
		'show'    => [ 'type' => 'choice', 'options' => [ 'both', 'icon', 'text' ], 'default' => 'both', 'labels' => [ 'both' => [ 'de_DE' => 'Icon und Name', 'en_US' => 'Icon and name' ], 'icon' => [ 'de_DE' => 'Icon', 'en_US' => 'Icon' ], 'text' => [ 'de_DE' => 'Name', 'en_US' => 'Name' ] ], 'label' => [ 'de_DE' => 'Gezeigt', 'en_US' => 'Shown' ], 'hint' => [ 'de_DE' => 'Beim Icon allein trägt der Link seinen Namen für Vorleseprogramme.', 'en_US' => 'With the icon alone the link still carries its name for screen readers.' ] ],
	],
];
