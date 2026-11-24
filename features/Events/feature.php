<?php
// features/Events/feature.php - what the Features panel reads. The class is
// not declared: features/Events/ can only ever serve \Nino\Modules\Events.
return [
	'key'					=> 'events',
	'name'				=> [ 'en_US' => 'Events', 'de_DE' => 'Termine' ],
	'description'	=> [
		'en_US' => 'Dates as elements of the project: a list component that shows what is coming, a chip that writes a date in the language of the page, a link that adds one to a calendar app and a feed of the public dates. The feed is for everyone, or for a group of members where the Members feature is on. Dates are shown and never booked.',
		'de_DE' => 'Termine als Elemente des Projekts: eine Listen-Komponente, die zeigt, was ansteht, ein Chip, der ein Datum in der Sprache der Seite schreibt, ein Link, der einen Termin in eine Kalender-App bringt, und ein Feed der öffentlichen Termine. Der Feed ist für alle da, oder für eine Gruppe von Mitgliedern, wo das Feature Mitglieder an ist. Termine werden angezeigt und nie gebucht.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[events]…[/events]' => [
				'en_US' => 'Draws what it holds once for each date that is coming, soonest first, and says so in one line where there is none; a date with a group is listed for the members of that group only.',
				'de_DE' => 'Zeichnet, was zwischen den Marken steht, für jeden anstehenden Termin, den nächsten zuerst, und sagt in einer Zeile, wo es keinen gibt; ein Termin mit Gruppe wird nur deren Mitgliedern gelistet.',
			],
			'[events scope="past" order="desc"]' => [
				'en_US' => 'scope is upcoming (the default), past or all, and order asc starts with the date nearest to now, desc turns it around.',
				'de_DE' => 'scope ist upcoming (Standard), past oder all, und order asc beginnt mit dem Termin, der Jetzt am nächsten liegt, desc dreht es um.',
			],
			'[events limit="3" offset="1" query="place=Hall"]' => [
				'en_US' => 'limit is the number of dates (0 is all of them) and counts what this visitor sees, offset skips some and query narrows by a field.',
				'de_DE' => 'limit ist die Zahl der Termine (0 sind alle) und zählt, was dieser Besucher sieht, offset überspringt einige und query grenzt über ein Feld ein.',
			],
			'[events][section-col][event-when .start end="end"][section-title .title][event-ics .slug][/section-col][/events]' => [
				'en_US' => 'Inside a list the components of Basis read the fields of the date, .title, .place, .text and .link, and .date is its start for a block; a column in it is drawn once for each date.',
				'de_DE' => 'In einer Liste lesen die Komponenten von Basis die Felder des Termins, .title, .place, .text und .link, und .date ist sein Beginn für einen Block; eine Spalte darin wird je Termin gezeichnet.',
			],
			'[elements /events]' => [
				'en_US' => 'Another loop over /events shows every date, the dates of a group too, because it knows nothing of groups; only [events] keeps them for their members.',
				'de_DE' => 'Eine andere Schleife über /events zeigt jeden Termin, auch die einer Gruppe, weil sie nichts von Gruppen weiß; nur [events] hält sie für ihre Mitglieder zurück.',
			],
			'[event-when .start end="end"]' => [
				'en_US' => 'The start of the date and, where end names the field that holds the end, its range, as a time element in the language of the page with Today and Tomorrow in words.',
				'de_DE' => 'Der Beginn des Termins und, wo end das Feld mit dem Ende nennt, seine Spanne, als time-Element in der Sprache der Seite, mit Heute und Morgen in Worten.',
			],
			'[event-when .start show="date"]' => [
				'en_US' => 'show is datetime (the default), date (the day alone) or time (the time alone); a date at midnight with no end is a day and shows no time unless show is time.',
				'de_DE' => 'show ist datetime (Standard), date (nur der Tag) oder time (nur die Uhrzeit); ein Termin um Mitternacht ohne Ende ist ein Tag und zeigt keine Uhrzeit, außer show ist time.',
			],
			'[event-ics .slug]' => [
				'en_US' => 'A link that adds one date to a calendar app, /.events/<slug>.ics; nothing for a date the visitor may not see, for one that does not exist and for a name that is no slug.',
				'de_DE' => 'Ein Link, der einen Termin in eine Kalender-App bringt, /.events/<slug>.ics; nichts für einen Termin, den der Besucher nicht sehen darf, den es nicht gibt oder dessen Name kein Slug ist.',
			],
			'[event-feed]' => [
				'en_US' => 'A link to the feed, with the token of the member who is signed in, which adds the dates of their group; nothing while the setting feed is off.',
				'de_DE' => 'Ein Link zum Feed, mit dem Token des angemeldeten Mitglieds, der die Termine seiner Gruppe hinzufügt; nichts, solange die Einstellung feed aus ist.',
			],
		],
		'markup' => [
			'<time class="event-when">' => [
				'en_US' => 'The chip, with event-when--today and event-when--past, a datetime attribute and the text; the links are a.event-ics and a.event-feed and the line for nothing to list is p.events-empty.',
				'de_DE' => 'Der Chip, mit event-when--today und event-when--past, einem Attribut datetime und dem Text; die Links sind a.event-ics und a.event-feed und die Zeile für nichts zu Listendes ist p.events-empty.',
			],
			'Custom properties' => [
				'en_US' => 'The knobs of the group move the chip through --event-when-space and --event-when-radius, and the rest is drawn from the tokens of the page.',
				'de_DE' => 'Die Regler der Gruppe bewegen den Chip über --event-when-space und --event-when-radius, und der Rest wird aus den Tokens der Seite gezeichnet.',
			],
			'Timezone' => [
				'en_US' => 'The times of a date are wall-clock times read in the zone of the setting timezone, written by the chip in that zone and converted from it to UTC in the calendar files.',
				'de_DE' => 'Die Zeiten eines Termins sind Uhrzeiten, gelesen in der Zone der Einstellung Zeitzone, vom Chip in dieser Zone geschrieben und in den Kalenderdateien von ihr in UTC umgerechnet.',
			],
			'Restore' => [
				'en_US' => 'The index of the feed tokens in /data/events.php is regenerable and has no callback of /nino/admin/restore, so an old link that a restored file does not know opens the public feed.',
				'de_DE' => 'Der Index der Feed-Tokens in /data/events.php lässt sich neu erzeugen und hat keinen Restore-Callback; ein Link, den die wiederhergestellte Datei nicht kennt, öffnet den öffentlichen Feed.',
			],
			'Page cache' => [
				'en_US' => 'A page with a list, a chip or a link is kept by the page cache of the kernel where it is on, so Today and the classes of the chip are as they were when the page was drawn.',
				'de_DE' => 'Eine Seite mit einer Liste, einem Chip oder einem Link hält der Seiten-Cache des Kernels, wo er an ist, so dass Heute und die Klassen des Chips so dastehen, wie beim Zeichnen der Seite.',
			],
			'No personal data' => [
				'en_US' => 'Nothing is stored about a visitor, and the one thing the feature writes is the token of a member\'s feed link, a hash tied to the account, in /data/events.php.',
				'de_DE' => 'Über einen Besucher wird nichts gespeichert, und das Einzige, was das Feature schreibt, ist der Token des Feed-Links eines Mitglieds, ein an das Konto gebundener Hash, in /data/events.php.',
			],
		],
		'routes' => [
			'GET /.events.ics' => [
				'en_US' => 'The feed for a calendar app, text/calendar, cached for an hour: one VEVENT for each public date within the settings ahead and past, and a 404 while the setting feed is off.',
				'de_DE' => 'Der Feed für eine Kalender-App, text/calendar, eine Stunde gecacht: ein VEVENT je öffentlichem Termin innerhalb der Einstellungen ahead und past, und ein 404, solange die Einstellung feed aus ist.',
			],
			'GET /.events.ics?k=<token>' => [
				'en_US' => 'The feed of a member: the public dates and those of the group of the account the token was made for, and the public feed for a token that is none or whose account is gone.',
				'de_DE' => 'Der Feed eines Mitglieds: die öffentlichen Termine und die der Gruppe des Kontos, für das der Token gemacht wurde, und der öffentliche Feed für einen Token, der keiner ist oder dessen Konto weg ist.',
			],
			'GET /.events/<slug>.ics' => [
				'en_US' => 'One date as a calendar file, and the same 404 for a date that is not there and for a date of a group the visitor is not in.',
				'de_DE' => 'Ein Termin als Kalenderdatei, und derselbe 404 für einen Termin, den es nicht gibt, und für einen Termin einer Gruppe, in der der Besucher nicht ist.',
			],
		],
		'panel' => [
			'Elements: Events' => [
				'en_US' => 'The feature has no panel of its own: the dates are elements of the type Events, entered in the Elements panel with a title, a start, an end, a place, a text, a link and a group.',
				'de_DE' => 'Das Feature hat kein eigenes Panel: Die Termine sind Elemente des Typs Events und werden im Panel Elemente erfasst, mit Titel, Beginn, Ende, Ort, Text, Link und Gruppe.',
			],
		],
		'callbacks' => [
			'/events/organizer' => [
				'en_US' => 'Asked once for each calendar file, with name, email, phone, address, url and the account the file is for, to say whom the dates are with; a listener fills what it knows.',
				'de_DE' => 'Wird je Kalenderdatei einmal gefragt, mit name, email, phone, address, url und dem Konto, für das die Datei ist, um zu sagen, bei wem die Termine sind; ein Lauscher füllt, was er weiß.',
			],
			'/members/removed' => [
				'en_US' => 'Listened to, so that the feed token of an account that is removed is forgotten and its link opens nothing.',
				'de_DE' => 'Wird gehört, damit der Feed-Token eines Kontos, das entfernt wird, vergessen wird und sein Link nichts mehr öffnet.',
			],
		],
		'install' => [
			'events.php' => [
				'en_US' => 'The element type /events, if the project has none: title, start (required), end, place, text, link and group, added without overwriting what is there.',
				'de_DE' => 'Der Elementtyp /events, falls das Projekt keinen hat: title, start (Pflicht), end, place, text, link und group, ohne zu überschreiben, was da ist.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The words of the chip, of the links, of the name of the calendar, of the organizer in a calendar file and of the line a list writes for nothing to list, into the Text panel.',
				'de_DE' => 'Die Worte des Chips, der Links, des Kalendernamens, des Betreibers in einer Kalenderdatei und der Zeile, die eine Liste für nichts zu Listendes schreibt, ins Panel Texte.',
			],
			'blocks/events-list/' => [
				'en_US' => 'One block: a headline and the next three dates as cards, each with its chip, title and text, and the link to the calendar feed under them.',
				'de_DE' => 'Ein Block: eine Überschrift und die nächsten drei Termine als Karten, je mit Chip, Titel und Text, und darunter der Link zum Kalender-Feed.',
			],
			'No privacy section' => [
				'en_US' => 'Not shipped, because the feature stores nothing about a visitor.',
				'de_DE' => 'Wird nicht mitgeliefert, weil das Feature nichts über einen Besucher speichert.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	// Basis: the cells of a list are columns and the fields of a date are drawn by its text and title
	'requires'		=> [ 'basis' ],
	// The feed tokens of members. The dates are ordinary elements and live in /elements/, where a backup finds them
	'data'				=> [ '/data/events.php' ],
	'assets'			=> [],
	'settings'		=> [
		'ahead' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Days ahead', 'de_DE' => 'Tage voraus' ],
			'hint'		=> [
				'en_US' => 'How far into the future the feed and the upcoming dates of a list look.',
				'de_DE' => 'Wie weit in die Zukunft der Feed und die anstehenden Termine einer Liste sehen.',
			],
			'min'			=> 1,
			'max'			=> 730,
			'unit'		=> 'days',
			'default'	=> 365,
		],
		'past' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Days back', 'de_DE' => 'Tage zurück' ],
			'hint'		=> [
				'en_US' => 'How far back the past dates of a list and the feed reach, counted from the end of a date. 0 is as far as there are any for a list; the feed reads 0 as 365 days, so that it does not grow for ever.',
				'de_DE' => 'Wie weit die vergangenen Termine einer Liste und der Feed zurückreichen, vom Ende eines Termins gerechnet. 0 ist bei einer Liste so weit, wie es welche gibt; der Feed liest 0 als 365 Tage, damit er nicht für immer wächst.',
			],
			'min'			=> 0,
			'max'			=> 3650,
			'unit'		=> 'days',
			'default'	=> 0,
		],
		'feed' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Feed', 'de_DE' => 'Feed' ],
			'hint'		=> [
				'en_US' => 'Whether /.events.ics answers, for the public and for the members. Off, it is a 404 and [event-feed] shows nothing; the file of a single date, /.events/<slug>.ics, stays.',
				'de_DE' => 'Ob /.events.ics antwortet, für die Öffentlichkeit und für die Mitglieder. Aus, ist es ein 404 und [event-feed] zeigt nichts; die Datei eines einzelnen Termins, /.events/<slug>.ics, bleibt.',
			],
			'default'	=> true,
		],
		'name' => [
			'type'		=> 'string',
			'label'		=> [ 'en_US' => 'Name of the calendar', 'de_DE' => 'Name des Kalenders' ],
			'hint'		=> [
				'en_US' => 'What a calendar app calls the feed. Empty takes the name of the company.',
				'de_DE' => 'Wie eine Kalender-App den Feed nennt. Leer nimmt den Namen der Firma.',
			],
			'maxlength'	=> 80,
			'default'		=> '',
		],
		/*	The zone the dates are read in. 'server' is a value of its own rather than '': a select the Features panel has
			once filled cannot be emptied again. The map is built here without a variable, because Features::manifest(),
			bin/build.php and the tests all include() this file	*/
		'timezone' => [
			'type'			=> 'select',
			'label'			=> [ 'en_US' => 'Timezone', 'de_DE' => 'Zeitzone' ],
			'hint'			=> [
				'en_US' => 'The zone the dates of the Elements panel are read in: the feed converts from it to UTC and the chip writes in it. "As the server is set" is the zone php runs in.',
				'de_DE' => 'Die Zone, in der die Termine aus dem Panel Elemente gelesen werden: Der Feed rechnet von ihr in UTC um und der Chip schreibt in ihr. „Wie der Server eingestellt ist“ ist die Zone, in der php läuft.',
			],
			'options'		=> [ 'server' => [ 'en_US' => 'As the server is set (PHP default)', 'de_DE' => 'Wie der Server eingestellt ist (PHP-Standard)' ] ]
				+ ( static fn( array $ids ): array => array_combine( $ids, $ids ) )( \DateTimeZone::listIdentifiers() ),
			'required'	=> true,
			'default'		=> 'server',
		],
	],
];
