<?php
// The class is not declared here: the folder can only serve \Nino\Modules\CrmSurfaceProbeTwo. A second package of the suite of the surface, made in the sandbox of the suite and never a feature of the catalogue
return [
	'key'					=> 'crm-surface-probe-two',
	'name'				=> [ 'en_US' => 'CRM surface probe two', 'de_DE' => 'CRM-Oberflächen-Probe zwei' ],
	'description'	=> [
		'en_US' => 'A second package of the suite of the surface: it enters nothing, so that the suite can say what a package may not take from the folder, the pages and the data of another. Never a feature of the catalogue.',
		'de_DE' => 'Ein zweites Paket der Suite der Oberfläche: Es trägt nichts ein, damit die Suite sagen kann, was ein Paket aus dem Ordner, den Seiten und den Daten eines anderen nicht nehmen darf. Nie ein Feature des Katalogs.',
	],
	'manual'			=> [ 'shortcodes' => [], 'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [] ],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'crm' ],
	'data'				=> [],
	'settings'		=> [],
];
