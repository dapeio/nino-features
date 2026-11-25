<?php
// The class is not declared here: the folder can only serve \Nino\Modules\CrmProbe. A package of the suite of the core, made in the sandbox of the suite and never a
// feature of the catalogue: it docks to every place the core offers, to show that a package can, without the core naming it
return [
	'key'					=> 'crm-probe',
	'name'				=> [ 'en_US' => 'CRM probe', 'de_DE' => 'CRM-Probe' ],
	'description'	=> [
		'en_US' => 'A package that docks to every place of the core: a schema, a setting, a number sequence, a line of the status, a shared path, the words of its mails and the band of its mails.',
		'de_DE' => 'Ein Paket, das an jeder Stelle des Kerns andockt: Schema, Einstellung, Nummernkreis, Zeile des Zustands, geteilter Pfad, die Worte seiner Mails und das Band seiner Mails.',
	],
	'manual'			=> [ 'shortcodes' => [], 'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [] ],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'crm' ],
	'data'				=> [],
	'settings'		=> [],
];
