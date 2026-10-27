<?php
// features/Design/tests/fixtures/features/Probe/feature.php - a feature that exists for the tests of the Design feature and
// brings variants of its own (design/): the smokes copy it into a features directory of their own and list its class as active
return [
	'key'					=> 'probe',
	'name'				=> [ 'en_US' => 'Probe', 'de_DE' => 'Probe' ],
	'description'	=> [ 'en_US' => 'One element with the five kinds of knob a feature can bring to the Design panel.', 'de_DE' => 'Ein Element mit den fünf Reglerarten, die ein Feature in das Design-Panel bringen kann.' ],
	'manual'			=> [
		'shortcodes' => [ '[probe]' => [ 'en_US' => 'The element the variants style.', 'de_DE' => 'Das Element, das die Varianten gestalten.' ] ],
		'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [],
	],
	'category'		=> 'ui',
	'maturity'		=> 'Fixture',
	'version'			=> '1.0.0',
	'nino'				=> '^1.8',
	'requires'		=> [],
	'data'				=> [],
	'assets'			=> [ '/features/Probe/assets/probe.css' ],
	'settings'		=> [],
];
