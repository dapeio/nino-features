<?php
// The class is not declared here: the folder can only serve \Nino\Modules\CrmSurfaceProbe. A package of the suite of the surface, made in the sandbox of the suite and never a
// feature of the catalogue: it docks to every place the surface of the core offers, to show that a package can, without the core naming it
return [
	'key'					=> 'crm-surface-probe',
	'name'				=> [ 'en_US' => 'CRM surface probe', 'de_DE' => 'CRM-Oberflächen-Probe' ],
	'description'	=> [
		'en_US' => 'A package that docks to every place of the surface of the core: its words, a script, a style sheet, an entry of the rail, the data of the page, a page that devices come back to, an app and its icon.',
		'de_DE' => 'Ein Paket, das an jeder Stelle der Oberfläche des Kerns andockt: Worte, Skript, Stil, Eintrag des Menüs, Daten der Seite, eine Seite, zu der Geräte zurückkehren, eine App und ihr Symbol.',
	],
	'manual'			=> [ 'shortcodes' => [], 'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [] ],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'crm' ],
	'data'				=> [],
	'settings'		=> [],
];
