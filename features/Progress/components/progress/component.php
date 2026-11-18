<?php
declare(strict_types=1);

return [
	'group'      => 'progress',
	'label'      => [ 'de_DE' => 'Lesefortschritt', 'en_US' => 'Reading progress' ],
	'icon'       => 'gauge',
	'in'         => [ 'page', 'flow' ],
	'preview'    => 'block',
	'attributes' => [
		'of'    => [ 'type' => 'string', 'default' => '#article', 'label' => [ 'de_DE' => 'Gemessen wird', 'en_US' => 'What is measured' ], 'hint' => [ 'de_DE' => 'Ein CSS-Selektor des Elements, dessen Lesen der Balken misst, etwa #article. Leer misst die ganze Seite.', 'en_US' => 'A CSS selector of the element whose reading the bar measures, such as #article. Empty measures the whole page.' ] ],
		'label' => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Beschriftung', 'en_US' => 'Label' ], 'hint' => [ 'de_DE' => 'Wie ein Screenreader den Balken nennt. Leer versteckt den Balken vor Screenreadern.', 'en_US' => 'What a screen reader calls the bar. Empty hides the bar from screen readers.' ] ],
	],
];
