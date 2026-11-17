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
				'en_US' => 'Shows the card as the Dashboard draws it for your account, behind the permission /_admin/greeting/manage; its Settings tab switches the three lines of the card on and off.',
				'de_DE' => 'Zeigt die Karte so, wie das Dashboard sie für Dein Konto zeichnet, hinter dem Recht /_admin/greeting/manage; sein Reiter Einstellungen schaltet die drei Zeilen der Karte ein und aus.',
			],
		],
		'callbacks' => [
			'/nino/admin/dashboard/head' => [
				'en_US' => 'Adds the card to the head of the Dashboard, for every account that opens it, with the facts and the sentences the script draws it from.',
				'de_DE' => 'Fügt die Karte dem Kopf des Dashboards hinzu, für jedes Konto, das es öffnet, mit den Angaben und Sätzen, aus denen das Skript sie zeichnet.',
			],
		],
		'install' => [
			'config.php /nino/admin/greeting/*' => [
				'en_US' => 'The three switches the feature kept in config.php in Nino 1 are not read; they stay there unused, and the three settings of the feature are on.',
				'de_DE' => 'Die drei Schalter, die das Feature in Nino 1 in der config.php hielt, werden nicht gelesen; sie bleiben dort ungenutzt, und die drei Einstellungen des Features sind an.',
			],
			'elements/greetings.php' => [
				'en_US' => 'The sentences of the card, an element type put in place by the first request of the Dashboard where the project has none; a sentence an editor changed, hid or deleted stays as it is.',
				'de_DE' => 'Die Sätze der Karte, ein Elementtyp, den die erste Anfrage des Dashboards anlegt, wo das Projekt keinen hat; ein Satz, den jemand geändert, ausgeblendet oder gelöscht hat, bleibt, wie er ist.',
			],
		],
	],
	'category'		=> 'system',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// The sentences are elements and the three lines are settings: nothing under data/
	'data'				=> [],
	'settings'		=> [
		'hello'			=> [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Greeting with the name', 'de_DE' => 'Gruß mit dem Namen' ],
			'hint'		=> [ 'en_US' => 'The large line: the greeting for the time of day and the name the account carries under Users.', 'de_DE' => 'Die große Zeile: der Gruß zur Tageszeit und der Name, den das Konto unter Benutzer trägt.' ],
			'default'	=> true,
		],
		'sentence'	=> [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Sentence of the day', 'de_DE' => 'Satz des Tages' ],
			'hint'		=> [ 'en_US' => 'A random sentence for the hour, the weekday or the occasion. The sentences are the element type Greetings and can be changed there.', 'de_DE' => 'Ein zufälliger Satz zur Stunde, zum Wochentag oder zum Anlass. Die Sätze sind der Elementtyp Greetings und lassen sich dort ändern.' ],
			'default'	=> true,
		],
		'facts'			=> [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Facts about the last visit', 'de_DE' => 'Angaben zum letzten Besuch' ],
			'hint'		=> [ 'en_US' => 'The small line: when you were last here, what the log has recorded since and how old the last backup is.', 'de_DE' => 'Die kleine Zeile: wann Du zuletzt hier warst, was das Protokoll seitdem verzeichnet hat und wie alt die letzte Sicherung ist.' ],
			'default'	=> true,
		],
	],
];
