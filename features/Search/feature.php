<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Search/ can only ever serve
// \Nino\Modules\Search.
return [
	'key'					=> 'search',
	'name'				=> [ 'en_US' => 'Elements search', 'de_DE' => 'Elemente-Suche' ],
	'description'	=> [
		'en_US' => 'A locale-aware fuzzy search index over configured Element fields, rebuilt on every save and from the Search panel. Configure the indexed types under /nino/elements/index in config.php.',
		'de_DE' => 'Ein sprachbewusster unscharfer Suchindex über konfigurierte Elementfelder, neu gebaut bei jedem Speichern und aus dem Panel Suche. Die indizierten Typen stehen unter /nino/elements/index in config.php.',
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^1.0',
	'requires'		=> [],
	'settings'		=> [],
	// The index files are derived from the Elements and rebuilt on demand -
	// not data a backup has to carry
	'data'				=> [],
];
