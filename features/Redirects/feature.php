<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Redirects/ can only ever serve
// \Nino\Modules\Redirects.
return [
	'key'					=> 'redirects',
	'name'				=> [ 'en_US' => 'Redirects', 'de_DE' => 'Weiterleitungen' ],
	'description'	=> [
		'en_US' => 'Old addresses that still work: one rule per page or per subtree, 301 or 302, applied only where nothing else answers - and a list of the addresses nothing answered, so the rules worth writing can be read rather than guessed.',
		'de_DE' => 'Alte Adressen, die weiter funktionieren: eine Regel je Seite oder je Teilbaum, 301 oder 302, angewendet nur dort, wo sonst nichts antwortet - und eine Liste der Adressen, die nichts beantwortet hat, damit die lohnenden Regeln ablesbar sind statt geraten.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Redirects' => [
				'en_US' => 'The rules, and the addresses nothing answered - each with the one button that turns it into a rule.',
				'de_DE' => 'Die Regeln und die Adressen, die nichts beantwortet hat - jede mit dem einen Knopf, der eine Regel daraus macht.',
			],
		],
		'callbacks' => [
			'/nino/http/response' => [
				'en_US' => 'Answers an address no route has, or remembers that nothing did.',
				'de_DE' => 'Beantwortet eine Adresse, die keine Route hat, oder merkt sich, dass nichts sie beantwortet hat.',
			],
		],
		'install' => [],
	],
	'category'		=> 'system',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	'settings'		=> [
		'record' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Remember what happened', 'de_DE' => 'Merken, was passiert ist' ],
			'hint'		=> [
				'en_US' => 'The addresses nothing answered, and how often each rule was followed. One file write per request that would otherwise have touched nothing - switch it off on a site under heavy crawling, and the panel keeps what it already has.',
				'de_DE' => 'Die Adressen, die nichts beantwortet hat, und wie oft jeder Regel gefolgt wurde. Ein Dateischreibvorgang je Anfrage, die sonst nichts angefasst hätte - auf einer stark gecrawlten Site abschaltbar, das Panel behält dann, was schon da ist.',
			],
			'default'	=> true,
		],
	],
	// The rules are the one thing here that cannot be worked out again from
	// what is on disk - and the list of unanswered addresses is a month of
	// looking that a restore should not have to repeat
	'data'				=> [ '/data/redirects.php' ],
];
