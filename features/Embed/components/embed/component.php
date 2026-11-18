<?php
declare(strict_types=1);

return [
	'group'      => 'embed',
	'label'      => [ 'de_DE' => 'Eingebetteter Inhalt', 'en_US' => 'Embedded content' ],
	'icon'       => 'play',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'block',
	'attributes' => [
		'youtube' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'YouTube-Video', 'en_US' => 'YouTube video' ],
			'hint' => [ 'de_DE' => 'Die Video-ID oder die aus dem Browser kopierte Adresse. Gezeigt über youtube-nocookie.com.', 'en_US' => 'The video ID, or the address copied from the browser. Shown through youtube-nocookie.com.' ] ],
		'vimeo'   => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Vimeo-Video', 'en_US' => 'Vimeo video' ],
			'hint' => [ 'de_DE' => 'Die Video-ID oder die aus dem Browser kopierte Adresse.', 'en_US' => 'The video ID, or the address copied from the browser.' ] ],
		'url'     => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Adresse', 'en_US' => 'Address' ],
			'hint' => [ 'de_DE' => 'Alles andere mit einer Einbettungsadresse - eine Karte, ein Buchungswidget, ein Kalender. Nur https. Gilt, wo kein Video genannt ist.', 'en_US' => 'Anything else with an embed address - a map, a booking widget, a calendar. https only. Used where neither video is named.' ] ],
		'title'   => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Titel', 'en_US' => 'Title' ],
			'hint' => [ 'de_DE' => 'Was auf der Fläche steht und wie der geladene Rahmen heißt. Bei jeder Einbindung angeben: Es ist der einzige Name, den ein Screenreader bekommt.', 'en_US' => 'What the surface says and what the loaded frame is called. Say it for every embed: it is the only name a screen reader gets.' ] ],
		'poster'  => [ 'type' => 'image', 'default' => '', 'label' => [ 'de_DE' => 'Bild auf der Fläche', 'en_US' => 'Picture on the surface' ],
			'hint' => [ 'de_DE' => 'Ein Bildplatz des Projekts. Nie ein Vorschaubild des Anbieters.', 'en_US' => 'A picture slot of the project. Never a thumbnail from the provider.' ] ],
		'ratio'   => [ 'type' => 'choice', 'options' => [ '16-9', '4-3', '1-1', '21-9' ], 'default' => '16-9', 'label' => [ 'de_DE' => 'Seitenverhältnis', 'en_US' => 'Ratio' ],
			'labels' => [ '16-9' => [ 'de_DE' => '16:9', 'en_US' => '16:9' ], '4-3' => [ 'de_DE' => '4:3', 'en_US' => '4:3' ], '1-1' => [ 'de_DE' => '1:1', 'en_US' => '1:1' ], '21-9' => [ 'de_DE' => '21:9', 'en_US' => '21:9' ] ] ],
	],
];
