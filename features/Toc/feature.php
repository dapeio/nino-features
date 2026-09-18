<?php
// features/Toc/feature.php - what the Features panel reads. The class is not
// declared here: features/Toc/ can only ever serve \Nino\Modules\Toc.
return [
	'key'					=> 'toc',
	'name'				=> [ 'en_US' => 'Table of Contents', 'de_DE' => 'Inhaltsverzeichnis' ],
	'description'	=> [
		'en_US' => 'A list of a long page\'s own headings, built from the page as it stands, that says which section is being read - and an anchor on every heading, so a passage can be linked to.',
		'de_DE' => 'Eine Liste der eigenen Überschriften einer langen Seite, aus der Seite selbst gebaut, die zeigt, welcher Abschnitt gerade gelesen wird - und ein Anker an jeder Überschrift, damit sich eine Stelle verlinken lässt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[toc]' => [
				'en_US' => 'The list, built from the headings that follow it on the page.',
				'de_DE' => 'Die Liste, gebaut aus den Überschriften, die ihr auf der Seite folgen.',
			],
			'[toc levels="2"]' => [
				'en_US' => 'Only h2. Default is h2 and h3.',
				'de_DE' => 'Nur h2. Vorgabe sind h2 und h3.',
			],
			'[toc within="#content"]' => [
				'en_US' => 'Take the headings from inside one element rather than from the whole page.',
				'de_DE' => 'Nimmt die Überschriften aus einem Element statt von der ganzen Seite.',
			],
			'[toc title="Auf dieser Seite"]' => [
				'en_US' => 'What stands over the list. Without it the Text panel\'s own words are used.',
				'de_DE' => 'Was über der Liste steht. Ohne die Angabe werden die Wörter aus dem Panel Texte verwendet.',
			],
		],
		'markup' => [
			'class="nino-toc-skip"' => [
				'en_US' => 'On a heading: left out of the list, and given no anchor.',
				'de_DE' => 'An einer Überschrift: bleibt aus der Liste und bekommt keinen Anker.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The two words the list and its anchors carry, into the Text panel.',
				'de_DE' => 'Die zwei Wörter der Liste und ihrer Anker, ins Panel Texte.',
			],
		],
	],
	// It is a way through what is already written on a page, which is the
	// thing the Features panel files under content
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	// Nothing under data/: the list is the page, read as it stands
	'data'				=> [],
	'settings'		=> [
		'anchors' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Anchors on every heading', 'de_DE' => 'Anker an jeder Überschrift' ],
			'hint'		=> [
				'en_US' => 'Put a link on every heading of a page that has a list, so a passage can be linked to. Off: only the list\'s own headings get one, and only so the list can reach them.',
				'de_DE' => 'Setzt an jede Überschrift einer Seite mit Liste einen Link, damit sich eine Stelle verlinken lässt. Aus: Nur die Überschriften der Liste bekommen einen, und nur damit die Liste sie erreicht.',
			],
			'default'	=> true,
		],
	],
];
