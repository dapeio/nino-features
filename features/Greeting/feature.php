<?php
// features/Greeting/feature.php - what the Features panel reads. The class is
// not declared here: features/Greeting/ can only ever serve \Nino\Modules\Greeting.
return [
	'key'					=> 'greeting',
	'name'				=> [ 'en_US' => 'Greeting', 'de_DE' => 'Begrüßung' ],
	'description'	=> [
		'en_US' => 'Greeting opens the Dashboard of the workbench with a personal card: a greeting for the time of day with the name of the account, a sentence picked at random and a few facts about the last visit. Each of the three lines can be switched off on its own, and the sentences are an element type that can be edited.',
		'de_DE' => 'Die Begrüßung eröffnet das Dashboard der Workbench mit einer persönlichen Karte: ein Gruß zur Tageszeit mit dem Namen des Kontos, ein zufälliger Satz und ein paar Angaben zum letzten Besuch. Jede der drei Zeilen lässt sich einzeln abschalten, und die Sätze sind ein Elementtyp, den Du bearbeiten kannst.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Greeting' => [
				'en_US' => 'Switches the three lines of the card on and off, behind the permission /_admin/greeting/manage; the card itself stands at the head of the Dashboard.',
				'de_DE' => 'Schaltet die drei Zeilen der Karte ein und aus, hinter dem Recht /_admin/greeting/manage; die Karte selbst steht am Kopf des Dashboards.',
			],
		],
		'callbacks' => [
			'/nino/admin/dashboard/head' => [
				'en_US' => 'Adds the card to the head of the Dashboard, for every account that opens it, with the facts and the sentences the script draws it from.',
				'de_DE' => 'Fügt die Karte dem Kopf des Dashboards hinzu, für jedes Konto, das es öffnet, mit den Angaben und Sätzen, aus denen das Skript sie zeichnet.',
			],
		],
		'install' => [
			'elements/greetings.php' => [
				'en_US' => 'The sentences of the card, an element type put in place by the first request of the Dashboard where the project has none; a sentence an editor changed, hid or deleted stays as it is.',
				'de_DE' => 'Die Sätze der Karte, ein Elementtyp, den die erste Anfrage des Dashboards anlegt, wo das Projekt keinen hat; ein Satz, den jemand geändert, ausgeblendet oder gelöscht hat, bleibt, wie er ist.',
			],
		],
	],
	'category'		=> 'system',
	'version'			=> '1.0.0',
	'nino'				=> '^1.12',
	'requires'		=> [],
	// The three switches are keys of config.php and the sentences are elements: nothing under data/
	'data'				=> [],
	'settings'		=> [],
];
