<?php
// The class is not declared here: the folder can only serve \Nino\Modules\CrmPdfProbe. A package of the suite of crm-pdf, made in the sandbox of the suite and never a feature of the catalogue: it
// answers the three callbacks of the PDF feature the way a package that uses templates does (the documents of the CRM will), to show that the screens work with it, without it and with it gone
return [
	'key'					=> 'crm-pdf-probe',
	'name'				=> [ 'en_US' => 'CRM PDF probe', 'de_DE' => 'CRM-PDF-Probe' ],
	'description'	=> [
		'en_US' => 'A package that answers what the PDF feature asks of the packages that use its templates: who uses a template, which tokens and slots a template may use and where the editor is.',
		'de_DE' => 'Ein Paket, das beantwortet, was das Feature PDF den Paketen fragt, die seine Vorlagen nutzen: wer eine Vorlage nutzt, welche Tokens und Bereiche eine Vorlage nutzen darf und wo der Editor ist.',
	],
	'manual'			=> [ 'shortcodes' => [], 'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [] ],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'crm', 'pdf' ],
	'data'				=> [],
	'settings'		=> [],
];
