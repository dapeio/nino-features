<?php
declare(strict_types=1);

return [
	'group'      => 'typewriter',
	'label'      => [ 'de_DE' => 'Schreibmaschine', 'en_US' => 'Typewriter' ],
	'icon'       => 'text-cursor-input',
	'in'         => [ 'flow' ],
	'source'     => 'text',
	'format'     => 'lines',
	'preview'    => 'title',
	'attributes' => [
		'tag'   => [ 'type' => 'choice', 'options' => [ 'p', 'h1', 'h2', 'h3', 'h4', 'span' ], 'default' => 'p', 'class' => false,
			'labels' => [ 'p' => [ 'de_DE' => 'Absatz', 'en_US' => 'Paragraph' ], 'h1' => [ 'de_DE' => 'Überschrift 1', 'en_US' => 'Heading 1' ], 'h2' => [ 'de_DE' => 'Überschrift 2', 'en_US' => 'Heading 2' ], 'h3' => [ 'de_DE' => 'Überschrift 3', 'en_US' => 'Heading 3' ], 'h4' => [ 'de_DE' => 'Überschrift 4', 'en_US' => 'Heading 4' ], 'span' => [ 'de_DE' => 'Ohne Absatz', 'en_US' => 'No paragraph' ] ],
			'label' => [ 'de_DE' => 'Element', 'en_US' => 'Element' ], 'hint' => [ 'de_DE' => 'Ein Titel der Seite ist Überschrift 1, und davon gibt es eine je Seite.', 'en_US' => 'The title of a page is heading 1, and a page has one.' ] ],
		'speed' => [ 'type' => 'int', 'min' => 5, 'max' => 1000, 'default' => 45, 'label' => [ 'de_DE' => 'Tempo', 'en_US' => 'Speed' ], 'hint' => [ 'de_DE' => 'Millisekunden je Zeichen. Je kleiner, desto schneller.', 'en_US' => 'Milliseconds per character. The smaller, the faster.' ] ],
		'hold'  => [ 'type' => 'int', 'min' => 0, 'max' => 30000, 'default' => 1600, 'label' => [ 'de_DE' => 'Haltezeit', 'en_US' => 'Hold' ], 'hint' => [ 'de_DE' => 'Millisekunden, die eine fertige Zeile steht, bevor sie ausgeblendet wird.', 'en_US' => 'Milliseconds a finished line stands before it fades out.' ] ],
		'loop'  => [ 'type' => 'bool', 'default' => false, 'class' => false, 'label' => [ 'de_DE' => 'In der Schleife', 'en_US' => 'Round and round' ], 'hint' => [ 'de_DE' => 'Beginnt nach der letzten Zeile von vorn, statt auf ihr stehen zu bleiben.', 'en_US' => 'Starts over after the last line instead of staying on it.' ] ],
	],
];
