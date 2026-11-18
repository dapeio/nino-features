<?php
declare(strict_types=1);

return [
	'group'      => 'ticker',
	'label'      => [ 'de_DE' => 'Laufband', 'en_US' => 'Ticker' ],
	'icon'       => 'move-horizontal',
	'in'         => [ 'page', 'flow' ],
	'offers'     => [ 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'speed'     => [ 'type' => 'int', 'min' => 1, 'max' => 1000, 'default' => 40, 'label' => [ 'de_DE' => 'Tempo', 'en_US' => 'Speed' ], 'hint' => [ 'de_DE' => 'Pixel je Sekunde. 40 ist langsam genug, um ein Wort im Vorbeilaufen zu lesen.', 'en_US' => 'Pixels per second. 40 is slow enough to read a word on the way past.' ] ],
		'direction' => [ 'type' => 'choice', 'options' => [ 'left', 'right' ], 'default' => 'left', 'class' => false,
			'labels' => [ 'left' => [ 'de_DE' => 'Nach links', 'en_US' => 'To the left' ], 'right' => [ 'de_DE' => 'Nach rechts', 'en_US' => 'To the right' ] ],
			'label' => [ 'de_DE' => 'Richtung', 'en_US' => 'Direction' ] ],
		'loop'      => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'In der Schleife', 'en_US' => 'Round and round' ], 'hint' => [ 'de_DE' => 'Beginnt nahtlos von vorn, statt nach einem Durchlauf still zu stehen.', 'en_US' => 'Starts again without a seam instead of standing still after one cycle.' ] ],
		'toggle'    => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'Pause-Knopf', 'en_US' => 'Pause button' ], 'hint' => [ 'de_DE' => 'Ein Knopf hinter der Reihe, der sie anhält und wieder aufnimmt (WCAG 2.2.2). Eine Schleife braucht ihn.', 'en_US' => 'A button after the row that pauses it and takes it up again (WCAG 2.2.2). A loop needs one.' ] ],
		'pause'     => [ 'type' => 'bool', 'default' => true, 'class' => false, 'label' => [ 'de_DE' => 'Unter dem Zeiger anhalten', 'en_US' => 'Stop under the pointer' ], 'hint' => [ 'de_DE' => 'Die Reihe hält an, solange man darauf zeigt, damit ein Logo betrachtet werden kann. Aus lässt sie weiterlaufen.', 'en_US' => 'The row stops while it is pointed at, so a logo can be looked at. Off keeps it running.' ] ],
	],
];
