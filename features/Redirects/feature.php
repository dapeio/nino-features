<?php
// The feature manifest - what the Features panel reads to list, activate
// and update this feature (see \Nino\Features and docs/features.md). The
// class is not declared here: features/Redirects/ can only ever serve
// \Nino\Modules\Redirects.
return [
	'key'					=> 'redirects',
	'name'				=> [ 'en_US' => 'Redirects', 'de_DE' => 'Weiterleitungen' ],
	'description'	=> [
		'en_US' => 'Redirects lets old addresses keep working: one rule per page or per subtree, 301 or 302. They apply only where nothing else answers. A list of the unanswered addresses shows which rules are worth writing.',
		'de_DE' => 'Weiterleitungen ermöglichen, dass alte Adressen weiter funktionieren: eine Regel je Seite oder je Teilbaum, 301 oder 302. Sie gelten nur dort, wo sonst nichts antwortet. Eine Liste der unbeantworteten Adressen zeigt, welche Regeln sich lohnen.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Redirects' => [
				'en_US' => 'Keeps the rules in a table with a search, one rule in a dialog, and lists the addresses nothing answered in a tab, each with a button that makes a rule of it.',
				'de_DE' => 'Pflegt die Regeln in einer Tabelle mit Suche, eine Regel in einem Dialog, und listet die Adressen, die nichts beantwortet hat, in einem Reiter, je mit einem Knopf, der eine Regel daraus macht.',
			],
			'Probe' => [
				'en_US' => 'Asks, in a view of its own, what a visitor would meet at an address: a page, a rule or the 404 page.',
				'de_DE' => 'Fragt in einer eigenen Ansicht, was ein Besucher unter einer Adresse träfe: eine Seite, eine Regel oder die 404-Seite.',
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
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
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
