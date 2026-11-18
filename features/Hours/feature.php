<?php
// features/Hours/feature.php - what the Features panel reads. The class is not
// declared here: features/Hours/ can only ever serve \Nino\Modules\Hours.
return [
	'key'					=> 'hours',
	'name'				=> [ 'en_US' => 'Opening hours', 'de_DE' => 'Öffnungszeiten' ],
	'description'	=> [
		'en_US' => 'Opening hours shows when one or several places are open: a week with the days of equal hours put together, the days that deviate, and one line for today. The places and the deviations are elements, which the editors keep under Elements.',
		'de_DE' => 'Öffnungszeiten zeigt, wann ein oder mehrere Standorte geöffnet sind: eine Woche mit zusammengefassten Tagen gleicher Zeiten, die abweichenden Tage und eine Zeile für heute. Die Standorte und die Abweichungen sind Elemente, die die Redaktion unter Elemente pflegt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[hours]' => [
				'en_US' => 'Draws the week of the one location there is, or of every location one below the other with its name, then the closures of the next 60 days and the note.',
				'de_DE' => 'Zeichnet die Woche des einen Standorts oder aller Standorte untereinander mit Namen, danach die Abweichungen der nächsten 60 Tage und den Hinweis.',
			],
			'[hours location="/locations/main" ahead="30" address name="0" closures="0"]' => [
				'en_US' => 'Names the location, the days ahead for the closures, and whether the name, the address and the closures are drawn; the Builder offers it as a block.',
				'de_DE' => 'Benennt den Standort, die Tage voraus für die Abweichungen und ob Name, Adresse und Abweichungen gezeichnet werden; der Builder bietet es als Block an.',
			],
			'[collection /locations][hours][/collection]' => [
				'en_US' => 'Inside a loop over the type locations, an empty location takes the location of each element.',
				'de_DE' => 'In einer Schleife über den Typ locations nimmt ein leerer Standort den Standort jedes Elements.',
			],
			'[hours-today location="/locations/main" name]' => [
				'en_US' => 'Draws one line for today, a closure counted in: the times, or Closed with the reason; without a location the first one, with name its name in front.',
				'de_DE' => 'Zeichnet eine Zeile für heute, eine Abweichung eingerechnet: die Zeiten, oder Geschlossen mit dem Grund; ohne Standort der erste, mit name mit seinem Namen davor.',
			],
		],
		'markup' => [
			'<div class="hours" data-location>' => [
				'en_US' => 'One location: a table with the name as its caption and a row for each run of days with equal hours, a list of closures and the note.',
				'de_DE' => 'Ein Standort: eine Tabelle mit dem Namen als Überschrift und einer Zeile für jede Folge von Tagen mit gleichen Zeiten, eine Liste der Abweichungen und der Hinweis.',
			],
			'aria-current="date" data-today' => [
				'en_US' => 'The row that holds today carries the attribute and the word Today, so that the day is marked in more than colour.',
				'de_DE' => 'Die Zeile mit dem heutigen Tag trägt das Attribut und das Wort Heute, der Tag ist also nicht nur durch Farbe markiert.',
			],
			'<time datetime>' => [
				'en_US' => 'Every time of day and every date of a closure is a time element with the machine-readable value.',
				'de_DE' => 'Jede Uhrzeit und jedes Datum einer Abweichung ist ein time-Element mit dem maschinenlesbaren Wert.',
			],
			'script type="application/ld+json"' => [
				'en_US' => 'A Place with an OpeningHoursSpecification for each time of the week and each listed closure, once for each location on a page; every angle bracket in it is an escape, so no text can end the element.',
				'de_DE' => 'Ein Place mit einer OpeningHoursSpecification für jede Zeit der Woche und jede aufgelistete Abweichung, einmal je Standort und Seite; jede spitze Klammer darin ist ein Escape, kein Text beendet es.',
			],
			'--hours-gap, --hours-width, --hours-days' => [
				'en_US' => 'The space between the week, the closures and the note is a knob of the group; the widest the hours grow and the column of days are tokens a project sets on :root.',
				'de_DE' => 'Der Abstand von Woche, Abweichungen und Hinweis ist ein Regler der Gruppe; die größte Breite und die Spalte der Tage sind Eigenschaften, die ein Projekt auf :root setzt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Elements: locations' => [
				'en_US' => 'A day is written like 9-12, 14-18, also as 9:30 or 9.30 with a hyphen or an en dash, 24 as an end and 18-2 past midnight; empty is closed and what is not readable is refused.',
				'de_DE' => 'Ein Tag wird geschrieben wie 9-12, 14-18, auch als 9:30 oder 9.30 mit Bindestrich oder Halbgeviertstrich, 24 als Ende und 18-2 über Mitternacht; leer ist geschlossen, Unlesbares wird abgelehnt.',
			],
			'Elements: closures' => [
				'en_US' => 'A closure names a location or every one, a first and a last day and the times of those days, empty for closed; the one that names the location wins, then the one that begins later.',
				'de_DE' => 'Eine Abweichung nennt einen Standort oder alle, einen ersten und einen letzten Tag und die Zeiten dieser Tage, leer für geschlossen; es gewinnt die mit Standort, dann die, die später beginnt.',
			],
			'Features: Opening hours' => [
				'en_US' => 'The timezone that today is counted in; a page kept by the Cache module is kept until the next midnight of that timezone at most.',
				'de_DE' => 'Die Zeitzone, in der heute gezählt wird; eine Seite, die das Modul Cache aufbewahrt, bleibt höchstens bis zur nächsten Mitternacht dieser Zeitzone.',
			],
		],
		'callbacks' => [
			'/nino/elements/locations/insert' => [
				'en_US' => 'Vetoes the write of a location whose days cannot be read, and stores the days in their normal form; /update does the same.',
				'de_DE' => 'Legt sein Veto gegen das Schreiben eines Standorts ein, dessen Tage nicht lesbar sind, und speichert die Tage in Normalform; /update tut dasselbe.',
			],
			'/nino/http/output' => [
				'en_US' => 'Adds the name of the field to the answer of a refused save, because the Elements panel marks a field only where the answer carries its name.',
				'de_DE' => 'Fügt der Antwort auf ein abgelehntes Speichern den Feldnamen hinzu, weil das Elements-Panel ein Feld nur markiert, wenn die Antwort seinen Namen trägt.',
			],
			'/nino/elements/closures/insert' => [
				'en_US' => 'Vetoes the write of a closure with a date that does not exist, an end before its beginning, unreadable times or an unknown location; /update does the same.',
				'de_DE' => 'Legt sein Veto gegen das Schreiben einer Abweichung mit einem Datum, das es nicht gibt, einem Ende vor dem Beginn, unlesbaren Zeiten oder unbekanntem Standort ein; /update tut dasselbe.',
			],
		],
		'install' => [
			'locations.php, closures.php' => [
				'en_US' => 'The element types locations and closures, copied to /elements where the project has none, with no element in them.',
				'de_DE' => 'Die Elementtypen locations und closures, nach /elements kopiert, wo das Projekt keine hat, ohne ein Element darin.',
			],
			'blocks/hours-week/' => [
				'en_US' => 'A block: a headline and the week with the address.',
				'de_DE' => 'Ein Block: eine Überschrift und die Woche mit der Adresse.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The names of the days, the words Closed and Today, how a time and a date range are written, the week\'s first day and the labels of the fields, only added, never replaced.',
				'de_DE' => 'Die Namen der Tage, die Wörter Geschlossen und Heute, wie Zeiten und Datumsbereiche geschrieben werden, der erste Tag der Woche und die Feldbeschriftungen, nur hinzugefügt, nie ersetzt.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// The places and the closures are elements, which is where a project keeps them
	'data'				=> [],
	'assets'			=> [],
	/*	One setting, the zone that today is counted in. 'server' is a value of its own rather than '':
		a select the Features panel has once filled cannot be emptied again, and the kernel refuses '' for a
		required one. The map is built here without a variable, because Features::manifest(), bin/build.php and
		the tests all include() this file	*/
	'settings'		=> [
		'timezone' => [
			'type'			=> 'select',
			'label'			=> [ 'en_US' => 'Timezone', 'de_DE' => 'Zeitzone' ],
			'hint'			=> [
				'en_US' => 'The timezone that today and the days of the closures are counted in, by the clock of the server. "As the server is set" takes the timezone of PHP.',
				'de_DE' => 'Die Zeitzone, in der heute und die Tage der Abweichungen gezählt werden, nach der Uhr des Servers. „Wie der Server eingestellt ist“ nimmt die Zeitzone von PHP.',
			],
			'options'		=> [ 'server' => [ 'en_US' => 'As the server is set (PHP default)', 'de_DE' => 'Wie der Server eingestellt ist (PHP-Standard)' ] ]
				+ ( static fn( array $ids ): array => array_combine( $ids, $ids ) )( \DateTimeZone::listIdentifiers() ),
			'required'	=> true,
			'default'		=> 'server',
		],
	],
];
