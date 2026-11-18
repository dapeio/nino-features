<?php
declare(strict_types=1);

return [
	'group'      => 'form',
	'label'      => [ 'de_DE' => 'Formular', 'en_US' => 'Form' ],
	'icon'       => 'text-cursor-input',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'block',
	'variants'   => [ 'filled', 'line' ],
	'labels'     => [
		'regular' => [ 'de_DE' => 'Umrandet', 'en_US' => 'Boxed' ],
		'filled'  => [ 'de_DE' => 'Getönt', 'en_US' => 'Filled' ],
		'line'    => [ 'de_DE' => 'Linie', 'en_US' => 'Line' ],
	],
	'attributes' => [
		// A choice among the forms there are: the list is a method of the class (Forms::options()), the empty word is the first form
		'key' => [ 'type' => 'choice', 'options' => '\\Nino\\Modules\\Forms::options', 'default' => '', 'class' => false,
			'label' => [ 'de_DE' => 'Formular', 'en_US' => 'Form' ],
			'hint' => [ 'de_DE' => 'Das Formular, das gezeichnet wird. Das erste Formular steht oben im Panel Formulare.', 'en_US' => 'The form to draw. The first form is the one at the top of the Forms panel.' ] ],
	],
];
