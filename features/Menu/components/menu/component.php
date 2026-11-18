<?php
declare(strict_types=1);

return [
	'group'      => 'menu',
	'label'      => [ 'de_DE' => 'Menü mit Panels', 'en_US' => 'Menu with panels' ],
	'icon'       => 'panels-top-left',
	'in'         => [ 'header-wrap', 'footer-col' ],
	'once'       => true,
	'source'     => 'none',
	'preview'    => 'block',
	'attributes' => [
		// A choice among the menus there are: the list is a method of the class (Menu::options()), the empty word is the first menu
		'key'   => [ 'type' => 'choice', 'options' => '\\Nino\\Modules\\Menu::options', 'default' => '', 'class' => false,
			'label' => [ 'de_DE' => 'Menü', 'en_US' => 'Menu' ],
			'hint' => [ 'de_DE' => 'Das Menü, das gezeichnet wird. Ohne Wahl ist es das erste Menü. Menüs legst Du im Panel Menü an.', 'en_US' => 'The menu to draw. Without a choice it is the first menu. You make menus in the Menu panel.' ] ],
		'label' => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Name der Navigation', 'en_US' => 'Name of the navigation' ],
			'hint' => [ 'de_DE' => 'Was ein Screenreader als Namen der Navigation vorliest. Leer nimmt das Wort der Website für die Hauptnavigation oder die Fußnavigation.', 'en_US' => 'What a screen reader reads as the name of the navigation. Empty takes the site\'s word for the main or the footer navigation.' ] ],
	],
];
