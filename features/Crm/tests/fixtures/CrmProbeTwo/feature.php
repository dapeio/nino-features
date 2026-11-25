<?php
// The class is not declared here: the folder can only serve \Nino\Modules\CrmProbeTwo. A second package of the suite of the core, a feature that is on and nothing more: it
// is the key the suite enters schemas, words and the like under to show what a package may not enter under the key of another
return [
	'key'					=> 'crm-probe-two',
	'name'				=> [ 'en_US' => 'CRM probe two', 'de_DE' => 'CRM-Probe zwei' ],
	'description'	=> [
		'en_US' => 'A second package of the suite of the core, which enters nothing by itself.',
		'de_DE' => 'Ein zweites Paket der Prüfung des Kerns, das von sich aus nichts anmeldet.',
	],
	'manual'			=> [ 'shortcodes' => [], 'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [] ],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'crm' ],
	'data'				=> [],
	'settings'		=> [],
];
