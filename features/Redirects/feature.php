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
				'en_US' => 'The rules, and the addresses nothing answered - each with the one button that turns it into a rule. A target can be picked from the pages of the site, and one nothing answers is flagged and warned about, never refused.',
				'de_DE' => 'Die Regeln und die Adressen, die nichts beantwortet hat - jede mit dem einen Knopf, der eine Regel daraus macht. Ein Ziel lässt sich aus den Seiten der Site wählen, und eines, das nichts beantwortet, wird markiert und beim Speichern gemeldet, aber nie abgelehnt.',
			],
			'Redirects: a rule' => [
				'en_US' => 'Old address, sends to (a path of this site or an https address), kind (301 or 302) and whether everything below it moves too. A rule is consulted only where no route answers; GET and HEAD only.',
				'de_DE' => 'Alte Adresse, Ziel (ein Pfad dieser Seite oder eine https-Adresse), Art (301 oder 302) und ob alles darunter mitzieht. Eine Regel gilt nur, wo keine Route antwortet; nur GET und HEAD.',
			],
			'Probe' => [
				'en_US' => 'Asks what a visitor would meet at an address: a page answers it, a rule sends it on, or nothing does and the 404 page is shown.',
				'de_DE' => 'Fragt, was ein Besucher unter einer Adresse träfe: Eine Seite antwortet, eine Regel leitet weiter, oder nichts antwortet und die 404-Seite erscheint.',
			],
			'Addresses with no answer' => [
				'en_US' => 'The second screen: the paths nothing answered, most asked for first, at most 50, with one button that opens the editor with the address filled in. The path only - no visitor is recorded.',
				'de_DE' => 'Der zweite Bildschirm: die Pfade, auf die nichts antwortete, die häufigsten zuerst, höchstens 50, mit einer Schaltfläche, die den Editor mit der Adresse öffnet. Nur der Pfad – kein Besucher wird erfasst.',
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
	'nino'				=> '^1.4',
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
