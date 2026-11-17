<?php
declare(strict_types=1);

// The banner. It has no `in`: no tool offers it and no page writes it - the output phase draws it (Consent::callbackBanner())
// and the theme chooses its look, the variant that stands where nobody names one
return [
	'group'      => 'consent',
	'label'      => [ 'de_DE' => 'Einwilligungs-Banner', 'en_US' => 'Consent banner' ],
	'icon'       => 'text',
	'preview'    => 'block',
	'variants'   => [ 'box', 'dialog' ],
	'labels'     => [
		'regular' => [ 'de_DE' => 'Leiste', 'en_US' => 'Bar' ],
		'box'    => [ 'de_DE' => 'Kasten', 'en_US' => 'Box' ],
		'dialog' => [ 'de_DE' => 'Dialog', 'en_US' => 'Dialog' ],
	],
	'attributes' => [
		'inline' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'In der Seite', 'en_US' => 'In the page' ], 'hint' => [ 'de_DE' => 'Zeigt das Banner dort, wo es steht, statt am Rand des Fensters - für eine Vorschau.', 'en_US' => 'Shows the banner where it stands instead of at the edge of the window - for a preview.' ] ],
	],
];
